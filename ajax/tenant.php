<?php

/*
 * This file handles request from the tenant mobile app developed for The Colony. It was never really used.
 *
 * eticomcloud.com/ajax/tenant/function points here, which will call the named function in this file.
 */

// Allow access from Cordova app
header("Access-Control-Allow-Origin: *");

// Do custom initialisation instead of init.ajax.php to avoid standard user authentication
require_once '../inc/init.app.php';

// Each function in this file will return JSON data on success/failure
App::set_content_type();

$tenant_id = isset($_SESSION['logged_in_tenant']) ? $_SESSION['logged_in_tenant'] : 0;
$tenant = null;

$tenant_func = key($GLOBALS["_GET"]);
if($tenant_func != 'authenticate') {
	// Validate logged in tenant
	if($tenant_id) $tenant = new Tenant($tenant_id);

	if(!$tenant || !$tenant->validate()) {
		http_response_code(403);
		echo App::encode_result('FAIL', 'Unauthorized', []);
		return;
	}
}

echo function_exists($tenant_func) ? call_user_func($tenant_func, $tenant) : App::encode_result('FAIL', 'Invalid parameters.', []);

function authenticate($tenant) {
	// Clear session login variable
	unset($_SESSION['logged_in_tenant']);
	$tenant_id = 0;
	$tenant = null;

	$email = App::post('email', '', true);
	$password = $_POST['password'];
	$tenants = App::sql()->query("SELECT * FROM tenant WHERE active = 1 AND email_address = '$email';");
	if($tenants) {
		foreach($tenants as $t) {
			if(password_verify($password, $t->password)) {
				$tenant = new Tenant($t->id);
				if($tenant->validate()) {
					$tenant_id = $t->id;
					$_SESSION['logged_in_tenant'] = $tenant_id;
					break;
				} else {
					$tenant = null;
				}
			}
		}
	}

	return App::encode_result($tenant ? 'OK' : 'FAIL', $tenant ? 'Success' : 'Invalid email address or password.', []);
}

// Returns tenant's details
// Lists all areas with currently active leases for the tenant
function get_my_details($tenant) {
	$result = [
		'name'             => $tenant->info->name,
		'company'          => $tenant->info->company,
		'email_address'    => $tenant->info->email_address,
		'telephone_number' => $tenant->info->telephone_number,
		'mobile_number'    => $tenant->info->mobile_number,
		'areas'            => array_map(function($lease) {

			$rental_cost = $lease->info->rental_cost_pounds_ex_vat_per_year ?: 0;
			$service_charge = $lease->info->service_charge_pounds_ex_vat_per_year ?: 0;
			$bill_frequency = $lease->info->bill_frequency;

			switch($bill_frequency) {
				case Lease::BILL_MONTH:
					$rental_cost /= 12;
					$service_charge /= 12;
					break;

				case Lease::BILL_QUARTER_ENG:
				case Lease::BILL_QUARTER_SCOT:
				case Lease::BILL_QUARTER_LA:
					$rental_cost /= 4;
					$service_charge /= 4;
					break;
			}

			return [
				'description'    => $lease->get_area_description(),
				'bill_frequency' => $lease->get_bill_frequency_short_description() ?: 'pa',
				'rental_cost'    => $rental_cost,
				'service_charge' => $service_charge
			];

		}, $tenant->get_active_leases())
	];

	return App::encode_result('OK', 'Success', $result);
}

function request_change($tenant) {
	$request_message = $_POST['message'];
	$result = 'OK';
	$message = 'Changes will be made once we have verified your request.';

	if(!$request_message) {
		$result = 'FAIL';
		$message = 'Please enter details of any changes you require.';
	} else {
		$client_info = App::sql()->query_row("SELECT * FROM client WHERE id = '{$tenant->info->client_id}';");
		$client_id = $client_info ? $client_info->id : '0';
		$client_name = $client_info ? $client_info->name : 'Eticom';
		$date = new DateTime();
		$dt = $date->format('Y-m-d H:i:s');

		$email_body = "
			<h1>Eticom Tenancy</h1>
			<h2>Tenant change request</h2>
			<p>The following tenant has requested a change through the tenancy app:</p>
			<ul>
				<li>Client ID: <b>$client_id<b></li>
				<li>Client Name: <b>$client_name<b></li>
				<li>Tenant ID: <b>$tenant->id</b></li>
				<li>Tenant Name: <b>{$tenant->info->name}</b></li>
			</ul>
			<h3>Request message</h3>
			<pre>$request_message</pre>
		";

		$mailer = new Mailer();
		$from = $mailer->get_default_from('Eticom App');
		$to = $mailer->build_address_info('info@eticom.co.uk', 'Eticom');
		$return = $mailer->email($from, $to, '[Eticom Tenancy] Tenant change request - '.$dt, $email_body);

		if(!$return) {
			$result = 'FAIL';
			$message = 'Unable to complete request. Please try again later.';
		}
	}

	return App::encode_result($result, $message, null);
}

