<?php

class Meter {
	public $id, $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM meter WHERE id = '$id'");
	}

	public function validate($building_id = null) {
		if(!$this->info) return false;
		if($building_id != null && $building_id != $this->get_building_id()) return false;
		return true;
	}

	public function get_available_tariffs($client_id) {
		$tariff_table = '';
		switch($this->info->meter_type) {
			case 'E': $tariff_table = 'tariff_electricity'; break;
			case 'G': $tariff_table = 'tariff_gas';         break;
			case 'W': $tariff_table = 'tariff_water';       break;
			case 'H':
				// TODO: Heat meters not yet supported
				break;
		}

		$result = [];
		if($client_id != null && $tariff_table) {
			if($this->info->tariff_id) {
				$result = App::sql()->query("SELECT id, description FROM $tariff_table WHERE client_id = $client_id OR id = ".$this->info->tariff_id." ORDER BY description");
			} else {
				$result = App::sql()->query("SELECT id, description FROM $tariff_table WHERE client_id = $client_id ORDER BY description");
			}
		}

		$result = array_merge([[ 'id' => '', 'description' => 'No tariff' ]], $result ? $result : []);

		return $result;
	}

	public function get_building_id() {
		$area_id = $this->info->area_id;
		if(!$area_id) return null;

		$r = App::sql()->query_row(
			"SELECT floor.building_id
			FROM area
			JOIN floor ON floor.id = area.floor_id
			WHERE area.id = '$area_id'
			LIMIT 1;
		");
		return $r ? $r->building_id : null;
	}

	public function get_tariff_info() {
		$tariff_table = '';
		$supplier_field = '';
		switch($this->info->meter_type) {
			case 'E': $tariff_table = 'tariff_electricity'; $supplier_field = 'supplier_id';       break;
			case 'G': $tariff_table = 'tariff_gas';         $supplier_field = 'supplier_id';       break;
			case 'W': $tariff_table = 'tariff_water';       $supplier_field = 'water_supplier_id'; break;
			case 'H':
				// TODO: Heat meters not yet supported
				break;
		}

		if($tariff_table) {
			if($this->info->tariff_id && $supplier_field) {
				
				return App::sql()->query_row("SELECT tt.*, s.description AS supplier_name FROM $tariff_table AS tt LEFT JOIN energy_supplier AS s ON s.id = tt.{$supplier_field} WHERE tt.id = '".$this->info->tariff_id."'");

			}
		}
		
		return null;
	}

	public function get_supply_tariff_info() {
		if($this->is_submeter()) {
			$parent = $this->get_parent_meter();
			return $parent->get_supply_tariff_info();
		} else {
			return $this->get_tariff_info();
		}
	}

	public function get_number_of_readings() {
		if($this->is_submeter()) {
			$parent = $this->get_parent_meter();
			return $parent ? $parent->get_number_of_readings() : 1;
		} else {
			switch($this->info->meter_type) {
				case 'E':
					// Sub-meters always have a single reading for now
					if($this->info->parent_id) return 1;

					// Check tariff's night and weekend rate
					$tariff = $this->get_tariff_info();
					if($tariff) {
						if($tariff->economy7_tariff_non_dd_unit_rate_night) return 2;
						if($tariff->economy7_tariff_dd_unit_rate_night) return 2;
						if($tariff->economy7_tariff_non_dd_unit_rate_ew) return 3;
						if($tariff->economy7_tariff_dd_unit_rate_ew) return 3;
					}

					// If no special rates set, just use a single reading
					return 1;

				case 'G':
					return 1;

				case 'W':
					return 1;

				case 'H':
					return 1;

				default:
					return 0;
			}
		}
	}

	public function get_reading_unit($html = false) {
		switch($this->info->unit) {
			case 'm3':     return $html ? 'm<sup>3</sup>' : 'm3';
			case 'litres': return 'litres';
			case 'ft3':    return $html ? 'ft<sup>3</sup>' : 'ft3';
			case 'kWh':    return 'kWh';
			case 'btu':    return 'BTU';
			default: return '';
		}
	}

	public function is_submeter() {
		return !!$this->info->parent_id;
	}

	public function has_submeters() {
		$sub = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM meter WHERE parent_id = '$this->id';");
		return $sub ? $sub->cnt > 0 : false;
	}

	public function get_submeters() {
		$list = App::sql()->query("SELECT id FROM meter WHERE parent_id = '$this->id';", MySQL::QUERY_ASSOC, false);
		return array_map(function($item) { return new Meter($item['id']); }, $list ?: []);
	}

	public function is_automatic() {
		return $this->info->monitoring_device_type != 'none' || $this->info->monitoring_bus_type === 'calculated';
	}

	public static function type_to_description($type) {
		switch($type) {
			case 'E': return 'Electric';
			case 'G': return 'Gas';
			case 'W': return 'Water';
			case 'H': return 'Heat';
			case 'EG': return 'Generated';
			default:  return '';
		}
	}

	public function get_parent_meter() {
		return $this->info->parent_id ? new Meter($this->info->parent_id) : null;
	}

	public function get_tenant_name() {
		$area_id = $this->info->area_id;
		if($area_id) {
			$area_info = App::sql()->query_row(
				"SELECT tenant.company AS tenant_name FROM area
				LEFT JOIN tenanted_area ON tenanted_area.area_id = area.id AND (area.is_tenanted = 1 OR area.is_owner_occupied = 1)
				LEFT JOIN tenant ON tenant.id = tenanted_area.tenant_id
				WHERE area.id = '$area_id';");
			return isset($area_info->tenant_name) ? ($area_info->tenant_name ?: '') : '';
		} else {
			return '';
		}
	}

	/**
	 * @param $date ISO format (YYYY-MM-DD)
	 */
	public function get_previous_reading($date = null, $reading_id = 0) {
		if(!$date) $date = explode('T', date('c'))[0];
		$date = App::escape($date);
		$reading_id = App::escape($reading_id);

		return App::sql()->query_row(
			"SELECT * FROM meter_reading
			WHERE meter_id = '$this->id' AND reading_date < '$date' AND id <> '$reading_id'
			ORDER BY reading_date DESC, initial_reading DESC
			LIMIT 1;
		");
	}

	/**
	 * @param $date ISO format (YYYY-MM-DD)
	 */
	public function get_next_reading($date = null, $reading_id = 0) {
		if(!$date) $date = explode('T', date('c'))[0];
		$date = App::escape($date);
		$reading_id = App::escape($reading_id);

		return App::sql()->query_row(
			"SELECT * FROM meter_reading
			WHERE meter_id = '$this->id' AND reading_date > '$date' AND id <> '$reading_id'
			ORDER BY reading_date, initial_reading
			LIMIT 1;
		");
	}

	public function get_latest_reading() {
		return App::sql()->query_row(
			"SELECT * FROM meter_reading
			WHERE meter_id = '$this->id'
			ORDER BY reading_date DESC, initial_reading DESC
			LIMIT 1;
		");
	}

	public function get_date_reading($prev_dt) {
		return App::sql()->query_row(
			"SELECT * FROM automated_meter_reading_history
			WHERE meter_id = '$this->id' AND reading_day = '$prev_dt'
			ORDER BY reading_day DESC
			LIMIT 1;
		");
	}

	public function get_date_change_reading($prev_dt, $meter_id) {
		return App::sql()->query_row(
			"SELECT * FROM automated_meter_reading_history
			WHERE meter_id = '$meter_id' AND reading_day = '$prev_dt'
			ORDER BY reading_day DESC
			LIMIT 1;
		");
	}

	public function get_all_readings() {
		return App::sql()->query(
			"SELECT * FROM meter_reading
			WHERE meter_id = '$this->id'
			ORDER BY reading_date DESC, initial_reading DESC;
		");
	}

	public static function calculate_usage($previous_reading, $current_reading) {
		if(!is_numeric($previous_reading) || !is_numeric($current_reading)) return null;

		if($current_reading >= $previous_reading) {
			return $current_reading - $previous_reading;
		} else {
			// Meter has turned over
			$turning_point = pow(10, max(ceil(log10($previous_reading)), 5));
			return ($turning_point - $previous_reading) + $current_reading;
		}
	}

	/**
	 * @param $date ISO format (YYYY-MM-DD)
	 */
	public function add_meter_reading($user_id, $date, $readings, $initial_reading) {
		if(!$date) $date = explode('T', date('c'))[0];
		if(!is_array($readings)) $readings = [$readings];

		$building_id = $this->get_building_id();
		$meter_id = $this->id;
		$read_by = 'u_'.$user_id;
		$reading_date = $date;
		$reading_1 = isset($readings[0]) ? (is_numeric($readings[0]) ? (int)$readings[0] : null) : null;
		$reading_2 = isset($readings[1]) ? (is_numeric($readings[1]) ? (int)$readings[1] : null) : null;
		$reading_3 = isset($readings[2]) ? (is_numeric($readings[2]) ? (int)$readings[2] : null) : null;
		$imported_since = null;
		$imported_rate_1 = null;
		$imported_rate_2 = null;
		$imported_rate_3 = null;
		$initial_reading = $initial_reading ? 1 : 0;

		// You can't take two readings on the same day for the same meter with the same initial reading flag
		$duplicate = App::sql()->query_row("SELECT * FROM meter_reading WHERE meter_id = '$meter_id' AND reading_date = '$reading_date' AND initial_reading = '$initial_reading';");
		if($duplicate) {
			throw new Exception('A reading has already been recorded for this meter on the same day.');
		}

		$previous_reading = $this->get_previous_reading($date);
		$next_reading = $this->get_next_reading($date);

		// Calculate usage based on the previous reading
		if(!$initial_reading && $previous_reading) {
			$imported_rate_1 = self::calculate_usage($previous_reading->reading_1, $reading_1);
			$imported_rate_2 = self::calculate_usage($previous_reading->reading_2, $reading_2);
			$imported_rate_3 = self::calculate_usage($previous_reading->reading_3, $reading_3);
			$imported_since = $previous_reading->reading_date;
		}

		// Insert reading into the database
		$new_id = App::insert('meter_reading', [
			'building_id' => $building_id,
			'meter_id' => $meter_id,
			'read_by' => $read_by,
			'reading_date' => $reading_date,
			'reading_1' => $reading_1,
			'reading_2' => $reading_2,
			'reading_3' => $reading_3,
			'imported_since' => $imported_since,
			'imported_rate_1' => $imported_rate_1,
			'imported_rate_2' => $imported_rate_2,
			'imported_rate_3' => $imported_rate_3,
			'initial_reading' => $initial_reading
		]);

		if(!$new_id) {
			throw new Exception('Meter reading could not be recorded.');
		}

		// Update usage on next reading based on current one
		// This means you're taking an intermediate reading between two existing ones
		if($next_reading && !$next_reading->initial_reading) {
			App::update('meter_reading', $next_reading->id, [
				'imported_since' => $reading_date,
				'imported_rate_1' => self::calculate_usage($reading_1, $next_reading->reading_1),
				'imported_rate_2' => self::calculate_usage($reading_2, $next_reading->reading_2),
				'imported_rate_3' => self::calculate_usage($reading_3, $next_reading->reading_3)
			]);
		}

		// Update meter periods

		// If there is a previous reading, update the period that it belongs to (could be the same period that the current reading is in)
		$reading = new MeterReading($new_id);
		list($reading_year, $reading_month) = $reading->get_year_and_month();

		$prev = $reading->get_previous_reading();
		$prev_year = 0;
		$prev_month = 0;
		if($prev) {
			list($prev_year, $prev_month) = $prev->get_year_and_month();
			MeterPeriod::recalculate_period($this->id, $prev_year, $prev_month);
		}

		if($prev_year != $reading_year && $prev_month != $reading_month) {
			// If there is a next reading, update current period
			$next = $reading->get_next_reading();
			if($next) {
				MeterPeriod::recalculate_period($this->id, $reading_year, $reading_month);
			}
		}
	}

	/**
	 * Get an array with daily/hourly usage breakdown for a period. All dates are in ISO date format (YYYY-MM-DD)
	 * If there's no data for the day, it attempts to interpolate from daily data, filling it with zeros if there's none.
	 */
	public function get_hourly_usage($date_from, $date_to = '', $export = false) {
		$usage = [];
		if(!$date_to) $date_to = $date_from;

		// Make sure dates are in the correct format
		$date_from = date('Y-m-d', strtotime($date_from));
		$date_to = date('Y-m-d', strtotime($date_to));

		// Limits maximum number of days
		// The main purpose of this is to avoid infinite loops
		$max_days = 365;

		// Initialise empty array
		$day = date('Y-m-d', strtotime('-1 day', strtotime($date_from)));
		$incomplete = 0;
		do {
			$day = date('Y-m-d', strtotime('+1 day', strtotime($day)));

			$usage[$day] = [
				'incomplete' => 1,
				'hours' => [],
				'used' => 0,
				'used_cost' => 0,
				'estimated' => 0
			];
			for($i = 0; $i < 24; $i++) {
				$usage[$day]['hours'][$i] = [
					'used' => 0,
					'used_cost' => 0,
					'estimated' => 0
				];
			}

			$max_days--;
			$incomplete++;
		} while($day != $date_to && $max_days > 0);

		// Build field list
		$fields = [];
		for($i = 0; $i < 24; $i++) {
			if($export) {
				$fields[] = "kwh_exported_total_hour_$i";
				$fields[] = "cost_exported_total_hour_$i";
			} else {
				$fields[] = "unit_imported_total_hour_$i";
				$fields[] = "cost_imported_total_hour_$i";
			}
		}
		$fields = implode(', ', $fields);

		// Get hourly readings for meter
		$hourly = App::sql()->query(
			"SELECT date, $fields
			FROM meter_usage_hourly_step_60_days
			WHERE meter_id = '$this->id' AND date BETWEEN '$date_from' AND '$date_to'
			ORDER BY date;
		", MySQL::QUERY_ASSOC, false) ?: [];

		foreach($hourly as $record) {
			if(isset($record['date'])) {
				$incomplete--;
				$usage[$record['date']]['incomplete'] = 0;
				for($i = 0; $i < 24; $i++) {
					if($export) {
						// Hourly
						$usage[$record['date']]['hours'][$i]['used'] += $record["kwh_exported_total_hour_$i"] ?: 0;
						$usage[$record['date']]['hours'][$i]['used_cost'] += $record["cost_exported_total_hour_$i"] ?: 0;

						// Daily totals
						$usage[$record['date']]['used'] += $record["kwh_exported_total_hour_$i"] ?: 0;
						$usage[$record['date']]['used_cost'] += $record["cost_exported_total_hour_$i"] ?: 0;
					} else {
						// Hourly
						$usage[$record['date']]['hours'][$i]['used'] += $record["unit_imported_total_hour_$i"] ?: 0;
						$usage[$record['date']]['hours'][$i]['used_cost'] += $record["cost_imported_total_hour_$i"] ?: 0;

						// Daily totals
						$usage[$record['date']]['used'] += $record["unit_imported_total_hour_$i"] ?: 0;
						$usage[$record['date']]['used_cost'] += $record["cost_imported_total_hour_$i"] ?: 0;
					}
				}
			}
		}

		if($incomplete === 0 || $export) return $usage;

		// Need to get estimated value
		// Get daily usage and divide it by 24
		// Cannot get cost this way

		$daily = $this->get_daily_usage($date_from, $date_to, true) ?: [];

		foreach($daily as $day => $record) {
			if(!isset($usage[$day])) continue;
			if(!$usage[$day]['incomplete']) continue;

			$avg = (($record['imported_rate_1'] ?: 0) + ($record['imported_rate_2'] ?: 0) + ($record['imported_rate_3'] ?: 0)) / 24;

			for($i = 0; $i < 24; $i++) {
				// Hourly
				$usage[$day]['hours'][$i]['used'] = $avg;
				$usage[$day]['hours'][$i]['estimated'] = 1;

				// Daily totals
				$usage[$day]['used'] += $avg;
				$usage[$day]['estimated'] = 1;
			}
		}

		return $usage;
	}

	/**
	 * Adds values of usage arrays together for multiple meters. All arrays passed must be from the same date.
	 * @param $array is an array of arrays returned by get_hourly_usage
	 */
	public static function get_total_hourly_usage($array) {
		if(!is_array($array) || count($array) == 0) return [];

		$result = array_shift($array);
		if(!is_array($result)) return [];

		foreach($array as $usage) {
			if(!is_array($usage)) continue;

			foreach($usage as $day => $data) {
				if(isset($result[$day])) {
					$r = &$result[$day];
					$r['incomplete'] = ($r['incomplete'] || $data['incomplete']) ? 1 : 0;
					$r['used'] += $data['used'];
					$r['estimated'] = ($r['estimated'] || $data['estimated']) ? 1 : 0;

					for($i = 0; $i < 24; $i++) {
						$r = &$result[$day]['hours'][$i];
						$d = $data['hours'][$i];
						$r['used'] += $d['used'];
						$r['used_cost'] += $d['used_cost'];
						$r['estimated'] = ($r['estimated'] || $d['estimated']) ? 1 : 0;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Gets an array with a daily usage breakdown. All dates are in ISO date format (YYYY-MM-DD)
	 */
	public function get_daily_usage($date_from, $date_to = '', $estimate = false) {
		$usage = [];echo '<p class="myText-noData">No data found for the Selected Date!</p>';exit;
		if(!$date_to) $date_to = $date_from;

		// Make sure dates are in the correct format
		$date_from = date('Y-m-d', strtotime($date_from));
		$date_to = date('Y-m-d', strtotime($date_to));

		// Limits maximum number of days
		// The main purpose of this is to avoid infinite loops
		$max_days = 365;

		// Initialise empty array
		$day = date('Y-m-d', strtotime('-1 day', strtotime($date_from)));
		do {
			$day = date('Y-m-d', strtotime('+1 day', strtotime($day)));

			$usage[$day] = [
				'id' => null,
				'imported_rate_1' => 0,
				'imported_rate_2' => 0,
				'imported_rate_3' => 0,
				'estimated' => 0,
				'incomplete' => 1
			];

			$max_days--;
		} while($day != $date_to && $max_days > 0);

		// Get all readings between the dates and populate usage data
		$readings = App::sql()->query("SELECT id, reading_date, imported_rate_1, imported_rate_2, imported_rate_3 FROM meter_reading WHERE meter_id = '$this->id' AND reading_date BETWEEN '$date_from' AND '$date_to' AND initial_reading = 0 ORDER BY reading_date;") ?: [];
		foreach($readings as $r) {
			if(isset($usage[$r->reading_date])) {
				$u = &$usage[$r->reading_date];
				$u['id'] = $r->id;
				$u['imported_rate_1'] = $r->imported_rate_1 ?: 0;
				$u['imported_rate_2'] = $r->imported_rate_2 ?: 0;
				$u['imported_rate_3'] = $r->imported_rate_3 ?: 0;
				$u['incomplete'] = 0;
			}
		}

		if(!$estimate) return $usage;

		// Estimate empty values
		// estimation works on a flipped array
		$usage = array_reverse($usage);

		$start_date = null;
		$length = $avg_1 = $avg_2 = $avg_3 = 0;

		$calculate_averages = function($reading_id) use (&$start_date, &$length, &$avg_1, &$avg_2, &$avg_3) {
			$start_date = null;
			$length = $avg_1 = $avg_2 = $avg_3 = 0;

			$end_reading = new MeterReading($reading_id);
			$start_reading = $end_reading->get_previous_reading();
			if($start_reading) {
				$start_date = $start_reading->info->reading_date;
				$length = (strtotime($end_reading->info->reading_date) - strtotime($start_date)) / 86400;
				if($length < 1) {
					$length = 0;
					$avg_1 = 0;
					$avg_2 = 0;
					$avg_3 = 0;
				} else {
					$avg_1 = ($end_reading->info->imported_rate_1 ?: 0) / $length;
					$avg_2 = ($end_reading->info->imported_rate_2 ?: 0) / $length;
					$avg_3 = ($end_reading->info->imported_rate_3 ?: 0) / $length;
				}
			}
		};

		// First, calculate the average use between the last reading of the period and the next reading (if any)
		$r = App::sql()->query_row("SELECT id FROM meter_reading WHERE meter_id = '$this->id' AND reading_date > '$date_to' AND initial_reading = 0 ORDER BY reading_date LIMIT 1;");
		if($r) $calculate_averages($r->id);

		$last_id = null;
		$needs_init = 0;
		foreach($usage as $date => &$day) {
			if($day['id'] == null) {
				// This day has no data, needs to be estimated
				if($needs_init && $last_id) {
					$calculate_averages($last_id);
					$needs_init = 0;
				}

				if($length) {
					$day['imported_rate_1'] = $avg_1;
					$day['imported_rate_2'] = $avg_2;
					$day['imported_rate_3'] = $avg_3;
					$day['estimated'] = 1;
					$day['incomplete'] = 0;
				}
			} else {
				$last_id = $day['id'];
				$needs_init = 1;
				$length = 0;
			}

			if($date == $start_date) $length = 0;
		}

		// Flip back
		$usage = array_reverse($usage);

		// Finally loop through, and kill the peaks
		$copy = $u1 = $u2 = $u3 = 0;
		foreach($usage as &$day) {
			if($day['estimated']) {
				$copy = 1;
				$u1 = $day['imported_rate_1'];
				$u2 = $day['imported_rate_2'];
				$u3 = $day['imported_rate_3'];
			} else if($day['id'] != null && $copy) {
				$copy = 0;
				$day['imported_rate_1'] = $u1;
				$day['imported_rate_2'] = $u2;
				$day['imported_rate_3'] = $u3;
			} else {
				$copy = 0;
			}
		}
		unset($day);

		return $usage;
	}

	/**
	 * Adds values of usage arrays together for multiple meters. All arrays passed must be within the same date range.
	 * @param $array is an array of arrays returned by get_daily_usage
	 */
	public static function get_total_daily_usage($array) {
		if(!is_array($array) || count($array) == 0) return [];

		$result = array_shift($array);
		if(!is_array($result)) return [];

		foreach($array as $usage) {
			if(!is_array($usage)) continue;

			foreach($usage as $day => $data) {
				if(isset($result[$day])) {
					$r = &$result[$day];
					$r['imported_rate_1'] += $data['imported_rate_1'];
					$r['imported_rate_2'] += $data['imported_rate_2'];
					$r['imported_rate_3'] += $data['imported_rate_3'];
					$r['estimated'] = ($r['estimated'] || $data['estimated']) ? 1 : 0;
					$r['incomplete'] = ($r['incomplete'] || $data['incomplete']) ? 1 : 0;
				}
			}
		}

		return $result;
	}
}
