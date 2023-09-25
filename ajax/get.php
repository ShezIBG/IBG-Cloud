<?php

/*
 * This page is the main document to grab data from the database, All these functions don't update anything, nor set anything in the database.
 *
 * This is part of the old framework. eticomcloud.com/ajax/get/function points here, which will call the named function in this file.
 *
 * Each function starts off with App::set_content_type() - This tells javascript they are output as json. You can find the function in /lib/class.app.php
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

$get_func = key($GLOBALS["_GET"]);
if(in_array($get_func, ['print_contract', 'print_customer_invoice'])) {
	// No user auth for this endpoint
	require_once '../inc/init.app.php';
	$user = null;
} else {
	// inititate the ajax, setup the sessions and active instances of classes.
	require_once 'init.ajax.php';
	if (!$user) {
		// If there isn't a user session then don't call any functions.
		exit(die("Unable to continue with the query."));
	}
}

$get_func = key($GLOBALS["_GET"]);
echo function_exists($get_func) ? call_user_func($get_func, $user) : null;

/**
 * This funtion filters the available reports to a user based on their selection.
 */
function report_type_change($user) {
	App::set_content_type();

	list($building_id, $type, $report_type, $filter, $yr, $mo, $dy, $wk) = App::get(['id', 'type', 'report_type', 'filter', 'yr', 'mo', 'dy', 'wk'], '', true);

	// Check if user has access to the building
	if(!Permission::get_building($building_id)->check(Permission::REPORTS_ENABLED)) {
		$result = false;
	} else if($building_id) {
		$r_type = Report::type_switch($report_type);

		switch($type) {
			default:
			case 'choose_report':
				//create a filter for report types from the database. - dropdown is coded in the main output of the /view/reports.php page.
				if(isset($filter)) {
					// The date filter is set
					if($filter == 'year') {
						$date_filter = " AND `year` = '$yr' ";
					} else if($filter == 'month') {
						$date_filter = " AND `year` = '$yr' AND month = '$mo' ";
					} else if($filter == 'day') {
						$date_filter = " AND `year` = '$yr' AND month = '$mo' AND day = '$dy' ";
					} else if($filter == 'week') {
						$date_filter = " AND `year` = '$yr' AND month = '$mo' AND week_number = '$wk' ";
					} else {
						$date_filter = "";
					}
				} else {
					$date_filter = "";
				}

				// create a dropdown of reports - $r_type->report_description uses CONCAT to combine 3 fields in to one string (currently in year - month - day order.)
				$array2 = is_array($result = App::sql()->query("SELECT ". $r_type->report_description ." as description, report_history.id as value, directory, tag FROM `report_history` WHERE building_id = '$building_id' AND report_type = '$r_type->report_type' ". $date_filter ." ORDER BY report_history.year DESC, report_history.month DESC, report_history.day DESC")) ? $result : [];
				$field = ($type == '') ? [(object)[ 'value' => '', 'description' => 'Please select a report type' ]] : array_merge([(object)[ 'value' => '', 'description' => 'Please select a report to view.' ]], $array2);
				break;

			case 'filter_year':
				$array2 = is_array($result = App::sql()->query("SELECT DISTINCT(report_history.year) as description, report_history.year as value, report_history.year as directory FROM `report_history` WHERE building_id = '$building_id' AND report_type = '$r_type->report_type' ORDER BY report_history.year DESC")) ? $result : []; //Filters down the date fields
				$field = ($type == '') ? [(object)[ 'value' => '', 'description' => 'Filter By Year']] : array_merge([(object)[ 'value' => '', 'description' => 'Filter By Year' ]], $array2);
				break;

			case 'filter_month':
				$array2 = is_array($result = App::sql()->query("SELECT DISTINCT(report_history.month) as description, report_history.month as value, report_history.month as directory, month FROM `report_history` WHERE building_id = '$building_id' AND report_type = '$r_type->report_type' AND year = '$yr' ORDER BY report_history.month DESC")) ? $result : []; //Filters down the date fields
				$field = ($type == '') ? [(object)[ 'value' => '', 'description' => 'Filter By Month' ]] : array_merge([(object)[ 'value' => '', 'description' => 'Filter By Month' ]], $array2);
				break;

			case 'filter_day':
				$array2 = is_array($result = App::sql()->query("SELECT DISTINCT(report_history.day) as description, report_history.day as value FROM `report_history` WHERE building_id = '$building_id' AND report_type = '$r_type->report_type' AND year = '$yr' AND month = '$mo' ORDER BY report_history.day DESC")) ? $result : []; //Filters down the date fields
				$field = ($type == '') ? [(object)[ 'value' => '', 'description' => 'Filter By Day' ]] : array_merge([(object)[ 'value' => '', 'description' => 'Filter By Day' ]], $array2);
				break;

			case 'filter_week':
				$array2 = is_array($result = App::sql()->query("SELECT DISTINCT(report_history.week_number) as description, report_history.week_number as value FROM `report_history` WHERE building_id = '$building_id' AND report_type = '$r_type->report_type' AND year = '$yr' AND month = '$mo' ORDER BY report_history.year DESC, report_history.month DESC, report_history.day DESC")) ? $result : []; //This is shown when selected an end of week report, filters by week numbers.
				$field = ($type == '') ? [(object)[ 'value' => '', 'description' => 'Filter By Week' ]] : array_merge([(object)[ 'value' => '', 'description' => 'Filter By Week' ]], $array2);
				break;
		}

		if($field) {
			$string = '';
			if(count($field) > 1) {
				foreach($field as $item) {
					$description = $item->description;
					if(isset($item->month) && $description == $item->month) {
						$dateObj = DateTime::createFromFormat('!m', $item->month);
						$monthName = $dateObj->format('F');
						$description = $monthName;
					} else if(isset($item->tag) && $item->tag) {
						$description .= " ($item->tag)";
					} else if(isset($item->directory) && preg_match("/by_hour/i", $item->directory)) {
						// The file name of the document contains 'by_hour', it's hourly data in the document.
						$description = $description . " (Hourly)";
					}

					$string .= "<option value='".($item->value ? $item->value : '')."'> {$description} </option>";
				}
				$result = [ 'html' => $string ];
			} else {
				$result = false;
				$result_message = "Unable to find any reports for the selected type.";
			}
		} else {
			$result = false;
		}
	} else {
		$result = false;
	}

	return App::encode_result($result ? 'OK' : 'FAIL', $result ? 'Data found' : (isset($result_message) ? $result_message : 'Unable to find data'), $result ? $result : []);
}

