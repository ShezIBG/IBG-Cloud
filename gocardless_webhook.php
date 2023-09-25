<?php

include 'inc/init.app.php';

$webhook = new \Ockle\GoCardlessWebhook\Service(GOCARDLESS_WEBHOOK_SECRET, new \Symfony\Component\EventDispatcher\EventDispatcher);

$webhook->onMandate(function (\Ockle\GoCardlessWebhook\Events\MandateEvent $event) {
	$mandate_id = $event->getMandateId();

	if($mandate_id) {
		if (
			$event->actionIs(\Ockle\GoCardlessWebhook\Events\MandateEvent::ACTION_CANCELLED) ||
			$event->actionIs(\Ockle\GoCardlessWebhook\Events\MandateEvent::ACTION_FAILED) ||
			$event->actionIs(\Ockle\GoCardlessWebhook\Events\MandateEvent::ACTION_EXPIRED)
		) {
			$sql = App::sql();

			// Check if this mandate belongs to a customer and send email
			$md = $sql->query_row(
				"SELECT pgm.*, pg.owner_type, pg.owner_id
				FROM payment_gocardless_mandate AS pgm
				JOIN payment_gateway AS pg ON pg.id = pgm.payment_gateway_id
				WHERE pgm.status = 'authorised' AND pgm.gocardless_mandate_id = '$mandate_id'
				LIMIT 1;
			", MySQL::QUERY_ASSOC);
			if($md) {
				// Update status immediately
				// Make sure we don't wait for the email to complete before the database is updated
				$sql->update("UPDATE payment_gocardless_mandate SET status = 'cancelled', date_cancelled = NOW() WHERE gocardless_mandate_id = '$mandate_id';");

				$payment_gateway_id = $md['payment_gateway_id'];
				$customer_type = $md['customer_type'];
				$customer_id = $md['customer_id'];
				$owner_type = $md['owner_type'];
				$owner_id = $md['owner_id'];

				// Send email
				$customer = Customer::resolve_details($customer_type, $customer_id);
				if($customer && $customer['email_address']) {
					$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);

					// Select first customer contract for context
					$contract = null;
					$cdata = App::sql()->query_row("SELECT id FROM contract WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id' AND status NOT IN ('cancelled', 'ended') LIMIT 1;", MySQL::QUERY_ASSOC);
					if($cdata) $contract = new Contract($cdata['id']);

					Mailer::send_from_template($owner_type, $owner_id, 'isp_dd_cancelled', $customer['email_address'], $customer['name'] ?: '', [
						'customer' => $customer,
						'payment_account' => $pa,
						'contract' => $contract,
						'invoice' => $contract ? $contract->get_last_period_invoice() : null
					]);
				}
			}

			$sql->update("UPDATE payment_gocardless_mandate SET status = 'cancelled', date_cancelled = NOW() WHERE gocardless_mandate_id = '$mandate_id';");
		}
	}
});

$webhook->onPayment(function (\Ockle\GoCardlessWebhook\Events\PaymentEvent $event) {
	$payment_id = $event->getPaymentId();

	if($payment_id) {
		$action = '';

		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CREATED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CREATED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CUSTOMER_APPROVAL_GRANTED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CUSTOMER_APPROVAL_GRANTED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CUSTOMER_APPROVAL_DENIED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CUSTOMER_APPROVAL_DENIED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_SUBMITTED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_SUBMITTED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CONFIRMED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CONFIRMED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGEBACK_CANCELLED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGEBACK_CANCELLED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_PAID_OUT)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_PAID_OUT;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_LATE_FAILURE_SETTLED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_LATE_FAILURE_SETTLED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGEBACK_SETTLED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGEBACK_SETTLED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_FAILED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_FAILED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGED_BACK)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGED_BACK;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CANCELLED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CANCELLED;
		if($event->actionIs(\Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_RESUBMISSION_REQUESTED)) $action = \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_RESUBMISSION_REQUESTED;

		$transaction_status = '';

		switch($action) {
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CUSTOMER_APPROVAL_DENIED:
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGEBACK_CANCELLED:
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_LATE_FAILURE_SETTLED:
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGEBACK_SETTLED:
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_FAILED:
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CHARGED_BACK:
			// case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CANCELLED:
				$transaction_status = 'fail';
				break;

			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CONFIRMED:
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_PAID_OUT:
				$transaction_status = 'ok';
				break;
			case \Ockle\GoCardlessWebhook\Events\PaymentEvent::ACTION_CANCELLED:
				$transaction_status = 'cancelled';
			default:
				// Unknown action, do nothing
				return;
		}

		$sql = App::sql();
		$payment_id = $sql->escape($payment_id);
		$action = $sql->escape($action);

		// New transaction processing
		$txn = $sql->query_row("SELECT * FROM payment_transaction WHERE type = 'dd' AND transaction_ref = '$payment_id' LIMIT 1;", MySQL::QUERY_ASSOC);
		if($txn) {
			$update = [
				'status' => $transaction_status,
				'notes' => $action
			];
			$update["{$transaction_status}_datetime"] = App::now();

			$record = App::select('payment_transaction', $txn['id']);
			App::update('payment_transaction', $txn['id'], $update);


			if($record['status'] !== 'ok' && $transaction_status === 'ok') {

				if($txn['invoice_id']) App::update('invoice', $txn['invoice_id'], ['status' => 'paid']);

				$pa = PaymentAccount::from_id($record['account_id']);
				if($pa) $pa->process_after_balance_changed();

			} else if($record['status'] !== 'fail' && $transaction_status === 'fail') {

				// TODO: Handle retries
				// Change the status of the failed invoices
				// dd_failed() send out an email notify of failed dd && create new invoice

				if($txn['invoice_id']) {
					$invoice = new Invoice($txn['invoice_id']);
					if($invoice->validate()) {
						$invoice->mark_as_outstanding();
						//$invoice->dd_failed();
					}
				}

				$pa = PaymentAccount::from_id($record['account_id']);
				if($pa) $pa->process_after_balance_changed();

			}
			else if($record['status'] != 'fail' && $transaction_status === 'cancelled') {

				//do nothing 

			}
		}
	}
});

$post_body = file_get_contents('php://input');
$webhook->process(getallheaders()['Webhook-Signature'], $post_body);

http_response_code($webhook->getResponseStatus());
