#!/usr/bin/php -q
<?php

// One-off script to generate Legacie's backdated bills

include '../inc/init.app.php';

MySQL::$clean = false;

function end_of_month($date = null) {
	if(!$date) $date = date('Y-m-d');
	$date = date('Y-m-01', strtotime($date));
	$date = strtotime('+1 month', strtotime($date));
	$date = date('Y-m-d', strtotime('-1 day', $date));
	return $date;
}

function start_of_month($date = null) {
	if(!$date) $date = date('Y-m-d');
	$date = date('Y-m-01', strtotime($date));
	return $date;
}

function days_between($date1, $date2) {
	$date1 = strtotime($date1);
	$date2 = strtotime($date2);
	return round(abs($date2 - $date1) / (60 * 60 * 24));
}

function add_days($n, $date = null) {
	if(!$date) $date = date('Y-m-d');
	$date = date('Y-m-d', strtotime("+$n day", strtotime($date)));
	return $date;
}

function sub_days($n, $date = null) {
	if(!$date) $date = date('Y-m-d');
	$date = date('Y-m-d', strtotime("-$n day", strtotime($date)));
	return $date;
}

function start_of_next_month($date = null) {
	if(!$date) $date = date('Y-m-d');
	$date = start_of_month($date);
	$date = add_days(32, $date);
	$date = start_of_month($date);
	return $date;
}

function end_of_next_month($date = null) {
	if(!$date) $date = date('Y-m-d');
	$date = start_of_next_month($date);
	$date = end_of_month($date);
	return $date;
}

$sql = App::sql();

$back_date_from = '2018-08-01';
$back_date_to = '2019-07-31';
$moved_out_after = '2019-07-01';
$issue_date = '2019-12-19';
$due_date = '2020-02-08';

$monthly_price = 29.40;

$line_desc = 'Discounted Tariff from tenancy start date';

// c.id NOT IN rule is to avoid already generated invoices for the re-run

