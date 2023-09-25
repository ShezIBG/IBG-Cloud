<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_overview() {
		$buildings = Permission::list_buildings([ 'with' => Permission::CLIMATE_ENABLED ]);
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

		$device_types = App::sql('climate')->query(
			"SELECT
				ch.building_id,
				SUM(IF(ms.category = 'wall-mounted', 1, 0)) AS type_w,
				SUM(IF(ms.category = 'ducted', 1, 0)) AS type_d,
				SUM(IF(ms.category = 'outdoor', 1, 0)) AS type_o,
				SUM(IF(ms.category = 'ceiling', 1, 0)) AS type_c
			FROM coolplug AS cp
			JOIN coolhub AS ch ON ch.id = cp.coolhub_id AND ch.active = 1
			JOIN ac_model_series AS ms ON ms.id = cp.model_series_id
			WHERE cp.active = 1 AND ch.building_id IN ($building_list)
			GROUP BY ch.building_id;
		");

		// Fake ac_demand flags in DB
		App::sql('climate')->update(
			"UPDATE coolplug SET ac_demand = CASE
				WHEN ac_onoff = 0 THEN 0
				WHEN ac_mode = 'cool' AND ac_room_temp - ac_setpoint >= 0 THEN 1
				WHEN ac_mode = 'heat' AND ac_setpoint - ac_room_temp >= 0 THEN 1
				WHEN ac_mode = 'auto' AND ABS(ac_setpoint - ac_room_temp) > 0 THEN 1
				WHEN ac_mode IN ('dry', 'fan') THEN 1
				ELSE 0
			END;
		");

		$device_statuses = App::sql('climate')->query_row(
			"SELECT
				SUM(IF(COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') <> '', 1, 0)) AS faulty,
				SUM(IF(cp.ac_demand = 0 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '', 1, 0)) AS inactive,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'cool' OR (cp.ac_mode = 'auto' AND cp.ac_setpoint < cp.ac_room_temp)), 1, 0)) AS cool,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'heat' OR (cp.ac_mode = 'auto' AND cp.ac_setpoint > cp.ac_room_temp)), 1, 0)) AS heat,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'dry'), 1, 0)) AS dry,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'fan'), 1, 0)) AS fan
			FROM coolplug AS cp
			JOIN coolhub AS ch ON ch.id = cp.coolhub_id AND ch.active = 1
			WHERE cp.active = 1 AND ch.building_id IN ($building_list);
		");

		$cnt = count($building_details);

		$result = [
			'history' => Climate::get_history_with_details("WHERE h.building_id IN ($building_list) ORDER BY h.datetime DESC LIMIT 20"),
			'device_types' => $device_types ?: [],
			'device_statuses' => $device_statuses ?: [],
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
		$buildings = Permission::find_buildings([ 'with' => Permission::CLIMATE_ENABLED ]) ?: [];
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

		$device_types = App::sql('climate')->query_row(
			"SELECT
				ch.building_id,
				SUM(IF(ms.category = 'wall-mounted', 1, 0)) AS type_w,
				SUM(IF(ms.category = 'ducted', 1, 0)) AS type_d,
				SUM(IF(ms.category = 'outdoor', 1, 0)) AS type_o,
				SUM(IF(ms.category = 'ceiling', 1, 0)) AS type_c
			FROM coolplug AS cp
			JOIN coolhub AS ch ON ch.id = cp.coolhub_id AND ch.active = 1
			JOIN ac_model_series AS ms ON ms.id = cp.model_series_id
			WHERE ch.building_id = '$building_id' AND cp.active = 1;
		");
		$result['device_types'] = $device_types ?: [
			'building_id' => $building_id,
			'type_w' => 0,
			'type_d' => 0,
			'type_o' => 0,
			'type_c' => 0
		];

		// Fake ac_demand flags in DB
		App::sql('climate')->update(
			"UPDATE coolplug SET ac_demand = CASE
				WHEN ac_onoff = 0 THEN 0
				WHEN ac_mode = 'cool' AND ac_room_temp - ac_setpoint >= 0 THEN 1
				WHEN ac_mode = 'heat' AND ac_setpoint - ac_room_temp >= 0 THEN 1
				WHEN ac_mode = 'auto' AND ABS(ac_setpoint - ac_room_temp) > 0 THEN 1
				WHEN ac_mode IN ('dry', 'fan') THEN 1
				ELSE 0
			END;
		");

		$device_statuses = App::sql('climate')->query_row(
			"SELECT
				SUM(IF(COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') <> '', 1, 0)) AS faulty,
				SUM(IF(cp.ac_demand = 0 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '', 1, 0)) AS inactive,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'cool' OR (cp.ac_mode = 'auto' AND cp.ac_setpoint < cp.ac_room_temp)), 1, 0)) AS cool,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'heat' OR (cp.ac_mode = 'auto' AND cp.ac_setpoint > cp.ac_room_temp)), 1, 0)) AS heat,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'dry'), 1, 0)) AS dry,
				SUM(IF(cp.ac_demand = 1 AND COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') = '' AND (cp.ac_mode = 'fan'), 1, 0)) AS fan
			FROM coolplug AS cp
			JOIN coolhub AS ch ON ch.id = cp.coolhub_id AND ch.active = 1
			WHERE ch.building_id = '$building_id' AND cp.active = 1;
		");
		$result['device_statuses'] = $device_statuses ?: [
			'faulty' => 0,
			'inactive' => 0,
			'cool' => 0,
			'heat' => 0,
			'dry' => 0,
			'fan' => 0
		];

		$schedules = App::sql('climate')->query(
			"SELECT
				ws.id, ws.description,
				COUNT(cp.id) AS device_count,
				MIN(cp.min_setpoint) AS device_min_setpoint,
				MAX(cp.max_setpoint) AS device_max_setpoint,
				i.min_setpoint AS schedule_min_setpoint,
				i.max_setpoint AS schedule_max_setpoint
			FROM ac_weekly_schedule AS ws
			LEFT JOIN coolplug AS cp ON cp.weekly_schedule_id = ws.id
			LEFT JOIN (
				SELECT weekly_schedule_id, MIN(ac_setpoint) AS min_setpoint, MAX(ac_setpoint) AS max_setpoint FROM ac_weekly_schedule_item GROUP BY weekly_schedule_id
			) AS i ON i.weekly_schedule_id = ws.id
			WHERE ws.building_id = '$building_id' AND ws.active = 1
			GROUP BY ws.id, ws.description
			ORDER BY ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new ClimateWeeklySchedule($s['id']);
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
		$result['history'] = Climate::get_history_with_details("WHERE h.building_id = '$building_id' ORDER BY h.datetime DESC LIMIT 20");
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

		$schedules = App::sql('climate')->query(
			"SELECT
				ws.id, ws.description,
				COUNT(cp.id) AS device_count,
				MIN(cp.min_setpoint) AS device_min_setpoint,
				MAX(cp.max_setpoint) AS device_max_setpoint,
				i.min_setpoint AS schedule_min_setpoint,
				i.max_setpoint AS schedule_max_setpoint
			FROM ac_weekly_schedule AS ws
			LEFT JOIN coolplug AS cp ON cp.weekly_schedule_id = ws.id
			LEFT JOIN (
				SELECT weekly_schedule_id, MIN(ac_setpoint) AS min_setpoint, MAX(ac_setpoint) AS max_setpoint FROM ac_weekly_schedule_item GROUP BY weekly_schedule_id
			) AS i ON i.weekly_schedule_id = ws.id
			WHERE ws.building_id = '$building_id' AND ws.active = 1
			GROUP BY ws.id, ws.description
			ORDER BY ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new ClimateWeeklySchedule($s['id']);
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
		$data = App::json();
		$id = App::insert('ac_weekly_schedule@climate', $data);

		App::insert('ac_history@climate', [
			'building_id' => $data['building_id'],
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_create'
		]);

		return $this->success($id);
	}

	public function get_schedule() {
		$id = App::get('id', 0, true);
		$record = App::select('ac_weekly_schedule@climate', $id);
		if(!$record) return $this->error('Schedule not found.');

		$items = App::sql('climate')->query(
			"SELECT *
			FROM ac_weekly_schedule_item
			WHERE weekly_schedule_id = '$id'
			ORDER BY time;
		");

		$info = App::sql('climate')->query_row(
			"SELECT
				MIN(cp.min_setpoint) AS device_min_setpoint,
				MAX(cp.max_setpoint) AS device_max_setpoint,
				i.min_setpoint AS schedule_min_setpoint,
				i.max_setpoint AS schedule_max_setpoint
			FROM ac_weekly_schedule AS ws
			LEFT JOIN coolplug AS cp ON cp.weekly_schedule_id = ws.id
			LEFT JOIN (
				SELECT weekly_schedule_id, MIN(ac_setpoint) AS min_setpoint, MAX(ac_setpoint) AS max_setpoint FROM ac_weekly_schedule_item GROUP BY weekly_schedule_id
			) AS i ON i.weekly_schedule_id = ws.id
			WHERE ws.id = '$id';
		", MySQL::QUERY_ASSOC) ?: [];

		return $this->success([
			'record' => $record,
			'info' => $info,
			'items' => $items ?: []
		]);
	}

	public function delete_schedule() {
		$id = App::get('id', 0, true);
		$record = App::select('ac_weekly_schedule@climate', $id);
		if(!$record) return $this->error('Schedule not found.');

		App::update('ac_weekly_schedule@climate', $id, [ 'active' => 0 ]);

		App::insert('ac_history@climate', [
			'building_id' => $record['building_id'],
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_delete'
		]);

		Climate::rebuild_weekly_schedule($id);

		$list = App::sql('climate')->query("SELECT id FROM coolplug WHERE weekly_schedule_id = '$id' AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			App::insert('ac_history@climate', [
				'building_id' => $record['building_id'],
				'user_id' => App::user()->id,
				'weekly_schedule_id' => null,
				'coolplug_id' => $item['id'],
				'event' => 'device_schedule'
			]);
		}

		App::sql('climate')->update("UPDATE coolplug SET weekly_schedule_id = NULL WHERE weekly_schedule_id = '$id';");

		return $this->success();
	}

	public function save_schedule() {
		$data = App::json();
		$id = $data['record']['id'];

		foreach($data['items'] as $item) {
			if(!$item['time']) return $this->error('Please fill in all schedule fields.');

			if($item['ac_onoff']) {
				if(!$item['ac_setpoint'] || !$item['ac_mode'] || !$item['ac_fanspeed'] || !$item['ac_swing']) {
					return $this->error('Please fill in all schedule fields.');
				}
			}
		}

		$record = App::select('ac_weekly_schedule@climate', $id);
		if(!$record) return $this->error('Schedule not found.');

		App::sql('climate')->delete("DELETE FROM ac_weekly_schedule_item WHERE weekly_schedule_id = '$id';");
		App::update('ac_weekly_schedule@climate', $id, [
			'description' => $data['record']['description'],
			'off_on_holidays' => $data['record']['off_on_holidays'] ? 1 : 0
		]);

		foreach($data['items'] as $item) {
			App::insert('ac_weekly_schedule_item@climate', [
				'weekly_schedule_id' => $id,
				'day' => $item['day'],
				'time' => $item['time'],
				'ac_setpoint' => $item['ac_setpoint'],
				'ac_onoff' => $item['ac_onoff'] ? 1 : 0,
				'ac_mode' => $item['ac_mode'],
				'ac_fanspeed' => $item['ac_fanspeed'],
				'ac_swing' => $item['ac_swing']
			]);
		}

		App::insert('ac_history@climate', [
			'building_id' => $record['building_id'],
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_update'
		]);

		Climate::rebuild_weekly_schedule($id);

		return $this->success();
	}

	public function list_devices() {
		$result = [];
		$building_id = App::get('building_id', 0, true);

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
			WHERE fp.building_id = '$building_id' AND item_type = 'coolplug';
		") ?: [];

		// Fake ac_demand flags in DB
		App::sql('climate')->update(
			"UPDATE coolplug SET ac_demand = CASE
				WHEN ac_onoff = 0 THEN 0
				WHEN ac_mode = 'cool' AND ac_room_temp - ac_setpoint >= 0 THEN 1
				WHEN ac_mode = 'heat' AND ac_setpoint - ac_room_temp >= 0 THEN 1
				WHEN ac_mode = 'auto' AND ABS(ac_setpoint - ac_room_temp) > 0 THEN 1
				WHEN ac_mode IN ('dry', 'fan') THEN 1
				ELSE 0
			END;
		");

		$result['devices'] = App::sql('climate')->query(
			"SELECT
				cp.*, ms.category,
				CASE
					WHEN COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') <> '' THEN 'faulty'
					WHEN cp.ac_demand = 0 THEN 'inactive'
					WHEN cp.ac_mode = 'auto' AND cp.ac_setpoint < cp.ac_room_temp THEN 'cool'
					WHEN cp.ac_mode = 'auto' AND cp.ac_setpoint > cp.ac_room_temp THEN 'heat'
					WHEN cp.ac_mode = 'auto' THEN 'inactive'
					ELSE COALESCE(cp.ac_mode, 'inactive')
				END AS show_mode
			FROM coolplug AS cp
			JOIN coolhub AS ch ON ch.id = cp.coolhub_id AND ch.active = 1
			LEFT JOIN ac_model_series AS ms ON ms.id = cp.model_series_id
			WHERE ch.building_id = '$building_id' AND cp.active = 1;
		") ?: [];

		return $this->success($result);
	}

	public function get_device() {
		$id = App::get('id', 0, true);

		$record = App::select('coolplug@climate', $id);
		if(!$record) return $this->error('Device not found.');

		// Fake ac_demand flags in DB
		App::sql('climate')->update(
			"UPDATE coolplug SET ac_demand = CASE
				WHEN ac_onoff = 0 THEN 0
				WHEN ac_mode = 'cool' AND ac_room_temp - ac_setpoint >= 0 THEN 1
				WHEN ac_mode = 'heat' AND ac_setpoint - ac_room_temp >= 0 THEN 1
				WHEN ac_mode = 'auto' AND ABS(ac_setpoint - ac_room_temp) > 0 THEN 1
				WHEN ac_mode IN ('dry', 'fan') THEN 1
				ELSE 0
			END;
		");

		$info = App::sql('climate')->query_row(
			"SELECT
				cp.*, ms.category, ch.building_id,
				CASE
					WHEN COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') <> '' THEN 'faulty'
					WHEN cp.ac_demand = 0 THEN 'inactive'
					WHEN cp.ac_mode = 'auto' AND cp.ac_setpoint < cp.ac_room_temp THEN 'cool'
					WHEN cp.ac_mode = 'auto' AND cp.ac_setpoint > cp.ac_room_temp THEN 'heat'
					WHEN cp.ac_mode = 'auto' THEN 'inactive'
					ELSE COALESCE(cp.ac_mode, 'inactive')
				END AS show_mode
			FROM coolplug AS cp
			JOIN coolhub AS ch ON ch.id = cp.coolhub_id AND ch.active = 1
			LEFT JOIN ac_model_series AS ms ON ms.id = cp.model_series_id
			WHERE cp.id = '$id' AND cp.active = 1
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		$now = date('Y-m-d H:i:s');
		$hold = null;
		if($record['coolhub_id']) {
			$hold = App::sql('climate')->query_row(
				"SELECT * FROM ac_hold
				WHERE coolhub_id = '$record[coolhub_id]' AND coolplug_id = '$record[coolplug_id]' AND '$now' BETWEEN datetime_start AND datetime_end
				LIMIT 1;
			", MySQL::QUERY_ASSOC);
		}

		if(!$hold) $hold = [
			'id' => 0,
			'datetime_start' => null,
			'datetime_end' => null,
			'coolhub_id' => $record['coolhub_id'],
			'coolplug_id' => $record['coolplug_id'],
			'ac_setpoint' => null,
			'ac_onoff' => null,
			'ac_mode' => null,
			'ac_fanspeed' => null,
			'ac_swing' => null
		];

		if($hold['ac_setpoint'] === null) $hold['ac_setpoint'] = $record['ac_setpoint'] ?: 20;
		if($hold['ac_onoff'] === null) $hold['ac_onoff'] = $record['ac_onoff'];
		if($hold['ac_mode'] === null) $hold['ac_mode'] = $record['ac_mode'];
		if($hold['ac_fanspeed'] === null) $hold['ac_fanspeed'] = $record['ac_fanspeed'];
		if($hold['ac_swing'] === null) $hold['ac_swing'] = $record['ac_swing'];

		$hold['coolplug_table_id'] = $id;

		if($info) {
			$building = App::select('building', $info['building_id']);
			if($building) {
				$tz = Eticom::find_timezone_id($building['timezone']);
				if($hold['datetime_start']) $hold['datetime_start'] = App::timezone($hold['datetime_start'], 'UTC', $tz);
				if($hold['datetime_end']) $hold['datetime_end'] = App::timezone($hold['datetime_end'], 'UTC', $tz);
			}
		}

		return $this->success([
			'record' => $record,
			'info' => $info,
			'hold' => $hold
		]);
	}

	public function set_hold() {
		$data = App::json();

		// Create record
		$record = $data;
		$record = App::keep($record, ['coolplug_table_id', 'ac_setpoint', 'ac_onoff', 'ac_mode', 'ac_fanspeed', 'ac_swing', 'minutes']);
		$record = App::ensure($record, ['coolplug_table_id', 'minutes'], 0);
		$record = App::ensure($record, ['ac_setpoint', 'ac_onoff', 'ac_mode', 'ac_fanspeed', 'ac_swing'], null);

		$id = $record['coolplug_table_id'];
		$plug = App::select('coolplug@climate', $id);
		if(!$plug) return $this->error('Device not found.');

		$hub = App::select('coolhub@climate', $plug['coolhub_id']);
		if(!$hub) return $this->error('Hub not found.');

		if($record['ac_onoff'] === null) return $this->error('Please select on/off state.');
		if($record['ac_onoff']) {
			if($record['ac_setpoint'] === null) return $this->error('Please select a valid set point.');
			if($record['ac_mode'] === null) return $this->error('Please select a valid operation mode.');
			if($record['ac_fanspeed'] === null) return $this->error('Please select a valid fan speed.');
			if($record['ac_swing'] === null) return $this->error('Please select a valid louvre position.');
		} else {
			$record['ac_setpoint'] = null;
			$record['ac_mode'] = null;
			$record['ac_fanspeed'] = null;
			$record['ac_swing'] = null;
		}
		if($record['minutes'] <= 0) return $this->error('Please select a valid hold length.');

		$date_from = date('Y-m-d H:i:s');
		$date_to = date('Y-m-d H:i:s', strtotime("+$record[minutes] minutes", strtotime($date_from)));

		App::sql('climate')->delete(
			"DELETE FROM ac_hold
			WHERE coolhub_id = '$plug[coolhub_id]' AND coolplug_id = '$plug[coolplug_id]' AND '$date_from' BETWEEN datetime_start AND datetime_end;
		", MySQL::QUERY_ASSOC);

		App::insert('ac_hold@climate', [
			'datetime_start' => $date_from,
			'datetime_end' => $date_to,
			'coolhub_id' => $plug['coolhub_id'],
			'coolplug_id' => $plug['coolplug_id'],
			'ac_setpoint' => $record['ac_setpoint'],
			'ac_onoff' => $record['ac_onoff'] ? 1 : 0,
			'ac_mode' => $record['ac_mode'],
			'ac_fanspeed' => $record['ac_fanspeed'],
			'ac_swing' => $record['ac_swing']
		]);

		App::insert('ac_history@climate', [
			'building_id' => $hub['building_id'],
			'user_id' => App::user()->id,
			'coolplug_id' => $plug['id'],

			'hold_start' => $date_from,
			'hold_end' => $date_to,
			'hold_setpoint' => $record['ac_setpoint'],
			'hold_onoff' => $record['ac_onoff'] ? 1 : 0,
			'hold_mode' => $record['ac_mode'],
			'hold_fanspeed' => $record['ac_fanspeed'],
			'hold_swing' => $record['ac_swing'],

			'event' => 'device_hold'
		]);

		return $this->success();
	}

	// Remove current hold for plug
	public function remove_hold() {
		$id = App::get('id', 0, true);

		$plug = App::select('coolplug@climate', $id);
		if(!$plug) return $this->error('Device not found.');

		$now = date('Y-m-d H:i:s');
		App::sql('climate')->delete(
			"DELETE FROM ac_hold
			WHERE coolhub_id = '$plug[coolhub_id]' AND coolplug_id = '$plug[coolplug_id]' AND '$now' BETWEEN datetime_start AND datetime_end;
		", MySQL::QUERY_ASSOC);

		return $this->success();
	}

	public function create_hold() {
		$data = App::json();

		// Create record
		$record = $data;
		$record = App::keep($record, ['coolplug_table_id', 'ac_setpoint', 'ac_onoff', 'ac_mode', 'ac_fanspeed', 'ac_swing', 'datetime_start', 'datetime_end']);
		$record = App::ensure($record, ['coolplug_table_id'], 0);
		$record = App::ensure($record, ['ac_setpoint', 'ac_onoff', 'ac_mode', 'ac_fanspeed', 'ac_swing', 'datetime_start', 'datetime_end'], null);

		$id = $record['coolplug_table_id'];
		$plug = App::select('coolplug@climate', $id);
		if(!$plug) return $this->error('Device not found.');

		$hub = App::select('coolhub@climate', $plug['coolhub_id']);
		if(!$hub) return $this->error('Hub not found.');

		$building = App::select('building', $hub['building_id']);
		if(!$building) return $this->error('Building not found.');

		if($record['ac_onoff'] === null) return $this->error('Please select on/off state.');
		if($record['ac_onoff']) {
			if($record['ac_setpoint'] === null) return $this->error('Please select a valid set point.');
			if($record['ac_mode'] === null) return $this->error('Please select a valid operation mode.');
			if($record['ac_fanspeed'] === null) return $this->error('Please select a valid fan speed.');
			if($record['ac_swing'] === null) return $this->error('Please select a valid louvre position.');
		} else {
			$record['ac_setpoint'] = null;
			$record['ac_mode'] = null;
			$record['ac_fanspeed'] = null;
			$record['ac_swing'] = null;
		}
		if(!$record['datetime_start']) return $this->error('Please select a valid start time.');
		if(!$record['datetime_end']) return $this->error('Please select a valid end time.');
		if(strtotime($record['datetime_start']) >= strtotime($record['datetime_end'])) return $this->error('Start time must be before end time.');

		// Check if setpoint is in range
		if($record['ac_setpoint'] !== null) {
			$pass = true;
			if($plug['min_setpoint'] && $record['ac_setpoint'] < $plug['min_setpoint']) $pass = false;
			if($plug['max_setpoint'] && $record['ac_setpoint'] > $plug['max_setpoint']) $pass = false;

			if(!$pass) {
				$range = '';
				if($plug['min_setpoint'] && $plug['max_setpoint']) {
					$range = "$plug[min_setpoint] - $plug[max_setpoint]";
				} else if($plug['min_setpoint']) {
					$range = "> $plug[min_setpoint]";
				} else if($plug['max_setpoint']) {
					$range = "< $plug[max_setpoint]";
				}

				return $this->error("Invalid set point. Valid range: $range °C");
			}
		}

		// Convert times from local to UTC
		$record['datetime_start'] = App::timezone($record['datetime_start'], $building['timezone'], 'UTC');
		$record['datetime_end'] = App::timezone($record['datetime_end'], $building['timezone'], 'UTC');

		// Check if there are overlapping holds
		$overlap = App::sql('climate')->query(
			"SELECT id
			FROM ac_hold
			WHERE coolhub_id = '$plug[coolhub_id]' AND coolplug_id = '$plug[coolplug_id]' AND (
					(datetime_start >= '$record[datetime_start]' AND datetime_start <= '$record[datetime_end]')
					OR (datetime_end >= '$record[datetime_start]' AND datetime_end <= '$record[datetime_end]')
					OR (datetime_start <= '$record[datetime_start]' AND datetime_end >= '$record[datetime_end]')
				);
		");

		if($overlap) return $this->error('Temporary holds cannot overlap.');

		App::insert('ac_hold@climate', [
			'datetime_start' => $record['datetime_start'],
			'datetime_end' => $record['datetime_end'],
			'coolhub_id' => $plug['coolhub_id'],
			'coolplug_id' => $plug['coolplug_id'],
			'ac_setpoint' => $record['ac_setpoint'],
			'ac_onoff' => $record['ac_onoff'] ? 1 : 0,
			'ac_mode' => $record['ac_mode'],
			'ac_fanspeed' => $record['ac_fanspeed'],
			'ac_swing' => $record['ac_swing']
		]);

		App::insert('ac_history@climate', [
			'building_id' => $hub['building_id'],
			'user_id' => App::user()->id,
			'coolplug_id' => $plug['id'],

			'hold_start' => $record['datetime_start'],
			'hold_end' => $record['datetime_end'],
			'hold_setpoint' => $record['ac_setpoint'],
			'hold_onoff' => $record['ac_onoff'] ? 1 : 0,
			'hold_mode' => $record['ac_mode'],
			'hold_fanspeed' => $record['ac_fanspeed'],
			'hold_swing' => $record['ac_swing'],

			'event' => 'device_hold'
		]);

		return $this->success();
	}

	public function update_device_schedule() {
		$id = App::get('id', 0, true);
		$scheduleId = App::get('schedule', 0, true);

		if(!$scheduleId) $scheduleId = null;

		$plug = App::select('coolplug@climate', $id);
		if(!$plug) return $this->error('Device not found.');

		$hub = App::select('coolhub@climate', $plug['coolhub_id']);
		if(!$hub) return $this->error('Hub not found.');

		App::update('coolplug@climate', $id, [
			'weekly_schedule_id' => $scheduleId
		]);

		App::insert('ac_history@climate', [
			'building_id' => $hub['building_id'],
			'user_id' => App::user()->id,
			'coolplug_id' => $plug['id'],
			'weekly_schedule_id' => $scheduleId,
			'event' => 'device_schedule'
		]);

		Climate::rebuild_coolplug_schedule($id);

		return $this->success();
	}

	public function delete_hold() {
		$id = App::get('id', 0, true);

		$now = date('Y-m-d H:i:s');
		App::sql('climate')->delete("DELETE FROM ac_hold WHERE id = '$id';", MySQL::QUERY_ASSOC);

		return $this->success();
	}

	public function get_device_details() {
		$id = App::get('id', 0, true);

		$record = App::select('coolplug@climate', $id);
		if(!$record) return $this->error('Device not found.');

		// Fake ac_demand flags in DB
		App::sql('climate')->update(
			"UPDATE coolplug SET ac_demand = CASE
				WHEN ac_onoff = 0 THEN 0
				WHEN ac_mode = 'cool' AND ac_room_temp - ac_setpoint >= 0 THEN 1
				WHEN ac_mode = 'heat' AND ac_setpoint - ac_room_temp >= 0 THEN 1
				WHEN ac_mode = 'auto' AND ABS(ac_setpoint - ac_room_temp) > 0 THEN 1
				WHEN ac_mode IN ('dry', 'fan') THEN 1
				ELSE 0
			END;
		");

		$info = App::sql('climate')->query_row(
			"SELECT
				cp.*,
				ms.category, ms.model_series,
				m.desc AS manufacturer_description,
				ch.building_id,
				CASE
					WHEN COALESCE(NULLIF(cp.ac_error_code, 'ok'), '') <> '' THEN 'faulty'
					WHEN cp.ac_demand = 0 THEN 'inactive'
					WHEN cp.ac_mode = 'auto' AND cp.ac_setpoint < cp.ac_room_temp THEN 'cool'
					WHEN cp.ac_mode = 'auto' AND cp.ac_setpoint > cp.ac_room_temp THEN 'heat'
					WHEN cp.ac_mode = 'auto' THEN 'inactive'
					ELSE COALESCE(cp.ac_mode, 'inactive')
				END AS show_mode,
				ch.building_id,
				cp.area_id
			FROM coolplug AS cp
			JOIN coolhub AS ch ON ch.id = cp.coolhub_id AND ch.active = 1
			LEFT JOIN ac_model_series AS ms ON ms.id = cp.model_series_id
			LEFT JOIN ac_manufacturer AS m ON m.id = ms.manufacturer_id
			WHERE cp.id = '$id' AND cp.active = 1
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$info) return $this->error('Device not found.');

		$location = App::sql()->query_row(
			"SELECT
				a.description AS area_description,
				f.description AS floor_description,
				b.description AS building_description
			FROM area AS a
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			WHERE a.id = '$info[area_id]'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		$now = date('Y-m-d H:i:s');
		$holds = [];
		if($record['coolhub_id']) {
			$holds = App::sql('climate')->query(
				"SELECT
					*,
					IF('$now' BETWEEN datetime_start AND datetime_end, 1, 0) AS active
				FROM ac_hold
				WHERE coolhub_id = '$record[coolhub_id]' AND coolplug_id = '$record[coolplug_id]' AND datetime_end >= '$now'
				ORDER BY datetime_start;
			", MySQL::QUERY_ASSOC) ?: [];
		}

		// Update holds to local timezone for display
		$building = App::select('building', $info['building_id']);
		if($building) {
			$tz = Eticom::find_timezone_id($building['timezone']);
			foreach($holds as &$hold) {
				if($hold['datetime_start']) $hold['datetime_start'] = App::timezone($hold['datetime_start'], 'UTC', $tz);
				if($hold['datetime_end']) $hold['datetime_end'] = App::timezone($hold['datetime_end'], 'UTC', $tz);
			}
			unset($hold);
		}

		$schedules = App::sql('climate')->query("SELECT id, description FROM ac_weekly_schedule WHERE building_id = '$info[building_id]' AND active = 1 ORDER BY description;", MySQL::QUERY_ASSOC);

		return $this->success([
			'record' => $record,
			'info' => $info,
			'holds' => $holds,
			'location' => $location,
			'schedules' => $schedules ?: [],
			'history' => Climate::get_history_with_details("WHERE cp.id = '$id' ORDER BY h.datetime DESC LIMIT 100")
		]);
	}

}
