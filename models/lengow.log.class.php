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
 * The Lengow Log class
 *
 * @author Romain Le Polh <romain@lengow.com>
 * @copyright 2014 Lengow SAS
 */

class LengowLog extends ObjectModel {

    public $lengow_order_id;
    public $is_processing;
    public $is_finished;
    public $message;
    public $date;
    public $extra;

    public static $definition = array(
        'table' => 'lengow_logs_import',
        'primary' => 'lengow_order_id',
        'multilang' => FALSE,
        'fields' => array(
            'lengow_order_id' => array('type' => self::TYPE_STRING, 'size' => 32),
            'is_processing' => array('type' => self::TYPE_BOOL),
            'is_finished' => array('type' => self::TYPE_BOOL),
            'message' => array('type' => self::TYPE_STRING, 'size' => 255),
            'date' => array('type' => self::TYPE_DATE),
            'extra' => array('type' => self::TYPE_STRING, 'size' => 99999999),
        ),
    );

}