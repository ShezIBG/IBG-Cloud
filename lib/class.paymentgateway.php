<?php

class PaymentGateway {

	//
	// Static
	//

	const TYPE_GOCARDLESS = 'gocardless';
	const TYPE_STRIPE = 'stripe';

	public static function create($record) {
		$record = App::keep($record, ['owner_type', 'owner_id', 'type', 'description', 'allow_part_payment', 'part_minimum_pence']);
		$record = App::ensure($record, ['owner_type', 'owner_id', 'type', 'description'], '');
		$record = App::ensure($record, ['allow_part_payment', 'part_minimum_pence'], 0);

		if(!$record['owner_type'] || !$record['type'] || !$record['description']) return null;
		if($record['owner_type'] !== 'E' && !$record['owner_id']) return null;

		$record['date_created'] = App::now();

		$id = App::insert('payment_gateway', $record);

		if($id) {
			$gateway = new PaymentGateway($id);
			if($gateway->is_valid()) return $gateway;
		}

		return null;
	}

	public static function new_record($owner_type, $owner_id, $type) {
		$description = '';
		switch($type) {
			case self::TYPE_GOCARDLESS: $description = 'GoCardless account'; break;
			case self::TYPE_STRIPE: $description = 'Stripe account'; break;
		}

		return [
			'owner_type' => $owner_type,
			'owner_id' => $owner_id,
			'type' => $type,
			'description' => $description,
			'allow_part_payment' => 0,
			'part_minimum_pence' => 0
		];
	}

	public static function is_valid_type($type) {
		return $type === self::TYPE_GOCARDLESS || $type === self::TYPE_STRIPE;
	}

	public static function get_gocardless_settings() {
		$is_sandbox = substr(GOCARDLESS_ACCESS_TOKEN, 0, 8) === "sandbox_";

		return [
			'client_id'        => GOCARDLESS_APP_CLIENT_ID,
			'client_secret'    => GOCARDLESS_APP_CLIENT_SECRET,
			'url'              => $is_sandbox ? 'https://connect-sandbox.gocardless.com/oauth/' : 'https://connect.gocardless.com/oauth/',
			'redirect'         => APP_URL.'/gocardless_auth.php',
			'access_token'     => GOCARDLESS_ACCESS_TOKEN,
			'environment'      => $is_sandbox ? \GoCardlessPro\Environment::SANDBOX : \GoCardlessPro\Environment::LIVE,
			'verification_url' => $is_sandbox ? 'https://verify-sandbox.gocardless.com/' : 'https://verify.gocardless.com/'
		];
	}

	public static function get_stripe_settings() {
		$is_sandbox = substr(STRIPE_SECRET_KEY, 0, 8) === "sk_test_";

		return [
			'client_id'       => STRIPE_CONNECT_CLIENT_ID,
			'secret_key'      => STRIPE_SECRET_KEY,
			'publishable_key' => STRIPE_PUBLISHABLE_KEY,
			'url'             => 'https://connect.stripe.com/oauth/',
			'redirect'        => APP_URL.'/stripe_auth.php'
		];
	}

	//
	// Instance
	//

