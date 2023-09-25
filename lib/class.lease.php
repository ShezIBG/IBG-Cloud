<?php

class Lease {
	const STATUS_PREVIOUS         = 'previous';
	const STATUS_CURRENT_ACTIVE   = 'current - active';
	const STATUS_CURRENT_EXPIRING = 'current - expiring';
	const STATUS_CURRENT_ENDING   = 'current - ending';
	const STATUS_FUTURE           = 'future - approved';

	const BILL_MONTH        = 'Monthly';
	const BILL_QUARTER_ENG  = 'Quarter Days - England, Wales and Ireland';
	const BILL_QUARTER_SCOT = 'Quarter Days - Scotland';
	const BILL_QUARTER_LA   = 'Quarter Days - Local Authority';

	const PAYMENT_DD    = 'Direct Debit';
	const PAYMENT_OTHER = 'Other';

	const TERM_WEEK  = 'week';
	const TERM_MONTH = 'month';
	const TERM_YEAR  = 'year';

	public $id;
	public $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM tenant_lease WHERE id = '$this->id';");
	}

	public static function get_previous_lease($tenanted_id) {
		$tenanted_area_id = App::escape($tenanted_id);
		$status = "'".self::STATUS_PREVIOUS."'";
		$lease = App::sql()->query_row("SELECT tl.id FROM tenant_lease AS tl JOIN tenanted_area AS ta ON ta.area_id = tl.area_id WHERE ta.id = $tenanted_area_id AND tl.status = $status ORDER BY tl.lease_end_date DESC LIMIT 1");
		return $lease ? new Lease($lease->id) : null;
	}

	public static function get_current_lease($tenanted_id) {
		$tenanted_area_id = App::escape($tenanted_id);
		$status = "('".self::STATUS_CURRENT_ACTIVE."', '".self::STATUS_CURRENT_EXPIRING."', '".self::STATUS_CURRENT_ENDING."')";
		$lease = App::sql()->query_row("SELECT tl.id FROM tenant_lease AS tl JOIN tenanted_area AS ta ON ta.area_id = tl.area_id WHERE ta.id = $tenanted_area_id AND tl.status IN $status ORDER BY tl.lease_end_date DESC LIMIT 1");
		return $lease ? new Lease($lease->id) : null;
	}

	public static function get_future_lease($tenanted_id) {
		$tenanted_area_id = App::escape($tenanted_id);
		$status = "'".self::STATUS_FUTURE."'";
		$lease = App::sql()->query_row("SELECT tl.id FROM tenant_lease AS tl JOIN tenanted_area AS ta ON ta.area_id = tl.area_id WHERE ta.id = $tenanted_area_id AND tl.status = $status ORDER BY tl.lease_start_date LIMIT 1");
		return $lease ? new Lease($lease->id) : null;
	}

	public static function list_statuses() {
		return [
			[ 'value' => self::STATUS_PREVIOUS,         'description' => 'Previous'           ],
			[ 'value' => self::STATUS_CURRENT_ACTIVE,   'description' => 'Current (active)'   ],
			[ 'value' => self::STATUS_CURRENT_EXPIRING, 'description' => 'Current (expiring)' ],
			[ 'value' => self::STATUS_CURRENT_ENDING,   'description' => 'Current (ending)'   ],
			[ 'value' => self::STATUS_FUTURE,           'description' => 'Future'             ]
		];
	}

	public function get_status_description() {
		foreach(self::list_statuses() as $item) {
			if($item['value'] == $this->info->status) return $item['description'];
		}
		return '';
	}

	public function get_sub_status_description() {
		$subs = [
			[ 'value' => self::STATUS_CURRENT_ACTIVE,   'description' => 'active'   ],
			[ 'value' => self::STATUS_CURRENT_EXPIRING, 'description' => 'expiring' ],
			[ 'value' => self::STATUS_CURRENT_ENDING,   'description' => 'ending'   ]
		];
		foreach($subs as $item) {
			if($item['value'] == $this->info->status) return $item['description'];
		}
		return '';
	}

	public static function list_bill_frequencies() {
		return [
			[ 'value' => self::BILL_MONTH,        'description' => self::BILL_MONTH        ],
			[ 'value' => self::BILL_QUARTER_ENG,  'description' => self::BILL_QUARTER_ENG  ],
			[ 'value' => self::BILL_QUARTER_SCOT, 'description' => self::BILL_QUARTER_SCOT ],
			[ 'value' => self::BILL_QUARTER_LA,   'description' => self::BILL_QUARTER_LA   ],
		];
	}

	public function get_bill_frequency_description($type = TenantBill::TYPE_RENT) {
		foreach(self::list_bill_frequencies() as $item) {
			switch($type) {
				case TenantBill::TYPE_RENT:    if($item['value'] == $this->info->bill_generate_frequency_rent)        { return $item['description']; } else { break; }
				case TenantBill::TYPE_UTILITY: if($item['value'] == $this->info->bill_generate_frequency_utility)     { return $item['description']; } else { break; }
				case TenantBill::TYPE_ESTATE:  if($item['value'] == $this->info->bill_generate_frequency_estate_cost) { return $item['description']; } else { break; }
				case TenantBill::TYPE_MISC:    if($item['value'] == $this->info->bill_generate_frequency_misc)        { return $item['description']; } else { break; }
			}
		}
		return '';
	}

	public function get_bill_frequency_short_description($type = TenantBill::TYPE_RENT) {
		$bfs = [
			[ 'value' => self::BILL_MONTH,        'description' => 'pm' ],
			[ 'value' => self::BILL_QUARTER_ENG,  'description' => 'pq' ],
			[ 'value' => self::BILL_QUARTER_SCOT, 'description' => 'pq' ],
			[ 'value' => self::BILL_QUARTER_LA,   'description' => 'pq' ],
		];
		foreach($bfs as $item) {
			switch($type) {
				case TenantBill::TYPE_RENT:    if($item['value'] == $this->info->bill_generate_frequency_rent)        { return $item['description']; } else { break; }
				case TenantBill::TYPE_UTILITY: if($item['value'] == $this->info->bill_generate_frequency_utility)     { return $item['description']; } else { break; }
				case TenantBill::TYPE_ESTATE:  if($item['value'] == $this->info->bill_generate_frequency_estate_cost) { return $item['description']; } else { break; }
				case TenantBill::TYPE_MISC:    if($item['value'] == $this->info->bill_generate_frequency_misc)        { return $item['description']; } else { break; }
			}
		}
		return '';
	}

	public static function list_payment_types() {
		return [
			[ 'value' => self::PAYMENT_DD,    'description' => 'Direct Debit' ],
			[ 'value' => self::PAYMENT_OTHER, 'description' => 'Other'        ]
		];
	}

	public function get_payment_type_description() {
		foreach(self::list_payment_types() as $item) {
			if($item['value'] == $this->info->payment_type) return $item['description'];
		}
		return '';
	}

	public static function list_term_units() {
		return [
			[ 'value' => self::TERM_WEEK,  'description' => 'Weeks'  ],
			[ 'value' => self::TERM_MONTH, 'description' => 'Months' ],
			[ 'value' => self::TERM_YEAR,  'description' => 'Years'  ]
		];
	}

	public function get_term_units_description() {
		foreach(self::list_term_units() as $item) {
			if($item['value'] == $this->info->term_units) return $item['description'];
		}
		return '';
	}

	// Generates the next billing date after $date depending on billing frequency
	// $date should be in ISO format YYYY-MM-DD, also returns ISO format
	public static function get_next_bill_date($date, $freq) {
		$d = new DateTime($date);
		$y = $d->format('Y');
		$date = $d->format('Y-m-d');

		switch($freq) {
			case self::BILL_MONTH:
				$day = strtotime('first day of this month', strtotime($date));
				if($day <= strtotime('today')) $day = strtotime('+1 month', $day);
				return date('Y-m-d', $day);

			case self::BILL_QUARTER_ENG:
				$q1 = new DateTime("$y-03-25"); if($q1 > $d) return $q1->format('Y-m-d');
				$q2 = new DateTime("$y-06-24"); if($q2 > $d) return $q2->format('Y-m-d');
				$q3 = new DateTime("$y-09-29"); if($q3 > $d) return $q3->format('Y-m-d');
				$q4 = new DateTime("$y-12-25"); if($q4 > $d) return $q4->format('Y-m-d');
				$q1->modify('+1 year');
				return $q1->format('Y-m-d');

			case self::BILL_QUARTER_SCOT:
				$q1 = new DateTime("$y-02-28"); if($q1 > $d) return $q1->format('Y-m-d');
				$q2 = new DateTime("$y-05-28"); if($q2 > $d) return $q2->format('Y-m-d');
				$q3 = new DateTime("$y-08-28"); if($q3 > $d) return $q3->format('Y-m-d');
				$q4 = new DateTime("$y-11-28"); if($q4 > $d) return $q4->format('Y-m-d');
				$q1->modify('+1 year');
				return $q1->format('Y-m-d');

			case self::BILL_QUARTER_LA:
				$q1 = new DateTime("$y-01-01"); if($q1 > $d) return $q1->format('Y-m-d');
				$q2 = new DateTime("$y-04-01"); if($q2 > $d) return $q2->format('Y-m-d');
				$q3 = new DateTime("$y-07-01"); if($q3 > $d) return $q3->format('Y-m-d');
				$q4 = new DateTime("$y-10-01"); if($q4 > $d) return $q4->format('Y-m-d');
				$q1->modify('+1 year');
				return $q1->format('Y-m-d');

			default:
				return $date;
		}
	}

	public function get_tenant() {
		if($this->info->tenant_id) {
			$tenant_id = $this->info->tenant_id;
			return App::sql()->query_row("SELECT * FROM tenant WHERE id = $tenant_id;");
		}
	}

	public function is_previous() {
		return $this->info->status == self::STATUS_PREVIOUS;
	}

	public function is_future() {
		return $this->info->status == self::STATUS_FUTURE;
	}

	public function is_current() {
		$status = $this->info->status;
		return $status == self::STATUS_CURRENT_ACTIVE || $status == self::STATUS_CURRENT_EXPIRING || $status == self::STATUS_CURRENT_ENDING;
	}

	public function is_expiring() {
		return $this->info->status == self::STATUS_CURRENT_EXPIRING;
	}

	public function is_ending() {
		return $this->info->status == self::STATUS_CURRENT_ENDING;
	}

	public function get_area_description() {
		$area_info = App::sql()->query_row("SELECT description FROM area WHERE id = '{$this->info->area_id}';");
		return $area_info ? $area_info->description : '';
	}

	public function get_area_info() {
		return isset($this->info->area_id) ? App::sql()->query_row("SELECT * FROM area WHERE id = '{$this->info->area_id}';") : false;
	}

	public function get_latest_bills_by_type() {
		return TenantBill::get_latest_bills_by_type($this->info->tenant_id, $this->info->area_id);
	}

}