function html_building_manager_unit_list($user) {
	App::set_content_type();

	$building_id = App::get('building_id', '', true);
	if(!$building_id) return App::encode_result('OK', 'Success', '');

	$building = new Building($building_id);

	return App::encode_result('OK', 'Success', $building->html_building_manager_unit_list());
}

function html_building_manager_tenant_list($user) {
	App::set_content_type();

	$building_id = App::get('building_id', '', true);
	if(!$building_id) return App::encode_result('OK', 'Success', '');

	$building = new Building($building_id);

	return App::encode_result('OK', 'Success', $building->html_building_manager_tenant_list());
}

function html_building_manager_agent_list($user) {
	App::set_content_type();

	$building_id = App::get('building_id', '', true);
	if(!$building_id) return App::encode_result('OK', 'Success', '');

	$building = new Building($building_id);

	return App::encode_result('OK', 'Success', $building->html_building_manager_agent_list($building_id));
}

function building_manager_fields($user) {
	App::set_content_type();

	$building_id = App::get('building_id', null, true);
	if(!$building_id) {
		return App::encode_result('FAIL', 'Invalid parameters.', null);
	}

	$building = new Building($building_id);
	if (!$building->validate()) {
		return App::encode_result('FAIL', 'Access denied.', null);
	}

	$area_list = $building->get_tenanted_areas(
		[ 'building_id' => '= '.$building->id ],
		['tenant.id as tenant_id', 'area.description as unit_name', 'floor.description as floor_name', 'tenant.name as tenant_name', 'area.id as area_id'],
		['INNER JOIN floor ON floor.id = area.floor_id', 'LEFT JOIN tenant ON tenant.id = tenant_id'],
		'ORDER BY floor.display_order, area.display_order'
	);
	$occupied_list = $building->get_tenanted_areas(
		[ 'building_id' => '= '.$building->id, 'tenant_id' => 'IS NOT NULL' ],
		[],
		['INNER JOIN floor ON floor.id = area.floor_id']
	);

	$total_units = $area_list ? count($area_list) : 0;
	$occupied_units = $occupied_list ? count($occupied_list) : 0;

	return App::encode_result('OK', 'Success', [
		'total_units'    => $total_units,
		'occupied_units' => $occupied_units,
		'vacant_units'   => $total_units - $occupied_units
	]);
}

/**
 * Used to download the report from the server.
 */
function get_report($user) {
	list($id, $view) = App::get(['id', 'view'], '');
	return Report::get_report($id, ($view == '' ? 'attachment' : 'inline'));
}

/**
 * Used to download the report from the server.
 */
function print_stock_location($user) {
	$warehouse_id = App::get('warehouse', '', true);
	$download = isset($_GET['download']) ? 'attachment' : 'inline';
	if(!$warehouse_id) return '';

	$record = App::select('stock_warehouse', $warehouse_id);
	if(!$record) return '';
	if(!Permission::get($record['owner_level'], $record['owner_id'])->check(Permission::STOCK_ENABLED)) return '';

	$rack = App::get('rack', '', true);
	$bay = App::get('bay', '', true);
	$level = App::get('level', '', true);

	$tag = [$warehouse_id];
	if($rack) $tag[] = $rack;
	if($bay) $tag[] = $bay;
	if($level) $tag[] = $level;
	$tag = implode('-', $tag);

	$url = "/print/print.php?type=stock_location&warehouse={$warehouse_id}&rack={$rack}&bay={$bay}&level={$level}";
	$filename = "project-stock-location-{$tag}.pdf";

	return Report::generate_pdf_report($url, $filename, $download, '', '', '-T 5mm -B 5mm -L 5mm -R 5mm');
}

/**
 * Used to download the report from the server.
 */
function print_project_proposal($user) {
	$id = App::get('id');
	$download = isset($_GET['download']) ? 'attachment' : 'inline';
	$hide_labour = App::get('hide_labour', 0);
	if(!$id) return '';

	$p = new Project($id);
	
	if(!$p->validate() || !$p->can_show_pricing()) return '';

	$url = "/print/print.php?type=proposal&project_id={$id}&hide_labour={$hide_labour}";
	
	$project_name = $p->info['description'] ? $p->info['description'] : 'Project';
	$project_no = $p->info['project_no'];
	$doc_type = 'Proposal';
	$filename = App::safe_string("$project_name (project $project_no) - $doc_type").'.pdf';
	
	return Report::generate_pdf_report($url, $filename, $download);
}

/**
 * Used to download the report from the server.
 */
function print_project_quotation($user) {
	$id = App::get('id');
	$variant = App::get('variant', '');
	$download = isset($_GET['download']) ? 'attachment' : 'inline';
	$hide_labour = App::get('hide_labour', 0);
	if(!$id) return '';

	$p = new Project($id);
	if(!$p->validate() || !$p->can_show_pricing()) return '';

	$url = "/print/print.php?type=quotation&variant={$variant}&project_id={$id}&hide_labour={$hide_labour}";
	$header_url = "/print/print.php?type=quotation_header&project_id={$id}&hide_labour={$hide_labour}";
	$footer_url = "/print/print.php?type=quotation_footer&project_id={$id}";

	$project_name = $p->info['description'] ? $p->info['description'] : 'Project';
	$project_no = $p->info['project_no'];
	$doc_type = 'Quotation';
	if($variant) {
		$filename = App::safe_string("$project_name (project $project_no) - $doc_type ($variant)").'.pdf';
	} else {
		$filename = App::safe_string("$project_name (project $project_no) - $doc_type").'.pdf';
	}

	return Report::generate_pdf_report($url, $filename, $download, $header_url, $footer_url, '-T 44mm -B 18mm');
}

/**
 * Used to download the report from the server.
 */
function print_project_area_summary($user) {
	$id = App::get('id');
	$download = isset($_GET['download']) ? 'attachment' : 'inline';
	$hide_labour = App::get('hide_labour', 0);
	if(!$id) return '';

	$p = new Project($id);
	if(!$p->validate() || !$p->can_show_pricing()) return '';

	$url = "/print/print.php?type=area_summary&project_id={$id}&hide_labour={$hide_labour}";
	$header_url = "/print/print.php?type=area_summary_header&project_id={$id}&hide_labour={$hide_labour}";
	$footer_url = "/print/print.php?type=area_summary_footer&project_id={$id}";

	$project_name = $p->info['description'] ? $p->info['description'] : 'Project';
	$project_no = $p->info['project_no'];
	$doc_type = 'Area Summary';
	$filename = App::safe_string("$project_name (project $project_no) - $doc_type").'.pdf';

	return Report::generate_pdf_report($url, $filename, $download, $header_url, $footer_url, '-T 40mm -B 18mm');
}

