<?php

/**
 * Copyright 2013 Lengow.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

/**
 * The Lengow Cart Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowCartAbstract extends Cart {

    public static $TOTAL_CART_GET = array(
        'products' ,
        'discounts' , 
        'total' , 
        'total_without_shipping' , 
        'shipping' , 
        'wrapping' , 
        'products_without_shipping' , 
    );

    public $lengow_products = array();
    public $lengow_shipping = 0 ;
    public $lengow_channel = null ;
    public $lengow_fees = 0 ;
    public $tax_calculation_method = PS_TAX_EXC;
    
    /**
     * Current lengow order.
     */
    public static $current_order;

    public function getPackageList($flush = false)
    {
        static $cache = array();
        if (isset($cache[(int)$this->id]) && $cache[(int)$this->id] !== false && !$flush)
            return $cache[(int)$this->id];

        $product_list = $this->getProducts();
        // Step 1 : Get product informations (warehouse_list and carrier_list), count warehouse
        // Determine the best warehouse to determine the packages
        // For that we count the number of time we can use a warehouse for a specific delivery address
        $warehouse_count_by_address = array();
        $warehouse_carrier_list = array();

        $stock_management_active = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');

        foreach ($product_list as &$product)
        {
            if ((int)$product['id_address_delivery'] == 0)
                $product['id_address_delivery'] = (int)$this->id_address_delivery;

            if (!isset($warehouse_count_by_address[$product['id_address_delivery']]))
                $warehouse_count_by_address[$product['id_address_delivery']] = array();

            $product['warehouse_list'] = array();

            if ($stock_management_active &&
                ((int)$product['advanced_stock_management'] == 1 || Pack::usesAdvancedStockManagement((int)$product['id_product'])))
            {
                $warehouse_list = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute'], $this->id_shop);
                if (count($warehouse_list) == 0)
                    $warehouse_list = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute']);
                // Does the product is in stock ?
                // If yes, get only warehouse where the product is in stock

                $warehouse_in_stock = array();
                $manager = StockManagerFactory::getManager();

                foreach ($warehouse_list as $key => $warehouse)
                {
                    $product_real_quantities = $manager->getProductRealQuantities(
                        $product['id_product'],
                        $product['id_product_attribute'],
                        array($warehouse['id_warehouse']),
                        true
                    );

                    if ($product_real_quantities > 0 || Pack::isPack((int)$product['id_product']))
                        $warehouse_in_stock[] = $warehouse;
                }

                if (!empty($warehouse_in_stock))
                {
                    $warehouse_list = $warehouse_in_stock;
                    $product['in_stock'] = true;
                }
                else
                    $product['in_stock'] = false;
            }
            else
            {
                //simulate default warehouse
                $warehouse_list = array(0);
                $product['in_stock'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']) > 0;
            }

            foreach ($warehouse_list as $warehouse)
            {
                if (!isset($warehouse_carrier_list[$warehouse['id_warehouse']]))
                {
                    $warehouse_object = new Warehouse($warehouse['id_warehouse']);
                    $warehouse_carrier_list[$warehouse['id_warehouse']] = $warehouse_object->getCarriers();
                }

                $product['warehouse_list'][] = $warehouse['id_warehouse'];
                if (!isset($warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']]))
                    $warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']] = 0;

                $warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']]++;
            }
        }
        unset($product);

        arsort($warehouse_count_by_address);

        // Step 2 : Group product by warehouse
        $grouped_by_warehouse = array();
        foreach ($product_list as &$product)
        {
            if (!isset($grouped_by_warehouse[$product['id_address_delivery']]))
                $grouped_by_warehouse[$product['id_address_delivery']] = array(
                    'in_stock' => array(),
                    'out_of_stock' => array(),
                );
            
            $product['carrier_list'] = array();
            $id_warehouse = 0;
            foreach ($warehouse_count_by_address[$product['id_address_delivery']] as $id_war => $val)
            {
                if (in_array((int)$id_war, $product['warehouse_list']))
                {
                    $product['carrier_list'] = array_merge($product['carrier_list'], Carrier::getAvailableCarrierList(new Product($product['id_product']), $id_war, $product['id_address_delivery'], null, $this));
                    if (!$id_warehouse)
                        $id_warehouse = (int)$id_war;
                }
            }

            if (!isset($grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse]))
            {
                $grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse] = array();
                $grouped_by_warehouse[$product['id_address_delivery']]['out_of_stock'][$id_warehouse] = array();
            }

            if (!$this->allow_seperated_package)
                $key = 'in_stock';
            else
                $key = $product['in_stock'] ? 'in_stock' : 'out_of_stock';

            if (empty($product['carrier_list']))
                $product['carrier_list'] = array(0);

            $grouped_by_warehouse[$product['id_address_delivery']][$key][$id_warehouse][] = $product;
        }
        unset($product);

        // Step 3 : grouped product from grouped_by_warehouse by available carriers
        $grouped_by_carriers = array();
        foreach ($grouped_by_warehouse as $id_address_delivery => $products_in_stock_list)
        {
            if (!isset($grouped_by_carriers[$id_address_delivery]))
                $grouped_by_carriers[$id_address_delivery] = array(
                    'in_stock' => array(),
                    'out_of_stock' => array(),
                );
            foreach ($products_in_stock_list as $key => $warehouse_list)
            {
                if (!isset($grouped_by_carriers[$id_address_delivery][$key]))
                    $grouped_by_carriers[$id_address_delivery][$key] = array();
                foreach ($warehouse_list as $id_warehouse => $product_list)
                {
                    if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse]))
                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse] = array();
                    foreach ($product_list as $product)
                    {
                        $package_carriers_key = implode(',', $product['carrier_list']);

                        if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key]))
                            $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key] = array(
                                'product_list' => array(),
                                'carrier_list' => $product['carrier_list'],
                                'warehouse_list' => $product['warehouse_list']
                            );

                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key]['product_list'][] = $product;
                    }
                }
            }
        }

        $package_list = array();
        // Step 4 : merge product from grouped_by_carriers into $package to minimize the number of package
        foreach ($grouped_by_carriers as $id_address_delivery => $products_in_stock_list)
        {
            if (!isset($package_list[$id_address_delivery]))
                $package_list[$id_address_delivery] = array(
                    'in_stock' => array(),
                    'out_of_stock' => array(),
                );

            foreach ($products_in_stock_list as $key => $warehouse_list)
            {
                if (!isset($package_list[$id_address_delivery][$key]))
                    $package_list[$id_address_delivery][$key] = array();
                // Count occurance of each carriers to minimize the number of packages
                $carrier_count = array();
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers)
                {
                    foreach ($products_grouped_by_carriers as $data)
                    {
                        foreach ($data['carrier_list'] as $id_carrier)
                        {
                            if (!isset($carrier_count[$id_carrier]))
                                $carrier_count[$id_carrier] = 0;
                            $carrier_count[$id_carrier]++;
                        }
                    }
                }
                arsort($carrier_count);
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers)
                {
                    if (!isset($package_list[$id_address_delivery][$key][$id_warehouse]))
                        $package_list[$id_address_delivery][$key][$id_warehouse] = array();
                    foreach ($products_grouped_by_carriers as $data)
                    {
                        foreach ($carrier_count as $id_carrier => $rate)
                        {
                            if (in_array($id_carrier, $data['carrier_list']))
                            {
                                if (!isset($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]))
                                    $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier] = array(
                                        'carrier_list' => $data['carrier_list'],
                                        'warehouse_list' => $data['warehouse_list'],
                                        'product_list' => array(),
                                    );
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'] =
                                    array_intersect($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'], $data['carrier_list']);
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'] =
                                    array_merge($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'], $data['product_list']);

                                break;
                            }
                        }
                    }
                }
            }
        }

        // Step 5 : Reduce depth of $package_list
        $final_package_list = array();
        foreach ($package_list as $id_address_delivery => $products_in_stock_list)
        {
            if (!isset($final_package_list[$id_address_delivery]))
                $final_package_list[$id_address_delivery] = array();

            foreach ($products_in_stock_list as $key => $warehouse_list)
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers)
                    foreach ($products_grouped_by_carriers as $data)
                    {
                        $final_package_list[$id_address_delivery][] = array(
                            'product_list' => $data['product_list'],
                            'carrier_list' => $data['carrier_list'],
                            'warehouse_list' => $data['warehouse_list'],
                            'id_warehouse' => $id_warehouse,
                        );
                    }
        }
        $cache[(int)$this->id] = $final_package_list;
        return $final_package_list;
    }

}