$list = $sql->query(
	"SELECT
		c.id, c.owner_type, c.owner_id, c.customer_type, c.customer_id, c.area_id, c.status, c.start_date, c.end_date,

		IF(c.start_date > '$back_date_from', c.start_date, '$back_date_from') AS bill_start,
		IF(c.end_date < '$back_date_to' AND c.status NOT IN ('active', 'ending'), c.end_date, '$back_date_to') AS bill_end,

		ci.id AS contract_invoice_id
	FROM contract AS c
	JOIN contract_invoice AS ci ON ci.contract_id = c.id
	WHERE
		c.owner_type = 'C' AND c.owner_id = 29 AND c.id NOT IN (350,359,368,404,406,430,445)
		AND c.status IN ('active', 'ending', 'ended') AND c.is_template = 0
		AND c.start_date < '$back_date_to' AND (c.end_date > '$moved_out_after' OR c.status IN ('active', 'ending'));
", MySQL::QUERY_ASSOC) ?: [];

$cnt = count($list);
echo "Processing $cnt contracts...\n";

foreach($list as $item) {
	$ci = new ContractInvoice($item['contract_invoice_id']);
	$contract = $ci->contract;

	if(!$ci->validate()) {
		echo "Invalid contract invoice $item[contract_invoice_id]\n";
		continue;
	}

	if(!$contract) {
		echo "Invalid contract for contract invoice $item[contract_invoice_id]\n";
		continue;
	}

	$owner_info = $ci->get_owner_info();
	$customer_info = $ci->get_customer_info();

	$start_date = $item['bill_start'];
	$end_date = $item['bill_end'];

	if(!$start_date || !$end_date || strtotime($start_date) > strtotime($end_date)) {
		echo "Invalid start/end date on CI $item[contract_invoice_id] ($start_date, $end_date)\n";
		continue;
	}

	$lines = [];

	$date = $start_date;

	if($date !== start_of_month($date)) {
		// Add partial month at start
		$date_from = $date;
		$date_to = end_of_month($date);
		if(strtotime($date_to) > strtotime($end_date)) $date_to = $end_date;

		$month_days = days_between(start_of_month($date_from), end_of_month($date_from)) + 1;
		$month_desc = date('M Y', strtotime($date_from));

		$daily_price = $monthly_price / $month_days;
		$days = days_between($date_from, $date_to) + 1;

		$lines[] = [
			'icon' => '',
			'description' => "$line_desc ($month_desc)",
			'unit_price' => $daily_price,
			'quantity' => $days,
			'line_total' => round($daily_price * $days, 2)
		];

		$date = start_of_next_month($date);
	}

	$full_months = 0;
	$date_from = $date;
	$date = end_of_month($date);
	while(strtotime($date) <= strtotime($end_date)) {
		$full_months += 1;
		$date_from = add_days(1, $date);
		$date = end_of_next_month($date);
	}

	if($full_months) {
		// Add a number of full months
		$lines[] = [
			'icon' => '',
			'description' => "$line_desc",
			'unit_price' => $monthly_price,
			'quantity' => $full_months,
			'line_total' => round($monthly_price * $full_months, 2)
		];
	}

	if(strtotime($date_from) <= strtotime($end_date)) {
		// Add partial month at the end
		$date_to = $end_date;

		$month_days = days_between(start_of_month($date_from), end_of_month($date_from)) + 1;
		$month_desc = date('M Y', strtotime($date_from));

		$daily_price = $monthly_price / $month_days;
		$days = days_between($date_from, $date_to) + 1;

		$lines[] = [
			'icon' => '',
			'description' => "$line_desc ($month_desc)",
			'unit_price' => $daily_price,
			'quantity' => $days,
			'line_total' => round($daily_price * $days, 2)
		];
	}

	if(!count($lines)) {
		echo "No lines to process for contract invoice $item[contract_invoice_id]\n";
		continue;
	}

	$vat_rate = $ci->record['vat_rate'];
	$subtotal = 0;
	$vat_due = 0;
	$bill_total = 0;

	foreach($lines as $line) {
		$subtotal += $line['line_total'];
	}
	$vat_due = round($subtotal * ($vat_rate / 100), 2);
	$bill_total = $subtotal + $vat_due;

	$invoice_id = App::insert('invoice', [
		'owner_type' => $contract->record['owner_type'],
		'owner_id' => $contract->record['owner_id'],
		'customer_type' => $contract->record['customer_type'],
		'customer_id' => $contract->record['customer_id'],
		'invoice_no' => Invoice::generate_invoice_no($contract->record['owner_type'], $contract->record['owner_id']),
		'contract_id' => $contract->id,
		'contract_invoice_id' => $ci->id,
		'invoice_entity_id' => $ci->record['invoice_entity_id'],
		'description' => $ci->record['description'],
		'status' => 'not_approved',
		'owner_name' => $owner_info['name'],
		'owner_address_line_1' => $owner_info['invoice_address_line_1'],
		'owner_address_line_2' => $owner_info['invoice_address_line_2'],
		'owner_address_line_3' => $owner_info['invoice_address_line_3'],
		'owner_posttown' => $owner_info['invoice_posttown'],
		'owner_postcode' => $owner_info['invoice_postcode'],
		'customer_name' => $customer_info['name'],
		'customer_address_line_1' => $customer_info['invoice_address_line_1'],
		'customer_address_line_2' => $customer_info['invoice_address_line_2'],
		'customer_address_line_3' => $customer_info['invoice_address_line_3'],
		'customer_posttown' => $customer_info['invoice_posttown'],
		'customer_postcode' => $customer_info['invoice_postcode'],
		'customer_ref' => $contract->record['reference_no'],
		'bill_date' => $issue_date,
		'due_date' => $due_date,
		'period_start_date' => $start_date,
		'period_end_date' => $end_date,
		'vat_rate' => $vat_rate,
		'subtotal' => $subtotal,
		'vat_due' => $vat_due,
		'bill_total' => $bill_total,
		'bank_name' => $owner_info['bank_name'],
		'bank_sort_code' => $owner_info['bank_sort_code'],
		'bank_account_number' => $owner_info['bank_account_number'],
		'vat_reg_number' => $owner_info['vat_reg_number'],
		'is_first' => 0
	]);

	foreach($lines as $line) {
		$line['id'] = 'new';
		$line['invoice_id'] = $invoice_id;
		App::insert('invoice_line', $line);
	}

	echo "Success $item[contract_invoice_id], invoice: $invoice_id\n";
}

echo "Done.\n";
