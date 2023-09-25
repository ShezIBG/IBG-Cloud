<?php

class Lighting {

	public static function check_database($building_id) {
		if(!$building_id) return false;

		try {
			App::sql('dali')->query("SELECT * FROM ve_dali_${building_id}.sys_info;");
		} catch(Exception $ex) {
			return false;
		}

		return true;
	}

	public static function create_database($building_id) {
		if(!$building_id) return false;

		$query =
			"CREATE DATABASE IF NOT EXISTS `ve_dali_${building_id}` CHARACTER SET utf8 COLLATE utf8_general_ci;
			USE `ve_dali_${building_id}`;

			CREATE TABLE `dali_group_light` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`dali_group_id` int(11) NOT NULL,
				`dali_light_id` int(11) NOT NULL,
				`last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `dali_group_schedule` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`dali_group_id` int(11) NOT NULL,
				`weekly_schedule_id` int(11) NOT NULL,
				`datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`light_onoff` tinyint(4) NOT NULL,
				`light_level` int(11) DEFAULT NULL,
				`last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `dali_history` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`user_id` int(10) unsigned NOT NULL,
				`datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`weekly_schedule_id` int(10) unsigned DEFAULT NULL,
				`dali_light_id` int(10) unsigned DEFAULT NULL,
				`event` enum('schedule_create','schedule_update','schedule_delete','light_schedule') NOT NULL,
				`notes` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `dali_light` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`ve_subnet_id` int(3) unsigned NOT NULL DEFAULT '0',
				`dali_id` int(10) unsigned NOT NULL DEFAULT '0',
				`building_server_id` int(10) unsigned DEFAULT NULL,
				`area_id` int(10) unsigned DEFAULT NULL,
				`description` varchar(60) DEFAULT NULL,
				`category` varchar(45) NOT NULL DEFAULT 'Lights',
				`category_icon` varchar(3) NOT NULL DEFAULT '',
				`weekly_schedule_id` int(10) unsigned DEFAULT NULL,
				`no_of_lights` int(10) unsigned NOT NULL DEFAULT '1',
				`light_onoff` int(1) unsigned DEFAULT NULL,
				`light_level` int(3) unsigned DEFAULT NULL,
				`light_error_code` varchar(50) DEFAULT NULL,
				`last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`active` tinyint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `dali_weekly_schedule` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`dali_group_id` int(10) unsigned NOT NULL,
				`description` varchar(60) NOT NULL DEFAULT '',
				`off_on_holidays` tinyint(1) unsigned NOT NULL DEFAULT '0',
				`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `dali_weekly_schedule_item` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`weekly_schedule_id` int(10) unsigned NOT NULL,
				`day` enum('mon','tue','wed','thu','fri','sat','sun') NOT NULL,
				`type` enum('before-sunrise','after-sunrise','set-time','before-sunset','after-sunset') DEFAULT 'set-time',
				`time` time(4) NOT NULL,
				`light_onoff` int(1) unsigned NOT NULL DEFAULT '1',
				`light_level` int(3) unsigned DEFAULT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `dev_null` (
				`value` varchar(255) DEFAULT NULL
			) ENGINE=BLACKHOLE DEFAULT CHARSET=utf8;

			CREATE TABLE `sys_info` (
				`schedule_changed` tinyint(1) NOT NULL,
				`dali_command` varchar(60) DEFAULT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			INSERT INTO `sys_info` VALUES (0, NULL);

			CREATE DEFINER=`root`@`%` TRIGGER `schedule_changed` AFTER UPDATE ON `sys_info`
				FOR EACH ROW BEGIN
					IF (NEW.schedule_changed=1) THEN
						INSERT INTO dev_null
							SELECT stompsend2('80.85.86.43','ve_dali_${building_id}', '<schedule_changed>', 'persistent', 'true', 'priority', '3');
					end if;
					if (NEW.dali_command<>'') then
						insert into dev_null
							select stompsend2('80.85.86.43','ve_dali_${building_id}', NEW.dali_command,'persistent','true','priority', '4');
					end if;
				END;
		";

		try {
			$sql = App::sql('dali');
			$sql->multi_query($query);
			sleep(1);
			$sql->reconnect();
		} catch (Exception $ex) {
			return false;
		}

		return true;
	}

	public static function notify_schedule_change($building_id) {
		App::sql('dali')->update("UPDATE ve_dali_${building_id}.sys_info SET schedule_changed = 1;");
	}

	public static function is_schedule_synced($building_id) {
		$ret = App::sql('dali')->query_row("SELECT schedule_changed FROM ve_dali_${building_id}.sys_info LIMIT 1;", MySQL::QUERY_ASSOC);
		return $ret ? !$ret['schedule_changed'] : false;
	}

	public static function rebuild_weekly_schedule($building_id, $sid) {
		$building_id = App::escape($building_id);
		$sid = App::escape($sid);
		$schedule = new LightingWeeklySchedule($building_id, $sid);
		$schedule->rebuild();
	}

	public static function rebuild_building_schedule($bid) {
		$bid = App::escape($bid);
		$list = App::sql('dali')->query("SELECT id FROM ve_dali_$bid.dali_weekly_schedule WHERE active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			Lighting::rebuild_weekly_schedule($bid, $item['id']);
		}
	}

	public static function rebuild_building_holiday_schedule($bid) {
		$bid = App::escape($bid);
		$list = App::sql('dali')->query("SELECT id FROM ve_dali_$bid.dali_weekly_schedule WHERE off_on_holidays = 1 AND active = 1;", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $item) {
			Lighting::rebuild_weekly_schedule($bid, $item['id']);
		}
	}

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

	public static function change_light_state($building_id, $dali_light_id, $state) {
		$building_id = App::escape($building_id);
		$db = "ve_dali_${building_id}";

		$light = App::select("$db.dali_light@dali", $dali_light_id);
		if(!$light) return false;

		$subnet = $light['ve_subnet_id'];
		$dest = 'single';
		$dali = $light['dali_id'];
		$cmd = $state ? 'on' : 'off';

		$command = "[subnet:$subnet][dest:$dest][dali:$dali][cmd:$cmd]";
		App::sql('dali')->update("UPDATE $db.sys_info SET dali_command = '$command';");

		return true;
	}

	public static function change_group_state($building_id, $schedule_id, $state) {
		$building_id = App::escape($building_id);
		$schedule_id = App::escape($schedule_id);
		$db = "ve_dali_${building_id}";

		$schedule = App::select("$db.dali_weekly_schedule@dali", $schedule_id);
		if(!$schedule) return false;

		$subnets = App::sql('dali')->query("SELECT DISTINCT ve_subnet_id AS id FROM $db.dali_light WHERE weekly_schedule_id = '$schedule_id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($subnets as $subnet) {
			$subnet = $subnet['id'];
			$dest = 'group';
			$dali = $schedule['dali_group_id'];
			$cmd = $state ? 'on' : 'off';

			$command = "[subnet:$subnet][dest:$dest][dali:$dali][cmd:$cmd]";
			App::sql('dali')->update("UPDATE $db.sys_info SET dali_command = '$command';");
		}

		return true;
	}

}

class LightingWeeklySchedule {

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

