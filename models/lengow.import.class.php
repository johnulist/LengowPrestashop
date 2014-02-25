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

require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.core.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.connector.class.php';
if (_PS_VERSION_ >= '1.5')
    require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.gender.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.address.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.cart.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.product.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.order.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.orderdetail.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.payment.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.marketplace.class.php';

if (_PS_VERSION_ < '1.5')
    require_once _PS_MODULE_DIR_ . 'lengow' . $sep . 'backward_compatibility' . $sep . 'backward.php';

/**
 * The Lengow Import Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowImportAbstract {

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
    public $single_order = false;

    /**
     * Construct the import manager
     *
     * @param $id_lang integer ID lang
     * @param $id_shop integer ID shop
     */
    public function __construct($id_lang = null, $id_shop = null) {
        $this->context = LengowCore::getContext();
        if (empty($id_lang))
            $this->id_lang = $this->context->language->id;
        if (empty($id_shop))
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

        if (array_key_exists('debug', $args) && $args['debug'] == true)
            self::$debug = true;
        elseif(Configuration::get('LENGOW_DEBUG'))
            self::$debug = true;

        if(Configuration::get('LENGOW_IS_IMPORT') === 'processing' && self::$debug != true)
            die('An import process seems already running. You can reset it on the module page configuration.');

        LengowCore::setImportProcessing();
        LengowCore::disableSendState();

        self::$import_start = true;
        $count_orders_updated = 0;
        $count_orders_added = 0;
        $lengow = new Lengow();
        $lengow_connector = new LengowConnector((integer) LengowCore::getIdCustomer(), LengowCore::getTokenCustomer());
        
        if(array_key_exists('id_order_lengow', $args) && $args['id_order_lengow'] != '' && array_key_exists('feed_id', $args) && $args['feed_id'] != '') {
            $args_order = array(
                'orderid' => $args['id_order_lengow'],
                'feed_id' => $args['feed_id'],
                'id_group' => LengowCore::getGroupCustomer()
            );
            $this->force_log_output = -1;
            $this->single_order = true;
        } else {
            $args_order = array(
                'dateFrom' => $args['dateFrom'],
                'dateTo' => $args['dateTo'],
                'id_group' => LengowCore::getGroupCustomer(),
                'state' => 'plugin'
            );
        }

        $orders = $lengow_connector->api('commands', $args_order);

        if (!is_object($orders)) {
            LengowCore::log('Error on lengow webservice', $this->force_log_output);
            LengowCore::setImportEnd();
            die();
        } else {
            $find_count_orders = count($orders->orders->order);
            LengowCore::log('Find ' . $find_count_orders . ' order' . ($find_count_orders > 1 ? 's' : ''), $this->force_log_output);
        }

        //LengowCore::debug($orders);
        $count_orders = (integer) $orders->orders_count->count_total;
        if ($count_orders == 0) {
            LengowCore::log('No orders to import between ' . $args['dateFrom'] . ' and ' . $args['dateTo'], $this->force_log_output);
            LengowCore::setImportEnd();
            return false;
        }
        foreach ($orders->orders->order as $key => $data) {
            $lengow_order = $data;

            if (self::$debug == true)
                $lengow_order_id = (string) $lengow_order->order_id . '--' . time();
            else
                $lengow_order_id = (string) $lengow_order->order_id;

            /**
             * Log into database order is processing
             */
            if(LengowCore::isProcessing($lengow_order_id) && self::$debug != true && $this->single_order == false) {
                $msg = LengowCore::getOrgerLog($lengow_order_id);
                if($msg != '')
                    LengowCore::log('Order ' . $lengow_order_id . ' : ' . $msg, $this->force_log_output);
                else
                    LengowCore::log('Order ' . $lengow_order_id . ' : Order is flagged as processing or finished, ignore it', $this->force_log_output);
                continue;
            }
            LengowCore::startProcessOrder($lengow_order_id, Tools::jsonEncode($lengow_order));

            if((int)$lengow_order->tracking_informations->tracking_deliveringByMarketPlace == 1) {
                LengowCore::log('Order ' . $lengow_order_id . ' : Shipping by ' . (string) $lengow_order->marketplace, $this->force_log_output);
                LengowCore::endProcessOrder($lengow_order_id, 0, 1, 'Shipping by ' . (string) $lengow_order->marketplace);
                continue;
            }

            $marketplace = LengowCore::getMarketplaceSingleton((string) $lengow_order->marketplace);
            $id_flux = (integer) $lengow_order->idFlux;
            
            if ((string) $lengow_order->order_status->marketplace == '') {
                LengowCore::log('Order ' . $lengow_order_id . ' : no order\'s status', $this->force_log_output);
                LengowCore::endProcessOrder($lengow_order_id, 0, 1, 'No order status');
                continue;
            }
            if ((string) $lengow_order->tracking_informations->tracking_deliveringByMarketPlace == '') {
                LengowCore::log('Order ' . $lengow_order_id . ' : delivery by the marketplace (' . $lengow_order->marketplace . ')', $this->force_log_output);
                LengowCore::endProcessOrder($lengow_order_id, 0, 1, 'Delivery by the marketplace (' . $lengow_order->marketplace . ')');
                continue;
            }

            if (LengowOrder::isAlreadyImported($lengow_order_id, $id_flux) && $this->single_order == false) {
                
                LengowCore::log('Order ' . $lengow_order_id . ' : already imported in Prestashop with order ID ' . LengowOrder::getOrderId($lengow_order_id, $id_flux), $this->force_log_output);
                LengowCore::endProcessOrder($lengow_order_id, 0, 1, 'Already imported in Prestashop with order ID ' . LengowOrder::getOrderId($lengow_order_id, $id_flux));
                
                if(self::$debug)
                    continue;

                $id_state_lengow = LengowCore::getOrderState($marketplace->getStateLengow((string) $lengow_order->order_status->marketplace));
                $order = LengowOrder::getByOrderIDFlux($lengow_order_id, $id_flux);
                // Update status' order only if in process or shipped
                if ($order->current_state != $id_state_lengow) {
                    // Change state process to shipped
                    if ($order->current_state == LengowCore::getOrderState('process') && $marketplace->getStateLengow((string) $lengow_order->order_status->marketplace) == 'shipped') {
                        // Disable Mail
                        LengowCore::disableMail();

                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState(LengowCore::getOrderState('shipped'), $order, true);
                        try {
                            if(!$error = $history->validateFields(false, true))
                                throw new Exception($error);
                            $history->add();
                        } catch (Exception $e) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : Error during add history : ' . $e->getMessage(), $this->force_log_output);
                        }
                        $tracking_number = (string) $lengow_order->tracking_informations->tracking_number;
                        if ($tracking_number) {
                            $order->shipping_number = $tracking_number;
                            try {
                                if(!$error = $order->validateFields(false, true))
                                    throw new Exception($error);
                                $order->update();
                            } catch (Exception $e) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : Error during update state to shipped : ' . $e->getMessage(), $this->force_log_output);
                                continue;
                            }
                        }
                        LengowCore::log('Order ' . $lengow_order_id . ' : update state to shipped', $this->force_log_output);
                        $count_orders_updated++;
                        // Enable mail
                        LengowCore::enableMail();
                    } else if (($order->current_state == LengowCore::getOrderState('process') // Change state process or shipped to cancel
                            || $order->current_state == LengowCore::getOrderState('shipped')) && $marketplace->getStateLengow((string) $lengow_order->order_status->marketplace) == 'cancel') {
                        // Disable Mail
                        LengowCore::disableMail();

                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState(LengowCore::getOrderState('cancel'), $order, true);
                        try {
                            if(!$error = $history->validateFields(false, true))
                                throw new Exception($error);
                            $history->add();
                        } catch (Exception $e) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : Error during update state to cancel', $this->force_log_output);
                            continue;
                        }
                        LengowCore::log('Order ' . $lengow_order_id . ' : update state to cancel', $this->force_log_output);
                        $count_orders_updated++;

                        // Enable mail
                        LengowCore::enableMail();
                    }
                }
            } else {
                // Import only process order or shipped order and not imported with previous module
                $lengow_order_state = (string) $lengow_order->order_status->marketplace;
                $id_order_presta = (string) $lengow_order->order_external_id;

                if(self::$debug == true ||  $this->single_order == true)
                    $id_order_presta = false;
                
                if (($marketplace->getStateLengow($lengow_order_state) == 'processing' || $marketplace->getStateLengow($lengow_order_state) == 'shipped') && !$id_order_presta) {
                    // Currency
                    $id_currency = (int) Currency::getIdByIsoCode($lengow_order->order_currency);
                    if (!$id_currency) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : no currency', $this->force_log_output);
                        LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'No currency');
                        continue;
                    }
                    // Lang
                    $id_lang = $this->id_lang;
                    // Shop
                    $id_shop = $this->id_shop;
                    // Customer
                    if (self::$debug == true)
                        $email_address = '_' . (string) $lengow_order->billing_address->billing_email;
                    else
                        $email_address = (string) $lengow_order->billing_address->billing_email;
                    $customer = new Customer();
                    if (empty($email_address)) {
                        $email_address = 'no-mail+' . $lengow_order_id . '@' . LengowCore::getHost();
                        LengowCore::log('Order ' . $lengow_order_id . ' : no customer email, generate unique : ' . $email_address, $this->force_log_output);
                    }
                    $customer->getByEmail($email_address);
                    if ($customer->id) {
                        $id_customer = $customer->id;
                    } else {
                        if (empty($lengow_order->billing_address->billing_firstname)) {
                            $name = LengowAddress::extractName((string) $lengow_order->billing_address->billing_lastname);
                            $customer->firstname = $name['firstname'];
                            $customer->lastname = $name['lastname'];
                        } else {
                            $customer->firstname = LengowAddress::cleanName((string) $lengow_order->billing_address->billing_firstname);
                            $customer->lastname = LengowAddress::cleanName((string) $lengow_order->billing_address->billing_lastname);
                        }
                        if (empty($customer->firstname))
                            $customer->firstname = '--';
                        if (empty($customer->lastname))
                            $customer->lastname = '--';
                        $customer->company = LengowAddress::cleanName((string) $lengow_order->billing_address->billing_society);
                        $customer->email = $email_address;
                        $customer->passwd = md5(rand());
                        if (_PS_VERSION_ >= '1.5')
                            $customer->id_gender = LengowGender::getGender((string) $lengow_order->billing_address->billing_civility);
                        
                        try {
                            if(!$error = $customer->validateFields(false, true))
                                throw new Exception($error);
                            $customer->add();
                            $id_customer = $customer->id;
                        } catch (Exception $e) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : customer creation fail : ' . $e->getMessage(), $this->force_log_output);
                            LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Customer creation fail : ' . $e->getMessage());
                            continue;
                        }
                    }
                    // Address
                    $address_1 = (string) $lengow_order->billing_address->billing_address;
                    $address_2 = (string) $lengow_order->billing_address->billing_address_2;
                    if ($address_3 = (string) $lengow_order->billing_address->billing_address_complement)
                        $address_2 .= $address_3;
                    if (empty($address_1) && empty($address_2)) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : no adresse');
                        LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'No address');
                        continue;
                    }
                    $address_cp = (string) $lengow_order->billing_address->billing_zipcode;
                    if (empty($address_cp)) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : (Warning) no zipcode');
                        $address_cp = ' ';
                    } elseif(!LengowCore::isZipCodeFormat($address_cp)) {
                        $address_cp = preg_replace('/[^0-9-]+/', '', $address_cp);
                        if(!LengowCore::isZipCodeFormat($address_cp)) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : ZipCode is not valid', $this->force_log_output);
                            LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'ZipCode is not valid -> ' . (string) $lengow_order->billing_address->billing_zipcode);
                            continue;
                        }
                    }

                    $address_city = (string) $lengow_order->billing_address->billing_city;
                    if (empty($address_city)) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : no city');
                        LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'No city');
                        continue;
                    }
                    $address_iso_country = (string) $lengow_order->billing_address->billing_country_iso;
                    if (empty($address_iso_country)) {
                        $id_country = Context::getContext()->country->id;
                        LengowCore::log('Order ' . $lengow_order_id . ' : (Warning) no country ISO', $this->force_log_output);
                    } else if (!$id_country = Country::getByIso((string) $lengow_order->billing_address->billing_country_iso)) {
                        $id_country = Context::getContext()->country->id;
                        LengowCore::log('Order ' . $lengow_order_id . ' : (Warning) no country ' . (string) $lengow_order->billing_address->billing_country_iso . ' (billing) exist on this PRESTASHOP', $this->force_log_output);
                    }
                    // Billing address
                    if (!$billing_address = LengowAddress::getByHash((string) $lengow_order->billing_address->billing_full_address)) {
                        $billing_address = new LengowAddress();
                        $billing_address->id_customer = $id_customer;
                        $billing_address->firstname = $customer->firstname;
                        $billing_address->lastname = $customer->lastname;
                        $billing_address->id_country = $id_country;
                        $billing_address->country = (string) $lengow_order->billing_address->billing_country;
                        $billing_address->address1 = (string) $lengow_order->billing_address->billing_address;
                        $billing_address->address2 = (string) $lengow_order->billing_address->billing_address_2;
                        if((string) $lengow_order->billing_address->billing_society != '')
                                $billing_address->company = (string) $lengow_order->billing_address->billing_society;
                        if ((string) $lengow_order->billing_address->billing_address_complement != '')
                            $billing_address->address2 .= (string) $lengow_order->billing_address->billing_address_complement;
                        if (empty($billing_address->address1) && !empty($billing_address->address2)) {
                            $billing_address->address1 = $billing_address->address2;
                            $billing_address->address2 = '';
                        }
                        $billing_address->address1 = preg_replace('/[!<>?=+@{}_$%]/sim', '', $billing_address->address1);
                        $billing_address->address2 = preg_replace('/[!<>?=+@{}_$%]/sim', '', $billing_address->address2);
                        $billing_address->city = preg_replace('/[!<>?=#+;@{}_$%]/sim', '', (string) $lengow_order->billing_address->billing_city);
                        $billing_address->postcode = $address_cp;
                        if(empty($billing_address->postcode))
                            $billing_address->postcode = ' ';

                        // Phone
                        $billing_address->phone = LengowCore::cleanPhone((string) $lengow_order->billing_address->billing_phone_home);
                        if($billing_address->phone == '')
                            $billing_address->phone  = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_home);
                        if ((string) $lengow_order->billing_address->billing_phone_office != '')
                            $billing_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->billing_address->billing_phone_office);
                        else if ((string) $lengow_order->billing_address->billing_phone_mobile != '')
                            $billing_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->billing_address->billing_phone_mobile);

                        if($billing_address->phone_mobile == '') {
                            if ((string) $lengow_order->delivery_address->delivery_phone_office != '')
                                $billing_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_office);
                            else if ((string) $lengow_order->delivery_address->delivery_phone_mobile != '')
                                $billing_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_mobile);
                        }
                        // Alias
                        $billing_address->alias = LengowAddress::hash((string) $lengow_order->billing_address->billing_full_address);
                        try {
                            if(!$error = $billing_address->validateFields(false, true))
                                throw new Exception($error);
                            $billing_address->add();
                        } catch (Exception $e) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : Saving error billing address : ' . $e->getMessage(), $this->force_log_output);
                            LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Saving error billing address : ' . $e->getMessage());
                            continue;
                        }
                    }
                    // Shipping address
                    // IF shipping != billing
                    if (empty($lengow_order->delivery_address->delivery_firstname)) {
                        $name = LengowAddress::extractName((string) $lengow_order->delivery_address->delivery_lastname);
                        $shipping_address_firstname = $name['firstname'];
                        $shipping_address_lastname = $name['lastname'];
                    } else {
                        $shipping_address_firstname = LengowAddress::cleanName((string) $lengow_order->delivery_address->delivery_firstname);
                        $shipping_address_lastname = LengowAddress::cleanName((string) $lengow_order->delivery_address->delivery_lastname);
                    }
                    if (empty($shipping_address_firstname))
                        $shipping_address_firstname = '--';
                    if (empty($shipping_address_lastname))
                        $shipping_address_lastname = '--';

                    $shipping_zipcode = (string) $lengow_order->delivery_address->delivery_zipcode;
                    if (empty($shipping_zipcode)) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : (Warning) no shipping zipcode');
                        $shipping_zipcode = ' ';
                    } elseif(!LengowCore::isZipCodeFormat($shipping_zipcode)) {
                        $shipping_zipcode = preg_replace('/[^0-9-]+/', '', $shipping_zipcode);
                        if(!LengowCore::isZipCodeFormat($shipping_zipcode)) {
                            LengowCore::log('Order ' . $lengow_order_id . ' : Shipping ZipCode is not valid', $this->force_log_output);
                            LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Shipping ZipCode is not valid -> ' . (string) $lengow_order->shipping_address->shipping_zipcode);
                            continue;
                        }
                    }

                    if ((string) $billing_address->firstname . (string) $billing_address->lastname . (string) $lengow_order->billing_address->billing_full_address 
                        != $shipping_address_firstname . $shipping_address_lastname . (string) $lengow_order->delivery_address->delivery_full_address) {
                        if (!$shipping_address = LengowAddress::getByHash($shipping_address_firstname . $shipping_address_lastname . (string) $lengow_order->delivery_address->delivery_full_address)) {
                            $shipping_address = new LengowAddress();
                            $shipping_address->id_customer = $id_customer;
                            if (empty($lengow_order->delivery_address->delivery_firstname)) {
                                $name = LengowAddress::extractName((string) $lengow_order->delivery_address->delivery_lastname);
                                $shipping_address->firstname = $name['firstname'];
                                $shipping_address->lastname = $name['lastname'];
                            } else {
                                $shipping_address->firstname = LengowAddress::cleanName((string) $lengow_order->delivery_address->delivery_lastname);
                                $shipping_address->lastname = LengowAddress::cleanName((string) $lengow_order->delivery_address->delivery_firstname);
                            }
                            if (empty($shipping_address->firstname))
                                $shipping_address->firstname = '--';
                            if (empty($shipping_address->lastname))
                                $shipping_address->lastname = '--';
                            $shipping_address->id_country = $id_country;
                            if (!$country = Country::getByIso((string) $lengow_order->delivery_address->delivery_country_iso)) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : no country ' . (string) $lengow_order->delivery_address->delivery_country_iso . ' (shipping) exist on this PRESTASHOP', $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'No country ' . (string) $lengow_order->delivery_address->delivery_country_iso . ' (shipping) exist on this PRESTASHOP');
                                continue;
                            }
                            $shipping_address->country = (string) $lengow_order->delivery_address->delivery_country;
                            $shipping_address->address1 = (string) $lengow_order->delivery_address->delivery_address;
                            $shipping_address->address2 = (string) $lengow_order->delivery_address->delivery_address_2;
                            if((string) $lengow_order->delivery_address->delivery_society != '')
                                $shipping_address->company = (string) $lengow_order->delivery_address->delivery_society;
                            if ((string) $lengow_order->delivery_address->delivery_address_complement != '')
                                $shipping_address->address2 .= (string) $lengow_order->delivery_address->delivery_address_complement;
                            if (empty($shipping_address->address1) && !empty($shipping_address->address2)) {
                                $shipping_address->address1 = $shipping_address->address2;
                                $shipping_address->address2 = '';
                            }
                            $shipping_address->address1 = preg_replace('/[!<>?=+@{}_$%]/sim', '', $shipping_address->address1);
                            $shipping_address->address2 = preg_replace('/[!<>?=+@{}_$%]/sim', '', $shipping_address->address2);
                            $shipping_address->city = preg_replace('/[!<>?=#+;@{}_$%]/sim', '', (string) $lengow_order->delivery_address->delivery_city);
                            $shipping_address->postcode = $shipping_zipcode;
                            if(empty($shipping_address->postcode))
                                $shipping_address->postcode = ' ';
                            $shipping_address->phone = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_home);
                            if ((string) $lengow_order->delivery_address->delivery_phone_home != '')
                                $shipping_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_home);
                            else if ((string) $lengow_order->delivery_address->delivery_phone_office != '')
                                $shipping_address->phone_mobile = LengowCore::cleanPhone((string) $lengow_order->delivery_address->delivery_phone_office);
                            $shipping_address->alias = LengowAddress::hash((string) $shipping_address->firstname . (string) $shipping_address->lastname . (string) $lengow_order->delivery_address->delivery_full_address);
                            try {
                                if(!$error = $shipping_address->validateFields(false, true))
                                    throw new Exception($error);
                                $shipping_address->add();
                            } catch (Exception $e) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : Saving error shipping address : ' . $e->getMessage(), $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Saving error shipping address : ' . $e->getMessage());
                                continue;
                            }
                        }
                    } else {
                        $shipping_address = $billing_address;
                    }
                    $id_carrier = LengowCore::getDefaultCarrier();
                    $id_carrier = $this->getRealCarrier($id_carrier, $lengow_order->tracking_informations);

                    // Order
                    $order_created_at_time = strtotime((string) $lengow_order->order_purchase_date . ' ' . (string) $lengow_order->order_purchase_heure);
                    $order_date_add = date('Y-m-d H:i:s', time());
                    $order_fees = 0;
                    $order_irow = 0;
                    $order_shipping_price = 0;
                    $lengow_total_order = 0;
                    // Building Cart
                    $cart = new LengowCart();
                    $cart->id_address_delivery = $shipping_address->id;
                    $cart->id_address_invoice = $billing_address->id;
                    $cart->id_carrier = $id_carrier;
                    $cart->id_currency = $id_currency;
                    $cart->id_customer = $id_customer;
                    $cart->id_lang = $id_lang;
                    try {
                        if(!$error = $cart->validateFields(false, true))
                            throw new Exception($error);
                        $cart->add();
                    } catch (Exception $e) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : Add Cart Exception : ' . $e->getMessage(), $this->force_log_output);
                        LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Add Cart Exception : ' . $e->getMessage());
                        continue;
                    }
                    Context::getContext()->cart = new Cart($cart->id);
                    $lengow_total_order = 0;
                    $shipping_price = 0;
                    $total_saleable_quantity = 0;

                    $lengow_new_order = false;
                    $lengow_products = array();
                    $lengow_products_order = $lengow_order->cart->products->product;

                    foreach ($lengow_products_order as $lengow_product) {

                        $product_name = (string) $lengow_product->title;
                        $product_quantity = (integer) $lengow_product->quantity;
                        $product_price = (float) $lengow_product->price_unit;
                        $array_ref = array('sku', 'idMP');
                        $size_ref = count($array_ref);
                        $id_product_attribute = 0;
                        $id_product = 0;
                        $product = null;
                        $n = 0;
                        $product_field = strtolower((string) $lengow_product->sku['field'][0]);
                        if($product_field == 'identifiant_unique')
                            $product_field = 'id_product';

                        while(!$product && $n < $size_ref) {
                            $product_ids = LengowProduct::getIdsProduct($lengow_product, $array_ref[$n]);
                            $id_product = $product_ids['id_product'];
                            $id_product_attribute = $product_ids['id_product_attribute'];

                            if(array_key_exists($product_field, LengowExport::$DEFAULT_FIELDS)) {
                                switch(LengowExport::$DEFAULT_FIELDS[$product_field]) {
                                    case 'reference':
                                        $product_ids = LengowProduct::findProduct('reference', $lengow_product->{$array_ref[$n]});
                                        $product_sku = (string) $lengow_product->reference;
                                        $product_sku = str_replace('\_', '_', $product_sku);
                                        $product_sku = str_replace('X', '_', $product_sku);
                                        $id_product = $product_ids['id_product'];
                                        $id_product_attribute = (array_key_exists('id_product_attribute', $product_ids) ? $product_ids['id_product_attribute'] : 0);
                                        break;
                                    case 'ean':
                                        $product_ids = LengowProduct::findProduct('ean13', $lengow_product->{$array_ref[$n]});
                                        $product_sku = (string) $lengow_product->ean13;
                                        $product_sku = str_replace('\_', '_', $product_sku);
                                        $product_sku = str_replace('X', '_', $product_sku);
                                        $id_product = $product_ids['id_product'];
                                        $id_product_attribute = (array_key_exists('id_product_attribute', $product_ids) ? $product_ids['id_product_attribute'] : 0);
                                        break;
                                    case 'upc':
                                        $product_ids = LengowProduct::findProduct('upc', $lengow_product->{$array_ref[$n]});
                                        $product_sku = (string) $lengow_product->upc;
                                        $product_sku = str_replace('\_', '_', $product_sku);
                                        $product_sku = str_replace('X', '_', $product_sku);
                                        $id_product = $product_ids['id_product'];
                                        $id_product_attribute = (array_key_exists('id_product_attribute', $product_ids) ? $product_ids['id_product_attribute'] : 0);
                                        break;
                                    default:
                                        $product_sku = (string) $lengow_product->sku;
                                        $product_sku = str_replace('\_', '_', $product_sku);
                                        $product_sku = str_replace('X', '_', $product_sku);
                                        break;
                                }
                            }

                            if(is_numeric($id_product)) {
                                $product = new LengowProduct($id_product);

                                // Check Product
                                if($product->name == '') {
                                    $product = null;
                                } else {
                                    if ($id_product_attribute)
                                        $product_sku = $id_product . '_' . $id_product_attribute;
                                    else
                                        $product_sku = $id_product;
                                }
                            } else {
                                $product = null;
                            }

                            $n++;
                        }

                        // If there is no product
                        if (!$product || !$product->id) {

                            $array_ref = array('sku', 'idMP', 'idLengow');
                            $id_product = 0;
                            $id_product_attribute = 0;
                            $i = 0;

                            while ($id_product == 0 && $id_product_attribute == 0 && $i < count($array_ref)) {
                                $product_sku = (string) $lengow_product->{$array_ref[$i]};
                                $product_sku = str_replace('\_', '_', $product_sku);
                                $product_sku = str_replace('X', '_', $product_sku);
                                $result = $this->_findProduct($lengow_product->{$array_ref[$i]});
                                
                                if ($result->id_product != 0)
                                    $id_product = $result->id_product;
                                if ($result->id_product_attribute)
                                    $id_product_attribute = $result->id_product_attribute;
                                $i++;
                            }
                            
                            if ($id_product == 0) {
                                LengowCore::log('Log - Order ' . $lengow_order_id . ' : product product_sku ' . $product_sku . ' doesn\'t exist', $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Product ' . $product_sku . ' doesn\'t exist');
                                unset($lengow_products[$product_sku]);
                                $cart->delete();
                                continue 2;
                            } else {
                                // Create sku
                                if ($id_product_attribute)
                                    $product_sku = $id_product . '_' . $id_product_attribute;
                                else
                                    $product_sku = $id_product;
                            }

                            $product = new LengowProduct((int) $id_product, $this->id_lang);
                        }
                        // Test if product is active
                        if($product->active != 1) {
                            if(Configuration::get('LENGOW_IMPORT_FORCE_PRODUCT') != 1) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : Product ' . $product_sku . ' is disabled in your back office', $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Product ' . $product_sku . ' is disabled in your back office');
                                continue 2;
                            }
                        }
                        if($product->available_for_order != 1) {
                            if(Configuration::get('LENGOW_IMPORT_FORCE_PRODUCT') != 1) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : Product ' . $product_sku . ' is not available for order', $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Product ' . $product_sku . ' is not available for order');
                                continue 2;
                            }
                        }
                        if (isset($lengow_products[$product_sku])) {
                            $lengow_products[$product_sku]['qty'] += $product_quantity;
                        } else {
                            $lengow_products[$product_sku] = array(
                                'id' => $product_sku,
                                'qty' => $product_quantity,
                                'price' => $product_price,
                                'name' => $product_name,
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
                            if (isset($combinations[$id_product_attribute])) {
                                $lengow_products[$product_sku]['id_product_attribute'] = $id_product_attribute;
                            }
                        }
                        $id_product_complete = $id_product_attribute > 0 ? $id_product . '_' . $id_product_attribute : $id_product;

                        if(Configuration::get('LENGOW_IMPORT_FORCE_PRODUCT') == true) {
                            // Force disabled or out of stock product
                            if($cart->containsProduct($id_product, $id_product_attribute)) {
                                if(_PS_VERSION_ >= '1.5') {
                                    $result_update = Db::getInstance()->execute('
                                        UPDATE `'._DB_PREFIX_.'cart_product`
                                        SET `quantity` = `quantity` + '.(int)$product_quantity.', `date_add` = NOW()
                                        WHERE `id_product` = '.(int)$id_product.
                                        (!empty($id_product_attribute) ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '').'
                                        AND `id_cart` = '.(int)$cart->id.(Configuration::get('PS_ALLOW_MULTISHIPPING') && $cart->isMultiAddressDelivery() ? ' AND `id_address_delivery` = '.(int)$shipping_address->id : '').'
                                        LIMIT 1'
                                    );
                                } else {
                                    $result_update = Db::getInstance()->autoExecute(_DB_PREFIX_ . 'cart_product', array(
                                        'quantity' => '`quantity` + ' . (int)$product_quantity,
                                        'date_add' => date('Y-m-d H:i:s')
                                    ), 'UPDATE', '`id_product` = ' . (int)$id_product);
                                }
                            } else {
                                if(_PS_VERSION_ >= '1.5') {
                                    $result_add = Db::getInstance()->insert('cart_product', array(
                                        'id_product' => (int)$id_product,
                                        'id_product_attribute' => (int)$id_product_attribute,
                                        'id_cart' => (int)$cart->id,
                                        'id_address_delivery' => (int)$shipping_address->id,
                                        'id_shop' => $this->context->shop->id,
                                        'quantity' => (int)$product_quantity,
                                        'date_add' => date('Y-m-d H:i:s')
                                    ));
                                } else {
                                    $result_add = Db::getInstance()->autoExecute(_DB_PREFIX_ . 'cart_product', array(
                                        'id_product' => (int)$id_product,
                                        'id_product_attribute' => (int)$id_product_attribute,
                                        'id_cart' => (int)$cart->id,
                                        'quantity' => (int)$product_quantity,
                                        'date_add' => date('Y-m-d H:i:s')
                                    ), 'INSERT');
                                }
                            }

                            if(isset($result_add) && $result_add === false) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : Error to add product [' . $id_product_complete . '] on cart', $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Error to add product [' . $id_product_complete . '] on cart');
                                continue 2;
                            }
                            if(isset($result_update) && $result_update === false) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : Error to add product [' . $id_product_complete . '] on cart', $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Error to add product [' . $id_product_complete . '] on cart');
                                continue 2;
                            }
                        } else {
                            // Basic functionnality
                            if ($product->active == 0 || !$cart->updateQty($product_quantity, $id_product, $id_product_attribute)) {
                                $r = $cart->containsProduct($id_product, $id_product_attribute, false);
                                if($product->active == 0) {
                                    LengowCore::log('Order ' . $lengow_order_id . ' : product cart [' . $id_product_complete . '] is disabled', $this->force_log_output);
                                    LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Product ' . $product_sku . ' is disabled in your back office');
                                }
                                else {
                                    $msg = 'Order ' . $lengow_order_id . ' : product cart [' . $id_product_complete . '] not enough quantity (' . $product_quantity . ' ordering, ' . LengowProduct::getRealQuantity($id_product, $id_product_attribute) . ' in stock)';
                                    LengowCore::log($msg, $this->force_log_output);
                                    LengowCore::endProcessOrder($lengow_order_id, 1, 0, $msg);
                                }
                                unset($lengow_products[$product_sku]);
                                $cart->delete();
                                continue 2;
                            }
                        }

                        $total_saleable_quantity += (integer) $lengow_product->quantity;
                        $lengow_new_order = true;
                    }

                    // Check product wharehouse
                    if(_PS_VERSION_ >= '1.5' && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') == 1) {
                        foreach($cart->getPackageList() as $key => $value) {
                            if(count($value) > 1) {
                                LengowCore::log('Order ' . $lengow_order_id . ' : Products are stocked in different warehouse', $this->force_log_output);
                                LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Order ' . $lengow_order_id . ' : Products are stocked in diferent warehouse.');
                                continue 2;
                            }
                        }
                    }

                    $cart->lengow_products = $lengow_products;
                    $cart->lengow_shipping = $shipping_price;
                    $buffer_cart = $cart;
                    $payment = new LengowPaymentModule();
                    $payment->active = true;
                    if ($marketplace->getStateLengow($lengow_order_state) == 'shipped')
                        $id_status_import = LengowCore::getOrderState('shipped');
                    else
                        $id_status_import = LengowCore::getOrderState('process');
                    $import_method_name = LengowCore::getImportMethodName();
                    $order_amount_pay = (float) $lengow_order->order_amount;
                    LengowCart::$current_order['products'] = $lengow_products;
                    LengowCart::$current_order['total_pay'] = $order_amount_pay;
                    LengowCart::$current_order['shipping_price'] = (float) $lengow_order->order_shipping;
                    LengowCart::$current_order['wrapping_price'] = (float) $lengow_order->order_processing_fee;
                    if ($import_method_name == 'lengow') {
                        $method_name = 'Lengow';
                    } else {
                        $method_name = (string) $lengow_order->marketplace . ((string) $lengow_order->order_payment->payment_type ? ' - ' . (string) $lengow_order->order_payment->payment_type : '');
                    }
                    $message = 'Import Lengow | '
                            . 'ID order : ' . (string) $lengow_order->order_id . ' | ' . "\r\n"
                            . 'Marketplace : ' . (string) $lengow_order->marketplace . ' | ' . "\r\n"
                            . 'ID flux : ' . (integer) $lengow_order->idFlux . ' | ' . "\r\n"
                            . 'Total paid : ' . (float) $lengow_order->order_amount . ' | ' . "\r\n"
                            . 'Shipping : ' . (string) $lengow_order->order_shipping . ' | ' . "\r\n"
                            . 'Message : ' . (string) $lengow_order->order_comments . "\r\n";


                    LengowCore::disableMail();

                    // HACK force flush
                    if (_PS_VERSION_ >= '1.5') {
                        $this->context->customer = new Customer($this->context->cart->id_customer);
                        $this->context->language = new Language($this->context->cart->id_lang);
                        $this->context->shop = new Shop($this->context->cart->id_shop);
                        $id_currency = (int) $this->context->cart->id_currency;
                        $this->context->currency = new Currency($id_currency, null, $this->context->shop->id);
                        Context::getContext()->cart->getDeliveryOptionList(null, true);
                        Context::getContext()->cart->getPackageList(true);
                        Context::getContext()->cart->getDeliveryOption(null, false, false);
                    }
                    $lengow_total_pay = (float) Tools::ps_round((float) $this->context->cart->getOrderTotal(true, Cart::BOTH, null, null, false), 2);
                    //$lengow_total_pay = (float) Tools::ps_round($cart->getOrderTotal(true, Cart::BOTH), 2);

                    if(_PS_VERSION_ >= '1.5.2' && _PS_VERSION_ <= '1.5.3.1')
                        $validateOrder = 'validateOrder152';
                    else
                        $validateOrder = 'validateOrder';

                    // SoColissimo compatibility
                    if(LengowCore::isColissimo()) {
                        $shipping_address_complement = ($lengow_order->delivery_address->delivery_address_complement != '' ? pSQL($lengow_order->delivery_address->delivery_address_complement) : '');
                        $shipping_society = ($lengow_order->delivery_address->delivery_country->delivery_society != '' ? pSQL($lengow_order->delivery_address->delivery_country->delivery_society) : '');

                        $this->addColissimoAddress($cart->id, $id_customer, $shipping_address_lastname, $shipping_address_firstname, $shipping_address_complement, 
                                $shipping_address->address1, $shipping_address->address2, $shipping_address->postcode, $shipping_address->city, 
                                $shipping_address->phone_mobile, $customer->email, $shipping_society);
                    }

                    if (!$lengow_new_order) {
                        LengowCore::log('No new order to import', $this->force_log_output);
                        LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'No new order to import');
                        if (Validate::isLoadedObject($cart))
                            $cart->delete();
                        LengowCore::enableMail();
                        continue;
                    } elseif ($payment->$validateOrder($cart->id, $id_status_import, $lengow_total_pay, $method_name, $message, array(), null, true)) {
                        $id_order = $payment->currentOrder;
                        $id_flux = (integer) $lengow_order->idFlux;
                        $marketplace = (string) $lengow_order->marketplace;
                        //$lengow_order_id = (string) $lengow_order->order_id;
                        $message = (string) $lengow_order->order_comments;
                        $total_paid = (float) $lengow_order->order_amount;
                        $carrier = (string) $lengow_order->order_shipping;
                        $tracking = (string) $lengow_order->tracking_informations->tracking_number;
                        $extra = Tools::jsonEncode($lengow_order);
                        LengowOrder::addLengow($id_order, $lengow_order_id, $id_flux, $marketplace, $message, $total_paid, $carrier, $tracking, $extra);
                        $new_lengow_order = new LengowOrder($id_order);

                        if (Configuration::get('LENGOW_FORCE_PRICE')) {
                            $current_order = LengowCart::$current_order;
                            // Update order if real amout paid is different from Prestashop calc total pay
                            if ($new_lengow_order->total_paid != $order_amount_pay) {
                                $new_lengow_order->rebuildOrder($current_order['products'], $current_order['total_pay'], $current_order['shipping_price'], $current_order['wrapping_price']);
                            }
                        }

                        if(_PS_VERSION_ >= '1.5') {
                            if($new_lengow_order->getIdOrderCarrier() != LengowCore::getDefaultCarrier())
                                $new_lengow_order->forceCarrier(LengowCore::getDefaultCarrier());
                        }

                        if(LengowCore::isMondialRelay()) {
                            if($lengow_order->tracking_informations->tracking_carrier == 'Mondial Relay' &&
                                $lengow_order->tracking_informations->tracking_relay != '') {
                                $relay = self::getRelayPoint($lengow_order->tracking_informations->tracking_relay, $cart->id_address_delivery);
                                if($relay !== false)
                                    $new_lengow_order->addRelayPoint($relay);
                                else
                                    LengowCore::log('Unable to find Relay Point', $this->force_log_output);
                            }
                        }

                        // Update status on lengow if no debug
                        if (self::$debug == false) {
                            $lengow_connector = new LengowConnector((integer) LengowCore::getIdCustomer(), LengowCore::getTokenCustomer());
                            $orders = $lengow_connector->api('updatePrestaInternalOrderId', array('idClient' => LengowCore::getIdCustomer() ,
                              'idFlux' => $id_flux,
                              'idGroup' => LengowCore::getGroupCustomer(),
                              'idCommandeMP' => $new_lengow_order->lengow_id_order,
                              'idCommandePresta' => $new_lengow_order->id));
                        }
                        LengowCore::log('Order ' . $lengow_order_id . ' : success import on presta (ORDER ' . $id_order . ')', $this->force_log_output);
                        LengowCore::endProcessOrder($lengow_order_id, 0, 1, 'Success import on presta (ORDER ' . $id_order . ')');

                        // Custom Hook
                        if(_PS_VERSION_ >= '1.5') {
                            Hook::exec('actionValidateLengowOrder', array(
                                'id_order' => $id_order,
                                'lengow_order_id' => $lengow_order_id
                            ));
                        }

                        $count_orders_added++;
                        if(Tools::getValue('limit') != '' && Tools::getValue('limit') > 0) {
                            if($count_orders_added == (int) Tools::getValue('limit')) {
                                LengowCore::setImportEnd();
                                LengowCore::enableMail();
                                die();
                            }
                        }
                    } else {
                        LengowCore::log('Order ' . $lengow_order_id . ' : fail import on presta', $this->force_log_output);
                        LengowCore::endProcessOrder($lengow_order_id, 1, 0, 'Fail import on presta');
                        if (Validate::isLoadedObject($cart))
                            $cart->delete();
                    }
                    LengowCore::enableMail();
                } else {
                    if ($id_order_presta) {
                        LengowCore::log('Order ' . $lengow_order_id . ' : already imported in Prestashop with order ID ' . $id_order_presta, $this->force_log_output);
                        LengowCore::endProcessOrder($lengow_order_id, 0, 1, 'Already imported in Prestashop with order ID ' . $id_order_presta);
                    } else {
                        LengowCore::log('Order ' . $lengow_order_id . ' : order\'s status not available to import', $this->force_log_output);
                        LengowCore::deleteProcessOrder($lengow_order_id);
                    }
                }
            }
            unset($payment);
            unset($cart);
        }
        self::$import_start = false;
        LengowCore::setImportEnd();
        if(array_key_exists('id_order_lengow', $args) && $args['id_order_lengow'] != '') {
            if($new_lengow_order->id != '')
                return  $new_lengow_order->id;
            else
                return false;
        }
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
        $id_by_upc = LengowProduct::getIdByUpc($value);
        $product_by_attributes = LengowProduct::searchAttributeId($value);

        if (!$id_by_ean && !$id_by_reference && !$id_by_upc && !isset($product_by_attributes[0])) {
            return $return;
        } else {
            if ($id_by_reference != 0) {
                $product = new LengowProduct((int) $id_by_reference, $this->id_lang);
            } else if($id_by_upc) {
                $product = new LengowProduct((int) $id_by_upc, $this->id_lang);
            } else if ($id_by_ean != 0) {
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

    protected function getRealCarrier($id_carrier, $tracking_informations) {
        return $id_carrier;

        $tracking_method = $tracking_informations->tracking_method;
        $tracking_carrier = $tracking_informations->tracking_carrier;
        $tracking_relay = $tracking_informations->tracking_relay;

        if($tracking_method == '' && $tracking_carrier == '' && $tracking_relay == '')
            return $id_carrier;
    }

    /**
     * Save delivery address into socolissimo delivery table
     * Fix for old version of Socolissimo module
     *
     * @return boolean
     */
    protected function addColissimoAddress($cart_id, $id_customer, $lastname, $firstname, $complement, $address1, $address2, $postcode, $city, $phone_mobile, $email, $society, $dvmode = 'DOM') {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'socolissimo_delivery_info (
                    `id_cart`, `id_customer`, `delivery_mode`, `prid`, `prname`, `prfirstname`, `prcompladress`,
                    `pradress1`, `pradress2`, `pradress3`, `pradress4`, `przipcode`, `prtown`, `cephonenumber`, `ceemail` ,
                    `cecompanyname`, `cedeliveryinformation`, `cedoorcode1`, `cedoorcode2`)
                VALUES (' . $cart_id . ', ' . $id_customer . ',
                \'' . pSQL($dvmode) . '\',
                \'\',
                \'' . pSQL($lastname) . '\',
                \'' . pSQL($firstname) . '\',
                \'' . pSQL($complement) . '\',
                \'' . pSQL($address1) . '\',
                \'' . pSQL($address2) . '\',
                \'' . pSQL($address1) . '\',
                \'' . pSQL($address2) . '\',
                \'' . pSQL($postcode) . '\',
                \'' . pSQL($city) . '\',
                \'' . pSQL(LengowCore::cleanPhone($phone_mobile)) .'\',
                \'' . pSQL($email) . '\',
                \'' . pSQL($society) . '\',
                \'\',
                \'\',
                \'\')';

        if (Db::getInstance()->execute($sql))
            return true;

        return false;
    }

    /**
     * Get RelayPoint info with Mondial Relay module
     *
     * @param string Id Tracking Relay
     * @param int Id Address Delivery
     * 
     * @return boolean|array False if not found, Relay array
     */
    public static function getRelayPoint($tracking_relay, $id_address_delivery) {
        require_once _PS_MODULE_DIR_ . 'mondialrelay' . DS . 'classes' . DS . 'MRRelayDetail.php';
        $tracking_relay = str_pad($tracking_relay, 6, '0', STR_PAD_LEFT);
        $params = array(
            'relayPointNumList' => array($tracking_relay),
            'id_address_delivery' => $id_address_delivery
        );
        $MRRelayDetail = new MRRelayDetail($params, new MondialRelay());
        $MRRelayDetail->init();
        $MRRelayDetail->send();

        $result = $MRRelayDetail->getResult();

        if(empty($result['error'][0]) && array_key_exists($tracking_relay, $result['success'])) {
            $relay = $result['success'][$tracking_relay];
            return $relay;
        } else {
            return false;
        }
    }

}
