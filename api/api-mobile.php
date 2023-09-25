<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_default_building() {
		$buildings = Permission::list_buildings([ 'with' => [Permission::ELECTRICITY_ENABLED, Permission::CONTROL_ENABLED], 'with_any' => true ], 'building.active = 1');
		if(!$buildings) return $this->error('No default building found for mobile interface.');

		return $this->success($buildings[0]->id);
	}

	// TODO: Security
	public function get_building() {
		$id = App::get('id', null, true);
		if(!$id) return $this->access_denied();

		$record = App::select('building', $id);
		if(!$record) return $this->error('Building not found.');

		return $this->success([
			'id' => $record['id'],
			'description' => $record['description'],
			'address' => $record['address'],
			'posttown' => $record['posttown'],
			'postcode' => $record['postcode']
		]);
	}

	public function list_buildings() {
		$buildings = Permission::list_buildings([ 'with' => [Permission::ELECTRICITY_ENABLED, Permission::CONTROL_ENABLED], 'with_any' => true ], 'building.active = 1');
		if(!$buildings) return $this->success([]);

		$building_list = array_map(function($b) { return $b->id; }, $buildings);
		$building_list = implode(', ', $building_list);

		$list = App::sql()->query(
			"SELECT
				id, description, address, posttown, postcode
			FROM building
			WHERE id IN ($building_list)
			ORDER BY description;
		", MySQL::QUERY_ASSOC);

		return $this->success($list);
	}

	public function list_modules() {
		$id = App::get('id', null, true);
		if(!$id) return $this->access_denied();

		$record = App::select('building', $id);
		if(!$record) return $this->error('Building not found.');

		$perm = Permission::get_building($id);

		$modules = [
			[Permission::ELECTRICITY_ENABLED, 'electricity'],
			[Permission::CONTROL_ENABLED, 'control']
		];

		$list = [];
		foreach($modules as $m) {
			if($perm->check($m[0])) $list[] = $m[1];
		}

		return $this->success($list);
	}

	public function electricity_info() {
		// TODO: Security
		$building_id = App::get('building_id', 0, true);
		$time_period = App::get('time_period', '', true);

		if(!$building_id) return $this->access_denied();
		if(!$time_period) $time_period = 'yesterday';

		$result = [];

		// Resolve selected time period

		$time_periods = [
			[ 'id' => 'yesterday', 'description' => 'Yesterday' ],
			[ 'id' => 'last_week', 'description' => 'Last Week' ],
			[ 'id' => 'last_month', 'description' => 'Last Month' ]
		];

		$days = App::sql()->query("SELECT day_no FROM building_kwh_step_60 WHERE building_id = '$building_id' ORDER BY day_no;") ?: [];
		$today = strtotime('today');
		foreach($days as $row) {
			if($row->day_no >= 2 && $row->day_no <= 60) {
				$description = date("D j F Y", strtotime("-$row->day_no day", $today));
				$time_periods[] = [ 'id' => "today_minus_$row->day_no", 'description' => $description ];
			}
		}

		$found = false;
		foreach($time_periods as $tp) {
			if($time_period === $tp['id']) $found = true;
		}
		if(!$time_periods) return $this->error('No data.');
		if(!$found) $time_period = $time_periods[0]['id'];

		// Resolve selected time period info
		switch($time_period) {
			case 'yesterday':
				$tp_info = [
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

			case 'last_week':
				$start = strtotime('last week');
				$end = strtotime('+6 days', $start);

				$tp_info = [
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

			case 'last_month':
				$tp_info = [
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

			default:
				$day = explode('_', $time_period)[2];

				$tp_info = [
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

		// Overview

		$data = App::sql()->query_row(
			"SELECT SUM($tp_info[yec_kwh_used]) AS kwh_used, SUM($tp_info[yec_cost]) AS cost
			FROM `$tp_info[yec_table]`
			WHERE category_id = ".Eticom::CATEGORY_BILLING." AND building_id = $building_id"
		);

		$kwh = $data ? $data->kwh_used : 0;
		$cost = $data ? $data->cost : 0;

		$billing_category = Eticom::CATEGORY_BILLING;
		$top_consumer = App::sql()->query_row(
			"SELECT
				MIN(IF(c.description IS NOT NULL, c.description, yec.cat_desc)) AS cat_desc,
				SUM(yec.$tp_info[yec_cost]) AS total_cost,
				yec.category_id
			FROM `$tp_info[yec_table]` AS yec
			LEFT JOIN category AS c ON c.id = yec.category_id
			WHERE
				yec.category_id <> '$billing_category'
				AND yec.$tp_info[yec_kwh_used] > 0
				AND yec.building_id = '$building_id'
				AND yec.category_id NOT IN (SELECT category_id FROM building_category_settings WHERE building_id = '$building_id' AND hide_from_electricity_widget = 1)
			GROUP BY category_id
			ORDER BY total_cost DESC
			LIMIT 1
		");

		// Your Electricity

		$billing_category = Eticom::CATEGORY_BILLING;
		$electricity_data = App::sql()->query(
			"SELECT
				MIN(IF(c.description IS NOT NULL, c.description, yec.cat_desc)) AS cat_desc,
				SUM(yec.$tp_info[yec_kwh_used]) AS kwh_used,
				SUM(yec.$tp_info[yec_cost]) AS cost,
				yec.category_id
			FROM `$tp_info[yec_table]` AS yec
			LEFT JOIN category AS c ON yec.category_id = c.id
			WHERE
				yec.category_id <> '$billing_category'
				AND yec.building_id = '$building_id'
				AND yec.category_id NOT IN (SELECT category_id FROM building_category_settings WHERE building_id = '$building_id' AND hide_from_electricity_widget = 1)
			GROUP BY category_id
			ORDER BY kwh_used DESC;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($electricity_data as &$row) {
			$circuit_data = App::sql()->query(
				"SELECT
					MIN(IF(ct.long_description IS NOT NULL, ct.long_description, cce.ct_long_description)) AS description,
					SUM(cce.$tp_info[cce_kwh_used]) AS kwh,
					SUM(cce.$tp_info[cce_cost]) AS cost
				FROM ct_category_eod AS cce
				LEFT JOIN ct ON ct.id = cce.ct_id
				WHERE cce.building_id = '$building_id' AND cce.category_id = '$row[category_id]'
				GROUP BY ct_id
				ORDER BY kwh DESC;
			", MySQL::QUERY_ASSOC) ?: [];

			$row['items'] = $circuit_data;
		}
		unset($row);


		return $this->success([
			'time_periods' => $time_periods,
			'selected_time_period' => $time_period,
			'overview_kwh' => $kwh,
			'overview_cost' => $cost,
			'overview_top_consumer' => $top_consumer ? $top_consumer->cat_desc : '',
			'your_electricity' => $electricity_data
		]);
	}

}
