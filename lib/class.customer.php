<?php

class Customer {

	const TYPE_ETICOM = 'E';
	const TYPE_SERVICE_PROVIDER = 'SP';
	const TYPE_SYSTEM_INTEGRATOR = 'SI';
	const TYPE_HOLDING_GROUP = 'HG';
	const TYPE_CLIENT = 'C';
	const TYPE_CUSTOMER = 'CU';

	public $id = 0;
	public $record = null;

	public static function resolve_details($customer_type = null, $customer_id = null) {
		if($customer_type === null || $customer_id === null) {
			// Return empty record (fallback if you don't want to do a null check)
			return [
				'customer_type' => '',
				'customer_id' => 0,
				'name' => '',
				'contact_name' => '',
				'company_name' => '',
				'email_address' => '',
				'phone_number' => '',
				'mobile_number' => '',
				'address_line_1' => '',
				'address_line_2' => '',
				'address_line_3' => '',
				'posttown' => '',
				'postcode' => '',
				'invoice_address_line_1' => '',
				'invoice_address_line_2' => '',
				'invoice_address_line_3' => '',
				'invoice_posttown' => '',
				'invoice_postcode' => '',
				'bank_name' => '',
				'bank_sort_code' => '',
				'bank_account_number' => '',
				'vat_reg_number' => '',
				'logo_on_light_id' => '',
				'logo_on_dark_id' => '',
				'signup_hash' => ''
			];
		}

		switch($customer_type) {
			case 'E':
				$si = App::select('system_integrator', 1);

				$d = [
					'contact_name' => '',
					'company_name' => 'Eticom Ltd',
					'email_address' => '',
					'phone_number' => '',
					'mobile_number' => '',
					'address_line_1' => '',
					'address_line_2' => '',
					'address_line_3' => '',
					'posttown' => '',
					'postcode' => '',
					'invoice_address_line_1' => '',
					'invoice_address_line_2' => '',
					'invoice_address_line_3' => '',
					'invoice_posttown' => '',
					'invoice_postcode' => '',
					'bank_name' => '',
					'bank_sort_code' => '',
					'bank_account_number' => '',
					'vat_reg_number' => '',
					'logo_on_light_id' => $si['logo_on_light_id'] ?: '',
					'logo_on_dark_id' => $si['logo_on_dark_id'] ?: '',
					'signup_hash' => ''
				];
				break;

			case 'SP':
				$r = App::select('service_provider', $customer_id);
				if(!$r) return null;

				$d = [
					'contact_name' => '',
					'company_name' => $r['company_name'] ?: '',
					'email_address' => $r['email_address'] ?: '',
					'phone_number' => $r['phone_number'] ?: '',
					'mobile_number' => $r['mobile_number'] ?: '',
					'address_line_1' => $r['address_line_1'] ?: '',
					'address_line_2' => $r['address_line_2'] ?: '',
					'address_line_3' => $r['address_line_3'] ?: '',
					'posttown' => $r['posttown'] ?: '',
					'postcode' => $r['postcode'] ?: '',
					'invoice_address_line_1' => $r['invoice_address_line_1'] ?: '',
					'invoice_address_line_2' => $r['invoice_address_line_2'] ?: '',
					'invoice_address_line_3' => $r['invoice_address_line_3'] ?: '',
					'invoice_posttown' => $r['invoice_posttown'] ?: '',
					'invoice_postcode' => $r['invoice_postcode'] ?: '',
					'bank_name' => $r['bank_name'] ?: '',
					'bank_sort_code' => $r['bank_sort_code'] ?: '',
					'bank_account_number' => $r['bank_account_number'] ?: '',
					'vat_reg_number' => $r['vat_reg_number'] ?: '',
					'logo_on_light_id' => '',
					'logo_on_dark_id' => '',
					'signup_hash' => ''
				];

				// TODO: FIXME: This should not be hard-coded. Needs adding to SP record.
				if($customer_id == 1) {
					$si = App::select('system_integrator', 1);
					$d['logo_on_light_id'] = $si['logo_on_light_id'] ?: '';
					$d['logo_on_dark_id'] = $si['logo_on_dark_id'] ?: '';
				}
				break;

			case 'SI':
				$r = App::select('system_integrator', $customer_id);
				if(!$r) return null;

				$d = [
					'contact_name' => '',
					'company_name' => $r['company_name'] ?: '',
					'email_address' => $r['email_address'] ?: '',
					'phone_number' => $r['phone_number'] ?: '',
					'mobile_number' => $r['mobile_number'] ?: '',
					'address_line_1' => $r['address_line_1'] ?: '',
					'address_line_2' => $r['address_line_2'] ?: '',
					'address_line_3' => $r['address_line_3'] ?: '',
					'posttown' => $r['posttown'] ?: '',
					'postcode' => $r['postcode'] ?: '',
					'invoice_address_line_1' => $r['invoice_address_line_1'] ?: '',
					'invoice_address_line_2' => $r['invoice_address_line_2'] ?: '',
					'invoice_address_line_3' => $r['invoice_address_line_3'] ?: '',
					'invoice_posttown' => $r['invoice_posttown'] ?: '',
					'invoice_postcode' => $r['invoice_postcode'] ?: '',
					'bank_name' => $r['bank_name'] ?: '',
					'bank_sort_code' => $r['bank_sort_code'] ?: '',
					'bank_account_number' => $r['bank_account_number'] ?: '',
					'vat_reg_number' => $r['vat_reg_number'] ?: '',
					'logo_on_light_id' => $r['logo_on_light_id'] ?: '',
					'logo_on_dark_id' => $r['logo_on_dark_id'] ?: '',
					'signup_hash' => ''
				];
				break;

			case 'HG':
				$r = App::select('holding_group', $customer_id);
				if(!$r) return null;

				$d = [
					'contact_name' => '',
					'company_name' => $r['company_name'] ?: '',
					'email_address' => $r['email_address'] ?: '',
					'phone_number' => $r['phone_number'] ?: '',
					'mobile_number' => $r['mobile_number'] ?: '',
					'address_line_1' => $r['address_line_1'] ?: '',
					'address_line_2' => $r['address_line_2'] ?: '',
					'address_line_3' => $r['address_line_3'] ?: '',
					'posttown' => $r['posttown'] ?: '',
					'postcode' => $r['postcode'] ?: '',
					'invoice_address_line_1' => $r['invoice_address_line_1'] ?: '',
					'invoice_address_line_2' => $r['invoice_address_line_2'] ?: '',
					'invoice_address_line_3' => $r['invoice_address_line_3'] ?: '',
					'invoice_posttown' => $r['invoice_posttown'] ?: '',
					'invoice_postcode' => $r['invoice_postcode'] ?: '',
					'bank_name' => $r['bank_name'] ?: '',
					'bank_sort_code' => $r['bank_sort_code'] ?: '',
					'bank_account_number' => $r['bank_account_number'] ?: '',
					'vat_reg_number' => $r['vat_reg_number'] ?: '',
					'logo_on_light_id' => '',
					'logo_on_dark_id' => '',
					'signup_hash' => ''
				];
				break;

			case 'C':
				$r = App::select('client', $customer_id);
				if(!$r) return null;

				$d = [
					'contact_name' => '',
					'company_name' => $r['name'] ?: '',
					'email_address' => $r['email_address'] ?: '',
					'phone_number' => $r['phone_number'] ?: '',
					'mobile_number' => $r['mobile_number'] ?: '',
					'address_line_1' => $r['address_line_1'] ?: '',
					'address_line_2' => $r['address_line_2'] ?: '',
					'address_line_3' => $r['address_line_3'] ?: '',
					'posttown' => $r['posttown'] ?: '',
					'postcode' => $r['postcode'] ?: '',
					'invoice_address_line_1' => $r['invoice_address_line_1'] ?: '',
					'invoice_address_line_2' => $r['invoice_address_line_2'] ?: '',
					'invoice_address_line_3' => $r['invoice_address_line_3'] ?: '',
					'invoice_posttown' => $r['invoice_posttown'] ?: '',
					'invoice_postcode' => $r['invoice_postcode'] ?: '',
					'bank_name' => $r['bank_name'] ?: '',
					'bank_sort_code' => $r['bank_sort_code'] ?: '',
					'bank_account_number' => $r['bank_account_number'] ?: '',
					'vat_reg_number' => $r['vat_reg_number'] ?: '',
					'logo_on_light_id' => $r['image_id'] ?: '',
					'logo_on_dark_id' => $r['image_id'] ?: '',
					'signup_hash' => ''
				];
				break;

			case 'CU':
				$r = App::select('customer', $customer_id);
				if(!$r) return null;

				$customer = new Customer($customer_id);
				$hash = '';
				if($customer->validate()) $hash = $customer->get_signup_hash();

				$d = [
					'contact_name' => $r['contact_name'] ?: '',
					'company_name' => $r['company_name'] ?: '',
					'email_address' => $r['email_address'] ?: '',
					'phone_number' => $r['phone_number'] ?: '',
					'mobile_number' => $r['mobile_number'] ?: '',
					'address_line_1' => $r['address_line_1'] ?: '',
					'address_line_2' => $r['address_line_2'] ?: '',
					'address_line_3' => $r['address_line_3'] ?: '',
					'posttown' => $r['posttown'] ?: '',
					'postcode' => $r['postcode'] ?: '',
					'invoice_address_line_1' => $r['invoice_address_line_1'] ?: '',
					'invoice_address_line_2' => $r['invoice_address_line_2'] ?: '',
					'invoice_address_line_3' => $r['invoice_address_line_3'] ?: '',
					'invoice_posttown' => $r['invoice_posttown'] ?: '',
					'invoice_postcode' => $r['invoice_postcode'] ?: '',
					'bank_name' => '',
					'bank_sort_code' => '',
					'bank_account_number' => '',
					'vat_reg_number' => '',
					'logo_on_light_id' => '',
					'logo_on_dark_id' => '',
					'signup_hash' => $hash
				];
				break;

			default:
				return null;
		}

		$d['name'] = $d['contact_name'] && $d['company_name'] ? "$d[contact_name], $d[company_name]" : ($d['contact_name'] ?: $d['company_name']);
		$d['customer_type'] = $customer_type;
		$d['customer_id'] = $customer_id;

		// Use customer address if invoice address is not set
		if(trim("$d[invoice_address_line_1] $d[invoice_address_line_2] $d[invoice_address_line_3] $d[invoice_posttown] $d[invoice_postcode]") == '') {
			$d['invoice_address_line_1'] = $d['address_line_1'];
			$d['invoice_address_line_2'] = $d['address_line_2'];
			$d['invoice_address_line_3'] = $d['address_line_3'];
			$d['invoice_posttown'] = $d['posttown'];
			$d['invoice_postcode'] = $d['postcode'];
		}

		return $d;
	}

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('customer', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		$data = $this->record;
		$data['customer_name'] = $this->get_name();
		return $data;
	}

	public function get_name() {
		$name = [];
		if($this->record['contact_name']) $name[] = $this->record['contact_name'];
		if($this->record['company_name']) $name[] = $this->record['company_name'];
		return implode(', ', $name);
	}

	public function get_signup_hash() {
		if($this->record['archived']) return '';
		return md5($this->id.$this->record['owner_type'].$this->record['owner_id'].'SaltySalt');
	}

	public function create_token($type) {
		$ok = false;

		$expiry = strtotime('now');
		switch($type) {
			case 'login': $expiry = strtotime('+31 days'); break;
			case 'reset': $expiry = strtotime('+1 day'); break;
			default: return false;
		}

		do {
			$id = App::new_uid(true);
			$ok = !App::select('user_token', $id);
		} while (!$ok);

		App::insert('user_token', [
			'id' => $id,
			'customer_id' => $this->id,
			'type' => $type,
			'expiry' => date('Y-m-d H:i:s', $expiry)
		]);

		return $id;
	}

