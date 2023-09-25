<?php

require_once '../lib/include.php';
require_once 'shared-api.php';

$redirect_on_error = false;

if(isset($_SERVER['HTTP_REFERER'])) {
	preg_match('~^(.*?//.*?)/.*~', $_SERVER['HTTP_REFERER'], $matches);

	if(isset($matches[1])) {
		header('Access-Control-Allow-Origin: '.$matches[1]);
		header('Access-Control-Allow-Methods: GET,POST');
		header('Access-Control-Allow-Headers: Content-Type');
		header('Access-Control-Allow-Credentials: true');
	}
} else if(isset($_SERVER['HTTP_ORIGIN'])) {
	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Allow-Methods: GET,POST');
	header('Access-Control-Allow-Headers: Content-Type');
	header('Access-Control-Allow-Credentials: true');
}

// Handle browser preflight check, return 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') return;

if(!DEV_MODE) {
	require_once '../ajax/init.ajax.php';

	if(!isset($user) || !$user) {
		if(isset($_COOKIE[AUTH_COOKIE_NAME])) {
			$token_id = $_COOKIE[AUTH_COOKIE_NAME];
			$token = new UserToken($token_id);
			if($token->validate()) {
				$user = $token->get_user();
				if($user && $user->validate()) {
					$user->init_user_session();
				} else {
					$user = null;
				}
			}
		}
	}
} else {
	$auto_auth = true;

	if($auto_auth) {
		// Do custom initialisation instead of init.ajax.php to avoid standard user authentication
		require_once '../inc/init.app.php';

		if(!isset($user) || !$user) {
			$user = new User(64);
			// $user = new User(89);
		}
	} else {
		// Normal user auth
		require_once '../ajax/init.ajax.php';
	}
}

// Disable SQL class cleaning / HTML encoding for all API calls
MySQL::$clean = false;

$api_modules = [
	'account',
	'auth',
	'billing',
	'climate',
	'configurator',
	'content',
	'control',
	'emergency',
	'general',
	'isp',
	'lighting',
	'mobile',
	'monitor',
	'products',
	'public',
	'relay',
	'sales',
	'settings',
	'smoothpower',
	'stock',
	'survey'
];

list($module, $func) = App::get(['api_module', 'api_func'], '');

// Always return JSON response
if($module !== 'content') App::set_content_type();

if(in_array($module, $api_modules)) {
	require_once 'api-'.$module.'.php';
} else {
	http_response_code(400);
	echo $module === 'content' ? '' : App::encode_result('FAIL', 'Invalid request.', null);
	return;
}

// Only the account and public modules can be used without user authentication
// Account module is authenticated via a security token in the URL.
if($module !== 'account' && $module !== 'public') {
	if (!$user) {
		// If there isn't a user session then don't call any functions
		http_response_code(401);
		echo $module === 'content' ? '' : App::encode_result('FAIL', 'No user is logged in.', null);
		return;
	}
}

try {
	$api = new API();
	if (method_exists($api, $func)) {
		echo call_user_func([$api, $func]) ?: '';
	} else {
		http_response_code(400);
		echo $module === 'content' ? '' : App::encode_result('FAIL', 'Invalid request.', null);
	}
} catch(AccessDeniedException $ex) {
	http_response_code(403);
	echo $module === 'content' ? '' : App::encode_result('FAIL', 'Access denied.', null);
} catch(Exception $ex) {
	http_response_code(500);
	error_log("API Caught $ex");
	echo $module === 'content' ? '' : App::encode_result('FAIL', 'An exception has occurred.', null);
}

return;
