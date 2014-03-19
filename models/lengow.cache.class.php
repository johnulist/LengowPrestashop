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
 * The Lengow Cache Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */

require_once dirname(__FILE__) . $sep . '..' . $sep . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.specificprice.class.php';

if(_PS_CACHE_ENABLED_ == false) {
    eval('class LengowCache {

        public static function clear() {            
            LengowProduct::clear();
            if (_PS_VERSION_ >= \'1.5\')
                LengowProduct::flushPriceCache();
            Link::$cache = array(\'page\' => array());
            if (_PS_VERSION_ >= \'1.5\')
                LengowSpecificPrice::clear();
        }

    }');
} else {
    eval('class LengowCache extends ' . _PS_CACHING_SYSTEM_ . ' {

        public static function clear() {            
            if (_PS_VERSION_ >= \'1.5\')
                self::$local = array();
            if(_PS_CACHE_ENABLED_)
                Cache::getInstance()->delete(\'*\');
            LengowProduct::clear();
            if (_PS_VERSION_ >= \'1.5\')
                LengowProduct::flushPriceCache();
            Link::$cache = array(\'page\' => array());
            if (_PS_VERSION_ >= \'1.5\')
                LengowSpecificPrice::clear();
        }

    }');
}