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
@set_time_limit(0);
$sep = DIRECTORY_SEPARATOR;
require_once '..' . $sep . '..' . $sep . '..' . $sep . 'config' . $sep . 'config.inc.php';
require_once '..' . $sep . '..' . $sep . '..' . $sep . 'init.php';
require_once '..' . $sep . 'lengow.php';
require_once '..' . $sep . 'models' . $sep . 'lengow.core.class.php';
require_once '..' . $sep . 'models' . $sep . 'lengow.export.class.php';

$lengow = new Lengow();
// CheckIP
if (LengowCore::checkIP()) {
    // Force GET parameters
    // > Format
    $format = null;
    if (Tools::getValue('format'))
        $format = Tools::getValue('format');
    // > Fullmode
    $fullmode = null;
    if (Tools::getValue('mode') && Tools::getValue('mode') == 'full')
        $fullmode = true;
    else if (Tools::getValue('mode') && Tools::getValue('mode') == 'simple')
        $fullmode = false;
    // > Stream
    $stream = null;
    if (Tools::getValue('stream'))
        $stream = Tools::getValue('stream');
    // > All products
    $all = null;
    if (Tools::getValue('all'))
        $all = Tools::getValue('all');
    // > Shop
    if ($id_shop = Tools::getValue('shop')) {
        if ($shop = new Shop($id_shop))
            Context::getContext()->shop = $shop;
    }
    // > Currency
    if ($iso_code = Tools::getValue('cur')) {
        if ($id_currency = Currency::getIdByIsoCode($iso_code))
            Context::getContext()->currency = new Currency($id_currency);
    }
    // > Language
    if ($iso_code = Tools::getValue('lang')) {
        if ($id_language = Language::getIdByIso($iso_code))
            Context::getContext()->language = new Language($id_language);
    }

    // > Title
    $title = null;
    if (Tools::getValue('title') && Tools::getValue('title') == 'full')
        $title = true;
    elseif (Tools::getValue('title') && Tools::getValue('title') == 'simple')
        $title = false;

    // > Active
    $all_product = null;
    if (Tools::getValue('active') && Tools::getValue('active') == 'enabled')
        $all_product = false;
    elseif (Tools::getValue('active') && Tools::getValue('active') == 'all')
        $all_product = true;

    // Products ids
    $product_ids = null;
    if(Tools::getValue('ids') && Tools::getValue('ids') != '') {
        $product_ids = explode(',', Tools::getValue('ids'));
        if(empty($product_ids))
            $product_ids = null;
    }

    // > Limit
    $limit = null;
    if(Tools::getValue('limit') && Tools::getValue('limit') > 0)
        $limit = Tools::getValue('limit');

    $export = new LengowExport($format, $fullmode, $all, $stream, $title, $all_product, null, $limit, $product_ids);
    $export->exec();
} else {
    die('Unauthorized access');
}