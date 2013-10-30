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
        
        if(empty($checklist))
            return null;
        
        $out = '';
        foreach($checklist as $check) {
            $out .= '<tr>';
            $out .= '<td><b>' . $check['message'] . '</b></td>';
            if($check['state'] == 1)
                $out .= '<td><img src="/img/admin/enabled.gif" alt="ok"></td>';
            elseif($check['state'] == 2)
                $out .= '<td><img src="/img/admin/error.png" alt="warning"></td>';
            else
                $out .= '<td><img src="/img/admin/disabled.gif" alt="nok"></td>';
            $out .= '</tr>';
            
            if($check['state'] === 0 || $check['state'] === 2) {
                $out .= '<tr><td colspan="2"><p>' . $check['help'] . '</p></td></tr>';
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
        $mail_type = Configuration::get('PS_MAIL_METHOD');
        
        if(_PS_VERSION_ < '1.5.0') {
            
        } else {
            if($mail_type == 3)
                return 'Email are desactived.';
            
            if($mail_type == 2)
                return 'Email are enabled with custom settings.';
            
            return 'Email using php mail function.';
        }
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
     * Check API Authentification
     * 
     * @return boolean
     */
    public static function isValidAuth() {
        if(!self::isCurlActivated())
            return false;
        
        $id_customer = Configuration::get('LENGOW_ID_CUSTOMER');
        $id_group = Configuration::get('LENGOW_ID_GROUP');
        $token = Configuration::get('LENGOW_TOKEN');
        
        $connector = new LengowConnector((int)$id_customer, $token);
        $result = $connector->api('authentification');
        
        if($result['return'] == 'Ok')
            return true;
        else 
            return false;
    }
    
    /**
     * Get array of requirements and their status
     * 
     * @return array
     */
    private static function _getCheckListArray() {
        $checklist = array();
        
        $checklist[] = array(
            'message' => 'Lengow needs the CURL PHP extension',
            'help' => 'The CURL extension is not installed or enabled in your PHP installation. Check the <a target="_blank" href="http://www.php.net/manual/en/curl.setup.php">manual</a> for information on how to install or enable CURL on your system.',
            'state' => (int) self::isCurlActivated()
        );
        $checklist[] = array(
            'message' => 'Lengow needs the SimpleXML PHP extension',
            'help' => 'The SimpleXML extension is not installed or enabled in your PHP installation. Check the <a target="_blank" href="http://www.php.net/manual/en/book.simplexml.php">manual</a> for information on how to install or enable SimpleXML on your system.',
            'state' => (int) self::isSimpleXMLActivated()
        );
        $checklist[] = array(
            'message' => 'Lengow needs the JSON PHP extension',
            'help' => 'The JSON extension is not installed or enabled in your PHP installation. Check the <a target="_blank" href="http://www.php.net/manual/fr/book.json.php">manual</a> for information on how to install or enable JSON on your system.',
            'state' => (int) self::isJsonActivated()
        );
        $checklist[] = array(
            'message' => 'Lengow authentification',
            'help' => 'Please check your Client ID, Group ID and Token API. Make sure your website IP address is filled in your Lengow Dashboard.',
            'state' => (int) self::isValidAuth()
        );
        
        $checklist[] = array(
            'message' => 'Mail configuration',
            'help' => self::getMailConfiguration(),
            'state' => 2
        );
        
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
        return json_encode(self::_getCheckListArray());
    }

}