/**
 * Used to download the report from the server.
 */
function print_invoice($user) {
	$id = App::get('id', 0, true);
	$download = isset($_GET['download']) ? 'attachment' : 'inline';
	if(!$id) return '';

	$invoice = App::select('invoice', $id);
	if(!$invoice) return '';

	if($invoice['owner_type'] === 'SI') {
		$isp = new ISP($invoice['owner_id']);
		if(!$isp->validate()) return '';
	} else {
		if(!Permission::get($invoice['owner_type'], $invoice['owner_id'])->check(Permission::ADMIN)) return '';
	}

	$url = "/print/print.php?type=invoice&invoice_id=$id";
	$invoice_no = $invoice['invoice_no'];
	$filename = "invoice-{$invoice_no}.pdf";

	return Report::generate_pdf_report($url, $filename, $download);
}

function print_customer_invoice($user) {
	$id = App::get('id', 0, true);
	$token = App::get('token', '', true);
	$invoice_id = App::get('invoice', 0, true);
	$download = isset($_GET['download']) ? 'attachment' : 'inline';
	if(!$id) return '';

	$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
	if(!$pa) return '';

	$invoice = App::select('invoice', $invoice_id);
	if(!$invoice) return '';

	if($invoice['customer_type'] !== $pa['customer_type'] || $invoice['customer_id'] !== $pa['customer_id']) return '';

	$url = "/print/print.php?type=invoice&invoice_id=$invoice_id";
	$invoice_no = $invoice['invoice_no'];
	$filename = "invoice-{$invoice_no}.pdf";

	return Report::generate_pdf_report($url, $filename, $download);
}

