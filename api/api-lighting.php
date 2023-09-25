<?php

require_once 'shared-api.php';

// TODO: Security
class API extends SharedAPI {

	public function get_overview() {
		$buildings = Permission::list_buildings([ 'with' => Permission::LIGHTING_ENABLED ]);
		if(!$buildings) return $this->error('No buildings found.');

		$building_list = array_map(function($b) { return $b->id; }, $buildings);
		$building_list = implode(', ', $building_list);

		$building_details = App::sql()->query(
			"SELECT
				id, description, image_id, address, posttown, postcode
			FROM building
			WHERE id IN ($building_list)
			ORDER BY description;
		");

		$device_types = [];
		$device_statuses = [
			'light_on' => 0,
			'light_off' => 0
		];
		$building_statuses = [];
		$history = [];

		foreach($buildings as $b) {
			$db = "ve_dali_$b->id";

			$dt = App::sql('dali')->query(
				"SELECT
					$b->id AS building_id,
					category,
					category_icon,
					SUM(no_of_lights) AS light_count
				FROM $db.dali_light
				WHERE active = 1
				GROUP BY building_id, category, category_icon;
			", MySQL::QUERY_ASSOC);

			$ds = App::sql('dali')->query_row(
				"SELECT
					SUM(IF(light_onoff = 1, no_of_lights, 0)) AS light_on,
					SUM(IF(light_onoff = 1, 0, no_of_lights)) AS light_off
				FROM $db.dali_light
				WHERE active = 1;
			", MySQL::QUERY_ASSOC);

			$bs = App::sql('dali')->query(
				"SELECT
					$b->id AS building_id,
					COUNT(id) AS light_count,
					SUM(IF(light_onoff = 1, no_of_lights, 0)) AS light_on,
					SUM(IF(light_onoff = 1, 0, no_of_lights)) AS light_off
				FROM $db.dali_light
				WHERE active = 1
				GROUP BY building_id;
			", MySQL::QUERY_ASSOC);

			// Aggregate data

			$device_types = array_merge($device_types, $dt);
			$building_statuses = array_merge($building_statuses, $bs);
			if($ds) {
				$device_statuses['light_on'] += $ds['light_on'];
				$device_statuses['light_off'] += $ds['light_off'];
			}

			$history = array_merge($history, Lighting::get_history_with_details($b->id, "ORDER BY h.datetime DESC LIMIT 20") ?: []);
		}

		if(count($history) > 0) {
			$sort = [];
			foreach ($history as $key => $part) {
				$sort[$key] = strtotime($part['datetime']);
			}
			array_multisort($sort, SORT_DESC, $history);
			array_slice($history, 0, 20);
		}

		$cnt = count($building_details);

		$result = [
			'history' => $history,
			'device_types' => $device_types ?: [],
			'device_statuses' => $device_statuses ?: [],
			'building_statuses' => $building_statuses ?: [],
			'buildings' => array_map(function($bs) use ($cnt) {
				$uc = $cnt <= 6 ? new UserContent($bs->image_id) : null;

				return [
					'id' => $bs->id,
					'description' => $bs->description,
					'image' => ($uc && $uc->info) ? $uc->get_url() : 'assets/img/climate/building-placeholder.svg',
					'address' => $bs->address,
					'posttown' => $bs->posttown,
					'postcode' => $bs->postcode
				];
			}, $building_details ?: [])
		];

		return $this->success($result);
	}

	private function get_building_count() {
		$buildings = Permission::find_buildings([ 'with' => Permission::LIGHTING_ENABLED ]) ?: [];
		return count($buildings);
	}

	public function get_building() {
		$result = [];

		$building_id = App::get('id', 0, true);
		if(!$this->validate("building:$building_id")) return $this->validation_error;

		$result['building_count'] = $this->get_building_count();
		if($result['building_count'] == 0) return $this->error('No buildings found.');

		$building_details = App::sql()->query_row(
			"SELECT
				id, description, image_id, address, posttown, postcode
			FROM building
			WHERE id = '$building_id'
			ORDER BY description;
		");

		$db = "ve_dali_${building_id}";

		$device_types = App::sql('dali')->query(
			"SELECT category, category_icon, SUM(no_of_lights) AS light_count
			FROM $db.dali_light
			WHERE active = 1
			GROUP BY category, category_icon
			ORDER BY category;
		");
		$result['device_types'] = $device_types ?: [];

		$device_statuses = App::sql('dali')->query_row(
			"SELECT
				SUM(IF(light_onoff = 1, no_of_lights, 0)) AS light_on,
				SUM(IF(light_onoff = 1, 0, no_of_lights)) AS light_off
			FROM $db.dali_light
			WHERE active = 1;
		");
		$result['device_statuses'] = $device_statuses ?: [
			'light_on' => 0,
			'light_off' => 0
		];

		$schedules = App::sql('dali')->query(
			"SELECT
				ws.id, ws.description,
				COALESCE(SUM(l.no_of_lights), 0) AS light_count,
				COALESCE(COUNT(l.id), 0) AS node_count
			FROM $db.dali_weekly_schedule AS ws
			LEFT JOIN $db.dali_light AS l ON l.weekly_schedule_id = ws.id
			WHERE ws.active = 1
			GROUP BY ws.id, ws.description
			ORDER BY ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new LightingWeeklySchedule($building_id, $s['id']);
			$s['next_event'] = $schedule->get_next_event();
		}
		unset($s);

		$result['schedules'] = $schedules;

		$bs = $building_details;
		$uc = new UserContent($bs->image_id);
		$result['building'] = [
			'id' => $bs->id,
			'description' => $bs->description,
			'image' => ($uc && $uc->info) ? $uc->get_url() : 'assets/img/climate/building-placeholder.svg',
			'address' => $bs->address,
			'posttown' => $bs->posttown,
			'postcode' => $bs->postcode
		];

		$result['groups'] = [];
		$result['types'] = [];
		$result['group_issues'] = [];
		$result['light_issues'] = [];
		$result['faults'] = [];
		$result['history'] = Lighting::get_history_with_details($building_id, "ORDER BY h.datetime DESC LIMIT 20");
		$result['agent'] = null;

		return $this->success($result);
	}

	public function get_building_schedules() {
		$result = [];

		$building_id = App::get('id', 0, true);
		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		$result['building_count'] = $this->get_building_count();
		if($result['building_count'] == 0) return $this->error('No buildings found.');

		$result['building'] = [ 'id' => $building->id, 'description' => $building->info->description ];

		$db = "ve_dali_${building_id}";

		$schedules = App::sql('dali')->query(
			"SELECT
				ws.id, ws.description,
				COALESCE(SUM(l.no_of_lights), 0) AS light_count,
				COALESCE(COUNT(l.id), 0) AS node_count
			FROM $db.dali_weekly_schedule AS ws
			LEFT JOIN $db.dali_light AS l ON l.weekly_schedule_id = ws.id
			WHERE ws.active = 1
			GROUP BY ws.id, ws.description
			ORDER BY ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new LightingWeeklySchedule($building_id, $s['id']);
			$s['next_event'] = $schedule->get_next_event();
		}
		unset($s);

		$result['schedules'] = $schedules;
		$result['groups'] = [];
		$result['areas'] = [];
		$result['floorplans'] = [];
		$result['lights'] = [];

		return $this->success($result);
	}

	public function add_schedule() {
		$building_id = App::get('building_id', 0, true);
		$db = "ve_dali_${building_id}";

		// Get available DALI group ID
		$groups = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
		$existing = App::sql('dali')->query("SELECT dali_group_id FROM $db.dali_weekly_schedule WHERE active = 1;", MySQL::QUERY_ASSOC);
		$existing = array_map(function($item) {
			return $item['dali_group_id'];
		}, $existing ?: []);
		$groups = array_diff($groups, $existing);
		if(count($groups) === 0) return $this->error("Building is limited to 16 active schedules.");

		$dali_group_id = array_values($groups)[0];

		$data = App::json();
		$data['dali_group_id'] = $dali_group_id;
		$id = App::insert("$db.dali_weekly_schedule@dali", $data);

		App::insert("$db.dali_history@dali", [
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_create'
		]);

		return $this->success($id);
	}

	public function get_schedule() {
		$building_id = App::get('building_id', 0, true);
		$id = App::get('id', 0, true);
		$db = "ve_dali_${building_id}";

		$record = App::select("$db.dali_weekly_schedule@dali", $id);
		if(!$record) return $this->error('Schedule not found.');

		$items = App::sql('dali')->query(
			"SELECT *
			FROM $db.dali_weekly_schedule_item
			WHERE weekly_schedule_id = '$id'
			ORDER BY type, time;
		", MySQL::QUERY_ASSOC);

		return $this->success([
			'record' => $record,
			'items' => array_map(function($item) {
				$item['minutes'] = strtotime("1970-01-01 $item[time]") / 60;
				if($item['type'] === 'set-time') $item['minutes'] = 0;
				return $item;
			}, $items ?: [])
		]);
	}

	public function delete_schedule() {
		$building_id = App::get('building_id', 0, true);
		$id = App::get('id', 0, true);
		$db = "ve_dali_${building_id}";

		$record = App::select("$db.dali_weekly_schedule@dali", $id);
		if(!$record) return $this->error('Schedule not found.');

		App::update("$db.dali_weekly_schedule@dali", $id, [ 'active' => 0, 'dali_group_id' => 0 ]);

		App::insert("$db.dali_history@dali", [
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_delete'
		]);

		Lighting::rebuild_weekly_schedule($building_id, $id);

		$list = App::sql('dali')->query("SELECT id FROM $db.dali_light WHERE weekly_schedule_id = '$id' AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			App::insert("$db.dali_history@dali", [
				'user_id' => App::user()->id,
				'weekly_schedule_id' => null,
				'dali_light_id' => $item['id'],
				'event' => 'light_schedule'
			]);
		}

		App::sql('dali')->update("UPDATE $db.dali_light SET weekly_schedule_id = NULL WHERE weekly_schedule_id = '$id';");
		App::sql('dali')->update("DELETE FROM $db.dali_group_light WHERE dali_group_id = '$record[dali_group_id]';");

		Lighting::notify_schedule_change($building_id);

		return $this->success();
	}

	public function save_schedule() {
		$building_id = App::get('building_id', 0, true);
		$db = "ve_dali_${building_id}";

		$data = App::json();
		$id = $data['record']['id'];

		$items = [];

		foreach($data['items'] as $item) {
			if($item['type'] === 'set-time' && !$item['time']) return $this->error('Please fill in all schedule fields.');
			if($item['type'] !== 'set-time' && $item['minutes'] === '') return $this->error('Please fill in all schedule fields.');

			if($item['type'] !== 'set-time') {
				// Convert minutes field into time, then remove it
				if(!is_numeric($item['minutes'])) return $this->error('Invalid minutes value.');
				$item['time'] = date('H:i:s', $item['minutes'] * 60);
				unset($item['minutes']);
			}

			$items[] = $item;
		}

		$record = App::select("$db.dali_weekly_schedule@dali", $id);
		if(!$record) return $this->error('Schedule not found.');

		App::sql('dali')->delete("DELETE FROM $db.dali_weekly_schedule_item WHERE weekly_schedule_id = '$id';");
		App::update("$db.dali_weekly_schedule@dali", $id, [
			'description' => $data['record']['description'],
			'off_on_holidays' => $data['record']['off_on_holidays'] ? 1 : 0
		]);

		foreach($items as $item) {
			App::insert("$db.dali_weekly_schedule_item@dali", [
				'weekly_schedule_id' => $id,
				'day' => $item['day'],
				'time' => $item['time'],
				'type' => $item['type'],
				'light_onoff' => $item['light_onoff'] ? 1 : 0
			]);
		}

		App::insert("$db.dali_history@dali", [
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_update'
		]);

		Lighting::rebuild_weekly_schedule($building_id, $id);
		Lighting::notify_schedule_change($building_id);

		return $this->success();
	}

	public function list_devices() {
		$result = [];
		$building_id = App::get('building_id', 0, true);
		$db = "ve_dali_${building_id}";

		if(!$this->validate("building:$building_id")) return $this->validation_error;
		$building = $this->building;

		$result['building_count'] = $this->get_building_count();
		if($result['building_count'] == 0) return $this->error('No buildings found.');

		$result['building'] = [ 'id' => $building->id, 'description' => $building->info->description ];

		$result['areas'] = App::sql()->query(
			"SELECT
				a.id, a.description
			FROM area AS a
			JOIN floor AS f ON a.floor_id = f.id
			WHERE f.building_id = '$building_id'
			ORDER BY f.display_order, a.display_order;
		") ?: [];

		$r = App::sql()->query("SELECT id, description, image_id FROM floorplan WHERE building_id = '$building->id' ORDER BY description;");
		$result['floorplans'] = array_map(function($fp) {
			$uc = new UserContent($fp->image_id);
			return [
				'id' => $fp->id,
				'description' => $fp->description,
				'image' => $uc ? $uc->get_url() : null
			];
		}, $r ?: []);

		$result['floorplan_items'] = App::sql()->query(
			"SELECT fpi.*
			FROM floorplan_item AS fpi
			JOIN floorplan AS fp ON fpi.floorplan_id = fp.id
			WHERE fp.building_id = '$building_id' AND item_type = 'dali_light';
		") ?: [];

		$result['devices'] = App::sql('dali')->query(
			"SELECT *, IF(light_onoff = 1, 'on', 'off') AS show_mode
			FROM $db.dali_light
			WHERE active = 1;
		") ?: [];

		return $this->success($result);
	}

	public function update_device_details() {
		$id = App::get('id', 0, true);
		$scheduleId = App::get('schedule', 0, true);
		$light_count = App::get('no_of_lights', 0, true);
		$building_id = App::get('building_id', 0, true);
		$db = "ve_dali_${building_id}";

		if(!is_numeric($light_count)) $light_count = 1;
		if($light_count < 1) $light_count = 1;

		if(!$scheduleId) $scheduleId = null;

		$light = App::select("$db.dali_light@dali", $id);
		if(!$light) return $this->error('Device not found.');

		$bs = App::select('building_server', $light['building_server_id']);
		if(!$bs) return $this->error('Building server not found.');

		if($scheduleId != $light['weekly_schedule_id']) {
			App::sql('dali')->delete("DELETE FROM $db.dali_group_light WHERE dali_light_id = '$id';");
			if($scheduleId) {
				$schedule = App::select("$db.dali_weekly_schedule@dali", $scheduleId);
				if(!$schedule) return $this->error('Schedule not found.');

				$dali_group_id = $schedule['dali_group_id'];
				App::sql('dali')->insert("INSERT INTO $db.dali_group_light (dali_group_id, dali_light_id) VALUES ($dali_group_id, $id);");
			}

			Lighting::notify_schedule_change($building_id);

			App::insert("$db.dali_history@dali", [
				'user_id' => App::user()->id,
				'dali_light_id' => $id,
				'weekly_schedule_id' => $scheduleId,
				'event' => 'light_schedule'
			]);
		}

		App::update("$db.dali_light@dali", $id, [
			'weekly_schedule_id' => $scheduleId,
			'no_of_lights' => $light_count
		]);

		return $this->success();
	}

	public function get_device_details() {
		$id = App::get('id', 0, true);
		$building_id = App::get('building_id', 0, true);
		$db = "ve_dali_${building_id}";

		$record = App::select("$db.dali_light@dali", $id);
		if(!$record) return $this->error('Device not found.');

		$info = App::sql('dali')->query_row(
			"SELECT
				l.*,
				IF(l.light_onoff = 1, 'on', 'off') AS show_mode
			FROM $db.dali_light AS l
			WHERE l.id = '$id' AND l.active = 1
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$info) return $this->error('Device not found.');

		$location = App::sql()->query_row(
			"SELECT
				b.id AS building_id,
				a.description AS area_description,
				f.description AS floor_description,
				b.description AS building_description
			FROM area AS a
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			WHERE a.id = '$info[area_id]'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		$schedules = App::sql('dali')->query("SELECT id, description FROM $db.dali_weekly_schedule WHERE active = 1 ORDER BY description;", MySQL::QUERY_ASSOC);

		return $this->success([
			'record' => $record,
			'info' => $info,
			'location' => $location,
			'schedules' => $schedules ?: [],
			'history' => Lighting::get_history_with_details($building_id, "WHERE l.id = '$id' ORDER BY h.datetime DESC LIMIT 100")
		]);
	}

	public function change_light_state() {
		list($building_id, $id, $state) = App::get(['building_id', 'id', 'state'], 0, true);

		$result = Lighting::change_light_state($building_id, $id, $state);
		if(!$result) return $this->error('Light not found.');

		return $this->success();
	}

	public function change_group_state() {
		list($building_id, $id, $state) = App::get(['building_id', 'id', 'state'], 0, true);

		$result = Lighting::change_group_state($building_id, $id, $state);
		if(!$result) return $this->error('Group not found.');

		return $this->success();
	}

	public function change_area_state() {
		list($building_id, $area_id, $state) = App::get(['building_id', 'id', 'state'], 0, true);
		$db = "ve_dali_${building_id}";

		// Select all lights in the area
		$lights = App::sql('dali')->query("SELECT id, weekly_schedule_id FROM $db.dali_light WHERE area_id = '$area_id';", MySQL::QUERY_ASSOC);
		if(!$lights) return $this->error('No lights found in the area.');

		$schedule_list = [];
		$light_list = array_map(function ($item) use (&$schedule_list) {
			$schedule_id = $item['weekly_schedule_id'];
			if($schedule_id && !in_array($schedule_id, $schedule_list)) $schedule_list[] = $schedule_id;
			return $item['id'];
		}, $lights);

		// Process groups
		foreach($schedule_list as $schedule_id) {
			// Get list of all lights in group
			$schedule_lights = App::sql('dali')->query("SELECT id FROM $db.dali_light WHERE weekly_schedule_id = '$schedule_id';", MySQL::QUERY_ASSOC);
			$schedule_lights = array_map(function ($item) { return $item['id']; }, $schedule_lights ?: []);

			// Check if all lights in group are in the switching list
			if(!array_diff($schedule_lights, $light_list)) {
				// All lights in list, switch whole group
				Lighting::change_group_state($building_id, $schedule_id, $state);

				$light_list = array_diff($light_list, $schedule_lights);
			}
		}

		// Process individual lights
		foreach($light_list as $light_id) {
			Lighting::change_light_state($building_id, $light_id, $state);
		}

		return $this->success();
	}

	public function is_schedule_synced() {
		$building_id = App::get('building_id', 0, true);
		if(!Lighting::check_database($building_id)) return $this->error('Database not initialised.');
		return $this->success(Lighting::is_schedule_synced($building_id));
	}

}
