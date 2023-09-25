<?php

/*
 * Initialises the application, includes all source files needed and starts the PHP session. Doesn't authenticate user.
 */

if(session_id() == '') session_start();

$directory = realpath(dirname(__FILE__));
$document_root = realpath($_SERVER['DOCUMENT_ROOT']);
$app_path = $document_root;
if(strpos($directory, $document_root) === 0) {
	$app_path .= substr($directory, strlen($document_root));
}

require_once dirname($app_path).'/lib/include.php';

$ui = new SmartUI();
$is_mobile = App::is_mobile();
