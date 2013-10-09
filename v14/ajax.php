<?php
@set_time_limit(0);
$sep = DIRECTORY_SEPARATOR;
require_once '..' . $sep . '..' . $sep . '..' . $sep . 'config' . $sep . 'config.inc.php';
require_once '..' . $sep . '..' . $sep . '..' . $sep . 'init.php';
require_once '..' . $sep . 'lengow.php';
require_once _PS_MODULE_DIR_ . 'lengow' . $sep . 'models' . $sep . 'lengow.connector.class.php';

$is_https =  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
$lengow_connector = new LengowConnector((integer) LengowCore::getIdCustomer(), LengowCore::getTokenCustomer());
$params = 'format=' . Tools::getValue('format');
$params .= '&mode=' . Tools::getValue('mode');
$params .= '&all=' . Tools::getValue('all');
$params .= '&shop=' . Tools::getValue('shop');
$params .= '&cur=' . Tools::getValue('cur');
$params .= '&lang=' . Tools::getValue('lang');
$new_flow = (defined('_PS_SHOP_DOMAIN_') ? 'http' . $is_https . '://' . _PS_SHOP_DOMAIN_ : _PS_BASE_URL_) . __PS_BASE_URI__ . 'modules/lengow/webservice/export.php?' . $params; 
$args = array('idClient' => LengowCore::getIdCustomer() ,
       	  	  'idGroup' => LengowCore::getGroupCustomer() , 
        	  'urlFlux' => $new_flow);
$data_flows = get_object_vars(Tools::jsonDecode(Configuration::get('LENGOW_FLOW_DATA')));
if($id_flow = Tools::getValue('idFlow')) {
	$args['idFlux'] = $id_flow;
	$data_flows[$id_flow] = array('format' => Tools::getValue('format') ,
							      'mode' => Tools::getValue('mode') == 'yes' ? 1 : 0,
								  'all' => Tools::getValue('all') == 'yes' ? 1 : 0 ,
								  'currency' => Tools::getValue('cur') ,
								  'shop' => Tools::getValue('shop') ,
								  'language' => Tools::getValue('lang') ,
								 );
	Configuration::updateValue('LENGOW_FLOW_DATA', Tools::jsonEncode($data_flows));
}
if($call = $lengow_connector->api('updateRootFeed', $args)) {
	//Configuration::updateValue('LENGOW_CARRIER_DEFAULT', true);
	echo Tools::jsonEncode(array('return' => true,
								 'flow' => $new_flow));
} else {
	echo Tools::jsonEncode(array('return' => false));
}