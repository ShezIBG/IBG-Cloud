<?php

class Dashboard {

	const DASHBOARD_TYPE_HOME       = 'home';
	const DASHBOARD_TYPE_MAIN       = 'main';
	const DASHBOARD_TYPE_METERS     = 'meters';
	const DASHBOARD_TYPE_GAS        = 'gas';
	const DASHBOARD_TYPE_WATER      = 'water';
	const DASHBOARD_TYPE_RENEWABLES = 'renewables';

	const DASHBOARD_HOME_DEFAULT       = 4;
	const DASHBOARD_MAIN_DEFAULT       = 3;
	const DASHBOARD_METERS_DEFAULT     = 6;
	const DASHBOARD_GAS_DEFAULT        = 7;
	const DASHBOARD_WATER_DEFAULT      = 8;
	const DASHBOARD_RENEWABLES_DEFAULT = 9;

	const TIME_PERIOD_YESTERDAY  = 'yesterday';
	const TIME_PERIOD_LAST_WEEK  = 'last_week';
	const TIME_PERIOD_LAST_MONTH = 'last_month';
	const TIME_PERIOD_DAY        = 'today_minus_';

	public $id;
	public $info;
	public $type;

	public function __construct($id) {
		$this->id = $id;
		$this->info = App::sql()->query_row("SELECT * FROM dashboard WHERE id = '$id' AND active = 1");
		if ($this->info) $this->type = $this->info->type;
	}

	public function validate() {
		return !!$this->info;
	}

