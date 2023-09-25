<?php

require_once 'lib/include.php';

$data = App::json();
$event = null;

try {
	$event = \Stripe\Event::constructFrom($data);
} catch(\UnexpectedValueException $e) {
	// Invalid payload
	http_response_code(400);
	exit;
}

if(!$event) {
	http_response_code(400);
	exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Handle the event
switch ($event->type) {
	case 'payment_intent.succeeded':
		$intent = $event->data->object;

		if($pi = App::select('payment_stripe_intent', $intent->id)) {
			$pa = PaymentAccount::from_id($pi['payment_account_id']);
			$pg = new PaymentGateway($pi['payment_gateway_id']);

			$txn = App::sql()->query_row("SELECT * FROM payment_transaction WHERE payment_gateway_id = '$pg->id' AND transaction_ref = '$intent->id' LIMIT 1;");

			if(!$txn) {
				// Payment is successful, register transaction on customer's account
				App::insert('payment_transaction', [
					'account_id' => $pa->id,
					'create_datetime' => App::now(),
					'type' => 'card',
					'description' => 'Card payment',
					'amount' => $intent->amount / 100,
					'status' => 'ok',
					'payment_gateway_id' => $pg->id,
					'ok_datetime' => App::now(),
					'transaction_ref' => $intent->id
				]);

				if($pi['upgrade_contract_id'] && $pi['upgrade_isp_package_id']) {
					// This payment was made as part of an upgrade request.
					// We need to raise an invoice for the upgrade amount, update their contract and activate their new package.
					try {
						// Resolve customer's new package
						$package = new ISPPackage($pi['upgrade_isp_package_id']);
						if(!$package->validate()) throw new Exception('Cannot resolve upgrade package.');

						// Resolve contract
						$contract = new Contract($pi['upgrade_contract_id']);
						if(!$contract->validate()) throw new Exception('Cannot resolve upgrade contract.');

						// Resolve area
						$area = new ISPArea($contract->record['area_id']);
						if(!$area->validate()) throw new Exception('Cannot resolve contract area.');

						$onu = $area->get_onu();
						if(!$onu) throw new Exception('Cannot resolve ONU.');

						// Find the ContractInvoice with the ISP package and update it
						$found_ci = null;
						foreach($contract->invoices as $ci) {
							foreach($ci->lines as $line) {
								if($line['type'] === 'isp_package' || $line['type'] === 'isp_package_custom') {
									// Found the actual invoice line with the ISP package, update
									App::update('contract_invoice_line', $line['id'], [
										'isp_package_id' => $package->id,
										'description' => $package->record['description'],
										'unit_price' => $package->record['monthly_price']
									]);

									$found_ci = $ci;
								}
							}
						}

						if($found_ci) {
							// Raise invoice with the upgrade amount taken
							$upgrade_net = $pi['upgrade_net_pence'] / 100;
							$found_ci->raise_custom_invoice([[
								'icon' => '',
								'description' => 'Upgrade to '.$package->record['description'],
								'unit_price' => $upgrade_net,
								'quantity' => 1,
								'line_total' => $upgrade_net
							]]);
						}

						// Switch user's router to their new upgraded package
						$onu->set_package($package);

					} catch(Exception $ex) {
						error_log($ex);
					}
				}

				$pa->process_after_balance_changed();
			}

			// All done, remove payment intent record
			// If we don't get to this point, it means something has thrown an exception. Webhook may be retried later.
			// Because of this, we need to take care we don't register a payment transaction multiple times.
			App::delete('payment_stripe_intent', $pi['id']);
		}
		break;

	case 'checkout.session.completed':
		$session = $event->data->object;

		// Get saved checkout info
		if($checkout = App::select('payment_stripe_checkout', $session->id)) {
			$pa = PaymentAccount::from_id($checkout['payment_account_id']);
			$pg = new PaymentGateway($checkout['payment_gateway_id']);
			$cd = Customer::resolve_details($pa->customer_type, $pa->customer_id);
			$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg->id' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id' LIMIT 1;", MySQL::QUERY_ASSOC);
			$customer = null;

			if($checkout['checkout_mode'] === 'setup') {
				// Setup
				$intent = \Stripe\SetupIntent::retrieve($session->setup_intent, ['stripe_account' => $pg->record['stripe_user_id']]);
				$payment_method = \Stripe\PaymentMethod::retrieve($intent->payment_method, ['stripe_account' => $pg->record['stripe_user_id']]);
			} else {
				// Payment
				$intent = \Stripe\PaymentIntent::retrieve($session->payment_intent, ['stripe_account' => $pg->record['stripe_user_id']]);
				$customer = \Stripe\Customer::retrieve($session->customer, ['stripe_account' => $pg->record['stripe_user_id']]);
				$payment_method = \Stripe\PaymentMethod::retrieve($intent->payment_method, ['stripe_account' => $pg->record['stripe_user_id']]);

				if($intent->status === 'succeeded') {
					// Check if transaction has already been registered for this checkout session.
					// If it has, this is a retry, take care not to register one session as multiple payments on the customer's account.
					$txn = App::sql()->query_row("SELECT * FROM payment_transaction WHERE payment_gateway_id = '$pg->id' AND transaction_ref = '$intent->id' LIMIT 1;");

					if(!$txn) {
						// Payment is successful, register transaction on customer's account
						App::insert('payment_transaction', [
							'account_id' => $pa->id,
							'create_datetime' => App::now(),
							'type' => 'card',
							'description' => 'Card payment',
							'amount' => $intent->amount / 100,
							'status' => 'ok',
							'payment_gateway_id' => $pg->id,
							'ok_datetime' => App::now(),
							'transaction_ref' => $intent->id
						]);

						if($checkout['upgrade_contract_id'] && $checkout['upgrade_isp_package_id']) {
							// This payment was made as part of an upgrade request.
							// We need to raise an invoice for the upgrade amount, update their contract and activate their new package.
							try {
								// Resolve customer's new package
								$package = new ISPPackage($checkout['upgrade_isp_package_id']);
								if(!$package->validate()) throw new Exception('Cannot resolve upgrade package.');

								// Resolve contract
								$contract = new Contract($checkout['upgrade_contract_id']);
								if(!$contract->validate()) throw new Exception('Cannot resolve upgrade contract.');

								// Resolve area
								$area = new ISPArea($contract->record['area_id']);
								if(!$area->validate()) throw new Exception('Cannot resolve contract area.');

								$onu = $area->get_onu();
								if(!$onu) throw new Exception('Cannot resolve ONU.');

								// Find the ContractInvoice with the ISP package and update it
								$found_ci = null;
								foreach($contract->invoices as $ci) {
									foreach($ci->lines as $line) {
										if($line['type'] === 'isp_package' || $line['type'] === 'isp_package_custom') {
											// Found the actual invoice line with the ISP package, update
											App::update('contract_invoice_line', $line['id'], [
												'isp_package_id' => $package->id,
												'description' => $package->record['description'],
												'unit_price' => $package->record['monthly_price']
											]);

											$found_ci = $ci;
										}
									}
								}

								if($found_ci) {
									// Raise invoice with the upgrade amount taken
									$upgrade_net = $checkout['upgrade_net_pence'] / 100;
									$found_ci->raise_custom_invoice([[
										'icon' => '',
										'description' => 'Upgrade to '.$package->record['description'],
										'unit_price' => $upgrade_net,
										'quantity' => 1,
										'line_total' => $upgrade_net
									]]);
								}

								// Switch user's router to their new upgraded package
								$onu->set_package($package);

							} catch(Exception $ex) {
								error_log($ex);
							}
						}

						$pa->process_after_balance_changed();
					}
				}
			}

			if($card) {
				// Remove existing payment methods
				$list = \Stripe\PaymentMethod::all(
					['customer' => $card['stripe_customer'], 'type' => 'card'],
					['stripe_account' => $pg->record['stripe_user_id']]
				) ?: [];

				foreach($list as $pm) if($payment_method->id !== $pm->id) $pm->detach();

				$customer_id = $card['stripe_customer'];

				if(!$customer) {
					// It means we're in setup mode, needs attaching payment method
					$payment_method->attach(
						['customer' => $customer_id],
						['stripe_account' => $pg->record['stripe_user_id']]
					);
				}
			} else if($customer) {
				// Customer has just been created via the transaction
				$customer_id = $customer->id;
			} else {
				// Create stripe customer with new payment method
				$fields = ['description' => $cd['name']];
				if($cd['email_address']) $fields['email'] = $cd['email_address'];

				$customer = \Stripe\Customer::create(
					$fields,
					['stripe_account' => $pg->record['stripe_user_id']]
				);

				$customer_id = $customer->id;

				$payment_method->attach(
					['customer' => $customer_id],
					['stripe_account' => $pg->record['stripe_user_id']]
				);
			}

			// Update customer details
			$fields = [
				'invoice_settings' => ['default_payment_method' => $payment_method->id],
				'description' => $cd['name']
			];
			if($cd['email_address']) $fields['email'] = $cd['email_address'];

			\Stripe\Customer::update(
				$customer_id,
				$fields,
				['stripe_account' => $pg->record['stripe_user_id']]
			);

			// Save new card details in the system
			if($card) {
				App::sql()->delete("DELETE FROM payment_stripe_card WHERE payment_gateway_id = '$pg->id' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id';");
			}

			$card_type = $payment_method->card->brand;
			$card_exp_month = $payment_method->card->exp_month;
			$card_exp_year = $payment_method->card->exp_year;
			$card_last4 = $payment_method->card->last4;
			App::sql()->insert(
				"INSERT INTO payment_stripe_card (payment_gateway_id, customer_type, customer_id, stripe_customer, card_type, exp_month, exp_year, last4)
				VALUES ('$pg->id', '$pa->customer_type', '$pa->customer_id', '$customer_id', '$card_type', '$card_exp_month', '$card_exp_year', '$card_last4');
			");

			// All done, remove checkout session record
			// If we don't get to this point, it means something has thrown an exception. Webhook may be retried later.
			// Because of this, we need to take care we don't register a payment transaction multiple times.
			App::delete('payment_stripe_checkout', $checkout['id']);
		}
		break;

}

http_response_code(200);
