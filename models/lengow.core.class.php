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
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.option.class.php';
require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.connector.class.php';

/**
 * The Lengow Core Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowCoreAbstract {
    /**
     * Version.
     */

    const VERSION = '1.0.0';

    /**
     * Lengow module.
     */
    public static $module;

    /**
     * Presta context.
     */
    public static $context;

    /**
     * Presta log file instance.
     */
    public static $log_instance;

    /**
     * Buffer mail value.
     */
    public static $buffer_mail_value;

    /**
     * Registers.
     */
    public static $registers;

    /**
     * Send state.
     * To not send a new order's state @ lengow
     */
    public static $send_state = true;

    /**
     * Days life log
     */
    public static $log_life = 7;

    /**
     * Lengow shipping name.
     */
    public static $FORMAT_LENGOW = array(
        'csv',
        'xml',
        'json',
        'yaml',
    );

    /**
     * Lengow tracker types.
     */
    public static $TRACKER_LENGOW = array(
        'none' => 'No tracker',
        'tagcapsule' => 'TagCapsule',
        'simpletag' => 'SimpleTag',
    );

    /**
     * Lengow shipping name.
     */
    public static $SHIPPING_LENGOW = array(
        'lengow' => 'Lengow',
        'marketplace' => 'Markeplace\'s name',
    );

    /**
     * Lengow IP.
     */
    public static $IPS_LENGOW = array(
        '95.131.137.18',
        '95.131.137.19',
        '95.131.137.21',
        '95.131.137.26',
        '95.131.137.27',
        '88.164.17.227',
        '88.164.17.216',
        '109.190.78.5',
    );

    /**
     * Default fields to export
     */
    public static $DEFAULT_FIELDS = array(
        'id_product',
        'name_product',
        'reference_product',
        'supplier_reference',
        'manufacturer',
        'category',
        'description',
        'description_short',
        'price_product',
        'wholesale_price',
        'price_ht',
        'price_reduction',
        'pourcentage_reduction',
        'quantity',
        'weight',
        'ean',
        'upc',
        'ecotax',
        'available_product',
        'url_product',
        'image_product',
        'fdp',
        'id_mere',
        'delais_livraison',
        'image_product_2',
        'image_product_3',
        'reduction_from',
        'reduction_to',
        'meta_keywords',
        'meta_description',
        'url_rewrite',
        'product_type',
        'product_variation',
        'currency',
        'condition'
    );

    /**
     * Lengow XML Marketplace configuration.
     */
    public static $MP_CONF_LENGOW = 'http://kml.lengow.com/mp.xml';
    public static $image_type_cache;

    /**
     * Prestashop context.
     *
     * @param object $context The current context
     *
     */
    public static function getContext() {
        self::$context = self::$context ? self::$context : Context::getContext();
        return self::$context;
    }

    /**
     * Dependance injection of Lengow module.
     *
     * @param object $module The Lengow module
     *
     */
    public static function setModule($module) {
        self::$module = $module;
    }

    /**
     * The Prestashop compare version with current version.
     *
     * @param varchar $version The version to compare
     *
     * @return boolean The comparaison
     */
    public static function compareVersion($version = '1.4') {
        $sub_verison = substr(_PS_VERSION_, 0, 3);
        return version_compare($sub_verison, $version);
    }

    /**
     * The export format aivalable.
     *
     * @return array Formats
     */
    public static function getExportFormats() {
        $array_formats = array();
        foreach (self::$FORMAT_LENGOW as $value) {
            $array_formats[] = new LengowOption($value, $value);
        }
        return $array_formats;
    }

    /**
     * Export all products.
     *
     * @return boolean
     */
    public static function isExportAllProducts() {
        return Configuration::get('LENGOW_EXPORT_ALL');
    }

    /**
     * The export format used.
     *
     * @return varchar Format
     */
    public static function getExportFormat() {
        return Configuration::get('LENGOW_EXPORT_FORMAT');
    }

    /**
     * Export all products, attributes & features or single products.
     *
     * @return boolean
     */
    public static function isExportFullmode() {
        return Configuration::get('LENGOW_EXPORT_ALL_ATTRIBUTES');
    }
    
    /**
     * Export all products, attributes & features or single products.
     *
     * @return boolean
     */
    public static function isExportFeatures() {
        return Configuration::get('LENGOW_EXPORT_FEATURES');
    }

    /**
     * Export full name of product or parent's name of product.
     *
     * @return boolean
     */
    public static function isFullName() {
        return Configuration::get('LENGOW_EXPORT_FULLNAME');
    }

    /**
     * Export full name of product or parent's name of product.
     *
     * @return boolean
     */
    public static function countExportAllImages() {
        return Configuration::get('LENGOW_IMAGES_COUNT');
    }

    /**
     * Get the ID Lengow Customer.
     *
     * @return integer
     */
    public static function getIdCustomer() {
        return Configuration::get('LENGOW_ID_CUSTOMER');
    }

    /**
     * Get the ID Group.
     *
     * @return integer
     */
    public static function getGroupCustomer() {
        return Configuration::get('LENGOW_ID_GROUP');
    }

    /**
     * Get the token API.
     *
     * @return integer
     */
    public static function getTokenCustomer() {
        return Configuration::get('LENGOW_TOKEN');
    }

    /**
     * Get the default carrier to import.
     *
     * @return integer
     */
    public static function getDefaultCarrier() {
        return Configuration::get('LENGOW_CARRIER_DEFAULT');
    }

    /**
     * Auto export new product.
     *
     * @return boolean
     */
    public static function isAutoExport() {
        return Configuration::get('LENGOW_EXPORT_NEW') ? true : false;
    }

    /**
     * Export in file.
     *
     * @return boolean
     */
    public static function exportInFile() {
        return Configuration::get('LENGOW_EXPORT_FILE') ? true : false;
    }

    /**
     * Export only title or title + attribute
     *
     * @return boolean
     */
    public static function exportTitle() {
        return LengowExport::$full_title;
    }

    /**
     * Export active products
     *
     * @return boolean
     */
    public static function exportAllProduct() {
        return Configuration::get('LENGOW_EXPORT_DISABLED') ? true : false;
    }

    /**
     * Get the Id of new status order.
     *
     * @param varchar $version The version to compare
     *
     * @return integer
     */
    public static function getOrderState($state) {
        switch ($state) {
            case 'process' :
                return Configuration::get('LENGOW_ORDER_ID_PROCESS');
            case 'shipped' :
                return Configuration::get('LENGOW_ORDER_ID_SHIPPED');
            case 'cancel' :
                return Configuration::get('LENGOW_ORDER_ID_CANCEL');
        }
        return false;
    }

    /**
     * Get the import method name value.
     *
     * @return integer
     */
    public static function getImportMethodName() {
        return Configuration::get('LENGOW_IMPORT_METHOD_NAME');
    }

    /**
     * Disable mail.
     */
    public static function disableMail() {
        self::$buffer_mail_value = Configuration::get('PS_MAIL_METHOD');
        Configuration::updateValue('PS_MAIL_METHOD', 3);
    }

    /**
     * Enable mail.
     */
    public static function enableMail() {
        Configuration::updateValue('PS_MAIL_METHOD', self::$buffer_mail_value);
    }

    /**
     * Disable send state.
     */
    public static function disableSendState() {
        self::$send_state = false;
    }

    /**
     * Enable send state.
     */
    public static function enableSendState() {
        self::$send_state = true;
    }

    /**
     * Is send state.
     *
     * @return boolean
     */
    public static function isSendState() {
        return self::$send_state;
    }

    /**
     * The image export format used.
     *
     * @return varchar Format
     */
    public static function getImageFormat() {
        if (self::$image_type_cache)
            return self::$image_type_cache;
        $id_type_image = Configuration::get('LENGOW_IMAGE_TYPE');
        $image_type = new ImageType($id_type_image);
        self::$image_type_cache = $image_type->name;
        return self::$image_type_cache;
    }

    /**
     * The tracker options.
     *
     * @return array Lengow tracker option
     */
    public static function getTrackers() {
        $array_tracker = array();
        foreach (self::$TRACKER_LENGOW as $name => $value) {
            $array_tracker[] = new LengowOption($name, $value);
        }
        return $array_tracker;
    }

    /**
     * Get the tracking mode.
     *
     * @return string Lengow current tracker mode
     */
    public static function getTrackingMode() {
        return Configuration::get('LENGOW_TRACKING');
        ;
    }

    /**
     * The images number to export.
     *
     * @return array Images count option
     */
    public static function getImagesCount() {
        if (!self::$module)
            self::setModule(new Lengow());
        $array_images = array(new LengowOption('all', self::$module->l('All images')));
        for ($i = 3; $i < 11; $i++) {
            $array_images[] = new LengowOption($i, self::$module->l($i . ' image' . ($i > 1 ? 's' : '')));
        }
        return $array_images;
    }

    /**
     * The shipping names options.
     *
     * @return array Lengow shipping names option
     */
    public static function getShippingName() {
        $array_shipping = array();
        foreach (self::$SHIPPING_LENGOW as $name => $value) {
            $array_shipping[] = new LengowOption($name, $value);
        }
        return $array_shipping;
    }

    /**
     * The number days to import.
     *
     * @return integer Number of days
     */
    public static function getCountDaysToImport() {
        return Configuration::get('LENGOW_IMPORT_DAYS');
    }

    /**
     * The shipping names options.
     *
     * @return array Lengow shipping names option
     */
    public static function getInstanceCarrier() {
        $id_carrier = Configuration::get('LENGOW_CARRIER_DEFAULT');
        return new Carrier($id_carrier);
    }

    /**
     * The shipping names options.
     *
     * @return array Lengow shipping names option
     */
    public static function getMarketplaceSingleton($name) {
        if (!isset(self::$registers[$name]))
            self::$registers[$name] = new LengowMarketplace($name);
        return self::$registers[$name];
    }

    /**
     * Clean html.
     *
     * @param string $html The html content
     *
     * @return string Text cleaned.
     */
    public static function cleanHtml($html) {
        $string = str_replace('<br />', '', nl2br($html));
        $string = trim(strip_tags(htmlspecialchars_decode($string)));
        $string = preg_replace('`[\s]+`sim', ' ', $string);
        $string = preg_replace('`"`sim', '', $string);
        $string = nl2br($string);
        $pattern = '@<[\/\!]*?[^<>]*?>@si'; //nettoyage du code HTML
        $string = preg_replace($pattern, ' ', $string);
        $string = preg_replace('/[\s]+/', ' ', $string); //nettoyage des espaces multiples		
        $string = trim($string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace('|', ' ', $string);
        $string = str_replace('"', '\'', $string);
        $string = str_replace('’', '\'', $string);
        $string = str_replace('&#39;', '\' ', $string);
        $string = str_replace('&#150;', '-', $string);
        $string = str_replace(chr(9), ' ', $string);
        $string = str_replace(chr(10), ' ', $string);
        $string = str_replace(chr(13), ' ', $string);
        return $string;
    }

    /**
     * Formate float.
     *
     * @param float $float The float to format
     *
     * @return float Float formated
     */
    public static function formatNumber($float) {
        return number_format(round($float, 2), 2, '.', '');
    }

    /**
     * Get host for generated email.
     *
     * @return string Hostname
     */
    public static function getHost() {
        $domain = Configuration::get('PS_SHOP_DOMAIN');
        preg_match('`([a-zàâäéèêëôöùûüîïç0-9-]+\.[a-z]+$)`', $domain, $out);
        if ($out[1])
            return $out[1];
        return $domain;
    }

    /**
     * Get flows.
     *
     * @return array Flow
     */
    public static function getFlows($id_flow = null) {
        $lengow_connector = new LengowConnector((integer) self::getIdCustomer(), self::getTokenCustomer());
        $args = array('idClient' => (integer) self::getIdCustomer(),
            'idGroup' => (string) self::getGroupCustomer());
        if ($id_flow)
            $args['idFlow'] = $id_flow;
        return $lengow_connector->api('getRootFeed', $args);
    }

    /**
     * Check if current IP is authorized.
     *
     * @return boolean.
     */
    public static function checkIP() {
        $ips = Configuration::get('LENGOW_AUTHORIZED_IP');
        $ips = trim(str_replace(array("\r\n", ',', '-', '|', ' '), ';', $ips), ';');
        $ips = explode(';', $ips);
        $authorized_ips = array_merge($ips, self::$IPS_LENGOW);
        // Proxy
        /* if(function_exists('apache_request_headers')) {
          $headers = apache_request_headers();
          if (array_key_exists('X-Forwarded-For', $headers)) {
          $hostname_ip = $headers['X-Forwarded-For'];
          } else {
          $hostname_ip = $_SERVER['REMOTE_ADDR'];
          }
          } else {
          $hostname_ip = $_SERVER['REMOTE_ADDR'];
          } */
        $hostname_ip = $_SERVER['REMOTE_ADDR'];
        if (in_array($hostname_ip, $authorized_ips))
            return true;
        return false;
    }

    /**
     * Check and update xml of marketplace's configuration.
     *
     * @return boolean.
     */
    public static function updateMarketPlaceConfiguration() {
        $sep = DIRECTORY_SEPARATOR;
        $mp_update = Configuration::get('LENGOW_MP_CONF');
        if (!$mp_update || $mp_update != date('Y-m-d')) {
            if ($xml = fopen(self::$MP_CONF_LENGOW, 'r')) {
                $handle = fopen(dirname(__FILE__) . $sep . '..' . $sep . 'config' . $sep . LengowMarketplace::$XML_MARKETPLACES . '', 'w');
                stream_copy_to_stream($xml, $handle);
                fclose($handle);
                Configuration::updateValue('LENGOW_MP_CONF', date('Y-m-d'));
            }
        }
    }

    /**
     * Log.
     *
     * @param float $float The float to format
     * @param mixed $force_output Force print output (-1 no output)
     *
     * @return float Float formated
     */
    public static function log($txt, $force_output = false) {
        $sep = DIRECTORY_SEPARATOR;
        $debug = Configuration::get('LENGOW_DEBUG');
        if ($force_output !== -1) {
            if ($debug || $force_output) {
                echo date('Y-m-d : H:i:s') . ' - ' . $txt . '<br />' . "\r\n";
                flush();
            }
        }
        if (!self::$log_instance)
            self::$log_instance = @fopen(dirname(__FILE__) . $sep . '..' . $sep . 'logs' . $sep . 'logs-' . date('Y-m-d') . '.txt', 'a+');
        fwrite(self::$log_instance, date('Y-m-d : H:i:s - ') . $txt . "\r\n");
    }

    /**
     * Log.
     *
     * @param mixed $var object or text for debugger
     */
    public static function debug($var) {
        $debug = Configuration::get('LENGOW_DEBUG');
        if ($debug) {
            if (is_object($var) or is_array($var)) {
                echo '<pre>' . print_r($var) . '</var>';
            } else {
                echo $var . "\r\n";
            }
            flush();
        }
    }

    /**
     * Log.
     *
     * @param mixed $var object or text for debugger
     */
    public static function cleanLog() {
        $debug = Configuration::get('LENGOW_DEBUG');
        if ($debug)
            return false;
        $sep = DIRECTORY_SEPARATOR;
        $days = array();
        $days[] = 'logs-' . date('Y-m-d') . '.txt';
        for ($i = 1; $i < self::$log_life; $i++) {
            $days[] = 'logs-' . date('Y-m-d', strtotime('-' . $i . 'day')) . '.txt';
        }
        if ($handle = opendir(dirname(__FILE__) . $sep . '..' . $sep . 'logs' . $sep)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (!in_array($entry, $days))
                        unlink(dirname(__FILE__) . $sep . '..' . $sep . 'logs' . $sep . $entry);
                }
            }
            closedir($handle);
        }
    }
    
    /**
     * Clean phone number
     * 
     * @param string $phone Phone to clean
     */
    public static function cleanPhone($phone) {
        if(!$phone)
            return null;
        if(Validate::isPhoneNumber($phone))
            return $phone;
        else
            return preg_replace('/^[+0-9. ()-]*$/', '', $phone);
    }

}