<?php

/*
 * This page is the main document to post data to the database.
 *
 * This is part of the old framework. eticomcloud.com/ajax/post/function points here, which will call the named function in this file.
 *
 * Each function starts off with App::set_content_type() - This tells javascript they are output as json. You can find the function in /lib/class.app.php line 392
 *
 * Any classes referenced in this document can be found in /lib
 * Any SmartAdmin classes referenced in this document can be found in /lib/smartui
 * Any Mailer classes referenced in this document can be found in /lib/phpmailer
 *
 * SmartAdmin framework is documented in the following pages:
 *
 * http://myorange.ca/themes/preview/smartadmin/1.5/phpversion/#ajax/dashboard.php
 *
 * SmartForm: http://myorange.ca/themes/preview/smartadmin/1.5/phpversion/#ajax/smartui-form.php
 *
 */

require_once 'init.ajax.php';
if (!$user) {
	// If there isn't a user session then don't call any functions.
	exit(die("Unable to continue with the query."));
}

$get_func = key($_GET);
echo function_exists($get_func) ? call_user_func($get_func, $user) : null;

/**
 * Used when a user doesn't/hadn't have access to the main dashboard - create one for the user. Or used when changing the time period on the main electricity dashboard.
 */
function update_dashboard_main($user) {
	App::set_content_type();

	$time_period  = App::post('time_period');
	$dashboard_id = App::post('dashboard_id');

	$dashboard = new Dashboard($dashboard_id);
	if ($dashboard->is_default()) {
		// save as new dashboard for this user
		$result = $user->add_dashboard('Single dashboard', 'automatically added', $time_period, true, $dashboard->type);
	} else {
		// update current dashboard with the newly set time period
		$result = $user->update_dashboard($dashboard, [ 'time_period' => $time_period ]);
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'dashboard updated' : 'Failed to update dashboard');
}

/**
 * Used when changing information about a dashboard on the following:
 *  - device dashboard
 */
function update_dashboard($user) {
	App::set_content_type();

	$title        = $_POST['title'];
	$desc         = $_POST['desc'];
	$dashboard_id = $_POST['dashboard_id'];

	$result = $user->update_dashboard($dashboard_id, [ 'title' => $title, 'description' => $desc ]);
	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'dashbord updated' : 'Failed to update dashboard');
}

/**
 * Add a new tenant into the database
 */
function add_tenant($user) {
	App::set_content_type();

	$building_id = $_POST['building_id'];
	$building = new Building($building_id);

	if ($building->validate()) {
		$result = App::insert('tenant', [
			'name'             => App::post('name', ''),
			'company'          => App::post('company', ''),
			'email_address'    => App::post('email_address', ''),
			'customer_reference_number' => App::post('customer_reference_number', ''),
			'home_address'     => App::post('home_address', ''),
			'telephone_number' => App::post('telephone_number', ''),
			'mobile_number'    => App::post('mobile_number', ''),
			'client_id'        => App::post('client_id', 0)
		]);

		if($result) {
			$reset_password = App::post('reset_password', 0);
			if($reset_password) {
				$tenant = new Tenant($result);
				$res = $tenant->reset_password();
			}
		}
	} else {
		$result = false;
	}

	return App::encode_result($result ? 'OK' : 'FAIL', '', $result);
}

/**
 * Update tenant information
 */
function update_tenant($user) {
	App::set_content_type();

	list($building_id, $tenant_id) = App::post(['building_id', 'tenant_id'], null);

	$building = new Building($building_id);
	if ($tenant_id && $building->validate()) {
		$result = App::update('tenant', $tenant_id, [
			'name'             => App::post('name', ''),
			'company'          => App::post('company', ''),
			'email_address'    => App::post('email_address', ''),
			'customer_reference_number' => App::post('customer_reference_number', ''),
			'home_address'     => App::post('home_address', ''),
			'telephone_number' => App::post('telephone_number', ''),
			'mobile_number'    => App::post('mobile_number', '')
		]);

		if($result) {
			$reset_password = App::post('reset_password', 0);
			if($reset_password) {
				$tenant = new Tenant($tenant_id);
				$res = $tenant->reset_password();
			}
		}
	} else {
		$result = false;
	}

	return App::encode_result($result ? 'OK' : 'FAIL', '', $tenant_id);
}

function tenant_bill_paid($user) {
	App::set_content_type();

	list($type, $bill_id) = App::post(['tenant_bill_type', 'tenant_bill_id'], 0, true);

	$result = false;
	$message = '';

	if($type && $bill_id) {
		try {
			$bill = new TenantBill($type, $bill_id);
			$bill->mark_as_paid();
			$result = true;
		} catch(Exception $ex) {
			$message = $ex->getMessage();
		}
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $message);
}

