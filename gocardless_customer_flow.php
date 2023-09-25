<?php

// This is where GoCardless redirects to after customer has authorised payment for a client

require_once 'inc/init.app.php';

$ok = true;
$pa = null;

try {
	// Verify logged in user and client
	$customer_account = $_SESSION['gocardless_customer_account'];
	list($owner_type, $owner_id, $customer_type, $customer_id) = explode('-', $customer_account);
	$pg_id = $_SESSION['gocardless_payment_gateway'];
	$from_login = $_SESSION['gocardless_from_login'];
	$contract_id = $_SESSION['gocardless_contract_id'];

	if ($owner_type && $owner_id && $customer_type && $customer_id && isset($_GET['redirect_flow_id'])) {
		$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);
		$pg = new PaymentGateway($pg_id);
		if($pg->is_valid() && $owner_type == $pg->record['owner_type'] && $owner_id == $pg->record['owner_id']) {
			$response = $pg->complete_customer_mandate($_GET['redirect_flow_id']);
			if($response) {
				$gc_mandate_id = $response->links->mandate;
				$gc_customer_id = $response->links->customer;

				$mandate = PaymentGoCardlessMandate::request($pg->id, $customer_type, $customer_id);
				if($mandate) {
					$mandate->authorise($gc_mandate_id, $gc_customer_id);

					if($contract_id) {
						// Mandate form was popped from a special auth screen, which portal customers use when they first log into the system.
						// When they log in the first time and authorise the DD mandate, that's when their contract starts.
						// This is the only part of the system that automatically enabled an unconfirmed contract.

						$contract = App::select('contract', $contract_id);
						if($contract && $contract['status'] === 'unconfirmed') {
							$start_date = date('Y-m-d');
							$end_date = null;
							if($contract['term'] && $contract['term_units']) {
								$end_date = date('Y-m-d', strtotime("+$contract[term] $contract[term_units]", strtotime($start_date)));
								$end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
							}

							App::update('contract', $contract_id, [
								'status' => 'active',
								'start_date' => $start_date,
								'end_date' => $end_date
							]);
						}
					}
				}
			}
		}
	}
} catch(Exception $ex) {
	$ok = false;
}

// Redirect to accounts page

if($pa) {
	if($from_login) {
		header('Location: '.APP_URL.'/v3/auth/login');
	} else {
		header('Location: '.APP_URL.'/v3/account/'.$pa->id.'/'.$pa->record['security_token']);
	}
} else {
	header('Location: '.APP_URL.'/v3/account/error');
}