	public function revoke_tokens() {
		App::sql()->delete("DELETE FROM user_token WHERE user_id = '$this->id';");
	}

	public function reset_password() {
		// Generate reset token
		$token_id = $this->create_token('reset');

		// Send password reset email

		$base_reset_url = APP_URL.'/v3/auth/reset';
		$reset_url = $base_reset_url.'/'.urlencode($token_id);

		$brand_name = 'Eticom';
		if(BRANDING === 'elanet') $brand_name = 'Elanet';
		$customer_name = $this->record['contact_name'] ?: $this->record['company_name'] ?: '';

		$body = '
			<p>Hi '.$customer_name.',</p>
			<p>We have received a password reset request for your account. You can use the following link within the next day to reset your password:</p>
			<p><a href="'.$reset_url.'">'.$reset_url.'</a></p>
			<p>If you don\'t use this link within 24 hours, it will expire. To get a new password reset link, visit <a href="'.$base_reset_url.'">'.$base_reset_url.'</a></p>
			<p>If you didn\'t make the request, please ignore this email.</p>

			<p>
			Thanks,<br>
			The '.$brand_name.' Team
			</p>
		';

		$mailer = new Mailer();
		$from = $mailer->get_default_from($brand_name);
		return $mailer->email($from, $this->record['email_address'], "$brand_name password reset", $body);
	}

}

class Contract {

	/**
	 * unconfirmed - contract is disabled, has to be activated manually
	 * not_signed  - contract is disabled, waiting for customer to sign the PDF contract
	 * pending     - active contract, start date not yet reached
	 * active      - active contract, within term and/or rolling
	 * ending      - active contract, once end date reached, will switch to ended
	 * cancelled   - cancelled within the fixed term
	 * ended       - ended contract, either no longer rolling or end date reached on fixed term
	 */

	const STATUS_UNCONFIRMED = 'unconfirmed';
	const STATUS_NOT_SIGNED = 'not_signed';
	const STATUS_PENDING = 'pending';
	const STATUS_ACTIVE = 'active';
	const STATUS_ENDING = 'ending';
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_ENDED = 'ended';

	/**
	 * fixed    - fixed term contract going from start to end date
	 * variable - rolling contract with optional fixed term at start
	 */

	const TERM_FIXED = 'fixed';
	const TERM_VARIABLE = 'variable';

	/**
	 * Fixed term units
	 */

	const UNIT_WEEK = 'week';
	const UNIT_MONTH = 'month';
	const UNIT_YEAR = 'year';

	public $id = 0;
	public $record = null;
	public $invoices = [];

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('contract', $id);
		}

		if($this->validate()) $this->load_invoices();
	}

	public function validate() {
		return !!$this->record;
	}

	public function update($record) {
		if(count($record) === 0) return;
		App::update('contract', $this->id, $record);
		$this->record = array_merge($this->record, $record);
	}

	public function get_info($options = []) {
		$data = $this->record;

		$cd = Customer::resolve_details($this->record['customer_type'], $this->record['customer_id']);
		$data['customer_name'] = $cd ? $cd['name'] : '';

		$building_id = 0;
		$building_description = '';
		$area_description = '';
		if($data['area_id']) {
			$r = App::sql()->query_row(
				"SELECT
					a.description AS area_description,
					b.description AS building_description,
					b.id AS building_id
				FROM area AS a
				JOIN floor AS f ON f.id = a.floor_id
				JOIN building AS b ON b.id = f.building_id
				WHERE a.id = '$data[area_id]';
			", MySQL::QUERY_ASSOC);

			if($r) {
				$building_id = $r['building_id'];
				$building_description = $r['building_description'];
				$area_description = $r['area_description'];
			}
		}
		$data['building_id'] = $building_id;
		$data['building_description'] = $building_description;
		$data['area_description'] = $area_description;

		$data['is_active'] = $this->is_active();

		if(in_array('expand', $options)) {
			$data['invoices'] = isp_info($this->invoices ?: [], ['expand']);
		}

		return $data;
	}

	public function get_isp_area() {
		$area = new ISPArea($this->record['area_id']);
		return $area->validate() ? $area : null;
	}

	public function get_isp_package_name() {
		foreach($this->invoices as $invoice) {
			foreach($invoice->lines as $line) {
				if(($line['type'] === 'isp_package' || $line['type'] === 'isp_package_custom') && $line['isp_package_id']) return $line['description'];
			}
		}
		return '';
	}

	public function get_last_period_invoice() {
		$invoice = App::sql()->query_row("SELECT id FROM invoice WHERE contract_id = '$this->id' AND period_start_date IS NOT NULL AND status NOT IN ('not_approved', 'cancelled') ORDER BY bill_date DESC LIMIT 1;", MySQL::QUERY_ASSOC);
		return $invoice ? new Invoice($invoice['id'], $this) : null;
	}

	public function load_invoices() {
		$this->invoices = [];
		$list = App::sql()->query("SELECT id FROM contract_invoice WHERE contract_id = '$this->id';") ?: [];
		foreach($list as $item) {
			$this->invoices[] = new ContractInvoice($item->id, $this);
		}
	}

	/**
	 * Checks if contract is active.
	 */
	public function is_active() {
		return in_array($this->record['status'], [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_ENDING]);
	}

	public function is_within_term($date = null) {
		if($date) {
			$date = strtotime($date);
		} else {
			$date = strtotime('today');
		}

		$start = strtotime($this->record['start_date']);
		$end = strtotime($this->record['end_date']);

		return $date >= $start && $date <= $end;
	}

	public function is_in_future($date = null) {
		if($date) {
			$date = strtotime($date);
		} else {
			$date = strtotime('today');
		}

		$start = strtotime($this->record['start_date']);
		return $date < $start;
	}

	public function is_in_past($date = null) {
		if($date) {
			$date = strtotime($date);
		} else {
			$date = strtotime('today');
		}

		$end = strtotime($this->record['end_date']);
		return $date > $end;
	}

	public function process($force_first_invoice = false) {
		if(!$this->is_active()) return false;
		if($this->record['is_template']) return false;

		$contract_ended = false;

		if($this->is_in_future()) {
			// Contract hasn't started yet, update status to pending if needed
			if($this->record['status'] !== self::STATUS_PENDING) {
				$this->update(['status' => self::STATUS_PENDING]);
			}

			if($force_first_invoice) {
				// Even though contract is in the future, force out the first invoice as requested
				foreach($this->invoices as $invoice) {
					$invoice->process(true);
				}
			}
		} else {
			// Contract is active
			$update = [];
			$trigger_balance_change = false;
			if($this->record['status'] === self::STATUS_PENDING) {
				$update['status'] = self::STATUS_ACTIVE;
				$trigger_balance_change = true;
			}

			foreach($this->invoices as $invoice) {
				$invoice->process();
			}

			if($this->is_in_past()) {
				if($this->record['status'] === self::STATUS_ENDING || $this->record['contract_term'] === self::TERM_FIXED) {
					// Mark contract as ended once it's out of date and everything has been processed
					$update['status'] = self::STATUS_ENDED;
					$contract_ended = true;
				}
			}

			$this->update($update);

			if($trigger_balance_change) {
				// This is for contract that start out as not signed. When the customer signs the contract, the contract
				// will become pending, but the first invoice is forced out so it can be paid there and then, even if
				// the contract itself hasn't started yet. In this case, we need to trigger the balance change when
				// the contract finally activates. This makes sure the customer's services will be activated.
				$owner_type = $this->record['owner_type'];
				$owner_id = $this->record['owner_id'];
				$customer_type = $this->record['customer_type'];
				$customer_id = $this->record['customer_id'];

				$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);
				$pa->process_after_balance_changed();
			}

			if($contract_ended) $this->event_contract_ended();
		}

		return true;
	}

	public function event_contract_ended() {
		$this->switch_off_devices();
	}

	public function switch_off_devices($print = false) {
		if($this->record['owner_type'] !== 'SI') return;

		$area = $this->get_isp_area();
		if($area) {
			// Check if there are other, active contracts
			$switch_off = true;
			$info = $area->get_info(['expand']);
			if(isset($info['contracts'])) {
				foreach($info['contracts'] as $c) {
					if($c['id'] !== $this->id && $c['is_active'] && $c['owner_type'] == $this->record['owner_type'] && $c['owner_id'] == $this->record['owner_id']) $switch_off = false;
				}
			}

			if($switch_off) {
				$onu = $area->get_onu();
				if($onu) {
					$active = $onu->get_active_package();
					if($active) {
						$onu->set_package(null);
						if($print) echo "Contract $this->id had package $active->id, now it's off.\n";
						error_log("CONTRACT $this->id SWITCHED OFF");
					} else {
						if($print) echo "Contract $this->id is already switched off.\n";
					}
				}
			}
		}
	}

	public function send_activation_email() {
		if($this->validate()) {
			$owner_type = $this->record['owner_type'];
			$owner_id = $this->record['owner_id'];
			$customer_type = $this->record['customer_type'];
			$customer_id = $this->record['customer_id'];

			if(!$this->record['is_template'] && !$this->record['activation_email_sent']) {
				$customer = Customer::resolve_details($customer_type, $customer_id);
				if($customer && $customer['email_address']) {
					$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);

					Mailer::send_from_template($owner_type, $owner_id, 'isp_activate', $customer['email_address'], $customer['name'] ?: '', [
						'customer' => $customer,
						'payment_account' => $pa,
						'contract' => $this,
						'invoice' => $this->get_last_period_invoice()
					]);
				}

				App::update('contract', $this->id, [ 'activation_email_sent' => 1 ]);
				$this->record['activation_email_sent'] = 1;
			}
		}
	}

	public function send_not_signed_email() {
		if($this->validate()) {
			$owner_type = $this->record['owner_type'];
			$owner_id = $this->record['owner_id'];
			$customer_type = $this->record['customer_type'];
			$customer_id = $this->record['customer_id'];

			if(!$this->record['is_template'] && !$this->record['not_signed_email_sent']) {
				$customer = Customer::resolve_details($customer_type, $customer_id);
				if($customer && $customer['email_address']) {
					$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);

					Mailer::send_from_template($owner_type, $owner_id, 'isp_not_signed', $customer['email_address'], $customer['name'] ?: '', [
						'customer' => $customer,
						'payment_account' => $pa,
						'contract' => $this
					]);
				}

				App::update('contract', $this->id, [ 'not_signed_email_sent' => 1 ]);
				$this->record['not_signed_email_sent'] = 1;
			}
		}
	}

}

class ContractInvoice {

	const FREQUENCY_MONTHLY = 'monthly';
	const FREQUENCY_MONTHLY_ARREARS = 'monthly-';
	const FREQUENCY_MONTHLY_ADVANCE = 'monthly+';
	const FREQUENCY_ANNUAL = 'annual';

	public $id = 0;
	public $record = null;
	public $contract = null;
	public $lines = [];

	public function __construct($id, $contract = null) {
		$this->id = App::escape($id);
		$this->record = App::select('contract_invoice', $id);

		$this->contract = $contract;
		if($this->contract === null) $this->contract = new Contract($this->record['contract_id']);

		if($this->validate()) $this->load_lines();
	}

	public function validate() {
		return !!$this->record;
	}

	public function update($record) {
		if(count($record) === 0) return;
		App::update('contract_invoice', $this->id, $record);
		$this->record = array_merge($this->record, $record);
	}

	public function get_info($options = []) {
		$data = $this->record;

		if(in_array('expand', $options)) {
			$data['lines'] = $this->lines;
		}

		return $data;
	}

