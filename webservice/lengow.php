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

$lengow = new Lengow();
// CheckIP
if (LengowCore::checkIP()) {

    // Checking configuration
    if (Tools::getValue('action') == 'check') {
        if(Tools::getValue('format') == 'json') {
            header('Content-Type: application/json');
            echo LengowCheck::getJsonCheckList();
        } else {
            echo "<h1>Lengow check configuration<h1>";
            echo LengowCheck::getHtmlCheckList();
        }
    }

    if (Tools::getValue('action') == 'logs') {
        $days = 10;
        $show_extra = false;

        if(Tools::getValue('delete') != '') {
            LengowCore::deleteProcessOrder(Tools::getValue('delete'));
        }

        if(Tools::getValue('days') != '') {
            $days = Tools::getValue('days');
        }

        if(Tools::getValue('show_extra') == 1)
            $show_extra = true;

        echo LengowCheck::getHtmlLogs($days, $show_extra);
    }

    if(Tools::getValue('action') == 'tax') {
        $id_order = Tools::getValue('id_order');
        $rate = Tools::getValue('rate');

        if($rate == '')
            die('No rate');
        if($id_order == '')
            die('No order in parameters');

        $order = new LengowOrder($id_order);
        if($order->getTaxesAverageUsed() == 19.6 || $order->getTaxesAverageUsed() == 20) {

            $rate = 1 + ($rate / 100);
            $order->total_products = Tools::ps_round($order->total_products_wt / $rate, 2);
            if(_PS_VERSION_ >= '1.5') {
                $order->total_paid_tax_excl = Tools::ps_round($order->total_paid_tax_incl / $rate, 2);
                $order->total_shipping_tax_excl = Tools::ps_round($order->total_shipping_tax_incl / $rate, 2);
                $order->total_wrapping_tax_excl = Tools::ps_round($order->total_wrapping_tax_incl / $rate, 2);

                // Update Order Carrier
                $sql = 'UPDATE `' . _DB_PREFIX_ . 'order_carrier`
                        SET `shipping_cost_tax_excl` = `shipping_cost_tax_incl` / ' . $rate . '
                        WHERE `id_order` = ' . $id_order . '
                        LIMIT 1';
                Db::getInstance()->execute($sql);
            }
            $order->update();

            // Update Order Detail
            if(_PS_VERSION_ >= '1.5')
                $order_detail = $order->getOrderDetailList();
            else
                $order_detail = $order->getProductsDetail();

            foreach($order_detail as $detail) {
                $detail = new OrderDetail($detail['id_order_detail']);

                if(_PS_VERSION_ >= '1.5') {
                    $detail->unit_price_tax_excl = $detail->unit_price_tax_incl / $rate;
                    $detail->total_price_tax_excl = $detail->total_price_tax_incl / $rate;
                    $detail->reduction_amount_tax_excl = $detail->reduction_amount_tax_incl / $rate;
                    // Update detail tax
                    $unit_amount = $detail->unit_price_tax_incl - $detail->unit_price_tax_excl;
                    $total_amount = $detail->total_price_tax_incl -  $detail->total_price_tax_excl;

                    $sql = 'UPDATE `' . _DB_PREFIX_ . 'order_detail_tax`
                        SET `unit_amount` = ' . $unit_amount . ',
                            `total_amount` = ' . $total_amount . '
                        WHERE `id_order_detail` = ' . $detail->id . '
                        LIMIT 1';

                    Db::getInstance()->execute($sql);
                } else {
                    $detail->product_price = Tools::ps_round(($detail->product_price * (1 + ($detail->tax_rate / 100))) / $rate, 6);
                    $detail->tax_rate = Tools::getValue('rate');
                }

                $detail->update();
            }

            // Order Invoice
            if(_PS_VERSION_ >= '1.5') {
                if($order->hasInvoice()) {
                    $invoice = new OrderInvoice($order->invoice_number);
                    $invoice->total_paid_tax_excl = $invoice->total_paid_tax_incl / $rate;
                    $invoice->total_discount_tax_excl = $invoice->total_discount_tax_incl / $rate;
                    $invoice->total_products = $invoice->total_products_wt / $rate;
                    $invoice->update();
                }
            }
            
            exit('End Process');
        }
    }
} else {
    die('Unauthorized access');
}