function customers_in_arrears_csv($user) {
	$owner_type = App::get('owner_type', '', true);
	$owner_id = App::get('owner_id', '', true);

	if(!Permission::get($owner_type, $owner_id)->check(Permission::BILLING_ENABLED)) return '';

	$list = App::sql()->query(
		"SELECT
			c.id AS 'Customer ID',
			c.contact_name AS 'Contact Name',
			c.company_name AS 'Company Name',
			c.reference_no AS 'Reference No',
			c.email_address AS 'Email Address',
			COALESCE(acs.active_contract_count, 0) AS 'Active Contracts',
			COALESCE(cc.cc_ok, 0) AS 'Card Saved?',
			COALESCE(dd.dd_ok, 0) AS 'Direct Debit?',
			acs.active_contract_area AS 'Active Contract Area',
			acs.active_contract_description AS 'Active Contract Description',
			COALESCE(tx.balance, 0) AS 'Balance',
			COALESCE(tx.pending, 0) AS 'Pending',
			-(COALESCE(tx.outstanding, 0)) AS 'Outstanding',
			COALESCE(inv.oustanding_invoice_count, 0) AS 'O/S Invoices',
			inv.last_outstanding_invoice_date AS 'Last O/S Invoice Date',
			inv.first_outstanding_invoice_date AS 'First O/S Invoice Date',
			tx.last_payment_date AS 'Last Payment Date'
		FROM customer AS c

		LEFT JOIN payment_account AS pa ON pa.owner_type = c.owner_type AND pa.owner_id = c.owner_id AND pa.customer_type = 'CU' AND pa.customer_id = c.id
		LEFT JOIN (
			SELECT
				tpa.id,
				SUM(IF(ttx.status = 'ok', ttx.amount, 0)) AS balance,
				SUM(IF(ttx.status = 'pending', ttx.amount, 0)) AS pending,
				SUM(IF(ttx.status = 'ok' OR ttx.status = 'pending', ttx.amount, 0)) AS outstanding,
				MAX(IF(ttx.amount > 0 AND ttx.status = 'ok', CAST(ttx.create_datetime AS DATE), NULL)) AS last_payment_date
			FROM payment_account AS tpa
			JOIN payment_transaction AS ttx ON ttx.account_id = tpa.id
			WHERE tpa.owner_type = '$owner_type' AND tpa.owner_id = '$owner_id' AND tpa.customer_type = 'CU'
			GROUP BY tpa.id
		) AS tx ON pa.id = tx.id

		LEFT JOIN (
			SELECT
				ccc.customer_id,
				MAX(IF(ccc.stripe_customer IS NULL OR ccc.stripe_customer = '' OR ccc.last4 IS NULL OR ccc.last4 = '', 0, 1)) AS cc_ok
			FROM payment_stripe_card AS ccc
			JOIN payment_gateway AS ccpg ON ccpg.id = ccc.payment_gateway_id AND ccpg.owner_type = '$owner_type' AND ccpg.owner_id = '$owner_id'
			WHERE ccc.customer_type = 'CU'
			GROUP BY ccc.customer_id
		) AS cc ON cc.customer_id = c.id

		LEFT JOIN (
			SELECT
				ddm.customer_id,
				MAX(IF(ddm.status = 'authorised', 1, 0)) AS dd_ok
			FROM payment_gocardless_mandate AS ddm
			JOIN payment_gateway AS ddpg ON ddpg.id = ddm.payment_gateway_id AND ddpg.owner_type = '$owner_type' AND ddpg.owner_id = '$owner_id'
			WHERE ddm.customer_type = 'CU'
			GROUP BY ddm.customer_id
		) AS dd ON dd.customer_id = c.id

		LEFT JOIN (
			SELECT
				cn_c.customer_id,
				GROUP_CONCAT(DISTINCT CONCAT(cn_b.description, ' / ', cn_a.description) ORDER BY cn_b.description, cn_f.display_order, cn_a.display_order SEPARATOR '\\n') AS active_contract_area,
				GROUP_CONCAT(DISTINCT cn_c.description ORDER BY cn_b.description, cn_f.display_order, cn_a.display_order SEPARATOR '\\n') AS active_contract_description,
				COUNT(cn_c.id) AS active_contract_count,
				MIN(cn_b.id * 1000 * 1000 + cn_f.display_order * 1000 + cn_a.display_order) AS display_order
			FROM contract AS cn_c
			LEFT JOIN area AS cn_a ON cn_a.id = cn_c.area_id
			LEFT JOIN floor AS cn_f ON cn_f.id = cn_a.floor_id
			LEFT JOIN building AS cn_b ON cn_b.id = cn_f.building_id
			WHERE cn_c.owner_type = '$owner_type' AND cn_c.owner_id = '$owner_id' AND cn_c.customer_type = 'CU' AND cn_c.status IN ('active', 'ending')
			GROUP BY cn_c.customer_id
		) AS acs ON acs.customer_id = c.id

		LEFT JOIN (
			SELECT
				inv_i.customer_id,
				COUNT(*) AS oustanding_invoice_count,
				MAX(bill_date) AS last_outstanding_invoice_date,
				MIN(bill_date) AS first_outstanding_invoice_date
			FROM invoice AS inv_i
			WHERE inv_i.owner_type = '$owner_type' AND inv_i.owner_id = '$owner_id' AND inv_i.customer_type = 'CU' AND inv_i.status = 'outstanding'
			GROUP BY inv_i.customer_id
		) AS inv ON inv.customer_id = c.id

		WHERE c.owner_type = '$owner_type' AND c.owner_id = '$owner_id' AND c.archived = '0' AND tx.balance < 0
		ORDER BY acs.display_order, c.contact_name, c.company_name;
	", MySQL::QUERY_ASSOC);

	$data = App::array_to_csv($list ?: [], true);

	$filename = 'customers-in-arrears';
	$d = date('Y-m-d');
	$filename .= "-({$d}).csv";

	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=$filename");
	return $data;
}

function invoice_csv_export($user) {
	$data_isp = App::get('isp', '', true);
	$data_date_from = App::get('date_from', '', true);
	$data_date_to = App::get('date_to', '', true);
	$data_status = App::get('status', '', true);
	$data_customer = App::get('customer', '', true);

	if(!$data_isp) return '';

	$isp = new ISP($data_isp);
	if(!$isp->validate()) return '';

	$filters = [];
	if($data_date_from) $filters[] = "AND i.bill_date >= '$data_date_from'";
	if($data_date_to) $filters[] = "AND i.bill_date <= '$data_date_to'";
	if($data_status) {
		$data_status = explode(',', $data_status);
		$filters[] = "AND i.status IN ('".implode("','", $data_status)."')";
	}
	$filters = implode(' ', $filters);

	if($data_customer) {
		$customer = new Customer($data_customer);
		if(!$customer->validate()) return '';

		$list = App::sql()->query(
			"SELECT
				'SI' AS 'Type',
				COALESCE(c.reference_no, '') AS 'Account Reference',
				'4000' AS 'Nominal A/C Ref',
				'0' AS 'Department Code',
				i.bill_date AS 'Date',
				i.invoice_no AS 'Reference',
				i.description AS 'Details',
				i.subtotal AS 'Net Amount',
				'T1' AS 'Tax Code',
				i.vat_due AS 'Tax Amount'
			FROM invoice AS i
			LEFT JOIN customer AS c ON i.customer_type = 'CU' AND c.id = i.customer_id
			WHERE i.owner_type = 'SI' AND i.owner_id = '$isp->id' AND i.customer_type = 'CU' AND i.customer_id = '$customer->id'
			$filters
			ORDER BY i.bill_date DESC, i.id DESC;
		", MySQL::QUERY_ASSOC);
	} else {
		$list = App::sql()->query(
			"SELECT
				'SI' AS 'Type',
				COALESCE(c.reference_no, '') AS 'Account Reference',
				'4000' AS 'Nominal A/C Ref',
				'0' AS 'Department Code',
				i.bill_date AS 'Date',
				i.invoice_no AS 'Reference',
				i.description AS 'Details',
				i.subtotal AS 'Net Amount',
				'T1' AS 'Tax Code',
				i.vat_due AS 'Tax Amount'
			FROM invoice AS i
			LEFT JOIN customer AS c ON i.customer_type = 'CU' AND c.id = i.customer_id
			WHERE i.owner_type = 'SI' AND i.owner_id = '$isp->id'
			$filters
			ORDER BY i.bill_date DESC, i.id DESC;
		", MySQL::QUERY_ASSOC);
	}

	$data = App::array_to_csv($list ?: [], false);

	$filename = 'sage-export';
	$d = date('Y-m-d');
	$filename .= "-({$d}).csv";

	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=$filename");
	return $data;
}
/**
 * Generate a CSV export of all customers
 */
function customer_csv_export($user) {
	$list = App::sql()->query(
		"SELECT id AS account_reference, 
		IF(reference_no IS NOT NULL, TRIM(BOTH FROM reference_no), '') AS ref_no,
		IF(contact_name='',TRIM(BOTH FROM company_name), IF(company_name='',TRIM(BOTH FROM contact_name),CONCAT(contact_name,'/',company_name))) AS account_Name, 
		IF(address_line_1 IS NOT NULL, TRIM(BOTH FROM address_line_1),'') AS street_1, 
		IF(address_line_2 IS NOT NULL, TRIM(BOTH FROM address_line_2), '') AS street_2, 
		IF(address_line_3 IS NOT NULL, TRIM(BOTH FROM address_line_3),'') AS town, 
		IF(posttown IS NOT NULL, TRIM(BOTH FROM posttown),'') AS county,postcode, 
		'' AS contact_name,
		IF(mobile_number IS NULL, IF(phone_number IS NULL,'',TRIM(BOTH FROM phone_number) ),TRIM(BOTH FROM mobile_number)) AS telephone_number, 
		IF(email_address IS NOT NULL, TRIM(BOTH FROM email_address),'') AS email
		
		FROM `customer` 
		WHERE owner_type='SI' AND owner_id=4 AND archived=0 ORDER BY contact_name", MySQL::QUERY_ASSOC);

		$data = App::array_to_csv($list ?: [], false);

		$filename = 'customer-export';
		$d = date('Y-m-d');
		$filename .= "-({$d}).csv";

		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=$filename");
		return $data;
}


function get_monthly_building_cost(){

	$user = User::check_login_session(isset($redirect_on_error) ? $redirect_on_error : true, isset($is_ajax_request) ? $is_ajax_request : false);
    
    $User_id = $user->info->id;
    $building = $user->get_default_building(Permission::METERS_ENABLED, '', true);
    $area_access = $building->get_area_ids_with_permission(Permission::METERS_ENABLED);
    if($area_access) $area_access = '('.implode(',', $area_access).')';

    $building_access = Permission::get_building($building->id)->check(Permission::METERS_ENABLED);


    $meter_list = $building->get_meters(['floor.building_id' => "='$building->id'", 'COALESCE(virtual_area_id, area_id)' => "IN $area_access" ]);
	
	return $meter_list;


}

function get_half_hour_data(){
	//Get User Info
	$user = User::check_login_session(isset($redirect_on_error) ? $redirect_on_error : true, isset($is_ajax_request) ? $is_ajax_request : false);
	$User_id = $user->info->id;
	
	$building = $user->get_default_building(Permission::METERS_ENABLED, '', true);
	$area_access = $building->get_area_ids_with_permission(Permission::METERS_ENABLED);
	if($area_access) $area_access = '('.implode(',', $area_access).')';
	
	$building_access = Permission::get_building($building->id)->check(Permission::METERS_ENABLED);
	
	
	$meter_list = $building->get_meters(['floor.building_id' => "='$building->id'", 'COALESCE(virtual_area_id, area_id)' => "IN $area_access" ]);
	//$meter_list[0]->id;

	echo'<html>
	<head>
	<link rel="stylesheet" type="text/css" media="screen" href="'.ASSETS_URL.'/css/mystyle.css">
	<link rel="stylesheet" type="text/css" media="screen" href="'.ASSETS_URL.'/css/newstyle.css">
	</head>
	<body>
	<p class="myWidget-title">Download Custom Data Reports</p>
		<form action="../custom_chart.php" method="post">
			<label class="col-md-2 control-label">Select Date From:</label>
				<input type="date" name="mm_datefrom"><br>
			<label class="col-md-3 control-label">Select Date To:</label>
				<input type="date" name="mm_dateto"><br>
			<input type="submit" value="Submit" >
			
	
	
	';

	foreach ($meter_list as $value){

		echo "<br/><input type='checkbox' class='myWidget-title' name='meter_list[]' value='$value->id' />$value->description<br>";
	};

	echo
	'</form>
	</body>
	</html>';

	//get_meter_list($meter_list);
	
}
/**
 * Used to generate and download meter reports from the server.
 */
function get_meter_report($user) {
	$data = '';

	list($report_type, $meter_type, $meter_direction) = App::get(['type', 'meter_type', 'meter_direction'], '', true);
	list($building_id, $meter_id) = App::get(['building_id', 'meter_id'], 0, true);

	list($main_type) = explode('-', $report_type);
	if($main_type === 'building') {
		$building = new Building($building_id);
		if(!$building->validate()) return '';
	} else if($main_type === 'meter') {
		if(!$meter_id) return '';
		$meter = new Meter($meter_id);
		if(!$meter->validate($building_id)) return '';
		$assigned_area = $meter->info->virtual_area_id ?: $meter->info->area_id;
		if(!Permission::get_area($assigned_area)->has_access()) return '';
	}

	$report_method = 'csv';
	$filename = '';
	$url = '';

	switch($report_type) {
		case 'building-utility-summary':
			$param = App::get('param', 0, true);
			if(!$param) return '';
			$report_method = 'pdf';
			$url = "/print/print.php?type=utility_summary&building_id=$building_id&period=$param";
			$filename = "utility-summary-building-{$building_id}-{$param}.pdf";
			$data = true;
			break;

		case 'building-periods':
			$fields = [];
			if(!$meter_type || $meter_type == 'E') $fields[] = "SUM(IF(m.meter_type = 'E', mp.usage_1 + mp.usage_2 + mp.usage_3, 0)) AS `Electricity Usage (kWh)`";
			if(!$meter_type || $meter_type == 'G') $fields[] = "SUM(IF(m.meter_type = 'G', mp.usage_1 + mp.usage_2 + mp.usage_3, 0)) AS `Gas Usage (m3)`";
			if(!$meter_type || $meter_type == 'W') $fields[] = "SUM(IF(m.meter_type = 'W', mp.usage_1 + mp.usage_2 + mp.usage_3, 0)) AS `Water Usage (m3)`";
			if(!$meter_type || $meter_type == 'H') $fields[] = "SUM(IF(m.meter_type = 'H', mp.usage_1 + mp.usage_2 + mp.usage_3, 0)) AS `Heat Usage (kWh)`";
			$fields = implode(', ', $fields);
			$meter_type_condition = $meter_type ? "AND m.meter_type = '$meter_type'" : '';
			if($meter_direction) $meter_type_condition = "$meter_type_condition AND m.meter_direction = '$meter_direction'";

			$result = App::sql()->query(
				"SELECT
					mp.year AS `Year`,
					mp.month AS `Month`,
					$fields
				FROM meter_period AS mp
				JOIN meter AS m ON mp.meter_id = m.id
				JOIN area AS a ON a.id = m.area_id
				JOIN floor AS f ON f.id = a.floor_id AND f.building_id = '$building_id'
				WHERE mp.complete = 1 $meter_type_condition
				GROUP BY mp.year, mp.month
				ORDER BY mp.year, mp.month;
			", MySQL::QUERY_ASSOC);
			if($result) $data = App::array_to_csv($result);
			break;

		case 'building-readings':
			$meter_type_condition = $meter_type ? "WHERE m.meter_type = '$meter_type'" : '';
			if($meter_direction) {
				if($meter_type_condition) {
					$meter_type_condition = "$meter_type_condition AND m.meter_direction = '$meter_direction'";
				} else {
					$meter_type_condition = "WHERE m.meter_direction = '$meter_direction'";
				}
			}

			$result = App::sql()->query(
				"SELECT
					mr.meter_id AS `Meter ID`,
					m.meter_type AS `Meter Type`,
					m.description AS `Meter Description`,
					mr.reading_date AS `Reading Date`,
					mr.reading_1 AS `Reading 1 (Day)`,
					mr.reading_2 AS `Reading 2 (Night)`,
					mr.reading_3 AS `Reading 3 (Evening/Weekend)`,
					COALESCE(mr.reading_total, COALESCE(mr.reading_1, 0) + COALESCE(mr.reading_2, 0) + COALESCE(mr.reading_3, 0)) AS `Reading Total`,
					IF(mr.initial_reading = 1, 'Yes', 'No') AS `Initial reading?`
				FROM meter_reading AS mr
				JOIN meter AS m ON mr.meter_id = m.id
				JOIN area AS a ON a.id = m.area_id
				JOIN floor AS f ON f.id = a.floor_id AND f.building_id = '$building_id'
				$meter_type_condition
				ORDER BY mr.reading_date;
			", MySQL::QUERY_ASSOC);
			if($result) $data = App::array_to_csv($result);
			break;

		case 'building-tariffs':
			$meter_type_condition = $meter_type ? "AND m.meter_type = '$meter_type'" : '';

			$result = App::sql()->query(
				"SELECT
					m.id AS `Meter ID`,
					m.meter_type AS `Meter Type`,
					m.description AS `Meter Description`,
					s.description AS `Supplier`,
					COALESCE(te.description, tg.description, tw.description, '') AS `Tariff`
				FROM meter AS m
				LEFT JOIN tariff_electricity AS te ON m.meter_type = 'E' AND m.tariff_id = te.id
				LEFT JOIN tariff_gas AS tg ON m.meter_type = 'G' AND m.tariff_id = tg.id
				LEFT JOIN tariff_water AS tw ON m.meter_type = 'W' AND m.tariff_id = tw.id
				LEFT JOIN energy_supplier AS s ON s.id = te.supplier_id OR s.id = tg.supplier_id OR s.id = tw.water_supplier_id
				JOIN area AS a ON a.id = m.area_id
				JOIN floor AS f ON f.id = a.floor_id
				WHERE f.building_id = '$building_id' AND m.parent_id IS NULL $meter_type_condition
				ORDER BY m.meter_type, m.description;
			", MySQL::QUERY_ASSOC);
			if($result) $data = App::array_to_csv($result);
			break;

		case 'meter-periods':
			if($meter->has_submeters()) {
				$result = App::sql()->query(
					"SELECT
						m.id AS `Meter ID`,
						m.description AS `Meter Description`,
						mp.year AS `Year`,
						mp.month AS `Month`,
						fr.reading_1 AS `First Reading 1`,
						lr.reading_1 AS `Last Reading 1`,
						mp.usage_1 AS `Usage 1 (Day)`,
						su.usage_1 AS `Sub-meter Usage 1`,
						fr.reading_2 AS `First Reading 2`,
						lr.reading_2 AS `Last Reading 2`,
						mp.usage_2 AS `Usage 2 (Night)`,
						su.usage_2 AS `Sub-meter Usage 2`,
						fr.reading_3 AS `First Reading 3`,
						lr.reading_3 AS `Last Reading 3`,
						mp.usage_3 AS `Usage 3 (Evening/Weekend)`,
						su.usage_3 AS `Sub-meter Usage 3`,
						COALESCE(fr.reading_total, COALESCE(fr.reading_1,0) + COALESCE(fr.reading_2,0) + COALESCE(fr.reading_3,0)) AS `First Reading Total`,
						COALESCE(lr.reading_total, COALESCE(lr.reading_1,0) + COALESCE(lr.reading_2,0) + COALESCE(lr.reading_3,0)) AS `Last Reading Total`,
						COALESCE(mp.usage_1,0) + COALESCE(mp.usage_2,0) + COALESCE(mp.usage_3,0) AS `Usage Total`,
						COALESCE(su.usage_1,0) + COALESCE(su.usage_2,0) + COALESCE(su.usage_3,0) AS `Sub-meter Usage Total`
					FROM meter_period AS mp
					JOIN meter AS m ON mp.meter_id = m.id
					LEFT JOIN (
						SELECT
							su_mp.year AS year,
							su_mp.month AS month,
							SUM(su_mp.usage_1) AS usage_1,
							SUM(su_mp.usage_2) AS usage_2,
							SUM(su_mp.usage_3) AS usage_3
						FROM meter_period AS su_mp
						JOIN meter AS su_m ON su_m.id = su_mp.meter_id
						WHERE su_m.parent_id = '$meter_id' AND su_mp.complete = 1
						GROUP BY su_mp.year, su_mp.month
					) AS su ON su.year = mp.year AND su.month = mp.month
					LEFT JOIN meter_reading AS fr ON fr.id = mp.first_reading_id
					LEFT JOIN meter_reading AS lr ON lr.id = mp.last_reading_id
					WHERE m.id = '$meter_id' AND mp.complete = 1
					ORDER BY mp.year, mp.month;
				", MySQL::QUERY_ASSOC);
			} else {
				$result = App::sql()->query(
					"SELECT
						m.id AS `Meter ID`,
						m.description AS `Meter Description`,
						mp.year AS `Year`,
						mp.month AS `Month`,
						fr.reading_1 AS `First Reading 1`,
						lr.reading_1 AS `Last Reading 1`,
						mp.usage_1 AS `Usage 1 (Day)`,
						fr.reading_2 AS `First Reading 2`,
						lr.reading_2 AS `Last Reading 2`,
						mp.usage_2 AS `Usage 2 (Night)`,
						fr.reading_3 AS `First Reading 3`,
						lr.reading_3 AS `Last Reading 3`,
						mp.usage_3 AS `Usage 3 (Evening/Weekend)`,
						COALESCE(fr.reading_total, COALESCE(fr.reading_1,0) + COALESCE(fr.reading_2,0) + COALESCE(fr.reading_3,0)) AS `First Reading Total`,
						COALESCE(lr.reading_total, COALESCE(lr.reading_1,0) + COALESCE(lr.reading_2,0) + COALESCE(lr.reading_3,0)) AS `Last Reading Total`,
						COALESCE(mp.usage_1,0) + COALESCE(mp.usage_2,0) + COALESCE(mp.usage_3,0) AS `Usage Total`
					FROM meter_period AS mp
					JOIN meter AS m ON mp.meter_id = m.id
					LEFT JOIN meter_reading AS fr ON fr.id = mp.first_reading_id
					LEFT JOIN meter_reading AS lr ON lr.id = mp.last_reading_id
					WHERE m.id = '$meter_id' AND mp.complete = 1
					ORDER BY mp.year, mp.month;
				", MySQL::QUERY_ASSOC);
			}
			if($result) $data = App::array_to_csv($result);
			break;

		case 'meter-readings':
			$result = App::sql()->query(
				"SELECT
					mr.meter_id AS `Meter ID`,
					m.description AS `Meter Description`,
					mr.reading_date AS `Reading Date`,
					mr.reading_1 AS `Reading 1 (Day)`,
					mr.reading_2 AS `Reading 2 (Night)`,
					mr.reading_3 AS `Reading 3 (Evening/Weekend)`,
					COALESCE(mr.reading_total, COALESCE(mr.reading_1,0) + COALESCE(mr.reading_2,0) + COALESCE(mr.reading_3,0)) AS `Reading Total`,
					IF(mr.initial_reading = 1, 'Yes', 'No') AS `Initial reading?`
				FROM meter_reading AS mr
				JOIN meter AS m ON mr.meter_id = m.id
				WHERE m.id = '$meter_id'
				ORDER BY mr.reading_date;
			", MySQL::QUERY_ASSOC);
			if($result) $data = App::array_to_csv($result);
			break;

		case 'meter-submeter-readings':
			$result = App::sql()->query(
				"SELECT
					mr.meter_id AS `Meter ID`,
					m.description AS `Meter Description`,
					mr.reading_date AS `Reading Date`,
					mr.reading_1 AS `Reading 1 (Day)`,
					mr.reading_2 AS `Reading 2 (Night)`,
					mr.reading_3 AS `Reading 3 (Evening/Weekend)`,
					COALESCE(mr.reading_total, COALESCE(mr.reading_1,0) + COALESCE(mr.reading_2,0) + COALESCE(mr.reading_3,0)) AS `Reading Total`,
					IF(mr.initial_reading = 1, 'Yes', 'No') AS `Initial reading?`
				FROM meter_reading AS mr
				JOIN meter AS m ON mr.meter_id = m.id
				WHERE m.parent_id = '$meter_id'
				ORDER BY mr.reading_date;
			", MySQL::QUERY_ASSOC);
			if($result) $data = App::array_to_csv($result);
			break;
	}
	
	if($data) {
		switch($report_method) {
			case 'csv':
				$meter_ids = [686, 687, 688];
				//print_r($report_type);exit;
				if ($report_type == "meter-readings" && $building_id == 88)
					{
						$csv_filename = "sim_".$meter_id."_diff.csv";						
						$filepath = APP_URL."/downloads/".$csv_filename;
						//print_r($filepath); exit;						
						// force download  
						$thumb_name = $_SERVER['DOCUMENT_ROOT'] . 'eticom/downloads/'.$csv_filename;
						//print_r($thumb_name); exit;
						if (file_exists($thumb_name))
						{
							header('Content-Description: File Transfer');
							header('Content-Type: application/csv');
							header('Content-Disposition: attachment; filename='.basename($thumb_name));
							//header('Content-Transfer-Encoding: binary');
							header('Expires: 0');
							header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
							header('Pragma: public');
							header('Content-Length: ' . filesize($thumb_name));
							ob_clean();
							flush();
							readfile($thumb_name);
							exit;
						}
						else{print_r('No file exist'); exit;	}
					}
				$filename = 'report';
				if($meter_type) $filename .= '-'.strtolower(Meter::type_to_description($meter_type));
				$filename .= "-{$report_type}-building-{$building_id}";
				if(isset($meter)) $filename .= "-meter-{$meter_id}";
				$d = date('Y-m-d');
				$filename .= "-({$d}).csv";

				header("Content-Type: text/csv");
				header("Content-Disposition: attachment; filename=$filename");
				return $data;

			case 'pdf':
				
				return Report::generate_pdf_report($url, $filename, 'inline');
				//Shez to generate download report by dates for John.W (peel ports customer request)
		}
	}

	return 'No meter data. Please try again once monthly readings have been registered.';
}

/**
 * Used to download a tenant bill from the server.
 */
function get_tenant_bill($user) {
	list($id, $type) = App::get(['id', 'type']);
	$bill = new TenantBill($type, $id);
	return $bill->get_bill('inline');
}

function tariff_electricity_info($user) {
	App::set_content_type();

	$tariff_id = App::get('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('OK', '', 'NO ID');

	$tariff = App::sql()->query_row("SELECT te.*, s.description as supplier_name FROM tariff_electricity AS te LEFT JOIN energy_supplier AS s ON s.id = te.supplier_id WHERE te.id = $tariff_id;");
	if(!$tariff) return App::encode_result('OK', '', 'NO REC');

	$daily_standing_charge = 0;
	$unit_rate_day = 0;
	$unit_rate_night = 0;

	if($tariff->standard_tariff_non_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->standard_tariff_non_dd_daily_standing_charge;
	if($tariff->standard_tariff_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->standard_tariff_dd_daily_standing_charge;
	if($tariff->economy7_tariff_non_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->economy7_tariff_non_dd_daily_standing_charge;
	if($tariff->economy7_tariff_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->economy7_tariff_dd_daily_standing_charge;

	if($tariff->standard_tariff_non_dd_unit_rate > $unit_rate_day) $unit_rate_day = $tariff->standard_tariff_non_dd_unit_rate;
	if($tariff->standard_tariff_dd_unit_rate > $unit_rate_day) $unit_rate_day = $tariff->standard_tariff_dd_unit_rate;
	if($tariff->economy7_tariff_non_dd_unit_rate_day > $unit_rate_day) $unit_rate_day = $tariff->economy7_tariff_non_dd_unit_rate_day;
	if($tariff->economy7_tariff_dd_unit_rate_day > $unit_rate_day) $unit_rate_day = $tariff->economy7_tariff_dd_unit_rate_day;

	if($tariff->economy7_tariff_non_dd_unit_rate_night > $unit_rate_night) $unit_rate_night = $tariff->economy7_tariff_non_dd_unit_rate_night;
	if($tariff->economy7_tariff_dd_unit_rate_night > $unit_rate_night) $unit_rate_night = $tariff->economy7_tariff_dd_unit_rate_night;

	$result = '
		<div class="col col-md-12">
			<strong class="font-md">'.$tariff->description.'</strong><br><br>
		</div>
		<div class="col col-md-6">
			<strong>Supplier</strong>&nbsp;&nbsp;&nbsp;'.$tariff->supplier_name.'<br>
			<strong>Daily standing charge</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($daily_standing_charge).'<br>
			<strong>Excess capacity rate</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->excess_capacity_rate_pounds_per_kva).'<br>
			<strong>Climate change rate</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->CCL_pence_per_unit).' / kWh<br>
			<strong>Climate change cost</strong>&nbsp;&nbsp;&nbsp;&pound;0.00<br>
		</div>
		<div class="col col-md-6">
			<strong>Notice period</strong>&nbsp;&nbsp;&nbsp;'.($tariff->business_contract_notice_period_days ?: 0).' days<br>
			<strong>Cost /kWh (day)</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($unit_rate_day).'<br>
			<strong>Cost /kWh (night)</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($unit_rate_night).'<br>
			<strong>Reactive power rate</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->reactive_power_rate_pounds_per_kva).' / kWh<br>
			<strong>Settlement/Agent charges</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->settlement_charges_pounds_per_year).' / year<br>
		</div>
	';

	if($tariff->client_id != 0) {
		$result .= '
			<div class="col col-md-12">
				<br><button data-id="'.$tariff_id.'" data-type="edit" class="pull-right">Edit</button>
			</div>
		';
	}

	return App::encode_result('OK', '', $result);
}

function tariff_gas_info($user) {
	App::set_content_type();

	$tariff_id = App::get('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('OK', '', 'NO ID');

	$tariff = App::sql()->query_row("SELECT tg.*, s.description as supplier_name FROM tariff_gas AS tg LEFT JOIN energy_supplier AS s ON s.id = tg.supplier_id WHERE tg.id = $tariff_id;");
	if(!$tariff) return App::encode_result('OK', '', 'NO REC');

	$standing_charge = 0;
	$cost_per_kwh = 0;

	if($tariff->standing_charge_non_dd > $standing_charge) $standing_charge = $tariff->standing_charge_non_dd;
	if($tariff->standing_charge_dd > $standing_charge) $standing_charge = $tariff->standing_charge_dd;

	if($tariff->cost_per_kwh_non_dd > $cost_per_kwh) $cost_per_kwh = $tariff->cost_per_kwh_non_dd;
	if($tariff->cost_per_kwh_dd > $cost_per_kwh) $cost_per_kwh = $tariff->cost_per_kwh_dd;

	$result = '
		<div class="col col-md-12">
			<strong class="font-md">'.$tariff->description.'</strong><br><br>
		</div>
		<div class="col col-md-6">
			<strong>Supplier</strong>&nbsp;&nbsp;&nbsp;'.$tariff->supplier_name.'<br>
			<strong>Standing charge</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($standing_charge).'<br>
		</div>
		<div class="col col-md-6">
			<strong>Cost /kWh</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($cost_per_kwh).'<br>
		</div>
	';

	if($tariff->client_id != 0) {
		$result .= '
			<div class="col col-md-12">
				<br><button data-id="'.$tariff_id.'" data-type="edit" class="pull-right">Edit</button>
			</div>
		';
	}

	return App::encode_result('OK', '', $result);
}

function tariff_water_info($user) {
	App::set_content_type();

	$tariff_id = App::get('tariff_id', 0, true);
	if(!$tariff_id) return App::encode_result('OK', '', 'NO ID');

	$tariff = App::sql()->query_row("SELECT tw.*, s1.description AS water_supplier_name, s2.description AS waste_supplier_name FROM tariff_water AS tw LEFT JOIN energy_supplier AS s1 ON s1.id = tw.water_supplier_id LEFT JOIN energy_supplier AS s2 ON s2.id = tw.waste_supplier_id WHERE tw.id = $tariff_id;");
	if(!$tariff) return App::encode_result('OK', '', 'NO REC');

	$result = '
		<div class="col col-md-12">
			<strong class="font-md">'.$tariff->description.'</strong><br><br>
		</div>
		<div class="col col-md-6">
			<strong>Water supplier</strong>&nbsp;&nbsp;&nbsp;'.$tariff->water_supplier_name.'<br>
			<strong>Daily standing charge</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->water_standing_charge_pence_per_day).'<br>
			<strong>Cost /m<sup>3</sup></strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->water_volumetric_charge_per_m3).'<br>
		</div>
		<div class="col col-md-6">
			<strong>Waste supplier</strong>&nbsp;&nbsp;&nbsp;'.$tariff->waste_supplier_name.'<br>
			<strong>Daily standing charge</strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->waste_standing_charge).'<br>
			<strong>Cost /m<sup>3</sup></strong>&nbsp;&nbsp;&nbsp;&pound;'.App::format_number($tariff->waste_volumetric_charge_per_m3).'<br>
		</div>
	';

	if($tariff->client_id != 0) {
		$result .= '
			<div class="col col-md-12">
				<br><button data-id="'.$tariff_id.'" data-type="edit" class="pull-right">Edit</button>
			</div>
		';
	}

	return App::encode_result('OK', '', $result);
}

function meter_tariffs($user) {
	App::set_content_type();

	$meter_id = App::get('meter_id', 0, true);
	if(!$meter_id) return App::encode_result('FAIL', 'No meter set.');

	$meter = new Meter($meter_id);
	$building = new Building($meter->get_building_id());
	if(!$building) return App::encode_result('FAIL', 'No building set.');

	$result = $meter->get_available_tariffs($building->info->client_id);
	return App::encode_result('OK', '', $result);
}

function tenant_info($user) {
	if(!isset($_GET['building_id'])) return App::encode_result('ERROR', 'Failed to get building info');

	$building_id = App::get('building_id');
	if(!$building_id) return App::encode_result('OK', 'Success', '');

	$building = new Building($building_id);

	$tenant_id = App::get('tenant_id');
	if(!$tenant_id) return App::encode_result('OK', 'Success', '');

	$tenant = $building->get_tenant_info(App::escape($tenant_id));
	if(!$tenant) return App::encode_result('OK', 'Success', '');

	$info_fields = [];
	if($tenant->company)                   $info_fields['Company']   = $tenant->company;
	if($tenant->name)                      $info_fields['Contact']   = $tenant->name;
	if($tenant->email_address)             $info_fields['Email ']    = $tenant->email_address;
	if($tenant->customer_reference_number) $info_fields['Reference'] = $tenant->customer_reference_number;
	if($tenant->home_address)              $info_fields['Address']   = $tenant->home_address;
	if($tenant->telephone_number)          $info_fields['Telephone'] = $tenant->telephone_number;
	if($tenant->mobile_number)             $info_fields['Mobile']    = $tenant->mobile_number;

	$info = new Info($info_fields);
	return App::encode_result('OK', 'Success', $info->print_html(true));
}

function get_mmm_building_meters_html($user) {
	$building_id = App::get('building_id', 0, true);
	list($meter_type, $meter_id) = App::get(['meter_type', 'meter_id'], '', true);

	if(!$building_id || !$meter_type) return '';

	$building = new Building($building_id);
	if($building->validate()) {
		return $building->get_mmm_building_meters_html($meter_type, $meter_id);
	} else {
		return '';
	}
}

function get_mmm_meter_readings_html($user) {
	$building_id = App::get('building_id', 0, true);
	$meter_id = App::get('meter_id', '', true);
	$partial = App::get('partial', 0, true);
	$partial = $partial ? 1 : 0;

	if(!$building_id) return '';

	$building = new Building($building_id);
	if($building->validate()) {
		return $building->get_mmm_meter_readings_html($meter_id, $partial);
	} else {
		return '';
	}
}

function get_mmm_building_summary_html($user) {
	$building_id = App::get('building_id', 0, true);

	if(!$building_id) return '';

	$building = new Building($building_id);
	if($building->validate()) {
		return $building->get_mmm_building_summary_html();
	} else {
		return '';
	}
}

/**
 * Used to download the report from the server.
 */
function print_contract($user) {
	$id = App::get('id', 0, true);
	$token = App::get('token', '', true);
	$contract = App::get('contract', 0, true);
	$download = isset($_GET['download']) ? 'attachment' : 'inline';
	if(!$id) return '';

	$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
	if(!$pa) return '';

	$contract = App::select('contract', $contract);
	if(!$contract) return '';

	if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return '';

	$url = "/print/print.php?type=contract&id=$id&token=$token&contract=$contract[id]";
	$filename = "contract-$contract[id].pdf";

	return Report::generate_pdf_report($url, $filename, $download, '', '', '-T 10mm -B 10mm -L 10mm -R 10mm');
}
