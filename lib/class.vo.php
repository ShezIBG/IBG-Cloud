<?php

class VO {

	public $id;
	public $record;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->reload();
	}

	public function reload() {
		$this->record = App::select('vo_unit', $this->id);
	}

	public function is_valid() {
		return !!$this->record;
	}

	public function get_input_voltage_history($date_from, $date_to) {
		$abb_id = $this->record['input_abb_id'];
		return $this->get_abb_voltage_history($abb_id, $date_from, $date_to);
	}

	public function get_output_voltage_history($date_from, $date_to) {
		$abb_id = $this->record['output_abb_id'];
		return $this->get_abb_voltage_history($abb_id, $date_from, $date_to);
	}

	private function get_abb_voltage_history($abb_id, $date_from, $date_to) {
		$step = 0;
		if(strlen($date_from) > 10 || strlen($date_to) > 10) $step = 1;

		if(strlen($date_from) == 10) $date_from .= ' 00:00:00';
		if(strlen($date_to) == 10) $date_to .= ' 23:59:59';

		$data = App::sql()->query_row(
			"SELECT
				abb.modbus_id,
				g.pi_serial,
				g.monitoring_server_id
			FROM abb_meter AS abb
			JOIN gateway AS g ON g.id = abb.gateway_id
			WHERE abb.id = '$abb_id' AND g.monitoring_server_id IS NOT NULL AND abb.modbus_id > 0
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$data) return [];

		$sql = App::monitoring_sql($data['monitoring_server_id']);

		try {
			if($step == 0) {
				$diff = (strtotime($date_to) - strtotime($date_from)) / (24*60*60);
				$step = 5*60;
				if($diff > 0) $step = 15*60;
				if($diff > 7) $step = 60*60;
			}

			$r = $sql->query(
				"SELECT
					AVG(IF(volts_L1 > 0, volts_L1 / 10, NULL)) AS l1,
					AVG(IF(volts_L2 > 0, volts_L2 / 10, NULL)) AS l2,
					AVG(IF(volts_L3 > 0, volts_L3 / 10, NULL)) AS l3,
					MAX(datetime) AS datetime,
					FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(datetime) / $step) * $step) AS grp
				FROM abb_$data[pi_serial]
				WHERE datetime BETWEEN '$date_from' AND '$date_to' AND meter_id LIKE '%.$data[modbus_id]'
				GROUP BY grp;
			", MySQL::QUERY_ASSOC);

			$r = array_map(function($item) {
				$sum = 0;
				$cnt = 0;
				if($item['l1']) { $sum += $item['l1']; $cnt += 1; }
				if($item['l2']) { $sum += $item['l2']; $cnt += 1; }
				if($item['l3']) { $sum += $item['l3']; $cnt += 1; }

				if($cnt === 0) {
					$item['avg'] = null;
				} else {
					$item['avg'] = $sum / $cnt;
				}

				return $item;
			}, $r ?: []);

			return $r;
		} catch(Exception $ex) {
			return [];
		}
	}

	public function get_power_factor_history($date_from, $date_to) {
		$abb_id = $this->record['output_abb_id'];
		$step = 0;
		if(strlen($date_from) > 10 || strlen($date_to) > 10) $step = 1;

		if(strlen($date_from) == 10) $date_from .= ' 00:00:00';
		if(strlen($date_to) == 10) $date_to .= ' 23:59:59';

		$data = App::sql()->query_row(
			"SELECT
				abb.modbus_id,
				g.pi_serial,
				g.monitoring_server_id
			FROM abb_meter AS abb
			JOIN gateway AS g ON g.id = abb.gateway_id
			WHERE abb.id = '$abb_id' AND g.monitoring_server_id IS NOT NULL AND abb.modbus_id > 0
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$data) return [];

		$sql = App::monitoring_sql($data['monitoring_server_id']);

		try {
			if($step == 0) {
				$diff = (strtotime($date_to) - strtotime($date_from)) / (24*60*60);
				$step = 5*60;
				if($diff > 0) $step = 15*60;
				if($diff > 7) $step = 60*60;
			}

			$r = $sql->query(
				"SELECT
					AVG(IF(power_factor_L1 < 32000, power_factor_L1 / 1000, NULL)) AS l1,
					AVG(IF(power_factor_L2 < 32000, power_factor_L2 / 1000, NULL)) AS l2,
					AVG(IF(power_factor_L3 < 32000, power_factor_L3 / 1000, NULL)) AS l3,
					AVG(IF(power_factor_total < 32000, power_factor_total / 1000, NULL)) AS total,
					MAX(datetime) AS datetime,
					FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(datetime) / $step) * $step) AS grp
				FROM abb_$data[pi_serial]
				WHERE datetime BETWEEN '$date_from' AND '$date_to' AND meter_id LIKE '%.$data[modbus_id]'
				GROUP BY grp;
			", MySQL::QUERY_ASSOC);

			return $r ?: [];
		} catch(Exception $ex) {
			return [];
		}
	}

}
