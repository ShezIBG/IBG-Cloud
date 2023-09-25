<?php

class Climate {

	public static $modeDescription = [
		'cool' => 'Cool',
		'heat' => 'Heat',
		'auto' => 'Auto',
		'dry' => 'Dry',
		'haux' => 'HAux',
		'fan' => 'Fan'
	];

	public static $fanDescription = [
		'low' => 'Low',
		'medium' => 'Medium',
		'high' => 'High',
		'auto' => 'Auto',
		'top' => 'Top',
		'very_low' => 'Very Low'
	];

	public static $louvreDescription = [
		'vertical' => 'Vertical',
		'30' => '30&deg;',
		'45' => '45&deg;',
		'60' => '60&deg;',
		'horizontal' => 'Horizontal',
		'auto' => 'Auto',
		'off' => 'Off'
	];

	public static function rebuild_weekly_schedule($sid) {
		$sid = App::escape($sid);
		$schedule = new ClimateWeeklySchedule($sid);
		$schedule->rebuild_all_coolplugs();
	}

	public static function rebuild_coolplug_schedule($cid) {
		$cid = App::escape($cid);
		$cp = App::select('coolplug@climate', $cid);
		if($cp && $cp['coolhub_id']) {
			if($cp['weekly_schedule_id']) {
				$schedule = new ClimateWeeklySchedule($cp['weekly_schedule_id']);
				$schedule->rebuild_coolplug($cid);
			} else {
				// No schedule set, clear schedule items
				App::sql('climate')->delete("DELETE FROM ac_schedule WHERE coolhub_id = '$cp[coolhub_id]' AND coolplug_id = '$cp[coolplug_id]';");
			}
		}
	}

	public static function rebuild_building_schedule($bid) {
		$bid = App::escape($bid);
		$list = App::sql('climate')->query("SELECT id FROM ac_weekly_schedule WHERE building_id = '$bid' AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			Climate::rebuild_weekly_schedule($item['id']);
		}
	}

	public static function rebuild_building_holiday_schedule($bid) {
		$bid = App::escape($bid);
		$list = App::sql('climate')->query("SELECT id FROM ac_weekly_schedule WHERE building_id = '$bid' AND off_on_holidays = 1 AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			Climate::rebuild_weekly_schedule($item['id']);
		}
	}

	public static function get_history_with_details($condition) {
		$result = [];

		$r = App::sql('climate')->query(
			"SELECT
				h.*,
				ws.description AS weekly_schedule_description,
				cp.description AS coolplug_description
			FROM ac_history AS h
			LEFT JOIN ac_weekly_schedule AS ws ON ws.id = h.weekly_schedule_id
			LEFT JOIN coolplug AS cp ON cp.id = h.coolplug_id
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
			$cpd = htmlentities($h['coolplug_description']);

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
						$html = "Device <b>$cpd</b> schedule changed to <b>$wsd</b>.";
					} else {
						$html = "Device <b>$cpd</b> schedule unassigned.";
					}
					break;

				case 'device_hold':
					$dt_start = date('d/m/Y H:i', strtotime(App::timezone($h['hold_start'], 'UTC', $b['timezone'])));
					$dt_end = date('d/m/Y H:i', strtotime(App::timezone($h['hold_end'], 'UTC', $b['timezone'])));

					// Show only time if ends same day
					if(substr($dt_start, 0, 10) === substr($dt_end, 0, 10)) $dt_end = substr($dt_end, 11);

					$hold_info = '';

					if($h['hold_onoff']) {
						$hold_info = '<b>On</b> <span class="subtitle">';

						if($h['hold_setpoint']) $hold_info .= " &bullet; $h[hold_setpoint] &deg;C";
						if($h['hold_mode']) $hold_info .= ' &bullet; <i class="ei ei-ac-'.$h['hold_mode'].'"></i> '.self::$modeDescription[$h['hold_mode']];
						if($h['hold_fanspeed']) $hold_info .= ' &bullet; <i class="ei ei-ac-fan"></i> '.self::$fanDescription[$h['hold_fanspeed']];
						if($h['hold_swing']) $hold_info .= ' &bullet; Louvre: '.self::$louvreDescription[$h['hold_swing']];

						$hold_info .= '</span>';
					} else {
						$hold_info = '<b>Off</b>';
					}

					$html = "Temporary hold for device <b>$cpd</b>:<br><span class=\"subtitle\"><b>$dt_start</b> to <b>$dt_end</b></span><br>$hold_info";
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

class ClimateWeeklySchedule {

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
		$this->record = App::select('ac_weekly_schedule@climate', $id);

		$this->items = App::sql('climate')->query(
			"SELECT * FROM ac_weekly_schedule_item
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

	public function rebuild_all_coolplugs() {
		if(!$this->validate()) return;

		$list = App::sql('climate')->query("SELECT id FROM coolplug WHERE weekly_schedule_id = '$this->id' AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $cp) {
			$this->rebuild_coolplug($cp['id']);
		}
	}

	public function rebuild_coolplug($cid) {
		if(!$this->validate()) return;

		$cid = App::escape($cid);
		$cp = App::select('coolplug@climate', $cid);
		if(!$cp || !$cp['coolhub_id']) return;

		App::sql('climate')->delete("DELETE FROM ac_schedule WHERE coolhub_id = '$cp[coolhub_id]' AND coolplug_id = '$cp[coolplug_id]';");

		if(!$this->record['active']) return;

		for($d = -1; $d <= 3; $d++) {
			$day = date('Y-m-d', strtotime("$d day"));
			$dow = strtolower(date('D', strtotime($day)));

			if(in_array($day, $this->holidays)) {
				// Off all day on building holidays
				App::insert('ac_schedule@climate', [
					'datetime' => App::timezone("$day 00:00", $this->tz, 'UTC'),
					'coolhub_id' => $cp['coolhub_id'],
					'coolplug_id' => $cp['coolplug_id'],
					'ac_setpoint' => null,
					'ac_onoff' => 0,
					'ac_mode' => null,
					'ac_fanspeed' => null,
					'ac_swing' => null
				]);
			} else {
				foreach($this->daily_items[$dow] as $item) {
					if(!$item['ac_onoff']) {
						$item['ac_setpoint'] = null;
						$item['ac_mode'] = null;
						$item['ac_fanspeed'] = null;
						$item['ac_swing'] = null;
					}

					$setpoint = $item['ac_setpoint'];
					if($setpoint) {
						if($cp['min_setpoint'] && $setpoint < $cp['min_setpoint']) $setpoint = $cp['min_setpoint'];
						if($cp['max_setpoint'] && $setpoint > $cp['max_setpoint']) $setpoint = $cp['max_setpoint'];
					}

					App::insert('ac_schedule@climate', [
						'datetime' => App::timezone("$day $item[time]", $this->tz, 'UTC'),
						'coolhub_id' => $cp['coolhub_id'],
						'coolplug_id' => $cp['coolplug_id'],
						'ac_setpoint' => $setpoint,
						'ac_onoff' => $item['ac_onoff'],
						'ac_mode' => $item['ac_mode'],
						'ac_fanspeed' => $item['ac_fanspeed'],
						'ac_swing' => $item['ac_swing']
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
