<?php

class TenantBill {

	const URL = 'http://db3.eticom.co.uk/bills';

	const TYPE_RENT    = 'tenant_rent';
	const TYPE_UTILITY = 'tenant_utility';
	const TYPE_ESTATE  = 'tenant_estate';
	const TYPE_MISC    = 'tenant_misc';

	const STATUS_OUTSTANDING      = 'outstanding';
	const STATUS_SENT             = 'sent';
	const STATUS_PAID             = 'paid';
	const STATUS_FAILED_NO_FUNDS  = 'failed - no funds';
	const STATUS_FAILED_PERMANENT = 'failed - permanent';

	public $type;
	public $id;
	public $info;

	public static $tables = [
		self::TYPE_RENT    => 'tenant_bill_rent',
		self::TYPE_UTILITY => 'tenant_bill_utility',
		self::TYPE_ESTATE  => 'tenant_bill_estate_cost',
		self::TYPE_MISC    => 'tenant_bill_misc',
	];

	public static function get_type_description($type) {
		switch($type) {
			case self::TYPE_RENT:    return 'Rental Charges';
			case self::TYPE_UTILITY: return 'Utility Charges';
			case self::TYPE_ESTATE:  return 'Estate Costs';
			case self::TYPE_MISC:    return 'Miscellaneous';
		}
		return '';
	}

	public static function validate_type($type) {
		return in_array($type, [self::TYPE_RENT, self::TYPE_UTILITY, self::TYPE_ESTATE, self::TYPE_MISC]) ? $type : '';
	}

	public static function get_bill_generate_date($type) {
		$result = [];

		// For now, all types return first of the month for the next 2 years, starting from today

		$today = strtotime('today');
		$day = strtotime('first day of this month');
		if($day < $today) $day = strtotime('+1 month', $day);

		for($i = 0; $i < 24; $i++) {
			$result[] = [
				'value' => date('Y-m-d', $day),
				'description' => date('d F Y', $day)
			];

			$day = strtotime('+1 month', $day);
		}

		return $result;
	}

	public function __construct($type, $id) {
		$type = self::validate_type($type);

		$this->type = $type;
		$this->id = App::escape($id);

		$this->read_from_database();
	}

	public function validate() {
		return !!$this->info;
	}

	public function read_from_database() {
		$this->info = null;
		if($this->type) {
			$table = $this->get_table_name();
			$this->info = App::sql()->query_row("SELECT * FROM $table WHERE id = '$this->id';");
		}
	}

	public function get_table_name() {
		return isset(self::$tables[$this->type]) ? self::$tables[$this->type] : '';
	}

	public function get_bill($content_disp = "attachment") {
		if($this->info && Permission::get_client($this->info->client_id)->check(Permission::ADMIN)) {
			header("Content-type:application/pdf");
			header("Content-Disposition:". $content_disp .";filename=".$this->get_filename());
			return readfile($this->get_url());
		} else {
			return false;
		}
	}

	public function get_filename() {
		$date = $this->info->bill_date;
		$tenant_name = $this->info->tenant_company;
		if(!$tenant_name) $tenant_name = $this->info->tenant_name;
		$unit_name = $this->info->area_description;

		return $date."_". $tenant_name ."_".$unit_name."_".$this->type.".pdf";
	}

	public function get_url() {
		return self::path_to_url($this->info->filepath);
	}

	/**
	 * Must be called from a valid URL context (browser only)
	 */
	public static function get_public_url($type, $id) {
		return APP_URL.'/ajax/get/get_tenant_bill?type='.$type.'&id='.$id;
	}

	public function path_to_url($filepath) {
		$prefix = '/var/www/bills';
		if(substr($filepath, 0, strlen($prefix)) == $prefix) {
			$filepath = substr($filepath, strlen($prefix));
		}

		return self::URL.$filepath;
	}

	public function is_outstanding() {
		return $this->info->status != self::STATUS_PAID && $this->info->status != self::STATUS_SENT;
	}

	/**
	 * Manually mark bill as paid
	 */
	public function mark_as_paid() {
		if($this->is_outstanding()) {
			$paid_status = self::STATUS_PAID;
			$bill_table = $this->get_table_name();
			App::sql()->update("UPDATE $bill_table SET status = '$paid_status' WHERE id = '$this->id';");
		}
	}

	public static function get_latest_bills_by_type($tenant_id, $area_id) {
		$bills = [];
		foreach(self::$tables as $type => $table) {
			$bill = App::sql()->query_row("SELECT id FROM $table WHERE tenant_id = '$tenant_id' AND area_id = '$area_id' ORDER BY bill_date DESC LIMIT 1;");
			if($bill) {
				$bills[$type] = new self($type, $bill->id);
			}
		}

		return $bills;
	}

	public function get_lines() {
		$lines = [];

		$add_line = function($icon, $desc, $amount) use (&$lines) {
			if($desc && $amount) {
				$lines[] = [
					'icon'        => $icon,
					'description' => $desc,
					'amount'      => $amount
				];
			}
		};

		switch($this->type) {
			case self::TYPE_RENT:
				$add_line('area', 'Rent', $this->info->rental_charge_ex_vat);
				$add_line('plus', 'Service charge', $this->info->service_charge_ex_vat);
				break;

			case self::TYPE_UTILITY:
				$add_line('bolt', 'Electricity', $this->info->electric_cost);
				$add_line('flame', 'Gas', $this->info->gas_cost);
				$add_line('droplet', 'Water', $this->info->water_cost);
				break;

			case self::TYPE_ESTATE:
				$add_line('building-2', 'Estate charges', $this->info->estate_cost_ex_vat);
				break;

			case self::TYPE_MISC:
				$add_line('', $this->info->misc_1_desc, $this->info->misc_1_value);
				$add_line('', $this->info->misc_2_desc, $this->info->misc_2_value);
				$add_line('', $this->info->misc_3_desc, $this->info->misc_3_value);
				$add_line('', $this->info->misc_4_desc, $this->info->misc_4_value);
				$add_line('', $this->info->misc_5_desc, $this->info->misc_5_value);
				$add_line('', $this->info->misc_6_desc, $this->info->misc_6_value);
				$add_line('', $this->info->misc_7_desc, $this->info->misc_7_value);
				$add_line('', $this->info->misc_8_desc, $this->info->misc_8_value);
				$add_line('', $this->info->misc_9_desc, $this->info->misc_9_value);
				$add_line('', $this->info->misc_10_desc, $this->info->misc_10_value);
				break;
		}

		return $lines;
	}

	public function get_json_info() {
		return [
			'type'             => $this->type,
			'type_description' => self::get_type_description($this->type),
			'id'               => $this->id,
			'area_description' => $this->info->area_description,
			'bill_date'        => App::format_datetime('d F Y', $this->info->bill_date, 'Y-m-d'),
			'lines'            => $this->get_lines(),
			'subtotal'         => $this->info->subtotal,
			'vat_due'          => $this->info->vat_due,
			'vat_rate'         => App::format_number($this->info->vat_rate, 0, 2),
			'total'            => $this->info->bill_total
		];
	}

}
