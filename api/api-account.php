<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	private function get_payment_account($id, $token) {
		$id = App::escape($id);
		$token = App::escape($token);
		$r = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';");
		return $r ? new PaymentAccount($r->owner_type, $r->owner_id, $r->customer_type, $r->customer_id) : null;
	}

	public function get_customer_mandate_url() {
		$id = App::get('id', 0, true);
		$token = App::get('token', '', true);
		$pa = $this->get_payment_account($id, $token);
		if(!$pa) return $this->access_denied();

		$pg_id = App::get('gateway', 0, true);
		$pg = new PaymentGateway($pg_id);
		if(!$pg->is_valid()) return $this->access_denied();

		$m = $pg->new_customer_mandate();
		if(!$m) return $this->error('Error getting redirect URL.');

		$_SESSION['gocardless_customer_account'] = implode('-', [ $pa->owner_type, $pa->owner_id, $pa->customer_type, $pa->customer_id ]);
		$_SESSION['gocardless_payment_gateway'] = $pg_id;
		$_SESSION['gocardless_from_login'] = 0;
		$_SESSION['gocardless_contract_id'] = 0;

		return $this->success($m->redirect_url);
	}

	public function get_details() {
		$id = App::get('id', 0, true);
		$token = App::get('token', '', true);
		$pa = $this->get_payment_account($id, $token);
		if(!$pa) return $this->access_denied();

		$info = $pa->get_details(0);
		if(!$info) return $this->error('There has been an error getting the account information. Please try again later.');

		$auth = false;
		if(isset($_SESSION['customer_id']) && $pa->customer_type = 'CU' && $pa->customer_id == $_SESSION['customer_id']) $auth = true;
		if(App::user()) $auth = true;
		$info['authenticated'] = $auth;

		foreach($info['transactions'] as &$txn) {
			if($txn['type'] === 'invoice' && $txn['invoice_id']) {
				$txn['invoice_url'] = APP_URL."/ajax/get/print_customer_invoice?id=$id&token=$token&invoice=$txn[invoice_id]";
			}
		}
		unset($txn);

		return $this->success($info);
	}

	public function sign_contract() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'token', 'contract', 'name'], '');

		$id = App::escape($data['id']);
		$token = App::escape($data['token']);
		$contract_id = App::escape($data['contract']);

		if(!$id) return $this->access_denied();

		$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
		if(!$pa) return $this->access_denied();

		$contract = App::select('contract', $contract_id);
		if(!$contract) return $this->access_denied();

		if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return $this->access_denied();

		if(!$data['name']) return $this->error('Please enter your name to sign the contract.');

		$contract_start_date = strtotime($contract['start_date']);
		$today = strtotime('today');

		$start_date = $contract['start_date'];
		$end_date = $contract['end_date'];
		if($contract_start_date < $today) {
			$start_date = date('Y-m-d');
			$end_date = null;
			if($contract['term'] && $contract['term_units']) {
				$end_date = date('Y-m-d', strtotime("+$contract[term] $contract[term_units]", strtotime($start_date)));
				$end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
			}
		}

		$result = App::update('contract', $contract_id, [
			'start_date' => $start_date,
			'end_date' => $end_date,
			'status' => 'pending',
			'pdf_contract_signature' => $data['name'],
			'pdf_contract_signed' => 1,
			'pdf_contract_signed_datetime' => App::now()
		]);

		if(!$result) return $this->error('An error has occurred. Please try again later.');

		// Even if the contract is in the future, we need to force out the first invoice after signing.
		// This allows the customer to pay the initial invoice right away, even though the contract might not start for a number of days/weeks.
		// This doesn't apply for contract invoices that are paid monthly in arrears.

		$c = new Contract($contract_id);
		$c->process(true);

		return $this->success();
	}

	/**
	 * Creates a Stripe checkout session for saving a new card, either with or without a payment.
	 * An amount of 0 means it will only add a new card without payment.
	 * An amount of -1 means it will pay the full outstanding balance (it can still resolve to 0 if there is nothing to pay).
	 */
	public function add_card() {
		$id = App::get('id', 0, true);
		$token = App::get('token', '', true);
		$gateway_id = App::get('gateway', 0, true);
		$amount_pence = App::get('amount_pence', 0, true);

		$pa = $this->get_payment_account($id, $token);
		if(!$pa) return $this->access_denied();

		$pg = new PaymentGateway($gateway_id);

		// Check if PaymentGateway is a valid Stripe account
		if(!$pg->is_valid() || !$pg->is_authorised()) return $this->access_denied();
		if($pg->record['type'] !== 'stripe') return $this->access_denied();

		// Check if PaymentAccount and PaymentGateway belongs to the same owner
		if($pa->owner_type != $pg->record['owner_type'] || $pa->owner_id != $pg->record['owner_id']) return $this->access_denied();

		// Resolve customer and balance details
		$cd = Customer::resolve_details($pa->customer_type, $pa->customer_id);
		$outstanding = $pa->get_outstanding_pence();
		$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg->id' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id' LIMIT 1;", MySQL::QUERY_ASSOC);

		// Validate amount
		if($amount_pence > 0 && $amount_pence != $outstanding) {
			// Custom part payment, validate
			if($amount_pence < $pg->record['part_minimum_pence']) return $this->error('Please enter at least the minimum amount.');
			if($amount_pence > $outstanding) return $this->error('You cannot pay more than the outstanding amount.');
		}
		if($amount_pence < 0) $amount_pence = $outstanding;

		// Set up checkout session to capture card details
		try {
			$options = [
				'billing_address_collection' => 'auto',
				'payment_method_types' => ['card'],
				'mode' => $amount_pence ? 'payment' : 'setup',
				'success_url' => $pa->get_account_url(),
				'cancel_url' => $pa->get_account_url()
			];

			if(!$card || !$amount_pence) {
				if($cd['email_address']) $options['customer_email'] = $cd['email_address'];
			}

			if($amount_pence) {
				// Set up session for immediate payment
				$options['line_items'] = [
					[
						'name' => 'Card Payment',
						'description' => "Card payment by customer $cd[name]",
						'amount' => $amount_pence,
						'currency' => 'gbp',
						'quantity' => 1
					]
				];

				$options['payment_intent_data'] = [
					'setup_future_usage' => 'off_session'
				];

				// For payments, send existing customer record if any
				if($card) $options['customer'] = $card['stripe_customer'];
			}

			\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
			$session = \Stripe\Checkout\Session::create($options, [ 'stripe_account' => $pg->record['stripe_user_id'] ]);
		} catch(Exception $ex) {
			return $this->error($ex->getMessage());
			return $this->error('Unable to set up checkout session.');
		}

		if(!$session) return $this->error('Invalid checkout session.');

		// Create checkout record to keep track of them
		App::insert('payment_stripe_checkout', [
			'id' => $session->id,
			'checkout_mode' => $amount_pence ? 'payment' : 'setup',
			'payment_account_id' => $pa->id,
			'payment_gateway_id' => $pg->id
		]);

		// Return Stripe's checkout session ID for redirect from the frontend
		return $this->success([
			'checkout_session_id' => $session->id,
			'stripe_user_id' => $pg->record['stripe_user_id']
		]);
	}

	/**
	 * Starts an on-session Stripe payment using the customer's saved card.
	 */
	public function pay_by_saved_card() {
		$id = App::get('id', 0, true);
		$token = App::get('token', '', true);
		$gateway_id = App::get('gateway', 0, true);
		$amount_pence = App::get('amount_pence', 0, true);

		$pa = $this->get_payment_account($id, $token);
		if(!$pa) return $this->access_denied();

		$pg = new PaymentGateway($gateway_id);

		// Check if PaymentGateway is a valid Stripe account
		if(!$pg->is_valid() || !$pg->is_authorised()) return $this->access_denied();
		if($pg->record['type'] !== 'stripe') return $this->access_denied();

		// Check if PaymentAccount and PaymentGateway belongs to the same owner
		if($pa->owner_type != $pg->record['owner_type'] || $pa->owner_id != $pg->record['owner_id']) return $this->access_denied();

		// Resolve customer and balance details
		$cd = Customer::resolve_details($pa->customer_type, $pa->customer_id);
		$outstanding = $pa->get_outstanding_pence();
		$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg->id' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id' LIMIT 1;", MySQL::QUERY_ASSOC);

		if(!$card) return $this->error('Saved card not found. Please add a new payment card.');
		if(!$outstanding) return $this->error('There is nothing to pay at the moment.');

		\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

		// Validate amount
		if($amount_pence > 0 && $amount_pence != $outstanding) {
			// Custom part payment, validate
			if($amount_pence < $pg->record['part_minimum_pence']) return $this->error('Please enter at least the minimum amount.');
			if($amount_pence > $outstanding) return $this->error('You cannot pay more than the outstanding amount.');
		}
		if($amount_pence < 0) $amount_pence = $outstanding;

		// Find customer's default payment method
		$payment_method_id = null;
		$customer = \Stripe\Customer::retrieve($card['stripe_customer'], ['stripe_account' => $pg->record['stripe_user_id']]);
		if($customer->invoice_settings->default_payment_method) {
			$payment_method_id = $customer->invoice_settings->default_payment_method;
		} else {
			// No default is set, see if customer has any card details saved
			$list = \Stripe\PaymentMethod::all(
				['customer' => $card['stripe_customer'], 'type' => 'card'],
				['stripe_account' => $pg->record['stripe_user_id']]
			) ?: [];

			// Pick the first saved card. If the customer is correctly managed by our system,
			// there should only ever be one saved card.
			foreach($list as $pm) if(!$payment_method_id) $payment_method_id = $pm->id;
		}

		if(!$payment_method_id) return $this->error('Default payment method not found. Please add a new payment card.');

		$intent = \Stripe\PaymentIntent::create([
			'amount' => $amount_pence,
			'currency' => 'gbp',
			'customer' => $card['stripe_customer'],
			'payment_method' => $payment_method_id
		], ['stripe_account' => $pg->record['stripe_user_id']]);

		// Create payment intent record to keep track of them
		App::insert('payment_stripe_intent', [
			'id' => $intent->id,
			'off_session' => 0,
			'payment_account_id' => $pa->id,
			'payment_gateway_id' => $pg->id
		]);

		return $this->success([
			'client_secret' => $intent->client_secret,
			'stripe_user_id' => $pg->record['stripe_user_id']
		]);
	}

	/**
	 * Starts an on-session Stripe payment using the customer's saved card.
	 * This is to upgrade the account to a specific ISP package.
	 */
	public function upgrade_by_saved_card() {
		$id = App::get('id', 0, true);
		$token = App::get('token', '', true);
		$gateway_id = App::get('gateway', 0, true);
		$contract_id = App::get('contract', 0, true);
		$package_id = App::get('package', 0, true);

		$pa = $this->get_payment_account($id, $token);
		if(!$pa) return $this->access_denied();

		$pg = new PaymentGateway($gateway_id);

		// Check if PaymentGateway is a valid Stripe account
		if(!$pg->is_valid() || !$pg->is_authorised()) return $this->access_denied();
		if($pg->record['type'] !== 'stripe') return $this->access_denied();

		// Check if PaymentAccount and PaymentGateway belongs to the same owner
		if($pa->owner_type != $pg->record['owner_type'] || $pa->owner_id != $pg->record['owner_id']) return $this->access_denied();

		// Resolve customer and balance details
		$cd = Customer::resolve_details($pa->customer_type, $pa->customer_id);
		$outstanding = $pa->get_outstanding_pence();
		$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg->id' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id' LIMIT 1;", MySQL::QUERY_ASSOC);

		if(!$card) return $this->error('Saved card not found. Please add a new payment card.');
		if($outstanding) return $this->error('Please pay your outstanding balance before upgrading your service.');

		// Find package and select price payable now.
		$upgrades_json = $this->list_possible_upgrades($id, $token, $contract_id);
		$upgrades = json_decode($upgrades_json, true);
		if($upgrades['status'] !== 'OK') return $upgrades_json;

		$amount_pence = 0;
		$net_pence = 0;
		foreach($upgrades['data']['packages'] as $p) {
			if($p['id'] == $package_id) {
				$amount_pence = (int)($p['upgrade_total'] * 100);
				$net_pence = (int)($p['upgrade_price'] * 100);
			}
		}
		if(!$amount_pence) return $this->error('Cannot find package upgrade. Please try again later.');

		\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

		// Find customer's default payment method
		$payment_method_id = null;
		$customer = \Stripe\Customer::retrieve($card['stripe_customer'], ['stripe_account' => $pg->record['stripe_user_id']]);
		if($customer->invoice_settings->default_payment_method) {
			$payment_method_id = $customer->invoice_settings->default_payment_method;
		} else {
			// No default is set, see if customer has any card details saved
			$list = \Stripe\PaymentMethod::all(
				['customer' => $card['stripe_customer'], 'type' => 'card'],
				['stripe_account' => $pg->record['stripe_user_id']]
			) ?: [];

			// Pick the first saved card. If the customer is correctly managed by our system,
			// there should only ever be one saved card.
			foreach($list as $pm) if(!$payment_method_id) $payment_method_id = $pm->id;
		}

		if(!$payment_method_id) return $this->error('Default payment method not found. Please add a new payment card.');

		$intent = \Stripe\PaymentIntent::create([
			'amount' => $amount_pence,
			'currency' => 'gbp',
			'customer' => $card['stripe_customer'],
			'payment_method' => $payment_method_id
		], ['stripe_account' => $pg->record['stripe_user_id']]);

		// Create payment intent record to keep track of them
		App::insert('payment_stripe_intent', [
			'id' => $intent->id,
			'off_session' => 0,
			'payment_account_id' => $pa->id,
			'payment_gateway_id' => $pg->id,
			'upgrade_contract_id' => $contract_id,
			'upgrade_isp_package_id' => $package_id,
			'upgrade_net_pence' => $net_pence
		]);

		return $this->success([
			'client_secret' => $intent->client_secret,
			'stripe_user_id' => $pg->record['stripe_user_id']
		]);
	}

	/**
	 * Creates a Stripe checkout session for saving a new card, an does the upgrade.
	 */
	public function upgrade_by_new_card() {
		$id = App::get('id', 0, true);
		$token = App::get('token', '', true);
		$gateway_id = App::get('gateway', 0, true);
		$contract_id = App::get('contract', 0, true);
		$package_id = App::get('package', 0, true);

		$pa = $this->get_payment_account($id, $token);
		if(!$pa) return $this->access_denied();

		$pg = new PaymentGateway($gateway_id);

		// Check if PaymentGateway is a valid Stripe account
		if(!$pg->is_valid() || !$pg->is_authorised()) return $this->access_denied();
		if($pg->record['type'] !== 'stripe') return $this->access_denied();

		// Check if PaymentAccount and PaymentGateway belongs to the same owner
		if($pa->owner_type != $pg->record['owner_type'] || $pa->owner_id != $pg->record['owner_id']) return $this->access_denied();

		// Resolve customer and balance details
		$cd = Customer::resolve_details($pa->customer_type, $pa->customer_id);
		$outstanding = $pa->get_outstanding_pence();
		$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg->id' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id' LIMIT 1;", MySQL::QUERY_ASSOC);

		// Don't let them upgrade if they have outstanding balance
		if($outstanding) return $this->error('Please pay your outstanding balance before upgrading your service.');

		// Find package and select price payable now.
		$upgrades_json = $this->list_possible_upgrades($id, $token, $contract_id);
		$upgrades = json_decode($upgrades_json, true);
		if($upgrades['status'] !== 'OK') return $upgrades_json;

		$amount_pence = 0;
		$net_pence = 0;
		foreach($upgrades['data']['packages'] as $p) {
			if($p['id'] == $package_id) {
				$amount_pence = (int)($p['upgrade_total'] * 100);
				$net_pence = (int)($p['upgrade_price'] * 100);
			}
		}
		if(!$amount_pence) return $this->error('Cannot find package upgrade. Please try again later.');

		// Set up checkout session to capture card details
		try {
			$options = [
				'billing_address_collection' => 'auto',
				'payment_method_types' => ['card'],
				'mode' => $amount_pence ? 'payment' : 'setup',
				'success_url' => $pa->get_account_url(),
				'cancel_url' => $pa->get_account_url()
			];

			if(!$card || !$amount_pence) {
				if($cd['email_address']) $options['customer_email'] = $cd['email_address'];
			}

			if($amount_pence) {
				// Set up session for immediate payment
				$options['line_items'] = [
					[
						'name' => 'Card Payment',
						'description' => "Card payment by customer $cd[name]",
						'amount' => $amount_pence,
						'currency' => 'gbp',
						'quantity' => 1
					]
				];

				$options['payment_intent_data'] = [
					'setup_future_usage' => 'off_session'
				];

				// For payments, send existing customer record if any
				if($card) $options['customer'] = $card['stripe_customer'];
			}

			\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
			$session = \Stripe\Checkout\Session::create($options, [ 'stripe_account' => $pg->record['stripe_user_id'] ]);
		} catch(Exception $ex) {
			return $this->error($ex->getMessage());
			return $this->error('Unable to set up checkout session.');
		}

		if(!$session) return $this->error('Invalid checkout session.');

		// Create checkout record to keep track of them
		App::insert('payment_stripe_checkout', [
			'id' => $session->id,
			'checkout_mode' => $amount_pence ? 'payment' : 'setup',
			'payment_account_id' => $pa->id,
			'payment_gateway_id' => $pg->id,
			'upgrade_contract_id' => $contract_id,
			'upgrade_isp_package_id' => $package_id,
			'upgrade_net_pence' => $net_pence
		]);

		// Return Stripe's checkout session ID for redirect from the frontend
		return $this->success([
			'checkout_session_id' => $session->id,
			'stripe_user_id' => $pg->record['stripe_user_id']
		]);
	}

	private function get_cancel_date_message($date, $contract) {
		if($contract['status'] !== 'active') return 'You can only cancel an active contract.';
		if($contract['contract_term'] === 'fixed') return 'You cannot cancel a fixed term contract.';
		if(strtotime($date) < strtotime($contract['end_date'])) return 'The date you\'ve selected is within the mandatory period.';

		$today = date('Y-m-d');
		$today30 = strtotime('+30 days', strtotime($today));
		if(strtotime($date) < $today30) return 'The date you\'ve selected is within 30 days.';

		return '';
	}

	public function check_cancel_date() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'token', 'contract', 'cancel_date'], '');

		$id = App::escape($data['id']);
		$token = App::escape($data['token']);
		$contract_id = App::escape($data['contract']);

		if(!$id) return $this->access_denied();

		$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
		if(!$pa) return $this->access_denied();

		$contract = App::select('contract', $contract_id);
		if(!$contract) return $this->access_denied();

		if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return $this->access_denied();

		if(!$data['cancel_date']) return $this->error('Please select a contract end date.');

		$message = $this->get_cancel_date_message($data['cancel_date'], $contract);

		return $this->success([
			'can_cancel' => !$message,
			'message' => $message
		]);
	}

	public function cancel_contract() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'token', 'contract', 'cancel_date'], '');

		$id = App::escape($data['id']);
		$token = App::escape($data['token']);
		$contract_id = App::escape($data['contract']);

		if(!$id) return $this->access_denied();

		$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
		if(!$pa) return $this->access_denied();

		$contract = App::select('contract', $contract_id);
		if(!$contract) return $this->access_denied();

		if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return $this->access_denied();

		if(!$data['cancel_date']) return $this->error('Please select a contract end date.');

		$message = $this->get_cancel_date_message($data['cancel_date'], $contract);
		if($message) return $this->error($message);

		$result = App::update('contract', $contract_id, [
			'status' => 'ending',
			'end_date' => $data['cancel_date']
		]);

		if(!$result) return $this->error('An error has occurred. Please try again later.');

		return $this->success();
	}

	private function list_possible_upgrades($id, $token, $contract_id) {
		if(!$id) return $this->access_denied();

		$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
		if(!$pa) return $this->access_denied();

		$contract = App::select('contract', $contract_id);
		if(!$contract) return $this->access_denied();

		if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return $this->access_denied();

		// Get current package for contract
		$result = App::sql()->query_row(
			"SELECT cil.isp_package_id AS id, ci.last_period_end_date AS billed_date, ci.vat_rate, IF(cil.type = 'isp_package_custom', cil.unit_price, 0) AS unit_price
			FROM contract_invoice AS ci
			JOIN contract_invoice_line AS cil ON cil.contract_invoice_id = ci.id
			WHERE ci.contract_id = '$contract_id' AND cil.type IN ('isp_package', 'isp_package_custom')
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$result) return $this->error('Cannot find current package.');

		$package_id = $result['id'];
		$billed_date = $result['billed_date'];
		if(!$billed_date) return $this->error('An error has occurred. Please try again later.');
		$monthly_date = date('Y-m-d', strtotime('+1 day', strtotime($billed_date)));
		$vat_rate = $result['vat_rate'];
		$custom_package_price = $result['unit_price'];

		$today = date('Y-m-d');
		$datediff = strtotime($billed_date) - strtotime($today);
		$days = round($datediff / (60 * 60 * 24));
		if($days < 0) $days = 0;

		// Get packages for contract
		$area = new ISPArea($contract['area_id']);
		if(!$area->validate()) return $this->access_denied();

		$building = $area->get_building();
		if(!$building->validate()) return $this->access_denied();

		$packages = $building->list_packages();

		// Get current price
		$current_price = 0;
		foreach($packages as $p) {
			if($p->id == $package_id) $current_price = $p->record['monthly_price'];
		}
		if($custom_package_price) $current_price = $custom_package_price;

		// Evaluate packages
		$list = [];
		foreach($packages as $p) {
			if($p->record['monthly_price'] <= $current_price) continue;

			$monthly_price = $p->record['monthly_price'];
			$monthly_vat = round($monthly_price * ($vat_rate / 100), 2);
			$monthly_total = $monthly_price + $monthly_vat;

			$upgrade_price = round((($monthly_price - $current_price) / 30) * $days, 2);
			$upgrade_vat = round($upgrade_price * ($vat_rate / 100), 2);
			$upgrade_total = $upgrade_price + $upgrade_vat;

			// Valid package to upgrade to
			$item = [
				'id' => $p->id,
				'description' => $p->record['description'],
				'monthly_price' => $monthly_price,
				'monthly_vat' => $monthly_vat,
				'monthly_total' => $monthly_total,
				'monthly_date' => $monthly_date,
				'days' => $days,
				'billed_date' => $billed_date,
				'upgrade_price' => $upgrade_price,
				'upgrade_vat' => $upgrade_vat,
				'upgrade_total' => $upgrade_total
			];

			$list[] = $item;
		}

		return $this->success([
			'packages' => $list
		]);
	}

	public function get_upgrade_info() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'token', 'contract'], '');

		$id = App::escape($data['id']);
		$token = App::escape($data['token']);
		$contract_id = App::escape($data['contract']);

		return $this->list_possible_upgrades($id, $token, $contract_id);
	}

	public function get_support_info() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'token', 'contract'], '');

		$id = App::escape($data['id']);
		$token = App::escape($data['token']);
		$contract_id = App::escape($data['contract']);

		if(!$id) return $this->access_denied();

		$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
		if(!$pa) return $this->access_denied();

		$contract = App::select('contract', $contract_id);
		if(!$contract) return $this->access_denied();

		if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return $this->access_denied();

		$area = new ISPArea($contract['area_id']);
		if(!$area->validate()) return $this->error('An error has occurred. Please try again later.');

		$onu = $area->get_onu();
		if(!$onu) return $this->error('Cannot find router.');

		$info_offline = null;
		$info_reboot = null;
		if($onu->record['type_id']) {
			$type_record = App::select('onu_type@isp', $onu->record['type_id']);
			if($type_record) {
				$info_offline = $type_record['info_offline'];
				$info_reboot = $type_record['info_reboot'];
			}
		}

		return $this->success([
			'router_status' => $onu->record['status'],
			'wifi_ssid' => $onu->record['wifi_ssid'],
			'wifi_password' => $onu->record['wifi_password'],
			'info_offline' => $info_offline,
			'info_reboot' => $info_reboot
		]);
	}

	public function fix_my_internet() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'token', 'contract'], '');

		$id = App::escape($data['id']);
		$token = App::escape($data['token']);
		$contract_id = App::escape($data['contract']);

		if(!$id) return $this->access_denied();

		$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
		if(!$pa) return $this->access_denied();

		$contract = App::select('contract', $contract_id);
		if(!$contract) return $this->access_denied();

		if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return $this->access_denied();

		$area = new ISPArea($contract['area_id']);
		if(!$area->validate()) return $this->error('An error has occurred. Please try again later.');

		$onu = $area->get_onu();
		if(!$onu) return $this->error('Cannot find router.');

		$onu->reboot();

		return $this->success();
	}

}
