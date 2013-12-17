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
require_once '..' . $sep . 'models' . $sep . 'lengow.import.class.php';


// CheckIP
if(LengowCore::checkIP()) {
	$import = new LengowImport();
	$date_to = date('Y-m-d');
	$days = (integer) LengowCore::getCountDaysToImport();
	if(Tools::getValue('days'))
        $days = (integer) Tools::getValue('days');
        
    if(Tools::getValue('debug'))
        $debug = true;
    else
        $debug = false;
        
	// > Shop
	if($id_shop = Tools::getValue('shop')) {
		if($shop = new Shop($id_shop))
			Context::getContext()->shop = $shop;
	}
	$date_from = date('Y-m-d', strtotime(date('Y-m-d') . ' -' . $days . 'days'));
	$import->exec('commands', array('dateFrom' => $date_from,
	                                'dateTo' => $date_to,
                                    'debug' => $debug));
	LengowCore::setImportEnd();
} else {
	die('Unauthorized access');
}