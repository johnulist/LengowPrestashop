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
 * The Lengow Module Class.
 *
 * @author Ludovic Drin <ludovic@lengow.com>
 */
$sep = DIRECTORY_SEPARATOR;
$path = '';

if (file_exists(dirname(__FILE__) . $sep . 'override'))
    define('_LENGOW_CLASS_FOLDER_', 'override');
else
    define('_LENGOW_CLASS_FOLDER_', 'install');

if (_PS_VERSION_ <= '1.4.4.0')
    $path = $_SERVER['DOCUMENT_ROOT'] . $sep . 'modules' . $sep . 'lengow' . $sep;

require_once $path . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.core.class.php';
require_once $path . 'models' . $sep . 'lengow.check.class.php';
require_once $path . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.order.class.php';
require_once $path . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.marketplace.class.php';
require_once $path . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.product.class.php';
require_once $path . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.import.class.php';
require_once $path . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.taxrule.class.php';
require_once $path . _LENGOW_CLASS_FOLDER_ . $sep . 'lengow.export.class.php';

class Lengow extends Module {

    const LENGOW_TRACK_HOMEPAGE = 'homepage';
    const LENGOW_TRACK_PAGE = 'page';
    const LENGOW_TRACK_PAGE_LIST = 'listepage';
    const LENGOW_TRACK_PAGE_PAYMENT = 'payment';
    const LENGOW_TRACK_PAGE_CART = 'basket';
    const LENGOW_TRACK_PAGE_LEAD = 'lead';
    const LENGOW_TRACK_PAGE_CONFIRMATION = 'confirmation';

    static private $_CURRENT_PAGE_TYPE = 'page';
    static private $_USE_SSL = false;
    static private $_ID_ORDER = '';
    static private $_ORDER_TOTAL = '';
    static private $_IDS_PRODUCTS = '';
    static private $_IDS_PRODUCTS_CART = '';
    static private $_ID_CATEGORY = '';
    static private $_CRON_SELECT = array(5, 10, 15, 30);
    static private $_BUFFER_STATE = '';
    static private $_LENGOW_ORDER_STATE = array();
    static private $_TABS = array(
        'Lengow' => array('AdminLengow', 'AdminLengow14'),
        'Lengow logs' => array('AdminLengowLog', 'AdminLengowLog14'),
    );

    protected $context;

    /**
     * Construct Lengow module.
     */
    public function __construct() {
        $this->name = 'lengow';
        $this->tab = 'export';
        $this->version = '2.0.4.3';
        $this->author = 'Lengow';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.4', 'max' => '1.6');

        parent::__construct();
        $this->registerHook('actionAdminControllerSetMedia');
        $this->displayName = $this->l('Lengow');
        $this->description = $this->l('New module of lengow (beta 1).');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the Lengow module ?');

        if (_PS_VERSION_ < '1.5') {
            $sep = DIRECTORY_SEPARATOR;
            require_once _PS_MODULE_DIR_ . $this->name . $sep . 'backward_compatibility' . $sep . 'backward.php';
            $this->context = Context::getContext();
            $this->smarty = $this->context->smarty;
        }

        LengowCore::updateMarketPlaceConfiguration();
        LengowCore::updatePluginsVersion();
        LengowCore::setModule($this);
        LengowCore::cleanLog();

        // Update Process
        if(Configuration::get('LENGOW_VERSION') == '')
            Configuration::updateValue('LENGOW_VERSION', '2.0.0.0');

        LengowCheck::checkPluginVersion();

        $this->update();
        $this->_installOverride();

        if (!defined('_PS_CURRENCY_DEFAULT_'))
            define('_PS_CURRENCY_DEFAULT_', Configuration::get('PS_CURRENCY_DEFAULT'));

        self::$_LENGOW_ORDER_STATE = array(
            LengowCore::getOrderState('process'),
            LengowCore::getOrderState('shipped'),
            LengowCore::getOrderState('cancel')
        );
    }

