<?php

require_once '../lib/include.php';

// Do custom initialisation instead of init.ajax.php to avoid standard user authentication
require_once '../inc/init.app.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	// Handle browser preflight check
	// Return 200 OK
	return;
}

if (!defined("INTERNAL_SECRET")) return;
if (App::get('key', '') !== INTERNAL_SECRET) return;

$report_type = App::get('type', '');
$print_auth = true;

if(!$print_auth) return;

require_once "print_$report_type.php";
