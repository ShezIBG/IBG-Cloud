#!/usr/bin/php -q
<?php

/*
 * This process should be run every 5 minutes as a cron job
 */

include '../inc/init.app.php';

$contract = new Contract(0);
$contract->id = 1;
$contract->record = [
	'id' => 1,
	'owner_type' => 'SI',
	'owner_id' => 1,
	'customer_type' => 'CU',
	'customer_id' => 1,
	'area_id' => 1,
	'reference_no' => '',
	'description' => '',
	'status' => 'ending',
	'start_date' => '2018-06-16',
	'end_date' => '2018-08-05',
	'term' => 6,
	'term_units' => 'months',
	'contract_term' => 'variable',
	'is_template' => 0
];

$invoice = new ContractInvoice(0, $contract);
$invoice->id = 1;
$invoice->record = [
	'id' => 1,
	'contract_id' => 1,
	'description' => '',
	'frequency' => 'monthly+',

	'cutoff_day' => 15,
	'issue_day' => 21,
	'payment_day' => 28,

	'last_bill_date' => null,
	'last_due_date' => null,
	'last_period_start_date' => null,
	'last_period_end_date' => null
];

while($next = $invoice->get_next_bill_dates()) {

	echo json_encode($next, JSON_PRETTY_PRINT);
	echo "\n";

	$invoice->record = array_merge($invoice->record, [
		'last_bill_date' => $next['bill_date'],
		'last_due_date' => $next['due_date'],
		'last_period_start_date' => $next['period_start_date'],
		'last_period_end_date' => $next['period_end_date']
	]);

}