function create_lease($user) {
	App::set_content_type();

	$tenant_id                = App::post('tenant_id', 0, true);
	$lease_start_date         = App::post('lease_start_date', null);
	$term                     = App::post('term', 0);
	$term_units               = App::post('term_units', '');
	$lease_renewal_alert_date = App::post('lease_renewal_alert_date', null);
	$payment_type             = App::post('payment_type', 'Other');
	$account_ref              = App::post('account_ref', '');
	$invoice_address_1        = App::post('invoice_address_1', '');
	$invoice_address_2        = App::post('invoice_address_2', '');
	$invoice_address_3        = App::post('invoice_address_3', '');
	$invoice_posttown         = App::post('invoice_posttown', '');
	$postcode                 = App::post('postcode', '');
	$building_id              = App::post('building_id', 0, true);
	$tenanted_id              = App::post('tenanted_id', 0, true);

	if(!$tenant_id) {
		return App::encode_result('FAIL', 'Please select tenant.');
	}

	if(!$building_id || !$tenanted_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	if(!$lease_start_date || !$term || !$term_units) {
		return App::encode_result('FAIL', 'Please enter start date and term length.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	if($lease_start_date) $lease_start_date = App::format_datetime('Y-m-d', $lease_start_date, 'd/m/Y');
	$lease_end_date = date('Y-m-d', strtotime("+{$term} {$term_units} -1 day", strtotime($lease_start_date)));
	if($lease_renewal_alert_date) $lease_renewal_alert_date = App::format_datetime('Y-m-d', $lease_renewal_alert_date, 'd/m/Y');

	$today = new DateTime();
	$d = new DateTime($lease_start_date);
	$status = Lease::STATUS_FUTURE;
	if($d <= $today) {
		$d = $today;
		$status = Lease::STATUS_CURRENT_ACTIVE;

		if(!$account_ref) return App::encode_result('FAIL', 'Account reference is mandatory for active leases.');
	}

	if(Lease::get_future_lease($tenanted_id)) {
		return App::encode_result('FAIL', 'A future/approved contract has already been created for this unit.');
	}

	if($status == Lease::STATUS_CURRENT_ACTIVE) {
		if(Lease::get_current_lease($tenanted_id)) {
			return App::encode_result('FAIL', 'There is a currently active contract for this unit. Move the current tenant out, or select a future start date.');
		}
	}

	// Resolve area_id from tenanted area
	$tenanted_area = App::sql()->query_row("SELECT * FROM tenanted_area WHERE id = '$tenanted_id';");
	$area_id = $tenanted_area->area_id;
	if(!$area_id) {
		return App::encode_result('FAIL', 'Area not found.');
	}

	$result = App::insert('tenant_lease', [
		'tenant_id'                => $tenant_id,
		'lease_start_date'         => $lease_start_date,
		'lease_end_date'           => $lease_end_date,
		'term'                     => $term,
		'term_units'               => $term_units,
		'lease_renewal_alert_date' => $lease_renewal_alert_date,
		'payment_type'             => $payment_type,
		'account_ref'              => $account_ref,
		'invoice_address_1'        => $invoice_address_1,
		'invoice_address_2'        => $invoice_address_2,
		'invoice_address_3'        => $invoice_address_3,
		'invoice_posttown'         => $invoice_posttown,
		'postcode'                 => $postcode,
		'area_id'                  => $area_id,
		'status'                   => $status,

		// Automatically fill in asking prices from tenanted area
		'rental_cost_pounds_ex_vat_per_year'    => $tenanted_area->asking_rental_cost_pounds ?: 0,
		'service_charge_pounds_ex_vat_per_year' => $tenanted_area->asking_service_charge_pounds ?: 0,
		'service_charge_info'                   => $tenanted_area->service_charge_info ?: '',
		'electric_ex_vat_cost_in_pence_per_kwh' => $tenanted_area->asking_electric_ex_vat_cost_in_pence_per_kwh ?: 0,
		'gas_ex_vat_cost_in_pence_per_kwh'      => $tenanted_area->asking_gas_ex_vat_cost_in_pence_per_kwh ?: 0,
		'water_ex_vat_cost_in_pence_per_m3'     => $tenanted_area->asking_water_ex_vat_cost_in_pence_per_m3 ?: 0,
		'utility_vat_rate'                      => 20
	]);

	if($status == Lease::STATUS_CURRENT_ACTIVE) {
		if($result) {
			App::sql()->update("UPDATE tenanted_area SET tenant_id = $tenant_id, occupied = 1, vacant_since = NULL WHERE id = $tenanted_id;");
		}
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease created.' : 'Error creating lease.', null);
}

function update_lease_invoice_address($user) {
	App::set_content_type();

	$lease_id          = App::post('lease_id', 0, true);
	$building_id       = App::post('building_id', 0, true);
	$invoice_address_1 = App::post('invoice_address_1', '');
	$invoice_address_2 = App::post('invoice_address_2', '');
	$invoice_address_3 = App::post('invoice_address_3', '');
	$invoice_posttown  = App::post('invoice_posttown', '');
	$postcode          = App::post('postcode', '');

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	$result = App::update('tenant_lease', $lease_id, [
		'invoice_address_1' => $invoice_address_1,
		'invoice_address_2' => $invoice_address_2,
		'invoice_address_3' => $invoice_address_3,
		'invoice_posttown'  => $invoice_posttown,
		'postcode'          => $postcode
	]);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function update_lease($user) {
	App::set_content_type();

	$lease_id                 = App::post('lease_id', 0, true);
	$lease_start_date         = App::post('lease_start_date', null);
	$term                     = App::post('term', 12);
	$term_units               = App::post('term_units', 'month');
	$lease_renewal_alert_date = App::post('lease_renewal_alert_date', null);
	$payment_type             = App::post('payment_type', 'Other');
	$account_ref              = App::post('account_ref', '');
	$building_id              = App::post('building_id', 0, true);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->info) {
		return App::encode_result('FAIL', 'Lease not found.');
	}

	if($lease_start_date && (!$term || !$term_units)) {
		return App::encode_result('FAIL', 'Please enter start date and term length.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	if($lease_start_date) {
		$lease_start_date = App::format_datetime('Y-m-d', $lease_start_date, 'd/m/Y');
		$lease_end_date = date('Y-m-d', strtotime("+{$term} {$term_units} -1 day", strtotime($lease_start_date)));
	}
	if($lease_renewal_alert_date) $lease_renewal_alert_date = App::format_datetime('Y-m-d', $lease_renewal_alert_date, 'd/m/Y');

	if($lease->is_future()) {
		$today = new DateTime();
		$d = new DateTime($lease_start_date);
		if($d <= $today) {
			return App::encode_result('FAIL', 'Please select a future start date.');
		}
	}

	if($lease->is_current() && !$account_ref) {
		return App::encode_result('FAIL', 'Account reference is mandatory for active leases.');
	}

	$data = [
		'lease_renewal_alert_date' => $lease_renewal_alert_date,
		'payment_type'             => $payment_type,
		'account_ref'              => $account_ref
	];

	if($lease->is_future() && $lease_start_date) {
		// Update start/end dates
		$data = array_merge($data, [
			'lease_start_date' => $lease_start_date,
			'lease_end_date'   => $lease_end_date,
			'term'             => $term,
			'term_units'       => $term_units
		]);
	}

	$result = App::update('tenant_lease', $lease_id, $data);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_move_in($user) {
	App::set_content_type();

	$lease_id                 = App::post('lease_id', 0, true);
	$lease_start_date         = App::post('lease_start_date', null);
	$term                     = App::post('term', 12);
	$term_units               = App::post('term_units', 'month');
	$lease_renewal_alert_date = App::post('lease_renewal_alert_date', null);
	$account_ref              = App::post('account_ref', '');
	$building_id              = App::post('building_id', 0, true);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	if(!$lease_start_date || !$term || !$term_units) {
		return App::encode_result('FAIL', 'Please enter start date and term length.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	$lease_start_date = App::format_datetime('Y-m-d', $lease_start_date, 'd/m/Y');
	$lease_end_date = date('Y-m-d', strtotime("+{$term} {$term_units} -1 day", strtotime($lease_start_date)));
	if($lease_renewal_alert_date) $lease_renewal_alert_date = App::format_datetime('Y-m-d', $lease_renewal_alert_date, 'd/m/Y');

	$today = new DateTime();
	$d = new DateTime($lease_start_date);
	if($d > $today) {
		return App::encode_result('FAIL', 'Please select a start date of today or a date in the past.');
	}

	$d = new DateTime($lease_end_date);
	if($d <= $today) {
		return App::encode_result('FAIL', 'Contract term must end in the future.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->is_future()) {
		return App::encode_result('FAIL', 'You can only move in a future/approved contract.');
	}

	if(!$account_ref) {
		return App::encode_result('FAIL', 'Account reference is mandatory for active leases.');
	}

	$result = App::update('tenant_lease', $lease_id, [
		'lease_start_date'         => $lease_start_date,
		'lease_end_date'           => $lease_end_date,
		'term'                     => $term,
		'term_units'               => $term_units,
		'lease_renewal_alert_date' => $lease_renewal_alert_date,
		'account_ref'              => $account_ref,
		'status'                   => Lease::STATUS_CURRENT_ACTIVE
	]);

	if($result) {
		$tenant_id = $lease->info->tenant_id;
		$area_id = $lease->info->area_id;
		App::sql()->update("UPDATE tenanted_area SET tenant_id = $tenant_id, occupied = 1, vacant_since = NULL WHERE area_id = '$area_id';");
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_move_out($user) {
	App::set_content_type();

	$lease_id       = App::post('lease_id', 0, true);
	$lease_end_date = App::post('lease_end_date', null);
	$building_id    = App::post('building_id', 0, true);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	if(!$lease_end_date) {
		return App::encode_result('FAIL', 'Please enter lease end date.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	if($lease_end_date) $lease_end_date = App::format_datetime('Y-m-d', $lease_end_date, 'd/m/Y');

	$today = new DateTime();
	$d = new DateTime($lease_end_date);
	if($d > $today) {
		return App::encode_result('FAIL', 'Please select an end date of today or a date in the past.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->is_current()) {
		return App::encode_result('FAIL', 'You can only move out a current contract.');
	}

	$sd = new DateTime($lease->info->lease_start_date);
	if($d < $sd) {
		return App::encode_result('FAIL', 'End date must be after start date.');
	}

	$result = App::update('tenant_lease', $lease_id, [
		'lease_end_date' => $lease_end_date,
		'status'         => Lease::STATUS_PREVIOUS
	]);

	if($result) {
		$tenant_id = $lease->info->tenant_id;
		$area_id = $lease->info->area_id;
		App::sql()->update("UPDATE tenanted_area SET tenant_id = NULL, occupied = 0, vacant_since = NOW() WHERE area_id = '$area_id';");
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_set_end($user) {
	App::set_content_type();

	$lease_id       = App::post('lease_id', 0, true);
	$lease_end_date = App::post('lease_end_date', null);
	$building_id    = App::post('building_id', 0, true);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	if(!$lease_end_date) {
		return App::encode_result('FAIL', 'Please enter end date.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	if($lease_end_date) $lease_end_date = App::format_datetime('Y-m-d', $lease_end_date, 'd/m/Y');

	$today = new DateTime();
	$d = new DateTime($lease_end_date);
	if($d < $today) {
		return App::encode_result('FAIL', 'Please select an end date of today or a date in the future.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->is_current()) {
		return App::encode_result('FAIL', 'This is not a current lease.');
	}

	$sd = new DateTime($lease->info->lease_start_date);
	if($d < $sd) {
		return App::encode_result('FAIL', 'Lease cannot end before its start date.');
	}

	$result = App::update('tenant_lease', $lease_id, [
		'lease_end_date' => $lease_end_date,
		'status'         => Lease::STATUS_CURRENT_ENDING
	]);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_change_end($user) {
	App::set_content_type();

	$lease_id       = App::post('lease_id', 0, true);
	$lease_end_date = App::post('lease_end_date', null);
	$building_id    = App::post('building_id', 0, true);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	if(!$lease_end_date) {
		return App::encode_result('FAIL', 'Please enter end date.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	if($lease_end_date) $lease_end_date = App::format_datetime('Y-m-d', $lease_end_date, 'd/m/Y');

	$today = new DateTime();
	$d = new DateTime($lease_end_date);
	if($d < $today) {
		return App::encode_result('FAIL', 'Please select an end date of today or a date in the future.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->is_current()) {
		return App::encode_result('FAIL', 'This is not a current lease.');
	}

	$sd = new DateTime($lease->info->lease_start_date);
	if($d < $sd) {
		return App::encode_result('FAIL', 'Lease cannot end before its start date.');
	}

	$result = App::update('tenant_lease', $lease_id, [
		'lease_end_date' => $lease_end_date,
		'status'         => Lease::STATUS_CURRENT_ACTIVE
	]);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_bill_rent($user) {
	App::set_content_type();

	$lease_id                              = App::post('lease_id', 0, true);
	$building_id                           = App::post('building_id', 0, true);
	$rental_cost_pounds_ex_vat_per_year    = App::post('rental_cost_pounds_ex_vat_per_year', 0);
	$service_charge_pounds_ex_vat_per_year = App::post('service_charge_pounds_ex_vat_per_year', 0);
	$service_charge_info                   = App::post('service_charge_info', '');
	$rent_review_date                      = App::post('rent_review_date', null);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	if($rent_review_date) $rent_review_date = App::format_datetime('Y-m-d', $rent_review_date, 'd/m/Y');

	$lease = new Lease($lease_id);
	if(!$lease->is_current()) {
		return App::encode_result('FAIL', 'This is not a current lease.');
	}

	$data = [
		'rental_cost_pounds_ex_vat_per_year'    => $rental_cost_pounds_ex_vat_per_year,
		'service_charge_pounds_ex_vat_per_year' => $service_charge_pounds_ex_vat_per_year,
		'service_charge_info'                   => $service_charge_info,
		'rent_review_date'                      => $rent_review_date
	];

	if(isset($_POST['enable_billing'])) {
		$data['auto_generate_rent_bill'] = 1;
		$data['bill_generate_date_rent'] = App::post('bill_generate_date_rent', '1970-01-01', true);
		$data['days_to_pay_rent_bill']   = App::post('days_to_pay_rent_bill', 14, true);
	} else {
		$data['auto_generate_rent_bill'] = 0;
		$data['bill_generate_date_rent'] = '1970-01-01';
	}

	$result = App::update('tenant_lease', $lease_id, $data);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_bill_utility($user) {
	App::set_content_type();

	$lease_id                              = App::post('lease_id', 0, true);
	$building_id                           = App::post('building_id', 0, true);
	$electric_ex_vat_cost_in_pence_per_kwh = App::post('electric_ex_vat_cost_in_pence_per_kwh', 0);
	$gas_ex_vat_cost_in_pence_per_kwh      = App::post('gas_ex_vat_cost_in_pence_per_kwh', 0);
	$water_ex_vat_cost_in_pence_per_m3     = App::post('water_ex_vat_cost_in_pence_per_m3', 0);
	$utility_vat_rate                      = App::post('utility_vat_rate', 0);
	$display_electric_usage_on_bill        = App::post('display_electric_usage_on_bill', 0);
	$display_gas_usage_on_bill             = App::post('display_gas_usage_on_bill', 0);
	$display_water_usage_on_bill           = App::post('display_water_usage_on_bill', 0);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->is_current()) {
		return App::encode_result('FAIL', 'This is not a current lease.');
	}

	$data = [
		'electric_ex_vat_cost_in_pence_per_kwh' => $electric_ex_vat_cost_in_pence_per_kwh,
		'gas_ex_vat_cost_in_pence_per_kwh'      => $gas_ex_vat_cost_in_pence_per_kwh,
		'water_ex_vat_cost_in_pence_per_m3'     => $water_ex_vat_cost_in_pence_per_m3,
		'utility_vat_rate'                      => $utility_vat_rate,
		'display_electric_usage_on_bill'        => $display_electric_usage_on_bill,
		'display_gas_usage_on_bill'             => $display_gas_usage_on_bill,
		'display_water_usage_on_bill'           => $display_water_usage_on_bill
	];

	if(isset($_POST['enable_billing'])) {
		$data['auto_generate_utility_bill'] = 1;
		$data['bill_generate_date_utility'] = App::post('bill_generate_date_utility', '1970-01-01', true);
		$data['days_to_pay_utility_bill']   = App::post('days_to_pay_utility_bill', 14, true);
	} else {
		$data['auto_generate_utility_bill'] = 0;
		$data['bill_generate_date_utility'] = '1970-01-01';
	}

	$result = App::update('tenant_lease', $lease_id, $data);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_bill_estate($user) {
	App::set_content_type();

	$lease_id                              = App::post('lease_id', 0, true);
	$building_id                           = App::post('building_id', 0, true);
	$estate_cost_pounds_ex_vat_per_year    = App::post('estate_cost_pounds_ex_vat_per_year', 0, true);
	$bill_generate_frequency_estate_cost   = App::post('bill_generate_frequency_estate_cost', 0, true);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->is_current()) {
		return App::encode_result('FAIL', 'This is not a current lease.');
	}

	$data = [
		'estate_cost_pounds_ex_vat_per_year'    => $estate_cost_pounds_ex_vat_per_year,
		'bill_generate_frequency_estate_cost'   => $bill_generate_frequency_estate_cost
	];

	if(isset($_POST['enable_billing'])) {
		$day = App::format_datetime('Y-m-d', App::post('bill_generate_date_estate_cost'), 'd/m/Y');
		if(!$day) $day = date('Y-m-d');
		$day = date('Y-m-d', strtotime('-1 day', strtotime($day)));

		$data['auto_generate_estate_cost_bill'] = 1;
		$data['bill_generate_date_estate_cost'] = Lease::get_next_bill_date($day, $bill_generate_frequency_estate_cost);
		$data['days_to_pay_estate_cost_bill'] = App::post('days_to_pay_estate_cost_bill', 14, true);
	} else {
		$data['auto_generate_estate_cost_bill'] = 0;
		$data['bill_generate_date_estate_cost'] = '1970-01-01';
	}

	$result = App::update('tenant_lease', $lease_id, $data);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function lease_bill_misc($user) {
	App::set_content_type();

	$lease_id                     = App::post('lease_id', 0, true);
	$building_id                  = App::post('building_id', 0, true);
	$bill_generate_frequency_misc = App::post('bill_generate_frequency_misc', 0, true);

	$items = [];
	for($i = 1; $i <= 10; $i++) {
		$desc = App::post("misc_{$i}_desc", '', true);
		$value = App::post("misc_{$i}_value", 0, true);
		$recurring = isset($_POST["misc_{$i}_recurring"]) ? 1 : 0;
		if($desc) {
			$items[] = [
				'desc'      => $desc,
				'value'     => $value,
				'recurring' => $recurring
			];
		}
	}

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->is_current()) {
		return App::encode_result('FAIL', 'This is not a current lease.');
	}

	$data = [];
	foreach($items as $i => $item) {
		$index = $i + 1;
		$data["misc_{$index}_desc"]      = $item['desc'];
		$data["misc_{$index}_value"]     = $item['value'];
		$data["misc_{$index}_recurring"] = $item['recurring'];
	}

	if(isset($_POST['enable_billing'])) {
		$day       = App::format_datetime('Y-m-d', App::post('bill_generate_date_misc'), 'd/m/Y');
		$date_from = App::post('bill_from_date_misc', '');
		$date_to   = App::post('bill_to_date_misc', '');

		if($date_from && $date_to) {
			$date_from = App::format_datetime('Y-m-d', $date_from, 'd/m/Y');
			$date_to   = App::format_datetime('Y-m-d', $date_to, 'd/m/Y');
			if(strtotime($date_from) > strtotime($date_to)) {
				list($date_from, $date_to) = [$date_to, $date_from];
			}
		}

		if(!$day) {
			$day = date('Y-m-d');
			$day = date('Y-m-d', strtotime('-1 day', strtotime($day)));
			$day = Lease::get_next_bill_date($day, $bill_generate_frequency_misc);
		}

		$data['auto_generate_misc_bill'] = 1;
		$data['bill_generate_date_misc'] = $day;
		$data['days_to_pay_misc_bill']   = App::post('days_to_pay_misc_bill', 14, true);
		$data['bill_from_date_misc']     = $date_from;
		$data['bill_to_date_misc']       = $date_to;
		$data['print_zero_misc_bill']    = isset($_POST['print_zero_misc_bill']) ? 1 : 0;
	} else {
		$data['auto_generate_misc_bill'] = 0;
		$data['bill_generate_date_misc'] = '1970-01-01';
	}

	$result = App::update('tenant_lease', $lease_id, $data);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Lease updated.' : 'Error updating lease.', null);
}

function delete_lease($user) {
	App::set_content_type();

	$lease_id    = App::post('lease_id', 0, true);
	$building_id = App::post('building_id', 0, true);

	if(!$building_id) {
		return App::encode_result('FAIL', 'Building not selected.');
	}

	$building = new Building($building_id);
	if(!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.');
	}

	$lease = new Lease($lease_id);
	if(!$lease->info) {
		return App::encode_result('FAIL', 'Lease not found.');
	}

	if(!$lease->is_future()) {
		return App::encode_result('FAIL', 'You can only delete a future contract.');
	}

	$result = App::delete('tenant_lease', $lease_id);

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Contract deleted.' : 'Error deleting contract.', null);
}

function update_tenanted_area($user) {
	App::set_content_type();

	$building_id       = $_POST['building_id'];
	$area_id           = $_POST['area_id'];
	$tenanted_area_id  = $_POST['tenanted_area_id'];
	$tenant_type       = isset($_POST['tenant_type']) && $_POST['tenant_type'] == 'serviced' ? 'serviced' : 'independent';
	$is_owner_occupied = isset($_POST['is_owner_occupied']) ? 1 : 0;

	$building = new Building($building_id);
	if ($building->validate()) {

		// Update area fields
		$area_data = [
			'size'              => $_POST['size'],
			'size_unit'         => $_POST['size_unit'],
			'is_owner_occupied' => $is_owner_occupied ? 1 : 0,
			'is_tenanted'       => $is_owner_occupied ? 0 : 1
		];

		$area_description = App::post('area_description', null);
		if($area_description) $area_data['description'] = $area_description;

		$result = App::update('area', $area_id, $area_data);

		// Update tenanted_area fields

		App::update('tenanted_area', $tenanted_area_id, [
			'asking_rental_cost_pounds'                    => $_POST['asking_rental_cost_pounds'] > 0 ? $_POST['asking_rental_cost_pounds'] : 0,
			'asking_service_charge_pounds'                 => $_POST['asking_service_charge_pounds'] > 0 ? $_POST['asking_service_charge_pounds'] : 0,
			'asking_electric_ex_vat_cost_in_pence_per_kwh' => $_POST['asking_electric_ex_vat_cost_in_pence_per_kwh'] > 0 ? $_POST['asking_electric_ex_vat_cost_in_pence_per_kwh'] : 0,
			'asking_gas_ex_vat_cost_in_pence_per_kwh'      => $_POST['asking_gas_ex_vat_cost_in_pence_per_kwh'] > 0 ? $_POST['asking_gas_ex_vat_cost_in_pence_per_kwh'] : 0,
			'asking_water_ex_vat_cost_in_pence_per_m3'     => $_POST['asking_water_ex_vat_cost_in_pence_per_m3'] > 0 ? $_POST['asking_water_ex_vat_cost_in_pence_per_m3'] : 0,
			'service_charge_info'                          => App::post('service_charge_info', ''),
			'tenant_type'                                  => $tenant_type
		]);

	} else {
		$result = false;
	}

	return App::encode_result($result ? 'OK' : 'FAIL');
}

function add_meter_reading($user) {
	App::set_content_type();

	$meter_id = App::post('meter_id', 0);
	$building_id = App::post('building_id', 0);
	$initial_reading = isset($_POST['initial_reading']) ? 1 : 0;
	$reading_date = App::post('reading_date', null);
	$reading_1 = isset($_POST['reading_1']) && is_numeric($_POST['reading_1']) ? $_POST['reading_1'] : null;
	$reading_2 = isset($_POST['reading_2']) && is_numeric($_POST['reading_2']) ? $_POST['reading_2'] : null;
	$reading_3 = isset($_POST['reading_3']) && is_numeric($_POST['reading_3']) ? $_POST['reading_3'] : null;

	if(!$building_id || !$meter_id) return App::encode_result('FAIL', 'Incomplete request.');

	$building = new Building($building_id);
	if(!$building->validate()) return App::encode_result('FAIL', 'Access denied.');

	$meter = new Meter($meter_id);
	if(!$meter->validate($building_id)) return App::encode_result('FAIL', 'Invalid meter.');
	//if(!Permission::get_building($building_id)->check(Permission::METERS_ADD_READING)) return App::encode_result('FAIL', 'Access denied.');

	if($reading_date == null || $reading_1 == null) return App::encode_result('FAIL', 'Both reading date and reading value is mandatory.');

	/*if($meter->is_automatic() && !Permission::get_eticom()->check(Permission::ADMIN)) return App::encode_result('FAIL', 'Meter is read automatically, you are not allowed to take a manual meter reading.');*/

	$ok = false;
	$message = '';

	try {
		$meter->add_meter_reading($user->id, App::format_datetime('Y-m-d', $reading_date, 'd/m/Y'), [$reading_1, $reading_2, $reading_3], $initial_reading);
		$ok = true;
	} catch(Exception $e) {
		$message = $e->getMessage();
	}

	return App::encode_result($ok ? 'OK' : 'FAIL', $message);
}

function generate_demo_readings($user) {
	App::set_content_type();

	$meter_id = App::post('meter_id', 0);
	if(!$meter_id) return App::encode_result('FAIL', 'Incomplete request.');

	$meter = new Meter($meter_id);
	if(!$meter->validate()) return App::encode_result('FAIL', 'Invalid meter.');

	$building = new Building($meter->get_building_id());
	if(!$building->validate()) return App::encode_result('FAIL', 'Access denied.');
	if(!$building->info->is_demo) return App::encode_result('FAIL', 'Not a demo building.');

	$weekday_use = 0;
	$weekend_use = 0;
	$noise = 0.33;

	switch($meter->info->meter_type) {
		case 'E':
			$weekday_use = 600;
			$weekend_use = 300;
			break;

		case 'W':
			$weekday_use = 4;
			$weekend_use = 1;
			break;

		case 'G':
			$weekday_use = 150;
			$weekend_use = 50;
			break;

		case 'H':
			$weekday_use = 100;
			$weekend_use = 50;
			break;
	}

	if($meter->is_submeter()) {
		$weekday_use /= 4;
		$weekend_use /= 4;
	}

	// Delete all readings for the meter

	App::sql()->delete("DELETE FROM meter_period WHERE meter_id = '$meter->id';");
	App::sql()->delete("DELETE FROM meter_reading WHERE meter_id = '$meter->id';");

	// Generate new meter readings

	$r = 0;
	$i = 1;
	for($d = 400; $d > 0; $d--) {
		$day = strtotime("-$d day");
		$dow = date('w', $day);
		$dom = date('j', $day);

		$u = $weekday_use;
		if($dow == 0 || $dow == 6) $u = $weekend_use;
		$u += (rand(-100, 100) / 100) * $u * $noise;

		$r += $u > 0 ? $u : 0;

		if(($d > 70 && $dom == 1) || $d <= 70) {
			$reading_date = date('Y-m-d', $day);
			$meter->add_meter_reading($user->id, $reading_date, [floor($r)], $i);
			$i = 0;
		}
	}

	return App::encode_result('OK', 'Data generated.');
}

function set_default_building($user) {
	App::set_content_type();

	if(isset($_POST['building_id'])) $user->set_default_building($_POST['building_id']);

	return App::encode_result('OK', 'Default building has been updated.');
}

/**
 * Used when modifying the opening hours from the after hours widget when the 'Change your working times' link has been clicked.
 */
function update_afterhours($user) {
	App::set_content_type();

	$building_id = $_POST['bldg_id'];

	$building = new Building($building_id);
	if (!$building->validate()) return App::encode_result('FAIL', 'Invalid building #');

	// Update the working hours as posted from the form.
	$building->update_working_hours($_POST['working_hours_open_time'], $_POST['working_hours_close_time'], $_POST['working_hours_closed_all_day']);

	// holidays
	$dates = [];
	foreach ($_POST['holidays_date'] as $index => $date) {
		$dates[] = (object)[
			'description'    => $_POST['holidays_description'][$index],
			'date'           => App::format_datetime('Y-m-d', $date, 'd/m/Y'),
			'open_time'      => $_POST['holidays_open_time'][$index],
			'close_time'     => $_POST['holidays_close_time'][$index],
			'closed_all_day' => $_POST['holidays_closed_all_day'][$index]
		];
	}

	//update the holiday days as posted from the form (this also includes the holidays for tenanted areas.
	$building->update_holidays($dates);

	return App::encode_result('OK');
}

function update_landlord_supply($user) {
	App::set_content_type();

	$meter_id = App::post('meter_id', 0, true);
	$meter = new Meter($meter_id);
	$building_id = $meter->get_building_id();

	$building = new Building($building_id);
	if (!$building->validate()) return App::encode_result('FAIL', 'Invalid building #');

	$mpan = $_POST['mpan'];

	$data = [];
	$data['serial_number'] = $_POST['serial_number'];
	$data['mpan'] = $mpan;
	$data['tariff_id'] = $_POST['tariff_id'] ?: null;
	if($meter->info->tariff_id && $data['tariff_id'] != $meter->info->tariff_id) {
		// Tariff has changed
		$data['previous_tariff_end_date'] = $_POST['tariff_change_date'] ? App::format_datetime('Y-m-d', $_POST['tariff_change_date'], 'd/m/Y') : null;
		$data['previous_tariff_id'] = $meter->info->tariff_id;
	}
	$result = App::update('meter', $meter_id, $data);

	return App::encode_result($result ? 'OK' : 'FAIL');
}

function add_electricity_tariff($user) {
	App::set_content_type();

	$description                          = App::post('description', '', true);
	$supplier_id                          = App::post('supplier_id', 'NULL', true);
	$business_contract_notice_period_days = App::post('business_contract_notice_period_days', 0, true);
	$daily_standing_charge                = App::post('daily_standing_charge', 0, true);
	$unit_rate_day                        = App::post('unit_rate_day', 0, true);
	$unit_rate_night                      = App::post('unit_rate_night', 0, true);
	$reactive_power_rate_pounds_per_kva   = App::post('reactive_power_rate_pounds_per_kva', 0, true);
	$CCL_pence_per_unit                   = App::post('CCL_pence_per_unit', 0, true);
	$CCL_cost_pounds                      = App::post('CCL_cost_pounds', 0, true);
	$settlement_charges_pounds_per_year   = App::post('settlement_charges_pounds_per_year', 0, true);
	$excess_capacity_rate_pounds_per_kva  = App::post('excess_capacity_rate_pounds_per_kva', 0, true);
	$client_id                            = App::post('client_id', 0, true);

	$result = App::sql()->insert("INSERT INTO tariff_electricity
			(description, supplier_id, business_contract_notice_period_days, reactive_power_rate_pounds_per_kva, CCL_pence_per_unit, settlement_charges_pounds_per_year, excess_capacity_rate_pounds_per_kva, client_id,
			standard_tariff_non_dd_daily_standing_charge, standard_tariff_dd_daily_standing_charge, economy7_tariff_non_dd_daily_standing_charge, economy7_tariff_dd_daily_standing_charge,
			standard_tariff_non_dd_unit_rate, standard_tariff_dd_unit_rate, economy7_tariff_non_dd_unit_rate_day, economy7_tariff_dd_unit_rate_day,
			economy7_tariff_non_dd_unit_rate_night, economy7_tariff_dd_unit_rate_night)
		VALUES
			('$description', $supplier_id, $business_contract_notice_period_days, $reactive_power_rate_pounds_per_kva, $CCL_pence_per_unit, $settlement_charges_pounds_per_year, $excess_capacity_rate_pounds_per_kva, $client_id,
			$daily_standing_charge, $daily_standing_charge, $daily_standing_charge, $daily_standing_charge,
			$unit_rate_day, $unit_rate_day, $unit_rate_day, $unit_rate_day,
			$unit_rate_night, $unit_rate_night);
	");

	return App::encode_result($result ? 'OK' : 'FAIL', '', $result);
}

function update_electricity_tariff($user) {
	App::set_content_type();

	$tariff_id = App::post('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('FAIL', 'Invalid tariff #.');

	$description                          = App::post('description', '', true);
	$supplier_id                          = App::post('supplier_id', 'NULL', true);
	$business_contract_notice_period_days = App::post('business_contract_notice_period_days', 0, true);
	$daily_standing_charge                = App::post('daily_standing_charge', 0, true);
	$unit_rate_day                        = App::post('unit_rate_day', 0, true);
	$unit_rate_night                      = App::post('unit_rate_night', 0, true);
	$reactive_power_rate_pounds_per_kva   = App::post('reactive_power_rate_pounds_per_kva', 0, true);
	$CCL_pence_per_unit                   = App::post('CCL_pence_per_unit', 0, true);
	$CCL_cost_pounds                      = App::post('CCL_cost_pounds', 0, true);
	$settlement_charges_pounds_per_year   = App::post('settlement_charges_pounds_per_year', 0, true);
	$excess_capacity_rate_pounds_per_kva  = App::post('excess_capacity_rate_pounds_per_kva', 0, true);
	$client_id                            = App::post('client_id', 0, true);

	$result = App::sql()->update("UPDATE tariff_electricity SET
			description = '$description',
			supplier_id = $supplier_id,
			business_contract_notice_period_days = $business_contract_notice_period_days,
			reactive_power_rate_pounds_per_kva = $reactive_power_rate_pounds_per_kva,
			CCL_pence_per_unit = $CCL_pence_per_unit,
			settlement_charges_pounds_per_year = $settlement_charges_pounds_per_year,
			excess_capacity_rate_pounds_per_kva = $excess_capacity_rate_pounds_per_kva,
			standard_tariff_non_dd_daily_standing_charge = $daily_standing_charge,
			standard_tariff_dd_daily_standing_charge = $daily_standing_charge,
			economy7_tariff_non_dd_daily_standing_charge = $daily_standing_charge,
			economy7_tariff_dd_daily_standing_charge = $daily_standing_charge,
			standard_tariff_non_dd_unit_rate = $unit_rate_day,
			standard_tariff_dd_unit_rate = $unit_rate_day,
			economy7_tariff_non_dd_unit_rate_day = $unit_rate_day,
			economy7_tariff_dd_unit_rate_day = $unit_rate_day,
			economy7_tariff_non_dd_unit_rate_night = $unit_rate_night,
			economy7_tariff_dd_unit_rate_night = $unit_rate_night
		WHERE id = $tariff_id;
	");

	return App::encode_result($result ? 'OK' : 'FAIL');
}

function delete_electricity_tariff($user) {
	App::set_content_type();

	$tariff_id = App::post('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('FAIL', 'Invalid tariff #.');

	$result = App::sql()->delete("DELETE FROM tariff_electricity WHERE id = $tariff_id;");
	return App::encode_result($result ? 'OK' : 'FAIL');
}

function add_gas_tariff($user) {
	App::set_content_type();

	$description     = App::post('description', '', true);
	$supplier_id     = App::post('supplier_id', 'NULL', true);
	$standing_charge = App::post('standing_charge', 0, true);
	$cost_per_kwh    = App::post('cost_per_kwh', 0, true);
	$client_id       = App::post('client_id', 0, true);

	$result = App::sql()->insert("INSERT INTO tariff_gas
			(description, supplier_id, client_id,
			standing_charge_non_dd, standing_charge_dd,
			cost_per_kwh_non_dd, cost_per_kwh_dd)
		VALUES
			('$description', $supplier_id, $client_id,
			$standing_charge, $standing_charge,
			$cost_per_kwh, $cost_per_kwh);
	");

	return App::encode_result($result ? 'OK' : 'FAIL', '', $result);
}

function update_gas_tariff($user) {
	App::set_content_type();

	$tariff_id = App::post('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('FAIL', 'Invalid tariff #.');

	$description     = App::post('description', '', true);
	$supplier_id     = App::post('supplier_id', 'NULL', true);
	$standing_charge = App::post('standing_charge', 0, true);
	$cost_per_kwh    = App::post('cost_per_kwh', 0, true);
	$client_id       = App::post('client_id', 0, true);

	$result = App::sql()->update("UPDATE tariff_gas SET
			description = '$description',
			supplier_id = $supplier_id,
			standing_charge_non_dd = $standing_charge,
			standing_charge_dd = $standing_charge,
			cost_per_kwh_non_dd = $cost_per_kwh,
			cost_per_kwh_dd = $cost_per_kwh
		WHERE id = $tariff_id;
	");
	return App::encode_result($result ? 'OK' : 'FAIL');
}

function delete_gas_tariff($user) {
	App::set_content_type();

	$tariff_id = App::post('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('FAIL', 'Invalid tariff #.');

	$result = App::sql()->delete("DELETE FROM tariff_gas WHERE id = $tariff_id;");
	return App::encode_result($result ? 'OK' : 'FAIL');
}

function add_water_tariff($user) {
	App::set_content_type();

	$description                         = App::post('description', '', true);
	$water_supplier_id                   = App::post('water_supplier_id', 'NULL', true);
	$water_standing_charge_pence_per_day = App::post('water_standing_charge_pence_per_day', 0, true);
	$water_volumetric_charge_per_m3      = App::post('water_volumetric_charge_per_m3', 0, true);
	$waste_supplier_id                   = App::post('waste_supplier_id', 'NULL', true);
	$waste_standing_charge               = App::post('waste_standing_charge', 0, true);
	$waste_volumetric_charge_per_m3      = App::post('waste_volumetric_charge_per_m3', 0, true);
	$client_id                           = App::post('client_id', 0, true);

	$result = App::sql()->insert("INSERT INTO tariff_water
			(description, client_id,
			water_supplier_id, water_standing_charge_pence_per_day, water_volumetric_charge_per_m3,
			waste_supplier_id, waste_standing_charge, waste_volumetric_charge_per_m3)
		VALUES
			('$description', $client_id,
			$water_supplier_id, $water_standing_charge_pence_per_day, $water_volumetric_charge_per_m3,
			$waste_supplier_id, $waste_standing_charge, $waste_volumetric_charge_per_m3);
	");

	return App::encode_result($result ? 'OK' : 'FAIL', '', $result);
}

function update_water_tariff($user) {
	App::set_content_type();

	$tariff_id = App::post('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('FAIL', 'Invalid tariff #.');

	$description                         = App::post('description', '', true);
	$water_supplier_id                   = App::post('water_supplier_id', 'NULL', true);
	$water_standing_charge_pence_per_day = App::post('water_standing_charge_pence_per_day', 0, true);
	$water_volumetric_charge_per_m3      = App::post('water_volumetric_charge_per_m3', 0, true);
	$waste_supplier_id                   = App::post('waste_supplier_id', 'NULL', true);
	$waste_standing_charge               = App::post('waste_standing_charge', 0, true);
	$waste_volumetric_charge_per_m3      = App::post('waste_volumetric_charge_per_m3', 0, true);
	$client_id                           = App::post('client_id', 0, true);

	$result = App::sql()->update("UPDATE tariff_water SET
			description = '$description',
			water_supplier_id = $water_supplier_id,
			water_standing_charge_pence_per_day = $water_standing_charge_pence_per_day,
			water_volumetric_charge_per_m3 = $water_volumetric_charge_per_m3,
			waste_supplier_id = $waste_supplier_id,
			waste_standing_charge = $waste_standing_charge,
			waste_volumetric_charge_per_m3 = $waste_volumetric_charge_per_m3
		WHERE id = $tariff_id;
	");

	return App::encode_result($result ? 'OK' : 'FAIL');
}

function delete_water_tariff($user) {
	App::set_content_type();

	$tariff_id = App::post('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('FAIL', 'Invalid tariff #.');

	$result = App::sql()->delete("DELETE FROM tariff_water WHERE id = $tariff_id;");
	return App::encode_result($result ? 'OK' : 'FAIL');
}

function add_agent($user) {
	App::set_content_type();

	$name             = App::post('name', '', true);
	$email_address    = App::post('email_address', '', true);
	$office_address   = App::post('office_address', '', true);
	$postcode         = App::post('postcode', '', true);
	$telephone_number = App::post('telephone_number', '', true);
	$mobile_number    = App::post('mobile_number', '', true);
	$client_id        = App::post('client_id', 0, true);
	$building_id      = App::post('building_id', 0, true);

	$result = App::sql()->insert("INSERT INTO agent (name, email_address, office_address, postcode, telephone_number, mobile_number, client_id) VALUES ('$name', '$email_address', '$office_address', '$postcode', '$telephone_number', '$mobile_number', $client_id);");
	if($result && $building_id) {
		$building = new Building($building_id);
		if ($building->validate()) {
			$result = App::sql()->insert("INSERT INTO agent_building (agent_id, building_id) VALUES ($result, $building_id);");
		}
	}

	return App::encode_result($result ? 'OK' : 'FAIL');
}

function update_agent($user) {
	App::set_content_type();

	$agent_id = App::post('agent_id', null, true);
	if(!$agent_id) return App::encode_result('FAIL', 'Invalid agent #.');

	$name             = App::post('name', '', true);
	$email_address    = App::post('email_address', '', true);
	$office_address   = App::post('office_address', '', true);
	$postcode         = App::post('postcode', '', true);
	$telephone_number = App::post('telephone_number', '', true);
	$mobile_number    = App::post('mobile_number', '', true);

	$result = App::sql()->update("UPDATE agent SET name = '$name', email_address = '$email_address', office_address = '$office_address', postcode = '$postcode', telephone_number = '$telephone_number', mobile_number = '$mobile_number' WHERE id = $agent_id;");

	return App::encode_result($result ? 'OK' : 'FAIL');
}

function assign_agent_buildings($user) {
	App::set_content_type();

	$agent_id     = App::post('agent_id', 0, true);
	$building_ids = isset($_POST['buildings']) ?  $_POST['buildings'] : [];

	// Remove all assigned buildings (but only the ones user has access to)
	$agent_info = App::sql()->query_row("SELECT client_id FROM agent WHERE id = '$agent_id';");
	if($agent_info) {
		$client_id = $agent_info->client_id;

		$building_select = Permission::select([ 'level' => PermissionLevel::BUILDING, 'with' => Permission::BUILDING_ENABLED, 'filter_level' => PermissionLevel::CLIENT, 'id' => $client_id ]);
		App::sql()->delete("DELETE FROM agent_building WHERE agent_id = '$agent_id' AND building_id IN (SELECT building_id FROM ($building_select) AS subquery);");

		foreach ($building_ids as $building_id) {
			$id = App::escape($building_id);
			App::sql()->insert("INSERT INTO agent_building (agent_id, building_id) VALUES ('$agent_id', '$id');");
		}
	}

	return App::encode_result('OK');
}

function assign_building_agents($user) {
	App::set_content_type();

	$building_id = App::post('building_id', 0, true);
	$agent_ids   = isset($_POST['agents']) ?  $_POST['agents'] : [];

	$building = new Building($building_id);
	if(!$building->validate()) return App::encode_result('FAIL', 'Access denied.');

	// Remove all assigned agents
	App::sql()->delete("DELETE FROM agent_building WHERE building_id = '$building_id';");

	foreach ($agent_ids as $agent_id) {
		$id = App::escape($agent_id);
		App::sql()->insert("INSERT INTO agent_building (agent_id, building_id) VALUES ('$id', '$building_id');");
	}

	return App::encode_result('OK');
}