	public $id;
	public $record;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->reload();
	}

	public function reload() {
		$this->record = App::select('payment_gateway', $this->id);
	}

	public function is_valid() {
		return !!$this->record;
	}

	public function user_has_access() {
		return Permission::get($this->record['owner_type'], $this->record['owner_id'])->check(Permission::ADMIN);
	}

	public function is_authorised() {
		return !!$this->record['authorised'];
	}

	public function is_archived() {
		return !!$this->record['archived'];
	}

	public function generate_auth_secret() {
		$secret = App::new_uid();
		App::update('payment_gateway', $this->id, [ 'auth_secret' => $secret ]);
		$this->reload();
		return $secret;
	}

	public function validate_auth_secret($secret) {
		return $secret === $this->record['auth_secret'];
	}

	public function get_owner_record() {
		switch($this->record['owner_type']) {
			case 'E': [];
			case 'SP': return App::select('service_provider', $this->record['owner_id']);
			case 'SI': return App::select('system_integrator', $this->record['owner_id']);
			case 'HG': return App::select('holding_group', $this->record['owner_id']);
			case 'C': return App::select('client', $this->record['owner_id']);
		}

		return null;
	}

	public function get_gocardless_prefill() {
		$prefill = [];
		$owner = $this->get_owner_record();

		switch($this->record['owner_type']) {
			case 'E':
				$prefill['email'] = APP_EMAIL;
				$prefill['organisation_name'] = 'Eticom Ltd';
				break;

			case 'SP':
				$prefill['organisation_name'] = $owner['company_name'];
				break;

			case 'SI':
				$prefill['organisation_name'] = $owner['company_name'];
				break;

			case 'HG':
				$prefill['organisation_name'] = $owner['company_name'];
				break;

			case 'C':
				$prefill['organisation_name'] = $owner['name'];
				break;
		}

		return $prefill;
	}

	/**
	 * Request oauth URL to redirect the user to authorise the payment gateway.
	 */
	public function get_authorisation_url() {
		switch($this->record['type']) {

			case self::TYPE_GOCARDLESS:
				$settings = self::get_gocardless_settings();
				$client = new OAuth2\Client($settings['client_id'], $settings['client_secret']);

				return $client->getAuthenticationUrl(
					$settings['url'].'authorize',
					$settings['redirect'],
					[
						'scope' => 'read_write',
						'prefill' => $this->get_gocardless_prefill(),
						'state' => $this->id.'-'.$this->generate_auth_secret()
					]
				);

			case self::TYPE_STRIPE:
				$settings = self::get_stripe_settings();
				$client = new OAuth2\Client($settings['client_id'], $settings['secret_key']);

				return $client->getAuthenticationUrl(
					$settings['url'].'authorize',
					$settings['redirect'],
					[
						'scope' => 'read_write',
						'response_type' => 'code',
						'state' => $this->id.'-'.$this->generate_auth_secret()
					]
				);

		}

		return null;
	}

	/**
	 * Requests mandate to connect a customer account.
	 * Must be called from a valid URL context (browser only)
	 */
	public function new_customer_mandate() {
		if($this->record['type'] !== self::TYPE_GOCARDLESS) return '';

		$settings = self::get_gocardless_settings();
		$prefill = $this->get_gocardless_prefill();

		$options = [
			'access_token' => $this->record['gocardless_access_token'],
			'environment' => $settings['environment']
		];

		$client = new \GoCardlessPro\Client($options);

		return $client->redirectFlows()->create([
			'params' => [
				'description'          => $prefill['organisation_name'],
				'session_token'        => session_id(),
				'success_redirect_url' => APP_URL.'/gocardless_customer_flow.php'
			]
		]);
	}

	public function complete_customer_mandate($flow_id) {
		if($this->record['type'] !== self::TYPE_GOCARDLESS) return null;

		$settings = self::get_gocardless_settings();

		$options = [
			'access_token' => $this->record['gocardless_access_token'],
			'environment' => $settings['environment']
		];

		$client = new \GoCardlessPro\Client($options);

		return $client->redirectFlows()->complete($flow_id, [
			'params' => [
				'session_token' => session_id()
			]
		]);
	}

	public function get_gocardless_verification_status() {
		if($this->record['type'] !== self::TYPE_GOCARDLESS) return '';

		$settings = self::get_gocardless_settings();

		$options = [
			'access_token' => $this->record['gocardless_access_token'],
			'environment' => $settings['environment']
		];

		$client = new \GoCardlessPro\Client($options);

		$creditor = $client->creditors()->list()->records[0];

		App::update('payment_gateway', $this->id, [ 'gocardless_status' => $creditor->verification_status ]);

		return $creditor->verification_status;
	}

	public function cancel_payment($payment_id) {
		if($this->record['type'] !== self::TYPE_GOCARDLESS) return false;

		$settings = self::get_gocardless_settings();

		$options = [
			'access_token' => $this->record['gocardless_access_token'],
			'environment' => $settings['environment']
		];

		$client = new \GoCardlessPro\Client($options);

		$client->payments()->cancel($payment_id);
		return true;
	}

	/**
	 * Authorise payment gateway by checking and processing the information returned by oauth redirect.
	 * Must be called on oauth return, with $_GET variables intact.
	 */
	public function authorise() {
		if(!$this->record['auth_secret']) return;

		switch($this->record['type']) {

			case self::TYPE_GOCARDLESS:
				if(isset($_GET['state'])) {
					$auth_secret = explode('-', App::get('state', '', true), 2)[1];
					if($this->validate_auth_secret($auth_secret)) {
						if (isset($_GET['code'])) {
							$settings = self::get_gocardless_settings();
							$client = new OAuth2\Client($settings['client_id'], $settings['client_secret']);

							$response = $client->getAccessToken(
								$settings['url'].'access_token',
								'authorization_code',
								[
									'code'         => $_GET['code'],
									'redirect_uri' => $settings['redirect']
								]
							);

							$access_token = $response['result']['access_token'] ?: null;
							$organisation_id = $response['result']['organisation_id'] ?: null;

							if($access_token && $organisation_id) {
								App::update('payment_gateway', $this->id, [
									'date_authorised' => App::now(),
									'authorised' => 1,
									'archived' => 0,
									'gocardless_access_token' => $access_token,
									'gocardless_organisation_id' => $organisation_id,
									'auth_secret' => null
								]);
								$this->reload();
							}
						}
					}
				}
				break;

			case self::TYPE_STRIPE:
				if(isset($_GET['state'])) {
					$auth_secret = explode('-', App::get('state', '', true), 2)[1];
					if($this->validate_auth_secret($auth_secret)) {
						if (isset($_GET['code'])) {
							$settings = self::get_stripe_settings();
							$client = new OAuth2\Client($settings['client_id'], $settings['secret_key']);

							$response = $client->getAccessToken(
								$settings['url'].'token',
								'authorization_code',
								[
									'code' => $_GET['code'],
									'redirect_uri' => $settings['redirect']
								]
							);

							$stripe_user_id = $response['result']['stripe_user_id'] ?: null;
							$stripe_refresh_token = $response['result']['refresh_token'] ?: null;
							$stripe_access_token = $response['result']['access_token'] ?: null;

							if($stripe_user_id && $stripe_refresh_token && $stripe_access_token) {
								App::update('payment_gateway', $this->id, [
									'date_authorised' => App::now(),
									'authorised' => 1,
									'archived' => 0,
									'stripe_user_id' => $stripe_user_id,
									'stripe_refresh_token' => $stripe_refresh_token,
									'stripe_access_token' => $stripe_access_token,
									'auth_secret' => null
								]);
								$this->reload();
							}
						}
					}
				}
				break;

		}
	}

	public function get_account_url_path() {
		$tab = 'payment-gateways';
		$owner_id = $this->record['owner_id'];

		switch($this->record['owner_type']) {
			case 'E': return "eticom/$tab";
			case 'SP': return "service-provider/$owner_id/$tab";
			case 'SI': return "system-integrator/$owner_id/$tab";
			case 'HG': return "holding-group/$owner_id/$tab";
			case 'C': return "client/$owner_id/$tab";
		}

		return '';
	}

}

