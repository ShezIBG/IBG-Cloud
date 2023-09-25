<?php

/*
 * Intialises the application and sets the global $user variable if a user is authenticated.
 */

require_once 'init.app.php';

// redirect or HTTP 401
$user = User::check_login_session(isset($redirect_on_error) ? $redirect_on_error : true, isset($is_ajax_request) ? $is_ajax_request : false);
