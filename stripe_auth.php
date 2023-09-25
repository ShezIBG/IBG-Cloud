<?php

// This is where Stripe redirects to after the payment gateway has been authorised

require_once 'ajax/init.ajax.php';

$url = '';

try {
	if(isset($_GET['state'])) {
		$pg_id = explode('-', App::get('state', '', true), 2)[0];
		$pg = new PaymentGateway($pg_id);
		if($pg->is_valid()) {
			if(!$pg->is_authorised()) $pg->authorise();
			$url = $pg->get_account_url_path();
		}
	}
} catch(Exception $ex) { }

// Whatever happens, redirect back to account admin page (payment gateways tab)
// TODO: Once the whole app is migrated to Angular, swap directly to the v3 address
// header('Location: '.APP_URL.'/v3/settings/'.$url);

header('Location: '.APP_URL.'/admin?path='.urlencode($url));
