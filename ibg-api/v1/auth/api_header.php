<?php
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

$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$domain = preg_replace("/[^a-z0-9\.]+/", "", $domain);


include($_SERVER['DOCUMENT_ROOT']."/eticom/lib/class.user.php");
include($_SERVER['DOCUMENT_ROOT']."/eticom/lib/class.app.php");
include($_SERVER['DOCUMENT_ROOT']."/eticom/lib/class.permission.php");
include($_SERVER['DOCUMENT_ROOT']."/eticom/lib/class.mysql.php");
include($_SERVER['DOCUMENT_ROOT']."/eticom/lib/class.module.php");
include($_SERVER['DOCUMENT_ROOT']."/eticom/lib/class.building.php");
$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$domain = preg_replace("/[^a-z0-9\.]+/", "", $domain);
require_once $_SERVER['DOCUMENT_ROOT']."/eticom/config/config_$domain.php";

include "../auth/header_auth.php";
include_once '../auth/db_auth.php';
include "../get_user.php";
include "../auth/GetPermissions.php";
include "../meter_request.php";
include "../device_control.php";