    /**
     * Install Lengow module.
     *
     * @return boolean install sucess or fail
     */
    public function install() {
        /*
          // Creation of employee Lengow
          $new_employe = new Employee();
          $new_employe->lastname = 'Bot';
          $new_employe->firstname = 'Lengow';
          $new_employe->id_lang = (int) $this->context->language->id;
          $new_employe->email = 'lengow@lengow.fr';
          $new_employe->passwd = openssl_random_pseudo_bytes(5);
          $new_employe->id_profile = 1;
          $new_employe->add();
          // Creation of customer Lengow
          $new_customer = new Customer();
          $new_customer->lastname   = 'Client';
          $new_customer->firstname = 'Lengow';
          $new_customer->passwd = '|€-§oW';
          $new_customer->email = openssl_random_pseudo_bytes(5);
          $new_customer->add();
         */
        $this->_createTab();
        return parent::install() &&
                Configuration::updateValue('LENGOW_AUTHORIZED_IP', $_SERVER['REMOTE_ADDR']) &&
                Configuration::updateValue('LENGOW_TRACKING', '') &&
                Configuration::updateValue('LENGOW_ID_CUSTOMER', '') &&
                Configuration::updateValue('LENGOW_ID_GROUP', '') &&
                Configuration::updateValue('LENGOW_TOKEN', '') &&
                Configuration::updateValue('LENGOW_EXPORT_ALL', true) &&
                Configuration::updateValue('LENGOW_EXPORT_DISABLED', false) &&
                Configuration::updateValue('LENGOW_EXPORT_NEW', false) &&
                Configuration::updateValue('LENGOW_EXPORT_ALL_ATTRIBUTES', true) &&
                Configuration::updateValue('LENGOW_EXPORT_FULLNAME', true) &&
                Configuration::updateValue('LENGOW_EXPORT_FEATURES', false) &&
                Configuration::updateValue('LENGOW_EXPORT_FORMAT', 'csv') &&
                Configuration::updateValue('LENGOW_EXPORT_FIELDS', json_encode(LengowCore::$DEFAULT_FIELDS)) &&
                Configuration::updateValue('LENGOW_IMAGE_TYPE', 3) &&
                Configuration::updateValue('LENGOW_IMAGES_COUNT', 3) &&
                Configuration::updateValue('LENGOW_ORDER_ID_PROCESS', 2) &&
                Configuration::updateValue('LENGOW_ORDER_ID_SHIPPED', 4) &&
                Configuration::updateValue('LENGOW_ORDER_ID_CANCEL', 6) &&
                Configuration::updateValue('LENGOW_IMPORT_METHOD_NAME', false) &&
                Configuration::updateValue('LENGOW_IMPORT_FORCE_PRODUCT', false) &&
                Configuration::updateValue('LENGOW_IMPORT_DAYS', 3) &&
                Configuration::updateValue('LENGOW_FORCE_PRICE', true) &&
                Configuration::updateValue('LENGOW_CARRIER_DEFAULT', Configuration::get('PS_CARRIER_DEFAULT')) &&
                Configuration::updateValue('LENGOW_FLOW_DATA', '') &&
                Configuration::updateValue('LENGOW_MIGRATE', false) &&
                Configuration::updateValue('LENGOW_MP_CONF', false) &&
                Configuration::updateValue('LENGOW_CRON', false) &&
                Configuration::updateValue('LENGOW_DEBUG', false) &&
                // Orders lengow table
                Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lengow_orders` (
                `id_order` INTEGER(10) UNSIGNED NOT NULL ,
                `id_order_lengow` VARCHAR(32) ,
                `id_shop` INTEGER(11) UNSIGNED NOT NULL DEFAULT \'1\' ,
                `id_shop_group` INTEGER(11) UNSIGNED NOT NULL DEFAULT \'1\' ,
                `id_lang` INTEGER(10) UNSIGNED NOT NULL DEFAULT \'1\' ,
                `id_flux` INTEGER(11) UNSIGNED NOT NULL ,
                `marketplace` VARCHAR(100) ,
                `message` TEXT ,
                `total_paid` DECIMAL(17,2) NOT NULL ,
                `carrier` VARCHAR(100) ,
                `tracking` VARCHAR(100) ,
                `extra` TEXT ,
                `date_add` DATETIME NOT NULL ,
                PRIMARY KEY(id_order) ,
                INDEX (`id_order_lengow`) ,
                INDEX (`id_flux`) ,
                INDEX (`id_shop`) ,
                INDEX (`id_shop_group`) ,
                INDEX (`marketplace`) ,
                INDEX (`date_add`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;') &&
                // Products exports
                Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lengow_product` (
                `id_product` INTEGER UNSIGNED NOT NULL ,
                `id_shop` INTEGER(11) UNSIGNED NOT NULL DEFAULT \'1\' ,
                `id_shop_group` INTEGER(11) UNSIGNED NOT NULL DEFAULT \'1\' ,
                `id_lang` INTEGER(10) UNSIGNED NOT NULL DEFAULT \'1\' ,
                PRIMARY KEY ( `id_product` ) ,
                INDEX (`id_shop`) ,
                INDEX (`id_shop_group`) ,
                INDEX (`id_lang`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;') &&
                // Products exports
                //$this->registerHook('leftColumn') &&
                // TODO add hook add product
                $this->registerHook('footer') && // displayFooter
                $this->registerHook('postUpdateOrderStatus') && // actionOrderStatusPostUpdate
                $this->registerHook('paymentTop') && // displayPaymentTop
                $this->registerHook('addproduct') && // actionProductAdd
                $this->registerHook('adminOrder') && // displayAdminOrder
                $this->registerHook('backOfficeHeader') && // Backofficeheader
                $this->registerHook('newOrder') && // actionValidateOrder
                $this->registerHook('updateOrderStatus') && // actionOrderStatusUpdate
                (LengowCore::compareVersion('1.5') === 0 ? $this->registerHook('displayAdminHomeStatistics') : true) &&
                (LengowCore::compareVersion('1.5') === 0 ? $this->registerHook('actionAdminControllerSetMedia') : true) &&
                $this->registerHook('orderConfirmation'); // displayOrderConfirmation
    }

    /**
     * Update process
     *
     * @return void
     */
    public function update() {
        if (Configuration::get('LENGOW_EXPORT_FIELDS') == '')
            Configuration::updateValue('LENGOW_EXPORT_FIELDS', json_encode(LengowCore::$DEFAULT_FIELDS));

        // Update version 2.0.4
        if(Configuration::get('LENGOW_VERSION') == '2.0.0.0') {
            // Import log
            $add_log_table = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'lengow_logs_import ('
                         . ' `lengow_order_id` VARCHAR(32) NOT NULL,'
                         . ' `is_processing` int(11) DEFAULT 0,'
                         . ' `is_finished` int(11) NOT NULL,'
                         . ' `message` varchar(255) DEFAULT NULL,'
                         . ' `date` datetime DEFAULT NULL,'
                         . ' `extra` text NOT NULL,'
                         . ' PRIMARY KEY(lengow_order_id));';

            Db::getInstance()->execute($add_log_table);
            
            // Add Lengow order error status
            $states = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'order_state WHERE module_name = \'' . $this->name . '\'');
            if(empty($states)) {
                $lengow_state = new OrderState();
                $lengow_state->send_email = false;
                $lengow_state->module_name = $this->name;
                $lengow_state->invoice = false;
                $lengow_state->delivery = false;
                $lengow_state->shipped = false;
                $lengow_state->paid = false;
                $lengow_state->unremovable = false;
                $lengow_state->logable = false;
                $lengow_state->color = '#205985';
                $lengow_state->name[1] = 'Erreur technique - Lengow';
                $languages = Language::getLanguages(false);
                foreach ($languages as $language)
                    $lengow_state->name[$language['id_lang']] = 'Erreur technique - Lengow';
                $lengow_state->add();
                Configuration::updateValue('LENGOW_STATE_ERROR', $lengow_state->id);
            } else {
                Configuration::updateValue('LENGOW_STATE_ERROR', $states[0]['id_order_state']);
            }

            Configuration::updateValue('LENGOW_VERSION', '2.0.4.0');
        }

        // Update version 2.0.4.1
        if(Configuration::get('LENGOW_VERSION') < '2.0.4.1') {
            $this->registerHook('actionValidateLengowOrder');
            Configuration::updateValue('LENGOW_VERSION', '2.0.4.1');
        }

        // Update version 2.0.4.2
        if(Configuration::get('LENGOW_VERSION') < '2.0.4.2') {
            $this->_createTab();
            Configuration::updateValue('LENGOW_VERSION', '2.0.4.2');
        }

        // Update version 2.0.4.3
        if(Configuration::get('LENGOW_VERSION') < '2.0.4.3') {
            Configuration::updateValue('LENGOW_TRACKING_ID', 'id');
            Configuration::updateValue('LENGOW_VERSION', '2.0.4.3');
        }
    }

    /**
     * Ininstall Lengow module.
     *
     * @return boolean uninstall sucess or fail
     */
    public function uninstall() {
        if (!parent::uninstall() ||
                !Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'lengow_orders` , ' . '`' . _DB_PREFIX_ . 'lengow_product`;') ||
                !Configuration::deleteByName('LENGOW_LOGO_URL') ||
                !Configuration::deleteByName('LENGOW_AUTHORIZED_IP') ||
                !Configuration::deleteByName('LENGOW_TRACKING') ||
                !Configuration::deleteByName('LENGOW_ID_CUSTOMER') ||
                !Configuration::deleteByName('LENGOW_ID_GROUP') ||
                !Configuration::deleteByName('LENGOW_TOKEN') ||
                !Configuration::deleteByName('LENGOW_EXPORT_ALL') ||
                !Configuration::deleteByName('LENGOW_EXPORT_NEW') ||
                !Configuration::deleteByName('LENGOW_EXPORT_ALL_ATTRIBUTES') ||
                !Configuration::deleteByName('LENGOW_EXPORT_FULLNAME') ||
                !Configuration::deleteByName('LENGOW_EXPORT_FIELDS') ||
                !Configuration::deleteByName('LENGOW_ORDER_ID_PROCESS') ||
                !Configuration::deleteByName('LENGOW_ORDER_ID_SHIPPED') ||
                !Configuration::deleteByName('LENGOW_ORDER_ID_CANCEL') ||
                !Configuration::deleteByName('LENGOW_IMAGE_TYPE') ||
                !Configuration::deleteByName('LENGOW_IMAGES_COUNT') ||
                !Configuration::deleteByName('LENGOW_IMPORT_METHOD_NAME') ||
                !Configuration::deleteByName('LENGOW_IMPORT_FORCE_PRODUCT') ||
                !Configuration::deleteByName('LENGOW_IMPORT_DAYS') ||
                !Configuration::deleteByName('LENGOW_EXPORT_FEATURES') ||
                !Configuration::deleteByName('LENGOW_EXPORT_FORMAT') ||
                !Configuration::deleteByName('LENGOW_EXPORT_FILE') ||
                !Configuration::deleteByName('LENGOW_CARRIER_DEFAULT') ||
                !Configuration::deleteByName('LENGOW_FLOW_DATA') ||
                !Configuration::deleteByName('LENGOW_MIGRATE') ||
                !Configuration::deleteByName('LENGOW_CRON') ||
                !Configuration::deleteByName('LENGOW_DEBUG') ||
                !$this->_uninstallTab())
            return false;
        return true;
    }

    /**
     * Get the admin content configuration.
     *
     * @return varchar generate html for admin configuration
     */
    public function getContent() {
        $selected_tab = $this->_selectedTab();
        //$html = $this->_displayTabs();
        $html = $this->_postProcessForm();
        switch ($selected_tab) {
            case 'informations' :
                $html .= $this->_getInformationAdmin();
                break;
            case 'products' :
                $html .= $this->_getProductsAdmin();
                break;
            case 'configuration' :
            default :
                $html .= $this->_getConfigAdmin();
                break;
        }
        return $html;
    }

    /**
     * Process after post admin form.
     */
    private function _postProcessForm() {
        $output = null;
        if(isset($_POST['reset-import'])) {
            Configuration::updateValue('LENGOW_IS_IMPORT', 'stopped');
        }
        if (isset($_POST['submit' . $this->name])) {
            Configuration::updateValue('LENGOW_AUTHORIZED_IP', Tools::getValue('lengow_authorized_ip'));
            Configuration::updateValue('LENGOW_TRACKING', Tools::getValue('lengow_tracking'));
            Configuration::updateValue('LENGOW_TRACKING_ID', Tools::getValue('lengow_tracking_id'));
            Configuration::updateValue('LENGOW_ID_CUSTOMER', Tools::getValue('lengow_customer_id'));
            Configuration::updateValue('LENGOW_ID_GROUP', Tools::getValue('lengow_group_id'));
            Configuration::updateValue('LENGOW_TOKEN', Tools::getValue('lengow_token'));
            Configuration::updateValue('LENGOW_EXPORT_ALL', Tools::getValue('lengow_export_all'));
            Configuration::updateValue('LENGOW_EXPORT_NEW', Tools::getValue('lengow_export_new'));
            Configuration::updateValue('LENGOW_EXPORT_ALL_ATTRIBUTES', Tools::getValue('lengow_export_all_attributes'));
            Configuration::updateValue('LENGOW_EXPORT_FEATURES', Tools::getValue('lengow_export_features'));
            Configuration::updateValue('LENGOW_EXPORT_FULLNAME', Tools::getValue('lengow_export_fullname'));
            Configuration::updateValue('LENGOW_EXPORT_FIELDS', json_encode(Tools::getValue('lengow_export_fields')));
            Configuration::updateValue('LENGOW_ORDER_ID_PROCESS', Tools::getValue('lengow_order_process'));
            Configuration::updateValue('LENGOW_ORDER_ID_SHIPPED', Tools::getValue('lengow_order_shipped'));
            Configuration::updateValue('LENGOW_ORDER_ID_CANCEL', Tools::getValue('lengow_order_cancel'));
            Configuration::updateValue('LENGOW_IMAGE_TYPE', Tools::getValue('lengow_image_type'));
            Configuration::updateValue('LENGOW_IMAGES_COUNT', Tools::getValue('lengow_images_count'));
            Configuration::updateValue('LENGOW_IMPORT_METHOD_NAME', Tools::getValue('lengow_method_name'));
            Configuration::updateValue('LENGOW_IMPORT_FORCE_PRODUCT', Tools::getValue('lengow_import_force_product'));
            Configuration::updateValue('LENGOW_IMPORT_DAYS', Tools::getValue('lengow_import_days'));
            Configuration::updateValue('LENGOW_FORCE_PRICE', Tools::getValue('lengow_force_price'));
            Configuration::updateValue('LENGOW_EXPORT_FORMAT', Tools::getValue('lengow_export_format'));
            Configuration::updateValue('LENGOW_EXPORT_FILE', Tools::getValue('lengow_export_file'));
            Configuration::updateValue('LENGOW_CARRIER_DEFAULT', Tools::getValue('lengow_carrier_default'));
            Configuration::updateValue('LENGOW_DEBUG', Tools::getValue('lengow_debug'));
            Configuration::updateValue('LENGOW_EXPORT_DISABLED', Tools::getValue('lengow_export_disabled'));

            // Send to Lengow versions
            if (LengowCore::getTokenCustomer() && LengowCore::getIdCustomer() && LengowCore::getGroupCustomer()) {
                $lengow_connector = new LengowConnector((integer) LengowCore::getIdCustomer(), LengowCore::getTokenCustomer());
                $orders = $lengow_connector->api('updateEcommerceSolution', array('type' => 'Prestashop',
                    'version' => _PS_VERSION_,
                    'idClient' => LengowCore::getIdCustomer(),
                    'idGroup' => LengowCore::getGroupCustomer(),
                    'module' => $this->version));
            }

            if (Tools::getValue('cron-delay') > 0) {
                Configuration::updateValue('LENGOW_CRON', Tools::getValue('cron-delay'));
                self::updateCron(Tools::getValue('cron-delay'));
            }
        }
    }

