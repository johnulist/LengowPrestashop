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
 * The Lengow Order Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowOrderAbstract extends Order {

    /**
     * Version.
     */
    const VERSION = '1.0.0';
  
    /**
     * Order ID from marketplace.
     */
    public $lengow_id_order;

    /**
     * Flux ID from Lengow.
     */
    public $lengow_id_flux;
    
    /**
     * Marketplace's name.
     */
    public $lengow_marketplace;
    
    /**
     * Message.
     */
    public $lengow_message;
    
    /**
     * Total paid on marketplace.
     */
    public $lengow_total_paid;
    
    /**
     * Carrier from marketplace.
     */
    public $lengow_carrier;
    
    /**
     * Tracking.
     */
    public $lengow_tracking;
    
    /**
     * Extra information (json node form import).
     */
    public $lengow_extra;

    /**
     * Is importing, prevent multiple import
     */
    public $is_import;

    /**
     * Construc a Prestashop product with Lengow fields.
     *
     * @param integer $id The ID of product
     * @param integer $id_lang The lang of product
     */
    public function __construct($id = NULL, $id_lang = NULL)   {
        parent::__construct($id, $id_lang);
        if ($id)
          $this->_getLengowFields($id);
    }

    /**
     * Check if order is already imported
     *
     * @param integer $id_order_lengow The marketplace ID
     * @param integer $id_flux The flux ID
     *
     * @return boolean.
     */
    static public function isAlreadyImported($id_order_lengow, $id_flux) {
        $select = 'SELECT COUNT(`id_order`) AS `count` FROM `' . _DB_PREFIX_ . 'lengow_orders` '
                . 'WHERE `id_order_lengow` = \'' . pSQL($id_order_lengow) . '\' '
                . 'AND `id_flux` = \'' . pSQL($id_flux) . '\';';
        $count = Db::getInstance()->ExecuteS($select);
        if($count[0]['count'] >= 1)
            return true;
        return false;
    }

    /**
     * Get id of imported order
     *
     * @param integer $id_order_lengow The marketplace ID
     * @param integer $id_flux The flux ID
     *
     * @return interger $order_id
     */
    static public function getOrderId($id_order_lengow, $id_flux) {
        $select = 'SELECT `id_order` FROM `' . _DB_PREFIX_ . 'lengow_orders` '
                . 'WHERE `id_order_lengow` = \'' . pSQL($id_order_lengow) . '\' '
                . 'AND `id_flux` = \'' . pSQL($id_flux) . '\';';
        $order_id = Db::getInstance()->ExecuteS($select);
        return $order_id[0]['id_order'];
    }

    /**
     * Get Prestashop order with ID marketplace & ID flux
     *
     * @param integer $id_order_lengow The marketplace ID
     * @param integer $id_flux The flux ID
     *
     * @return boolean.
     */
    public static function getByOrderIDFlux($id_order_lengow, $id_flux) {
        $query = 'SELECT `id_order` FROM `' . _DB_PREFIX_ . 'lengow_orders` '
               . 'WHERE `id_order_lengow` = \'' . pSQL($id_order_lengow) . '\' '
               . 'AND `id_flux` = \'' . pSQL($id_flux) . '\' LIMIT 1;';
        if ($result = Db::getInstance()->ExecuteS($query)) {
            return new Order($result[0]['id_order']);
        }
        return null;
    }

    /**
     * Save a Lengow's order on database
     *
     * @param integer $id_order The Prestashop order ID
     * @param integer $id_order_lengow The marketplace ID
     * @param integer $id_flux The flux ID
     * @param string $marketplace The marketplace ID
     * @param string $message Message from marketplace
     * @param float $total_paid The total paid on marketplace
     * @param string $carrier Carrier of order's marketplace
     * @param string $tracking Trakcking from marketplace
     * @param string $extra Extra value (node json) of order imported
     * @param integer $id_lang Land ID
     * @param integer $id_shop Shop ID
     * @param integer $id_shop_group Shop group ID
     *
     * @return boolean.
     */
    public static function addLengow($id_order, $id_order_lengow, $id_flux, $marketplace, $message, $total_paid, $carrier, $tracking, $extra, $id_lang = null, $id_shop = null, $id_shop_group = null) {
        $context = LengowCore::getContext();
        if(empty($id_lang))
            $id_lang = $context->language->id;
        if(empty($id_shop))
            $id_shop = $context->shop->id;
        $id_shop_group = $context->shop->id_shop_group;
        return Db::getInstance()->autoExecute(_DB_PREFIX_ . 'lengow_orders', array(
                                              'id_order'        =>    pSQL($id_order),
                                              'id_order_lengow' =>    pSQL($id_order_lengow),
                                              'id_shop'         =>    $id_shop,
                                              'id_shop_group'   =>    $id_shop_group,
                                              'id_lang'         =>    $id_lang,
                                              'id_flux'         =>    $id_flux,
                                              'marketplace'     =>    pSQL($marketplace),
                                              'message'         =>    pSQL($message),
                                              'total_paid'      =>    $total_paid,
                                              'carrier'         =>    pSQL($carrier),
                                              'tracking'        =>    pSQL($tracking),
                                              'extra'           =>    pSQL($extra),
                                              'date_add'        =>    date('Y-m-d H:i:s'),
                                             ), 'INSERT');
    }

    /**
     * Get the shipping price with current method
     *
     * @param float $total The total of order
     *
     * @return float The shipping price.
     */
    public static function getShippingPrice($total) {
        $context = Context::getContext();
        $carrier = LengowCore::getInstanceCarrier();
        $id_zone = $context->country->id_zone;
        $id_currency = $context->cart->id_currency;
        $shipping_method = $carrier->getShippingMethod();
        if ($shipping_method != Carrier::SHIPPING_METHOD_FREE) {
            if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT)
                return LengowCore::formatNumber($carrier->getDeliveryPriceByWeight($total, (int) $id_zone));
            else
                return LengowCore::formatNumber($carrier->getDeliveryPriceByPrice($total, (int) $id_zone, (int) $id_currency));
        }
        return 0;
    }

    /**
     * Init lengow fields from the order ID
     *
     * @param integer $id_order The order ID
     *
     * @return boolean. 
     */
    private function _getLengowFields($id) {
        $query = 'SELECT `id_order_lengow` , `id_flux` , `marketplace` , `message` , `total_paid` , `carrier` , `tracking` , `extra` '
               . 'FROM `' . _DB_PREFIX_ . 'lengow_orders` '
               . 'WHERE `id_order` = \'' . pSQL($id) . '\' '
               . 'LIMIT 1;';
        if ($result = Db::getInstance()->ExecuteS($query)) {
            $result = array_shift($result);
            $this->lengow_id_order = $result['id_order_lengow'];
            $this->lengow_id_flux = $result['id_flux'];
            $this->lengow_marketplace = $result['marketplace'];
            $this->lengow_message = $result['message'];
            $this->lengow_total_paid = $result['total_paid'];
            $this->lengow_carrier = $result['carrier'];
            $this->lengow_tracking = $result['tracking'];
            $this->lengow_extra = $result['extra'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Rebuild order with Lengow prices
     *
     * @param array $lengow_products Products with prices
     * @param float $total_paid Total paid on marketplace
     * @param float $shipping_price Total shipping price
     * @param float $wrapping_price Total wrapping price
     */
    public function rebuildOrder($lengow_products, $total_paid, $shipping_price, $wrapping_price) {
        if ($products = $this->getProducts()) {
            $total_order = 0;
            $total_order_tax_excl = 0;
            foreach ($products as $product_line) {
                $order_detail = new LengowOrderDetail($product_line['id_order_detail']);
                if ($order_detail->product_attribute_id > 0)
                    $product_sku = $order_detail->product_id . '_' . $order_detail->product_attribute_id;
                else
                    $product_sku = $order_detail->product_id;
                $order_detail->changePrice($lengow_products[$product_sku]['price'], $lengow_products[$product_sku]['tax_rate']);
                $tax_product = 1 + (0.01 * $lengow_products[$product_sku]['tax_rate']);
                $total_order += $lengow_products[$product_sku]['price'] * $lengow_products[$product_sku]['qty'];
                $total_order_tax_excl += ($lengow_products[$product_sku]['price'] / $tax_product) * $lengow_products[$product_sku]['qty'];
            }
            // Total
            $this->total_products = LengowCore::formatNumber($total_order_tax_excl);
            $this->total_products_wt = $total_order;
            // Discount
            if (_PS_VERSION_ >= '1.5') {
                $this->total_discounts_tax_excl = 0;
                $this->total_discounts_tax_incl = 0;
            }
            $this->total_discounts = 0;
            // Shipping
            $carrier_tax = 1 + (0.01 * $this->carrier_tax_rate);
            $this->total_shipping_tax_excl = LengowCore::formatNumber($shipping_price / $carrier_tax);
            $this->total_shipping_tax_incl = (float) $shipping_price;
            $this->total_shipping = (float) $shipping_price;
            // Wrapping
            if (_PS_VERSION_ >= '1.5') {
                $id_address = (int) $this->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
                $address = LengowAddress::initialize($id_address);
                $tax_manager = TaxManagerFactory::getManager($address, (int) Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'));
                $tax_calculator = $tax_manager->getTaxCalculator();
                $this->total_wrapping_tax_excl = $tax_calculator->addTaxes($wrapping_price);
                $this->total_wrapping_tax_incl = (float) $wrapping_price;
            }
            $this->total_wrapping = (float) $wrapping_price;
            // Pay
            if (_PS_VERSION_ >= '1.5') {
                $this->total_paid_tax_excl = LengowCore::formatNumber($total_order_tax_excl + $this->total_shipping_tax_excl + $this->total_wrapping_tax_excl);
                $this->total_paid_tax_incl = $total_paid;
            }
            $this->total_paid = $total_paid;
            $this->total_paid_real = $total_paid;
            $this->update();
            if (_PS_VERSION_ >= '1.5') {
                $this->rebuildOrderPayment();
                $this->rebuildOrderInvoice();
                $this->rebuildOrderCarrier();
                $this->rebuildOrderDetailTax();
            }
        }
    }

    /**
     * Rebuild order payment with Lengow prices
     *
     * @param float $total_paid Total paid on marketplace
     */
    public function rebuildOrderPayment() {
        $update = 'UPDATE `' . _DB_PREFIX_ . 'order_payment` SET `amount` = \'' . pSQL($this->total_paid). '\' WHERE `order_reference` = \'' . pSQL($this->reference) . '\' LIMIT 1;';
        Db::getInstance()->execute($update);
    }

    /**
     * Rebuild order payment with Lengow prices
     */
    public function rebuildOrderInvoice() {
        $invoices = $this->getInvoicesCollection();
        if(isset($invoices[0])) {
            $invoice = $invoices[0];
            $invoice->total_products = $this->total_products;
            $invoice->total_products_wt = $this->total_products_wt;
            // Discount
            $invoice->total_discounts_tax_excl = 0;
            $invoice->total_discounts_tax_incl = 0;
            $invoice->total_discounts = 0;
            // Shipping
            $invoice->total_shipping_tax_excl = $this->total_shipping_tax_excl;
            $invoice->total_shipping_tax_incl = $this->total_shipping_tax_incl;
            $invoice->total_shipping = $this->total_shipping;
            // Wrapping
            $invoice->total_wrapping_tax_excl = $this->total_wrapping_tax_excl;
            $invoice->total_wrapping_tax_incl = $this->total_wrapping_tax_incl;
            $invoice->total_wrapping = $this->total_wrapping;
            // Pay
            $invoice->total_paid_tax_excl = $this->total_paid_tax_excl;
            $invoice->total_paid_tax_incl = $this->total_paid_tax_incl;
            $invoice->total_paid = $this->total_paid;
            $invoice->total_paid_real = $this->total_paid_real;
            $invoice->update();     
        }   
    }

    /**
     * Rebuild order carrier with Lengow prices
     */
    public function rebuildOrderCarrier() {
        $id_order_carrier = Db::getInstance()->getValue(
            'SELECT `id_order_carrier` ' .
            'FROM `'._DB_PREFIX_.'order_carrier` ' . 
            'WHERE `id_order` = ' . $this->id . ';');
        if ($id_order_carrier) {
            $order_carrier = new OrderCarrier($id_order_carrier);
            $order_carrier->shipping_cost_tax_exc = $this->total_shipping_tax_excl;
            $order_carrier->shipping_cost_tax_incl = $this->total_shipping_tax_incl;
            $order_carrier->update();
        }
    }

    /**
     * Rebuild OrderDetailTax
     *
     * @return void
     */
    public function rebuildOrderDetailTax() {
        $detail_list = OrderDetail::getList($this->id);
        foreach($detail_list as $detail) {
            $order_detail = new OrderDetail($detail['id_order_detail']);
            $order_detail->updateTaxAmount($this);
        }
    }

    /**
     * Rebuild OrderCarrier after validateOrder
     *
     * @param int $id_carrier 
     * @return void
     */
    public function forceCarrier($id_carrier) {
        if($id_carrier == '')
            return null;
        
        $this->id_carrier = $id_carrier;
        $this->update();

        if($this->getIdOrderCarrier() != '') {
            $order_carrier = new OrderCarrier($this->getIdOrderCarrier());
            $order_carrier->id_carrier = $id_carrier;
            $order_carrier->update();
        }
    }

    public function getIdOrderCarrier() 
    {
        return (int)Db::getInstance()->getValue('
                SELECT `id_order_carrier`
                FROM `'._DB_PREFIX_.'order_carrier`
                WHERE `id_order` = '.(int)$this->id);
    }

    /**
     * Add Relay Point in Mondial Relay table
     *
     * @param array $relay informations
     * @return boolean true if success, false if not
     */
    public function addRelayPoint($relay) {
        if(!is_array($relay) || empty($relay))
            return false;

        $insert_values = array(
            'id_customer' => (int) $this->id_customer,
            'id_method' => (int) $this->id_carrier,
            'id_cart' => (int) $this->id_cart,
            'id_order' => (int) $this->id,
            'MR_Selected_Num' => pSQL($relay['Num']),
            'MR_Selected_LgAdr1' => pSQL($relay['LgAdr1']),
            'MR_Selected_LgAdr2' => pSQL($relay['LgAdr2']),
            'MR_Selected_LgAdr3' => pSQL($relay['LgAdr3']),
            'MR_Selected_LgAdr4' => pSQL($relay['LgAdr4']),
            'MR_Selected_CP' => pSQL($relay['CP']),
            'MR_Selected_Ville' => pSQL($relay['Ville']),
            'MR_Selected_Pays' => pSQL($relay['Pays'])
        );

        if(_PS_VERSION_ < '1.5') {
            return Db::getInstance()->autoExecute(_DB_PREFIX_ . 'mr_selected', $insert_values, 'INSERT');
        } else {
            return DB::getInstance()->insert('mr_selected', $insert_values);
        }
    }
}
                                                     