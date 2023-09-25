<?php

class Relay {

	public static function rebuild_weekly_schedule($sid) {
		$sid = App::escape($sid);
		$schedule = new RelayWeeklySchedule($sid);
		$schedule->rebuild_all_devices();
	}

	public static function rebuild_device_schedule($id) {
		$id = App::escape($id);
		$device = App::select('relay_end_device@relay', $id);
		if($device) {
			if($device['weekly_schedule_id']) {
				$schedule = new RelayWeeklySchedule($device['weekly_schedule_id']);
				$schedule->rebuild_device($id);
			} else {
				// No schedule set, clear schedule items
				App::sql('relay')->delete("DELETE FROM relay_schedule WHERE relay_pin_id = '$device[state_pin_id]';");
			}
		}
	}

	public static function rebuild_building_schedule($bid) {
		$bid = App::escape($bid);
		$list = App::sql('relay')->query("SELECT id FROM relay_weekly_schedule WHERE building_id = '$bid' AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			Relay::rebuild_weekly_schedule($item['id']);
		}
	}

	public static function rebuild_building_holiday_schedule($bid) {
		$bid = App::escape($bid);
		$list = App::sql('relay')->query("SELECT id FROM relay_weekly_schedule WHERE building_id = '$bid' AND off_on_holidays = 1 AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			Relay::rebuild_weekly_schedule($item['id']);
		}
	}

	public static function get_history_with_details($condition) {
		$result = [];

		$r = App::sql('relay')->query(
			"SELECT
				h.*,
				ws.description AS weekly_schedule_description,
				d.description AS device_description
			FROM relay_history AS h
			LEFT JOIN relay_weekly_schedule AS ws ON ws.id = h.weekly_schedule_id
			LEFT JOIN relay_end_device AS d ON d.id = h.end_device_id
			$condition;
		", MySQL::QUERY_ASSOC) ?: [];

		$blist = [];
		$ulist = [];
		foreach($r as $h) {
			if(!in_array($h['building_id'], $blist)) $blist[] = $h['building_id'];
			if(!in_array($h['user_id'], $ulist)) $ulist[] = $h['user_id'];
		}
		$blist = implode(',', $blist);
		$ulist = implode(',', $ulist);

		$buildings = [];
		if($blist) {
			$list = App::sql()->query("SELECT id, description, timezone FROM building WHERE id IN ($blist);", MySQL::QUERY_ASSOC) ?: [];
			foreach($list as $item) $buildings[$item['id']] = $item;
		}

		$users = [];
		if($ulist) {
			$list = App::sql()->query("SELECT id, name, email_addr FROM userdb WHERE id IN ($ulist);", MySQL::QUERY_ASSOC) ?: [];
			foreach($list as $item) $users[$item['id']] = $item;
		}

		foreach($r as $h) {
			if(!isset($buildings[$h['building_id']]) || !isset($users[$h['user_id']])) continue;

			$wsd = htmlentities($h['weekly_schedule_description']);
			$dvd = htmlentities($h['device_description']);

			$b = $buildings[$h['building_id']];
			$u = $users[$h['user_id']];

			switch($h['event']) {
				case 'schedule_create':
					$html = "Schedule <b>$wsd</b> created.";
					break;

				case 'schedule_update':
					$html = "Schedule <b>$wsd</b> changed.";
					break;

				case 'schedule_delete':
					$html = "Schedule <b>$wsd</b> deleted.";
					break;

				case 'device_schedule':
					if($h['weekly_schedule_id']) {
						$html = "Device <b>$dvd</b> schedule changed to <b>$wsd</b>.";
					} else {
						$html = "Device <b>$dvd</b> schedule unassigned.";
					}
					break;

				default:
					continue;
			}

			$result[] = [
				'id' => $h['id'],
				'building_id' => $h['building_id'],
				'user_id' => $h['user_id'],
				'datetime' => App::timezone($h['datetime'], 'UTC', $b['timezone']),
				'event' => $h['event'],
				'notes' => $h['notes'],
				'user_name' => $u['name'],
				'user_email' => $u['email_addr'],
				'building_description' => $b['description'],
				'html' => $html
			];
		}

		return $result;
	}

}

class RelayWeeklySchedule {

	public $id = 0;
	public $record = null;
	public $items = null;
	public $daily_items = [
		'mon' => [],
		'tue' => [],
		'wed' => [],
		'thu' => [],
		'fri' => [],
		'sat' => [],
		'sun' => []
	];

	public $tz = null;
	public $holidays = [];

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->record = App::select('relay_weekly_schedule@relay', $id);

		$this->items = App::sql('relay')->query(
			"SELECT * FROM relay_weekly_schedule_item
			WHERE weekly_schedule_id = '$this->id'
			ORDER BY day, time;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($this->items as $item) {
			$this->daily_items[$item['day']][] = $item;
		}

		if($this->record) {
			$building = App::select('building', $this->record['building_id']);
			if($building) {
				$this->tz = Eticom::find_timezone_id($building['timezone']);

				if($this->record['off_on_holidays']) {
					$date_from = date('Y-m-d', strtotime('-7 days'));
					$list = App::sql()->query("SELECT date FROM building_holiday WHERE building_id = '$building[id]' AND closed_all_day = 1 AND date > '$date_from' ORDER BY date;", MySQL::QUERY_ASSOC) ?: [];
					foreach($list as $item) {
						$this->holidays[] = $item['date'];
					}
				}
			}
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function rebuild_all_devices() {
		if(!$this->validate()) return;

		$list = App::sql('relay')->query("SELECT id FROM relay_end_device WHERE weekly_schedule_id = '$this->id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $d) {
			$this->rebuild_device($d['id']);
		}
	}

	public function rebuild_device($id) {
		if(!$this->validate()) return;

		$id = App::escape($id);
		$device = App::select('relay_end_device@relay', $id);
		if(!$device) return;

		App::sql('relay')->delete("DELETE FROM relay_schedule WHERE relay_pin_id = '$device[state_pin_id]';");

		if(!$this->record['active']) return;
		if(!$device['state_pin_id']) return;

		for($d = -1; $d <= 3; $d++) {
			$day = date('Y-m-d', strtotime("$d day"));
			$dow = strtolower(date('D', strtotime($day)));

			if(in_array($day, $this->holidays)) {
				// Off all day on building holidays
				App::insert('relay_schedule@relay', [
					'datetime' => App::timezone("$day 00:00", $this->tz, 'UTC'),
					'relay_pin_id' => $device['state_pin_id'],
					'new_state' => 0
				]);
			} else {
				foreach($this->daily_items[$dow] as $item) {
					App::insert('relay_schedule@relay', [
						'datetime' => App::timezone("$day $item[time]", $this->tz, 'UTC'),
						'relay_pin_id' => $device['state_pin_id'],
						'new_state' => $item['new_state']
					]);
				}
			}
		}
	}

	public function get_next_event() {
		if(!$this->validate()) return null;

		$now = App::timezone(date('Y-m-d H:i:s'), 'UTC', $this->tz);
		$now_t = strtotime($now);

		for($d = 0; $d <= 14; $d++) {
			$day = date('Y-m-d', strtotime("$d day"));
			$dow = strtolower(date('D', strtotime($day)));

			foreach($this->daily_items[$dow] as $item) {
				$gen = strtotime("$day $item[time]");
				if($now_t < $gen) {
					return date('Y-m-d H:i:s', $gen);
				}
			}
		}

		return null;
	}

}
