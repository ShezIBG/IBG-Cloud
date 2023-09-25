<?php

class MeterPeriod {
	public $id, $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM meter_period WHERE id = '$id'");
	}

	public function validate() {
		return !!$this->info;
	}

	public function get_first_reading() {
		return $this->info->first_reading_id ? new MeterReading($this->info->first_reading_id) : null;
	}

	public function get_last_reading() {
		return $this->info->last_reading_id ? new MeterReading($this->info->last_reading_id) : null;
	}

	public function get_total_cost() {
		$meter_id = $this->info->meter_id;
		$first_reading_id = $this->info->first_reading_id;
		$last_reading_id = $this->info->last_reading_id;

		if(!$meter_id || !$first_reading_id || !$last_reading_id) return 0;

		$r = App::sql()->query_row(
			"SELECT SUM(cost_total_total) AS total
			FROM meter_reading
			WHERE meter_id = '$meter_id' AND id BETWEEN '$first_reading_id' AND '$last_reading_id' AND id <> '$first_reading_id';
		", MySQL::QUERY_ASSOC);

		return $r ? ($r['total'] ?: 0) : 0;
	}

	public static function get_period($meter_id, $year, $month) {
		$meter_id = App::escape($meter_id);
		$period = App::sql()->query_row("SELECT id FROM meter_period WHERE meter_id = '$meter_id' AND year = '$year' AND month = '$month';");
		return $period ? new MeterPeriod($period->id) : null;
	}

	/**
	 * This function is for internal use of the class only. Deletes a period in case recalculation fails.
	 * The above can happen if a past meter reading is deleted after registering the period.
	 */
	private static function delete_period($meter_id, $year, $month) {
		App::sql()->delete("DELETE FROM meter_period WHERE meter_id = '$meter_id' AND year = '$year' AND month = '$month';");
	}

	/**
	 * Recalculates usage data for the given period.
	 */
	public static function recalculate_period($meter_id, $year, $month) {
		$meter_id = App::escape($meter_id);
		$meter = new Meter($meter_id);
		if(!$meter->validate()) return;

		// Get first and last days of the month

		$first_day = strtotime(sprintf('%02d-%02d-01', $year, $month));
		$last_day = strtotime('+1 month -1 day', $first_day);

		$first_day_str = date('Y-m-d', $first_day);
		$last_day_str = date('Y-m-d', $last_day);

		// Find the first and last readings within the period

		$result = App::sql()->query_row("SELECT id FROM meter_reading WHERE meter_id = '$meter_id' AND reading_date BETWEEN '$first_day_str' AND '$last_day_str' ORDER BY reading_date, initial_reading LIMIT 1");
		if(!$result) {
			self::delete_period($meter_id, $year, $month);
			return;
		}

		$first_reading = new MeterReading($result->id);

		$result = App::sql()->query_row("SELECT id FROM meter_reading WHERE meter_id = '$meter_id' AND reading_date BETWEEN '$first_day_str' AND '$last_day_str' ORDER BY reading_date DESC, initial_reading DESC LIMIT 1");
		if(!$result) {
			self::delete_period($meter_id, $year, $month);
			return;
		}

		$last_reading = new MeterReading($result->id);

		// If there is a reading after the last reading, make that one the last reading
		// A period lasts from the first reading of the month to the first reading AFTER the end of the month
		$after = $last_reading->get_next_reading();
		if($after) $last_reading = $after;

		// Stop processing if the first/last readings we have found are the same
		if($first_reading->id == $last_reading->id) {
			self::delete_period($meter_id, $year, $month);
			return;
		}

		// Calculate total usage by summing the reading usage values of the period.
		// The first reading's value is NOT added to the total, as that belongs to the previous period
		$totals = App::sql()->query_row(
			"SELECT
				SUM(imported_rate_1) AS total_1,
				SUM(imported_rate_2) AS total_2,
				SUM(imported_rate_3) AS total_3
			FROM meter_reading
			WHERE meter_id = '$meter_id' AND reading_date > '{$first_reading->info->reading_date}' AND reading_date <= '{$last_reading->info->reading_date}';
		");

		if(!$totals) {
			self::delete_period($meter_id, $year, $month);
			return;
		}

		$total_1 = $totals->total_1 ?: 0;
		$total_2 = $totals->total_2 ?: 0;
		$total_3 = $totals->total_3 ?: 0;

		$complete = $first_reading->get_year_and_month() == $last_reading->get_year_and_month() ? 0 : 1;

		// Got everything. Check if we already have a record for the period, then insert/update.

		$period = self::get_period($meter_id, $year, $month);
		if($period) {
			// Update
			App::sql()->update(
				"UPDATE meter_period SET
					usage_1 = '$total_1',
					usage_2 = '$total_2',
					usage_3 = '$total_3',
					first_reading_id = '$first_reading->id',
					last_reading_id = '$last_reading->id',
					complete = '$complete'
				WHERE id = '$period->id';
			");
		} else {
			// Insert
			App::sql()->insert(
				"INSERT INTO meter_period (building_id, meter_id, year, month, usage_1, usage_2, usage_3, first_reading_id, last_reading_id, complete)
				VALUES ('{$meter->get_building_id()}', '$meter_id', '$year', '$month', '$total_1', '$total_2', '$total_3', '{$first_reading->id}', '{$last_reading->id}', '$complete');
			");
		}
	}

	/**
	 * Returns [$year, $month] of the last period matching the filter parameters,
	 */
	public static function get_last_period_date($building_id, $meter_type = '', $complete = false, $generated = false) {
		$complete = $complete ? 1 : 0;

		$q = "SELECT MAX(year * 100 + month) AS `year_month` FROM meter_period AS mp
			JOIN meter AS m ON m.id = mp.meter_id
			WHERE mp.building_id = '$building_id'";

		if($meter_type) $q .= " AND m.meter_type = '$meter_type'";
		$q .= $generated ? " AND m.meter_direction = 'generation'" : " AND m.meter_direction <> 'generation'";
		if($complete) $q .= " AND complete = '$complete'";

		$result = App::sql()->query_row($q);

		if($result && $result->year_month) {
			$ym = $result->year_month;
			$year = substr($ym, 0, 4) + 0;
			$month = substr($ym, 4, 2) + 0;
			return [$year, $month];
		}

		return null;
	}

}
