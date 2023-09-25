<?php

// configure constants

$directory = realpath(dirname(__FILE__));
$document_root = realpath($_SERVER['DOCUMENT_ROOT']);
$app_path = $document_root;
if (isset($_SERVER['HTTP_HOST'])) $app_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
if (strpos($directory, $document_root)===0) {
	$app_path .= substr($directory, strlen($document_root));
	if(isset($_SERVER['HTTP_HOST'])) $app_url .= str_replace(DIRECTORY_SEPARATOR, '/', substr($directory, strlen($document_root)));
}

defined("APP_PATH") ? null : define("APP_PATH", dirname($app_path));
defined("APP_URL") ? null : define("APP_URL", isset($_SERVER['HTTP_HOST']) ? dirname($app_url) : '');
defined("GOOGLE_MAPS_API_KEY") ? null : define("GOOGLE_MAPS_API_KEY", "AIzaSyDPV1ZO_vmoOWoiBjCrG3V16YFSCV9o9uk");

$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$domain = preg_replace("/[^a-z0-9\.]+/", "", $domain);

//require library files
require_once 'smartresize/smartresize.php';
require_once __DIR__.'/../config/config_'.$domain.'.php';
require_once 'class.app.php';

require_once 'smartui/class.smartutil.php';
require_once 'smartui/class.smartui.php';

// smart UI pluginss
require_once 'smartui/class.smartui-widget.php';
require_once 'smartui/class.smartui-datatable.php';
require_once 'smartui/class.smartui-button.php';
require_once 'smartui/class.smartui-tab.php';
require_once 'smartui/class.smartui-accordion.php';
require_once 'smartui/class.smartui-carousel.php';
require_once 'smartui/class.smartui-smartform.php';
require_once 'smartui/class.smartui-info.php';
require_once 'smartui/class.smartui-nav.php';
require_once 'smartui/class.smartui-wizard.php';

// register our UI plugins
SmartUI::register('widget', 'Widget');
SmartUI::register('datatable', 'DataTable');
SmartUI::register('button', 'Button');
SmartUI::register('tab', 'Tab');
SmartUI::register('accordion', 'Accordion');
SmartUI::register('carousel', 'Carousel');
SmartUI::register('smartform', 'SmartForm');
SmartUI::register('profileinfo', 'ProfileInfo');
SmartUI::register('info', 'Info');
SmartUI::register('nav', 'Nav');
SmartUI::register('wizard', 'Wizard');
SmartUI::$debug = false;
SmartUI::$icon_source = 'eticon';

require_once 'class.access.php';
require_once 'class.building.php';
require_once 'class.climate.php';
require_once 'class.control.php';
require_once 'class.customer.php';
require_once 'class.dashboard.php';
require_once 'class.date.php';
require_once 'class.emlight.php';
require_once 'class.eticom.php';
require_once 'class.fileupload.php';
require_once 'class.isp.php';
require_once 'class.lease.php';
require_once 'class.lighting.php';
require_once 'class.mailer.php';
require_once 'class.meter.php';
require_once 'class.meterperiod.php';
require_once 'class.meterreading.php';
require_once 'class.module.php';
require_once 'class.mysql.php';
require_once 'class.paymentgateway.php';
require_once 'class.permission.php';
require_once 'class.product.php';
//require_once 'class.pax_users.php';
require_once 'class.project.php';
require_once 'class.report.php';
require_once 'class.relay.php';
require_once 'class.stock.php';
require_once 'class.tenant.php';
require_once 'class.tenantbill.php';
require_once 'class.ui.php';
require_once 'class.user.php';
require_once 'class.user_access.php';
require_once 'class.usercontent.php';
require_once 'class.vo.php';
require_once 'class.weatherservice.php';
require_once 'class.surveillance.php';
require_once 'class.multisense.php';
require_once 'class.overdue.php';
require_once 'class.xero.php';
// Third party libraries

// Load Composer packages
//required packages: GoCardless, Oauth
require_once APP_PATH.'/vendor/autoload.php';