	public function get_owner_info() {
		$info = Customer::resolve_details($this->contract->record['owner_type'], $this->contract->record['owner_id']) ?: Customer::resolve_details();

		$invoice_entity_id = $this->record['invoice_entity_id'];
		if($invoice_entity_id) {
			// Overlay invoicing entity data
			$record = App::select('invoice_entity', $invoice_entity_id);

			if($record) {
				$info['name'] = $record['name'] ?: '';
				$info['contact_name'] = '';
				$info['company_name'] = $record['name'] ?: '';
				$info['bank_name'] = $record['bank_name'] ?: '';
				$info['bank_sort_code'] = $record['bank_sort_code'] ?: '';
				$info['bank_account_number'] = $record['bank_account_number'] ?: '';
				$info['vat_reg_number'] = $record['vat_reg_number'] ?: '';

				if($record['address_line_1'] || $record['address_line_2'] || $record['address_line_3'] || $record['posttown'] || $record['postcode']) {
					$info['address_line_1'] = $record['address_line_1'] ?: '';
					$info['address_line_2'] = $record['address_line_2'] ?: '';
					$info['address_line_3'] = $record['address_line_3'] ?: '';
					$info['posttown'] = $record['posttown'] ?: '';
					$info['postcode'] = $record['postcode'] ?: '';
					$info['invoice_address_line_1'] = $record['address_line_1'] ?: '';
					$info['invoice_address_line_2'] = $record['address_line_2'] ?: '';
					$info['invoice_address_line_3'] = $record['address_line_3'] ?: '';
					$info['invoice_posttown'] = $record['posttown'] ?: '';
					$info['invoice_postcode'] = $record['postcode'] ?: '';
				}

				if($record['image_id']) {
					$info['logo_on_light_id'] = $record['image_id'];
					$info['logo_on_dark_id'] = $record['image_id'];
				}
			}
		}
		return $info;
	}

	public function get_customer_info() {
		return Customer::resolve_details($this->contract->record['customer_type'], $this->contract->record['customer_id']) ?: Customer::resolve_details();
	}

	public function load_lines() {
		$this->lines = App::sql()->query("SELECT * FROM contract_invoice_line WHERE contract_invoice_id = '$this->id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($this->lines as &$line) {
			if($line['type'] === 'isp_package' && $line['isp_package_id']) {
				// Resolve description and price of ISP package
				$p = App::sql('isp')->query_row("SELECT description, monthly_price FROM olt_service WHERE id = '$line[isp_package_id]';", MySQL::QUERY_ASSOC);
				if(!$p) throw new Exception("Cannot read package details ($line[isp_package_id])");

				$desc = $p['description'];
				$price = $p['monthly_price'];
				if($this->record['frequency'] === self::FREQUENCY_ANNUAL) $price *= 12;

				$line['description'] = $desc;
				$line['unit_price'] = $price;
			}
			if($line['type'] === 'isp_package_custom' && $line['isp_package_id']) {
				// Resolve description of ISP package
				$p = App::sql('isp')->query_row("SELECT description FROM olt_service WHERE id = '$line[isp_package_id]';", MySQL::QUERY_ASSOC);
				if(!$p) throw new Exception("Cannot read package details ($line[isp_package_id])");

				$desc = $p['description'];

				$line['description'] = $desc;
			}
		}
		unset($line);
	}

