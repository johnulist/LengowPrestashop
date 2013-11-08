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
require_once 'lengow.option.class.php';
require_once 'lengow.connector.class.php';

/**
 * The Lengow Core Class.
 *
 * @author Romain Le Polh <romain@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowCheck {

    static private $_module = '';
    
    /**
     * Get header table
     * 
     * @return string
     */
    private static function _getAdminHeader() {
        return '<table class="table" cellpadding="0" cellspacing="0"><tbody>';
    }

    /**
     * Get HTML Table content of checklist
     * 
     * @param array $checklist
     * @return string|nullPS_MAIL_METHOD
     */
    private static function _getAdminContent($checklist = array()) {

        if (empty($checklist))
            return null;

        $out = '';
        foreach ($checklist as $check) {
            $out .= '<tr>';
            $out .= '<td><b>' . $check['message'] . '</b></td>';
            if ($check['state'] == 1)
                $out .= '<td><img src="/img/admin/enabled.gif" alt="ok"></td>';
            elseif ($check['state'] == 2)
                $out .= '<td><img src="/img/admin/error.png" alt="warning"></td>';
            else
                $out .= '<td><img src="/img/admin/disabled.gif" alt="nok"></td>';
            $out .= '</tr>';

            if ($check['state'] === 0 || $check['state'] === 2) {
                $out .= '<tr><td colspan="2"><p>' . $check['help'];
                if($check['help_link'] != '') {
                    $out .= '<br /><a target="_blank" href="' . $check['help_link'] . '">' . $check['help_label'] . '</a>';
                }
                $out .= '</p></td></tr>';
            }
        }

        return $out;
    }

    /**
     * Get footer table
     * 
     * @return string
     */
    private static function _getAdminFooter() {
        return '</tbody></table>';
    }

    /**
     * Get mail configuration informations
     * 
     * @return string
     */
    public static function getMailConfiguration() {
        $mail_method = Configuration::get('PS_MAIL_METHOD');
        if ($mail_method == 2)
            return self::$_module->l('Email are enabled with custom settings.');
        elseif ($mail_method == 3 && _PS_VERSION_ >= '1.5.0')
            return self::$_module->l('Email are desactived.');
        elseif ($mail_method == 3)
            return self::$_module->l('Error mail settings, PS_MAIL_METHOD is 3 but this value is not allowed in Prestashop 1.4');
        else
            return self::$_module->l('Email using php mail function.');
    }

    /**
     * Check if PHP Curl is activated
     * 
     * @return boolean
     */
    public static function isCurlActivated() {
        return function_exists('curl_version');
    }

    /**
     * Check if SimpleXML Extension is activated
     * 
     * @return boolean
     */
    public static function isSimpleXMLActivated() {
        return function_exists('simplexml_load_file');
    }

    /**
     * Check if SimpleXML Extension is activated
     * 
     * @return boolean
     */
    public static function isJsonActivated() {
        return function_exists('json_decode');
    }
    
    /**
     * Check if shop functionality are enabled
     * 
     * @return boolean
     */
    public static function isShopActivated() {
        if(Configuration::get('PS_CATALOG_MODE'))
            return false;
        return true;
    }

    /**
     * Check API Authentification
     * 
     * @return boolean
     */
    public static function isValidAuth() {
        if (!self::isCurlActivated())
            return false;

        $id_customer = Configuration::get('LENGOW_ID_CUSTOMER');
        $id_group = Configuration::get('LENGOW_ID_GROUP');
        $token = Configuration::get('LENGOW_TOKEN');

        $connector = new LengowConnector((int) $id_customer, $token);
        $result = $connector->api('authentification');

        if ($result['return'] == 'Ok')
            return true;
        else
            return false;
    }

    /**
     * Check if config folder is writable
     * 
     * @return boolean
     */
    public static function isConfigWritable() {
        $config_folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config';
        return is_writable($config_folder);
    }

    /**
     * Check disabled product option
     * 
     * @return boolean
     */
    public static function isDisabledProduct() {
        return (Configuration::get('LENGOW_EXPORT_DISABLED') == true) ? false : true;
    }

    /**
     * Get array of requirements and their status
     * 
     * @return array
     */
    private static function _getCheckListArray() {
        $checklist = array();
        
        self::$_module = new Lengow();
        
        $checklist[] = array(
            'message' => self::$_module->l('Lengow needs the CURL PHP extension'),
            'help' => self::$_module->l('The CURL extension is not installed or enabled in your PHP installation. Check the manual for information on how to install or enable CURL on your system.'),
            'help_link' => 'http://www.php.net/manual/en/curl.setup.php',
            'help_label' => self::$_module->l('Go to Curl PHP extension manual'),
            'state' => (int) self::isCurlActivated()
        );
        $checklist[] = array(
            'message' => self::$_module->l('Lengow needs the SimpleXML PHP extension'),
            'help' => self::$_module->l('The SimpleXML extension is not installed or enabled in your PHP installation. Check the manual for information on how to install or enable SimpleXML on your system.'),
            'help_link' => 'http://www.php.net/manual/en/book.simplexml.php',
            'help_label' => self::$_module->l('Go to SimpleXML PHP extension manual'),
            'state' => (int) self::isSimpleXMLActivated()
        );
        $checklist[] = array(
            'message' => self::$_module->l('Lengow needs the JSON PHP extension'),
            'help' => self::$_module->l('The JSON extension is not installed or enabled in your PHP installation. Check the manual for information on how to install or enable JSON on your system.'),
            'help_link' => 'http://www.php.net/manual/fr/book.json.php',
            'help_label' => self::$_module->l('Go to JSON PHP extension manual'),
            'state' => (int) self::isJsonActivated()
        );
        $checklist[] = array(
            'message' => self::$_module->l('Lengow authentification'),
            'help' => sprintf(self::$_module->l('Please check your Client ID, Group ID and Token API. Make sure your website IP (%s) address is filled in your Lengow Dashboard.'), gethostbyname($_SERVER['HTTP_HOST'])),
            'help_link' => 'https://solution.lengow.com/api/',
            'help_label' => self::$_module->l('Go to Lengow dashboard'),
            'state' => (int) self::isValidAuth()
        );
        $checklist[] = array(
            'message' => self::$_module->l('Shop functionality'),
            'help' => self::$_module->l('Shop functionality are disabled, order import will be impossible, please enable them in your products settings.'),
            'state' => (int) self::isShopActivated()
        );
        $checklist[] = array(
            'message' => self::$_module->l('Config folder is writable'),
            'help' => self::$_module->l('The config folder must be writable.'),
            'state' => (int) self::isConfigWritable()
        );
        $checklist[] = array(
            'message' => self::$_module->l('Export disabled products'),
            'help' => self::$_module->l('Disabled product are enabled in export, Marketplace order import will not work with this configuration.'),
            'state' => (int) self::isDisabledProduct()
        );

        if(Configuration::get('LENGOW_DEBUG')) {
            $checklist[] = array(
                'message' => self::$_module->l('Mail configuration'),
                'help' => self::getMailConfiguration(),
                'state' => 2
            );
        }

        return $checklist;
    }

    /**
     * Get admin table html
     * 
     * @return string Html table
     */
    public static function getHtmlCheckList() {
        $out = '';
        $out .= self::_getAdminHeader();
        $out .= self::_getAdminContent(self::_getCheckListArray());
        $out .= self::_getAdminFooter();
        return $out;
    }

    /**
     * Get check list json
     * 
     * @return string Json
     */
    public static function getJsonCheckList() {
        return Tools::jsonEncode(self::_getCheckListArray());
    }

}
