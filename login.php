<?php

require_once 'inc/init.app.php';

$page_title = "Login";
if (isset($_GET["logout"])) {
	User::logout();
} else if(isset($_COOKIE[AUTH_COOKIE_NAME])) {
	$token_id = $_COOKIE[AUTH_COOKIE_NAME];
	if($token_id) do_login($token_id);
}

if (isset($_SESSION[SESSION_NAME_USER_ID])) {
	$user = User::check_login_session(false);
	if ($user) {
		if (isset($_GET["r"])) {
			App::redirect($_GET["r"]);
		} else {
			$user->launch_home_page();
		}
		exit;
	}
}

function do_login($token_id) {
	$user = false;

	$token = new UserToken($token_id);
	if($token->validate()) {
		$user = $token->get_user();
		if(!$user || !$user->validate()) $user = false;
	}

	if ($user) {
		$user->init_user_session();
		if (isset($_GET["r"]))
			App::redirect($_GET["r"]);
		else {
			$user->launch_home_page();
		}
	}
}

App::redirect(APP_URL.'/v3/auth/login');
exit;
