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
$sep = DIRECTORY_SEPARATOR;
require_once '..' . $sep . '..' . $sep . '..' . $sep . 'config' . $sep . 'config.inc.php';
require_once '..' . $sep . '..' . $sep . '..' . $sep . 'init.php';
require_once '..' . $sep . 'lengow.php';
require_once '..' . $sep . 'models' . $sep . 'lengow.core.class.php';
require_once '..' . $sep . 'models' . $sep . 'lengow.check.class.php';
require_once '..' . $sep . 'models' . $sep . 'lengow.webservice.class.php';

$lengow = new Lengow();
// CheckIP
if (LengowCore::checkIP()) {
    $action = Tools::getValue('action');
    try {
        if(LengowWebservice::checkAction($action))
            LengowWebservice::execute($action);
    } catch (Exception $e) {
        echo $e->getMessage();
        echo '<br /><br />';
        LengowWebservice::showAvailableAction();
    }
    exit();
} else {
    die('Unauthorized access for IP : ' . $_SERVER['REMOTE_ADDR']);
}