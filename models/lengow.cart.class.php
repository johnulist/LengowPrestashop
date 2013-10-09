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
 * The Lengow Cart Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 * @copyright 2013 Lengow SAS
 */
class LengowCart extends Cart {

    public static $TOTAL_CART_GET = array(
        'products' ,
        'discounts' , 
        'total' , 
        'total_without_shipping' , 
        'shipping' , 
        'wrapping' , 
        'products_without_shipping' , 
    );

    public $lengow_products = array();
    public $lengow_shipping = 0 ;
    public $lengow_channel = null ;
    public $lengow_fees = 0 ;
    public $tax_calculation_method = PS_TAX_EXC;
    
    /**
     * Current lengow order.
     */
    public static $current_order;

}