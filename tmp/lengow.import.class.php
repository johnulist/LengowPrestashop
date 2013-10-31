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

$sep = DIRECTORY_SEPARATOR;
require_once 'lengow.core.class.php';
require_once 'lengow.connector.class.php';
if (_PS_VERSION_ >= '1.5')
    require_once 'lengow.gender.class.php';
require_once 'lengow.address.class.php';
require_once 'lengow.cart.class.php';
require_once 'lengow.product.class.php';
require_once 'lengow.order.class.php';
require_once 'lengow.orderdetail.class.php';
include_once 'lengow.payment.class.php';
include_once 'lengow.marketplace.class.php';
if (_PS_VERSION_ < '1.5')
    require_once _PS_MODULE_DIR_ . 'lengow' . $sep . 'backward_compatibility' . $sep . 'backward.php';

/**
 * The Lengow Import Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowImport {

    /**
     * Version.
     */
    const VERSION = '1.0.1';

    public $context;

    public $id_lang;

    public $id_shop;

    public $id_shop_group;

    public $force_log_output = true;

    public static $import_start = false;
    
    public static $debug = false;
    
    /**
     * Construct the import manager
     *
     * @param $id_lang integer ID lang
     * @param $id_shop integer ID shop
     */
    public function __construct($id_lang = null, $id_shop = null) {
        $this->context = LengowCore::getContext();
        if(empty($id_lang))
            $this->id_lang = $this->context->language->id;
        if(empty($id_shop))
            $this->id_shop = $this->context->shop->id;
        $this->id_shop_group = $this->context->shop->id_shop_group;
    }

    /**
     * Construct the import manager
     *
     * @param $command varchar The command of import
     * @param mixed
     */
    public function exec($command, $args = array()) {
        switch ($command) {
            case 'orders':
                return $this->_importOrders($args);
            default:
                return $this->_importOrders($args);
        }
    }

    /**
      * Makes the Orders API Url.
      *
      * @param array $args The arguments to request at the API
      */
    protected function _importOrders($args = array()) {
        LengowCore::disableSendState();
        if($args['debug'] == true)
            self::$debug = true;
        
        self::$import_start = true;
        $count_orders_updated = 0;
        $count_orders_added = 0;
        $lengow = new Lengow();
        $lengow_connector = new LengowConnector((integer) LengowCore::getIdCustomer(), LengowCore::getTokenCustomer());
        $orders = $lengow_connector->api('commands', array('dateFrom' => $args['dateFrom'],
                                                           'dateTo' => $args['dateTo'],
                                                           'id_group' => LengowCore::getGroupCustomer(),
                                                           'state' => 'plugin'));
        if(!is_object($orders)) {
            LengowCore::log('Error on lengow webservice', $this->force_log_output);
            die();
        } else {
            $find_count_orders = count($orders->orders->order);
            LengowCore::log('Find ' . $find_count_orders . ' order' . ($find_count_orders > 1 ? 's' : ''));
        }
        //LengowCore::debug($orders);
        $count_orders = (integer) $orders->orders_count->count_total;
        if($count_orders == 0) {
            LengowCore::log('No orders to import between ' . $args['dateFrom'] . ' and ' . $args['dateTo'], $this->force_log_output);
            return false;
        }
        foreach($orders->orders->order as $key => $data) {
            $lengow_order = $data;
            
            if(self::$debug == true)
                $lengow_order_id = (string) $lengow_order->order_id . '--' . time();
            else
                $lengow_order_id = (string) $lengow_order->order_id;

            $marketplace = LengowCore::getMarketplaceSingleton((string) $lengow_order->marketplace);
            $id_flux = (integer) $lengow_order->idFlux;
            if((string) $lengow_order->order_status->marketplace == '') {
                LengowCore::log('Order ' . $lengow_order_id . ' : no order\'s status');
                continue;
            }
            if((string) $lengow_order->tracking_informations->tracking_deliveringByMarketPlace == '') {
                LengowCore::log('Order ' . $lengow_order_id . ' : delivry by the marketplace (' . $lengow_order->marketplace . ')');
                continue;
            }
            if(LengowOrder::isAlreadyImported($lengow_order_id, $id_flux)) {
                LengowCore::log('Order ' . $lengow_order_id . ' : already imported', true);
                $id_state_lengow = LengowCore::getOrderState($marketplace->getStateLengow((string) $lengow_order->order_status->marketplace));
                $order = LengowOrder::getByOrderIDFlux($lengow_order_id, $id_flux);
                // Update status' order only if in process or shipped
                if($order->current_state != $id_state_lengow) {
                    // Change state process to shipped
                    if($order->current_state == LengowCore::getOrderState('process')
                       && $marketplace->getStateLengow((string) $lengow_order->order_status->marketplace) == 'shipped') {
                        // Disable Mail
                        $order_state = new OrderState(LengowCore::getOrderState('shipped'));
                        $buffer_state = $order_state->send_email;
                        $order_state->send_email = false;
                        $order_state->update();
                        
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState(LengowCore::getOrderState('shipped'), $order, true);
                        $history->add();
                        $tracking_number = (string) $lengow_order->tracking_informations->tracking_number;
                        if($tracking_number) {
                            $order->shipping_number = $tracking_number;
                            $order->update();
                        }
                        LengowCore::log('Order ' . $lengow_order_id . ' : update state to shipped');
                        $count_orders_updated++;
                        // Enable mail 
                        $order_state->send_email = $buffer_state;
                        $order_state->update();
                    } else if(($order->current_state == LengowCore::getOrderState('process') // Change state process or shipped to cancel
                        || $order->current_state == LengowCore::getOrderState('shipped'))
                       && $marketplace->getStateLengow((string) $lengow_order->order_status->marketplace) == 'cancel') {
                        // Disable Mail
                        $order_state = new OrderState(LengowCore::getOrderState('shipped'));
                        $buffer_state = $order_state->send_email;
                        $order_state->send_email = false;
                        $order_state->update();

                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState(LengowCore::getOrderState('cancel'), $order, true);
                        $history->add();
                        LengowCore::log('Order ' . $lengow_order_id . ' : update state to cancel');
                        $count_orders_updated++;
                        // Enable mail
                        $order_state->send_email = $buffer_state;
                        $order_state->update();
                    }
                }
            } else {
                // Import only process order or shipped order and not imported with previous module
                $lengow_order_state = (string) $lengow_order->order_status->marketplace;
                $id_order_presta = (string) $lengow_order->order_external_id;

                if(($marketplace->getStateLengow($lengow_order_state) == 'processing'
                    || $marketplace->getStateLengow($lengow_order_state) == 'shipped' && !$id_order_presta) ) {
                    // Currency
                    $id_currency = (int) Currency::getIdByIsoCode($lengow_order->order_currency);
                    if(!$id_currency) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : no currency');
                        continue;
                    }
                    // Lang
                    $id_lang = $this->id_lang;
                    // Shop
                    $id_shop = $this->id_shop;
                    // Customer
                    if(self::$debug == true)
                        $email_address = '_' . (string) $lengow_order->billing_address->billing_email;
                    else
                        $email_address = (string) $lengow_order->billing_address->billing_email;
                    $customer = new Customer();
                    if (empty($email_address)) {
                        $email_address = 'no-mail+' . $lengow_order_id . '@' . LengowCore::getHost();
                        LengowCore::log('Order ' . $lengow_order_id . ' : no customer email, generate unique : ' . $email_address . ' ');
                    }
                    $customer->getByEmail($email_address);
                    if ($customer->id ) {
                        $id_customer = $customer->id;
                    } else {
                        if(empty($lengow_order->billing_address->billing_firstname)) {
                            $name = LengowAddress::extractName((string) $lengow_order->billing_address->billing_lastname);
                            $customer->firstname = $name['firstname'];
                            $customer->lastname = $name['lastname'];
                        } else {
                            $customer->firstname = LengowAddress::cleanName((string) $lengow_order->billing_address->billing_firstname);
                            $customer->lastname = LengowAddress::cleanName((string) $lengow_order->billing_address->billing_lastname);
                        }
                        if(empty($customer->firstname))
                            $customer->firstname = '-';
                        if(empty($customer->lastname))
                            $customer->lastname = '-';
                        $customer->company = LengowAddress::cleanName((string) $lengow_order->billing_address->billing_society);
                        $customer->email = $email_address;
                        $customer->passwd = md5(rand());
                        if (_PS_VERSION_ >= '1.5')
                            $customer->id_gender = LengowGender::getGender((string) $lengow_order->billing_address->billing_civility);
                        if (!$customer->add()) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : customer creation fail');
                        } else {
                            $id_customer = $customer->id;
                        }
                    }
                    // Address
                    $address_1 = (string) $lengow_order->billing_address->billing_address;
                    $address_2 = (string) $lengow_order->billing_address->billing_address_2;
                    if($address_3 = (string) $lengow_order->billing_address->billing_address_complement)
                        $address_2 .= $address_3;
                    if (empty($address_1) && empty($address_2)) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : no adresse');
                        continue;
                    }
                    $address_cp = (string) $lengow_order->billing_address->billing_zipcode;
                    if (empty($address_cp)) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : no zipcode');
                        continue;
                    }
                    $address_city = (string) $lengow_order->billing_address->billing_city;
                    if (empty($address_city)) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : no city');
                        continue;
                    }
                    $address_iso_country = (string) $lengow_order->billing_address->billing_country_iso;
                    if (empty($address_iso_country)) {
                        $id_country = Context::getContext()->country->id;
                        LengowCore::log('Order ' . $lengow_order_id . ' : (Warning) no country ISO');
                       // continue;
                    } else if (!$id_country = Country::getByIso((string) $lengow_order->billing_address->billing_country_iso)) {
                        $id_country = Context::getContext()->country->id;
                        LengowCore::log('Order ' . $lengow_order_id . ' : (Warning) no country ' . (string) $lengow_order->billing_address->billing_country_iso . ' (billing) exist on this PRESTASHOP');
                    }
                    // Billing address
                    if(!$billing_address = LengowAddress::getByHash((string) $lengow_order->billing_address->billing_full_address)) {
                        $billing_address = new LengowAddress();
                        $billing_address->id_customer = $id_customer;
                        $billing_address->firstname = $customer->firstname;
                        $billing_address->lastname = $customer->lastname;
                        $billing_address->id_country = $id_country;
                        $billing_address->country = (string) $lengow_order->billing_address->billing_country;
                        $billing_address->address1 = (string) $lengow_order->billing_address->billing_address;
                        $billing_address->address2 = (string) $lengow_order->billing_address->billing_address_2;
                        if((string) $lengow_order->billing_address->billing_address_complement != '')
                            $billing_address->address2 .= (string) $lengow_order->billing_address->billing_address_complement;
                        if (empty($billing_address->address1) && !empty($billing_address->address2)) {
                            $billing_address->address1 = $billing_address->address2;
                            $billing_address->address2 = '';
                        }
                        $billing_address->city = (string) $lengow_order->billing_address->billing_city;
                        $billing_address->postcode = (string) $lengow_order->billing_address->billing_zipcode;
                        $billing_address->phone = LengowCore::cleanPhone((string) $lengow_order->billing_address->billing_phone_home);
                        if((string) $lengow_order->billing_address->billing_phone_office != '')
                            $billing_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->billing_address->billing_phone_office);
                        else if((string) $lengow_order->billing_address->billing_phone_mobile != '')
                            $billing_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->billing_address->billing_phone_mobile);
                        $billing_address->alias = LengowAddress::hash((string) $lengow_order->billing_address->billing_full_address);
                        if (!$billing_address->add()) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : saving error billing address');
                            continue;
                        }
                    }
                    // Shipping address
                    // IF shipping != billing
                    if((string) $lengow_order->billing_address->billing_full_address != (string) $lengow_order->delivery_address->delivery_full_address) {
                        if(!$shipping_address = LengowAddress::getByHash((string) $lengow_order->delivery_address->delivery_full_address)) {
                            $shipping_address = new LengowAddress();
                            $shipping_address->id_customer = $id_customer;
                            if(empty($lengow_order->delivery_address->delivery_firstname)) {
                                $name = LengowAddress::cleanName((string) $lengow_order->delivery_address->delivery_lastname);
                                $shipping_address->firstname = $name['firstname'];
                                $shipping_address->lastname = $name['lastname'];
                            } else {
                                $shipping_address->firstname = self::cleanName((string) $lengow_order->delivery_address->delivery_lastname);
                                $shipping_address->lastname = self::cleanName((string) $lengow_order->delivery_address->delivery_firstname);
                            }
                            $shipping_address->id_country = $id_country;
                            if (!$country = Country::getByIso((string) $lengow_order->delivery_address->delivery_country_iso)) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : no country ' . (string) $lengow_order->delivery_address->delivery_country_iso . ' (shipping) exist on this PRESTASHOP');
                                continue;
                            }
                            $shipping_address->country = (string) $lengow_order->delivery_address->delivery_country;
                            $shipping_address->address1 = (string) $lengow_order->delivery_address->delivery_address;
                            $shipping_address->address2 = (string) $lengow_order->delivery_address->delivery_address_2;
                            if((string) $lengow_order->delivery_address->delivery_address_complement != '')
                                $shipping_address->address2 .= (string) $lengow_order->delivery_address->delivery_address_complement;
                            if (empty($shipping_address->address1) && !empty($shipping_address->address2)) {
                                $shipping_address->address1 = $shipping_address->address2;
                                $shipping_address->address2 = '';
                            }
                            $shipping_address->city = (string) $lengow_order->delivery_address->delivery_country;
                            $shipping_address->postcode = (string) $lengow_order->delivery_address->delivery_zipcode;
                            $shipping_address->phone = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_home);
                            if((string) $lengow_order->delivery_address->delivery_phone_home != '')
                                $shipping_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_home);
                            else if((string) $lengow_order->delivery_address->delivery_phone_office != '')
                                $shipping_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_office);
                            $shipping_address->alias = LengowAddress::hash((string) $lengow_order->delivery_address->delivery_full_address);
                            if (!$shipping_address->add()) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : saving error billing address');
                                continue;
                            }
                        }
                    } else {
                        $shipping_address = $billing_address;
                    }
                    $id_carrier = LengowCore::getDefaultCarrier();
                    // Order
                    $order_created_at_time = strtotime((string) $lengow_order->order_purchase_date . ' ' . (string) $lengow_order->order_purchase_heure);
                    //$date_add = date('Y-m-d H:i:s', strtotime($order->PurchaseDate));
                    $order_date_add = date('Y-m-d H:i:s', time());
                    $order_fees = 0;
                    $order_irow = 0;
                    $order_shipping_price = 0;
                    $lengow_total_order = 0;
                    // Building Cart
                    $cart = new LengowCart();
                    $cart->id_address_delivery = $shipping_address->id;
                    $cart->id_address_invoice  = $billing_address->id;
                    $cart->id_carrier = $id_carrier;
                    $cart->id_currency = $id_currency;
                    $cart->id_customer = $id_customer;
                    $cart->id_lang = $id_lang;
                    $cart->add();
                    Context::getContext()->cart = new Cart($cart->id);

                    $lengow_total_order = 0;
                    $shipping_price = 0;
                    $total_saleable_quantity = 0;

                    $lengow_new_order = false;
                    $lengow_products = array();
                    $lengow_products_order = $lengow_order->cart->products->product;
                    foreach($lengow_products_order as $lengow_product) {
                        $product_name = (string) $lengow_product->title;
                        $product_quantity  = (integer) $lengow_product->quantity;
                        $product_price = (float) $lengow_product->price_unit;

                        $array_ref = array('sku', 'idMP');
                        $n = 0;
                        $size_ref = count($array_ref);
                        $id_product = 0;
                        $id_product_attribute = 0;
                        $product = null;
                        
                        // Find product with sku or idMp
                        while (!$product && $n < $size_ref) {

                            $product_sku = (string) $lengow_product->{$array_ref[$n]};
                            $product_sku = str_replace('X', '_', $product_sku);
                            
                            // If attribute, split product sku
                            if(preg_match('`_`', $product_sku)) {
                                $array_sku = explode('_', $product_sku);
                                $id_product = $array_sku[0];
                                $id_product_attribute = $array_sku[1];
                            } else {
                                $id_product = (string) $lengow_product->{$array_ref[$n]};
                            }
                            
                            if(is_numeric($id_product)) {
                                $product = new LengowProduct($id_product, $id_lang);
                                if($product->name == '')
                                    $product = null;
                            } else {
                                $product = null;
                            }
                            $n++;
                        }
                        
                        // If there is no product
                        if(!$product || !$product->id) {
                            
                            $array_ref = array('sku', 'idMP', 'idLengow');
                            $id_product = 0;
                            $id_product_attribute = 0;
                            $i = 0;
                            
                            while ($id_product == 0 && $id_product_attribute == 0 && $i < count($array_ref)) {
                                $result = $this->_findProduct($lengow_product->{$array_ref[$i]});
                                if ($result->id_product != 0)
                                    $id_product = $result->id_product;
                                if ($result->id_product_attribute)
                                    $id_product_attribute = $result->id_product_attribute;
                                $i++;
                            }
                            
                            if ($id_product == 0) {
                                LengowCore::log('Log #1 - Order ' . $lengow_order_id . ' : product product_sku ' . $product_sku . ' doesn\'t exist');
                                unset($lengow_products[$product_sku]);
                                $cart->delete();
                                continue 2;
                            } else {
                                // Create sku
                                if($id_product_attribute)
                                    $product_sku = $id_product . '_' . $id_product_attribute;
                                else
                                    $product_sku = $id_product;
                            }

                            $product = new LengowProduct((int) $id_product, $this->id_lang);
                        }
                        
                        if (isset($lengow_products[$product_sku])) {
                            $lengow_products[$product_sku]['qty'] += $product_quantity;
                        } else {
                            $lengow_products[$product_sku] = array(
                                              'id' => $product_sku,
                                              'qty' => $product_quantity,
                                              'price' => $product_price,
                                              'name'  => $product_name,
                                              'shipping' => 0,
                                              'fees' => 0,
                                            );
                        }
                        if (_PS_VERSION_ < '1.5')
                            $product_taxes = Tax::getProductTaxRate($product->id, $shipping_address->id);
                        else
                            $product_taxes = $product->getTaxesRate($shipping_address);
                        $real_price = $product->price;
                        $lengow_products[$product_sku]['tax_rate'] = $product_taxes;
                        $lengow_products[$product_sku]['id_tax'] = isset($product->id_tax) ? $product->id_tax : false;
                        $lengow_products[$product_sku]['id_product'] = $product->id;
                        $lengow_products[$product_sku]['id_address_delivery'] = $shipping_address->id;
                        $combinations = $product->getCombinations();
                        if ($id_product_attribute > 0 && $combinations) {
                            if(isset($combinations[$id_product_attribute])) {
                                $lengow_products[$product_sku]['id_product_attribute'] = $id_product_attribute;
                            }
                        }
                        $id_product_complete = $id_product_attribute > 0 ? $id_product . '_' . $id_product_attribute : $id_product;
                        /*
                        if (LengowProduct::getRealQuantity($id_product, $id_product_attribute) - $product_quantity < 0) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : product [' . $id_product_complete . '] not enough quantity (' . $product_quantity. ' ordering, ' . LengowProduct::getRealQuantity($id_product, $id_product_attribute) . ' in stock)');
                            unset($lengow_products[$product_sku]);
                            continue 2;
                        }
                        */
                        if (!$cart->updateQty($product_quantity, $id_product, $id_product_attribute)) {
                            $r = $cart->containsProduct($id_product, $id_product_attribute, false);
                            LengowCore::log('Order ' . $lengow_order_id . ' : product cart [' . $id_product_complete . '] not enough quantity (' . $product_quantity. ' ordering, ' . LengowProduct::getRealQuantity($id_product, $id_product_attribute) . ' in stock)');
                            unset($lengow_products[$product_sku]);
                            $cart->delete();
                            continue 2;
                        }
                        $total_saleable_quantity += (integer) $lengow_product->quantity;
                        //$cart->lengow_channel = $channel;
                        $lengow_new_order = true;
                    }
                    $cart->lengow_products = $lengow_products;
                    $cart->lengow_shipping = $shipping_price;
                    $buffer_cart = $cart;
                    $payment = new LengowPaymentModule(); //LengowPaymentModule
                    $payment->active = true;
                    if($marketplace->getStateLengow($lengow_order_state) == 'shipped')
                        $id_status_import = LengowCore::getOrderState('shipped');
                    else 
                        $id_status_import = LengowCore::getOrderState('process');
                    $import_method_name = LengowCore::getImportMethodName();
                    $order_amount_pay = (float) $lengow_order->order_amount;
                    LengowCart::$current_order['products'] = $lengow_products;
                    LengowCart::$current_order['total_pay'] = $order_amount_pay;
                    LengowCart::$current_order['shipping_price'] = (float) $lengow_order->order_shipping;
                    LengowCart::$current_order['wrapping_price'] = (float) $lengow_order->order_processing_fee;
                    if($import_method_name == 'lengow') {
                        $method_name = 'Lengow';
                    } else {
                        $method_name = (string) $lengow_order->marketplace . ((string) $lengow_order->order_payment->payment_type ? ' - '. (string) $lengow_order->order_payment->payment_type : '');
                    }
                    $message = 'Import Lengow | '
                             . 'ID order : ' . (string) $lengow_order->order_id . ' | ' . "\r\n"
                             . 'Marketplace : ' . (string) $lengow_order->marketplace . ' | ' . "\r\n"
                             . 'ID flux : ' . (integer) $lengow_order->idFlux . ' | ' . "\r\n"
                             . 'Total paid : ' . (float) $lengow_order->order_amount . ' | ' . "\r\n"
                             . 'Shipping : ' . (string) $lengow_order->order_shipping . ' | ' . "\r\n"
                             . 'Message : ' . (string) $lengow_order->order_comments . "\r\n";
                    //// LengowCore::disableMail();
                    if (_PS_VERSION_ < '1.5') {
                        $order_state = new OrderState($id_status_import);
                        $buffer_state = $order_state->send_email;
                        $order_state->send_email = false;
                        $order_state->update();
                    } else {
                        LengowCore::disableMail();
                    }
                    
                    // HACK force flush
                    if (_PS_VERSION_ >= '1.5') {
                        $this->context->customer = new Customer($this->context->cart->id_customer);
                        $this->context->language = new Language($this->context->cart->id_lang);
                        $this->context->shop = new Shop($this->context->cart->id_shop);
                        $id_currency = (int)$this->context->cart->id_currency;
                        $this->context->currency = new Currency($id_currency, null, $this->context->shop->id);
                        Context::getContext()->cart->getDeliveryOptionList(null, true);
                        Context::getContext()->cart->getPackageList(true);
                        Context::getContext()->cart->getDeliveryOption(null, false, false);
                    }
                    $lengow_total_pay = (float)Tools::ps_round((float)$this->context->cart->getOrderTotal(true, Cart::BOTH, null, null, false), 2);
                    if (!$lengow_new_order) {
                        LengowCore::log('No new order to import');
                        if (Validate::isLoadedObject($cart))
                            $cart->delete();
                        continue;
                    } elseif ($payment->validateOrder($cart->id, $id_status_import, $lengow_total_pay, $method_name, $message, array(), null, true)) {
                        $id_order = $payment->currentOrder;
                        $id_flux = (integer) $lengow_order->idFlux;
                        $marketplace = (string) $lengow_order->marketplace;
                        $lengow_order_id = (string) $lengow_order->order_id;
                        $message = (string) $lengow_order->order_comments;
                        $total_paid = (float) $lengow_order->order_amount;
                        $carrier = (string) $lengow_order->order_shipping;
                        $tracking = (string) $lengow_order->tracking_informations->tracking_number;
                        $extra = Tools::jsonEncode($lengow_order);
                        LengowOrder::addLengow($id_order , 
                                               $lengow_order_id ,
                                               $id_flux , 
                                               $marketplace , 
                                               $message , 
                                               $total_paid , 
                                               $carrier , 
                                               $tracking , 
                                               $extra);
                        $new_lengow_order = new LengowOrder($id_order);
                        if(Configuration::get('LENGOW_FORCE_PRICE')) {
                            $current_order = LengowCart::$current_order;
                            // Update order if real amout paid is different from Prestashop calc total pay
                            if($new_lengow_order->total_paid != $order_amount_pay) {
                                $new_lengow_order->rebuildOrder($current_order['products'], $current_order['total_pay'], $current_order['shipping_price'], $current_order['wrapping_price']);
                            }
                        }
                        
                        // Update status on lengow if no debug
                        if(self::$debug == false) {
                            $lengow_connector = new LengowConnector((integer) LengowCore::getIdCustomer(), LengowCore::getTokenCustomer());
                            $orders = $lengow_connector->api('updatePrestaInternalOrderId', array('idClient' => LengowCore::getIdCustomer() ,
                                                                                              'idFlux' => $id_flux  ,
                                                                                              'idGroup' => LengowCore::getGroupCustomer() ,
                                                                                              'idCommandeMP' => $new_lengow_order->lengow_id_order  ,
                                                                                              'idCommandePresta' => $new_lengow_order->id));
                        }
                        LengowCore::log('Order ' . $lengow_order_id . ' : success import on presta (ORDER ' . $id_order . ')', $this->force_log_output);
                        $count_orders_added++;
                    } else {
                        LengowCore::log('Order ' . $lengow_order_id . ' : fail import on presta', $this->force_log_output);
                        if (Validate::isLoadedObject($cart))
                        $cart->delete();
                    }
                    ////LengowCore::enableMail
                    if (_PS_VERSION_ < '1.5') {
                        $order_state->send_email = $buffer_state;
                        $order_state->update();
                    } else {
                        LengowCore::enableMail();
                    }
                } else {
                    if($id_order_presta) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : already imported in Prestashop with order ID ' . $id_order_presta);
                    } else {
                        LengowCore::log('Order ' . $lengow_order_id . ' : order\'s status not available to import');
                    }
                }
            }
            unset($payment);
            unset($cart);
        }
        self::$import_start = false;
        return array('new' => $count_orders_added,
                     'update' => $count_orders_updated);
    }

    /**
     * Convert xml to array
     *
     * @param $xml_object object The SimpleXML Object 
     * @param $out array The output array
     *
     * @return array
     */
    public function toArray($xml_object, $out = array()) {
        foreach ((array) $xml_object as $index => $node)
            $out[$index] = (is_object($node)) ? $this->toArray($node) : $node;
        return $out;
    }
    
    /**
     * Try to find product by passing a SKU, EAN or Attribute ID
     *
     * @param $value SKU, EAN or Attribute ID
     *
     * @return Object
     */
    private function _findProduct($value) {
        $return = new stdClass();
        $return->id_product = 0;
        $return->id_product_attribute = 0;
        
        if (!$value)
            return $return;

        // Find the product by reference or ean13
        $id_by_reference = LengowProduct::getIdByReference($value);
        $id_by_ean = LengowProduct::getIdByEan13($value);
        $product_by_attributes = LengowProduct::searchAttributeId($value);

        if (!$id_by_ean && !$id_by_reference && !isset($product_by_attributes[0])) {
            return $return;
        } else {
            if ($id_by_reference != 0) {
                $product = new LengowProduct((int) $id_by_reference, $this->id_lang);
            }
            if ($id_by_ean != 0) {
                $product = new LengowProduct((int) $id_by_ean, $this->id_lang);
            } else if (isset($product_by_attributes[0]) && $product_by_attributes[0]) {
                $id_product = $product_by_attributes[0]['id_product'];
                $id_product_attribute = $product_by_attributes[0]['id_product_attribute'];
                $return->id_product_attribute = $id_product_attribute;
                $product = new LengowProduct((int) $id_product, $this->id_lang);
            }
            // Test if we have product
            if (!$product || !$product->id) {
                return $return;
            } else {
                $return->id_product = $product->id;
            }
        }

        return $return;
    }

}