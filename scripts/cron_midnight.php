#!/usr/bin/php -q
<?php

/*
 * This process should be run just after midnight, once per day as a cron job
 */

include '../inc/init.app.php';

MySQL::$clean = false;

$sql = App::sql();

echo "------------\n";
echo "MISC ACTIONS\n";
echo "------------\n\n";

echo "Resetting dashboard time periods set in the past...";
App::sql()->update("UPDATE dashboard SET time_period = 'yesterday' WHERE time_period LIKE 'today_minus_%' AND user_id IS NOT NULL;");

echo "\n--------------\n";
echo "CLIMATE MODULE\n";
echo "--------------\n\n";

echo "Rebuilding active schedules...";

$schedules = App::sql('climate')->query(
	"SELECT
		ws.id, COUNT(cp.id) AS device_count
	FROM ac_weekly_schedule AS ws
	LEFT JOIN coolplug AS cp ON cp.weekly_schedule_id = ws.id
	WHERE ws.active = 1
	GROUP BY ws.id
	HAVING device_count > 0;
", MySQL::QUERY_ASSOC) ?: [];

foreach($schedules as $s) {
	Climate::rebuild_weekly_schedule($s['id']);
}

echo "\n------------\n";
echo "RELAY MODULE\n";
echo "------------\n\n";

echo "Rebuilding active schedules...";

$schedules = App::sql('relay')->query(
	"SELECT
		ws.id, COUNT(d.id) AS device_count
	FROM relay_weekly_schedule AS ws
	LEFT JOIN relay_end_device AS d ON d.weekly_schedule_id = ws.id
	WHERE ws.active = 1
	GROUP BY ws.id
	HAVING device_count > 0;
", MySQL::QUERY_ASSOC) ?: [];

foreach($schedules as $s) {
	Relay::rebuild_weekly_schedule($s['id']);
}

echo "\n---------------------\n";
echo "FETCHING WEATHER DATA\n";
echo "---------------------\n\n";

echo "Getting list of buildings with location set... ";
$buildings = $sql->query("SELECT id, description FROM building WHERE latitude IS NOT NULL AND longitude IS NOT NULL;", MySQL::QUERY_ASSOC, false);
echo "done.\n";

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('yesterday'));

echo "\nYesterday: $yesterday\nToday: $today\n\n";

foreach($buildings as $b) {
	$id = $b['id'];
	$desc = $b['description'];
	echo "Weather for $desc... ";

	$w = new WeatherService($id);
	$w->ensure_weather_data($yesterday, $today);
	unset($w);

	echo "done.\n";
}

echo "\n---------------\n";
echo "LIGHTING MODULE\n";
echo "---------------\n\n";

echo "Rebuilding active schedules...";

$buildings = App::sql()->query("SELECT id FROM building WHERE module_lighting <> 0 AND archived = 0;", MySQL::QUERY_ASSOC) ?: [];
foreach($buildings as $b) {
	$building_id = $b['id'];
	if(Lighting::check_database($building_id)) {
		Lighting::rebuild_building_schedule($building_id);
		Lighting::notify_schedule_change($building_id);
	}
}

echo "\n--------------\n";
echo "CONTROL MODULE\n";
echo "--------------\n\n";

echo "Rebuilding active schedules...";

$buildings = App::sql()->query("SELECT id FROM building WHERE module_control <> 0 AND archived = 0;", MySQL::QUERY_ASSOC) ?: [];
foreach($buildings as $b) {
	$building_id = $b['id'];
	Control::rebuild_building_schedule($building_id);
}

echo "\n---------------------\n";
echo "COLLECTOR RELIABILITY\n";
echo "---------------------\n\n";

echo "Calculating... ";

$chart_from = date('Y-m-d H:i:s', strtotime('-7 days'));
$chart_to = date('Y-m-d H:i:s');

$gateways = $sql->query("SELECT gateway_id FROM gateway_status;") ?: [];

foreach($gateways as $row) {
	$id = $row->gateway_id;

	$chart = $sql->query(
		"SELECT
			datetime AS x,
			IF(status = 'ok', 1, 0) AS y
		FROM gateway_status_history
		WHERE gateway_id = '$id' AND datetime BETWEEN '$chart_from' AND '$chart_to'
		ORDER BY datetime;
	", MySQL::QUERY_ASSOC, false) ?: [];

	$before = $sql->query_row(
		"SELECT
			datetime AS x,
			IF(status = 'ok', 1, 0) AS y
		FROM gateway_status_history
		WHERE gateway_id = '$id' AND datetime < '$chart_from'
		ORDER BY datetime DESC
		LIMIT 1;
	", MySQL::QUERY_ASSOC, false);

	$last = $sql->query_row(
		"SELECT
			datetime AS x,
			IF(status = 'ok', 1, 0) AS y
		FROM gateway_status_history
		WHERE gateway_id = '$id'
		ORDER BY datetime DESC
		LIMIT 1;
	", MySQL::QUERY_ASSOC, false);

	if($last) {
		$last['x'] = $chart_to;
		$chart[] = $last;
	}

	$rel = null;
	if($before) {
		$rel = 0;
		$last_datetime = $chart_from;
		$last_status = $before['y'];

		foreach($chart as $p) {
			if($last_status) {
				// This ends a period of uptime, add to reliability
				$rel += strtotime($p['x']) - strtotime($last_datetime);
			}

			$last_datetime = $p['x'];
			$last_status = $p['y'];
		}

		$total = strtotime($chart_to) - strtotime($chart_from);
		$rel = ($rel / $total) * 100;

	}
	if($rel !== null) {
		$sql->update("UPDATE gateway_status SET reliability_7_days = '$rel' WHERE gateway_id = '$id';");
	} else {
		$sql->update("UPDATE gateway_status SET reliability_7_days = NULL WHERE gateway_id = '$id';");
	}
}
echo "done.\n";

echo "\n----------------------\n";
echo "PROCESSING CARD EXPIRY\n";
echo "----------------------\n\n";

// Check if we have any cards that are about to expire
$month = (int)date('m');
$year = date('Y');

// TODO: Handle card expiry emails for other customer types (SP, SI, HG, C)

$cards = App::sql()->query(
	"SELECT psc.*, pg.owner_type, pg.owner_id
	FROM payment_stripe_card AS psc
	JOIN customer AS c ON psc.customer_type = 'CU' AND psc.customer_id = c.id
	JOIN payment_gateway AS pg ON pg.id = psc.payment_gateway_id
	WHERE psc.exp_month = '$month' AND psc.exp_year = '$year' AND psc.expiry_warning_sent = 0 AND c.archived = 0;
", MySQL::QUERY_ASSOC) ?: [];

foreach($cards as $card) {
	$payment_gateway_id = $card['payment_gateway_id'];
	$customer_type = $card['customer_type'];
	$customer_id = $card['customer_id'];
	$owner_type = $card['owner_type'];
	$owner_id = $card['owner_id'];

	echo "Expiring card ($payment_gateway_id - $customer_type - $customer_id) on $card[exp_month]/$card[exp_year] ending $card[last4].\n";

	// Send email
	$customer = Customer::resolve_details($customer_type, $customer_id);
	if($customer && $customer['email_address']) {
		$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);

		// Select first customer contract for context
		$contract = null;
		$cdata = App::sql()->query_row("SELECT id FROM contract WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id' AND status NOT IN ('cancelled', 'ended') LIMIT 1;", MySQL::QUERY_ASSOC);
		if($cdata) $contract = new Contract($cdata['id']);

		Mailer::send_from_template($owner_type, $owner_id, 'isp_card_expires', $customer['email_address'], $customer['name'] ?: '', [
			'customer' => $customer,
			'payment_account' => $pa,
			'contract' => $contract,
			'invoice' => $contract ? $contract->get_last_period_invoice() : null
		]);

		App::sql()->update("UPDATE payment_stripe_card SET expiry_warning_sent = 1 WHERE payment_gateway_id = '$payment_gateway_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id';");
	}
}

// Check for expired cards
$cards = App::sql()->query(
	"SELECT psc.*, pg.owner_type, pg.owner_id
	FROM payment_stripe_card AS psc
	JOIN customer AS c ON psc.customer_type = 'CU' AND psc.customer_id = c.id
	JOIN payment_gateway AS pg ON pg.id = psc.payment_gateway_id
	WHERE
		(psc.exp_year < '$year' OR (psc.exp_month < '$month' AND psc.exp_year = '$year'))
		AND c.archived = 0;
", MySQL::QUERY_ASSOC) ?: [];

foreach($cards as $card) {
	$payment_gateway_id = $card['payment_gateway_id'];
	$customer_type = $card['customer_type'];
	$customer_id = $card['customer_id'];
	$owner_type = $card['owner_type'];
	$owner_id = $card['owner_id'];

	echo "Expired card ($payment_gateway_id - $customer_type - $customer_id) on $card[exp_month]/$card[exp_year] ending $card[last4].\n";

	// Remove card
	$pg = new PaymentGateway($payment_gateway_id);
	if($pg->is_valid() && $pg->record['stripe_user_id']) {
		try {
			\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

			$cu = \Stripe\Customer::retrieve($card['stripe_customer'], [
				'stripe_account' => $pg->record['stripe_user_id']
			]);
			$cu->delete();
		} catch(Exception $ex) {
			echo "Cannot delete customer $card[stripe_customer].";
		}
	}

	// Send email
	$customer = Customer::resolve_details($customer_type, $customer_id);
	if($customer && $customer['email_address']) {
		$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);

		// Select first customer contract for context
		$contract = null;
		$cdata = App::sql()->query_row("SELECT id FROM contract WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id' AND status NOT IN ('cancelled', 'ended') LIMIT 1;", MySQL::QUERY_ASSOC);
		if($cdata) $contract = new Contract($cdata['id']);

		Mailer::send_from_template($owner_type, $owner_id, 'isp_card_expired', $customer['email_address'], $customer['name'] ?: '', [
			'customer' => $customer,
			'payment_account' => $pa,
			'contract' => $contract,
			'invoice' => $contract ? $contract->get_last_period_invoice() : null
		]);
	}

	App::sql()->delete("DELETE FROM payment_stripe_card WHERE payment_gateway_id = '$payment_gateway_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id';");
}

echo "done.\n";

echo "\n-----------------------------\n";
echo "PROCESSING CUSTOMER CONTRACTS\n";
echo "-----------------------------\n\n";

$list = $sql->query("SELECT id FROM contract WHERE status IN ('pending', 'active', 'ending')") ?: [];
foreach($list as $item) {
	echo "Processing contract $item->id...";
	try {
		$contract = new Contract($item->id);
		$contract->process();
	} catch(Exception $ex) {
		echo $ex->getMessage();
		echo "\n";
	}
	echo "done.\n";
}

echo "\n----------------------------------\n";
echo "PROCESSING AUTOMATIC CARD PAYMENTS\n";
echo "----------------------------------\n\n";

$list = $sql->query("SELECT * FROM payment_account WHERE trigger_card_payment_date IS NOT NULL AND trigger_card_payment_date <= '$today';", MySQL::QUERY_ASSOC) ?: [];
foreach($list as $item) {
	$pa = new PaymentAccount($item['owner_type'], $item['owner_id'], $item['customer_type'], $item['customer_id']);
	echo "Automatic card payment for account $pa->id...";
	$outstanding = $pa->get_outstanding_pence();
	if($outstanding) {
		$invoice_id = $item['trigger_card_payment_invoice'];
		$invoice = new Invoice($invoice_id);
		if($invoice->validate() && $invoice->record['status'] === 'outstanding') {
			if(!$invoice->pay_invoice_by_card()) {
				// Payment failed, send email
				$customer = Customer::resolve_details($item['customer_type'], $item['customer_id']);
				if($customer && $customer['email_address']) {
					Mailer::send_from_template($item['owner_type'], $item['owner_id'], 'isp_card_fail', $customer['email_address'], $customer['name'] ?: '', [
						'customer' => $customer,
						'payment_account' => $pa,
						'contract' => $invoice->contract,
						'invoice' => $invoice
					]);
				}
			}
		}
	}
	$pa->schedule_card_payment(null, null);
	echo "done.\n";
}

echo "\n---------------------------\n";
echo "SWITCH OFF CUSTOMER ROUTERS\n";
echo "---------------------------\n\n";

// Find all customer accounts that are in arrears and have no pending transactions or upcoming card payments
$list = $sql->query("SELECT
	pa.id,
    c.contact_name,
    SUM(tx.amount) AS total
FROM payment_account AS pa
JOIN payment_transaction AS tx ON tx.account_id = pa.id AND tx.status = 'ok'
JOIN customer AS c ON c.id = pa.customer_id AND pa.customer_type = 'CU' AND c.archived = 0
WHERE
	pa.id NOT IN (SELECT account_id FROM payment_transaction WHERE status = 'pending')
    AND pa.trigger_card_payment_date IS NULL
GROUP BY pa.id, c.contact_name
HAVING total < 0;", MySQL::QUERY_ASSOC) ?: [];

foreach($list as $account) {
	$pa = PaymentAccount::from_id($account['id']);
	if($pa) {
		echo "Customer '$account[contact_name]' ($account[id]) is in arrears.\n";
		$pa->event_in_arrears(true);
	}
}
echo "done.\n";

echo "\n--------\n";
echo "Finished\n";
echo "--------\n";