	/**
	 * Returns a list of valid day indices for the main electricity dashboard (in the range of 2 to 60).
	 * Days with no widget data will be excluded.
	 */
	public function get_valid_period_days($building_id) {
		$result = [];

		if($this->type == Dashboard::DASHBOARD_TYPE_MAIN) {
			$data = App::sql()->query("SELECT day_no FROM building_kwh_step_60 WHERE building_id = '$building_id' ORDER BY day_no;") ?: [];
			foreach($data as $row) {
				if($row->day_no >= 2 && $row->day_no <= 60) $result[] = $row->day_no;
			}
		} else if($this->type == Dashboard::DASHBOARD_TYPE_GAS) {
			$date_from = date('Y-m-d', strtotime('-60 days'));
			$date_to = date('Y-m-d', strtotime('-2 days'));

			$data = App::sql()->query(
				"SELECT DISTINCT mr.date FROM meter_usage_hourly_step_60_days AS mr
				JOIN meter AS m ON m.id = mr.meter_id
				JOIN area AS a ON a.id = m.area_id
				JOIN floor AS f ON f.id = a.floor_id
				WHERE f.building_id = '$building_id' AND m.meter_type = 'G' AND mr.date BETWEEN '$date_from' AND '$date_to';
			");

			$readings = array_map(function($r) { return $r->date; }, $data ?: []);

			for($i = 2; $i <= 60; $i++) {
				$day = date('Y-m-d', strtotime("-{$i} day"));
				if(in_array($day, $readings)) $result[] = $i;
			}
		} else if($this->type == Dashboard::DASHBOARD_TYPE_RENEWABLES) {
			$date_from = date('Y-m-d', strtotime('-60 days'));
			$date_to = date('Y-m-d', strtotime('-2 days'));

			$data = App::sql()->query(
				"SELECT DISTINCT mr.date FROM meter_usage_hourly_step_60_days AS mr
				JOIN meter AS m ON m.id = mr.meter_id
				JOIN area AS a ON a.id = m.area_id
				JOIN floor AS f ON f.id = a.floor_id
				WHERE f.building_id = '$building_id' AND m.meter_type = 'E' AND m.meter_direction = 'generation' AND mr.date BETWEEN '$date_from' AND '$date_to';
			");

			$readings = array_map(function($r) { return $r->date; }, $data ?: []);

			for($i = 2; $i <= 60; $i++) {
				$day = date('Y-m-d', strtotime("-{$i} day"));
				if(in_array($day, $readings)) $result[] = $i;
			}
		} else if($this->type == Dashboard::DASHBOARD_TYPE_WATER) {
			$date_from = date('Y-m-d', strtotime('-60 days'));
			$date_to = date('Y-m-d', strtotime('-2 days'));

			$data = App::sql()->query(
				"SELECT DISTINCT mr.date FROM meter_usage_hourly_step_60_days AS mr
				JOIN meter AS m ON m.id = mr.meter_id
				JOIN area AS a ON a.id = m.area_id
				JOIN floor AS f ON f.id = a.floor_id
				WHERE f.building_id = '$building_id' AND m.meter_type = 'W' AND mr.date BETWEEN '$date_from' AND '$date_to';
			");

			$readings = array_map(function($r) { return $r->date; }, $data ?: []);

			for($i = 2; $i <= 60; $i++) {
				$day = date('Y-m-d', strtotime("-{$i} day"));
				if(in_array($day, $readings)) $result[] = $i;
			}
		}

		return $result;
	}

	/**
	 * Gets the current time period setting of the dashboard, optionally validated against a building.
	 * If the setting is invalid, it will return a proper default.
	 * To avoid running multiple queries, you can pass in $vpd, which is the array returned by get_valid_period_days()
	 */
	public function get_time_period($building_id = 0, $vpd = null) {
		$tp = $this->info->time_period;
		$day = 0;
		$info = [];

		if(!$tp) {
			// Get time period from user's other dashboards (if any)
			$user = App::user();
			if($user) {
				$res = App::sql()->query_row("SELECT MIN(time_period) AS time_period FROM dashboard WHERE user_id = '$user->id';");
				if($res) $tp = $res->time_period;
			}
		}

		if(!$building_id) return (object)[ 'value' => $tp ];

		if($this->type == self::DASHBOARD_TYPE_MAIN) {
			if(!in_array($tp, [self::TIME_PERIOD_YESTERDAY, self::TIME_PERIOD_LAST_WEEK, self::TIME_PERIOD_LAST_MONTH])) {
				if(strpos($tp, self::TIME_PERIOD_DAY) === 0) {
					// Check if day is valid
					$day = explode('_', $tp)[2];
					$tp = self::TIME_PERIOD_DAY;
					if($day < 2 || $day > 60) {
						// Day out of range
						$tp = self::TIME_PERIOD_YESTERDAY;
					} else {
						if(!$vpd) $vpd = $this->get_valid_period_days($building_id);
						if(!in_array($day, $vpd)) {
							// No data for the day
							$tp = self::TIME_PERIOD_YESTERDAY;
						}
					}
				} else {
					// Didn't match anything, set to default value
					$tp = self::TIME_PERIOD_YESTERDAY;
				}
			}

			// Get extra information about the selected time period
			$info = [];

			switch($tp) {
				case self::TIME_PERIOD_YESTERDAY:
					$info = [
						'dbvalue' => $tp,
						'title' => 'Yesterday',
						'subtitle' => date('D d F Y', strtotime('yesterday')),
						'yec_table' => 'your_electricity_yesterday_category',
						'yec_kwh_used' => 'kwh_used',
						'yec_cost' => 'cost',
						'step_table' => 'building_kwh_step_60',
						'step_filter' => 'day_no = 1',
						'cce_kwh_used' => 'kwh_used_yesterday',
						'cce_cost' => 'cost_of_yesterday',
						'cce_kwh_used_out' => 'kwh_used_yesterday_out_of_hours',
						'cce_cost_out' => 'cost_of_yesterday_out_of_hours',
						'ahc_table' => 'after_hours_yesterday_category',
						'ahc_kwh_used_out' => 'kwh_used_out_of_hours',
						'ahc_kwh_used_in' => 'kwh_used_in_hours',
						'ahc_cost_out' => 'cost_out_of_hours',
						'ahc_cost_in' => 'cost_in_hours',
					];
					break;

				case self::TIME_PERIOD_LAST_WEEK:
					$start = strtotime('last week');
					$end = strtotime('+6 days', $start);

					$info = [
						'dbvalue' => $tp,
						'title' => 'Last week',
						'subtitle' => date('d F Y', $start).' - '.date('d F Y', $end),
						'yec_table' => 'your_electricity_last_week_category',
						'yec_kwh_used' => 'kwh_used',
						'yec_cost' => 'cost',
						'step_table' => 'building_kwh_step_last_week',
						'step_filter' => '',
						'cce_kwh_used' => 'kwh_used_last_week',
						'cce_cost' => 'cost_of_last_week',
						'cce_kwh_used_out' => 'kwh_used_last_week_out_of_hours',
						'cce_cost_out' => 'cost_of_last_week_out_of_hours',
						'ahc_table' => 'after_hours_last_week_category',
						'ahc_kwh_used_out' => 'kwh_used_out_of_hours',
						'ahc_kwh_used_in' => 'kwh_used_in_hours',
						'ahc_cost_out' => 'cost_out_of_hours',
						'ahc_cost_in' => 'cost_in_hours',
					];
					break;

				case self::TIME_PERIOD_LAST_MONTH:
					$info = [
						'dbvalue' => $tp,
						'title' => 'Last month',
						'subtitle' => date('F Y', strtotime('-1 month')),
						'yec_table' => 'your_electricity_last_month_category',
						'yec_kwh_used' => 'kwh_used',
						'yec_cost' => 'cost',
						'step_table' => 'building_kwh_step_last_month',
						'step_filter' => '',
						'cce_kwh_used' => 'kwh_used_last_month',
						'cce_cost' => 'cost_of_last_month',
						'cce_kwh_used_out' => 'kwh_used_last_month_out_of_hours',
						'cce_cost_out' => 'cost_of_last_month_out_of_hours',
						'ahc_table' => 'after_hours_last_month_category',
						'ahc_kwh_used_out' => 'kwh_used_out_of_hours',
						'ahc_kwh_used_in' => 'kwh_used_in_hours',
						'ahc_cost_out' => 'cost_out_of_hours',
						'ahc_cost_in' => 'cost_in_hours',
					];
					break;

				case self::TIME_PERIOD_DAY:
					$info = [
						'dbvalue' => $tp.$day,
						'title' => date('d F Y', strtotime("-{$day} day")),
						'subtitle' => date('l', strtotime("-{$day} day")),
						'day' => $day,
						'yec_table' => 'your_electricity_yesterday_category',
						'yec_kwh_used' => 'kwh_used_today_minus_'.$day,
						'yec_cost' => 'cost_of_today_minus_'.$day,
						'step_table' => 'building_kwh_step_60',
						'step_filter' => 'day_no = '.$day,
						'cce_kwh_used' => 'kwh_used_today_minus_'.$day,
						'cce_cost' => 'cost_of_today_minus_'.$day,
						'cce_kwh_used_out' => 'kwh_used_today_minus_'.$day.'_out_of_hours',
						'cce_cost_out' => 'cost_of_today_minus_'.$day.'_out_of_hours',
						'ahc_table' => 'after_hours_yesterday_category',
						'ahc_kwh_used_out' => 'kwh_used_today_minus_'.$day.'_out_of_hours',
						'ahc_kwh_used_in' => 'kwh_used_today_minus_'.$day.'_in_hours',
						'ahc_cost_out' => 'cost_of_today_minus_'.$day.'_out_of_hours',
						'ahc_cost_in' => 'cost_of_today_minus_'.$day.'_in_hours',
					];
					break;
			}
		} else if($this->type == self::DASHBOARD_TYPE_GAS || $this->type == self::DASHBOARD_TYPE_WATER || $this->type === self::DASHBOARD_TYPE_RENEWABLES) {
			if(!in_array($tp, [self::TIME_PERIOD_YESTERDAY, self::TIME_PERIOD_LAST_WEEK, self::TIME_PERIOD_LAST_MONTH])) {
				if(strpos($tp, self::TIME_PERIOD_DAY) === 0) {
					// Check if day is valid
					$day = explode('_', $tp)[2];
					$tp = self::TIME_PERIOD_DAY;
					if($day < 2 || $day > 60) {
						// Day out of range
						$tp = self::TIME_PERIOD_YESTERDAY;
					} else {
						if(!$vpd) $vpd = $this->get_valid_period_days($building_id);
						if(!in_array($day, $vpd)) {
							// No data for the day
							$tp = self::TIME_PERIOD_YESTERDAY;
						}
					}
				} else {
					// Didn't match anything, set to default value
					$tp = self::TIME_PERIOD_YESTERDAY;
				}
			}

			// Get extra information about the selected time period
			$info = [];

			switch($tp) {
				case self::TIME_PERIOD_YESTERDAY:
					$info = [
						'dbvalue' => $tp,
						'title' => 'Yesterday',
						'subtitle' => date('D d F Y', strtotime('yesterday')),
						'date_from' => date('Y-m-d', strtotime('yesterday')),
						'date_to' => date('Y-m-d', strtotime('yesterday')),
						'period_length' => 1
					];
					break;

				case self::TIME_PERIOD_LAST_WEEK:
					$end = strtotime('yesterday');
					$start = strtotime('-6 days', $end);

					$info = [
						'dbvalue' => $tp,
						'title' => 'Last 7 days',
						'subtitle' => date('d F Y', $start).' - '.date('d F Y', $end),
						'date_from' => date('Y-m-d', $start),
						'date_to' => date('Y-m-d', $end),
						'period_length' => 7
					];
					break;

				case self::TIME_PERIOD_LAST_MONTH:
					$end = strtotime('yesterday');
					$start = strtotime('-29 days', $end);

					$info = [
						'dbvalue' => $tp,
						'title' => 'Last 30 days',
						'subtitle' => date('d F Y', $start).' - '.date('d F Y', $end),
						'date_from' => date('Y-m-d', $start),
						'date_to' => date('Y-m-d', $end),
						'period_length' => 30
					];
					break;

				case self::TIME_PERIOD_DAY:
					$start = strtotime("-{$day} day");
					$end = $start;

					$info = [
						'dbvalue' => $tp.$day,
						'title' => date('d F Y', $start),
						'subtitle' => date('l', $start),
						'day' => $day,
						'date_from' => date('Y-m-d', $start),
						'date_to' => date('Y-m-d', $end),
						'period_length' => 1
					];
					break;
			}
		}

		return (object) array_merge([ 'value' => $tp ], $info);
	}

	public function get_widget_grid($widget_id, $is_mobile = false) {
		$grid_info = json_decode(html_entity_decode($is_mobile ? $this->info->grid_mobile : $this->info->grid));
		if ($grid_info && isset($grid_info->{$widget_id})) return $grid_info->{$widget_id};
		return false;
	}

	public static function has_responsive_height($db_type) {
		return $db_type == self::DASHBOARD_TYPE_MAIN || $db_type == self::DASHBOARD_TYPE_HOME || $db_type == self::DASHBOARD_TYPE_METERS || $db_type == self::DASHBOARD_TYPE_GAS || $db_type == self::DASHBOARD_TYPE_WATER || $db_type == self::DASHBOARD_TYPE_RENEWABLES;
	}

	public function get_widgets($filters = []) {
		$dash_type = $this->type;
		if(BRANDING === 'elanet' && $dash_type === 'home') $dash_type = 'home-elanet';

		return App::sql()->query(
			"SELECT
				concat('def-', $this->id, '-', widget.id) as id,
				widget.id AS widget_id,
				widget.name,
				widget.resource_path,
				widget.gs_width,
				widget.gs_height
			FROM widget
			WHERE db_type = '$dash_type' AND widget.new_active = 1 ".MySQL::build_filter_string($filters));
	}

	public function get_widget_info($widget, $is_mobile = false) {
		if (!$grid_info = $this->get_widget_grid($widget->id, $is_mobile)) {
			$grid_info = (object)[
				'width'      => $widget->gs_width,
				'height'     => $widget->gs_height
				
			];
		}

		$markup_path = APP_PATH.$widget->resource_path.'/markup.php';

		$widget_info = new stdClass;
		$widget_info->markup_path = $markup_path;
		$widget_info->name = $widget->name;
		$widget_info->id = $widget->id;
		$widget_info->widget_id = $widget->widget_id;
		$widget_info->ui_id = $widget->name.'-'.$widget->widget_id.'-'.$widget->id;
		$widget_info->grid = $grid_info;

		return $widget_info;
	}

	public function update($fields) {
		if (isset($fields['title'])) $fields['name'] = App::slugify($fields['title']);
		$fields = App::escape($fields);

		$set_fields = array_map(function($field, $value) {
			if (is_null($value)) $value = 'null';
			switch (strtolower($value)) {
				case 'null':
					$value = 'NULL';
					break;

				case 'now()':
					$value = 'NOW()';
					break;

				default:
					$value = "'$value'";
					break;
			}
			return "$field = $value";
		}, array_keys($fields), $fields);

		$result = App::sql()->update("UPDATE dashboard SET ".implode(', ', $set_fields)." WHERE id = $this->id");
		$this->__construct($this->id);
		return $result;
	}

	public function is_default() {
		return $this->id == self::get_default_id($this->type);
	}

	public static function get_default($type = self::DASHBOARD_TYPE_MAIN) {
		return new self(self::get_default_id($type));
	}

	public static function get_default_id($type = self::DASHBOARD_TYPE_MAIN) {
		$id = false;
		switch ($type) {
			case self::DASHBOARD_TYPE_HOME:
				$id = self::DASHBOARD_HOME_DEFAULT;
				break;
			case self::DASHBOARD_TYPE_MAIN:
				$id = self::DASHBOARD_MAIN_DEFAULT;
				break;
			case self::DASHBOARD_TYPE_METERS:
				$id = self::DASHBOARD_METERS_DEFAULT;
				break;
			case self::DASHBOARD_TYPE_GAS:
				$id = self::DASHBOARD_GAS_DEFAULT;
				break;
			case self::DASHBOARD_TYPE_WATER:
				$id = self::DASHBOARD_WATER_DEFAULT;
				break;
			case self::DASHBOARD_TYPE_RENEWABLES:
				$id = self::DASHBOARD_RENEWABLES_DEFAULT;
				break;
		}

		return $id;
	}

	public static function add($fields) {
		$fields = App::keep($fields, ['title', 'description', 'user_id', 'time_period', 'type']);
		$fields = App::ensure($fields, ['user_id', 'time_period'], null);
		$fields = App::ensure($fields, ['type'], self::DASHBOARD_TYPE_MAIN);

		$fields['name'] = App::slugify($fields['title']);

		$result = App::insert('dashboard', $fields);

		return $result ? new self($result) : false;
	}

}
