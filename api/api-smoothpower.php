<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_building_info() {
		$building_id = App::get('id', 0, true);
		$user = App::user();

		if(!$building_id) {
			$building = $user->get_default_building(Permission::SMOOTHPOWER_ENABLED);
			if(!$building) return $this->access_denied();
		} else {
			$perm = Permission::get_building($building_id);
			if(!$perm->check(Permission::SMOOTHPOWER_ENABLED)) return $this->access_denied();

			App::update('userdb', $user->id, [
				'default_building_id' => $building_id
			]);

			$building = new Building($building_id);
		}

		$buildings = Permission::list_buildings([ 'with' => Permission::SMOOTHPOWER_ENABLED ]) ?: [];

		$smoothpower = App::sql()->query_row("SELECT serial, monitoring_server_id FROM smoothpower WHERE building_id = '$building->id';");
		if(!$smoothpower) return $this->error('SmoothPower unit not found.');
		if(!$smoothpower->monitoring_server_id) return $this->error('Monitoring server not found.');
		$msql = App::monitoring_sql($smoothpower->monitoring_server_id);
		if(!$msql) return $this->error('Unable to connect to monitoring database.');
		$smoothpower = $smoothpower->serial;

		$minmax = $msql->query_row(
			"SELECT
				DATE(MIN(datetime)) AS min,
				DATE(MAX(datetime)) AS max
			FROM abb_$smoothpower;
		", MySQL::QUERY_ASSOC);

		$months = $msql->query(
			"SELECT DISTINCT YEAR(datetime) AS year, MONTH(datetime) AS month
			FROM abb_$smoothpower;
		", MySQL::QUERY_ASSOC) ?: [];

		$years = $msql->query(
			"SELECT DISTINCT YEAR(datetime) AS year
			FROM abb_$smoothpower;
		", MySQL::QUERY_ASSOC) ?: [];

		return $this->success([
			'id' => $building->id,
			'description' => $building->info->description,
			'min_date' => $minmax['min'],
			'max_date' => $minmax['max'],
			'years' => array_map(function($item) {
				$year = "$item[year]";
				return [
					'id' => "$year-01-01",
					'description' => $year
				];
			}, $years),
			'months' => array_map(function($item) {
				$year = "$item[year]";
				$month = $item['month'];
				$monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
				$monthPad = str_pad("$month", 2, '0', STR_PAD_LEFT);
				return [
					'id' => "$year-$monthPad-01",
					'description' => "$monthNames[$month] $year"
				];
			}, $months),
			'buildings' => array_map(function($b) {
				return [
					'title' => $b->description,
					'data' => [ 'id' => $b->id ]
				];
			}, $buildings)
		]);
	}

	public function get_usage() {
		$data = App::json();
		if(!isset($data['building_id']) || !isset($data['type'])) return $this->access_denied();

		$building_id = App::escape($data['building_id']);
		$type = $data['type'];
		if(isset($data['date']) && $data['date']) {
			$date = explode(' ', $data['date'])[0];
		} else {
			$date = date('Y-m-d', strtotime('-1 day'));
		}

		$year = date('Y', strtotime($date));
		$month = date('m', strtotime($date));
		$day = date('d', strtotime($date));

		$perm = Permission::get_building($building_id);
		if(!$perm->check(Permission::SMOOTHPOWER_ENABLED)) return $this->access_denied();

		$smoothpower = App::sql()->query_row("SELECT serial, monitoring_server_id FROM smoothpower WHERE building_id = '$building_id';");
		if(!$smoothpower) return $this->error('SmoothPower unit not found.');
		if(!$smoothpower->monitoring_server_id) return $this->error('Monitoring server not found.');
		$msql = App::monitoring_sql($smoothpower->monitoring_server_id);
		if(!$msql) return $this->error('Unable to connect to monitoring database.');
		$smoothpower = $smoothpower->serial;

		$minmax = $msql->query_row(
			"SELECT
				DATE(MIN(datetime)) AS min,
				DATE(MAX(datetime)) AS max
			FROM abb_$smoothpower;
		", MySQL::QUERY_ASSOC);

		if(!$minmax || !$minmax['min']) return $this->error('No readings found.');

		if(strtotime($date) < strtotime($minmax['min'])) $date = $minmax['min'];
		if(strtotime($date) > strtotime($minmax['max'])) $date = $minmax['max'];

		// Check unit status
		$r = null;
		$r = $msql->query("SELECT datetime FROM abb_$smoothpower WHERE datetime > DATE_SUB(NOW(), INTERVAL 30 MINUTE);");
		$status = !!$r;

		// Check surge status
		$surge_status = false;
		try {
			$r = $msql->query_row("SELECT * FROM smoothpower.$smoothpower ORDER BY last_changed DESC LIMIT 1");
			if($r) $surge_status = !$r->surge_protect_fail;
		} catch(Exception $ex) { }

		$today = date('Y-m-d');
		$now_year = (int)date('Y');
		$now_month = (int)date('m');
		$now_day = (int)date('d');
		$now_hour = (int)date('H');

		$total = 0;
		$max = 0;
		$avg = 0;

		$total_label = 'Total kWh';
		$max_label = 'Max kWh';
		$avg_label = 'Avg kWh';

		$used = [];
		$used_from = '';
		$used_to = '';

		$compare = [];
		$compare_from = '';
		$compare_to = '';

		$max_scale = 0;
		$min_scale = PHP_INT_MAX;

		$temp_max_scale = 0;
		$temp_min_scale = 100;

		$subselect = "(
			SELECT

				list.current_datetime, list.current_reading / 100, list.next_datetime, next.kwh_total / 100 AS next_reading,
				IF(COALESCE(list.current_reading, 0) < COALESCE(next.kwh_total, 0), (COALESCE(next.kwh_total, 0) - COALESCE(list.current_reading, 0)) / 100, 0) AS used

			FROM
				(
					SELECT @next AS next_datetime, @next := abb.datetime AS current_datetime, abb.kwh_total AS current_reading, abb.abb_serial_no
					FROM (SELECT @next := null) AS init, abb_$smoothpower AS abb
					WHERE abb.meter_id LIKE '%.1'
					ORDER BY abb.datetime DESC
				) AS list

			JOIN abb_$smoothpower AS next ON next.meter_id LIKE '%.1' AND next.datetime = list.next_datetime AND next.abb_serial_no = list.abb_serial_no
		)";

		switch($type) {
			case 'D':
				$used_from = $date;
				$used_to = $used_from;

				$compare_from = date('Y-m-d', strtotime('-1 week', strtotime($date)));
				$compare_to = $compare_from;

				$totals = $msql->query_row(
					"SELECT
						SUM(used) AS total,
						MAX(used) AS max,
						AVG(IF(year = '$now_year' AND month = '$now_month' AND day = '$now_day' AND hour = '$now_hour', NULL, used)) AS avg
					FROM (
						SELECT

							YEAR(current_datetime) AS year,
							MONTH(current_datetime) AS month,
							DAY(current_datetime) AS day,
							HOUR(current_datetime) AS hour,
							SUM(used) AS used,
							COUNT(used) AS data_points

						FROM $subselect AS r

						WHERE current_datetime BETWEEN '$used_from 00:00:00' AND '$used_to 23:59:59'
						GROUP BY year, month, day, hour
					) AS t;
				", MySQL::QUERY_ASSOC);

				if($totals) {
					$total = $totals['total'];
					$max = $totals['max'];
					$avg = $totals['avg'];
				}

				$used = $msql->query(
					"SELECT

						YEAR(current_datetime) AS year,
						MONTH(current_datetime) AS month,
						DAY(current_datetime) AS day,
						DATE(current_datetime) AS date,
						HOUR(current_datetime) AS hour,
						SUM(used) AS used,
						COUNT(used) AS data_points

					FROM $subselect AS r

					WHERE current_datetime BETWEEN '$used_from 00:00:00' AND '$used_to 23:59:59'
					GROUP BY year, month, day, date, hour
					ORDER BY year, month, day, hour;
				", MySQL::QUERY_ASSOC) ?: [];

				$w = new WeatherService($building_id);
				$wp = $w->get_hourly_weather_plot($used_from, $used_to);
				foreach($used as &$u) {
					if(isset($wp[$u['date']][$u['hour']])) {
						$t = $wp[$u['date']][$u['hour']]->temperature;
						$u['min_temp'] = $t;
						$u['max_temp'] = $t;
					} else {
						$u['min_temp'] = 0;
						$u['max_temp'] = 0;
					}

					if($u['used'] > $max_scale) $max_scale = $u['used'];
					if($u['used'] < $min_scale) $min_scale = $u['used'];
					if($u['min_temp'] > $temp_max_scale) $temp_max_scale = $u['min_temp'];
					if($u['min_temp'] < $temp_min_scale) $temp_min_scale = $u['min_temp'];
					if($u['max_temp'] > $temp_max_scale) $temp_max_scale = $u['max_temp'];
					if($u['max_temp'] < $temp_min_scale) $temp_min_scale = $u['max_temp'];
				}
				unset($u);

				$compare = $msql->query(
					"SELECT

						YEAR(current_datetime) AS year,
						MONTH(current_datetime) AS month,
						DAY(current_datetime) AS day,
						DATE(current_datetime) AS date,
						HOUR(current_datetime) AS hour,
						SUM(used) AS used,
						COUNT(used) AS data_points

					FROM $subselect AS r

					WHERE current_datetime BETWEEN '$compare_from 00:00:00' AND '$compare_to 23:59:59'
					GROUP BY year, month, day, date, hour
					ORDER BY year, month, day, hour;
				", MySQL::QUERY_ASSOC) ?: [];

				$w = new WeatherService($building_id);
				$wp = $w->get_hourly_weather_plot($compare_from, $compare_to);
				foreach($compare as &$u) {
					if(isset($wp[$u['date']][$u['hour']])) {
						$t = $wp[$u['date']][$u['hour']]->temperature;
						$u['min_temp'] = $t;
						$u['max_temp'] = $t;
					} else {
						$u['min_temp'] = 0;
						$u['max_temp'] = 0;
					}

					if($u['used'] > $max_scale) $max_scale = $u['used'];
					if($u['used'] < $min_scale) $min_scale = $u['used'];
					if($u['min_temp'] > $temp_max_scale) $temp_max_scale = $u['min_temp'];
					if($u['min_temp'] < $temp_min_scale) $temp_min_scale = $u['min_temp'];
					if($u['max_temp'] > $temp_max_scale) $temp_max_scale = $u['max_temp'];
					if($u['max_temp'] < $temp_min_scale) $temp_min_scale = $u['max_temp'];
				}
				unset($u);

				break;

			case 'M':
			case '7':
			case '28':

				switch($type) {
					case 'M':
						$used_from = date('Y-m-01', strtotime($date));
						$used_to = date('Y-m-d', strtotime('-1 day', strtotime('+1 month', strtotime($used_from))));

						$compare_from = date('Y-m-d', strtotime('-1 month', strtotime($used_from)));
						$compare_to = date('Y-m-d', strtotime('-1 day', strtotime('+1 month', strtotime($compare_from))));
						break;

					case '7':
						$used_to = $date;
						$used_from = date('Y-m-d', strtotime('-6 day', strtotime($used_to)));

						$compare_from = date('Y-m-d', strtotime('-7 day', strtotime($used_from)));
						$compare_to = date('Y-m-d', strtotime('-7 day', strtotime($used_to)));
						break;

					case '28':
						$used_to = $date;
						$used_from = date('Y-m-d', strtotime('-27 day', strtotime($used_to)));

						$compare_from = date('Y-m-d', strtotime('-28 day', strtotime($used_from)));
						$compare_to = date('Y-m-d', strtotime('-28 day', strtotime($used_to)));
						break;
				}

				$totals = $msql->query_row(
					"SELECT
						SUM(used) AS total,
						MAX(used) AS max,
						AVG(IF(year = '$now_year' AND month = '$now_month' AND day = '$now_day', NULL, used)) AS avg
					FROM (
						SELECT

							YEAR(current_datetime) AS year,
							MONTH(current_datetime) AS month,
							DAY(current_datetime) AS day,
							SUM(used) AS used,
							COUNT(used) AS data_points

						FROM $subselect AS r

						WHERE current_datetime BETWEEN '$used_from 00:00:00' AND '$used_to 23:59:59'
						GROUP BY year, month, day
					) AS t;
				", MySQL::QUERY_ASSOC);

				if($totals) {
					$total = $totals['total'];
					$max = $totals['max'];
					$avg = $totals['avg'];
				}

				$used = $msql->query(
					"SELECT

						YEAR(current_datetime) AS year,
						MONTH(current_datetime) AS month,
						DAY(current_datetime) AS day,
						DATE(current_datetime) AS date,
						SUM(used) AS used,
						COUNT(used) AS data_points

					FROM $subselect AS r

					WHERE current_datetime BETWEEN '$used_from 00:00:00' AND '$used_to 23:59:59'
					GROUP BY year, month, day, date
					ORDER BY year, month, day;
				", MySQL::QUERY_ASSOC) ?: [];

				$w = new WeatherService($building_id);
				$wp = $w->get_daily_weather_plot($used_from, $used_to);
				foreach($used as &$u) {
					if(isset($wp[$u['date']])) {
						$t = $wp[$u['date']];
						$u['min_temp'] = $t->temperatureMin;
						$u['max_temp'] = $t->temperatureMax;
					} else {
						$u['min_temp'] = 0;
						$u['max_temp'] = 0;
					}

					if($u['used'] > $max_scale) $max_scale = $u['used'];
					if($u['used'] < $min_scale) $min_scale = $u['used'];
					if($u['min_temp'] > $temp_max_scale) $temp_max_scale = $u['min_temp'];
					if($u['min_temp'] < $temp_min_scale) $temp_min_scale = $u['min_temp'];
					if($u['max_temp'] > $temp_max_scale) $temp_max_scale = $u['max_temp'];
					if($u['max_temp'] < $temp_min_scale) $temp_min_scale = $u['max_temp'];
				}
				unset($u);

				$compare = $msql->query(
					"SELECT

						YEAR(current_datetime) AS year,
						MONTH(current_datetime) AS month,
						DAY(current_datetime) AS day,
						DATE(current_datetime) AS date,
						SUM(used) AS used,
						COUNT(used) AS data_points

					FROM $subselect AS r

					WHERE current_datetime BETWEEN '$compare_from 00:00:00' AND '$compare_to 23:59:59'
					GROUP BY year, month, day, date
					ORDER BY year, month, day;
				", MySQL::QUERY_ASSOC) ?: [];

				$w = new WeatherService($building_id);
				$wp = $w->get_daily_weather_plot($compare_from, $compare_to);
				foreach($compare as &$u) {
					if(isset($wp[$u['date']])) {
						$t = $wp[$u['date']];
						$u['min_temp'] = $t->temperatureMin;
						$u['max_temp'] = $t->temperatureMax;
					} else {
						$u['min_temp'] = 0;
						$u['max_temp'] = 0;
					}

					if($u['used'] > $max_scale) $max_scale = $u['used'];
					if($u['used'] < $min_scale) $min_scale = $u['used'];
					if($u['min_temp'] > $temp_max_scale) $temp_max_scale = $u['min_temp'];
					if($u['min_temp'] < $temp_min_scale) $temp_min_scale = $u['min_temp'];
					if($u['max_temp'] > $temp_max_scale) $temp_max_scale = $u['max_temp'];
					if($u['max_temp'] < $temp_min_scale) $temp_min_scale = $u['max_temp'];
				}
				unset($u);

				break;

			case 'Y':

				$used_from = date('Y-01-01', strtotime($date));
				$used_to = date('Y-m-d', strtotime('-1 day', strtotime('+1 year', strtotime($used_from))));

				$compare_from = date('Y-m-d', strtotime('-1 year', strtotime($used_from)));
				$compare_to = date('Y-m-d', strtotime('-1 day', strtotime('+1 year', strtotime($compare_from))));

				$totals = $msql->query_row(
					"SELECT
						SUM(used) AS total,
						MAX(used) AS max,
						AVG(IF(year = '$now_year' AND month = '$now_month', NULL, used)) AS avg
					FROM (
						SELECT

							YEAR(current_datetime) AS year,
							MONTH(current_datetime) AS month,
							SUM(used) AS used,
							COUNT(used) AS data_points

						FROM $subselect AS r

						WHERE current_datetime BETWEEN '$used_from 00:00:00' AND '$used_to 23:59:59'
						GROUP BY year, month
					) AS t;
				", MySQL::QUERY_ASSOC);

				if($totals) {
					$total = $totals['total'];
					$max = $totals['max'];
					$avg = $totals['avg'];
				}

				$used = $msql->query(
					"SELECT

						YEAR(current_datetime) AS year,
						MONTH(current_datetime) AS month,
						SUM(used) AS used,
						COUNT(used) AS data_points

					FROM $subselect AS r

					WHERE current_datetime BETWEEN '$used_from 00:00:00' AND '$used_to 23:59:59'
					GROUP BY year, month
					ORDER BY year, month;
				", MySQL::QUERY_ASSOC) ?: [];

				foreach($used as &$u) {
					$u['min_temp'] = 0;
					$u['max_temp'] = 0;

					if($u['used'] > $max_scale) $max_scale = $u['used'];
					if($u['used'] < $min_scale) $min_scale = $u['used'];
					if($u['min_temp'] > $temp_max_scale) $temp_max_scale = $u['min_temp'];
					if($u['min_temp'] < $temp_min_scale) $temp_min_scale = $u['min_temp'];
					if($u['max_temp'] > $temp_max_scale) $temp_max_scale = $u['max_temp'];
					if($u['max_temp'] < $temp_min_scale) $temp_min_scale = $u['max_temp'];
				}
				unset($u);

				$compare = $msql->query(
					"SELECT

						YEAR(current_datetime) AS year,
						MONTH(current_datetime) AS month,
						SUM(used) AS used,
						COUNT(used) AS data_points

					FROM $subselect AS r

					WHERE current_datetime BETWEEN '$compare_from 00:00:00' AND '$compare_to 23:59:59'
					GROUP BY year, month
					ORDER BY year, month;
				", MySQL::QUERY_ASSOC) ?: [];

				foreach($compare as &$u) {
					$u['min_temp'] = 0;
					$u['max_temp'] = 0;

					if($u['used'] > $max_scale) $max_scale = $u['used'];
					if($u['used'] < $min_scale) $min_scale = $u['used'];
					if($u['min_temp'] > $temp_max_scale) $temp_max_scale = $u['min_temp'];
					if($u['min_temp'] < $temp_min_scale) $temp_min_scale = $u['min_temp'];
					if($u['max_temp'] > $temp_max_scale) $temp_max_scale = $u['max_temp'];
					if($u['max_temp'] < $temp_min_scale) $temp_min_scale = $u['max_temp'];
				}
				unset($u);

				break;
		}

		$ch = explode('-', $used_to);

		return $this->success([
			'serial' => $smoothpower,
			'status' => $status,
			'surge_status' => $surge_status,
			'voltage_status' => true,

			'total' => $total,
			'max' => $max,
			'avg' => $avg,
			'total_label' => $total_label,
			'max_label' => $max_label,
			'avg_label' => $avg_label,

			'max_scale' => $max_scale,
			'min_scale' => $min_scale,
			'temp_max_scale' => $temp_max_scale,
			'temp_min_scale' => $temp_min_scale,

			'type' => $type,

			'used' => $used,
			'used_from' => $used_from,
			'used_to' => $used_to,

			'compare' => $compare,
			'compare_from' => $compare_from,
			'compare_to' => $compare_to,

			'select_date' => "$ch[0]-$ch[1]-$ch[2]",
			'select_month' => "$ch[0]-$ch[1]-01",
			'select_year' => "$ch[0]-01-01"
		]);
	}

	public function get_unit_status() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$smoothpower = App::sql()->query_row("SELECT serial, monitoring_server_id FROM smoothpower WHERE id = '$id';");
		if(!$smoothpower) return $this->error('SmoothPower unit not found.');
		if(!$smoothpower->monitoring_server_id) return $this->error('Monitoring server not found.');
		$msql = App::monitoring_sql($smoothpower->monitoring_server_id);
		if(!$msql) return $this->error('Unable to connect to monitoring database.');
		$smoothpower = $smoothpower->serial;

		// Check unit status
		$r = null;
		$r = $msql->query("SELECT datetime FROM abb_$smoothpower WHERE datetime > DATE_SUB(NOW(), INTERVAL 30 MINUTE);");
		$status = !!$r;

		// Check surge status
		$surge_status = false;
		$temp_top = null;
		$temp_bottom = null;
		try {
			$r = $msql->query_row("SELECT * FROM smoothpower.$smoothpower ORDER BY last_changed DESC LIMIT 1");
			if($r) {
				$surge_status = !$r->surge_protect_fail;
				$temp_top = $r->top_probe_temperature;
				$temp_bottom = $r->bottom_probe_temperature;
			}
		} catch(Exception $ex) { }

		// Get voltage
		$r = null;
		$r = $msql->query_row(
			"SELECT
				AVG(IF(meter_id LIKE '%.1', IF(volts_L1 > 0, volts_L1 / 10, NULL), NULL)) AS input_l1,
				AVG(IF(meter_id LIKE '%.1', IF(volts_L2 > 0, volts_L2 / 10, NULL), NULL)) AS input_l2,
				AVG(IF(meter_id LIKE '%.1', IF(volts_L3 > 0, volts_L3 / 10, NULL), NULL)) AS input_l3,
				AVG(IF(meter_id LIKE '%.2', IF(volts_L1 > 0, volts_L1 / 10, NULL), NULL)) AS output_l1,
				AVG(IF(meter_id LIKE '%.2', IF(volts_L2 > 0, volts_L2 / 10, NULL), NULL)) AS output_l2,
				AVG(IF(meter_id LIKE '%.2', IF(volts_L3 > 0, volts_L3 / 10, NULL), NULL)) AS output_l3
			FROM abb_$smoothpower
			WHERE datetime > DATE_SUB(NOW(), INTERVAL 2 HOUR);
		", MySQL::QUERY_ASSOC);
		$voltage_input = null;
		$voltage_output = null;
		$voltage_reduction = null;
		if($r) {
			$voltage_input = 0;
			$count = 0;
			if($r['input_l1']) { $voltage_input += $r['input_l1']; $count++; }
			if($r['input_l2']) { $voltage_input += $r['input_l2']; $count++; }
			if($r['input_l3']) { $voltage_input += $r['input_l3']; $count++; }
			if($count > 1) $voltage_input /= $count;
			if(!$voltage_input) $voltage_input = null;

			$voltage_output = 0;
			$count = 0;
			if($r['output_l1']) { $voltage_output += $r['output_l1']; $count++; }
			if($r['output_l2']) { $voltage_output += $r['output_l2']; $count++; }
			if($r['output_l3']) { $voltage_output += $r['output_l3']; $count++; }
			if($count > 1) $voltage_output /= $count;
			if(!$voltage_output) $voltage_output = null;

			if($voltage_input && $voltage_output) $voltage_reduction = (1 - ($voltage_output / $voltage_input)) * 100;
			if($voltage_input) $voltage_input = round($voltage_input);
			if($voltage_output) $voltage_output = round($voltage_output);
		}

		return $this->success([
			'id' => $id,
			'serial' => $smoothpower,
			'status' => $status,
			'surge_status' => $surge_status,
			'temp_top' => $temp_top,
			'temp_bottom' => $temp_bottom,
			'voltage_input' => $voltage_input,
			'voltage_output' => $voltage_output,
			'voltage_reduction' => $voltage_reduction
		]);
	}

	public function uninstall_unit() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$record = App::select('smoothpower', $id);
		if(!$record) return $this->error('SmoothPower unit not found.');

		if(!Permission::get_system_integrator($record['system_integrator_id'])->check(Permission::ADMIN)) return $this->access_denied();

		App::update('smoothpower', $id, [
			'building_id' => null,
			'area_id' => null,
			'router_id' => null
		]);

		return $this->success();
	}

	public function get_smoothpower_unit() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$record = App::select('smoothpower', $id);
		if(!$record) return $this->error('SmoothPower unit not found.');

		// Only Eticom can edit SmoothPower boxes
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		$si_list = Permission::list_system_integrators([ 'with' => Permission::SMOOTHPOWER_ENABLED ]) ?: [];

		return $this->success([
			'details' => $record,
			'si_list' => array_map(function($item) {
				return [
					'id' => $item->id,
					'description' => $item->company_name
				];
			}, $si_list),
			'breadcrumbs' => [
				[ 'description' => 'SmoothPower Units', 'route' => '/stock/smoothpower' ],
				[ 'description' => $record['serial'] ]
			]
		]);
	}

	public function save_smoothpower_unit() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) return $this->access_denied();

		// Check if exists
		$record = App::select('smoothpower', $id);
		if(!$record) return $this->error('SmoothPower unit not found.');

		// Check permissions (only Eticom can edit for now)
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, ['system_integrator_id']);
		$record = App::ensure($record, ['system_integrator_id'], 1);

		// Insert/update record
		$id = App::update('smoothpower', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function install_unit() {
		$data = App::json();

		$data = App::ensure($data,
			[
				'id',
				'hg_id', 'hg_name',
				'client_id', 'client_name',
				'building_id', 'building_name',
				'floor_id', 'floor_name',
				'area_id', 'area_name'
			]
		);

		$id = $data['id'];
		$hg_id = $data['hg_id'];
		$hg_name = $data['hg_name'];
		$client_id = $data['client_id'];
		$client_name = $data['client_name'];
		$building_id = $data['building_id'];
		$building_name = $data['building_name'];
		$floor_id = $data['floor_id'];
		$floor_name = $data['floor_name'];
		$area_id = $data['area_id'];
		$area_name = $data['area_name'];

		// Data validation

		if(!$id) return $this->access_denied();

		if($client_id === null) return $this->error('Please select a client.');
		if($building_id === null) return $this->error('Please select a building.');
		if($floor_id === null) return $this->error('Please select a block.');
		if($area_id === null) return $this->error('Please select an area.');

		if($hg_id === 'new' && !$hg_name) return $this->error('Please enter new holding group name.');
		if($client_id === 'new' && !$client_name) return $this->error('Please enter new client name.');
		if($building_id === 'new' && !$building_name) return $this->error('Please enter new building name.');
		if($floor_id === 'new' && !$floor_name) return $this->error('Please enter new block name.');
		if($area_id === 'new' && !$area_name) return $this->error('Please enter new area name.');

		// Make sure IDs are escaped

		if($hg_id !== null) $hg_id = App::escape($hg_id);
		$client_id = App::escape($client_id);
		$building_id = App::escape($building_id);
		$floor_id = App::escape($floor_id);
		$area_id = App::escape($area_id);

		// Allow only ONE SmoothPower unit per building

		if($building_id !== 'new') {
			$exists = App::sql()->query("SELECT id FROM smoothpower WHERE building_id = '$building_id';");
			if($exists) return $this->error('Building already has a SmoothPower unit installed.');
		}

		// Make sure there is no selected ID after a NEW option

		if($hg_id === 'new') {
			$level = 'holding group';
			if($client_id !== 'new') return $this->error("Selecting existing client is not allowed for a new $level.");
			if($building_id !== 'new') return $this->error("Selecting existing building is not allowed for a new $level.");
			if($floor_id !== 'new') return $this->error("Selecting existing block is not allowed for a new $level.");
			if($area_id !== 'new') return $this->error("Selecting existing area is not allowed for a new $level.");
		} else if($client_id === 'new') {
			$level = 'client';
			if($building_id !== 'new') return $this->error("Selecting existing building is not allowed for a new $level.");
			if($floor_id !== 'new') return $this->error("Selecting existing block is not allowed for a new $level.");
			if($area_id !== 'new') return $this->error("Selecting existing area is not allowed for a new $level.");
		} else if($building_id === 'new') {
			$level = 'building';
			if($floor_id !== 'new') return $this->error("Selecting existing block is not allowed for a new $level.");
			if($area_id !== 'new') return $this->error("Selecting existing area is not allowed for a new $level.");
		} else if($floor_id === 'new') {
			$level = 'block';
			if($area_id !== 'new') return $this->error("Selecting existing area is not allowed for a new $level.");
		}

		// Check if selected IDs are in a valid chain

		if($area_id !== 'new') {
			$r = App::sql()->query(
				"SELECT *
				FROM area AS a
				JOIN floor AS f ON f.id = a.floor_id
				JOIN building AS b ON b.id = f.building_id
				JOIN client AS c ON c.id = b.client_id
				WHERE a.id = '$area_id'
			");
			if(!$r) return $this->error('Unable to resolve existing area.');
		} else if($floor_id !== 'new') {
			$r = App::sql()->query(
				"SELECT *
				FROM floor AS f
				JOIN building AS b ON b.id = f.building_id
				JOIN client AS c ON c.id = b.client_id
				WHERE f.id = '$floor_id'
			");
			if(!$r) return $this->error('Unable to resolve existing block.');
		} else if($building_id !== 'new') {
			$r = App::sql()->query(
				"SELECT *
				FROM building AS b
				JOIN client AS c ON c.id = b.client_id
				WHERE b.id = '$building_id'
			");
			if(!$r) return $this->error('Unable to resolve existing building.');
		}

		// Check access

		$record = App::select('smoothpower', $id);
		if(!$record) return $this->error('SmoothPower unit not found.');
		$si_id = $record['system_integrator_id'];
		if(!$si_id) return $this->access_denied();
		if(!Permission::get_system_integrator($si_id)->check(Permission::ADMIN)) return $this->access_denied();

		// Check if all records exist

		if($hg_id !== 'new' && $hg_id !== null && !App::select('holding_group', $hg_id)) return $this->error('Holding group not found.');
		if($client_id !== 'new' && !App::select('client', $client_id)) return $this->error('Client not found.');
		if($building_id !== 'new' && !App::select('building', $building_id)) return $this->error('Building not found.');
		if($floor_id !== 'new' && !App::select('floor', $floor_id)) return $this->error('Block not found.');
		if($area_id !== 'new' && !App::select('area', $area_id)) return $this->error('Area not found.');

		// All checks out, let's process everything

		App::sql()->start_transaction();

		try {

			if($hg_id === 'new') {
				$hg_id = App::insert('holding_group', [
					'system_integrator_id' => $si_id,
					'company_name' => $hg_name
				]);
				if(!$hg_id) throw new Exception('Cannot create holding group.');

				$hg_role = App::insert('user_role', [
					'owner_level' => 'HG',
					'owner_id' => $hg_id,
					'is_admin' => 1,
					'description' => 'Holding Group Admin',
					'smoothpower_permissions' => 1
				]);
				if(!$hg_role) throw new Exception('Cannot grant holding group permissions.');
			}

			if($client_id === 'new') {
				$client_data = [
					'system_integrator_id' => $si_id,
					'name' => $client_name,
					'active' => 1
				];

				if($hg_id) $client_data['holding_group_id'] = $hg_id;

				$client_id = App::insert('client', $client_data);
				if(!$client_id) throw new Exception('Cannot create client.');

				$client_role = App::insert('user_role', [
					'owner_level' => 'C',
					'owner_id' => $client_id,
					'is_admin' => 1,
					'description' => 'Client Admin',
					'smoothpower_permissions' => 1
				]);
				if(!$client_role) throw new Exception('Cannot grant client permissions.');
			} else {
				$res = App::sql()->update("UPDATE user_role SET smoothpower_permissions = 1 WHERE owner_level = 'C' AND owner_id = '$client_id' AND is_admin = 1 AND is_level_default = 0;");
				if(!$res) throw new Exception('Cannot update client permissions.');

				// If client has a holding group set, make sure to allow SmoothPower module
				$client = App::select('client', $client_id);
				if(!$client) throw new Exception('Client not found.');

				if($client['holding_group_id']) {
					$hg_id = $client['holding_group_id'];
					$res = App::sql()->update("UPDATE user_role SET smoothpower_permissions = 1 WHERE owner_level = 'HG' AND owner_id = '$hg_id' AND is_admin = 1 AND is_level_default = 0;");
					if(!$res) throw new Exception('Cannot update client permissions.');
				}
			}

			if($building_id === 'new') {
				$building_id = App::insert('building', [
					'client_id' => $client_id,
					'description' => $building_name,
					'active' => 1,
					'module_smoothpower' => 1
				]);
				if(!$building_id) throw new Exception('Cannot create building.');

				$building_role = App::insert('user_role', [
					'owner_level' => 'B',
					'owner_id' => $building_id,
					'is_admin' => 1,
					'description' => 'Building Admin',
					'smoothpower_permissions' => 1
				]);
				if(!$building_role) throw new Exception('Cannot grant building permissions.');
			} else {
				$res = App::update('building', $building_id, [ 'module_smoothpower' => 1 ]);
				if(!$res) throw new Exception('Cannot update building.');

				$res = App::sql()->update("UPDATE user_role SET smoothpower_permissions = 1 WHERE owner_level = 'B' AND owner_id = '$building_id' AND is_admin = 1 AND is_level_default = 0;");
				if(!$res) throw new Exception('Cannot update building permissions.');
			}

			if($floor_id === 'new') {
				$floor_id = App::insert('floor', [
					'building_id' => $building_id,
					'description' => $floor_name
				]);
				if(!$floor_id) throw new Exception('Cannot create block.');
			}

			if($area_id === 'new') {
				$area_id = App::insert('area', [
					'floor_id' => $floor_id,
					'description' => $area_name
				]);
				if(!$area_id) throw new Exception('Cannot create area.');
			}

			// Find router in existing building or create new one

			$router = App::sql()->query_row(
				"SELECT r.id
				FROM router AS r
				JOIN area AS a ON a.id = r.area_id
				JOIN floor AS f ON f.id = a.floor_id
				WHERE f.building_id = '$building_id'
				LIMIT 1;
			", MySQL::QUERY_ASSOC);

			if($router) {
				$router_id = $router['id'];
			} else {
				$router_id = App::insert('router', [
					'area_id' => $area_id,
					'description' => 'Router'
				]);
				if(!$router_id) throw new Exception('Cannot create router.');
			}

			// All good, add SmoothPower box to building

			$res = App::update('smoothpower', $id, [
				'building_id' => $building_id,
				'area_id' => $area_id,
				'router_id' => $router_id
			]);
			if(!$res) throw new Exception('Cannot update SmoothPower unit.');

		} catch(Exception $ex) {
			App::sql()->rollback_transaction();
			return $this->error($ex->getMessage());
		}

		App::sql()->commit_transaction();

		return $this->success();
	}

}
