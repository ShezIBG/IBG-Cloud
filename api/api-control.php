<?php

require_once 'shared-api.php';

// TODO: Security
class API extends SharedAPI {

	public function get_overview() {
		$buildings = Permission::list_buildings([ 'with' => Permission::CONTROL_ENABLED ]);
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
		
		$building_statuses = []; 
		$history = []; // TODO: History comment left from Rob

		$dt_list = [];

		foreach($buildings as $b) {
			$sql = App::sql("knx:{$b->id}");

			$device_types = $sql->query(
				"SELECT
					t.id, t.description, t.icon_text, COUNT(i.id) AS item_count
				FROM item AS i
				JOIN item_type AS t ON t.id = i.type_id
				GROUP BY t.description, t.icon_text, t.id
				ORDER BY t.description;
			") ?: [];

			$dt_list[$b->id] = $device_types;
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
			'building_statuses' => $building_statuses ?: [],
			'buildings' => array_map(function($bs) use ($cnt, $dt_list) {
				$uc = $cnt <= 6 ? new UserContent($bs->image_id) : null;

				return [
					'id' => $bs->id,
					'description' => $bs->description,
					'image' => ($uc && $uc->info) ? $uc->get_url() : 'assets/img/climate/building-placeholder.svg',
					'address' => $bs->address,
					'posttown' => $bs->posttown,
					'postcode' => $bs->postcode,
					'device_types' => $dt_list[$bs->id]
				];
			}, $building_details ?: [])
		];

		return $this->success($result);
	}

	private function get_building_count() {
		$buildings = Permission::find_buildings([ 'with' => Permission::CONTROL_ENABLED ]) ?: [];
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

		$sql = App::sql("knx:$building_id");
		if(!$sql) return $this->error('Cannot connect to database.');

		$device_types = $sql->query(
			"SELECT
				t.id, t.description, t.icon_text, COUNT(i.id) AS item_count
			FROM item AS i
			JOIN item_type AS t ON t.id = i.type_id
			GROUP BY t.description, t.icon_text, t.id
			ORDER BY t.description;
		");
		$result['device_types'] = $device_types ?: [];

		$device_statuses = $sql->query(
			"SELECT
				t.description, its.knx_datatype, its.knx_subtype,
				SUM(IF(d.baos_value = 1, 1, 0)) AS status_on,
				SUM(IF(d.baos_value = 1, 0, 1)) AS status_off
			FROM item AS i
			JOIN item_type AS t ON t.id = i.type_id
			JOIN item_slot AS s ON s.item_id = i.id AND s.slot_id = t.status_slot_id
			JOIN item_type_slot AS its ON its.id = t.status_slot_id
			JOIN device AS d ON d.id = s.knx_id
			GROUP BY t.description, its.knx_datatype, its.knx_subtype
			ORDER BY t.description;
		") ?: [];
		$result['device_statuses'] = $device_statuses;

		$schedules = [];
		$schedules = $sql->query(
			"SELECT
				ws.id, ws.description, it.description AS item_type_description,
				COUNT(i.id) AS device_count
			FROM weekly_schedule AS ws
			JOIN item_type AS it ON it.id = ws.item_type_id
			LEFT JOIN item AS i ON i.weekly_schedule_id = ws.id
			WHERE ws.active = 1
			GROUP BY ws.id, ws.description, it.description
			ORDER BY it.description, ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new ControlWeeklySchedule($building_id, $s['id']);
			$s['next_event'] = $schedule->get_next_event();
		}
		unset($s);

		$result['schedules'] = $schedules;

		$result['history'] = [];

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

		$sql = App::sql("knx:{$building->id}");

		$item_types = $sql->query(
			"SELECT id, description
			FROM item_type
			WHERE has_schedule = 1
			ORDER BY description;
		") ?: [];

		$schedules = $sql->query(
			"SELECT
				ws.id, ws.description, it.id AS item_type_id, it.description AS item_type_description,
				COUNT(i.id) AS device_count
			FROM weekly_schedule AS ws
			JOIN item_type AS it ON it.id = ws.item_type_id
			LEFT JOIN item AS i ON i.weekly_schedule_id = ws.id
			WHERE ws.active = 1
			GROUP BY ws.id, ws.description, it.id, it.description
			ORDER BY it.description, ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new ControlWeeklySchedule($building_id, $s['id']);
			$s['next_event'] = $schedule->get_next_event();
		}
		unset($s);

		$result['item_types'] = $item_types;
		$result['schedules'] = $schedules;
		$result['groups'] = [];
		$result['areas'] = [];
		$result['floorplans'] = [];
		$result['lights'] = [];

		return $this->success($result);
	}

	public function add_schedule() {
		$building_id = App::get('building_id', 0, true);
		$item_type_id = App::get('item_type_id', 0, true);

		$data = App::json();
		$data['item_type_id'] = $item_type_id;
		$id = App::insert("weekly_schedule@knx:$building_id", $data);

		return $this->success($id);
	}

	public function get_schedule() {
		$building_id = App::get('building_id', 0, true);
		$id = App::get('id', 0, true);
		$sql = App::sql("knx:$building_id");

		$record = App::select("weekly_schedule@knx:$building_id", $id);
		if(!$record) return $this->error('Schedule not found.');

		$items = $sql->query(
			"SELECT *
			FROM weekly_schedule_item
			WHERE weekly_schedule_id = '$id'
			ORDER BY type, time;
		", MySQL::QUERY_ASSOC);

		$slots = $sql->query(
			"SELECT id, description, knx_datatype, knx_subtype
			FROM item_type_slot
			WHERE type_id = '$record[item_type_id]' AND is_readonly = 0
			ORDER BY description;
		") ?: [];

		return $this->success([
			'record' => $record,
			'slots' => $slots,
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

		$sql = App::sql("knx:$building_id");

		$record = App::select("weekly_schedule@knx:$building_id", $id);
		if(!$record) return $this->error('Schedule not found.');

		App::update("weekly_schedule@knx:$building_id", $id, [ 'active' => 0 ]);

		Control::rebuild_weekly_schedule($building_id, $id);

		$sql->update("UPDATE item SET weekly_schedule_id = NULL WHERE weekly_schedule_id = '$id';");

		return $this->success();
	}

	public function save_schedule() {
		$building_id = App::get('building_id', 0, true);

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

			// Handle repeat interval
			if(!$item['repeat_minutes'] || !is_numeric($item['repeat_minutes'])) $item['repeat_minutes'] = 60;

			$items[] = $item;
		}

		$sql = App::sql("knx:$building_id");

		$record = App::select("weekly_schedule@knx:$building_id", $id);
		if(!$record) return $this->error('Schedule not found.');

		$sql->delete("DELETE FROM weekly_schedule_item WHERE weekly_schedule_id = '$id';");
		App::update("weekly_schedule@knx:$building_id", $id, [
			'description' => $data['record']['description'],
			'off_on_holidays' => $data['record']['off_on_holidays'] ? 1 : 0
		]);

		foreach($items as $item) {
			App::insert("weekly_schedule_item@knx:$building_id", [
				'weekly_schedule_id' => $id,
				'day' => $item['day'],
				'time' => $item['time'],
				'end_time' => $item['end_time'],
				'type' => $item['type'],
				'item_type_slot_id' => $item['item_type_slot_id'],
				'baos_value' => $item['baos_value'],
				'repeat_minutes' => $item['repeat_minutes']
			]);
		}

		// Rebuild schedules
		Control::rebuild_weekly_schedule($building_id, $id);

		return $this->success();
	}

	public function list_devices() {
		$result = [];
		$building_id = App::get('building_id', 0, true);
		
		// Current climate readings
		$weather = new WeatherService($building_id);
		$today = date('Y-m-d');
		$weather_today = $weather->get_hourly_weather_plot($today);
		$current_temp = 0;
		if($weather_today) {
			$details = $weather_today[$today][date('G')];
			if($details) {
				$current_temp = floor($details->temperature);
			}
		}
		if($current_temp !== 0 && $building_id == 52){
			$item_id = 41;
			//print_r($current_temp);exit;
			App::update("device@knx:$building_id", $item_id, [ 'baos_value' => $current_temp ]);
		}

		//print_r($current_temp);
		//exit;

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

		$sql = App::sql("knx:$building_id");
		if(!$sql) return $this->error('Cannot connect to database.');

		$result['floorplan_items'] = App::sql()->query(
			"SELECT fpi.*
			FROM floorplan_item AS fpi
			JOIN floorplan AS fp ON fpi.floorplan_id = fp.id
			WHERE fp.building_id = '$building_id' AND item_type = 'knx_device';
		") ?: [];

		$result['devices'] = $sql->query(
			"SELECT
				i.*,
				t.icon_type,
				t.icon_text,
				t.icon_slot_id,
				ss.knx_id AS icon_slot,
				s.knx_id,
				sts.knx_id AS status_knx_id
			FROM item AS i
			JOIN item_type AS t ON t.id = i.type_id
			LEFT JOIN item_slot AS s ON s.item_id = i.id AND s.slot_id = t.switch_slot_id
			LEFT JOIN item_slot AS ss ON ss.item_id = i.id AND ss.slot_id = t.icon_slot_id
			LEFT JOIN item_slot AS sts ON sts.item_id = i.id AND sts.slot_id = t.status_slot_id;
		", MySQL::QUERY_ASSOC) ?: [];

		$result['knx'] = $sql->query(
			"SELECT
				d.id,
				ts.knx_datatype,
				ts.knx_subtype,
				ts.is_readonly,
				d.baos_value AS value,
				i.id AS item_id,
				ts.description AS slot_description
			FROM item AS i
			JOIN item_type AS t ON t.id = i.type_id
			JOIN item_slot AS s ON s.item_id = i.id
			JOIN item_type_slot AS ts ON ts.id = s.slot_id
			JOIN device AS d ON d.id = s.knx_id
			ORDER BY i.id, ts.id;
		") ?: [];
		//print_r($result['knx']);exit;
		return $this->success($result);
	}

	public function update_device_details() {
		$id = App::get('id', 0, true);
		$scheduleId = App::get('schedule', 0, true);
		$building_id = App::get('building_id', 0, true);

		if(!$scheduleId) $scheduleId = null;

		$device = App::select("item@knx:$building_id", $id);
		if(!$device) return $this->error('Device not found.');

		App::update("item@knx:$building_id", $id, [
			'weekly_schedule_id' => $scheduleId
		]);

		if($scheduleId != $device['weekly_schedule_id']) {
			// Rebuild schedule
			Control::rebuild_building_schedule($building_id);
		}

		return $this->success();
	}

	public function get_device_details() {
		$id = App::get('id', 0, true);
		$building_id = App::get('building_id', 0, true);

		$sql = App::sql("knx:$building_id");
		if(!$sql) return $this->error('Cannot connect to database.');

		$info = $sql->query_row(
			"SELECT
				i.*,
				t.description AS type_description,
				t.icon_type,
				t.icon_text,
				t.icon_slot_id,
				s.knx_id
			FROM item AS i
			JOIN item_type AS t ON t.id = i.type_id
			LEFT JOIN item_slot AS s ON s.item_id = i.id AND s.slot_id = t.switch_slot_id
			WHERE i.id = '$id';
		", MySQL::QUERY_ASSOC) ?: [];

		$knx = $sql->query(
			"SELECT
				d.id,
				ts.description,
				ts.knx_datatype,
				ts.knx_subtype,
				ts.is_readonly,
				d.baos_value AS value
			FROM item AS i
			JOIN item_type AS t ON t.id = i.type_id
			JOIN item_slot AS s ON s.item_id = i.id
			JOIN item_type_slot AS ts ON ts.id = s.slot_id
			JOIN device AS d ON d.id = s.knx_id
			WHERE i.id = '$id'
			ORDER BY ts.id;
		") ?: [];

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

		$schedules = $sql->query("SELECT id, description FROM weekly_schedule WHERE item_type_id = '$info[type_id]' AND active = 1 ORDER BY description;", MySQL::QUERY_ASSOC);
		$history = [];

		return $this->success([
			'info' => $info,
			'knx' => $knx,
			'location' => $location,
			'schedules' => $schedules ?: [],
			'history' => $history
		]);
	}

	public function send_knx_values() {
		// TODO: Security
		$data = App::json();

		$sql = App::sql("knx:$data[building_id]");
		if(!$sql) return $this->error("Cannot connect to database.");

		foreach($data['knx'] as $item) {
			$datatype = App::escape($item['datatype']);
			$id = App::escape($item['id']);
			$value = App::escape($item['value']);

			$sql->insert("INSERT INTO todo (knx_datatype, baos_id, baos_value) VALUES ('$datatype', '$id', '$value');");
			// hack to device output
			App::update("device@knx:$data[building_id]", $id, ['baos_value' => $value]);

		}

		return $this->success();
	}

}
