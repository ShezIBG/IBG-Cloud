<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_overview() {
		$buildings = Permission::list_buildings([ 'with' => Permission::RELAY_ENABLED ]);
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

		$device_categories = App::sql('relay')->query(
			"SELECT
				d.building_id,
				d.category AS description,
				d.category_icon AS icon,
				COUNT(d.id) AS device_count
			FROM relay_end_device AS d
			WHERE d.building_id IN ($building_list)
			GROUP BY d.category, d.category_icon, d.building_id;
		");

		$device_statuses = App::sql('relay')->query_row(
			"SELECT
				SUM(IF(sp.state = 1, 1, 0)) AS state_on,
				SUM(IF(sp.state = 0, 1, 0)) AS state_off,
				SUM(IF(ip.state = 0, 1, 0)) AS mode_schedule,
				SUM(IF(ip.state = 1, 1, 0)) AS mode_override,
				0 AS faulty
			FROM relay_end_device AS d
			LEFT JOIN relay_pin AS sp ON sp.id = d.state_pin_id
			LEFT JOIN relay_pin AS ip ON ip.id = d.isolator_pin_id
			WHERE d.building_id IN ($building_list);
		");

		$cnt = count($building_details);

		$result = [
			'history' => Relay::get_history_with_details("WHERE h.building_id IN ($building_list) ORDER BY h.datetime DESC LIMIT 20"),
			'device_categories' => $device_categories ?: [],
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
		$buildings = Permission::find_buildings([ 'with' => Permission::RELAY_ENABLED ]) ?: [];
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

		$device_statuses = App::sql('relay')->query_row(
			"SELECT
				SUM(IF(sp.state = 1, 1, 0)) AS state_on,
				SUM(IF(sp.state = 0, 1, 0)) AS state_off,
				SUM(IF(ip.state = 0, 1, 0)) AS mode_schedule,
				SUM(IF(ip.state = 1, 1, 0)) AS mode_override,
				0 AS faulty
			FROM relay_end_device AS d
			LEFT JOIN relay_pin AS sp ON sp.id = d.state_pin_id
			LEFT JOIN relay_pin AS ip ON ip.id = d.isolator_pin_id
			WHERE d.building_id = '$building_id';
		");
		$result['device_statuses'] = $device_statuses ?: [
			'faulty' => 0,
			'state_on' => 0,
			'state_off' => 0,
			'mode_schedule' => 0,
			'mode_override' => 0
		];

		$schedules = App::sql('relay')->query(
			"SELECT
				ws.id, ws.description,
				COUNT(d.id) AS device_count
			FROM relay_weekly_schedule AS ws
			LEFT JOIN relay_end_device AS d ON d.weekly_schedule_id = ws.id
			WHERE ws.building_id = '$building_id' AND ws.active = 1
			GROUP BY ws.id, ws.description
			ORDER BY ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new RelayWeeklySchedule($s['id']);
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
		$result['history'] = Relay::get_history_with_details("WHERE h.building_id = '$building_id' ORDER BY h.datetime DESC LIMIT 20");
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

		$schedules = App::sql('relay')->query(
			"SELECT
				ws.id, ws.description,
				COUNT(d.id) AS device_count
			FROM relay_weekly_schedule AS ws
			LEFT JOIN relay_end_device AS d ON d.weekly_schedule_id = ws.id
			WHERE ws.building_id = '$building_id' AND ws.active = 1
			GROUP BY ws.id, ws.description
			ORDER BY ws.description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($schedules as &$s) {
			$schedule = new RelayWeeklySchedule($s['id']);
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
		$id = App::insert('relay_weekly_schedule@relay', $data);

		App::insert('relay_history@relay', [
			'building_id' => $data['building_id'],
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_create'
		]);

		return $this->success($id);
	}

	public function get_schedule() {
		$id = App::get('id', 0, true);
		$record = App::select('relay_weekly_schedule@relay', $id);
		if(!$record) return $this->error('Schedule not found.');

		$items = App::sql('relay')->query(
			"SELECT *
			FROM relay_weekly_schedule_item
			WHERE weekly_schedule_id = '$id'
			ORDER BY time;
		");

		return $this->success([
			'record' => $record,
			'items' => $items ?: []
		]);
	}

	public function delete_schedule() {
		$id = App::get('id', 0, true);
		$record = App::select('relay_weekly_schedule@relay', $id);
		if(!$record) return $this->error('Schedule not found.');

		App::update('relay_weekly_schedule@relay', $id, [ 'active' => 0 ]);

		App::insert('relay_history@relay', [
			'building_id' => $record['building_id'],
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_delete'
		]);

		Relay::rebuild_weekly_schedule($id);

		$list = App::sql('relay')->query("SELECT id FROM relay_end_device WHERE weekly_schedule_id = '$id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			App::insert('relay_history@relay', [
				'building_id' => $record['building_id'],
				'user_id' => App::user()->id,
				'weekly_schedule_id' => null,
				'end_device_id' => $item['id'],
				'event' => 'device_schedule'
			]);
		}

		App::sql('relay')->update("UPDATE relay_end_device SET weekly_schedule_id = NULL WHERE weekly_schedule_id = '$id';");

		return $this->success();
	}

	public function save_schedule() {
		$data = App::json();
		$id = $data['record']['id'];

		foreach($data['items'] as $item) {
			if(!$item['time']) return $this->error('Please fill in all schedule fields.');
		}

		$record = App::select('relay_weekly_schedule@relay', $id);
		if(!$record) return $this->error('Schedule not found.');

		App::sql('relay')->delete("DELETE FROM relay_weekly_schedule_item WHERE weekly_schedule_id = '$id';");
		App::update('relay_weekly_schedule@relay', $id, [
			'description' => $data['record']['description'],
			'off_on_holidays' => $data['record']['off_on_holidays'] ? 1 : 0
		]);

		foreach($data['items'] as $item) {
			App::insert('relay_weekly_schedule_item@relay', [
				'weekly_schedule_id' => $id,
				'day' => $item['day'],
				'time' => $item['time'],
				'new_state' => $item['new_state'] ? 1 : 0
			]);
		}

		App::insert('relay_history@relay', [
			'building_id' => $record['building_id'],
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $id,
			'event' => 'schedule_update'
		]);

		Relay::rebuild_weekly_schedule($id);

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
			WHERE fp.building_id = '$building_id' AND item_type = 'relay_end_device';
		") ?: [];

		$devices = App::sql('relay')->query(
			"SELECT
				d.*,
				sp.state AS state,
				ip.state AS override
			FROM relay_end_device AS d
			LEFT JOIN relay_pin AS sp ON sp.id = d.state_pin_id
			LEFT JOIN relay_pin AS ip ON ip.id = d.isolator_pin_id
			WHERE d.building_id = '$building_id';
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($devices as &$d) {
			$image_url = null;
			if($d['image_id']) {
				$uc = new UserContent($d['image_id']);
				if($uc->info) $image_url = $uc->get_url();
			}
			$d['image_url'] = $image_url;
		}
		unset($d);

		$result['devices'] = $devices;

		return $this->success($result);
	}

	public function get_device() {
		$id = App::get('id', 0, true);

		$record = App::select('relay_end_device@relay', $id);
		if(!$record) return $this->error('Device not found.');

		$info = App::sql('relay')->query_row(
			"SELECT
				d.*,
				sp.state AS state,
				ip.state AS override
			FROM relay_end_device AS d
			LEFT JOIN relay_pin AS sp ON sp.id = d.state_pin_id
			LEFT JOIN relay_pin AS ip ON ip.id = d.isolator_pin_id
			WHERE d.id = '$id'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		return $this->success([
			'record' => $record,
			'info' => $info
		]);
	}

	public function update_device_schedule() {
		$id = App::get('id', 0, true);
		$scheduleId = App::get('schedule', 0, true);

		if(!$scheduleId) $scheduleId = null;

		$device = App::select('relay_end_device@relay', $id);
		if(!$device) return $this->error('Device not found.');

		App::update('relay_end_device@relay', $id, [
			'weekly_schedule_id' => $scheduleId
		]);

		App::insert('relay_history@relay', [
			'building_id' => $device['building_id'],
			'user_id' => App::user()->id,
			'weekly_schedule_id' => $scheduleId,
			'end_device_id' => $id,
			'event' => 'device_schedule'
		]);

		Relay::rebuild_device_schedule($id);

		return $this->success();
	}

	public function get_device_details() {
		$id = App::get('id', 0, true);

		$record = App::select('relay_end_device@relay', $id);
		if(!$record) return $this->error('Device not found.');

		$info = App::sql('relay')->query_row(
			"SELECT
				d.*,
				sp.state AS state,
				ip.state AS override
			FROM relay_end_device AS d
			LEFT JOIN relay_pin AS sp ON sp.id = d.state_pin_id
			LEFT JOIN relay_pin AS ip ON ip.id = d.isolator_pin_id
			WHERE d.id = '$id'
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

		$schedules = App::sql('relay')->query("SELECT id, description FROM relay_weekly_schedule WHERE building_id = '$info[building_id]' AND active = 1 ORDER BY description;", MySQL::QUERY_ASSOC);

		return $this->success([
			'record' => $record,
			'info' => $info,
			'location' => $location,
			'schedules' => $schedules ?: [],
			'history' => Relay::get_history_with_details("WHERE d.id = '$id' ORDER BY h.datetime DESC LIMIT 100")
		]);
	}

}
