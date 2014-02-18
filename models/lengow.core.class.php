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
    public static $buffer_mail_domain;
    public static $buffer_mail_server;
    public static $buffer_mail_user;
    public static $buffer_mail_passwd;
    public static $buffer_mail_smtp_encryption;
    public static $buffer_mail_smtp_port;

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

    public static $TRACKER_CHOICE_ID = array(
        'id' => 'Product ID',
        'ean' => 'Product EAN',
        'upc' => 'Product UPC',
        'ref' => 'Product Reference',
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
        '95.131.141.168',
        '95.131.141.169',
        '95.131.141.170',
        '95.131.141.171'
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
     * Lengow XML Plugins status
     */
    public static $LENGOW_PLUGINS_VERSION = 'http://kml.lengow.com/plugins.xml';

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
        Configuration::updateValue('LENGOW_MAIL_VALUE', Configuration::get('PS_MAIL_METHOD'));
        Configuration::updateValue('LENGOW_MAIL_DOMAIN', Configuration::get('PS_MAIL_DOMAIN'));
        Configuration::updateValue('LENGOW_MAIL_SERVER', Configuration::get('PS_MAIL_SERVER'));
        Configuration::updateValue('LENGOW_MAIL_USER', Configuration::get('PS_MAIL_USER'));
        Configuration::updateValue('LENGOW_MAIL_PASSWD', Configuration::get('PS_MAIL_PASSWD'));
        Configuration::updateValue('LENGOW_MAIL_SMTP_ENCRYPTION', Configuration::get('PS_MAIL_SMTP_ENCRYPTION'));
        Configuration::updateValue('LENGOW_MAIL_SMTP_PORT', Configuration::get('PS_MAIL_SMTP_PORT'));
        if(_PS_VERSION_ < '1.5.4')
            LengowCore::_changeMailConfiguration();
        else
            Configuration::updateValue('PS_MAIL_METHOD', 3);
    }
    
    /**
     * Change mail settings with temp value
     * 
     * @return boolean
     */
    private static function _changeMailConfiguration() {
        if(Configuration::updateValue('PS_MAIL_DOMAIN', 'temp.lengow') &&
           Configuration::updateValue('PS_MAIL_SERVER', 'temp.lengow') &&
           Configuration::updateValue('PS_MAIL_USER', 'temp@lengow.temp') &&
           Configuration::updateValue('PS_MAIL_PASSWD', 'temp') &&
           Configuration::updateValue('PS_MAIL_SMTP_ENCRYPTION', 'off') &&
           Configuration::updateValue('PS_MAIL_SMTP_PORT', '25') &&
           Configuration::updateValue('PS_MAIL_METHOD', 2))
            return true;
        else
            return false;
    }
    
    /**
     * Enable mail.
     */
    public static function enableMail() {
        Configuration::updateValue('PS_MAIL_METHOD', Configuration::get('LENGOW_MAIL_VALUE'));
        Configuration::updateValue('PS_MAIL_DOMAIN', Configuration::get('LENGOW_MAIL_DOMAIN'));
        Configuration::updateValue('PS_MAIL_SERVER', Configuration::get('LENGOW_MAIL_SERVER'));
        Configuration::updateValue('PS_MAIL_USER', Configuration::get('LENGOW_MAIL_USER'));
        Configuration::updateValue('PS_MAIL_PASSWD', Configuration::get('LENGOW_MAIL_PASSWD'));
        Configuration::updateValue('PS_MAIL_SMTP_ENCRYPTION', Configuration::get('LENGOW_MAIL_SMTP_ENCRYPTION'));
        Configuration::updateValue('PS_MAIL_SMTP_PORT', Configuration::get('LENGOW_MAIL_SMTP_PORT'));
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

    public static function getTrackerChoiceId() {
        $array_choice_id = array();
        foreach(self::$TRACKER_CHOICE_ID as $name => $value) {
            $array_choice_id[] = new LengowOption($name, $value);
        }
        return $array_choice_id;
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
        $authorized_ips[] = $_SERVER['SERVER_ADDR'];
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
     * Check and update xml of plugins version
     *
     * @return boolean
     */
    public static function updatePluginsVersion() {
        $sep = DIRECTORY_SEPARATOR;
        $plg_update = Configuration::get('LENGOW_PLG_CONF');
        if (!$plg_update || $plg_update != date('Y-m-d')) {
            if ($xml = fopen(self::$LENGOW_PLUGINS_VERSION, 'r')) {
                $handle = fopen(dirname(__FILE__) . $sep . '..' . $sep . 'config' . $sep . LengowCheck::$XML_PLUGINS, 'w');
                stream_copy_to_stream($xml, $handle);
                fclose($handle);
                Configuration::updateValue('LENGOW_PLG_CONF', date('Y-m-d'));
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
            return preg_replace('/[^0-9]*/', '', $phone);
    }
    
    /**
     * Replace all accented chars by their equivalent non accented chars.
     *
     * @param string $str
     * @return string
     */
    public static function replaceAccentedChars($str) {
        /* One source among others:
          http://www.tachyonsoft.com/uc0000.htm
          http://www.tachyonsoft.com/uc0001.htm
         */
        $patterns = array(
            /* Lowercase */
            /* a */ '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}]/u',
            /* c */ '/[\x{00E7}\x{0107}\x{0109}\x{010D}]/u',
            /* d */ '/[\x{010F}\x{0111}]/u',
            /* e */ '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}]/u',
            /* g */ '/[\x{011F}\x{0121}\x{0123}]/u',
            /* h */ '/[\x{0125}\x{0127}]/u',
            /* i */ '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}]/u',
            /* j */ '/[\x{0135}]/u',
            /* k */ '/[\x{0137}\x{0138}]/u',
            /* l */ '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}]/u',
            /* n */ '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}]/u',
            /* o */ '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}]/u',
            /* r */ '/[\x{0155}\x{0157}\x{0159}]/u',
            /* s */ '/[\x{015B}\x{015D}\x{015F}\x{0161}]/u',
            /* ss */ '/[\x{00DF}]/u',
            /* t */ '/[\x{0163}\x{0165}\x{0167}]/u',
            /* u */ '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}]/u',
            /* w */ '/[\x{0175}]/u',
            /* y */ '/[\x{00FF}\x{0177}\x{00FD}]/u',
            /* z */ '/[\x{017A}\x{017C}\x{017E}]/u',
            /* ae */ '/[\x{00E6}]/u',
            /* oe */ '/[\x{0153}]/u',
            /* Uppercase */
            /* A */ '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u',
            /* C */ '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}]/u',
            /* D */ '/[\x{010E}\x{0110}]/u',
            /* E */ '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}]/u',
            /* G */ '/[\x{011C}\x{011E}\x{0120}\x{0122}]/u',
            /* H */ '/[\x{0124}\x{0126}]/u',
            /* I */ '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}]/u',
            /* J */ '/[\x{0134}]/u',
            /* K */ '/[\x{0136}]/u',
            /* L */ '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}]/u',
            /* N */ '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}]/u',
            /* O */ '/[\x{00D3}\x{014C}\x{014E}\x{0150}]/u',
            /* R */ '/[\x{0154}\x{0156}\x{0158}]/u',
            /* S */ '/[\x{015A}\x{015C}\x{015E}\x{0160}]/u',
            /* T */ '/[\x{0162}\x{0164}\x{0166}]/u',
            /* U */ '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}]/u',
            /* W */ '/[\x{0174}]/u',
            /* Y */ '/[\x{0176}]/u',
            /* Z */ '/[\x{0179}\x{017B}\x{017D}]/u',
            /* AE */ '/[\x{00C6}]/u',
            /* OE */ '/[\x{0152}]/u');

        // ö to oe
        // å to aa
        // ä to ae

        $replacements = array(
            'a', 'c', 'd', 'e', 'g', 'h', 'i', 'j', 'k', 'l', 'n', 'o', 'r', 's', 'ss', 't', 'u', 'y', 'w', 'z', 'ae', 'oe',
            'A', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'L', 'N', 'O', 'R', 'S', 'T', 'U', 'Z', 'AE', 'OE'
        );

        return preg_replace($patterns, $replacements, $str);
    }

    /**
     * Update import state when he is processing
     *
     * @return boolean
     */
    public static function setImportProcessing() {
        return Configuration::updateValue('LENGOW_IS_IMPORT', 'processing');
    }

    /**
     * Update import state when he is finished
     *
     * @return boolean
     */
    public static function setImportEnd() {
        return Configuration::updateValue('LENGOW_IS_IMPORT', 'stopped');
    }

    /**
     * Set current flag to 1 for order in process
     *
     * @return void
     */
    public static function startProcessOrder($lengow_order_id = null, $extra) {
        if(is_null($lengow_order_id))
            return false;

        $db = Db::getInstance();

        $sql_exist = 'SELECT * FROM `' . _DB_PREFIX_ . 'lengow_logs_import` '
                   . 'WHERE `lengow_order_id` = \'' . substr($lengow_order_id, 0, 32) . '\' ';

        $results = $db->ExecuteS($sql_exist);
        if(empty($results)) {
            // Insert
            if (_PS_VERSION_ >= '1.5') {
                $sql_add = $db->insert('lengow_logs_import', array(
                    'lengow_order_id' => pSQL(substr($lengow_order_id, 0, 32)),
                    'is_processing' => 1,
                    'is_finished' => 0,
                    'extra' => pSQL($extra),
                    'date' => date("Y-m-d H:i:s")
                ));
            } else {
                $sql_add = $db->autoExecute(_DB_PREFIX_ . 'lengow_logs_import', array(
                    'lengow_order_id' => pSQL(substr($lengow_order_id, 0, 32)),
                    'is_processing' => 1,
                    'is_finished' => 0,
                    'extra' => pSQL($extra),
                    'date' => date("Y-m-d H:i:s")
                    ), 'INSERT');
            }
        } else {
            // Update
            if (_PS_VERSION_ >= '1.5') {
                $sql_update = $db->update('lengow_logs_import', array(
                    'is_processing' => 1
                ), '`lengow_order_id` = \'' . pSQL(substr($lengow_order_id, 0, 32)) . '\'', 1);
            } else {
                 $sql_update = $db->autoExecute(_DB_PREFIX_ . 'lengow_logs_import', array(
                    'is_processing' => 1
                ), 'UPDATE', '`lengow_order_id` = \'' . pSQL(substr($lengow_order_id, 0, 32)) . '\'', 1);
            }
        }
    }

    public static function deleteProcessOrder($lengow_order_id = null) {
        if(is_null($lengow_order_id))
            return false;
        $db = Db::getInstance();
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'lengow_logs_import WHERE lengow_order_id = \'' . substr($lengow_order_id, 0, 32) . '\' LIMIT 1';
        return $db->execute($sql);
    }

    /**
     * Set flag to 0 for order in process
     *
     * @return void
     */
    public static function endProcessOrder($lengow_order_id, $is_processing, $is_finished, $message = null) {
        $db = Db::getInstance();
        if (_PS_VERSION_ >= '1.5') {
            $sql_update = $db->update('lengow_logs_import', array(
                    'is_processing' => (int) $is_processing,
                    'is_finished' => (int) $is_finished,
                    'message' => pSQL($message),
            ), '`lengow_order_id` = \'' . pSQL(substr($lengow_order_id, 0, 32)) . '\'', 1);
        } else {
            $sql_update = $db->autoExecute(_DB_PREFIX_ . 'lengow_logs_import', array(
                    'is_processing' => (int) $is_processing,
                    'is_finished' => (int) $is_finished,
                    'message' => pSQL($message),
            ), 'UPDATE', '`lengow_order_id` = \'' . pSQL(substr($lengow_order_id, 0, 32)) . '\'', 1);
        }
    }

    /**
     * Check if order is processing or finished
     *
     * @return boolean
     */
    public static function isProcessing($lengow_order_id = null) {
        if(is_null($lengow_order_id))
            return false;

        $db = Db::getInstance();

        $sql_exist = 'SELECT * FROM `' . _DB_PREFIX_ . 'lengow_logs_import` '
                   . 'WHERE `lengow_order_id` = \'' . substr($lengow_order_id, 0, 32) . '\' ';

        $results = $db->ExecuteS($sql_exist);

        if(empty($results))
            return false;

        foreach($results as $row) {

            if($row['is_processing'] == 1 || $row['is_finished'] == 1)
                return true;
            else
                return false;
        }
    }

    /**
     * Check if Mondial Relay is installed, activated and selected as default lengow carrier
     *
     * @return boolean true if installed and activated
     */
    public static function isMondialRelay() {
        $module_name = 'mondialrelay';
        $module_dir = _PS_MODULE_DIR_ . $module_name . DS;

        if(_PS_VERSION_ >= '1.5') {
            if(Module::isInstalled($module_name) && Module::isEnabled($module_name)) {
                $carrier = new Carrier(Configuration::get('LENGOW_CARRIER_DEFAULT'));
                if($carrier->external_module_name == $module_name)
                    return true;
                else
                    return false;
            }
        } else {
            if(Module::isInstalled($module_name)) {
                $carrier = new Carrier(Configuration::get('LENGOW_CARRIER_DEFAULT'));
                if($carrier->external_module_name == $module_name)
                    return true;
                else
                    return false;
            }
        }
    }

    /**
     * Check is soColissimo is installed, activated and selected as default lengow carrier
     *
     * @return boolean true if installed and activated
     */
    public static function isColissimo() {
        $module_name = 'socolissimo';
        $supported_version = '2.8.5';
        $sep = DIRECTORY_SEPARATOR;
        $module_dir = _PS_MODULE_DIR_ . $module_name . $sep;

        if(_PS_VERSION_ >= '1.5') {
            if(Module::isInstalled($module_name) 
                && Module::isEnabled($module_name)
                && Configuration::get('SOCOLISSIMO_CARRIER_ID') == Configuration::get('LENGOW_CARRIER_DEFAULT')) {

                require_once($module_dir . $module_name . '.php');
                $soColissimo = new Socolissimo();

                if(version_compare($soColissimo->version, $supported_version, '<')) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            if(Module::isInstalled($module_name)
                && Configuration::get('SOCOLISSIMO_CARRIER_ID') == Configuration::get('LENGOW_CARRIER_DEFAULT')) {

                require_once($module_dir . $module_name . '.php');
                $soColissimo = new Socolissimo();

                if(version_compare($soColissimo->version, $supported_version, '<')) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        
        return false;
    }

    /**
     * Check zipcode
     *
     * @return boolean
     */
    public static function isZipCodeFormat($zip_code) {
        if (!empty($zip_code))
            return preg_match('/^[NLCnlc 0-9-]+$/', $zip_code);

        return true;
    }

    public static function getOrgerLog($lengow_order_id) {
        if(is_null($lengow_order_id))
            return false;
        $db = Db::getInstance();
        $sql = 'SELECT `message` FROM `' . _DB_PREFIX_ . 'lengow_logs_import` '
                   . 'WHERE `lengow_order_id` = \'' . substr($lengow_order_id, 0, 32) . '\' ';
        $row = $db->getRow($sql);
        return $row['message'];
    }
}
