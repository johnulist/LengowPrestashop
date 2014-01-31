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
try {
    if (!function_exists('curl_init')) {
        throw new LengowApiException('Lengow needs the CURL PHP extension.', -1);
    }
    if (!function_exists('json_decode')) {
        throw new LengowApiException('Lengow needs the JSON PHP extension.', -2);
    }
    if (!function_exists('simplexml_load_string')) {
        throw new LengowAPIException('Lengow needs the SIMPLE XML PHP extension.', -3);
    }
} catch (LengowAPIException $e) {
    //echo $e->getMessage();
}

/**
 * The Lengow connector API.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowConnectorAbstract {

    /**
     * Version.
     */
    const VERSION = '1.0.1';

    /**
     * Error.
     */
    public $error;

    /**
     * Default options for curl.
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'lengow-api-1.0',
    );

    /**
     * Lengow webservices domains.
     */
    public static $DOMAIN_LENGOW = array(
        'solution' => array('protocol' => 'https',
            'url' => 'solution.lengow.com',
            'format' => 'json'),
        'api' => array('protocol' => 'http',
            'url' => 'api.lengow.com',
            'format' => 'xml'),
        'statistics' => array('protocol' => 'http',
            'url' => 'statistics.lengow.com',
            'format' => 'xml'),
    );

    /**
     * Lengow methods API.
     */
    public static $API_METHODS_LENGOW = array(
        'getListingFeeds' => array('service' => 'solution'),
        'updateFeed' => array('service' => 'solution'),
        'getHistoryFeed' => array('service' => 'solution'),
        'updateInformationsClient' => array('service' => 'solution'),
        'getListingGroups' => array('service' => 'solution'),
        'createGroup' => array('service' => 'solution'),
        'updateGroup' => array('service' => 'solution'),
        'getListingVip' => array('service' => 'solution'),
        'createVip' => array('service' => 'solution'),
        'updateVip' => array('service' => 'solution'),
        'getLeads' => array('service' => 'solution'),
        'statusLead' => array('service' => 'solution'),
        'updatePrestaInternalOrderId' => array('service' => 'solution'),
        'updateTrackingMagento' => array('service' => 'solution'),
        'updateRootFeed' => array('service' => 'solution'),
        'getRootFeed' => array('service' => 'solution'),
        'updateEcommerceSolution' => array('service' => 'solution'),
        'statistics' => array('service' => 'statistics'),
        'commands' => array('service' => 'api'),
        'authentification' => array('service' => 'solution'),
    );

    /**
     * Lengow token.
     */
    public $token;

    /**
     * Lengow ID customer.
     */
    public $id_customer;

    /**
     * Make a new Lengow API Connector.
     *
     * @param integer $id_customer Your customer ID.
     * @param varchar $token Your token Lengow API.
     */
    public function __construct($id_customer, $token) {
        try {
            if (is_integer($id_customer))
                $this->id_customer = $id_customer;
            else
                throw new LengowAPIException('Error Lengow Customer ID', 1);
            if (strlen($token) > 10)
                $this->token = $token;
            else
                throw new LengowAPIException('Error Lengow Token API', 2);
        } catch (LengowApiException $e) {
            $this->error = $e;
            return false;
        }
        return true;
    }

    /**
     * The API method.
     *
     * @param varchar $method Lengow method API call.
     * @param varchar $array Lengow method API parameters
     *
     * @return array The formated data response
     */
    public function api($method, $array = array()) {
        try {
            if (!$api = $this->_getMethod($method))
                throw new LengowAPIException('Error unknow method API', 3);
            else
                $data = $this->_callAction($api['service'], $method, $array);
        } catch (LengowAPIException $e) {
            return $e->getMessage();
        }
        return $data;
    }

    /**
     * Call the Lengow service with accepted method.
     *
     * @param varchar $service Lengow service name
     * @param varchar $method Lengow method API call.
     * @param varchar $array Lengow method API parameters
     *
     * @return array The formated data response
     */
    private function _callAction($service, $method, $array) {
        switch ($service) {
            case 'solution' :
                $url = $this->_getUrlService($service, $method, $array);
                break;
            case 'api' :
                if(array_key_exists('orderid', $array))
                    $url = $this->_getUrlOrder($service, $array);
                else
                    $url = $this->_getUrlOrders($service, $array);
                break;
            case 'statistics' :
                $url = $this->_getUrlStatistics($service, $array);
                break;
        }
        $result = $this->_makeRequest($url);
        return $this->_format($result, self::$DOMAIN_LENGOW[$service]['format']);
    }

    /**
     * Makes the Service API Url.
     *
     * @param string $service The URL to make the request to
     * @param string $array The array of query parameters
     *
     * @return string The url
     */
    private function _getUrlService($service, $method, $array) {
        $url = self::$DOMAIN_LENGOW[$service]['protocol']
                . '://'
                . self::$DOMAIN_LENGOW[$service]['url']
                . '/wsdl/connector/call.json?'
                . 'token=' . $this->token
                . '&idClient=' . $this->id_customer
                . '&method=' . $method
                . '&array=' . urlencode(serialize($array));
        return $url;
    }

    /**
     * Makes the Orders API Url.
     *
     * @param string $service The URL to make the request to
     * @param string $array The array of query parameters
     *
     * @return string The url
     */
    private function _getUrlOrders($service, $array) {
        $url = self::$DOMAIN_LENGOW[$service]['protocol']
                . '://'
                . self::$DOMAIN_LENGOW[$service]['url'] . '/'
                . 'v2/'
                . $array['dateFrom'] . '/'
                . $array['dateTo'] . '/'
                . $this->id_customer . '/'
                . $array['id_group'] . '/'
                . (isset($array['id']) && !empty($array['id']) ? $array['id'] : 'orders')
                . '/commands/'
                . (isset($array['state']) && !empty($array['state']) ? $array['state'] . '/' : '');
        return $url;
    }

    /**
     * Makes url to give one commands
     *
     * @param string $service The URL to make the request to
     * @param string $array The array of query parameters
     *
     * @return string The url
     */
    private function _getUrlOrder($service, $array) {
        $url = self::$DOMAIN_LENGOW[$service]['protocol']
                . '://'
                . self::$DOMAIN_LENGOW[$service]['url'] . '/'
                . 'v2/'
                . $this->id_customer . '/'
                . $array['feed_id'] . '/'
                . 'orderid/'
                . $array['orderid'] . '/';
        return $url;
    }

    /**
     * Makes the Statisctics API Url.
     *
     * @param string $service The URL to make the request to
     * @param string $array The array of query parameters
     *
     * @return string The url
     */
    private function _getUrlStatistics($service, $array) {
        $url = self::$DOMAIN_LENGOW[$service]['protocol']
                . '://'
                . self::$DOMAIN_LENGOW[$service]['url']
                . $array['dateFrom'] . '/'
                . $array['dateTo'] . '/'
                . $this->id_customer . '/'
                . $array['id']
                . '/total-All/';
        return $url;
    }

    /**
     * Get the method of Lengow API if exist.
     *
     * @param string $method The method's name
     *
     * @return string The method with service
     */
    private function _getMethod($method) {
        if (self::$API_METHODS_LENGOW[$method])
            return self::$API_METHODS_LENGOW[$method];
        else
            return false;
    }

    /**
     * Format data with good format.
     *
     * @param string $data the data's response of method request
     * @param string $format the return format
     *
     * @return string Data formated
     */
    private function _format($data, $format) {
        switch ($format) {
            case 'xml' :
                return simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
            case 'json' :
                return json_decode($data, true);
        }
        return null;
    }

    /**
     * Makes an HTTP request.
     *
     * @param string $url The URL to make the request to
     *
     * @return string The response text
     */
    protected function _makeRequest($url) {
        LengowCore::log('Connector ' . $url, -1);
        $ch = curl_init();
        // Options
        $opts = self::$CURL_OPTS;
        $opts[CURLOPT_URL] = $url;
        // Exectute url request
        curl_setopt_array($ch, $opts);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if ($result === false) {
            LengowCore::log('Connector Error (' . curl_error($ch) . ')' . $result, -1);
            throw new LengowApiException(
                array('message' => curl_error($ch),
                    'type' => 'CurlException',
                ), curl_errno($ch)
            );
        }
        curl_close($ch);
        if(is_object(json_decode($result))) {
            if(strtolower(json_decode($result)->return) == "ko") {
                LengowCore::log('API Error : ' . json_decode($result)->error, -1);
            }
        }
        return $result;
    }

}

/**
 * Thrown when an API call returns an exception.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 */
class LengowApiException extends Exception {

    /**
     * The result from the API server that represents the exception information.
     */
    protected $result;

    /**
     * Make a new API Exception with the given result.
     *
     * @param array $result The error result
     */
    public function __construct($result, $noerror) {
        $this->result = $result;
        if (is_array($result))
            $msg = $result['message'];
        else
            $msg = $result;
        parent::__construct($msg, $noerror);
    }

    /**
     * Return the associated result object returned by the API server.
     *
     * @return array The result from the API server
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Returns the associated type for the error. 
     *
     * @return string
     */
    public function getType() {
        if (isset($this->result['type']))
            return $this->result['type'];
        return 'LengowApiException';
    }

    /**
     * To make debugging easier.
     *
     * @return string The string representation of the error
     */
    public function __toString() {
        if (isset($this->result['message']))
            return $this->result['message'];
        return $this->message;
    }

}