	public function __construct($building_id, $id) {
		$this->building_id = $building_id;
		$this->id = App::escape($id);
		$this->record = App::select("ve_dali_$this->building_id.dali_weekly_schedule@dali", $id);

		$weather = App::sql()->query_row("SELECT TIME(sunriseTime) AS sunrise, TIME(sunsetTime) AS sunset FROM weather WHERE building_id = '$building_id' ORDER BY date DESC LIMIT 1;", MySQL::QUERY_ASSOC);
		if($weather) {
			$this->sunrise_time = $weather['sunrise'];
			$this->sunset_time = $weather['sunset'];
		} else {
			$this->sunrise_time = '07:00';
			$this->sunset_time = '20:00';
		}

		$this->items = App::sql('dali')->query(
			"SELECT * FROM ve_dali_$this->building_id.dali_weekly_schedule_item
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

		App::sql('dali')->delete("DELETE FROM ve_dali_$this->building_id.dali_group_schedule WHERE weekly_schedule_id = '$this->id';");

		if(!$this->record['active']) return;

		for($d = -1; $d <= 3; $d++) {
			$day = date('Y-m-d', strtotime("$d day"));
			$dow = strtolower(date('D', strtotime($day)));

			if(in_array($day, $this->holidays)) {
				// Off all day on building holidays
				App::insert("ve_dali_$this->building_id.dali_group_schedule@dali", [
					'datetime' => App::timezone("$day 00:00", $this->tz, 'UTC'),
					'dali_group_id' => $this->record['dali_group_id'],
					'weekly_schedule_id' => $this->id,
					'light_onoff' => 0,
					'light_level' => null
				]);
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

					App::insert("ve_dali_$this->building_id.dali_group_schedule@dali", [
						'datetime' => $dt,
						'dali_group_id' => $this->record['dali_group_id'],
						'weekly_schedule_id' => $this->id,
						'light_onoff' => $item['light_onoff'],
						'light_level' => $item['light_level']
					]);
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

			// We're processing consecutive days from today onwards.
			// If we're finished processing the current day AND there is a result, we can return it.
			if($res) return $res;
		}

		return $res;
	}

}
