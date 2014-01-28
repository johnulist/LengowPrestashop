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
 * The Lengow Product Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowProductAbstract extends Product {
    /**
     * Version.
     */
    const VERSION = '1.0.1';

    /**
     * Images of produtcs
     */
    protected $images;

    /**
     * The product cover.
     */
    protected $cover;

    /**
     * Default category.
     */
    protected $category_default;

    /**
     * Name of default category.
     */
    protected $category_name;

    /**
     * If product is sale.
     */
    protected $is_sale = false;

    /**
     * Array combination of product's attributes.
     */
    protected $combinations;

    /**
     * Array of product's features.
     */
    protected $features;

    /**
     * Variation.
     */
    protected $variation;

    /**
     * Load a new product.
     *
     * @param integer $id_product The ID product to load
     * @param integer $id_lang The ID lang for product's content
     * @param object $context The context
     */
    public function __construct($id_product = null, $id_lang = null) {
        parent::__construct($id_product, false, $id_lang);
        $context = Context::getContext();
        $this->tax_name = 'deprecated'; // The applicable tax may be BOTH the product one AND the state one (moreover this variable is some deadcode)
        $this->manufacturer_name = Manufacturer::getNameById((int) $this->id_manufacturer);
        $this->supplier_name = Supplier::getNameById((int) $this->id_supplier);
        $address = null;
        if (is_object($context->cart) && $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')} != null)
            $address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        if (LengowCore::compareVersion()) {
            $this->tax_rate = $this->getTaxesRate(new Address($address));
        } else {
            global $cart;
            if (is_object($cart) && $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')} != null)
                $this->tax_rate = Tax::getProductTaxRate($this->id, $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
            else
                $this->tax_rate = Tax::getProductTaxRate($this->id, null);
        }
        $this->new = $this->isNew();
        $this->base_price = $this->price;
        if($this->id) {
            $this->price = LengowProduct::getPriceStatic((int) $this->id, false, null, 2, null, false, true, 1, false, null, null, null, $this->specificPrice);
            $this->unit_price = ($this->unit_price_ratio != 0 ? $this->price / $this->unit_price_ratio : 0);
        }
        
        if (LengowCore::compareVersion())
            $this->loadStockData();
        if ($this->id_category_default && $this->id_category_default > 1) {
            $this->category_default = new Category((int) $this->id_category_default, $id_lang);
            $this->category_name = $this->category_default->name;
        } else {
            $categories = self::getProductCategories($this->id);
            if(!empty($categories)) {
                $this->category_default = new Category($categories[0], $id_lang);
                $this->category_name = $this->category_default->name;
            }
        }
        $images = $this->getImages($id_lang);
        $array_images = array();
        foreach ($images as $image) {
            if ($image['cover'])
                $this->cover = $image;
            else
                $array_images[] = $image;
        }
        $this->images = $array_images;
        $today = date('Y-m-d H:i:s');
        if(isset($this->specificPrice))
            if(array_key_exists('from', $this->specificPrice) && array_key_exists('to', $this->specificPrice))
                if ($this->specificPrice['from'] <= $today && $today <= $this->specificPrice['to'])
                    $this->is_sale = true;
        $this->_makeFeatures($context);
        $this->_makeAttributes($context);
    }

    /**
     * Get data of current product.
     *
     * @param string $name the data name
     * @param integer $id_product_attribute the id product attribute
     *
     * @return varchar The data. 
     */
    public function getData($name, $id_product_attribute = null) {
        switch ($name) {
            case 'id' :
                if ($id_product_attribute)
                    return $this->id . '_' . $id_product_attribute;
                return $this->id;
            case 'name' :
                if ($id_product_attribute && LengowExport::isFullName())
                    return $this->combinations[$id_product_attribute]['attribute_name'] ? $this->name . ' - ' . $this->combinations[$id_product_attribute]['attribute_name'] : $this->name;
                return $this->name;
            case 'reference' :
                if ($id_product_attribute > 1 && $this->combinations[$id_product_attribute]['reference'])
                    return $this->combinations[$id_product_attribute]['reference'];
                return $this->reference;
            case 'supplier_reference' :
                if ($id_product_attribute > 1 && $this->combinations[$id_product_attribute]['supplier_reference'])
                    return $this->combinations[$id_product_attribute]['supplier_reference'];
                return $this->supplier_reference;
            case 'manufacturer' :
                return $this->manufacturer_name;
            case 'category' :
                return $this->category_name;
            case 'breadcrumb' :
                if ($this->category_default) {
                    $breadcrumb = '';
                    $categories = $this->category_default->getParentsCategories();
                    foreach ($categories as $category) {
                        $breadcrumb = $category['name'] . ' > ' . $breadcrumb;
                    }
                    return rtrim($breadcrumb, ' > ');
                }
                return $this->category_name;
            case 'description' :
                return LengowCore::cleanHtml($this->description);
            case 'short_description' :
                return LengowCore::cleanHtml($this->description_short);
            case 'price' :
                if ($id_product_attribute)
                    return $this->getPrice($tax = true, $id_product_attribute, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = false, $quantity = 1);
                return $this->getPrice($tax = true, null, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = false, $quantity = 1);
                ;
            case 'wholesale_price' :
                if ($id_product_attribute > 1 && $this->combinations[$id_product_attribute]['wholesale_price'])
                    return LengowCore::formatNumber($this->combinations[$id_product_attribute]['wholesale_price']);
                return LengowCore::formatNumber($this->wholesale_price, 2);
            case 'price_duty_free' :
                if ($id_product_attribute)
                    return $this->getPrice($tax = false, $id_product_attribute, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = false, $quantity = 1);
                return $this->getPrice($tax = false, $id_product_attribute = null, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = false, $quantity = 1);
            case 'price_sale' :
                if ($id_product_attribute)
                    return $this->getPrice($tax = true, $id_product_attribute, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = true, $quantity = 1);
                return $this->getPrice($tax = true, $id_product_attribute = null, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = true, $quantity = 1);
            case 'price_sale_percent' :
                if ($id_product_attribute) {
                    $price = $this->getPrice($tax = true, $id_product_attribute, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = false, $quantity = 1);
                    $price_sale = $this->getPrice($tax = true, $id_product_attribute, $decimals = 2, $divisor = null, $only_reduc = true, $usereduc = true, $quantity = 1);
                } else {
                    $price = $this->getPrice($tax = true, $id_product_attribute = null, $decimals = 2, $divisor = null, $only_reduc = false, $usereduc = false, $quantity = 1);
                    $price_sale = $this->getPrice($tax = true, $id_product_attribute = null, $decimals = 2, $divisor = null, $only_reduc = true, $usereduc = true, $quantity = 1);
                }

                if ($price_sale && $price)
                    return LengowCore::formatNumber(($price_sale / $price) * 100);
                return 0;
            case 'quantity' :
                if ($id_product_attribute)
                    return self::getRealQuantity($this->id, $id_product_attribute);
                return self::getRealQuantity($this->id);
            case 'weight' :
                if ($id_product_attribute && $this->combinations[$id_product_attribute]['weight'])
                    return $this->weight + $this->combinations[$id_product_attribute]['weight'];
                return $this->weight;
            case 'ean' :
                if ($id_product_attribute > 1 && $this->combinations[$id_product_attribute]['ean13'])
                    return $this->combinations[$id_product_attribute]['ean13'];
                return $this->ean13;
            case 'upc' :
                if ($id_product_attribute > 1 && $this->combinations[$id_product_attribute]['upc'])
                    return $this->combinations[$id_product_attribute]['upc'];
                return $this->upc;
            case 'ecotax' :
                if ($id_product_attribute > 1 && $this->combinations[$id_product_attribute]['ecotax'])
                    return LengowCore::formatNumber($this->combinations[$id_product_attribute]['ecotax']);
                return isset($this->ecotaxinfos) && $this->ecotaxinfos > 0 ? LengowCore::formatNumber($this->ecotaxinfos) : LengowCore::formatNumber($this->ecotax);
            case 'available' :
                return $this->available_now;
            case 'url' :
                return LengowCore::getContext()->link->getProductLink($this);
            case 'image_1' :
                if ($id_product_attribute) {
                    $images = $this->getCombinationImages($this->id_lang);
                    if (is_array($images) && array_key_exists($id_product_attribute, $images))
                        return LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $images[$id_product_attribute][0]['id_image'], LengowCore::getImageFormat());
                }
                return isset($this->cover) ? LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $this->cover['id_image'], LengowCore::getImageFormat()) : '';
            case 'price_shipping' :
                if ($id_product_attribute && $id_product_attribute != null) {
                    $price = $this->getData('price_sale', $id_product_attribute);
                    $weight = $this->getData('weight', $id_product_attribute);
                } else {
                    $price = $this->getData('price_sale');
                    $weight = $this->getData('weight');
                }
                $context = Context::getContext();
                $carrier = LengowCore::getInstanceCarrier();
                $id_zone = $context->country->id_zone;
                $id_currency = $context->cart->id_currency;
                $shipping_method = $carrier->getShippingMethod();
                $shipping_cost = 0;
                if (!defined('Carrier::SHIPPING_METHOD_FREE') || $shipping_method != Carrier::SHIPPING_METHOD_FREE) {
                    if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                        $shipping_cost = LengowCore::formatNumber($carrier->getDeliveryPriceByWeight($weight, (int) $id_zone));
                    } else {
                        $shipping_cost = LengowCore::formatNumber($carrier->getDeliveryPriceByPrice($price, (int) $id_zone, (int) $id_currency));
                    }
                }

                // Check if product have single shipping cost
                if($this->additional_shipping_cost > 0)
                    $shipping_cost += $this->additional_shipping_cost;

                // Tax calcul
                $default_country = Configuration::get('PS_COUNTRY_DEFAULT');
                $taxe_rules = LengowTaxRule::getLengowTaxRulesByGroupId(Configuration::get('PS_LANG_DEFAULT'), $carrier->id_tax_rules_group);
                foreach ($taxe_rules as $taxe_rule) {
                    if (isset($taxe_rule['id_country']) && $taxe_rule['id_country'] == $default_country) {
                        $tr = new TaxRule($taxe_rule['id_tax_rule']);
                    }
                }

                if (isset($tr)) {
                    $t = new Tax($tr->id_tax);
                    $tax_calculator = new TaxCalculator(array($t));
                    $taxes = $tax_calculator->getTaxesAmount($shipping_cost);
                    if (!empty($taxes)) {
                        foreach ($taxes as $taxe) {
                            $shipping_cost += $taxe;
                        }
                    }
                }

                return LengowCore::formatNumber($shipping_cost);
            case 'id_parent' :
                return $this->id;
            case 'delivery_time' :
                return '';
            case 'image_2' :
                if ($id_product_attribute) {
                    $images = $this->getCombinationImages($this->id_lang);
                    if (is_array($images) && array_key_exists($id_product_attribute, $images))
                        if (isset($images[$id_product_attribute][1]['id_image']))
                            return LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $images[$id_product_attribute][1]['id_image'], LengowCore::getImageFormat());
                        else
                            return '';
                }
                return isset($this->images[0]) ? LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $this->images[0]['id_image'], LengowCore::getImageFormat()) : '';
            case 'image_3' :
                if ($id_product_attribute) {
                    $images = $this->getCombinationImages($this->id_lang);
                    if (is_array($images) && array_key_exists($id_product_attribute, $images))
                        if (isset($images[$id_product_attribute][2]['id_image']))
                            return LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $images[$id_product_attribute][2]['id_image'], LengowCore::getImageFormat());
                        else
                            return '';
                }
                return isset($this->images[1]) ? LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $this->images[1]['id_image'], LengowCore::getImageFormat()) : '';
            case 'sale_from' :
                return $this->is_sale ? $this->specificPrice['from'] : '';
            case 'sale_to' :
                return $this->is_sale ? $this->specificPrice['to'] : '';
            case 'meta_keywords' :
                return $this->meta_keywords;
            case 'meta_description' :
                return $this->meta_description;
            case 'url_rewrite' :
                return LengowCore::getContext()->link->getProductLink($this, $this->link_rewrite);
            case 'type' :
                if ($id_product_attribute)
                    return 'child';
                else if (empty($this->combinations))
                    return 'simple';
                else
                    return 'parent';
            case 'variation' :
                return $this->variation;
            case 'currency' :
                return LengowCore::getContext()->currency->iso_code;
            case 'condition' :
                return $this->condition;
            case 'supplier' :
                return $this->supplier_name;
        }
        if (preg_match('`image_([0-9]+)`', $name, $out)) {
            if ($id_product_attribute) {
                $id_image = $out[1] - 1;
                $attribute_images = $this->getCombinationImages($this->id_lang);
                if (is_array($attribute_images) && array_key_exists($id_product_attribute, $attribute_images))
                    if (isset($attribute_images[$id_product_attribute][$id_image]['id_image']))
                        return LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $attribute_images[$id_product_attribute][$id_image]['id_image'], LengowCore::getImageFormat());
                    else
                        return '';
            }
            return isset($this->images[$out[1]]) ? LengowCore::getContext()->link->getImageLink($this->link_rewrite, $this->id . '-' . $this->images[$out[1]]['id_image'], LengowCore::getImageFormat()) : '';

        }
        if(isset($this->{$name}))
            return $this->{$name};
    }

    /**
     * Clear data cache.
     */
    public static function clear() {
        self::$_taxCalculationMethod = null;
        self::$_prices = array();
        self::$_pricesLevel2 = array();
        self::$_incat = array();
        self::$_cart_quantity = array();
        self::$_tax_rules_group = array();
        self::$_cacheFeatures = array();
        self::$_frontFeaturesCache = array();
        self::$producPropertiesCache = array();
        if (_PS_VERSION_ >= '1.5')
            self::$cacheStock = array();
    }

    /**
     * Get data attribute of current product.
     *
     * @param integer $id_product_attribute the id product atrribute
     * @param string $name the data name attribute
     *
     * @return varchar The data. 
     */
    public function getDataAttribute($id_product_attribute, $name) {
        return isset($this->combinations[$id_product_attribute]['attributes'][$name][1]) ? $this->combinations[$id_product_attribute]['attributes'][$name][1] : '';
    }

    /**
     * Get data feature of current product.
     *
     * @param string $name the data name feature
     *
     * @return varchar The data. 
     */
    public function getDataFeature($name) {
        return isset($this->features[$name]['value']) ? $this->features[$name]['value'] : '';
    }

    /**
     * Get the products to export.
     *
     * @return varchar IDs product. 
     */
    public static function exportIds($all = true, $all_product = false, $product_ids = null) {
        $context = LengowCore::getContext();
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;
        $selected_products_sql = '';

        if ($all == false) {
            $selected_products_sql = 'AND p.`id_product` IN ('
                    . 'SELECT `id_product` FROM `' . _DB_PREFIX_ . 'lengow_product` '
                    . 'WHERE `id_shop` = ' . $id_shop . ' )';
                    //. 'AND `id_lang` = ' . $id_lang . ')';
        }
        if (LengowCore::compareVersion() < 0) {
            $query = 'SELECT p.`id_product` '
                    . 'FROM `' . _DB_PREFIX_ . 'product` p ';
            if ($all_product == false)
                $query .= 'WHERE pl.`id_lang` = ' . pSQL($id_lang) . ' ';
            else
                $query .= 'WHERE pl.`id_lang` = ' . pSQL($id_lang) . ' AND p.`active` = 1 ';

            //$query .= 'WHERE pl.`id_lang` = ' . pSQL($id_lang) . ' AND p.`active` = 1 '
            // Add Lengow selected products
            $query .= $selected_products_sql;
        } else { // 1.4.X & 1.5.X
            $query = 'SELECT p.id_product '
                    . 'FROM ' . _DB_PREFIX_ . 'product p ';
            if (LengowCore::compareVersion() == 1) { //version 1.5.X
                $query .='LEFT JOIN ' . _DB_PREFIX_ . 'product_shop ps ON p.id_product=ps.id_product ';
            }
            // Add Lengow selected products
            if (LengowCore::compareVersion() == 1 && $id_shop != '') { //version 1.5.X
                if ($all_product == false)
                    $query .= ' WHERE ps.id_shop = ' . pSQL($id_shop) . ' AND ps.active=1 '; //AND psupp.id_product_attribute=0 ';
                else
                    $query .= ' WHERE ps.id_shop = ' . pSQL($id_shop) . ' '; //AND psupp.id_product_attribute=0 ';
            } else {
                if ($all_product == false)
                    $query .=' WHERE p.active=1 ';
                else
                    $query .= ' WHERE 1 ';
            }
            // Add Lengow selected products
            $query .= $selected_products_sql;
        }

        if($product_ids != null) {
            $query .= ' AND p.`id_product` IN (' . implode(',', $product_ids) . ')';
        }
        return Db::getInstance()->executeS($query);
    }

    /**
     * Make the feature of current product
     *
     * @param object $context The given context 
     */
    public function _makeFeatures($context) {
        $features = $this->getFrontFeatures($context->language->id);
        if ($features) {
            foreach ($features as $feature) {
                $this->features[$feature['name']] = $feature;
            }
        }
    }

    /**
     * Get features of current product
     *
     * @return array All features. 
     */
    public function getFeatures() {
        return $this->features;
    }

    /**
     * Make the attributes of current product
     *
     * @param object $context The given context 
     */
    public function _makeAttributes($context) {
        $color_by_default = '#BDE5F8'; // ?? WTF';
        $combinations = $this->getAttributesGroups($context->language->id);
        $groups = array();
        $comb_array = array();
        if (is_array($combinations)) {
            $combination_images = $this->getCombinationImages($context->language->id);
            foreach ($combinations as $k => $combination) {
                $price_to_convert = Tools::convertPrice($combination['price'], $context->currency);
                $price = Tools::displayPrice($price_to_convert, $context->currency);
                $comb_array[$combination['id_product_attribute']]['id_product_attribute'] = $combination['id_product_attribute'];
                $comb_array[$combination['id_product_attribute']]['attributes'][$combination['group_name']] = array($combination['group_name'], $combination['attribute_name'], $combination['id_attribute']);
                $comb_array[$combination['id_product_attribute']]['wholesale_price'] = isset($combination['wholesale_price']) ? $combination['wholesale_price'] : '';
                $comb_array[$combination['id_product_attribute']]['price'] = $price;
                $comb_array[$combination['id_product_attribute']]['ecotax'] = isset($combination['ecotax']) ? $combination['ecotax'] : '';
                $comb_array[$combination['id_product_attribute']]['weight'] = $combination['weight'] . Configuration::get('PS_WEIGHT_UNIT');
                $comb_array[$combination['id_product_attribute']]['unit_impact'] = $combination['unit_price_impact'];
                $comb_array[$combination['id_product_attribute']]['reference'] = $combination['reference'];
                $comb_array[$combination['id_product_attribute']]['ean13'] = isset($combination['ean13']) ? $combination['ean13'] : '';
                $comb_array[$combination['id_product_attribute']]['upc'] = isset($combination['upc']) ? $combination['upc'] : '';
                $comb_array[$combination['id_product_attribute']]['supplier_reference'] = isset($combination['supplier_reference']) ? $combination['supplier_reference'] : '';
                $comb_array[$combination['id_product_attribute']]['id_image'] = isset($combination_images[$combination['id_product_attribute']][0]['id_image']) ? $combination_images[$combination['id_product_attribute']][0]['id_image'] : 0;
                if (LengowCore::compareVersion())
                    $comb_array[$combination['id_product_attribute']]['available_date'] = strftime($combination['available_date']);
                $comb_array[$combination['id_product_attribute']]['default_on'] = $combination['default_on'];
                if ($combination['is_color_group'])
                    $groups[$combination['id_attribute_group']] = $combination['group_name'];
            }
        }
        $irow = 0;
        if (isset($comb_array)) {
            foreach ($comb_array as $id_product_attribute => $product_attribute) {
                $list = '';
                $name = '';
                /* In order to keep the same attributes order */
                asort($product_attribute['attributes']);
                foreach ($product_attribute['attributes'] as $attribute) {
                    $list .= $attribute[0] . ' - ' . $attribute[1] . ', ';
                    $name .= $attribute[0] . ',';
                }
                $list = rtrim($list, ', ');
                // $name = rtrim($name, ', ');
                $comb_array[$id_product_attribute]['image'] = $product_attribute['id_image'] ? new Image($product_attribute['id_image']) : false;
                if (LengowCore::compareVersion())
                    $comb_array[$id_product_attribute]['available_date'] = $product_attribute['available_date'] != 0 ? date('Y-m-d', strtotime($product_attribute['available_date'])) : '0000-00-00';
                $comb_array[$id_product_attribute]['attribute_name'] = $list;
                $comb_array[$id_product_attribute]['name'] = $name;
                if ($product_attribute['default_on']) {
                    $comb_array[$id_product_attribute]['name'] = 'is_default';
                    $comb_array[$id_product_attribute]['color'] = $color_by_default;
                }
                if (!$this->variation)
                    $this->variation = $name;
            }
        }
        $this->combinations = $comb_array;
        //print_r($this->combinations);
    }

    /**
     * Get combinations of current product
     *
     * @return array All combinations. 
     */
    public function getCombinations() {
        return $this->combinations;
    }

    /**
     * Get count images of current product
     *
     * @return integer The number of images. 
     */
    public function getCountImages() {
        return count($this->images);
    }

    /**
     * OVERRIDE NATIVE FONCTION : add supplier_reference, ean13, upc, wholesale_price and ecotax
     * Get all available attribute groups
     *
     * @param integer $id_lang Language id
     * @return array Attribute groups
     */
    public function getAttributesGroups($id_lang) {
        if (LengowCore::compareVersion()) {
            if (!Combination::isFeatureActive())
                return array();
            $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
                        a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, pa.`id_product_attribute`,
                        IFNULL(stock.quantity, 0) as quantity, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, pa.`weight`,
                        product_attribute_shop.`default_on`, pa.`reference`, product_attribute_shop.`unit_price_impact`,
                        pa.`minimal_quantity`, pa.`available_date`, ag.`group_type`, ps.`product_supplier_reference` AS `supplier_reference`, pa.`ean13`, pa.`upc`, pa.`wholesale_price`, pa.`ecotax`
                    FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                    ' . Shop::addSqlAssociation('product_attribute', 'pa') . '
                    ' . Product::sqlStock('pa', 'pa') . '
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_supplier` ps ON ps.`id_product_attribute` = pa.`id_product_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON a.`id_attribute` = al.`id_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON ag.`id_attribute_group` = agl.`id_attribute_group`
                    ' . Shop::addSqlAssociation('attribute', 'a') . '
                    WHERE pa.`id_product` = ' . (int) $this->id . '
                        AND al.`id_lang` = ' . (int) $id_lang . '
                        AND agl.`id_lang` = ' . (int) $id_lang . '
                    GROUP BY id_attribute_group, id_product_attribute
                    ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
        } else {
            $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` group_name, agl.`public_name` public_group_name, a.`id_attribute`, al.`name` attribute_name,
                    a.`color` attribute_color, pa.*
                    FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
                    WHERE pa.`id_product` = ' . (int) $this->id . ' AND al.`id_lang` = ' . (int) $id_lang . ' AND agl.`id_lang` = ' . (int) $id_lang . '
                    ORDER BY agl.`public_name`, al.`name`';
        }
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Publis or unpublish to Lengow.
     *
     * @param integer $id_product the id product
     * @param integer $status 1 : publish, 0 : unpublish
     * @param integer $id_lang the id lang
     * @param integer $id_product the id shop
     *
     * @return boolean. 
     */
    public static function publish($id_product, $status = 1, $id_lang = null, $id_shop = null) {
        $context = LengowCore::getContext();
        if (empty($id_lang))
            $id_lang = $context->language->id;
        if (empty($id_shop))
            $id_shop = $context->shop->id;
        $id_shop_group = $context->shop->id_shop_group;
        if ($status == 1) {
            $select = 'SELECT COUNT(`id_product`) FROM `' . _DB_PREFIX_ . 'lengow_product` WHERE `id_product`= ' . pSQL($id_product) . ';';
            $count = Db::getInstance()->getValue($select);
            if ($count == 1)
                return true;
            else
                return Db::getInstance()->autoExecute(_DB_PREFIX_ . 'lengow_product', array(
                            'id_product' => pSQL($id_product),
                            'id_shop' => $id_shop,
                            'id_shop_group' => $id_shop_group,
                            'id_lang' => $id_lang,
                                ), 'INSERT');
        } else if ($status == 0) {
            return Db::getInstance()->delete(_DB_PREFIX_ . 'lengow_product', 'id_product = ' . pSQL($id_product), 1);
        }
    }

    /**
     * For a given product, returns its real quantity
     *
     * @param int $id_product
     * @param int $id_product_attribute
     * @param int $id_warehouse
     * @param int $id_shop
     * @return int real_quantity
     */
    public static function getRealQuantity($id_product, $id_product_attribute = 0, $id_warehouse = null, $id_shop = null) {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            if($id_product_attribute == 0 || $id_product_attribute == null)
                return Product::getQuantity($id_product);
            return Product::getQuantity($id_product, $id_product_attribute);
        }
        else
            return parent::getRealQuantity($id_product, $id_product_attribute, $id_warehouse, $id_shop);
    }

    /**
     * Get max number of images
     *
     * @return int max number of images for one product
     */
    public static function getMaxImages() {
        if (_PS_VERSION_ >= '1.5') {
            $sql = 'SELECT COUNT(i.`id_image`) AS `total`
                    FROM `' . _DB_PREFIX_ . 'image` i
                    ' . Shop::addSqlAssociation('image', 'i') . '
                    GROUP BY i.`id_product`
                    ORDER BY `total` DESC';
            $count = Db::getInstance()->getRow($sql);
            return $count['total'];
        } else {
            $sql = 'SELECT COUNT(i.`id_image`) AS `total`
                    FROM `' . _DB_PREFIX_ . 'image` i
                    GROUP BY i.`id_product`
                    ORDER BY `total` DESC';
            $count = Db::getInstance()->getRow($sql);
            return $count['total'];
        }
    }

    /**
     * For a given reference, returns the corresponding id
     *
     * @param string $reference
     * @return int id
     */
    public static function getIdByReference($reference) {
        if(empty($reference) || !Validate::isReference($reference))
            return 0;

        if (_PS_VERSION_ >= '1.5') {
            $query = new DbQuery();
            $query->select('p.id_product');
            $query->from('product', 'p');
            $query->where('p.reference = \'' . pSQL($reference) . '\'');
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);

            // If no result, search in attribute
            if($result == '') {
                $query = new DbQuery();
                $query->select('pa.id_product, pa.id_product_attribute');
                $query->from('product_attribute', 'pa');
                $query->where('pa.reference = \'' . pSQL($reference) . '\'');
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
            }
        } else {
            $sql = 'SELECT p.`id_product`
                    FROM `' . _DB_PREFIX_ . 'product` p
                    WHERE p.`reference` = \'' . pSQL($reference) . '\'';
            $result = Db::getInstance()->getRow($sql);

            if($result == '') {
                $sql = 'SELECT pa.`id_product`, pa.`id_product_attribute`
                        FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                        WHERE pa.`reference` = \'' . pSQL($reference) . '\'';
                $result = Db::getInstance()->getRow($sql);
            }
        }

        return $result;
    }


    public static function findProduct($key, $value) {
        if(empty($key) || empty($value))
            return 0;

        if (_PS_VERSION_ >= '1.5') {
            $query = new DbQuery();
            $query->select('p.id_product');
            $query->from('product', 'p');
            $query->where('p.' . $key . ' = \'' . pSQL($value) . '\'');
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);

            // If no result, search in attribute
            if($result == '') {
                $query = new DbQuery();
                $query->select('pa.id_product, pa.id_product_attribute');
                $query->from('product_attribute', 'pa');
                $query->where('pa.' . $key . ' = \'' . pSQL($value) . '\'');
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
            }
        } else {
            $sql = 'SELECT p.`id_product`
                    FROM `' . _DB_PREFIX_ . 'product` p
                    WHERE p.`' . $key . '` = \'' . pSQL($value) . '\'';
            $result = Db::getInstance()->getRow($sql);

            if($result == '') {
                $sql = 'SELECT pa.`id_product`, pa.`id_product_attribute`
                        FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                        WHERE pa.`' . $key . '` = \'' . pSQL($value) . '\'';
                $result = Db::getInstance()->getRow($sql);
            }
        }
        return $result;
    }

    /**
     * Search ID product with ref/ean/upc attributes
     *
     * @param string $ref
     * @return int id
     */
    public static function searchAttributeId($ref) {
        if (empty($ref))
            return 0;
        if (_PS_VERSION_ >= '1.5') {
            $query = new DbQuery();
            $query->select('pa.id_product, pa.id_product_attribute');
            $query->from('product_attribute', 'pa');
            $query->where('pa.reference = \'' . pSQL($ref) . '\' 
                              OR pa.supplier_reference = \'' . pSQL($ref) . '\' 
                              OR pa.ean13 = \'' . pSQL($ref) . '\'
                              OR pa.upc = \'' . pSQL($ref) . '\'');
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        } else {
            $sql = 'SELECT `pa`.`id_product`, `pa`.`id_product_attribute`
                    FROM `' . _DB_PREFIX_ . 'product_attribute` `pa`
                    WHERE pa.`reference` = \'' . pSQL($ref) . '\'
                    OR pa.supplier_reference = \'' . pSQL($ref) . '\' 
                    OR pa.ean13 = \'' . pSQL($ref) . '\'
                    OR pa.upc = \'' . pSQL($ref) . '\'';
            return Db::getInstance()->executeS($sql);
        }
    }

    /**
     * For a given ean13 reference, returns the corresponding id
     *
     * @param string $ean13
     * @return int id
     */
    public static function getIdByEan13($ean13) {
        if (empty($ean13))
            return 0;

        if (!Validate::isEan13($ean13))
            return 0;

        if (_PS_VERSION_ >= '1.5') {
            $query = new DbQuery();
            $query->select('p.id_product');
            $query->from('product', 'p');
            $query->where('p.ean13 = \'' . pSQL($ean13) . '\'');

            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        } else {
            $sql = 'SELECT p.`id_product`
                FROM `' . _DB_PREFIX_ . 'product` p
                WHERE p.`ean13` = \'' . pSQL($ean13) . '\'';
            return Db::getInstance()->getValue($sql);
        }
    }

    /**
     * For a given uoc reference, returns the corresponding id
     *
     * @param string $pc
     * @return int id
     */
    public static function getIdByUpc($upc) {
        if (empty($upc))
            return 0;

        if (_PS_VERSION_ >= '1.5') {
            $query = new DbQuery();
            $query->select('p.id_product');
            $query->from('product', 'p');
            $query->where('p.upc = \'' . pSQL($upc) . '\'');

            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        } else {
            $sql = 'SELECT p.`id_product`
                FROM `' . _DB_PREFIX_ . 'product` p
                WHERE p.`upc` = \'' . pSQL($upc) . '\'';
            return Db::getInstance()->getValue($sql);
        }
    }


    /**
     * Get Product id and attribute id for a product node
     *
     * @return array
     */
    public static function getIdsProduct($product, $ref) {
        $product_sku = (string) $product->{$ref};
        $product_sku = str_replace('\_', '_', $product_sku);
        $product_sku = str_replace('X', '_', $product_sku);

        // If attribute, split product sku
        if (preg_match('`_`', $product_sku)) {
            $array_sku = explode('_', $product_sku);
            $id_product = $array_sku[0];
            $id_product_attribute = $array_sku[1];
        } else {
            $id_product = (string) $product->{$ref};
            $id_product_attribute = null;
        }

        return array(
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute
            );
    }
}
