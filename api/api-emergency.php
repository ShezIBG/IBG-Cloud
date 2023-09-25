<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_overview() {
		$buildings = Permission::list_buildings([ 'with' => Permission::EMERGENCY_ENABLED ]);
		if(!$buildings) return $this->error('No buildings found.');

		$building_list = array_map(function($b) { return $b->id; }, $buildings);
		$building_list = implode(', ', $building_list);
		$light_query = EMLight::details_query("b.id IN ($building_list)");

		$q = "SELECT
				b.id, b.description, b.image_id, b.address, b.posttown, b.postcode,
				COUNT(l.id) AS light_count,
				SUM(IF(l.light_status = -1, 1, 0)) AS light_fail,
				SUM(IF(l.light_status = 0, 1, 0)) AS light_warning,
				SUM(IF(l.light_status = 1, 1, 0)) AS light_pass,
				MIN(l.light_status) AS building_status
			FROM ($light_query) AS l
			JOIN building AS b ON b.id = l.building_id
			GROUP BY b.id, b.description, b.image_id, b.address, b.posttown, b.postcode
			ORDER BY building_status, description;
		";

		$building_statuses = App::sql()->query($q);
		if(!$building_statuses) return $this->error('No buildings with emergency lights found.');

		$cnt = count($building_statuses);

		$result = [
			'history' => EMLight::get_log_with_details("WHERE log.building_id IN ($building_list) ORDER BY log.datetime DESC LIMIT 20"),
			'buildings' => array_map(function($bs) use ($cnt) {
				$uc = $cnt <= 6 ? new UserContent($bs->image_id) : null;

				$light_count = $bs->light_count ?: 0;
				$light_pass = $bs->light_pass ?: 0;
				$light_fail = $bs->light_fail ?: 0;
				$light_warning = $bs->light_warning ?: 0;

				$light_pass_perc = $light_count ? floor($light_pass / $light_count * 100) : 0;
				$light_fail_perc = $light_count ? floor($light_fail / $light_count * 100) : 0;
				$light_warning_perc = $light_count ? floor($light_warning / $light_count * 100) : 0;

				return [
					'id' => $bs->id,
					'description' => $bs->description,
					'status' => $bs->building_status,
					'image' => ($uc && $uc->info) ? $uc->get_url() : ASSETS_URL.'/img/building-no-image.png',
					'address' => $bs->address,
					'posttown' => $bs->posttown,
					'postcode' => $bs->postcode,
					'light_count' => $light_count,
					'light_pass' => $light_pass,
					'light_fail' => $light_fail,
					'light_warning' => $light_warning,
					'light_pass_perc' => $light_pass_perc,
					'light_fail_perc' => $light_fail_perc,
					'light_warning_perc' => $light_warning_perc
				];
			}, $building_statuses)
		];

		return $this->success($result);
	}

	private function get_building_count() {
		$buildings = Permission::find_buildings([ 'with' => Permission::EMERGENCY_ENABLED ]) ?: [];
		return count($buildings);
	}

	public function get_building() {
		$result = [];

		$building_id = App::get('id', 0, true);
		if(!$this->validate("building:$building_id")) return $this->validation_error;

		$result['building_count'] = $this->get_building_count();
		if($result['building_count'] == 0) return $this->error('No buildings found.');

		$light_query = EMLight::details_query("b.id = '$building_id'");

		$building_statuses = App::sql()->query(
			"SELECT
				b.id, b.description, b.image_id, b.address, b.posttown, b.postcode, b.timezone,
				COUNT(l.id) AS light_count,
				SUM(IF(l.light_status = -1, 1, 0)) AS light_fail,
				SUM(IF(l.light_status = 0, 1, 0)) AS light_warning,
				SUM(IF(l.light_status = 1, 1, 0)) AS light_pass,
				MIN(l.light_status) AS building_status
			FROM ($light_query) AS l
			JOIN building AS b ON b.id = l.building_id
			GROUP BY b.id, b.description, b.image_id, b.address, b.posttown, b.postcode
			LIMIT 1;
		");

		if(!$building_statuses) return $this->error('No emergency lights found.');

		$bs = $building_statuses[0];
		$uc = new UserContent($bs->image_id);
		$result['building'] = [
			'id' => $bs->id,
			'description' => $bs->description,
			'status' => $bs->building_status,
			'image' => ($uc && $uc->info) ? $uc->get_url() : ASSETS_URL.'/img/building-no-image.png',
			'address' => $bs->address,
			'posttown' => $bs->posttown,
			'postcode' => $bs->postcode,
			'light_count' => $bs->light_count ?: 0,
			'light_pass' => $bs->light_pass ?: 0,
			'light_fail' => $bs->light_fail ?: 0,
			'light_warning' => $bs->light_warning ?: 0
		];

		$groups = App::sql()->query(
			"SELECT
				lg.id, lg.description, lg.function_test_datetime, lg.duration_test_datetime,
				COUNT(l.id) AS light_count
			FROM em_light_group AS lg
			LEFT JOIN em_light AS l ON l.group_id = lg.id
			WHERE lg.building_id = '$building_id' AND lg.active = 1
			GROUP BY lg.id, lg.description, lg.function_test_datetime, lg.duration_test_datetime
			ORDER BY lg.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($groups as &$g) {
			if($g['function_test_datetime']) $g['function_test_datetime'] = App::timezone($g['function_test_datetime'], 'UTC', $bs->timezone);
			if($g['duration_test_datetime']) $g['duration_test_datetime'] = App::timezone($g['duration_test_datetime'], 'UTC', $bs->timezone);
		}
		unset($g);

		$result['groups'] = $groups;

		$types = App::sql()->query(
			"SELECT
				lt.id, lt.description, lt.icon,
				COUNT(l.id) AS light_count
			FROM em_light_type AS lt
			JOIN em_light AS l ON l.type_id = lt.id
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			WHERE b.id = '$building_id'
			GROUP BY lt.id, lt.description, lt.icon
			ORDER BY lt.description;
		");

		$result['types'] = $types ?: [];

		$issues = App::sql()->query(
			"SELECT
				SUM(IF(function_test_status = -1, 1, 0)) AS function_test_failed,
				SUM(IF(duration_test_status = -1, 1, 0)) AS duration_test_failed,
				SUM(IF(circuit_failure = -1, 1, 0)) AS circuit_failure,
				SUM(IF(battery_duration_failure = -1, 1, 0)) AS battery_duration_failure,
				SUM(IF(battery_failure = -1, 1, 0)) AS battery_failure,
				SUM(IF(emergency_lamp_failure = -1, 1, 0)) AS emergency_lamp_failure,
				SUM(IF(function_test_finished_datetime IS NOT NULL AND function_test_age_ok = 0, 1, 0)) AS old_function_test,
				SUM(IF(duration_test_finished_datetime IS NOT NULL AND duration_test_age_ok = 0, 1, 0)) AS old_duration_test,
				SUM(IF(function_test_finished_datetime IS NULL, 1, 0)) AS no_function_test,
				SUM(IF(duration_test_finished_datetime IS NULL, 1, 0)) AS no_duration_test,
				SUM(IF(has_group = 0, 1, 0)) AS no_group
			FROM ($light_query) AS l;
		");

		$result['group_issues'] = [];
		$result['light_issues'] = [];
		$function_period = EMLight::EMERGENCY_FUNCTION_PERIOD;
		$duration_period = EMLight::EMERGENCY_DURATION_PERIOD;
		if($issues) {
			$v = $issues[0]->old_function_test;        if($v) $result['light_issues'][] = ['description' => "Last function test over $function_period days ago", 'count' => $v, 'severity' => 'warning'];
			$v = $issues[0]->old_duration_test;        if($v) $result['light_issues'][] = ['description' => "Last duration test over $duration_period days ago", 'count' => $v, 'severity' => 'warning'];
			$v = $issues[0]->no_function_test;         if($v) $result['light_issues'][] = ['description' => 'Function test never ran', 'count' => $v, 'severity' => 'warning'];
			$v = $issues[0]->no_duration_test;         if($v) $result['light_issues'][] = ['description' => 'Duration test never ran', 'count' => $v, 'severity' => 'warning'];

			$v = $issues[0]->no_group;
			if($v) {
				$word = $v == 1 ? 'light' : 'lights';
				$result['group_issues'][] = ['description' => "$v $word not in group", 'severity' => 'warning'];
			}
		}

		$faults = App::sql()->query(
			"SELECT
				SUM(emf.function_test_failed) AS function_test_failed,
				SUM(emf.duration_test_failed) AS duration_test_failed,
				SUM(emf.circuit_failure) AS circuit_failure,
				SUM(emf.battery_failure) AS battery_failure,
				SUM(emf.battery_duration_failure) AS battery_duration_failure,
				SUM(emf.emergency_lamp_failure) AS emergency_lamp_failure
			FROM em_fault AS emf
			JOIN em_light AS l ON l.id = emf.em_light_id
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$building_id';
		");

		$result['faults'] = [];

		if($faults) {
			// TODO: Use this section once faults have been implemented!!
			//
			// $v = $faults[0]->function_test_failed;     if($v) $result['faults'][] = ['description' => 'Function test failed', 'count' => $v, 'severity' => 'danger'];
			// $v = $faults[0]->duration_test_failed;     if($v) $result['faults'][] = ['description' => 'Duration test failed', 'count' => $v, 'severity' => 'danger'];
			// $v = $faults[0]->circuit_failure;          if($v) $result['faults'][] = ['description' => 'Circuit failure', 'count' => $v, 'severity' => 'danger'];
			// $v = $faults[0]->battery_failure;          if($v) $result['faults'][] = ['description' => 'Battery failure', 'count' => $v, 'severity' => 'danger'];
			// $v = $faults[0]->battery_duration_failure; if($v) $result['faults'][] = ['description' => 'Battery duration failure', 'count' => $v, 'severity' => 'danger'];
			// $v = $faults[0]->emergency_lamp_failure;   if($v) $result['faults'][] = ['description' => 'Emergency lamp failure', 'count' => $v, 'severity' => 'danger'];

			$v = $issues[0]->function_test_failed;     if($v) $result['faults'][] = ['description' => 'Function test failed', 'count' => $v, 'severity' => 'danger'];
			$v = $issues[0]->duration_test_failed;     if($v) $result['faults'][] = ['description' => 'Duration test failed', 'count' => $v, 'severity' => 'danger'];
			$v = $issues[0]->circuit_failure;          if($v) $result['faults'][] = ['description' => 'Circuit failure', 'count' => $v, 'severity' => 'danger'];
			$v = $issues[0]->battery_failure;          if($v) $result['faults'][] = ['description' => 'Battery failure', 'count' => $v, 'severity' => 'danger'];
			$v = $issues[0]->battery_duration_failure; if($v) $result['faults'][] = ['description' => 'Battery duration failure', 'count' => $v, 'severity' => 'danger'];
			$v = $issues[0]->emergency_lamp_failure;   if($v) $result['faults'][] = ['description' => 'Emergency lamp failure', 'count' => $v, 'severity' => 'danger'];
		}

		$faults = App::sql()->query(
			"SELECT
				COUNT(*) AS auto_resolve
			FROM em_fault_history AS emf
			JOIN em_light AS l ON l.id = emf.em_light_id
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$building_id' AND repair_user_id IS NULL;
		");

		if($faults) {
			$v = $faults[0]->auto_resolve;     if($v) $result['faults'][] = ['description' => 'Automatically resolved', 'count' => $v, 'severity' => 'warning'];
		}

		$result['history'] = EMLight::get_log_with_details("WHERE log.building_id = '$building_id' ORDER BY log.datetime DESC LIMIT 20");

		return $this->success($result);
	}

	public function get_building_groups() {
		$result = [];

		$building_id = App::get('id', 0, true);
		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		$result['building_count'] = $this->get_building_count();
		if($result['building_count'] == 0) return $this->error('No buildings found.');

		$result['building'] = [ 'id' => $building->id, 'description' => $building->info->description ];

		$r = App::sql()->query("SELECT id, description, function_test_datetime, duration_test_datetime FROM em_light_group WHERE building_id = '$building->id' AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		$result['groups'] = array_map(function($g) use ($building) {
			if($g['function_test_datetime']) $g['function_test_datetime'] = App::timezone($g['function_test_datetime'], 'UTC', $building->info->timezone);
			if($g['duration_test_datetime']) $g['duration_test_datetime'] = App::timezone($g['duration_test_datetime'], 'UTC', $building->info->timezone);
			return $g;
		}, $r);

		$r = App::sql()->query("SELECT a.id, a.description FROM area AS a JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id';");
		$result['areas'] = $r ?: [];

		$r = App::sql()->query("SELECT id, description, image_id FROM floorplan WHERE building_id = '$building->id' ORDER BY description;");
		$result['floorplans'] = array_map(function($fp) {
			$uc = new UserContent($fp->image_id);
			return [
				'id' => $fp->id,
				'description' => $fp->description,
				'image' => $uc ? $uc->get_url() : null
			];
		}, $r ?: []);

		$r = App::sql()->query(
			"SELECT
				l.id, l.description, l.group_id, l.zone_number,
				a.description as area_description,
				f.description as floor_description,
				t.icon as type_icon,
				t.description as type_description,
				fpi.floorplan_id, fpi.x, fpi.y, fpi.direction
			FROM em_light AS l
			JOIN em_light_type AS t ON t.id = l.type_id
			JOIN area AS a ON l.area_id = a.id
			JOIN floor AS f ON a.floor_id = f.id
			LEFT JOIN floorplan_item AS fpi ON fpi.item_type = 'em_light' AND fpi.item_id = l.id
			WHERE f.building_id = '$building->id'
			ORDER BY f.display_order, a.display_order, l.description;
		");
		$result['lights'] = $r ?: [];

		return $this->success($result);
	}

	public function save_building_group() {
		$data = App::json();

		$building_id = App::escape($data['building_id']);
		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		$id = $data['id'];
		$escaped_id = App::escape($id);
		$record = App::keep($data, ['description', 'function_test_datetime', 'duration_test_datetime']);
		$record = App::ensure($record, ['description'], '');
		$record = App::ensure($record, ['function_test_datetime', 'duration_test_datetime'], null);

		if($record['function_test_datetime']) $record['function_test_datetime'] = App::timezone($record['function_test_datetime'], $building->info->timezone, 'UTC');
		if($record['duration_test_datetime']) $record['duration_test_datetime'] = App::timezone($record['duration_test_datetime'], $building->info->timezone, 'UTC');

		if($id !== 'new') {
			$result = App::sql()->query("SELECT id FROM em_light_group WHERE id = '$escaped_id' AND building_id = '$building_id';");
			if(!$result) return $this->error('Building not found.');

			App::update('em_light_group', $id, $record);
		} else {
			$record['building_id'] = $building_id;
			$id = App::insert('em_light_group', $record);
			$escaped_id = App::escape($id);
			if(!$id) return $this->error('Cannot create group.');

			App::insert('em_log', [
				'building_id' => $building_id,
				'client_id' => $this->building->info->client_id,
				'user_id' => App::user()->id,
				'group_id' => $id,
				'event' => 'group_create'
			]);
		}

		// At this point, group is created/updated, sort out light assignments

		$lights = $data['lights'];
		$light_list = implode(', ', App::escape_and_wrap($lights));

		// Delete all current light schedules for new/old lights
		if(count($lights) == 0) {
			App::sql()->delete("DELETE s FROM em_schedule AS s JOIN em_light AS l ON l.id = s.em_light_id WHERE (l.group_id = '$escaped_id') AND s.manual = 0;");

			// Update EM server
			$r = App::sql()->query("SELECT l.id, g.pi_serial, g.monitoring_server_id FROM em_light AS l JOIN gateway AS g ON g.id = l.gateway_id WHERE l.group_id = '$escaped_id' AND g.monitoring_server_id IS NOT NULL;", MySQL::QUERY_ASSOC) ?: [];
			foreach($r as $l) {
				$msql = App::sql("monitoring:$l[monitoring_server_id]");
				if($msql) {
					$msql->delete("DELETE s FROM em_schedule_$l[pi_serial] AS s WHERE s.em_light_id = '$l[id]' AND s.manual = 0;");
				}
			}

			// Check which lights are being unassigned
			$a = App::sql()->query("SELECT id FROM em_light WHERE group_id = '$escaped_id';") ?: [];
			foreach($a as $al) {
				$lid = $al->id;
				App::insert('em_log', [
					'building_id' => $building_id,
					'client_id' => $building->info->client_id,
					'user_id' => App::user()->id,
					'group_id' => $id,
					'light_id' => $lid,
					'event' => 'light_unassign'
				]);
			}
		} else {
			App::sql()->delete("DELETE s FROM em_schedule AS s JOIN em_light AS l ON l.id = s.em_light_id WHERE (l.group_id = '$escaped_id' OR l.id IN ($light_list)) AND s.manual = 0;");

			// Update EM server
			$r = App::sql()->query("SELECT l.id, g.pi_serial, g.monitoring_server_id FROM em_light AS l JOIN gateway AS g ON g.id = l.gateway_id WHERE (l.group_id = '$escaped_id' OR l.id IN ($light_list)) AND g.monitoring_server_id IS NOT null;", MySQL::QUERY_ASSOC) ?: [];
			foreach($r as $l) {
				$msql = App::sql("monitoring:$l[monitoring_server_id]");
				if($msql) {
					$msql->delete("DELETE s FROM em_schedule_$l[pi_serial] AS s WHERE s.em_light_id = '$l[id]' AND s.manual = 0;");
				}
			}

			// Check which lights are being unassigned
			$a = App::sql()->query("SELECT id FROM em_light WHERE group_id = '$escaped_id' AND id NOT IN ($light_list);") ?: [];
			foreach($a as $al) {
				$lid = $al->id;
				App::insert('em_log', [
					'building_id' => $building_id,
					'client_id' => $building->info->client_id,
					'user_id' => App::user()->id,
					'group_id' => $id,
					'light_id' => $lid,
					'event' => 'light_unassign'
				]);
			}

			// Check which lights are being assigned
			$a = App::sql()->query("SELECT id FROM em_light WHERE (group_id IS NULL OR group_id <> '$escaped_id') AND id IN ($light_list);") ?: [];
			foreach($a as $al) {
				$lid = $al->id;
				App::insert('em_log', [
					'building_id' => $building_id,
					'client_id' => $building->info->client_id,
					'user_id' => App::user()->id,
					'group_id' => $id,
					'light_id' => $lid,
					'event' => 'light_assign'
				]);
			}
		}

		// Update light groups
		App::sql()->update("UPDATE em_light SET group_id = NULL WHERE group_id = '$escaped_id';");
		if(count($lights) > 0) App::sql()->update("UPDATE em_light SET group_id = '$escaped_id' WHERE id IN ($light_list);");

		// Add light schedules
		$dt = $record['function_test_datetime'];
		if($dt) {
			foreach($lights as $lid) {
				$lid = App::sql()->escape($lid);
				$info = App::sql()->query_row(
					"SELECT
						l.dali_address,
						g.pi_serial,
						g.monitoring_server_id
					FROM em_light AS l
					LEFT JOIN gateway AS g ON g.id = l.gateway_id
					WHERE l.id = '$lid';
				");
				$mid = $info ? $info->monitoring_server_id : null;
				$pi_serial = $info ? $info->pi_serial : null;
				$dali_address = $info ? $info->dali_address : null;

				App::insert_ignore('em_schedule', [
					'em_light_id' => $lid,
					'datetime' => $dt,
					'test_type' => 'function',
					'dali_address' => $dali_address
				]);

				// Update EM server
				if($mid && $pi_serial) {
					App::insert_ignore("em_schedule_$pi_serial@monitoring:$mid", [
						'em_light_id' => $lid,
						'datetime' => $dt,
						'test_type' => 'function',
						'dali_address' => $dali_address
					]);
				}
			}
		}

		$dt = $record['duration_test_datetime'];
		if($dt) {
			foreach($lights as $lid) {
				$lid = App::sql()->escape($lid);
				$info = App::sql()->query_row(
					"SELECT
						l.dali_address,
						g.pi_serial,
						g.monitoring_server_id
					FROM em_light AS l
					LEFT JOIN gateway AS g ON g.id = l.gateway_id
					WHERE l.id = '$lid';
				");
				$mid = $info ? $info->monitoring_server_id : null;
				$pi_serial = $info ? $info->pi_serial : null;
				$dali_address = $info ? $info->dali_address : null;

				App::insert_ignore('em_schedule', [
					'em_light_id' => $lid,
					'datetime' => $dt,
					'test_type' => 'duration',
					'dali_address' => $dali_address
				]);

				// Update EM server
				if($mid && $pi_serial) {
					App::insert_ignore("em_schedule_$pi_serial@monitoring:$mid", [
						'em_light_id' => $lid,
						'datetime' => $dt,
						'test_type' => 'duration',
						'dali_address' => $dali_address
					]);
				}
			}
		}

		return $this->success();
	}

	public function delete_building_group() {
		$data = App::json();

		$building_id = App::escape($data['building_id']);
		if(!$this->validate("building:$building_id")) return $this->validation_error;

		$id = $data['id'];
		$escaped_id = App::escape($id);

		$result = App::sql()->query("SELECT id FROM em_light_group WHERE id = '$escaped_id' AND building_id = '$building_id';");
		if(!$result) return $this->error('Building not found.');

		// Delete automatic schedule items
		App::sql()->delete("DELETE s FROM em_schedule AS s JOIN em_light AS l ON l.id = s.em_light_id WHERE (l.group_id = '$escaped_id') AND s.manual = 0;");

		// Update EM server
		$r = App::sql()->delete("SELECT l.id, g.pi_serial, g.monitoring_server_id FROM em_light AS l JOIN gateway AS g ON g.id = l.gateway_id WHERE l.group_id = '$escaped_id' AND g.monitoring_server_id IS NOT NULL;", MySQL::QUERY_ASSOC) ?: [];
		foreach($r as $l) {
			$msql = App::sql("monitoring:$l[monitoring_server_id]");
			if($msql) {
				$msql->delete("DELETE s FROM em_schedule_$l[pi_serial] AS s WHERE s.em_light_id = '$l[id]' AND s.manual = 0;");
			}
		}

		// Remove all lights from group
		App::sql()->update("UPDATE em_light SET group_id = NULL WHERE group_id = '$escaped_id';");

		// Set group active flag to 0 to hide it
		App::sql()->update("UPDATE em_light_group SET active = 0 WHERE id = '$escaped_id';");

		// Add log entry
		App::insert('em_log', [
			'building_id' => $building_id,
			'client_id' => $this->building->info->client_id,
			'user_id' => App::user()->id,
			'group_id' => $id,
			'event' => 'group_delete'
		]);

		return $this->success();
	}

	public function get_building_lights() {
		$result = [];

		$building_id = App::get('id', 0, true);
		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		$result['building'] = [ 'id' => $building->id, 'description' => $building->info->description ];

		$result['building_count'] = $this->get_building_count();
		if($result['building_count'] == 0) return $this->error('No buildings found.');

		$light_query = EMLight::details_query("b.id = '$building_id'");

		$r = App::sql()->query(
			"SELECT *
			FROM ($light_query) AS l
			ORDER BY floor_display_order, area_display_order, l.zone_number, l.description, l.id;
		", MySQL::QUERY_ASSOC) ?: [];
		$result['lights'] = array_map(function($l) {
			if($l['function_test_finished_datetime']) $l['function_test_finished_datetime'] = App::timezone($l['function_test_finished_datetime'], 'UTC', $l['building_timezone']);
			if($l['duration_test_finished_datetime']) $l['duration_test_finished_datetime'] = App::timezone($l['duration_test_finished_datetime'], 'UTC', $l['building_timezone']);
			if($l['scheduled_function_datetime']) $l['scheduled_function_datetime'] = App::timezone($l['scheduled_function_datetime'], 'UTC', $l['building_timezone']);
			if($l['scheduled_duration_datetime']) $l['scheduled_duration_datetime'] = App::timezone($l['scheduled_duration_datetime'], 'UTC', $l['building_timezone']);
			if($l['manual_function_datetime']) $l['manual_function_datetime'] = App::timezone($l['manual_function_datetime'], 'UTC', $l['building_timezone']);
			if($l['manual_duration_datetime']) $l['manual_duration_datetime'] = App::timezone($l['manual_duration_datetime'], 'UTC', $l['building_timezone']);
			return $l;
		}, $r);

		$r = App::sql()->query("SELECT id, description, image_id FROM floorplan WHERE building_id = '$building->id' ORDER BY description;");
		$result['floorplans'] = array_map(function($fp) {
			$uc = new UserContent($fp->image_id);
			return [
				'id' => $fp->id,
				'description' => $fp->description,
				'image' => $uc ? $uc->get_url() : null
			];
		}, $r ?: []);

		$result['function_test_age_limit'] = EMLight::EMERGENCY_FUNCTION_PERIOD;
		$result['duration_test_age_limit'] = EMLight::EMERGENCY_DURATION_PERIOD;

		return $this->success($result);
	}

	public function get_light() {
		$result = [];

		$light_id = App::get('id', 0, true);

		$building_id = App::sql()->query_row(
			"SELECT b.id
			FROM em_light AS l
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id;"
		);
		if($building_id) $building_id = $building_id->id;
		if(!$building_id) return $this->error('Light not found.');

		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		// Get light

		$light_query = EMLight::details_query("l.id = '$light_id'");

		$r = App::sql()->query_row("SELECT * FROM ($light_query) AS l;", MySQL::QUERY_ASSOC);
		if(!$r) return $this->error('Light not found.');

		if($r['function_test_finished_datetime']) $r['function_test_finished_datetime'] = App::timezone($r['function_test_finished_datetime'], 'UTC', $building->info->timezone);
		if($r['duration_test_finished_datetime']) $r['duration_test_finished_datetime'] = App::timezone($r['duration_test_finished_datetime'], 'UTC', $building->info->timezone);
		if($r['scheduled_function_datetime']) $r['scheduled_function_datetime'] = App::timezone($r['scheduled_function_datetime'], 'UTC', $building->info->timezone);
		if($r['scheduled_duration_datetime']) $r['scheduled_duration_datetime'] = App::timezone($r['scheduled_duration_datetime'], 'UTC', $building->info->timezone);
		if($r['manual_function_datetime']) $r['manual_function_datetime'] = App::timezone($r['manual_function_datetime'], 'UTC', $building->info->timezone);
		if($r['manual_duration_datetime']) $r['manual_duration_datetime'] = App::timezone($r['manual_duration_datetime'], 'UTC', $building->info->timezone);

		$result['light'] = $r;

		$r = App::sql()->query_row("SELECT id, description, image_id FROM floorplan WHERE id = '$r[floorplan_id]' LIMIT 1;");
		$result['floorplan'] = null;
		if($r) {
			$uc = new UserContent($r->image_id);
			$result['floorplan'] = [
				'id' => $r->id,
				'description' => $r->description,
				'image' => $uc ? $uc->get_url() : null
			];
		}

		$r = App::sql()->query("SELECT * FROM em_history WHERE em_light_id = '$light_id' ORDER BY datetime DESC;");
		$result['test_history'] = $r ?: [];

		$result['history'] = EMLight::get_log_with_details("WHERE log.light_id = '$light_id' ORDER BY log.datetime DESC");

		return $this->success($result);
	}

	public function save_light_schedule() {
		$data = App::json();

		$building_id = App::escape($data['building_id']);
		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		$id = $data['id'];
		$escaped_id = App::escape($id);

		$repair_notes = $data['repair_notes'];
		$manual_function_datetime = $data['manual_function_datetime'];
		$manual_duration_datetime = $data['manual_duration_datetime'];

		$result = App::sql()->query(
			"SELECT l.id
			FROM em_light AS l
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			WHERE l.id = '$escaped_id' AND b.id = '$building_id';"
		);
		if(!$result) return $this->error('Building not found.');

		if($repair_notes) {
			App::insert('em_log', [
				'building_id' => $building_id,
				'client_id' => $this->building->info->client_id,
				'user_id' => App::user()->id,
				'light_id' => $id,
				'event' => 'light_repair',
				'notes' => $repair_notes
			]);
		}

		$info = App::sql()->query_row(
			"SELECT
				l.dali_address,
				g.pi_serial,
				g.monitoring_server_id
			FROM em_light AS l
			LEFT JOIN gateway AS g ON g.id = l.gateway_id
			WHERE l.id = '$escaped_id';
		");
		$mid = $info ? $info->monitoring_server_id : null;
		$pi_serial = $info ? $info->pi_serial : null;
		$dali_address = $info ? $info->dali_address : null;

		App::sql()->delete("DELETE FROM em_schedule WHERE em_light_id = '$escaped_id' AND manual = 1;");

		// Update EM server
		if($mid && $pi_serial) App::sql("monitoring:$mid")->delete("DELETE FROM em_schedule_$pi_serial WHERE em_light_id = '$escaped_id' AND manual = 1;");

		if($manual_function_datetime) {
			App::insert_ignore('em_schedule', [
				'em_light_id' => $id,
				'datetime' => App::timezone($manual_function_datetime, $building->info->timezone, 'UTC'),
				'test_type' => 'function',
				'manual' => 1,
				'dali_address' => $dali_address
			]);

			// Update EM server
			if($mid && $pi_serial) {
				App::insert_ignore("em_schedule_$pi_serial@monitoring:$mid", [
					'em_light_id' => $id,
					'datetime' => App::timezone($manual_function_datetime, $building->info->timezone, 'UTC'),
					'test_type' => 'function',
					'manual' => 1,
					'dali_address' => $dali_address
				]);
			}
		}

		if($manual_duration_datetime) {
			App::insert_ignore('em_schedule', [
				'em_light_id' => $id,
				'datetime' => App::timezone($manual_duration_datetime, $building->info->timezone, 'UTC'),
				'test_type' => 'duration',
				'manual' => 1,
				'dali_address' => $dali_address
			]);

			// Update EM server
			if($mid && $pi_serial) {
				App::insert_ignore("em_schedule_$pi_serial@monitoring:$mid", [
					'em_light_id' => $id,
					'datetime' => App::timezone($manual_duration_datetime, $building->info->timezone, 'UTC'),
					'test_type' => 'duration',
					'manual' => 1,
					'dali_address' => $dali_address
				]);
			}
		}

		return $this->success();
	}

	public function get_building_faults() {
		$result = [];

		$building_id = App::get('id', 0, true);
		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		$result['building'] = [ 'id' => $building->id, 'description' => $building->info->description ];
		$result['building_count'] = $this->get_building_count();
		if($result['building_count'] == 0) return $this->error('No buildings found.');

		$r = App::sql()->query(
			"SELECT
				emf.em_light_id,
				emf.fault_datetime,
				emf.action_safeguard,
				emf.action_rectify,
				emf.function_test_failed,
				emf.duration_test_failed,
				emf.circuit_failure,
				emf.battery_duration_failure,
				emf.battery_failure,
				emf.emergency_lamp_failure,
				l.description AS light_description,
				t.icon AS type_icon,
				a.description AS area_description,
				a.display_order AS area_display_order,
				f.display_order AS floor_display_order
			FROM em_fault AS emf
			JOIN em_light AS l ON l.id = emf.em_light_id
			JOIN em_light_type AS t ON t.id = l.type_id
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$building_id'
			ORDER BY floor_display_order, area_display_order, light_description;
		", MySQL::QUERY_ASSOC) ?: [];

		$result['active_faults'] = array_map(function($l) use ($building) {
			if($l['fault_datetime']) $l['fault_datetime'] = App::timezone($l['fault_datetime'], 'UTC', $building->info->timezone);
		}, $r);

		// Get automatically resolved faults

		$r = App::sql()->query(
			"SELECT
				emf.id,
				emf.em_light_id,
				emf.fault_datetime,
				emf.repair_datetime,
				emf.action_safeguard,
				emf.action_rectify,
				emf.function_test_failed,
				emf.duration_test_failed,
				emf.circuit_failure,
				emf.battery_duration_failure,
				emf.battery_failure,
				emf.emergency_lamp_failure,
				l.description AS light_description,
				t.icon AS type_icon,
				a.description AS area_description,
				a.display_order AS area_display_order,
				f.display_order AS floor_display_order
			FROM em_fault_history AS emf
			JOIN em_light AS l ON l.id = emf.em_light_id
			JOIN em_light_type AS t ON t.id = l.type_id
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$building_id' AND emf.repair_user_id IS NULL
			ORDER BY emf.repair_datetime DESC;
		", MySQL::QUERY_ASSOC) ?: [];

		$result['resolved_faults'] = array_map(function($l) use ($building) {
			if($l['fault_datetime']) $l['fault_datetime'] = App::timezone($l['fault_datetime'], 'UTC', $building->info->timezone);
			if($l['repair_datetime']) $l['repair_datetime'] = App::timezone($l['repair_datetime'], 'UTC', $building->info->timezone);
		}, $r);

		// Get repaired faults

		$r = App::sql()->query(
			"SELECT
				emf.id,
				emf.em_light_id,
				emf.fault_datetime,
				emf.repair_datetime,
				emf.repair_user_id,
				emf.action_safeguard,
				emf.action_rectify,
				emf.function_test_failed,
				emf.duration_test_failed,
				emf.circuit_failure,
				emf.battery_duration_failure,
				emf.battery_failure,
				emf.emergency_lamp_failure,
				l.description AS light_description,
				t.icon AS type_icon,
				a.description AS area_description,
				a.display_order AS area_display_order,
				f.display_order AS floor_display_order,
				u.name AS repair_user_name
			FROM em_fault_history AS emf
			JOIN em_light AS l ON l.id = emf.em_light_id
			JOIN em_light_type AS t ON t.id = l.type_id
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN userdb AS u ON u.id = emf.repair_user_id
			WHERE f.building_id = '$building_id' AND emf.repair_user_id IS NOT NULL
			ORDER BY emf.repair_datetime DESC;
		", MySQL::QUERY_ASSOC) ?: [];

		$result['repaired_faults'] = array_map(function($l) use ($building) {
			if($l['fault_datetime']) $l['fault_datetime'] = App::timezone($l['fault_datetime'], 'UTC', $building->info->timezone);
			if($l['repair_datetime']) $l['repair_datetime'] = App::timezone($l['repair_datetime'], 'UTC', $building->info->timezone);
		}, $r);

		return $this->success($result);
	}

}