	public function raise_custom_invoice($lines = []) {
		$owner_info = $this->get_owner_info();
		$customer_info = $this->get_customer_info();

		$today = date('Y-m-d');
		$today_time = strtotime($today);

		// Generate invoice
		if(!count($lines)) return false;

		$vat_rate = $this->record['vat_rate'];
		$subtotal = 0;
		$vat_due = 0;
		$bill_total = 0;

		foreach($lines as $line) {
			$subtotal += $line['line_total'];
		}
		$vat_due = round($subtotal * ($vat_rate / 100), 2);
		$bill_total = $subtotal + $vat_due;

		$invoice_id = App::insert('invoice', [
			'owner_type' => $this->contract->record['owner_type'],
			'owner_id' => $this->contract->record['owner_id'],
			'customer_type' => $this->contract->record['customer_type'],
			'customer_id' => $this->contract->record['customer_id'],
			'invoice_no' => Invoice::generate_invoice_no($this->contract->record['owner_type'], $this->contract->record['owner_id']),
			'contract_id' => $this->contract->id,
			'contract_invoice_id' => $this->id,
			'invoice_entity_id' => $this->record['invoice_entity_id'],
			'description' => $this->record['description'],
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
			'customer_ref' => $this->contract->record['reference_no'],
			'bill_date' => $today,
			'due_date' => $today,
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

		$invoice = new Invoice($invoice_id, $this->contract);
		$dd = false;
		if($invoice->validate()) $invoice->approve_invoice($dd);

		return true;
	}

	public function create_dd_fail_charges() {
		$owner_info = $this->get_owner_info();
		$customer_info = $this->get_customer_info();

		$today = date('Y-m-d');
		$today_time = strtotime($today);

		// Generate invoice
		$lines = [];

		// Add dd fail charges
		foreach($this->lines as $line) {
			if($line['charge_type'] === 'dd_fail') $lines[] = [
				'icon' => $line['icon'],
				'description' => $line['description'],
				'unit_price' => $line['unit_price'],
				'quantity' => $line['quantity'],
				'line_total' => round($line['unit_price'] * $line['quantity'], 2)
			];
		}
		

		if(!count($lines)) return false;

		$vat_rate = $this->record['vat_rate'];
		$subtotal = 0;
		$vat_due = 0;
		$bill_total = 0;

		foreach($lines as $line) {
			$subtotal += $line['line_total'];
		}
		$vat_due = round($subtotal * ($vat_rate / 100), 2);
		$bill_total = $subtotal + $vat_due;

		$invoice_id = App::insert('invoice', [
			'owner_type' => $this->contract->record['owner_type'],
			'owner_id' => $this->contract->record['owner_id'],
			'customer_type' => $this->contract->record['customer_type'],
			'customer_id' => $this->contract->record['customer_id'],
			'invoice_no' => Invoice::generate_invoice_no($this->contract->record['owner_type'], $this->contract->record['owner_id']),
			'contract_id' => $this->contract->id,
			'contract_invoice_id' => $this->id,
			'invoice_entity_id' => $this->record['invoice_entity_id'],
			'description' => $this->record['description'],
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
			'customer_ref' => $this->contract->record['reference_no'],
			'bill_date' => $today,
			'due_date' => $today,
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

		$invoice = new Invoice($invoice_id, $this->contract);
		$dd = true;
		if($invoice->validate()) $invoice->approve_invoice($dd);

		return true;
	}

	private function is_auto_quantity_line($line) {
		return substr($line['type'], 0, 8) === "utility_" || $line['type'] === 'isp_routers';
	}

	private function resolve_auto_quantity_line($line, $start_date, $end_date) {
		$quantity = 0;

		switch($line['type']) {
			case 'isp_routers':
				if($this->contract && $start_date && $end_date) {
					$owner_type = $this->contract->record['owner_type'];
					$owner_id = $this->contract->record['owner_id'];
					$customer_type = $this->contract->record['customer_type'];
					$customer_id = $this->contract->record['customer_id'];

					$building_list = [];

					switch($customer_type) {
						case 'SI':
							$building_list = App::sql()->query(
								"SELECT
									b.id
								FROM building AS b
								JOIN client AS c ON c.id = b.client_id
								WHERE c.system_integrator_id = '$customer_id';
							", MySQL::QUERY_ASSOC) ?: [];
							break;

						case 'C':
							$building_list = App::sql()->query(
								"SELECT
									b.id
								FROM building AS b
								WHERE b.client_id = '$customer_id';
							", MySQL::QUERY_ASSOC) ?: [];
							break;
					}

					$building_ids = [];
					foreach($building_list as $b) {
						$building_ids[] = $b['id'];
					}

					if(count($building_ids) > 0) {
						// Count ONU uptime records in ALL buildings
						$building_ids = "'".implode("','", $building_ids)."'";

						$r = App::sql('isp')->query_row(
							"SELECT
								COUNT(DISTINCT onu.id) AS cnt
							FROM onu_uptime AS up
							JOIN onu ON onu.id = up.onu_table_id
							JOIN olt ON olt.id = onu.olt_id
							JOIN hes ON hes.id = olt.hes_id
							WHERE hes.building_id IN ($building_ids) AND up.datetime BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59';
						", MySQL::QUERY_ASSOC);

						if($r) $quantity = $r['cnt'] ?: 0;
					}
				}
				break;

			case 'utility_s':
				// Standing charge
				$quantity = round((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24)) + 1;
				break;

			case 'utility_e':
			case 'utility_g':
			case 'utility_w':
			case 'utility_h':
				$meter_type = strtoupper(explode('_', $line['type'])[1]);
				if($this->contract) {
					$area_id = $this->contract->record['area_id'];
					if($area_id) {
						$meter_ids = array_map(
							function($meter) {
								return $meter['id'];
							},
							App::sql()->query("SELECT id FROM meter WHERE meter_type = '$meter_type' AND COALESCE(virtual_area_id, area_id) = '$area_id';", MySQL::QUERY_ASSOC) ?: []
						);
						if(count($meter_ids)) {
							// We have the meters, check the consumption

							$meter_count = count($meter_ids);
							$meter_ids = implode(',', $meter_ids);

							if($start_date === $this->start_of_month($start_date) && $end_date === $this->end_of_month($start_date)) {

								//
								// We have a full period, use meter_period table to get final readings
								//

								$year = date('Y', strtotime($start_date));
								$month = (int) date('m', strtotime($start_date));
								$usage = App::sql()->query_row(
									"SELECT
										SUM(COALESCE(usage_1, 0) + COALESCE(usage_2, 0) + COALESCE(usage_3, 0)) AS total,
										COUNT(DISTINCT meter_id) AS cnt
									FROM meter_period
									WHERE meter_id IN ($meter_ids) AND year = '$year' AND month = '$month';
								", MySQL::QUERY_ASSOC);

								// Postpone bill generation if we have no readings yet
								if(!$usage || $usage['cnt'] != $meter_count) return null;

								$quantity = $usage['total'] ?: 0;
							} else {

								//
								// It is a partial month, get total of daily readings
								//

								$verify = App::sql()->query_row(
									"SELECT
										COUNT(DISTINCT meter_id) AS cnt
									FROM automated_meter_reading_history
									WHERE meter_id IN ($meter_ids) AND reading_day >= '$end_date';
								", MySQL::QUERY_ASSOC);

								// Postpone bill generation if we have no up to date readings yet
								if(!$verify || $verify['cnt'] != $meter_count) return null;

								$usage = App::sql()->query_row(
									"SELECT
										SUM(total_imported_total) AS total
									FROM automated_meter_reading_history
									WHERE meter_id IN ($meter_ids) AND reading_day BETWEEN '$start_date' AND '$end_date';
								", MySQL::QUERY_ASSOC);

								// This should never happen, but check if data is returned, otherwise postpone.
								if(!$usage) return null;

								$quantity = $usage['total'] ?: 0;
							}
						}
					}
				}
				break;
		}

		return [
			'icon' => $line['icon'],
			'description' => $line['description'],
			'unit_price' => $line['unit_price'],
			'quantity' => $quantity,
			'line_total' => round($line['unit_price'] * $quantity, 2)
		];
	}

	public function process($force_first_invoice = false) {
		$skip_past = $this->contract->record['skip_past_invoices'];
		if($this->record['last_bill_date']) $skip_past = false;

		// return [
		// 	'is_first' => $is_first,
		// 	'is_last' => $is_last,
		// 	'partial_days_start' => $partial_days_start,
		// 	'period_days_start' => $period_days_start,
		// 	'partial_days_end' => $partial_days_end,
		// 	'period_days_end' => $period_days_end,
		// 	'full_period' => $full_period,
		// 	'bill_date' => $bill_date,
		// 	'due_date' => $due_date,
		// 	'period_start_date' => $period_start_date,
		// 	'period_end_date' => $period_end_date
		// ];

		$owner_info = $this->get_owner_info();
		$customer_info = $this->get_customer_info();

		$today = date('Y-m-d');

		// If we need to force out the first invoice, pretend that today is the contract's start date
		if($force_first_invoice) {
			if(!$this->contract) return;
			$today = $this->contract->record['start_date'];
			if(!$today) return;
		}

		$today_time = strtotime($today);
		$info = $this->get_next_bill_dates();

		while($info && strtotime($info['bill_date']) <= $today_time) {
			// Make sure we're not processing the same period again. It could happen if the contract billing date is updated mid-term.
			if(!$this->record['last_period_end_date'] || (strtotime($this->record['last_period_end_date']) < strtotime($info['period_start_date']))) {
				if(!$skip_past || strtotime($info['bill_date']) >= $today_time) {
					// Generate invoice
					$lines = [];

					if($info['is_first']) {
						// Add one-time charges
						foreach($this->lines as $line) {
							if($line['charge_type'] === 'once' && !$this->is_auto_quantity_line($line)) $lines[] = [
								'icon' => $line['icon'],
								'description' => $line['description'],
								'unit_price' => $line['unit_price'],
								'quantity' => $line['quantity'],
								'line_total' => round($line['unit_price'] * $line['quantity'], 2)
							];
						}
					}

					if($info['partial_days_start'] > 0) {
						// Add partial days at the start of the period
						$total_days = $info['period_days_start'];
						$days = $info['partial_days_start'];
						$month_partial = $info['partial_month_start'];
						$month_partial_issue = $info['partial_month_start_issue'];
						foreach($this->lines as $line) {
							if($line['charge_type'] === 'always' && !$this->is_auto_quantity_line($line)) {
								$unit_price = round(($line['unit_price'] / $total_days) * $days, 4);
								$lines[] = [
									'icon' => $line['icon'],
									'description' => $line['description'] . ' (' . $days . ' ' . ($days === 1 ? 'day' : 'days') . ($month_partial ? ' ' . $month_partial : '') . ')',
									'unit_price' => $unit_price,
									'quantity' => $line['quantity'],
									'line_total' => round($unit_price * $line['quantity'], 2)
								];
							}
						}
					}

					if($info['full_period']) {
						// Add full period lines
						$month_partial_issue = $info['partial_month_start_issue'];
						foreach($this->lines as $line) {
							if($line['charge_type'] === 'always' && !$this->is_auto_quantity_line($line)) $lines[] = [
								'icon' => $line['icon'],
								'description' => $line['description'].' ('.$month_partial_issue.') ',
								'unit_price' => $line['unit_price'],
								'quantity' => $line['quantity'],
								'line_total' => round($line['unit_price'] * $line['quantity'], 2)
							];
						}
					}

					if($info['partial_days_end'] > 0) {
						// Add partial days at the end of the period
						$total_days = $info['period_days_end'];
						$days = $info['partial_days_end'];
						$month_partial = $info['partial_month_start'];
						$month_partial_issue = $info['partial_month_start_issue'];
						foreach($this->lines as $line) {
							if($line['charge_type'] === 'always' && !$this->is_auto_quantity_line($line)) {
								$unit_price = round(($line['unit_price'] / $total_days) * $days, 4);
								$lines[] = [
									'icon' => $line['icon'],
									'description' => $line['description'] . ' (' . $days . ' ' . ($days === 1 ? 'day' : 'days') . ($month_partial ? ' ' . $month_partial : '') . ')',
									'unit_price' => $unit_price,
									'quantity' => $line['quantity'],
									'line_total' => round($unit_price * $line['quantity'], 2)
								];
							}
						}
					}

					// Add auto-quantity lines
					foreach($this->lines as $line) {
						if($this->is_auto_quantity_line($line)) {
							$processed_line = $this->resolve_auto_quantity_line($line, $info['period_start_date'], $info['period_end_date']);

							// If line is not resolved, postpone billing
							if(!$processed_line) {
								error_log('Invoice postponed, no quantity data.');
								return;
							}

							$lines[] = $processed_line;
						}
					}

					if(count($lines)) {
						$vat_rate = $this->record['vat_rate'];
						$subtotal = 0;
						$vat_due = 0;
						$bill_total = 0;

						foreach($lines as $line) {
							$subtotal += $line['line_total'];
						}
						$vat_due = round($subtotal * ($vat_rate / 100), 2);
						$bill_total = $subtotal + $vat_due;

						$invoice_id = App::insert('invoice', [
							'owner_type' => $this->contract->record['owner_type'],
							'owner_id' => $this->contract->record['owner_id'],
							'customer_type' => $this->contract->record['customer_type'],
							'customer_id' => $this->contract->record['customer_id'],
							'invoice_no' => Invoice::generate_invoice_no($this->contract->record['owner_type'], $this->contract->record['owner_id']),
							'contract_id' => $this->contract->id,
							'contract_invoice_id' => $this->id,
							'invoice_entity_id' => $this->record['invoice_entity_id'],
							'description' => $this->record['description'],
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
							'customer_ref' => $this->contract->record['reference_no'],
							'bill_date' => $info['bill_date'],
							'due_date' => $info['due_date'],
							'period_start_date' => $info['period_start_date'],
							'period_end_date' => $info['period_end_date'],
							'vat_rate' => $vat_rate,
							'subtotal' => $subtotal,
							'vat_due' => $vat_due,
							'bill_total' => $bill_total,
							'bank_name' => $owner_info['bank_name'],
							'bank_sort_code' => $owner_info['bank_sort_code'],
							'bank_account_number' => $owner_info['bank_account_number'],
							'vat_reg_number' => $owner_info['vat_reg_number'],
							'is_first' => $info['is_first'] ? 1 : 0
						]);

						foreach($lines as $line) {
							$line['id'] = 'new';
							$line['invoice_id'] = $invoice_id;
							App::insert('invoice_line', $line);
						}

						if(!$this->record['manual_authorisation']) {
							$invoice = new Invoice($invoice_id, $this->contract);
							$dd = true;
							if($info['is_first'] && $this->record['initial_card_payment']) $dd = false;
							if($invoice->validate()) $invoice->approve_invoice($dd);
						}
					}
				}
			}

			// Update for next iteration
			$this->update([
				'last_bill_date' => $info['bill_date'],
				'last_due_date' => $info['due_date'],
				'last_period_start_date' => $info['period_start_date'],
				'last_period_end_date' => $info['period_end_date']
			]);
			$info = $this->get_next_bill_dates();
		}
	}

	/**
	 * Gets the next day with day of month $day after the specified $date.
	 * If no date is set, defaults to today.
	 * $date is string with format Y-m-d.
	 */
	private function next_day_of_month($day, $date = null) {
		$day = $day > 0 ? ($day < 10 ? "0$day" : "$day") : '01';
		if(!$date) $date = date('Y-m-d');

		// Change day to desired date of month
		$result = date("Y-m-$day", strtotime($date));

		// Make sure the result is in the same month, otherwise make it the end of month
		// This could happen if we requested e.g. 2018-02-31, which resolves to 2018-03-03
		$eom = $this->end_of_month($date);
		if(strtotime($result) > strtotime($eom)) $result = $eom;

		// If the result is on or before the date, we need the next one
		if(strtotime($result) <= strtotime($date)) {
			// Add one day to the end of month to get the first of next month
			$result = date('Y-m-d', strtotime('+1 day', strtotime($eom)));

			// Update end of month to end of next month
			$eom = $this->end_of_month($result);

			// Change day to desired date of month
			$result = date("Y-m-$day", strtotime($result));

			// Make sure the result is in the same month, otherwise make it the end of month
			// This could happen if we requested e.g. 2018-02-31, which resolves to 2018-03-03
			if(strtotime($result) > strtotime($eom)) $result = $eom;
		}

		return $result;
	}

	/**
	 * Gets the previous day with day of month $day before the specified $date.
	 * If no date is set, defaults to today.
	 * $date is string with format Y-m-d.
	 */
	private function previous_day_of_month($day, $date = null) {
		$day = $day > 0 ? ($day < 10 ? "0$day" : "$day") : '01';
		if(!$date) $date = date('Y-m-d');

		// Change day to desired date of month
		$result = date("Y-m-$day", strtotime($date));

		// Make sure the result is in the same month, otherwise make it the end of month
		// This could happen if we requested e.g. 2018-02-31, which resolves to 2018-03-03
		$eom = $this->end_of_month($date);
		if(strtotime($result) > strtotime($eom)) $result = $eom;

		// If the result is on or after the date, we need the previous one
		if(strtotime($result) >= strtotime($date)) {
			// Get the start/end of the previous month
			$result = $this->start_of_month($date);
			$result = date('Y-m-d', strtotime('-1 day', strtotime($result)));
			$som = $this->start_of_month($result);
			$eon = $this->end_of_month($result);

			// Change day to desired date of month
			$result = date("Y-m-$day", strtotime($som));

			// Make sure the result is in the same month, otherwise make it the end of month
			// This could happen if we requested e.g. 2018-02-31, which resolves to 2018-03-03
			if(strtotime($result) > strtotime($eom)) $result = $eom;
		}

		return $result;
	}

	/**
	 * Gets the last day of the month for a given $date.
	 * If $date is not set, defaults to today.
	 */
	private function end_of_month($date = null) {
		if(!$date) $date = date('Y-m-d');
		$date = date('Y-m-01', strtotime($date));
		$date = strtotime('+1 month', strtotime($date));
		$date = date('Y-m-d', strtotime('-1 day', $date));
		return $date;
	}

	/**
	 * Gets the first day of the month for a given $date.
	 * If $date is not set, defaults to today.
	 */
	private function start_of_month($date = null) {
		if(!$date) $date = date('Y-m-d');
		$date = date('Y-m-01', strtotime($date));
		return $date;
	}

	private function add_days($n, $date = null) {
		if(!$date) $date = date('Y-m-d');
		$date = date('Y-m-d', strtotime("+$n day", strtotime($date)));
		return $date;
	}

	private function sub_days($n, $date = null) {
		if(!$date) $date = date('Y-m-d');
		$date = date('Y-m-d', strtotime("-$n day", strtotime($date)));
		return $date;
	}

	private function days_between($date1, $date2) {
		$date1 = strtotime($date1);
		$date2 = strtotime($date2);
		return round(abs($date2 - $date1) / (60 * 60 * 24));
	}

	/**
	 * Gets the next billing dates.
	 */
	public function get_next_bill_dates() {
		$last_bill_date = $this->record['last_bill_date'];
		$last_due_date = $this->record['last_due_date'];
		$last_period_start_date = $this->record['last_period_start_date'];
		$last_period_end_date = $this->record['last_period_end_date'];

		$cutoff_day = $this->record['cutoff_day'];
		$issue_day = $this->record['issue_day'];
		$payment_day = $this->record['payment_day'];

		$is_first = !$last_bill_date;
		$is_last = false;
		$partial_days_start = 0;
		$period_days_start = 0;
		$partial_days_end = 0;
		$period_days_end = 0;
		$full_period = false;

		$bill_date = null;
		$due_date = null;
		$period_start_date = null;
		$period_end_date = null;

		$contract_start_date = $this->contract->record['start_date'];
		$contract_end_date = $this->contract->record['end_date'];
		if($this->contract->record['status'] !== Contract::STATUS_ENDING && $this->contract->record['contract_term'] !== Contract::TERM_FIXED) $contract_end_date = null;
		if(!$this->contract->is_active()) return null;

		// First, calculate the billing date.

		if($is_first) {
			// Calculate first billing dates
			switch($this->record['frequency']) {
				case self::FREQUENCY_MONTHLY:
					$issue_day = intval(substr($contract_start_date, -2));
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$bill_date = $contract_start_date;
					$due_date = $this->add_days($payment_day, $bill_date);
					$period_start_date = $contract_start_date;
					$period_end_date = $this->next_day_of_month($issue_day, $period_start_date);
					$period_end_date = $this->sub_days(1, $period_end_date);
					$full_period = true;

					if($contract_end_date) {
						if(strtotime($contract_end_date) < strtotime($period_start_date)) return null;

						if(strtotime($contract_end_date) <= strtotime($period_end_date)) {
							$is_last = true;
							if($contract_end_date !== $period_end_date) {
								$full_period = false;
								$period_days_start = $this->days_between($period_start_date, $period_end_date) + 1;
								$period_end_date = $contract_end_date;
								$partial_days_start = $this->days_between($period_start_date, $period_end_date) + 1;
							}
						}
					}
					break;

				case self::FREQUENCY_MONTHLY_ARREARS:
					$period_start_date = $contract_start_date;
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$period_end_date = $this->end_of_month($period_start_date);
					$bill_date = $this->next_day_of_month($issue_day, $period_end_date);
					$due_date = $this->next_day_of_month($payment_day, $bill_date);

					$som = $this->start_of_month($period_start_date);
					if($som === $period_start_date) {
						$full_period = true;
					} else {
						$full_period = false;
						$partial_days_start = $this->days_between($period_start_date, $period_end_date) + 1;
						$period_days_start = $this->days_between($som, $period_end_date) + 1;
					}

					if($contract_end_date) {
						if(strtotime($contract_end_date) < strtotime($period_start_date)) return null;

						if(strtotime($contract_end_date) <= strtotime($period_end_date)) {
							$is_last = true;
							if($contract_end_date !== $period_end_date) {
								$full_period = false;
								$period_end_date = $contract_end_date;
								$partial_days_start = $this->days_between($period_start_date, $period_end_date) + 1;
								$period_days_start = $this->days_between($som, $this->end_of_month($som)) + 1;
							}

							$diff = $this->days_between($bill_date, $due_date);
							$bill_date = $this->add_days(1, $contract_end_date);
							$due_date = $this->add_days($diff, $bill_date);
						}
					}
					break;

				case self::FREQUENCY_MONTHLY_ADVANCE:
					$som = $this->start_of_month($contract_start_date);
					$eom = $this->end_of_month($contract_start_date);

					$period_start_date = $contract_start_date;
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$bill_date = $this->next_day_of_month($issue_day, $som);
					$due_date = $this->next_day_of_month($payment_day, $bill_date);
					$cutoff_date = $this->previous_day_of_month($cutoff_day, $bill_date);
					$period_end_date = $eom;

					$partial_days_start = $this->days_between($contract_start_date, $eom) + 1;
					$period_days_start = $this->days_between($som, $eom) + 1;
					$full_period = false;

					if(strtotime($contract_start_date) > strtotime($cutoff_date)) {
						// No time to set up, add next period on top
						$period_end_date = $this->add_days(1, $period_end_date);
						$period_end_date = $this->end_of_month($period_end_date);
						$full_period = true;
					}

					if($contract_end_date) {
						if(strtotime($contract_end_date) < strtotime($period_start_date)) return null;

						if(strtotime($contract_end_date) <= strtotime($period_end_date)) {
							$is_last = true;
							if($contract_end_date !== $period_end_date) {
								$full_period = false;
								$period_end_date = $contract_end_date;
								$partial_days_start = $this->days_between($period_start_date, $period_end_date) + 1;
								$period_days_start = $this->days_between($som, $this->end_of_month($period_end_date)) + 1;
							}
						}
					}

					// Bill them right away
					$bill_date = $contract_start_date;
					$due_date = $contract_start_date;
					break;

				case self::FREQUENCY_ANNUAL:
					$issue_day = intval(substr($contract_start_date, -2));
					$bill_date = $contract_start_date;
					$due_date = $this->add_days($payment_day, $bill_date);
					$period_start_date = $contract_start_date;
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$period_end_date = date('Y-m-01', strtotime($contract_start_date));
					$period_end_date = date('Y-m-d', strtotime('+1 year', strtotime($period_end_date)));
					$period_end_date = $this->sub_days(1, $period_end_date);
					$period_end_date = $this->next_day_of_month($issue_day, $period_end_date);
					$period_end_date = $this->sub_days(1, $period_end_date);
					$full_period = true;

					if($contract_end_date) {
						if(strtotime($contract_end_date) < strtotime($period_start_date)) return null;

						if(strtotime($contract_end_date) <= strtotime($period_end_date)) {
							$is_last = true;
							if($contract_end_date !== $period_end_date) {
								$full_period = false;
								$period_days_start = $this->days_between($period_start_date, $period_end_date) + 1;
								$period_end_date = $contract_end_date;
								$partial_days_start = $this->days_between($period_start_date, $period_end_date) + 1;
							}
						}
					}
					break;
			}
		} else {
			// Calculate next billing dates
			switch($this->record['frequency']) {
				case self::FREQUENCY_MONTHLY:
					$issue_day = intval(substr($contract_start_date, -2));
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$bill_date = $this->next_day_of_month($issue_day, $last_bill_date);
					$due_date = $this->add_days($payment_day, $bill_date);
					$period_start_date = $bill_date;
					$period_end_date = $this->next_day_of_month($issue_day, $period_start_date);
					$period_end_date = $this->sub_days(1, $period_end_date);
					$full_period = true;
					break;

				case self::FREQUENCY_MONTHLY_ARREARS:
					$period_start_date = $this->add_days(1, $last_period_end_date);
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$period_end_date = $this->end_of_month($period_start_date);
					$bill_date = $this->next_day_of_month($issue_day, $period_end_date);
					$due_date = $this->next_day_of_month($payment_day, $bill_date);
					$full_period = true;
					break;

				case self::FREQUENCY_MONTHLY_ADVANCE:
					$period_start_date = $this->add_days(1, $last_period_end_date);
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$period_end_date = $this->end_of_month($period_start_date);
					$bill_date = $this->previous_day_of_month($issue_day, $period_start_date);
					$due_date = $this->next_day_of_month($payment_day, $bill_date);
					$full_period = true;
					break;

				case self::FREQUENCY_ANNUAL:
					$issue_day = intval(substr($contract_start_date, -2));
					//Convert the $contract_start_date string to a DateTime object
					$start_date_object = DateTime::createFromFormat('Y-m-d', $contract_start_date);
					//Get the month as a string (e.g., "January", "February", etc.)
					$issue_month = $start_date_object->format('F');
					//Full period month
					$full_period_month = date('F', strtotime('+1 month', strtotime($contract_start_date)));
					$period_start_date = $this->add_days(1, $last_period_end_date);
					$bill_date = $period_start_date;
					$due_date = $this->add_days($payment_day, $bill_date);

					$period_end_date = date('Y-m-01', strtotime($period_start_date));
					$period_end_date = date('Y-m-d', strtotime('+1 year', strtotime($period_end_date)));
					$period_end_date = $this->sub_days(1, $period_end_date);
					$period_end_date = $this->next_day_of_month($issue_day, $period_end_date);
					$period_end_date = $this->sub_days(1, $period_end_date);

					$full_period = true;
					break;
			}

			if($contract_end_date) {
				if(strtotime($contract_end_date) < strtotime($period_start_date)) return null;

				if(strtotime($contract_end_date) <= strtotime($period_end_date)) {
					$is_last = true;
					if($contract_end_date !== $period_end_date) {
						$full_period = false;
						$period_days_end = $this->days_between($period_start_date, $period_end_date) + 1;
						$period_end_date = $contract_end_date;
						$partial_days_end = $this->days_between($period_start_date, $period_end_date) + 1;
					}
				}

				$day_after_end_date = $this->add_days(1, $contract_end_date);
				if(strtotime($bill_date) > strtotime($day_after_end_date)) {
					$diff = $this->days_between($bill_date, $due_date);
					$bill_date = $day_after_end_date;
					$due_date = $this->add_days($diff, $bill_date);
				}
			}
		}

		return [
			'is_first' => $is_first,
			'is_last' => $is_last,
			'partial_days_start' => $partial_days_start,
			'period_days_start' => $period_days_start,
			'partial_days_end' => $partial_days_end,
			'period_days_end' => $period_days_end,
			'full_period' => $full_period,
			'bill_date' => $bill_date,
			'due_date' => $due_date,
			'period_start_date' => $period_start_date,
			'period_end_date' => $period_end_date,
			'partial_month_start' => $issue_month,
			'partial_month_start_issue' => $full_period_month
		];
	}

}

class Invoice {

	public static function generate_invoice_no($owner_type, $owner_id) {
		$owner_type = App::escape($owner_type);
		$owner_id = App::escape($owner_id);

		$new_no = null;
		while($new_no === null) {
			try {
				$check = App::new_uid(false, 32);
				$record = App::sql()->query_row("SELECT * FROM invoice_counter WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' LIMIT 1;", MySQL::QUERY_ASSOC);
				if($record) {
					App::sql()->update("UPDATE invoice_counter SET last_no = last_no + 1, check_value = '$check' WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';");
				} else {
					App::insert('invoice_counter', [
						'owner_type' => $owner_type,
						'owner_id' => $owner_id,
						'last_no' => 1,
						'check_value' => $check
					]);
				}
				$new_record = App::sql()->query_row("SELECT * FROM invoice_counter WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' LIMIT 1;", MySQL::QUERY_ASSOC);
				if($new_record) {
					if($check === $new_record['check_value']) $new_no = $new_record['last_no'];
				}

				if($new_no === null) {
					// Error while generating new invoice number, sleep for a bit
					usleep(rand(500000, 1000000));
				}
			} catch(Exception $ex) { }
		}

		return $new_no;
	}

	public $id;
	public $record;
	public $contract = null;

	public function __construct($id, $contract = null) {
		$this->id = App::escape($id);
		$this->contract = $contract;
		$this->reload();

		if($this->record && $this->contract === null) {
			$this->contract = new Contract($this->record['contract_id']);
		}
	}

	public function validate() {
		return !!$this->record && !!$this->contract && $this->contract->validate();
	}

	public function reload() {
		$this->record = App::select('invoice', $this->id);
	}

	public function mark_as_paid() {
		if($this->record['status'] !== 'cancelled') {
			App::update('invoice', $this->id, ['status' => 'paid']);
			App::sql()->update("UPDATE payment_transaction SET status = 'ok', notes = NULL WHERE type = 'invoice' AND invoice_id = '$this->id';");
			$this->reload();
			return true;
		}
		return false;
	}

	public function mark_as_outstanding() {
		// If invoice transaction has not been registered yet, mark invoice as not_approved
		// Otherwise, set status to outstanding
		$new_status = 'not_approved';
		$txn = App::sql()->query_row("SELECT id FROM payment_transaction WHERE type = 'invoice' AND invoice_id = '$this->id' LIMIT 1;");
		if($txn) $new_status = 'outstanding';

		App::update('invoice', $this->id, ['status' => $new_status]);
		App::sql()->update("UPDATE payment_transaction SET status = 'ok', notes = NULL WHERE type = 'invoice' AND invoice_id = '$this->id';");
		$this->reload();
		return true;
	}

	public function mark_as_cancelled() {
		App::update('invoice', $this->id, ['status' => 'cancelled']);
		App::sql()->update("UPDATE payment_transaction SET status = 'fail', notes = 'Invoice cancelled' WHERE type = 'invoice' AND invoice_id = '$this->id';");
		App::sql()->update("UPDATE payment_account SET trigger_card_payment_date = NULL, trigger_card_payment_invoice = NULL WHERE trigger_card_payment_invoice = '$this->id';");
		$this->reload();
		return true;
	}

	public function approve_invoice($dd = false) {
		if($this->record['status'] === 'not_approved') {
			App::update('invoice', $this->id, [ 'status' => 'outstanding' ]);
			$tm = new PaymentAccount($this->contract->record['owner_type'], $this->contract->record['owner_id'], $this->contract->record['customer_type'], $this->contract->record['customer_id']);
			$tm->register_invoice_transaction($this->id, $this->record['bill_date'], $this->record['bill_total']);

			$ci = App::select('contract_invoice', $this->record['contract_invoice_id']);

			$owner_type = $this->contract->record['owner_type'];
			$owner_id = $this->contract->record['owner_id'];
			$customer = Customer::resolve_details($this->contract->record['customer_type'], $this->contract->record['customer_id']);

			// Resolve system integrator for branding
			$si_id = '';
			$chain = Permission::get_level_chain($owner_type, $owner_id);
			if($chain && isset($chain->SI_id)) {
				$si_id = $chain->SI_id;
			}

			// If this is the first invoice, send welcome email
			if($this->record['is_first']) {
				$template_type = 'isp_welcome';
				if($ci && $ci['dd_payment_gateway']) $template_type = 'isp_welcome_dd';
				if($customer && $customer['email_address']) {
					Mailer::send_from_template($owner_type, $owner_id, $template_type, $customer['email_address'], $customer['name'], [
						'customer' => $customer,
						'payment_account' => $tm,
						'contract' => $this->contract,
						'invoice' => $this
					]);
				}
			}

			// Send invoice email
			$template_type = 'isp_invoice';
			if($ci && $ci['dd_payment_gateway']) $template_type = 'isp_invoice_dd';
			if($customer && $customer['email_address']) {
				$url = App::url($si_id)."/print/print.php?type=invoice&invoice_id=$this->id";
				$invoice_no = $this->record['invoice_no'];
				$filename = "invoice-{$invoice_no}.pdf";
				$invoice_file = Report::generate_pdf_report_file($url, $filename);

				Mailer::send_from_template($owner_type, $owner_id, $template_type, $customer['email_address'], $customer['name'], [
					'customer' => $customer,
					'payment_account' => $tm,
					'contract' => $this->contract,
					'invoice' => $this
				], ["$invoice_file:$filename"]);

				unlink($invoice_file);
			}

			$outstanding = $tm->get_outstanding_pence();
			if($outstanding) {
				// Customer owes us money. $amount is in pence
				$amount = $outstanding;
				if(($amount / 100) > $this->record['bill_total']) {
					// Never charge more than the current invoice
					if($this->record['bill_total'] < 0) {
						$amount = 0;
					} else {
						$amount = (int) round($this->record['bill_total'] * 100);
					}
				}

				if($dd && $amount > 0) {
					$dd_charged = false;

					try {
						// Take DD payment if needed
						if($ci && $ci['dd_payment_gateway']) {
							$mandate = PaymentGoCardlessMandate::get($ci['dd_payment_gateway'], $this->record['customer_type'], $this->record['customer_id']);
							if($mandate && $mandate->is_authorised()) {
								$m = $mandate->get_mandate_object();
								if($m) {
									$charge_date = $this->record['due_date'];
									if($charge_date && $m->next_possible_charge_date) {
										if(strtotime($charge_date) < strtotime($m->next_possible_charge_date)) {
											$charge_date = null;
										}
									} else {
										$charge_date = null;
									}

									if($this->record['invoice_no']) {
										$ndesc = 'no. '.$this->record['invoice_no'];
									} else {
										$ndesc = '#'.$this->id;
									}

									$payment = $mandate->create_payment($amount, $this->record['description'].' '.$ndesc, $this->id, $this->record['nonce'], $charge_date);
									if($payment) {
										$tm->register_invoice_dd_payment($this->id, $amount / 100, $ci['dd_payment_gateway'], $mandate->record['gocardless_mandate_id'], $payment->id);
										$dd_charged = true;
									}
								}
							}
						}
					} catch(Exception $ex) { }

					try {
						if(!$dd_charged && $ci && $ci['card_payment_gateway'] && $ci['auto_charge_saved_card']) {
							// Set up automatic card payment for due date
							$charge_date = $this->record['due_date'];
							$tm->schedule_card_payment($charge_date, $this->id);
						}
					} catch(Exception $ex) { }
				}
			}

			$tm->process_after_balance_changed();
		}
	}

	public function resend_email() {
		if($this->record['status'] !== 'not_approved') {
			$tm = new PaymentAccount($this->contract->record['owner_type'], $this->contract->record['owner_id'], $this->contract->record['customer_type'], $this->contract->record['customer_id']);
			$ci = App::select('contract_invoice', $this->record['contract_invoice_id']);

			$owner_type = $this->contract->record['owner_type'];
			$owner_id = $this->contract->record['owner_id'];
			$customer = Customer::resolve_details($this->contract->record['customer_type'], $this->contract->record['customer_id']);
			// Resolve system integrator for branding
			$si_id = '';
			$chain = Permission::get_level_chain($owner_type, $owner_id);
			if($chain && isset($chain->SI_id)) {
				$si_id = $chain->SI_id;
			}
			
			// Send invoice email
			$template_type = 'isp_invoice';
			if($ci && $ci['dd_payment_gateway']) $template_type = 'isp_invoice_dd';
			if($customer && $customer['email_address']) {
				$url = App::url($si_id)."/print/print.php?type=invoice&invoice_id=$this->id";
				$invoice_no = $this->record['invoice_no'];
				$filename = "invoice-{$invoice_no}.pdf";
				$invoice_file = Report::generate_pdf_report_file($url, $filename);

				Mailer::send_from_template($owner_type, $owner_id, $template_type, $customer['email_address'], $customer['name'], [
					'customer' => $customer,
					'payment_account' => $tm,
					'contract' => $this->contract,
					'invoice' => $this
				], ["$invoice_file:$filename"]);

				unlink($invoice_file);

				return true;
			}
		}

		return false;
	}

	public function force_submit_payment_if_needed($dd = true) {
		if($this->record['status'] === 'outstanding') {
			$tm = new PaymentAccount($this->contract->record['owner_type'], $this->contract->record['owner_id'], $this->contract->record['customer_type'], $this->contract->record['customer_id']);

			$ci = App::select('contract_invoice', $this->record['contract_invoice_id']);

			$outstanding = $tm->get_outstanding_pence();
			if($outstanding) {
				// Customer owes us money. $amount is in pence
				$amount = $outstanding;
				if(($amount / 100) > $this->record['bill_total']) {
					// Never charge more than the current invoice
					if($this->record['bill_total'] < 0) {
						$amount = 0;
					} else {
						$amount = (int) round($this->record['bill_total'] * 100);
					}
				}

				if($dd && $amount > 0) {
					try {
						// Take DD payment if needed
						if($ci && $ci['dd_payment_gateway']) {
							$mandate = PaymentGoCardlessMandate::get($ci['dd_payment_gateway'], $this->record['customer_type'], $this->record['customer_id']);
							if($mandate && $mandate->is_authorised()) {
								$m = $mandate->get_mandate_object();
								if($m) {
									$charge_date = $this->record['due_date'];
									if($charge_date && $m->next_possible_charge_date) {
										if(strtotime($charge_date) < strtotime($m->next_possible_charge_date)) {
											$charge_date = null;
										}
									} else {
										$charge_date = null;
									}

									if($this->record['invoice_no']) {
										$ndesc = 'no. '.$this->record['invoice_no'];
									} else {
										$ndesc = '#'.$this->id;
									}

									$payment = $mandate->create_payment($amount, $this->record['description'].' '.$ndesc, $this->id, $this->record['nonce'], $charge_date);
									if($payment) {
										$tm->register_invoice_dd_payment($this->id, $amount / 100, $ci['dd_payment_gateway'], $mandate->record['gocardless_mandate_id'], $payment->id);
									}
								}
							}
						}
					} catch(Exception $ex) {
						error_log("PAYMENT EXCEPTION");
						error_log(print_r($ex, true));
					}
				}
			}

			$tm->process_after_balance_changed();
		}
	}

	public function pay_invoice_by_card() {
		$pa = new PaymentAccount($this->contract->record['owner_type'], $this->contract->record['owner_id'], $this->contract->record['customer_type'], $this->contract->record['customer_id']);

		// Check if we need to charge the customer
		$outstanding = $pa->get_outstanding_pence();
		if(!$outstanding) return true;

		$amount = $outstanding;
		if(($amount / 100) > $this->record['bill_total']) {
			// Never charge more than the current invoice
			if($this->record['bill_total'] < 0) {
				$amount = 0;
			} else {
				$amount = (int) round($this->record['bill_total'] * 100);
			}
		}

		if($amount <= 0) return true;

		// Charge customer's stripe card
		$cd = Customer::resolve_details($pa->customer_type, $pa->customer_id);
		if(!$cd) return false;

		$ci = App::select('contract_invoice', $this->record['contract_invoice_id']);
		if(!$ci) return false;

		$pg = App::select('payment_gateway', $ci['card_payment_gateway']);
		if(!$pg) return false;

		\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

		$psc = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg[id]' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id';", MySQL::QUERY_ASSOC);
		if(!$psc) return false;

		$customer_id = $psc['stripe_customer'];
		if(!$customer_id) return false;

		try {
			// Find customer's default payment method
			$payment_method_id = null;
			$customer = \Stripe\Customer::retrieve($psc['stripe_customer'], ['stripe_account' => $pg['stripe_user_id']]);
			if($customer->invoice_settings->default_payment_method) {
				$payment_method_id = $customer->invoice_settings->default_payment_method;
			} else {
				// No default is set, see if customer has any card details saved
				$list = \Stripe\PaymentMethod::all(
					['customer' => $psc['stripe_customer'], 'type' => 'card'],
					['stripe_account' => $pg['stripe_user_id']]
				) ?: [];

				// Pick the first saved card. If the customer is correctly managed by our system,
				// there should only ever be one saved card.
				foreach($list as $pm) if(!$payment_method_id) $payment_method_id = $pm->id;
			}

			if(!$payment_method_id) return false;

			$intent = \Stripe\PaymentIntent::create([
				'amount' => $amount,
				'currency' => 'gbp',
				'customer' => $psc['stripe_customer'],
				'payment_method' => $payment_method_id,
				'off_session' => true,
				'confirm' => true
			], ['stripe_account' => $pg['stripe_user_id']]);

			App::insert('payment_transaction', [
				'account_id' => $pa->id,
				'create_datetime' => App::now(),
				'type' => 'card',
				'description' => 'Card payment',
				'amount' => $amount / 100,
				'status' => 'ok',
				'invoice_id' => $this->id,
				'payment_gateway_id' => $pg['id'],
				'ok_datetime' => App::now(),
				'transaction_ref' => $intent->id
			]);

			$this->mark_as_paid();
			$pa->process_after_balance_changed();
		} catch(Exception $ex) {
			// Log exception to catch any errors in implementation
			error_log($ex);
			return false;
		}

		return true;
	}

	public function get_payment_account() {
		$pa = new PaymentAccount($this->contract->record['owner_type'], $this->contract->record['owner_id'], $this->contract->record['customer_type'], $this->contract->record['customer_id']);
		return $pa;
	}

	public function dd_failed() {
		$pa = new PaymentAccount($this->contract->record['owner_type'], $this->contract->record['owner_id'], $this->contract->record['customer_type'], $this->contract->record['customer_id']);

		// Check if we need to charge the customer
		$outstanding = $pa->get_outstanding_pence();
		if(!$outstanding) return;

		$template_type = 'isp_dd_fail';

		// Raise an invoice for charges if any
		$ci_id = $this->record['contract_invoice_id'];
		if($ci_id) {
			$ci = new ContractInvoice($ci_id);
			if($ci->validate()) {
				$ci->create_dd_fail_charges();
				// Check if we need to schedule card payment
				if($ci->record['charge_card_if_dd_fails']) {
					// Check if customer has card
					$pg_id = $ci->record['card_payment_gateway'];
					if($pg_id) {
						$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg_id' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id';", MySQL::QUERY_ASSOC);
						if($card && $card['last4']) {
							$days = $ci->record['charge_card_after_days'] ?: 1;
							$charge_date = date('Y-m-d', strtotime("+$days day"));
							$pa->schedule_card_payment($charge_date, $this->id);
							$template_type = 'isp_dd_fail_card';
						}
					}
				}
			}
		}

		// Send email about failure
		$owner_type = $this->contract->record['owner_type'];
		$owner_id = $this->contract->record['owner_id'];
		$customer_type = $this->contract->record['customer_type'];
		$customer_id = $this->contract->record['customer_id'];
		$customer = Customer::resolve_details($customer_type, $customer_id);
		if($customer && $customer['email_address']) {
			Mailer::send_from_template($owner_type, $owner_id, $template_type, $customer['email_address'], $customer['name'] ?: '', [
				'customer' => $customer,
				'payment_account' => $pa,
				'contract' => $this->contract,
				'invoice' => $this
			], $daysLeft);
		}
	}

}

class PaymentAccount {

	public $owner_type;
	public $owner_id;
	public $customer_type;
	public $customer_id;
	public $id;
	public $record;

	public static function from_id($id) {
		$record = App::select('payment_account', $id);
		if(!$record) return null;

		return new PaymentAccount($record['owner_type'], $record['owner_id'], $record['customer_type'], $record['customer_id']);
	}

	public static function exists($owner_type, $owner_id, $customer_type, $customer_id) {
		$owner_type = App::escape($owner_type);
		$owner_id = App::escape($owner_id);
		$customer_type = App::escape($customer_type);
		$customer_id = App::escape($customer_id);

		$record = App::sql()->query_row("SELECT * FROM payment_account WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id' LIMIT 1;", MySQL::QUERY_ASSOC);

		return !!$record;
	}

	public function __construct($owner_type, $owner_id, $customer_type, $customer_id) {
		$this->owner_type = App::escape($owner_type);
		$this->owner_id = App::escape($owner_id);
		$this->customer_type = App::escape($customer_type);
		$this->customer_id = App::escape($customer_id);

		$this->record = App::sql()->query_row("SELECT * FROM payment_account WHERE owner_type = '$this->owner_type' AND owner_id = '$this->owner_id' AND customer_type = '$this->customer_type' AND customer_id = '$this->customer_id' LIMIT 1;", MySQL::QUERY_ASSOC);
		if($this->record) {
			$this->id = $this->record['id'];
		} else {
			$this->id = App::insert('payment_account', [
				'owner_type' => $owner_type,
				'owner_id' => $owner_id,
				'customer_type' => $customer_type,
				'customer_id' => $customer_id,
				'security_token' => App::rnd_string(32)
			]);
			$this->record = App::select('payment_account', $this->id);
		}
	}

	public function get_account_url($base_url = '') {
		if(!$base_url) $base_url = APP_URL;
		return $base_url.'/v3/account/'.$this->id.'/'.$this->record['security_token'];
	}

	/**
	 * Returns customer balance, completed payments only.
	 */
	public function get_balance() {
		$r = App::sql()->query_row(
			"SELECT SUM(amount) AS balance FROM payment_transaction
			WHERE account_id = '$this->id'
			AND status = 'ok';
		");
		return $r ? ($r->balance ?: 0) : 0;
	}

	/**
	 * Returns outstanding balance for customer, includes pending payments.
	 */
	public function get_outstanding() {
		$r = App::sql()->query_row(
			"SELECT SUM(amount) AS balance FROM payment_transaction
			WHERE account_id = '$this->id'
			AND status IN ('pending', 'ok');
		");
		return $r ? ($r->balance ?: 0) : 0;
	}

	/**
	 * Returns outstanding amount for customer in pence, includes pending payments.
	 * If nothing is owed, 0 is returned. If owed, a positive integer is returned.
	 */
	public function get_outstanding_pence() {
		$r = App::sql()->query_row(
			"SELECT SUM(CAST(-amount * 100 AS SIGNED)) AS balance FROM payment_transaction
			WHERE account_id = '$this->id' AND status IN ('pending', 'ok')
			HAVING balance > 0;
		");
		return $r ? ($r->balance ?: 0) : 0;
	}

	public function get_outstanding_confirmed_pence() {
		$r = App::sql()->query_row(
			"SELECT SUM(CAST(-amount * 100 AS SIGNED)) AS balance FROM payment_transaction
			WHERE account_id = '$this->id' AND status = 'ok'
			HAVING balance > 0;
		");
		return $r ? ($r->balance ?: 0) : 0;
	}

	/**
	 * Leaves invoices outstanding that add up to the current outstanding balance. All other invoices
	 * will be marked as paid. Oldest invoices will be marked as paid first. Invoices with outstanding
	 * payments are ignored.
	 *
	 * This function also fires all necessary events for fully paid / in arrears accounts.
	 */
	public function process_after_balance_changed() {
		$outstanding = $this->get_outstanding_confirmed_pence();

		// List invoices with bill total in pence to prevent rounding errors
		$list = App::sql()->query(
			"SELECT
				id, CAST(bill_total * 100 AS SIGNED) AS bill_total
			FROM invoice
			WHERE
				owner_type = '$this->owner_type' AND owner_id = '$this->owner_id'
				AND customer_type = '$this->customer_type' AND customer_id = '$this->customer_id'
				AND status = 'outstanding'
				AND id NOT IN (
					SELECT DISTINCT invoice_id FROM payment_transaction
					WHERE status = 'pending'
				)
			ORDER BY bill_date DESC;
		", MySQL::QUERY_ASSOC) ?: [];

		// Evaluate invoices
		$mark = [];
		$left = $outstanding;
		foreach($list as $invoice) {
			if($left <= 0) {
				// Mark as paid if nothing is left
				$mark[] = $invoice['id'];
			} else {
				// Decrease amount by bill total
				$left -= $invoice['bill_total'];
			}
		}

		// Mark invoices as paid
		foreach($mark as $invoice_id) {
			$invoice = new Invoice($invoice_id);
			if($invoice->validate()) $invoice->mark_as_paid();
		}

		// Fire events
		if(!$outstanding) {
			$this->event_fully_paid();
		}
	}

	/**
	 * Debits customer when creating an invoice
	 */
	public function register_invoice_transaction($invoice_id, $invoice_date, $amount) {
		// Resolve invoice number
		$invoice = App::sql()->query_row("SELECT invoice_no FROM invoice WHERE id = '$invoice_id';", MySQL::QUERY_ASSOC);
		if($invoice) {
			$desc = "Invoice no. $invoice[invoice_no]";
		} else {
			$desc = "Invoice #$invoice_id";
		}

		App::insert('payment_transaction', [
			'create_datetime' => App::now(),
			'account_id' => $this->id,
			'type' => 'invoice',
			'description' => $desc,
			'amount' => -$amount,
			'status' => 'ok',
			'invoice_id' => $invoice_id,
			'ok_datetime' => App::now()
		]);
	}

	public function register_invoice_dd_payment($invoice_id, $amount, $pg_id, $mandate_id, $payment_id) {
		// Resolve invoice number
		$invoice = App::sql()->query_row("SELECT invoice_no FROM invoice WHERE id = '$invoice_id';", MySQL::QUERY_ASSOC);
		if($invoice) {
			$desc = "Invoice no. $invoice[invoice_no]";
		} else {
			$desc = "Invoice #$invoice_id";
		}

		App::insert('payment_transaction', [
			'create_datetime' => App::now(),
			'account_id' => $this->id,
			'type' => 'dd',
			'description' => "GoCardless payment for $desc",
			'amount' => $amount,
			'status' => 'pending',
			'invoice_id' => $invoice_id,
			'payment_gateway_id' => $pg_id,
			'gocardless_mandate_id' => $mandate_id,
			'pending_datetime' => App::now(),
			'transaction_ref' => $payment_id
		]);
	}

	public function add_manual_transaction($type, $amount, $description, $transaction_ref) {
		$user = App::user();

		App::insert('payment_transaction', [
			'account_id' => $this->id,
			'create_datetime' => App::now(),
			'create_user' => $user ? $user->id : 0,
			'type' => $type,
			'description' => $description,
			'amount' => $amount,
			'status' => 'ok',
			'transaction_ref' => $transaction_ref ?: null
		]);
	}

	public function event_fully_paid() {
		// Select all contracts
		$contracts = App::sql()->query("SELECT id, area_id FROM contract WHERE owner_type = '$this->owner_type' AND owner_id = '$this->owner_id' AND customer_type = '$this->customer_type' AND customer_id = '$this->customer_id' AND area_id IS NOT NULL AND status NOT IN ('cancelled', 'ended');", MySQL::QUERY_ASSOC) ?: [];
		foreach($contracts as $c) {
			// Find out which package the contract is for
			$p = App::sql()->query_row(
				"SELECT
					ci.mandatory_dd, ci.dd_payment_gateway, cil.isp_package_id
				FROM contract_invoice AS ci
				JOIN contract_invoice_line AS cil ON cil.contract_invoice_id = ci.id
				WHERE ci.contract_id = '$c[id]' AND cil.type IN ('isp_package', 'isp_package_custom')
				LIMIT 1;
			", MySQL::QUERY_ASSOC);

			if($p) {
				if($p['mandatory_dd'] && $p['dd_payment_gateway']) {
					// If DD is mandatory, check if customer has a valid mandate
					// If not, don't activate
					$mandate = App::sql()->query_row("SELECT * FROM payment_gocardless_mandate WHERE payment_gateway_id = '$p[dd_payment_gateway]' AND customer_type = '$this->customer_type' AND customer_id = '$this->customer_id' AND status = 'authorised';", MySQL::QUERY_ASSOC);
					if(!$mandate) continue;
				}

				$area = new ISPArea($c['area_id']);
				$package = new ISPPackage($p['isp_package_id']);
				if($area->validate() && $package->validate()) {
					$onu = $area->get_onu();
					if($onu) {
						$active = $onu->get_active_package();
						if(!$active || ($package->id != $active->id)) {
							$onu->set_package($package);
						}
					}
				}
			}

			// Send activation email
			$cobj = new Contract($c['id']);
			$cobj->send_activation_email();
		}
	}

	public function event_in_arrears($print = false) {
		$today = date('Y-m-d');

		// Iterate through active contracts and switch them off
		if($this->customer_type === 'CU') {
			// Select all contracts
			$contracts = App::sql()->query("SELECT id, area_id FROM contract WHERE owner_type = '$this->owner_type' AND owner_id = '$this->owner_id' AND customer_type = '$this->customer_type' AND customer_id = '$this->customer_id' AND area_id IS NOT NULL AND status NOT IN ('cancelled', 'ended');", MySQL::QUERY_ASSOC) ?: [];
			foreach($contracts as $c) {
				$contract = new Contract($c['id']);
				if(!$contract->is_active()) continue;

				// Check if date has passed the start of the first unpaid period
				$res = App::sql()->query_row("SELECT MIN(period_start_date) AS outstanding_date FROM invoice WHERE contract_id = '$c[id]' AND status = 'outstanding' AND due_date < CAST(NOW() AS DATE);", MySQL::QUERY_ASSOC);

				if(!$res || $res['outstanding_date'] === null || strtotime($res['outstanding_date']) <= strtotime($today)) {
					if($print) echo "Switching off contract $c[id].\n";
					$contract->switch_off_devices($print);
				} else {
					if($print) echo "Contract $c[id] outstanding period not started yet ('$res[outstanding_date]').\n";
				}
			}
		}
	}

	public function get_payment_card() {
		return App::sql()->query_row(
			"SELECT psc.*
			FROM payment_stripe_card AS psc
			JOIN payment_gateway AS pg ON pg.id = psc.payment_gateway_id
			WHERE pg.owner_type = '$this->owner_type' AND pg.owner_id = '$this->owner_id' AND psc.customer_type = '$this->customer_type' AND psc.customer_id = '$this->customer_id'
			LIMIT 1;
		", MySQL::QUERY_ASSOC) ?: null;
	}

	public function schedule_card_payment($date, $invoice_id) {
		// Make sure we don't charge before the invoice due date
		$invoice = App::select('invoice', $invoice_id);
		if($invoice && $invoice['due_date']) {
			if(strtotime($date) < strtotime($invoice['due_date'])) $date = $invoice['due_date'];
		}

		App::update('payment_account', $this->id, [
			'trigger_card_payment_date' => $date,
			'trigger_card_payment_invoice' => $invoice_id
		]);

		// Refresh record after change
		$this->record = App::select('payment_account', $this->id);
	}

	public function get_details($transaction_limit = 10) {
		$pa = $this;
		$cd = Customer::resolve_details($pa->customer_type, $pa->customer_id);
		if(!$cd) return null;

		$owner_type = $pa->record['owner_type'];
		$owner_id = $pa->record['owner_id'];
		$od = Customer::resolve_details($owner_type, $owner_id);
		if(!$od) return null;

		if($transaction_limit) {
			$transaction_limit = "LIMIT $transaction_limit";
		} else {
			$transaction_limit = '';
		}

		$transactions = App::sql()->query(
			"SELECT
				create_datetime, type, description, amount, invoice_id, status
			FROM payment_transaction
			WHERE account_id = '$pa->id' AND NOT (type = 'invoice' AND status = 'fail')
			ORDER BY create_datetime DESC
			$transaction_limit;
		", MySQL::QUERY_ASSOC);

		$gateways = App::sql()->query(
			"SELECT DISTINCT
				pg.id, pg.type, pg.allow_part_payment, pg.part_minimum_pence
			FROM contract AS c
			JOIN contract_invoice AS ci ON ci.contract_id = c.id
			JOIN payment_gateway AS pg ON pg.id = ci.card_payment_gateway OR pg.id = ci.dd_payment_gateway
			WHERE c.owner_type = '$pa->owner_type' AND c.owner_id = '$pa->owner_id' AND c.customer_type = '$pa->customer_type' AND c.customer_id = '$pa->customer_id'
			ORDER BY pg.type DESC;
		", MySQL::QUERY_ASSOC) ?: [];

		$gateways = array_map(function($pg) use ($pa) {
			switch($pg['type']) {
				case 'gocardless':
					$mandate = App::sql()->query_row("SELECT * FROM payment_gocardless_mandate WHERE payment_gateway_id = '$pg[id]' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id' AND status = 'authorised';", MySQL::QUERY_ASSOC);
					if($mandate) {
						$pg['has_mandate'] = true;
					} else {
						$pg['has_mandate'] = false;
					}
					break;

				case 'stripe':
					$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$pg[id]' AND customer_type = '$pa->customer_type' AND customer_id = '$pa->customer_id';", MySQL::QUERY_ASSOC);
					if($card && $card['last4']) {
						$pg['has_card'] = true;
						$pg['exp_month'] = $card['exp_month'];
						$pg['exp_year'] = $card['exp_year'];
						$pg['last4'] = $card['last4'];
					} else {
						$pg['has_card'] = false;
					}
					break;
			}

			return $pg;
		}, $gateways);

		//
		// Resolve custom invoicing entity
		//

		$invoice_entity_id = null;

		// Look for invoicing entity in current contracts
		$res = App::sql()->query_row(
			"SELECT ci.invoice_entity_id
			FROM contract AS c
			JOIN contract_invoice AS ci ON ci.contract_id = c.id
			WHERE c.owner_type = '$pa->owner_type' AND c.owner_id = '$pa->owner_id' AND c.customer_type = '$pa->customer_type' AND c.customer_id = '$pa->customer_id'
				AND c.status IN ('unconfirmed', 'not_signed', 'pending', 'active', 'ending')
				AND ci.invoice_entity_id IS NOT NULL
			ORDER BY c.start_date DESC
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if($res) $invoice_entity_id = $res['invoice_entity_id'];

		// If not found, look for invoicing entity in expired contracts
		if(!$invoice_entity_id) {
			$res = App::sql()->query_row(
				"SELECT ci.invoice_entity_id
				FROM contract AS c
				JOIN contract_invoice AS ci ON ci.contract_id = c.id
				WHERE c.owner_type = '$pa->owner_type' AND c.owner_id = '$pa->owner_id' AND c.customer_type = '$pa->customer_type' AND c.customer_id = '$pa->customer_id'
					AND c.status IN ('cancelled', 'ended')
					AND ci.invoice_entity_id IS NOT NULL
				ORDER BY c.start_date DESC
				LIMIT 1;
			", MySQL::QUERY_ASSOC);
			if($res) $invoice_entity_id = $res['invoice_entity_id'];
		}

		// If found, merge invoicing entity details into owner details
		if($invoice_entity_id) {
			// Overlay invoicing entity data
			$entity = App::select('invoice_entity', $invoice_entity_id);

			if($entity) {
				$od['name'] = $entity['name'] ?: '';
				$od['contact_name'] = '';
				$od['company_name'] = $entity['name'] ?: '';
				$od['bank_name'] = $entity['bank_name'] ?: '';
				$od['bank_sort_code'] = $entity['bank_sort_code'] ?: '';
				$od['bank_account_number'] = $entity['bank_account_number'] ?: '';
				$od['vat_reg_number'] = $entity['vat_reg_number'] ?: '';

				if($entity['address_line_1'] || $entity['address_line_2'] || $entity['address_line_3'] || $entity['posttown'] || $entity['postcode']) {
					$od['address_line_1'] = $entity['address_line_1'] ?: '';
					$od['address_line_2'] = $entity['address_line_2'] ?: '';
					$od['address_line_3'] = $entity['address_line_3'] ?: '';
					$od['posttown'] = $entity['posttown'] ?: '';
					$od['postcode'] = $entity['postcode'] ?: '';
					$od['invoice_address_line_1'] = $entity['address_line_1'] ?: '';
					$od['invoice_address_line_2'] = $entity['address_line_2'] ?: '';
					$od['invoice_address_line_3'] = $entity['address_line_3'] ?: '';
					$od['invoice_posttown'] = $entity['posttown'] ?: '';
					$od['invoice_postcode'] = $entity['postcode'] ?: '';
				}

				if($entity['image_id']) {
					$od['logo_on_light_id'] = $entity['image_id'];
					$od['logo_on_dark_id'] = $entity['image_id'];
				}
			}
		}

		//
		// Invoicing entity resolved
		//

		$logo_url = '';
		if($od['logo_on_light_id']) {
			$uc = new UserContent($od['logo_on_light_id']);
			$logo_url = $uc->get_url();
		}

		$clist = App::sql()->query(
			"SELECT *
			FROM contract AS c
			WHERE c.owner_type = '$pa->owner_type' AND c.owner_id = '$pa->owner_id' AND c.customer_type = '$pa->customer_type' AND c.customer_id = '$pa->customer_id' AND c.status IN ('unconfirmed', 'not_signed', 'pending', 'active', 'ending')
			ORDER BY c.start_date;
		", MySQL::QUERY_ASSOC) ?: [];

		$cloud_access = false;
		$contracts = [];
		foreach($clist as $c) {
			if($c['provides_access']) $cloud_access = true;

			$cilist = App::sql()->query("SELECT * FROM contract_invoice WHERE contract_id = '$c[id]';", MySQL::QUERY_ASSOC) ?: [];
			$invoices = [];
			foreach($cilist as $ci) {
				$frequency = '/ month';
				if($ci['frequency'] === 'annual') $frequency = '/ year';

				$cillist = App::sql()->query("SELECT * FROM contract_invoice_line WHERE contract_invoice_id = '$ci[id]';", MySQL::QUERY_ASSOC) ?: [];
				$lines = [];
				foreach($cillist as $cil) {
					$description = $cil['description'];
					$unit_price = $cil['unit_price'] ?: 0;
					$quantity = $cil['quantity'] ?: 1;
					$quantity_description = '';
					$total = 0;
					$total_description = '';

					switch($cil['type']) {
						case 'isp_package':
							$package = App::select('olt_service@isp', $cil['isp_package_id']);
							if($package) {
								$description = $package['description'];
								$unit_price = $package['monthly_price'];
							}
							break;

						case 'isp_package_custom':
							$package = App::select('olt_service@isp', $cil['isp_package_id']);
							if($package) {
								$description = $package['description'];
							}
							break;

						case 'isp_routers':
							$quantity_description = 'per active router';
							break;

						case 'utility_e':
							$quantity_description = 'per unit of electricity used';
							break;

						case 'utility_g':
							$quantity_description = 'per unit of gas used';
							break;

						case 'utility_w':
							$quantity_description = 'per unit of water used';
							break;

						case 'utility_h':
							$quantity_description = 'per unit of heat used';
							break;

						case 'utility_s':
							$quantity_description = 'per day';
							break;
					}

					if($quantity_description) {
						$total_description = 'variable';
					} else {
						$total = round($unit_price * $quantity, 2);
					}

					$lines[] = [
						'description' => $description,
						'unit_price' => $unit_price,
						'quantity' => $quantity,
						'quantity_description' => $quantity_description,
						'total' => $total,
						'total_description' => $total_description,
						'frequency' => "+ VAT $frequency"
					];
				} // cil

				if(count($lines)) $invoices[] = [
					'lines' => $lines
				];
			} // ci

			$url = '';
			if($c['pdf_contract_id']) $url = APP_URL.'/ajax/get/print_contract?id='.$this->id.'&token='.$this->record['security_token'].'&contract='.$c['id'];

			$area_addr = [];
			$area = App::select('area', $c['area_id']);
			if($area) {
				$floor = App::select('floor', $area['floor_id']);
				if($floor) {
					$building = App::select('building', $floor['building_id']);
					if($building) {
						$new_address = [
							'address_line_1' => $area['description'],
							'address_line_2' => $building['address'],
							'address_line_3' => '',
							'posttown' => $building['posttown'],
							'postcode' => $area['postcode'] ? $area['postcode'] : $building['postcode'] // Postcode only override (if any)
						];
						if($area['address_line_1'] || $area['address_line_2'] || $area['address_line_3'] || $area['posttown']) {
							// Full address override
							$new_address = [
								'address_line_1' => $area['address_line_1'],
								'address_line_2' => $area['address_line_2'],
								'address_line_3' => $area['address_line_3'],
								'posttown' => $area['posttown'],
								'postcode' => $area['postcode']
							];
						}
						if($new_address['address_line_1']) $area_addr[] = $new_address['address_line_1'];
						if($new_address['address_line_2']) $area_addr[] = $new_address['address_line_2'];
						if($new_address['address_line_3']) $area_addr[] = $new_address['address_line_3'];
						if($new_address['posttown']) $area_addr[] = $new_address['posttown'];
						if($new_address['postcode']) $area_addr[] = $new_address['postcode'];
					}
				}
			}
			$area_addr = implode(', ', $area_addr);

			if(count($invoices)) $contracts[] = [
				'id' => $c['id'],
				'description' => $c['description'],
				'status' => $c['status'],
				'invoices' => $invoices,
				'has_pdf' => !!$c['pdf_contract_id'],
				'is_pdf_signed' => !!$c['pdf_contract_signed'],
				'print_url' => $url,
				'area_address' => $area_addr
			];
		} // c

		$result = [
			'owner_type' => $owner_type,
			'owner_id' => $owner_id,
			'owner_name' => $od['name'],
			'owner_address_line_1' => $od['address_line_1'],
			'owner_address_line_2' => $od['address_line_2'],
			'owner_address_line_3' => $od['address_line_3'],
			'owner_posttown' => $od['posttown'],
			'owner_postcode' => $od['postcode'],
			'owner_logo' => $logo_url,
			'contact_name' => $cd['contact_name'],
			'company_name' => $cd['company_name'],
			'balance' => $pa->get_balance(),
			'outstanding' => $pa->get_outstanding(),
			'outstanding_pence' => $pa->get_outstanding_pence(),
			'transactions' => $transactions ?: [],
			'stripe_pk' => STRIPE_PUBLISHABLE_KEY,
			'gateways' => $gateways,
			'contracts' => $contracts,
			'cloud_access' => $cloud_access,
			'account_url' => $this->get_account_url()
		];

		return $result;
	}

}
