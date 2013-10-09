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
 * The Lengow Address Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowAddress extends Address {

    /**
     * Specify if an address is already in base
     *
     * @param $alias string The Alias
     *
     * @return mixed Addres or false
     */
    static public function getByAlias($alias) {
        $row = Db::getInstance()->getRow('
                 SELECT `id_address`
                 FROM '._DB_PREFIX_.'address a
                 WHERE a.`alias` = "'. strval($alias) . '"');
        if($row['id_address'] > 0)
            return new LengowAddress($row['id_address']);
        return false;
    }

    /**
     * Hash an alias and get the address with unique hash
     *
     * @param $alias string The Alias
     *
     * @return mixed Address or false
     */
    static public function getByHash($alias) {
        return self::getByAlias(self::hash($alias));
    }

    /**
     * Filter non printable caracters
     *
     * @param $text string
     *
     * @return string
     */
    public static function _filter($text) {
        return preg_replace('/[!<>?=+@{}_$%]*$/u', '', $text); // remove non printable
    }

    /**
     * Filter non printable caracters
     *
     * @param $text string
     *
     * @return string
     */
    public static function extractName($fullname) {
        $array_name =  explode(' ', $fullname);
        $firstname = $array_name[0];
        $lastname = str_replace($firstname . ' ', '', $fullname);
        $firstname = empty($firstname) ? 'unknown' : self::cleanName($firstname);
        $lastname = empty($lastname) ? 'unknown' : self::cleanName($lastname);
        return array('firstname' => ucfirst(strtolower($firstname)),
                     'lastname'  => ucfirst(strtolower($lastname)));
    }

    /**
     * Clean firstname or lastname to Prestashop
     *
     * @param $text string Name
     *
     * @return string
     */
    public static function cleanName($name) {
        return Tools::replaceAccentedChars(substr(trim(preg_replace('/[0-9!<>,;?=+()@#"�{}_$%:]/', '', $name)), 0, 31));
    }

    /**
     * Hash address with md5
     *
     * @param $text string Full address
     *
     * @return string Hash
     */
    public static function hash($address) {
        return md5($address);
    }

}                         