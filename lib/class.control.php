<?php

class Control {

	public static function rebuild_weekly_schedule($building_id, $sid) {
		$building_id = App::escape($building_id);
		$sid = App::escape($sid);
		$schedule = new ControlWeeklySchedule($building_id, $sid);
		$schedule->rebuild();
	}

	public static function rebuild_building_schedule($bid) {
		$bid = App::escape($bid);
		$sql = App::sql("knx:$bid");
		if($sql) {
			$list = $sql->query("SELECT id FROM weekly_schedule WHERE active = 1;", MySQL::QUERY_ASSOC) ?: [];
			foreach($list as $item) {
				Control::rebuild_weekly_schedule($bid, $item['id']);
			}
		}
	}

	public static function rebuild_building_holiday_schedule($bid) {
		$bid = App::escape($bid);
		$sql = App::sql("knx:$bid");
		if($sql) {
			$list = $sql->query("SELECT id FROM weekly_schedule WHERE off_on_holidays = 1 AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
			foreach($list as $item) {
				Control::rebuild_weekly_schedule($bid, $item['id']);
			}
		}
	}

	// TODO: Control history
	public static function get_history_with_details($building_id, $condition) {
		$building_id = App::escape($building_id);
		$result = [];

		$r = App::sql('dali')->query(
			"SELECT
				h.*,
				COALESCE(ws.description, 'Unknown') AS weekly_schedule_description,
				COALESCE(l.description, 'Unknown') AS light_description
			FROM ve_dali_${building_id}.dali_history AS h
			LEFT JOIN ve_dali_${building_id}.dali_weekly_schedule AS ws ON ws.id = h.weekly_schedule_id
			LEFT JOIN ve_dali_${building_id}.dali_light AS l ON l.id = h.dali_light_id
			$condition;
		", MySQL::QUERY_ASSOC) ?: [];

		$blist = [$building_id];
		$ulist = [];
		foreach($r as $h) {
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
			if(!isset($buildings[$building_id]) || !isset($users[$h['user_id']])) continue;

			$wsd = htmlentities($h['weekly_schedule_description']);
			$ld = htmlentities($h['light_description']);

			$b = $buildings[$building_id];
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

				case 'light_schedule':
					if($h['weekly_schedule_id']) {
						$html = "Light <b>$ld</b> schedule changed to <b>$wsd</b>.";
					} else {
						$html = "Light <b>$ld</b> schedule unassigned.";
					}
					break;

				default:
					continue;
			}

			$result[] = [
				'id' => $h['id'],
				'building_id' => $building_id,
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

class ControlWeeklySchedule {

	public $building_id = 0;
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

	public $sunset_time = 0;
	public $sunrise_time = 0;

	public $db = '';

	public function __construct($building_id, $id) {
		$this->building_id = $building_id;
		$this->id = App::escape($id);
		$this->db = "knx:$this->building_id";
		$this->record = App::select("weekly_schedule@$this->db", $id);

		$weather = App::sql()->query_row("SELECT TIME(sunriseTime) AS sunrise, TIME(sunsetTime) AS sunset FROM weather WHERE building_id = '$building_id' ORDER BY date DESC LIMIT 1;", MySQL::QUERY_ASSOC);
		if($weather) {
			$this->sunrise_time = $weather['sunrise'];
			$this->sunset_time = $weather['sunset'];
		} else {
			$this->sunrise_time = '07:00';
			$this->sunset_time = '20:00';
		}

		$this->items = App::sql($this->db)->query(
			"SELECT * FROM weekly_schedule_item
			WHERE weekly_schedule_id = '$this->id'
			ORDER BY day, time;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($this->items as $item) {
			$this->daily_items[$item['day']][] = $item;
		}

		if($this->record) {
			$building = App::select('building', $this->building_id);
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

	public function rebuild() {
		if(!$this->validate()) return;

		App::sql($this->db)->delete("DELETE FROM scheduled_event WHERE weekly_schedule_id = '$this->id';");

		if(!$this->record['active']) return;

		// Dry run to generate start dates per slot type ID
		// We need to do it this way, as items might not be in date/time order due to sunset/sunrise times
		$type_dts = [];
		$global_dts = [];

		for($d = -1; $d <= 3; $d++) {
			$day = date('Y-m-d', strtotime("$d day"));
			$dow = strtolower(date('D', strtotime($day)));

			if(in_array($day, $this->holidays)) {
				// Off all day on building holidays
				$start_datetime = App::timezone("$day 00:00:00", $this->tz, 'UTC');
				$global_dts[] = $start_datetime;
			} else {
				foreach($this->daily_items[$dow] as $item) {
					$seconds = strtotime("1970-01-01 $item[time]");

					switch($item['type']) {
						case 'before-sunrise':
							$dt = date('Y-m-d H:i:s', strtotime("-$seconds seconds", strtotime("$day $this->sunrise_time")));
							break;

						case 'before-sunset':
							$dt = date('Y-m-d H:i:s', strtotime("-$seconds seconds", strtotime("$day $this->sunset_time")));
							break;

						case 'after-sunrise':
							$dt = date('Y-m-d H:i:s', strtotime("+$seconds seconds", strtotime("$day $this->sunrise_time")));
							break;

						case 'after-sunset':
							$dt = date('Y-m-d H:i:s', strtotime("+$seconds seconds", strtotime("$day $this->sunset_time")));
							break;

						default:
							// Convert to local time. All times above are already UTC.
							$dt = App::timezone("$day $item[time]", $this->tz, 'UTC');
					}

					$start_datetime = $dt;

					if($item['item_type_slot_id'] === null) {
						$global_dts[] = $start_datetime;
					} else if(isset($type_dts[$item['item_type_slot_id']])) {
						$type_dts[$item['item_type_slot_id']][] = $start_datetime;
					} else {
						$type_dts[$item['item_type_slot_id']] = [$start_datetime];
					}
				}
			}
		}

		// Add end of last day to dt list
		$global_dts[] = "$day 23:59:59";

		// Add global date/times to typed date times
		$keys = array_keys($type_dts);
		foreach($keys as $k) {
			$type_dts[$k] = array_merge($type_dts[$k], $global_dts);
		}

		// Sort dates and times
		foreach($keys as $k) {
			sort($type_dts[$k]);
		}

		// Create items in DB
		for($d = -1; $d <= 3; $d++) {
			$day = date('Y-m-d', strtotime("$d day"));
			$dow = strtolower(date('D', strtotime($day)));

			if(in_array($day, $this->holidays)) {
				// Off all day on building holidays
				$start_datetime = App::timezone("$day 00:00:00", $this->tz, 'UTC');
				$end_datetime = App::timezone("$day 23:59:59", $this->tz, 'UTC');

				App::sql($this->db)->query(
					"INSERT INTO scheduled_event (start_datetime, end_datetime, repeat_cmd_interval, knx_datatype, baos_id, baos_value, weekly_schedule_id)
					SELECT '$start_datetime', '$end_datetime', 60, itsl.knx_datatype, isl.knx_id, '0', i.weekly_schedule_id
					FROM item AS i
					JOIN item_type AS it ON it.id = i.type_id
					JOIN item_slot AS isl ON isl.item_id = i.id AND isl.slot_id = it.switch_slot_id
					JOIN item_type_slot AS itsl ON itsl.id = it.switch_slot_id
					WHERE i.weekly_schedule_id = '$this->id';
				");
			} else {
				foreach($this->daily_items[$dow] as $item) {
					$seconds = strtotime("1970-01-01 $item[time]");

					switch($item['type']) {
						case 'before-sunrise':
							$dt = date('Y-m-d H:i:s', strtotime("-$seconds seconds", strtotime("$day $this->sunrise_time")));
							break;

						case 'before-sunset':
							$dt = date('Y-m-d H:i:s', strtotime("-$seconds seconds", strtotime("$day $this->sunset_time")));
							break;

						case 'after-sunrise':
							$dt = date('Y-m-d H:i:s', strtotime("+$seconds seconds", strtotime("$day $this->sunrise_time")));
							break;

						case 'after-sunset':
							$dt = date('Y-m-d H:i:s', strtotime("+$seconds seconds", strtotime("$day $this->sunset_time")));
							break;

						default:
							// Convert to local time. All times above are already UTC.
							$dt = App::timezone("$day $item[time]", $this->tz, 'UTC');
					}

					$start_datetime = $dt;
					$pos = array_search($start_datetime, $type_dts[$item['item_type_slot_id']]);
					if($pos !== false) {
						// End time will be 10 seconds before the next start time
						$end_datetime = $type_dts[$item['item_type_slot_id']][$pos + 1];
						$end_datetime = date('Y-m-d H:i:s', strtotime('-10 second', strtotime($end_datetime)));

						// Fall back to +1 second solution if we failed
						if(strtotime($start_datetime) > strtotime($end_datetime)) $end_datetime = date('Y-m-d H:i:s', strtotime('+1 second', strtotime($dt)));
					} else {
						$end_datetime = date('Y-m-d H:i:s', strtotime('+1 second', strtotime($dt)));
					}

					if($item['end_time']) {
						$target_datetime = App::timezone("$day $item[end_time]", $this->tz, 'UTC');
						if(strtotime($target_datetime) < strtotime($start_datetime)) {
							$target_datetime = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($target_datetime)));
						}
						$end_datetime = $target_datetime;
					}

					if($item['item_type_slot_id']) {
						App::sql($this->db)->query(
							"INSERT INTO scheduled_event (start_datetime, end_datetime, repeat_cmd_interval, knx_datatype, baos_id, baos_value, weekly_schedule_id)
							SELECT '$start_datetime', '$end_datetime', '$item[repeat_minutes]', itsl.knx_datatype, isl.knx_id, '$item[baos_value]', i.weekly_schedule_id
							FROM item AS i
							JOIN item_slot AS isl ON isl.item_id = i.id AND isl.slot_id = '$item[item_type_slot_id]'
							JOIN item_type_slot AS itsl ON itsl.id = isl.slot_id
							WHERE i.weekly_schedule_id = '$this->id';
						");
					}
				}
			}
		}
	}

	public function get_next_event() {
		if(!$this->validate()) return null;

		$now = date('Y-m-d H:i:s');
		$now_t = strtotime($now);

		$res = null;
		$res_diff = 1728000;

		for($d = 0; $d <= 14; $d++) {
			$day = date('Y-m-d', strtotime("$d day"));
			$dow = strtolower(date('D', strtotime($day)));

			if(in_array($day, $this->holidays)) {
				// Off all day on building holidays
				$dt = App::timezone("$day 00:00:00", $this->tz, 'UTC');

				$diff = strtotime($dt) - $now_t;
				if($diff > 0 && $diff < $res_diff) {
					$res_diff = $diff;
					$res = App::timezone($dt, 'UTC', $this->tz);
				}
			} else {
				foreach($this->daily_items[$dow] as $item) {
					$seconds = strtotime("1970-01-01 $item[time]");

					switch($item['type']) {
						case 'before-sunrise':
							$dt = date('Y-m-d H:i:s', strtotime("-$seconds seconds", strtotime("$day $this->sunrise_time")));
							break;

						case 'before-sunset':
							$dt = date('Y-m-d H:i:s', strtotime("-$seconds seconds", strtotime("$day $this->sunset_time")));
							break;

						case 'after-sunrise':
							$dt = date('Y-m-d H:i:s', strtotime("+$seconds seconds", strtotime("$day $this->sunrise_time")));
							break;

						case 'after-sunset':
							$dt = date('Y-m-d H:i:s', strtotime("+$seconds seconds", strtotime("$day $this->sunset_time")));
							break;

						default:
							// Convert to local time. All times above are already UTC.
							$dt = App::timezone("$day $item[time]", $this->tz, 'UTC');
					}

					$diff = strtotime($dt) - $now_t;
					if($diff > 0 && $diff < $res_diff) {
						$res_diff = $diff;
						$res = App::timezone($dt, 'UTC', $this->tz);
					}
				}
			}

			// We're processing consecutive days from today onwards.
			// If we're finished processing the current day AND there is a result, we can return it.
			if($res) return $res;
		}

		return $res;
	}

}