// Lists all areas associated with the tenant
// Shows all areas for which bills have ever been issued
function get_billed_areas($tenant) {
	return App::encode_result('OK', 'Success', $tenant->get_billed_areas());
}

/**
 * Gets the tenant's most recent bills per unit per bill type
 */
function get_most_recent_bills($tenant) {
	$areas = [];
	foreach($tenant->get_billed_areas() as $area) {
		$bills = TenantBill::get_latest_bills_by_type($tenant->id, $area->id);
		$bill_data = array_map(function($bill) { return $bill->get_json_info(); }, $bills);

		if(count($bill_data) > 0) {
			$areas[] = [
				'area_id'          => $area->id,
				'area_description' => $area->description,
				'bills'            => $bill_data
			];
		}
	}

	$result = count($areas) > 0;

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Success' : 'No bills found.', $areas);
}

function email_bill($tenant) {
	$bill_id = App::post('bill_id', 0, true);
	$type = App::post('type', '', true);
	$result = false;

	if($tenant->id != 22) {
		$bill = new TenantBill($type, $bill_id);

		if($bill->validate()) {
			$bill_date = App::format_datetime('d F Y', $bill->info->bill_date, 'Y-m-d');
			$type_desc = TenantBill::get_type_description($type);

			$email_body = "
				<h1>Your bill for {$bill->info->area_description}</h1>
				<h2>$bill_date</h2>
				<h3>$type_desc</h3>
				<p>Please find your bill attached to this email.</p>
			";

			$mailer = new Mailer();
			$from = $mailer->get_default_from('Eticom App');
			$to = $mailer->build_address_info($tenant->info->email_address, $tenant->info->name);
			$result = $mailer->email($from, $to, "Your bill for {$bill->info->area_description} - $bill_date - $type_desc", $email_body, [ $bill->get_url().'|'.$bill->get_filename() ]);
		}
	} else {
		// Tenant 22 is the test tenant for apple
		$result = true;
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Your bill will be emailed to you shortly.' : 'Unable to complete request. Please try again later.', $result);
}

function search_bills($tenant) {
	$area_id = App::post('area_id', '', true);
	$start_date = App::post('start_date', '', true);
	$end_date = App::post('end_date', '', true);
	$start = $start_date ? explode('T', $start_date)[0] : '1970-01-01';
	$end = $end_date ? explode('T', $end_date)[0] : '2099-12-31';

	$area_filter = '';
	if($area_id) $area_filter = "AND area_id = '$area_id'";

	$q = '';
	foreach(TenantBill::$tables as $type => $table) {
		if($q) $q .= ' UNION ALL ';
		$q .= "(SELECT '$type' AS bill_type, id, bill_date FROM $table WHERE tenant_id = '$tenant->id' AND bill_date >= '$start' AND bill_date <= '$end' $area_filter)";
	}

	$bills = App::sql()->query("SELECT * FROM ($q) AS bills ORDER BY bill_date DESC, bill_type");

	$result = array_map(function($bill) {
		return [
			'type' => $bill->bill_type,
			'id'   => $bill->id,
			'desc' => App::format_datetime('d F Y', $bill->bill_date, 'Y-m-d'),
			'sub'  => TenantBill::get_type_description($bill->bill_type)
		];
	}, $bills ?: []);

	return App::encode_result('OK', count($result) > 0 ? 'Success' : 'No bills found.', $result);
}

function get_bill_details($tenant) {
	$type = App::post('type', '', true);
	$bill_id = App::post('bill_id', 0, true);
	$result = false;

	$bill = new TenantBill($type, $bill_id);

	if($bill->validate()) {
		$result = $bill->get_json_info();
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Success' : 'Bill not found.', $result);
}
