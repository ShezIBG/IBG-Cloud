<?php

class MeterReading {
	public $id, $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM meter_reading WHERE id = '$id'");
	}

	public function validate() {
		return !!$this->info;
	}

	public function get_previous_reading() {
		if($this->info->initial_reading) {
			$r = App::sql()->query_row("SELECT id FROM meter_reading WHERE meter_id = '{$this->info->meter_id}' AND reading_date <= '{$this->info->reading_date}' AND id <> '{$this->id}' ORDER BY reading_date DESC LIMIT 1");
		} else {
			$r = App::sql()->query_row("SELECT id FROM meter_reading WHERE meter_id = '{$this->info->meter_id}' AND reading_date < '{$this->info->reading_date}' ORDER BY reading_date DESC LIMIT 1");
		}

		return $r ? new MeterReading($r->id) : null;
	}

	public function get_next_reading() {
		if(!$this->info->initial_reading) {
			$r = App::sql()->query_row("SELECT id FROM meter_reading WHERE meter_id = '{$this->info->meter_id}' AND reading_date >= '{$this->info->reading_date}' AND id <> '{$this->id}' ORDER BY reading_date LIMIT 1");
		} else {
			$r = App::sql()->query_row("SELECT id FROM meter_reading WHERE meter_id = '{$this->info->meter_id}' AND reading_date > '{$this->info->reading_date}' ORDER BY reading_date LIMIT 1");
		}

		return $r ? new MeterReading($r->id) : null;
	}

	/**
	 * Used to check which period the reading falls under. Returns [year, month].
	 */
	public function get_year_and_month() {
		$year = date('Y', strtotime($this->info->reading_date)) + 0;
		$month = date('m', strtotime($this->info->reading_date)) + 0;
		return [$year, $month];
	}

}