    /**
     * Get the config form admin
     *
     * @return varchar form html
     */
    private function _getConfigAdmin() {
        if (LengowCore::compareVersion('1.5') == 0) {
            // Get default language
            $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
            // Init Fields form array

            $fields_form[0]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Check configuration'),
                ),
                'input' => array(
                    array(
                        'type' => 'free',
                        'label' => $this->l('Checklist'),
                        'name' => 'lengow_check_configuration',
                        'desc' => '',
                        'required' => false,
                    ),
                ),
            );

            $fields_form[1]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Account'),
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Customer ID'),
                        'name' => 'lengow_customer_id',
                        'size' => 20,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Group ID'),
                        'name' => 'lengow_group_id',
                        'size' => 20,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Token API'),
                        'name' => 'lengow_token',
                        'size' => 32,
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'button'
                ),
            );
            $fields_form[2]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Security'),
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Authorized IP'),
                        'name' => 'lengow_authorized_ip',
                        'size' => 100,
                    ),
                ),
            );
            $fields_form[3]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Tracking'),
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Tracker type choice'),
                        'name' => 'lengow_tracking',
                        'options' => array(
                            'query' => LengowCore::getTrackers(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Product ID for tag'),
                        'name' => 'lengow_tracking_id',
                        'options' => array(
                            'query' => LengowCore::getTrackerChoiceId(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                ),
            );
            $fields_form[4]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Export parameters'),
                ),
                'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Export all products'),
                        'desc' => $this->l('If don\'t want to export all your available products, click "no" and go onto Tab Prestashop to select yours products'),
                        'name' => 'lengow_export_all',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Export disabled products'),
                        'desc' => $this->l('If you want to export disabled products, click "yes".'),
                        'name' => 'lengow_export_disabled',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Auto export of new product(s)'),
                        'desc' => $this->l('If you click "yes" your new product(s) will be automatically exported on the next feed'),
                        'name' => 'lengow_export_new',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Export product variations'),
                        'desc' => $this->l('If don\'t want to export all your products\' variations, click "no"'),
                        'name' => 'lengow_export_all_attributes',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Export product features'),
                        'desc' => $this->l('If you click "yes",  your product(s) will be exported with features.'),
                        'name' => 'lengow_export_features',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Title + attributes + features'),
                        'desc' => $this->l('Select this option if you want a variation product title as title + attributes + feature. By default the title will be the product name'),
                        'name' => 'lengow_export_fullname',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Image type to export'),
                        'name' => 'lengow_image_type',
                        'options' => array(
                            'query' => ImageType::getImagesTypes('products'),
                            'id' => 'id_image_type',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Number of images to export'),
                        'name' => 'lengow_images_count',
                        'options' => array(
                            'query' => LengowCore::getImagesCount(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Export default format'),
                        'name' => 'lengow_export_format',
                        'options' => array(
                            'query' => LengowCore::getExportFormats(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Export in a file'),
                        'desc' => $this->l('You should use this option if you have more than 10,000 products') . $this->getFileLink(),
                        'name' => 'lengow_export_file',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'multiple' => true,
                        'size' => 20,
                        'class' => 'lengow-select',
                        'label' => $this->l('Export fields'),
                        'desc' => $this->l('Maintain "control key or command key" to select fields.') . $this->getFileLink(),
                        'name' => 'lengow_export_fields[]',
                        'options' => array(
                            'query' => LengowExport::getDefaultFields(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'free',
                        'label' => $this->l('Your export script'),
                        'name' => 'url_feed_export',
                        'size' => 100,
                    ),
                ),
            );
            $fields_form[5]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Feed'),
                ),
                'input' => array(
                    array(
                        'type' => 'free',
                        'label' => $this->l('Feed used by Lengow'),
                        'name' => 'lengow_flow',
                        'desc' => $this->l('If you use the backoffice of the Lengow module, migrate your feed when you are sure to be ready') . '.<br />' .
                        $this->l('If you want to use the file export, don\'t use this fonctionality. Please contact Lengow Support Team') . '.'
                    ,
                    ),
                ),
            );
            $fields_form[6]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Import parameters'),
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Status of process orders'),
                        'name' => 'lengow_order_process',
                        'options' => array(
                            'query' => OrderState::getOrderStates((int) $this->context->cookie->id_lang),
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Status of shipped orders'),
                        'name' => 'lengow_order_shipped',
                        'options' => array(
                            'query' => OrderState::getOrderStates((int) $this->context->cookie->id_lang),
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Status of cancelled orders'),
                        'name' => 'lengow_order_cancel',
                        'options' => array(
                            'query' => OrderState::getOrderStates((int) $this->context->cookie->id_lang),
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Associated payment method'),
                        'name' => 'lengow_method_name',
                        'options' => array(
                            'query' => LengowCore::getShippingName(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'name' => 'lengow_carrier_default',
                        'label' => $this->l('Default carrier'),
                        'desc' => $this->l('Your default carrier'),
                        'cast' => 'intval',
                        'type' => 'select',
                        'identifier' => 'id_carrier',
                        'options' => array(
                            'query' => Carrier::getCarriers($this->context->cookie->id_lang, true, false, false, null, Carrier::ALL_CARRIERS),
                            'id' => 'id_carrier',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Import from x days'),
                        'name' => 'lengow_import_days',
                        'size' => 20,
                        'required' => true,
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Forced price'),
                        'desc' => $this->l('This option allows to force the product prices of the marketplace orders during the import'),
                        'name' => 'lengow_force_price',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Force products'),
                        'desc' => $this->l('Yes if you want to force import of disabled or out of stock product'),
                        'name' => 'lengow_import_force_product',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'free',
                        'label' => $this->l('Import state'),
                        'name' => 'lengow_is_import',
                        'size' => 200,
                    ),
                    array(
                        'type' => 'free',
                        'label' => $this->l('Your import script'),
                        'name' => 'url_feed_import',
                        'size' => 100,
                    ),
                ),
            );
            $fields_form[7]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Import cron'),
                ),
                'input' => array(
                    array(
                        'type' => 'free',
                        'label' => $this->l('Module Crontab'),
                        'name' => 'lengow_cron',
                        'desc' => $this->l('Hold "control or command" and click to select fields.'),
                    ),
                ),
            );
            $fields_form[8]['form'] = array(
                'legend' => array(
                    'title' => $this->l('Developer'),
                ),
                'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Debug mode'),
                        'name' => 'lengow_debug',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'free',
                        'label' => $this->l('Logs'),
                        'name' => 'lengow_logs',
                    ),
                ),
            );
            // +Status commandes
            // +Nom transporteur : marketplace ou lengow
            // +Debug mode
            // +Tache cron
            // +Choix image principale
            $helper = new HelperForm();
            // Module, token and currentIndex
            $helper->module = $this;
            $helper->name_controller = $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
            // Language
            $helper->default_form_language = $default_lang;
            $helper->allow_employee_form_lang = $default_lang;
            // Title and toolbar
            $helper->title = $this->displayName;
            $helper->show_toolbar = true;        // false -> remove toolbar
            $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
            $helper->submit_action = 'submit' . $this->name;
            $helper->toolbar_btn = array(
                'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
                ),
                'back' => array(
                    'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
            );

            // Load currents values
            $helper->fields_value['lengow_customer_id'] = Configuration::get('LENGOW_ID_CUSTOMER');
            $helper->fields_value['lengow_group_id'] = Configuration::get('LENGOW_ID_GROUP');
            $helper->fields_value['lengow_token'] = Configuration::get('LENGOW_TOKEN');
            $helper->fields_value['lengow_authorized_ip'] = Configuration::get('LENGOW_AUTHORIZED_IP');
            $helper->fields_value['lengow_export_all'] = Configuration::get('LENGOW_EXPORT_ALL');
            $helper->fields_value['lengow_export_disabled'] = Configuration::get('LENGOW_EXPORT_DISABLED');
            $helper->fields_value['lengow_export_new'] = Configuration::get('LENGOW_EXPORT_NEW');
            $helper->fields_value['lengow_export_all_attributes'] = Configuration::get('LENGOW_EXPORT_ALL_ATTRIBUTES');
            $helper->fields_value['lengow_export_features'] = Configuration::get('LENGOW_EXPORT_FEATURES');
            $helper->fields_value['lengow_export_fullname'] = Configuration::get('LENGOW_EXPORT_FULLNAME');
            $helper->fields_value['lengow_export_file'] = Configuration::get('LENGOW_EXPORT_FILE');
            $helper->fields_value['lengow_export_fields[]'] = json_decode(Configuration::get('LENGOW_EXPORT_FIELDS'));
            $helper->fields_value['lengow_tracking'] = Configuration::get('LENGOW_TRACKING');
            $helper->fields_value['lengow_tracking_id'] = Configuration::get('LENGOW_TRACKING_ID');
            $helper->fields_value['lengow_order_process'] = Configuration::get('LENGOW_ORDER_ID_PROCESS');
            $helper->fields_value['lengow_order_shipped'] = Configuration::get('LENGOW_ORDER_ID_SHIPPED');
            $helper->fields_value['lengow_order_cancel'] = Configuration::get('LENGOW_ORDER_ID_CANCEL');
            $helper->fields_value['lengow_image_type'] = Configuration::get('LENGOW_IMAGE_TYPE');
            $helper->fields_value['lengow_images_count'] = Configuration::get('LENGOW_IMAGES_COUNT');
            $helper->fields_value['lengow_method_name'] = Configuration::get('LENGOW_IMPORT_METHOD_NAME');
            $helper->fields_value['lengow_import_force_product'] = Configuration::get('LENGOW_IMPORT_FORCE_PRODUCT');
            $helper->fields_value['lengow_import_days'] = Configuration::get('LENGOW_IMPORT_DAYS');
            $helper->fields_value['lengow_export_format'] = Configuration::get('LENGOW_EXPORT_FORMAT');
            $helper->fields_value['lengow_carrier_default'] = Configuration::get('LENGOW_CARRIER_DEFAULT');
            $helper->fields_value['lengow_force_price'] = Configuration::get('LENGOW_FORCE_PRICE');
            $helper->fields_value['lengow_debug'] = Configuration::get('LENGOW_DEBUG');
            $helper->fields_value['lengow_is_import'] = $this->_getFormIsImport();

            $links = $this->_getWebservicesLinks();
            $helper->fields_value['url_feed_export'] = $links['link_feed_export'];
            $helper->fields_value['url_feed_import'] = $links['link_feed_import'];

            $helper->fields_value['lengow_check_configuration'] = $this->_getCheckList();
            $helper->fields_value['lengow_logs'] = $this->_getLogFiles();

            $helper->fields_value['lengow_flow'] = $this->_getFormFeeds();

            $helper->fields_value['lengow_cron'] = $this->_getFormCron();

            return $helper->generateForm($fields_form);
        } else {
            return $this->displayForm14();
        }
    }

    /**
     * Get the config form admin for version under 1.5
     *
     * @return varchar form html
     */
    public function displayForm14() {
        if(_PS_VERSION_ <= '1.4.4.0') {
            $options = array(
                'trackers' => LengowCore::getTrackers(),
                'images' => ImageType::getImagesTypes('products'),
                'images_count' => LengowCore::getImagesCount(),
                'formats' => LengowCore::getExportFormats(),
                'states' => OrderState::getOrderStates((int) $this->context->cookie->id_lang),
                'shippings' => LengowCore::getShippingName(),
                'export_fields' => LengowExport::getDefaultFields(),
                'carriers' => Carrier::getCarriers($this->context->cookie->id_lang, true, false, false, null, ALL_CARRIERS),
            );
        } else {
            $options = array(
                'trackers' => LengowCore::getTrackers(),
                'images' => ImageType::getImagesTypes('products'),
                'images_count' => LengowCore::getImagesCount(),
                'formats' => LengowCore::getExportFormats(),
                'states' => OrderState::getOrderStates((int) $this->context->cookie->id_lang),
                'shippings' => LengowCore::getShippingName(),
                'export_fields' => LengowExport::getDefaultFields(),
                'carriers' => Carrier::getCarriers($this->context->cookie->id_lang, true, false, false, null, Carrier::ALL_CARRIERS),
            );
        }
        $links = $this->_getWebservicesLinks();
        $this->context->smarty->assign(
                array(
                    'lengow_customer_id' => Configuration::get('LENGOW_ID_CUSTOMER'),
                    'lengow_group_id' => Configuration::get('LENGOW_ID_GROUP'),
                    'lengow_token' => Configuration::get('LENGOW_TOKEN'),
                    'lengow_authorized_ip' => Configuration::get('LENGOW_AUTHORIZED_IP'),
                    'lengow_export_all' => Configuration::get('LENGOW_EXPORT_ALL'),
                    'lengow_export_disabled' => Configuration::get('LENGOW_EXPORT_DISABLED'),
                    'lengow_export_new' => Configuration::get('LENGOW_EXPORT_NEW'),
                    'lengow_export_all_attributes' => Configuration::get('LENGOW_EXPORT_ALL_ATTRIBUTES'),
                    'lengow_export_fullname' => Configuration::get('LENGOW_EXPORT_FULLNAME'),
                    'lengow_export_features' => Configuration::get('LENGOW_EXPORT_FEATURES'),
                    'lengow_export_file' => Configuration::get('LENGOW_EXPORT_FILE'),
                    'lengow_export_fields' => json_decode(Configuration::get('LENGOW_EXPORT_FIELDS')),
                    'lengow_tracking' => Configuration::get('LENGOW_TRACKING'),
                    'lengow_tracking_id' => Configuration::get('LENGOW_TRACKING_ID'),
                    'lengow_order_process' => Configuration::get('LENGOW_ORDER_ID_PROCESS'),
                    'lengow_order_shipped' => Configuration::get('LENGOW_ORDER_ID_SHIPPED'),
                    'lengow_order_cancel' => Configuration::get('LENGOW_ORDER_ID_CANCEL'),
                    'lengow_image_type' => Configuration::get('LENGOW_IMAGE_TYPE'),
                    'lengow_images_count' => Configuration::get('LENGOW_IMAGES_COUNT'),
                    'lengow_method_name' => Configuration::get('LENGOW_IMPORT_METHOD_NAME'),
                    'lengow_import_days' => Configuration::get('LENGOW_IMPORT_DAYS'),
                    'lengow_export_format' => Configuration::get('LENGOW_EXPORT_FORMAT'),
                    'lengow_import_force_product' => Configuration::get('LENGOW_IMPORT_FORCE_PRODUCT'),
                    'lengow_carrier_default' => Configuration::get('LENGOW_CARRIER_DEFAULT'),
                    'lengow_force_price' => Configuration::get('LENGOW_FORCE_PRICE'),
                    'lengow_debug' => Configuration::get('LENGOW_DEBUG'),
                    'url_feed_export' => $links['link_feed_export'],
                    'url_feed_import' => $links['link_feed_import'],
                    'lengow_flow' => $this->_getFormFeeds(),
                    'lengow_cron' => $this->_getFormCron(),
                    'lengow_is_import' => $this->_getFormIsImport(),
                    'link_file_export' => $this->getFileLink(),
                    'options' => $options,
                    'checklist' => $this->_getCheckList(),
                    'log_files' => $this->_getLogFiles()
                )
        );
        return $this->display(__FILE__, 'views/templates/admin/form.tpl');
    }

    /**
     * Get the form flows.
     *
     * @return string The form flow
     */
    private function _getFormFeeds() {
        $display = '';
        if (!LengowCheck::isCurlActivated())
            return '<p>' . $this->l('Function unavailable with your configuration, please install PHP CURL extension.') . '</p>';

        $flows = LengowCore::getFlows();
        if (!$flows || $flows['return'] == 'KO') {
            return '<div clas="lengow-margin">' . $this->l('Please provide your Customer ID, Group ID and API Token ') . '</div>';
        }
        $data_flows_array = array();
        $data_flows = Tools::jsonDecode(Configuration::get('LENGOW_FLOW_DATA'));
        if ($data_flows) {
            foreach ($data_flows as $key => $value) {
                $data_flows_array[$key] = get_object_vars($value);
            }
        }
        if (_PS_VERSION_ < '1.5') {
            $controller = '/modules/lengow/v14/ajax.php?';
        } else {
            $controller = 'index.php?controller=AdminLengow&ajax&action=updateFlow&token=' . Tools::getAdminTokenLite('AdminLengow') . '';
        }
        if ($flows['return'] == 'OK') {
            $display = '<table id="table-flows">';
            $display .= '<tr>'
                    . '<th>' . $this->l('Feed ID') . '</th>'
                    . '<th>' . $this->l('Feed name') . '</th>'
                    . '<th>' . $this->l('Current feed') . '</th>'
                    . '<th>' . $this->l('Format') . '</th>'
                    . '<th>' . $this->l('Full mode') . '</th>'
                    . '<th>' . $this->l('All products') . '</th>'
                    . '<th>' . $this->l('Currency') . '</th>'
                    . '<th>' . $this->l('Shop') . '</th>'
                    . '<th>' . $this->l('Language') . '</th>'
                    . '<th></th>'
                    . '<td>';
            foreach ($flows['feeds'] as $key => $flow) {
                $display .= '<tr><td>' . $key . '</td><td>' . $flow['name'] . '</td><td><span id="lengow-flux-' . $key . '" class="lengow-flux">' . $flow['url'] . '</td>';
                $display .= $this->_formFeed($key, $data_flows_array);
                $display .= '<td>'
                        . '<button id="lengow-migrate-action-' . $key . '" data-url="' . $controller . '" data-flow="' . $key . '" class="lengow-migrate-action">' . $this->l('Migrate this flow') . '</button> '
                        . '<button id="lengow-migrate-action-all-' . $key . '" data-url="' . $controller . '" data-flow="' . $key . '" class="lengow-migrate-action-all">' . $this->l('Migrate all flows') . '</button>'
                        . '</span> </td>';
                $display .= '</tr>';
            }
            $display .= '</table>';
        }
        return $display;
    }

    /**
     * Get inputs to config a flow.
     *
     * @param integer $id_flow The ID of flow to config
     * @param array $data_flows The array of flows's configuration
     *
     * @return string The inputs html
     */
    private function _formFeed($id_flow, &$data_flows) {
        $form = '';
        // Init
        $formats = LengowCore::getExportFormats();
        $currencies = Currency::getCurrencies();
        $shops = Shop::getShops();
        $languages = Language::getLanguages();
        if (!isset($data_flows[$id_flow])) {
            $data_flows[$id_flow] = array('format' => $formats[0]->id,
                'mode' => 1,
                'all' => 1,
                'currency' => $currencies[0]['iso_code'],
                'shop' => $shops[1]['id_shop'],
                'language' => $languages[0]['iso_code'],
            );
            Configuration::updateValue('LENGOW_FLOW_DATA', Tools::jsonEncode($data_flows));
        }
        $data = $data_flows[$id_flow];
        // Format
        $form .= '<td><select name="format-' . $id_flow . '" id="format-' . $id_flow . '">';
        foreach ($formats as $format) {
            $form .= '<option id="' . $format->id . '"' . ($data['format'] == $format->id ? ' selected="selected"' : '') . '> ' . $format->name . '</option>';
        }
        $form .= '<select></td>';
        // Mode
        $form .= '<td><select name="mode-' . $id_flow . '" id="mode-' . $id_flow . '">';
        $form .= '<option id="1"' . ($data['mode'] == 1 ? ' selected="selected"' : '') . ' value="full"> ' . $this->l('yes') . '</option>';
        $form .= '<option id="0"' . ($data['mode'] == 0 ? ' selected="selected"' : '') . ' value="simple"> ' . $this->l('no') . '</option>';
        $form .= '<select></td>';

        // All
        $form .= '<td><select name="all-' . $id_flow . '" id="all-' . $id_flow . '">';
        $form .= '<option id="1"' . ($data['all'] == 1 ? ' selected="selected"' : '') . ' value="true"> ' . $this->l('yes') . '</option>';
        $form .= '<option id="0"' . ($data['all'] == 0 ? ' selected="selected"' : '') . ' value="false"> ' . $this->l('no') . '</option>';
        $form .= '<select></td>';

        // Currency
        $form .= '<td><select name="currency-' . $id_flow . '" id="currency-' . $id_flow . '">';
        foreach ($currencies as $currency) {
            $form .= '<option id="' . $currency['iso_code'] . '"' . ($data['currency'] == $currency['iso_code'] ? ' selected="selected"' : '') . ' value="' . $currency['iso_code'] . '"> ' . $currency['name'] . '</option>';
        }
        $form .= '</select></td>';

        // Shop
        $form .= '<td><select name="shop-' . $id_flow . '" id="shop-' . $id_flow . '">';
        foreach ($shops as $shop) {
            $form .= '<option id="' . $shop['id_shop'] . '"' . ($data['shop'] == $shop['id_shop'] ? ' selected="selected"' : '') . ' value="' . $shop['id_shop'] . '"> ' . $shop['name'] . '</option>';
        }
        $form .= '</select></td>';

        // Langage
        $form .= '<td><select name="lang-' . $id_flow . '" id="lang-' . $id_flow . '">';
        foreach ($languages as $language) {
            $form .= '<option id="' . $language['iso_code'] . '"' . ($data['language'] == $language['iso_code'] ? ' selected="selected"' : '') . ' value="' . $language['iso_code'] . '"> ' . $language['name'] . '</option>';
        }
        $form .= '</select></td>';
        return $form;
    }

    /**
     * Get select cron.
     *
     * @return string The select html
     */
    private function _getFormCron() {
        $links = $this->_getWebservicesLinks();
        if ($module_cron = Module::getInstanceByName('cron')) {
            $form = '<p>' . $this->l('You can use the Crontab Module to import orders from Lengow') . '</p>';
            $cron_value = Configuration::get('LENGOW_CRON');
            $form .= '<select id="cron-delay" name="cron-delay">';
            $form .= '<option value="NULL">' . $this->l('No cron configured') . '</option>';
            foreach (self::$_CRON_SELECT as $value) {
                $form .= '<option value="' . $value . '"' . ($cron_value == $value ? ' selected="selected"' : '') . '>' . $value . ' ' . $this->l('min') . '</option>';
            }
            $form .= '</select>';
            if (!self::getCron()) {
                $form .= '<span class="lengow-no">' . $this->l('Cron Import is not configured on your Prestashop') . '</span>';
            } else {
                $form .= '<span class="lengow-yes">' . $this->l('Cron Import exists on your Prestashop') . '</span>';
            }
            $form .= '<p> - ' . $this->l('or') . ' - </p>';
        } else {
            $form = '<p>' . $this->l('You can install "Crontab" Prestashop Plugin') . '</p>';
            $form .= '<p> - ' . $this->l('or') . ' - </p>';
        }
        $form .= '<p>' . $this->l('If you are using an unix system, you can use unix crontab like this :') . '</p>';
        $form .= '<strong><code>*/15 * * * * wget ' . $links['url_feed_import'] . '</code></strong><br /><br />';
        return '<div class="lengow-margin">' . $form . '</div>';
    }

    /**
     *
     * Get state of import process
     * 
     * @return string Html content
     */
    private function _getFormIsImport() {
        $content = '<p>';
        if(Configuration::get('LENGOW_IS_IMPORT') === 'processing') {
            $content .= $this->l('Import is running, click on the button below to reset it.');
            $content .= '<input type="submit" value="Reset Import" name="reset-import" id="reset-import" />';
        } else {
            $content .= $this->l('There is no import process currently running.');
        }
        $content .= '</p>';
        return $content;
    }

    /**
     * Get webservices links cron.
     *
     * @return array The links
     */
    private function _getWebservicesLinks() {
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
        if (_PS_VERSION_ < '1.5') {
            $feed_export_url = (defined('_PS_SHOP_DOMAIN_') ? 'http' . $is_https . '://' . _PS_SHOP_DOMAIN_ : _PS_BASE_URL_) . __PS_BASE_URI__ . 'modules/lengow/webservice/export.php';
            $feed_import_url = (defined('_PS_SHOP_DOMAIN_') ? 'http' . $is_https . '://' . _PS_SHOP_DOMAIN_ : _PS_BASE_URL_) . __PS_BASE_URI__ . 'modules/lengow/webservice/import.php';
        } else {
            $shop_url = new ShopUrl($this->context->shop->id);
            $feed_export_url = 'http' . $is_https . '://' . $shop_url->domain . $shop_url->physical_uri . 'modules/lengow/webservice/export.php';
            $feed_import_url = 'http' . $is_https . '://' . $shop_url->domain . $shop_url->physical_uri . 'modules/lengow/webservice/import.php';
        }
        return array('link_feed_export' => '<div class="lengow-margin"><a href="' . $feed_export_url . '" target="_blank">' . $feed_export_url . '</a></div>',
            'link_feed_import' => '<div class="lengow-margin"><a href="' . $feed_import_url . '" target="_blank">' . $feed_import_url . '</a></div>',
            'url_feed_export' => $feed_export_url,
            'url_feed_import' => $feed_import_url);
    }

    /**
     * Get file export
     *
     * @return array The links
     */
    public function getFileLink($format = null) {
        if (LengowCore::exportInFile()) {
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
            $sep = DIRECTORY_SEPARATOR;
            $context = LengowCore::getContext();
            $format = ($format == null) ? LengowCore::getExportFormat() : $format;
            $id_shop = $context->shop->id;
            $file_exist = file_exists(_PS_MODULE_DIR_ . 'lengow' . $sep . 'export' . $sep . 'flux-' . $id_shop . '.' . $format);
            if (_PS_VERSION_ < '1.5') {
                $file_export_url = (defined('_PS_SHOP_DOMAIN_') ? 'http' . $is_https . '://' . _PS_SHOP_DOMAIN_ : _PS_BASE_URL_) . __PS_BASE_URI__ . '/modules/lengow/export/flux-' . $id_shop . '.' . $format;
            } else {
                $shop_url = new ShopUrl($this->context->shop->id);
                $file_export_url = 'http' . $is_https . '://' . $shop_url->domain . '/modules/lengow/export/flux-' . $id_shop . '.' . $format;
            }
            if ($file_exist) {
                return '<br />' . $this->l('Your export file is available here') . ' : <a href="' . $file_export_url . '" target="_blank">' . $file_export_url . '</a>';
            }
        }
        return '';
    }

    /**
     * Generate tracker on footer.
     *
     * @return varchar The data. 
     */
    public function hookFooter() {
        $tracking_mode = LengowCore::getTrackingMode();

        // SSL
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            self::$_USE_SSL = true;

        if (empty($tracking_mode))
            return '';
        $current_controller = $this->context->controller;

        if ($current_controller instanceof OrderConfirmationController) {
            self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE_CONFIRMATION;
        } else if ($current_controller instanceof ProductController) {
            self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE;
        } else if ($current_controller instanceof OrderController) {
            if ($current_controller->step == -1 || $current_controller->step == 0)
                self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE_CART;
        } else if ($current_controller instanceof IndexController) {
            self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_HOMEPAGE;
        }

        // ID category
        if (!(self::$_ID_CATEGORY = (int) Tools::getValue('id_category'))) {
            if (isset($_SERVER['HTTP_REFERER']) && preg_match('!^(.*)\/([0-9]+)\-(.*[^\.])|(.*)id_category=([0-9]+)(.*)$!', $_SERVER['HTTP_REFERER'], $regs) && !strstr($_SERVER['HTTP_REFERER'], '.html')) {
                if (isset($regs[2]) && is_numeric($regs[2]))
                    self::$_ID_CATEGORY = (int) ($regs[2]);
                elseif (isset($regs[5]) && is_numeric($regs[5]))
                    self::$_ID_CATEGORY = (int) $regs[5];
            } elseif ($id_product = (int) Tools::getValue('id_product')) {
                $product = new Product($id_product);
                self::$_ID_CATEGORY = $product->id_category_default;
            }
            if (self::$_ID_CATEGORY == 0)
                self::$_ID_CATEGORY = '';
        } else {
            self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE_LIST;
        }

        // Basket
        if(self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CART ||
            self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_PAYMENT) {
            self::$_ORDER_TOTAL = $this->context->cart->getOrderTotal();
        }

        // Cart
        if(self::$_CURRENT_PAGE_TYPE != self::LENGOW_TRACK_PAGE_CONFIRMATION) {
            $ids_product = array();
            $cart = $this->context->cart;
            $cart_products = $cart->getProducts();
            if (count($cart_products) > 0) {
                $i = 1;
                foreach ($cart_products as $p) {
                    switch (Configuration::get('LENGOW_TRACKING_ID')) {
                        case 'upc':
                            $id_product = $p['upc'];
                            break;
                        case 'ean':
                            $id_product = $p['ean13'];
                            break;
                        case 'ref':
                            $id_product = $p['reference'];
                            break;
                        default:
                            if ($p['product_attribute_id'])
                                $id_product = $p['product_id'] . '_' . $p['product_attribute_id'];
                            else
                                $id_product = $p['product_id'];
                            break;
                    }
                    $products_cart[] = 'i' . $i . '=' . $id_product . '&p' . $i . '=' . $p['price_wt'] . '&q' . $i . '=' . $p['quantity'];
                    $i++;
                }
                self::$_IDS_PRODUCTS_CART = implode('&', $products_cart);
            }
        }

        // Product IDS
        if(self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_LIST || self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE || self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CART) {
            $array_products = array();
            $products = Context::getContext()->smarty->tpl_vars['products']->value;
            if(!empty($products)) {
                foreach($products as $p) {
                    switch (Configuration::get('LENGOW_TRACKING_ID')) {
                        case 'upc':
                            $id_product = $p['upc'];
                            break;
                        case 'ean':
                            $id_product = $p['ean13'];
                            break;
                        case 'ref':
                            $id_product = $p['reference'];
                            break;
                        default:
                            if ($p['product_attribute_id'])
                                $id_product = $p['product_id'] . '_' . $p['product_attribute_id'];
                            else
                                $id_product = $p['product_id'];
                            break;
                    }
                    $array_products[] = $id_product;
                }
            } else {
                $p = Context::getContext()->smarty->tpl_vars['product']->value;
                if($p instanceof Product) {
                    switch (Configuration::get('LENGOW_TRACKING_ID')) {
                        case 'upc':
                            $id_product =  $p->upc;
                            break;
                        case 'ean':
                            $id_product =  $p->ean13;
                            break;
                        case 'ref':
                            $id_product =  $p->reference;
                            break;
                        default:
                            if ($p->product_attribute_id)
                                $id_product =  $p->product_id . '_' .  $p->product_attribute_id;
                            else
                                $id_product =  $p->product_id;
                            break;
                    }
                    $array_products[] = $id_product;
                }
            }
            self::$_IDS_PRODUCTS = implode($array_products,'|');
        }

        // Generate tracker
        if ($tracking_mode == 'simpletag') {
            if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CONFIRMATION) {
                $this->context->smarty->assign(
                        array(
                            'page_type' => self::$_CURRENT_PAGE_TYPE,
                            'order_total' => self::$_ORDER_TOTAL,
                            'id_order' => self::$_ID_ORDER,
                            'ids_products' => self::$_IDS_PRODUCTS_CART,
                            'mode_payment' => self::$_ID_ORDER,
                            'id_customer' => LengowCore::getIdCustomer(),
                            'id_group' => LengowCore::getGroupCustomer(),
                        )
                );
                return $this->display(__FILE__, 'views/templates/tagpage.tpl');
            }
        } else if ($tracking_mode == 'tagcapsule') {
            $this->context->smarty->assign(
                    array(
                        'page_type' => self::$_CURRENT_PAGE_TYPE,
                        'order_total' => self::$_ORDER_TOTAL,
                        'id_order' => self::$_ID_ORDER,
                        'ids_products' => self::$_IDS_PRODUCTS,
                        'ids_products_cart' => self::$_IDS_PRODUCTS_CART,
                        'use_ssl' => self::$_USE_SSL ? 'true' : 'false',
                        'id_category' => self::$_ID_CATEGORY,
                        'id_customer' => LengowCore::getIdCustomer(),
                        'id_group' => LengowCore::getGroupCustomer(),
                    )
            );
            return $this->display(__FILE__, 'views/templates/tagcapsule.tpl');
        }
        return '';
    }
    
    /**
     * Hook before an status' update to synchronize status with lengow.
     *
     * @param array $args Arguments of hook
     */
    public function hookUpdateOrderStatus($args) {
        // Not send state if we are on lengow import module
        if (LengowCore::isSendState()) {
            $id_order = $args['id_order'];
            $lengow_order = new LengowOrder($id_order);
            if ($lengow_order->module == 'LengowPayment' && $lengow_order->lengow_id_order != '') {
                LengowCore::disableMail();
            }
        }
    }

    /**
     * Hook after an status' update to synchronize status with lengow.
     *
     * @param array $args Arguments of hook
     */
    public function hookPostUpdateOrderStatus($args) {
        // Not send state if we are on lengow import module
        if (LengowCore::isSendState()) {
            $new_order_state = $args['newOrderStatus'];
            $id_order_state = $new_order_state->id;
            $id_order = $args['id_order'];
            $lengow_order = new LengowOrder($id_order);
            if ($lengow_order->module == 'LengowPayment') {
                $marketplace = LengowCore::getMarketplaceSingleton((string) $lengow_order->lengow_marketplace);
                if ($lengow_order->lengow_id_order != '' && $marketplace->isLoaded()) {
                    // Call Lengow API WSDL to send shipped state order
                    if ($id_order_state == LengowCore::getOrderState('shipped')) {
                        $marketplace->wsdl('shipped', $lengow_order->lengow_id_flux, $lengow_order->lengow_id_order, $args);
                    }
                    // Call Lengow API WSDL to send refuse state order
                    if ($id_order_state == LengowCore::getOrderState('cancel')) {
                        $marketplace->wsdl('refuse', $lengow_order->lengow_id_flux, $lengow_order->lengow_id_order, $args);
                    }
                    LengowCore::enableMail();
                }
            }
        }
    }

    /**
     * Hook on order confirmation page to init order's product list.
     *
     * @param array $args Arguments of hook
     */
    public function hookOrderConfirmation($args) {
        self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE_CONFIRMATION;
        self::$_ID_ORDER = $args['objOrder']->id;
        self::$_ORDER_TOTAL = $args['total_to_pay'];
        $ids_product = array();
        $products_list = $args['objOrder']->getProducts();
        $i = 0;
        foreach ($products_list as $p) {
            $i++;
            switch (Configuration::get('LENGOW_TRACKING_ID')) {
                case 'upc':
                    $id_product = $p['upc'];
                    break;
                case 'ean':
                    $id_product = $p['ean13'];
                    break;
                case 'ref':
                    $id_product = $p['reference'];
                    break;
                default:
                    if ($p['product_attribute_id'])
                        $id_product = $p['product_id'] . '_' . $p['product_attribute_id'];
                    else
                        $id_product = $p['product_id'];
                    break;
            }
            // Ids Product
            $ids_products[] = $id_product;

            // Basket Product
            $products_cart[] .= 'i' . $i . '=' . $id_product . '&p' . $i . '=' . Tools::ps_round($p['unit_price_tax_incl'], 2) . '&q' . $i . '=' . $p['product_quantity'];
        }
        self::$_IDS_PRODUCTS_CART = implode('&', $products_cart);
        self::$_IDS_PRODUCTS = implode('|', $ids_products);
    }

    /**
     * Hook on Payment page.
     *
     * @param array $args Arguments of hook
     */
    public function hookPaymentTop($args) {
        self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE;
    }

    /**
     * Hook after add new product.
     *
     * @param array $args Arguments of hook
     */
    public function hookAddProduct($params) {
        if (!isset($params['product']->id))
            return false;
        $id_product = $params['product']->id;
        if ((int) $id_product < 1)
            return false;
        if (LengowCore::isAutoExport()) {
            LengowProduct::publish($id_product);
        }
    }

    /**
     * Hook on header dashboard.
     *
     * @param array $args Arguments of hook
     */
    public function hookActionAdminControllerSetMedia($args) {
        if (Tools::getValue('controller') == 'AdminModules' && Tools::getValue('configure') == 'lengow') {
            $this->context->controller->addJs($this->_path . 'views/js/admin.js');
            $this->context->controller->addCss($this->_path . 'views/css/admin.css');
        }
        if(Tools::getValue('controller') == 'AdminOrders') {
            $this->context->controller->addJs($this->_path . 'views/js/admin.js');
        }
        if (strtolower(Tools::getValue('controller')) == 'adminhome' || Tools::getValue('controller') == 'AdminLengow') {
            $this->context->controller->addCss($this->_path . 'views/css/admin.css');
            $this->context->controller->addJs($this->_path . 'views/js/chart.min.js');
        }
    }

    /**
     * Hook on dashboard.
     *
     * @param array $args Arguments of hook
     */
    public function hookDisplayAdminHomeStatistics($args) {
        $this->context->smarty->assign(
                array(
                    'token' => LengowCore::getTokenCustomer(),
                    'id_customer' => LengowCore::getIdCustomer(),
                    'id_group' => LengowCore::getGroupCustomer(),
                )
        );
        return $this->display(__FILE__, 'views/templates/admin/dashboard/stats.tpl');
    }

    /**
     * Hook on admin page's order.
     *
     * @param array $args Arguments of hook
     */
    public function hookAdminOrder($args) {
        $order = new LengowOrder($args['id_order']);
        if (!empty($order->lengow_id_order)) {
            if(Tools::getValue('action') == 'synchronize') {
                $lengow_connector = new LengowConnector((integer) LengowCore::getIdCustomer(), LengowCore::getTokenCustomer());
                $args = array(
                        'idClient' => LengowCore::getIdCustomer() ,
                        'idFlux' => $order->lengow_id_flux,
                        'idGroup' => LengowCore::getGroupCustomer(),
                        'idCommandeMP' => $order->lengow_id_order,
                        'idCommandePresta' => $order->id);
                $lengow_connector->api('updatePrestaInternalOrderId', $args);
            }

            if (_PS_VERSION_ < '1.5') {
                $action_reimport = 'index.php?tab=AdminOrders&id_order=' . $order->id . '&vieworder&action=reImportOrder&token=' . Tools::getAdminTokenLite('AdminOrders') . '';
                $action_reimport = $this->_path . 'v14/ajax.php?';
                $action_synchronize = 'index.php?tab=AdminOrders&id_order=' . $order->id . '&vieworder&action=synchronize&token=' . Tools::getAdminTokenLite('AdminOrders');
                $add_script = true;
            } else {
                $action_reimport = 'index.php?controller=AdminLengow&action=reimportOrder&ajax&token=' . Tools::getAdminTokenLite('AdminLengow');
                $action_synchronize = 'index.php?controller=AdminOrders&id_order=' . $order->id . '&vieworder&action=synchronize&token=' . Tools::getAdminTokenLite('AdminOrders');
                $add_script = false;
            }
            $lengow_order_extra = Tools::jsonDecode($order->lengow_extra);

            $template_data = array(
                                'id_order_lengow' => $order->lengow_id_order,
                                'id_flux' => $order->lengow_id_flux,
                                'marketplace' => $order->lengow_marketplace,
                                'total_paid' => $order->lengow_total_paid,
                                'carrier' => $order->lengow_carrier,
                                'message' => $order->lengow_message,
                                'action_synchronize' => $action_synchronize,
                                'action_reimport' => $action_reimport,
                                'order_id' => $args['id_order'],
                                'add_script' => $add_script,
                                'url_script' => $this->_path . 'views/js/admin.js',
                                'version' => _PS_VERSION_
                             );

            if(!is_object($lengow_order_extra->tracking_informations->tracking_method))
                $template_data['tracking_method'] = $lengow_order_extra->tracking_informations->tracking_method;

            if(!is_object($lengow_order_extra->tracking_informations->tracking_carrier))
                $template_data['tracking_carrier'] = $lengow_order_extra->tracking_informations->tracking_carrier;

            $this->context->smarty->assign($template_data);
            
            return $this->display(__FILE__, 'views/templates/admin/order/info.tpl');
        } else {
            return '';
        }
    }

    /**
     * Hook after a new order.
     *
     * @param array $args Arguments of hook
     */
    public function hookNewOrder($args) {
        /*
          if(Configuration::get('LENGOW_FORCE_PRICE') && LengowImport::$import_start == true) {
          $lengow_order = new LengowOrder($args['order']->id);
          $current_order = LengowCart::$current_order;
          // Update order if real amout paid is different from Prestashop calc total pay
          if($lengow_order->total_paid != $current_order['total_pay']) {
          $lengow_order->rebuildOrder($current_order['products'], $current_order['total_pay'], $current_order['shipping_price'], $current_order['wrapping_price']);
          }
          $args['order'] = $lengow_order;
          $marketplace = LengowCore::getMarketplaceSingleton((string) $lengow_order->lengow_marketplace);
          $marketplace->wsdl('link', $lengow_order->lengow_id_flux, $lengow_order->lengow_id_order, array('id_order_internal' => $lengow_order->id ,
          'id_client' => LengowCore::getIdCustomer()));
          LengowCore::log('Order ' . $lengow_order->lengow_id_order . ' : sync new ID order on Lengow (ORDER ' . $lengow_order->id . ')');
          }
         */
    }

    /**
     * Display tabs on Lengow's configutation
     *
     * @return string The html tabs
     */
    private function _displayTabs() {
        // Case 1.5
        if (LengowCore::compareVersion()) {
            $link = new Link();
            $lengow_admin_url = $link->getAdminLink('AdminModules');
            $lengow_admin_url .= '&configure=' . $this->name . '&tab_module=' . $this->name . '&module_name=' . $this->name . '';
            $selected_tab = $this->_selectedTab();
            $html = '<div style="clear:both"></div><ul id="lengow-tab">';
            $html .= '<li id="lengow-parameters" class="lengow-tab-list ' . ($selected_tab == 'parameters' ? 'selected' : '') . '"><a href="' . $lengow_admin_url . '&lengow_tab=configuration"><span>' . $this->l('Parameters') . '</span></a></li>';
            $html .= '<li id="lengow-categories" class="lengow-tab-list ' . ($selected_tab == 'products' ? 'selected' : '') . '"><a href="' . $lengow_admin_url . '&lengow_tab=products"><span>' . $this->l('Products & Categories') . '</span></a></li>';
            $html .= '<li id="lengow-informations" class="lengow-tab-list ' . ($selected_tab == 'informations' ? 'selected' : '') . '"><a href="' . $lengow_admin_url . '&lengow_tab=informations"><span>' . $this->l('Informations') . '</span></a></li>';
            $html .= '</ul>';
            return $html;
        } else { // Case 1.4
            return '';
        }
    }

    /**
     * Get the selected tab of Lengow's configuration
     *
     * @return string The selecte tab
     */
    private function _selectedTab() {
        global $cookie;
        return (($selected_tab = Tools::getValue('lengow_tab')) ? $selected_tab : ($cookie->id_lang ? strtolower(Language::getIsoById($cookie->id_lang)) : strtolower(Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')))));
    }

    /**
     * Get products.
     *
     * @return string The products list
     */
    private function _getProductsAdmin() {
        $d_lang = (int) $this->context->cookie->id_lang;
        $id_shop = (int) $this->context->shop->id;
        $d_shop_group = (int) $this->context->shop->id_shop_group;
        $categories = Category::getCategories($d_lang);
        $html = '<fieldset>';
        $html .= '<legend>' . $this->l('Select your categories and products to export') . '</legend>';
        $html .= '<div class="margin-form">' . $this->renderCategoryTree(null, $selected_cat = array()) . '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    /**
     * Get information admin's form
     *
     * @return string The information form
     */
    private function _getInformationAdmin() {
        $export_lengow_url = 'http://' . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'modules/' . $this->name . '/webservice/export.php';
        $export_lengow_url_full = $export_lengow_url . '?mode=full';
        $html = '<h2>' . $this->l('Lengow: synchronize yout catalog') . ' (v' . $this->version . ')</h2>
                 <fieldset>
                     <legend>' . $this->l('Informations  :') . '</legend>' . $this->l("Lengow is a SaaS solution to enable e-shopping optimize its product catalogs to price comparison, affiliation but also marketplaces and sites of Cashback.") . '<br />   
                     <br />' . $this->l('The principle is that the solution recovers the merchant\'s product catalog, configure, optimize and track information campaigns to restore market for e-trading statistics in the form of dashboards and charts.') . ' 
                     <br />' . $this->l('This process allows e-merchants to optimize their flow and their cost of acquisition on each channel.') . '
                     <br clear="all" />
                     <br clear="all" />
                     <a href="http://www.lengow.com" target="_blank"><div style="background-color:#2e85c9;text-align:center;padding:10px;border:1px solid #DDD"><img src="http://www.lengow.fr/img/slide_all_new.png" alt="' . $this->l('Lengow Solution') . '" border="0" /></div></a>
                 </fieldset>
                 <br clear="all" />
                 <fieldset>
                     <legend>' . $this->l('URL of your Product Catalog :') . '</legend>
                     <a href="' . $export_lengow_url . '" target="_blank" style="font-family:Courier">' . $export_lengow_url . '</a>
                 </fieldset>
                 <br clear="all" />
                 <fieldset>
                     <legend>' . $this->l('URL of your Product Catalog Full :') . '</legend>
                     <a href="' . $export_lengow_url_full . '" target="_blank" style="font-family:Courier">' . $export_lengow_url_full . '</a>
                 </fieldset>
                 <br clear="all" />
                 <fieldset>
                     <legend>' . $this->l('URL optional(s) parameter(s):') . '</legend>
                     <b>CUR</b>: ' . $this->l('Use Prestashop currency conversion tool to convert your products prices using isocode.') . '<br/><br/>
                     ' . $this->l('Example: convert your prices in $ (isocode: USD)') . ': <br/>
                     <a href="' . $export_lengow_url_full . '&cur=USD" target="_blank" style="font-family:Courier">' . $export_lengow_url_full . '&cur=USD</a><br/>
                     ' . $this->l('If not set, EUR is used as default value.') . '<br/><br/>
                     <b>lang</b>: ' . $this->l('Use Prestashop translation tool using language iso2 code to translate your products titles and descriptions.') . '<br/><br/>
                     ' . $this->l('Example: translate in Spanish (ES)') . ': <br/>
                     <a href="' . $export_lengow_url_full . '&lang=ES" target="_blank" style="font-family:Courier">' . $export_lengow_url_full . '&lang=ES</a><br/>
                     ' . $this->l('If not set below, FR is used as default value.') . '<br/><br/><br/>
                     ' . $this->l('Both optional parameters can be used') . ': <br/>
                     ' . $this->l('Example: convert your prices in &pound; (GBP) and translate in English') . ': <br/>
                     <a href="' . $export_lengow_url_full . '&lang=EN&cur=GBP" target="_blank" style="font-family:Courier">' . $export_lengow_url_full . '&lang=EN&cur=GBP</a><br/>
                 </fieldset>';
        return $html;
    }

    /**
     * Add admin Tab (Controller) 
     *
     * @return boolean Result of add tab on database.
     */
    private function _createTab() {
        foreach(self::$_TABS as $name => $value) {
            if (_PS_VERSION_ < '1.5')
                $tabName = $value[1];
            else
                $tabName = $value[0];

            if(Tab::getIdFromClassName($tabName) !== false)
                continue;

            $tab = new Tab();
            if (_PS_VERSION_ < '1.5') {
                $tab->class_name = $value[1];
                $tab->position = 10;
                $tab->id_parent = 1;
            } else {
                $tab->class_name = $value[0];
                $tab->position = 1;
                $tab->id_parent = 9;
            }

            $tab->module = $this->name;
            $tab->name[Configuration::get('PS_LANG_DEFAULT')] = $this->l($name);

            $tab->add();
        }

        return true;
    }

    /**
     * Remove admin tab
     * 
     * @return boolean Result of tab uninstallation
     */
    private function _uninstallTab() {
        foreach(self::$_TABS as $name => $value) {
            if (_PS_VERSION_ < '1.5') {
                $tabName = $value[1];
            } else {
                $tabName = $value[0];
            }

            if(_PS_VERSION_ >= '1.5') {
                $tab = Tab::getInstanceFromClassName($tabName);
            } else {
                $tab_id = Tab::getIdFromClassName($tabName);
                $tab = new Tab($tab_id);
            }
            
            if ($tab->id != 0) {
                $tab->delete();
            }
        }
        return false;
    }

    /**
     * Update Cron with module Crontab
     *
     * @param varchar The delay in minutes
     *      
     * @return boolean
     */
    public static function updateCron($delay) {
        $module_cron = Module::getInstanceByName('cron');
        $module_lengow = Module::getInstanceByName('lengow');
        if (Validate::isLoadedObject($module_cron)) {
            if ($delay > 1 && $delay < 60) {
                $delays = '';
                for ($i = 0; $i < 60; $i = $i + $delay) {
                    $delays .= $i . ',';
                }
                $delays = rtrim($delays, ',');
                $module_cron->deleteCron($module_lengow->id, 'cronImport');
                $module_cron->addCron($module_lengow->id, 'cronImport', $delays . ' * * * *');
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Get the cron of import orders from Lengow
     *
     * @return boolean Result of add tab on database.
     */
    public static function getCron() {
        $module_cron = Module::getInstanceByName('cron');
        $module_lengow = Module::getInstanceByName('lengow');
        if (Validate::isLoadedObject($module_cron) and $module_cron->cronExists($module_lengow->id, 'cronImport') != false)
            return true;
        return false;
    }

    /**
     * Get the cron of import orders from Lengow
     *
     * @return boolean Result of add tab on database.
     */
    public function cronImport() {
        @set_time_limit(0);
        $import = new LengowImport();
        $import->force_log_output = false;
        $date_to = date('Y-m-d');
        $days = (integer) LengowCore::getCountDaysToImport();
        $date_from = date('Y-m-d', strtotime(date('Y-m-d') . ' -' . $days . 'days'));
        LengowCore::log('Cron import');
        $result = $import->exec('commands', array('dateFrom' => $date_from,
          'dateTo' => $date_to));
    }

    /**
     * 
     * @retun string HTML Content of checklist
     */
    private function _getCheckList() {
        return LengowCheck::getHtmlCheckList();
    }

    private function _getLogFiles() {
        $out = '';
        if($handle = opendir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs')) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename != "." && $filename != "..") {
                    $files[] = '<a target="_blank" href="' . _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules' . DS . 'lengow' . DS . 'logs' . DS . $filename . '">' .$filename . '</a>';
                }
            }
            sort($files);
            $files = array_reverse($files);
            $out .= join('<br />', array_slice($files, 0, 10));
            closedir($handle);
        } else {
            $out .= "Dossier introuvable";
        }
        return $out;
    }

    /**
     * Check if override exists, install it if no
     * 
     * @return boolean
     */
    private function _installOverride() {
        $folder_override = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'override';
        $folder_temp = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install';

        if (file_exists($folder_override)) {
            // Copy file
            if (file_exists($folder_temp) && $handle = opendir($folder_temp)) {
                while (false !== ($entry = readdir($handle))) {
                    $ext = pathinfo($entry, PATHINFO_EXTENSION);
                    if ($ext === 'php') {
                        $temp_file = $folder_temp . DIRECTORY_SEPARATOR . $entry;
                        $override_file = $folder_override . DIRECTORY_SEPARATOR . $entry;
                        if (!file_exists($override_file))
                            @rename($temp_file, $override_file);
                        @unlink($temp_file);
                    }
                }
                @closedir($handle);
            }
        } else {
            return @rename($folder_temp, $folder_override);
        }
        
        return true;
    }

}
