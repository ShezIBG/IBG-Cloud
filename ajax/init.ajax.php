<?php

/*
 * Initialises the application and start the user's session. Also initialises the global $user variable if a user
 * is authenticated.
 */

$directory = realpath(dirname(__FILE__)); //get actual directory of the current directory
$document_root = realpath($_SERVER['DOCUMENT_ROOT']); //get actual directory of the apache configured root
$app_path = $document_root;
if(strpos($directory, $document_root)===0) { //check the two directories for similiaraties
	$app_path .= substr($directory, strlen($document_root)); //remove the extra from this directory.
}

//include the initialization file
require_once dirname($app_path).'/inc/init.user.php';