class PaymentGoCardlessMandate {

	//
	// Static
	//

	public static function get($payment_gateway_id, $customer_type, $customer_id) {
		$mandate = new PaymentGoCardlessMandate($payment_gateway_id, $customer_type, $customer_id);
		return $mandate->is_valid() ? $mandate : null;
	}

	public static function request($payment_gateway_id, $customer_type, $customer_id) {
		$existing = self::get($payment_gateway_id, $customer_type, $customer_id);
		if($existing) return $existing;

		App::insert('payment_gocardless_mandate', [
			'payment_gateway_id' => $payment_gateway_id,
			'customer_type' => $customer_type,
			'customer_id' => $customer_id,
			'status' => 'request',
			'date_requested' => App::now()
		]);

		return self::get($payment_gateway_id, $customer_type, $customer_id);
	}

	//
	// Instance
	//

	public $payment_gateway_id;
	public $customer_type;
	public $customer_id;
	public $record;

	public function __construct($payment_gateway_id, $customer_type, $customer_id) {
		$this->payment_gateway_id = App::escape($payment_gateway_id);
		$this->customer_type = App::escape($customer_type);
		$this->customer_id = App::escape($customer_id);
		$this->reload();
	}

	public function reload() {
		$this->record = App::sql()->query_row(
			"SELECT * FROM payment_gocardless_mandate
			WHERE payment_gateway_id = '$this->payment_gateway_id' AND customer_type = '$this->customer_type' AND customer_id = '$this->customer_id';
		", MySQL::QUERY_ASSOC, false);
	}

	public function is_valid() {
		return !!$this->record;
	}

	public function get_payment_gateway() {
		$pg = new PaymentGateway($this->record['payment_gateway_id']);
		return $pg->is_valid() ? $pg : null;
	}

	public function is_request() { return $this->record['status'] === 'request'; }
	public function is_authorised() { return $this->record['status'] === 'authorised'; }
	public function is_cancelled() { return $this->record['status'] === 'cancelled'; }

	public function authorise($gocardless_mandate_id, $gocardless_customer_id) {
		if($gocardless_mandate_id && $gocardless_customer_id) {
			$gocardless_mandate_id = App::escape($gocardless_mandate_id);
			$gocardless_customer_id = App::escape($gocardless_customer_id);

			App::sql()->update(
				"UPDATE payment_gocardless_mandate
				SET
					status = 'authorised',
					date_authorised = NOW(),
					gocardless_mandate_id = '$gocardless_mandate_id',
					gocardless_customer_id = '$gocardless_customer_id'
				WHERE payment_gateway_id = '$this->payment_gateway_id' AND customer_type = '$this->customer_type' AND customer_id = '$this->customer_id';
			");

			$this->reload();

			// Check if customer account is up-to-date and fire "fully paid" event
			$pg = $this->get_payment_gateway();
			if($pg) {
				$pa = new PaymentAccount($pg->record['owner_type'], $pg->record['owner_id'], $this->customer_type, $this->customer_id);
				$pa->process_after_balance_changed();
			}
		}
	}

	public function get_mandate_object() {
		if(!$this->is_authorised()) return null;

		$pg = $this->get_payment_gateway();
		if(!$pg) return null;

		if($pg->record['type'] !== PaymentGateway::TYPE_GOCARDLESS) return null;

		$settings = PaymentGateway::get_gocardless_settings();

		$options = [
			'access_token' => $pg->record['gocardless_access_token'],
			'environment' => $settings['environment']
		];

		$client = new \GoCardlessPro\Client($options);

		return $client->mandates()->get($this->record['gocardless_mandate_id']);
	}

	public function create_payment($amount, $description, $invoice_id, $nonce, $charge_date = null) {
		if(!$this->is_authorised()) return null;

		$pg = $this->get_payment_gateway();
		if(!$pg) return null;

		if($pg->record['type'] !== PaymentGateway::TYPE_GOCARDLESS) return null;

		$settings = PaymentGateway::get_gocardless_settings();

		$options = [
			'access_token' => $pg->record['gocardless_access_token'],
			'environment' => $settings['environment']
		];

		$client = new \GoCardlessPro\Client($options);

		$nonce = $this->customer_type.'-'.$this->customer_id.'-'.$this->payment_gateway_id.'-'.$invoice_id.'-'.$nonce;

		$payment_options = [
			'params' => [
				'amount'   => $amount,
				'currency' => 'GBP',
				'description' => $description,
				'metadata' => [
					'invoice_id' => "$invoice_id"
				],
				'links'    => [
					'mandate' => $this->record['gocardless_mandate_id']
				]
			],
			'headers' => [ 'Idempotency-Key' => $nonce ]
		];

		if($charge_date) $payment_options['params']['charge_date'] = $charge_date;

		$payment = $client->payments()->create($payment_options);

		return $payment;
	}

	public function cancel() {
		if(!$this->is_authorised()) return false;

		$pg = $this->get_payment_gateway();
		if(!$pg) return false;

		if($pg->record['type'] !== PaymentGateway::TYPE_GOCARDLESS) return false;

		$settings = PaymentGateway::get_gocardless_settings();

		$options = [
			'access_token' => $pg->record['gocardless_access_token'],
			'environment' => $settings['environment']
		];

		try {
			$client = new \GoCardlessPro\Client($options);
			$client->mandates()->cancel($this->record['gocardless_mandate_id']);
		} catch(Exception $ex) {
			return false;
		}

		return true;
	}

}
