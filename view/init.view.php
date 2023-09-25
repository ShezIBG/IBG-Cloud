<?php
	$directory = realpath(dirname(__FILE__));
	$document_root = realpath($_SERVER['DOCUMENT_ROOT']);
	$app_path = $document_root;
	if(strpos($directory, $document_root)===0) {
		$app_path .= substr($directory, strlen($document_root));
	}

	$redirect_on_error = false;
	$is_ajax_request = true;
	require_once dirname($app_path).'/inc/init.user.php';
