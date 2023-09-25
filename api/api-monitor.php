<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_collector_overview() {
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		$overview = App::sql()->query_row(
			"SELECT
				SUM(IF(gs.ignore <> 1, 1, 0)) AS monitored,
				SUM(IF(gs.ignore <> 1 AND gs.status = 'ok', 1, 0)) AS ok,
				SUM(IF(gs.ignore <> 1 AND gs.status <> 'ok', 1, 0)) AS error,
				SUM(IF(gs.ignore = 1, 1 ,0)) AS ignored,
				MIN(TIMESTAMPDIFF(SECOND, gs.last_checked, NOW())) AS last_check
			FROM gateway_status AS gs
			JOIN gateway AS g ON g.id = gs.gateway_id
			JOIN area AS a ON a.id = g.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			WHERE client_id <> 0;
		", MySQL::QUERY_ASSOC);

		if(!$overview) return $this->error('Error reading monitoring data.');

		if($overview['last_check'] !== null) {
			$overview['last_check_description'] = App::time_since($overview['last_check'], true);
		} else {
			$overview['last_check_description'] = 'Never';
		}

		$affected_clients = App::sql()->query(
			"SELECT
				MIN(c.name) AS client
			FROM gateway_status AS gs
			JOIN gateway AS g ON g.id = gs.gateway_id
			JOIN area AS a ON a.id = g.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			JOIN client AS c ON c.id = b.client_id
			WHERE client_id <> 0 AND gs.ignore <> 1 AND gs.status <> 'ok'
			GROUP BY c.id
			ORDER BY client;
		");

		$overview['affected_clients'] = array_map(function($c) { return $c->client; }, $affected_clients ?: []);

		$issues = App::sql()->query(
			"SELECT
				g.id AS id,
				c.name AS client,
				b.description AS building,
				f.description AS floor,
				a.description AS area,
				g.description AS gateway,
				g.pi_serial AS gateway_serial,
				IF(gs.last_checked IS NULL, NULL, TIMESTAMPDIFF(SECOND, gs.last_checked, NOW())) AS last_checked,
				gs.last_data_received AS last_received,
				gs.message AS message,
				g.type AS gateway_type,
				gs.status AS status,
				gs.ignore AS ignored
			FROM gateway_status AS gs
			JOIN gateway AS g ON g.id = gs.gateway_id
			JOIN area AS a ON a.id = g.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			JOIN client AS c ON c.id = b.client_id
			WHERE client_id <> 0 AND gs.ignore <> 1 AND gs.status <> 'ok'
			ORDER BY client, building, floor, area, gateway;
		");

		$overview['issues'] = array_map(function($r) {
			return [
				'id' => $r->id,
				'client' => $r->client,
				'building' => $r->building,
				'floor' => $r->floor,
				'area' => $r->area,
				'gateway' => $r->gateway,
				'gateway_serial' => $r->gateway_serial,
				'gateway_type' => $r->gateway_type,
				'message' => $r->message,
				'last_checked' => App::time_since($r->last_checked, true),
				'last_received' => $r->last_received ?: 'None',
				'status' => $r->status,
				'ignored' => $r->ignored == 1
			];
		}, $issues ?: []);

		$reliability = App::sql()->query(
			"SELECT
				g.id AS id,
				gs.reliability_7_days AS reliability,
				CONCAT(b.description, ' / ', g.description) AS description
			FROM gateway_status AS gs
			JOIN gateway AS g ON g.id = gs.gateway_id
			JOIN area AS a ON a.id = g.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			JOIN client AS c ON c.id = b.client_id
			WHERE client_id <> 0 AND gs.ignore <> 1
			ORDER BY reliability;
		", MySQL::QUERY_ASSOC, false);

		$overview['reliability'] = $reliability ?: [];

		return $this->success($overview);
	}

	public function get_collectors() {
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		$filter = App::get('filter', '');

		$condition = '';
		switch($filter) {
			case 'monitored':
				$condition = "gs.ignore <> 1";
				break;

			case 'ok':
				$condition = "gs.ignore <> 1 AND gs.status = 'ok'";
				break;

			case 'errors':
				$condition = "gs.ignore <> 1 AND gs.status <> 'ok'";
				break;

			case 'ignored':
				$condition = "gs.ignore = 1";
				break;

			default:
				// Show all collectors
				$condition = "";
				break;
		}

		if($condition) $condition = "AND $condition";

		$list = App::sql()->query(
			"SELECT
				g.id AS id,
				c.name AS client,
				b.description AS building,
				f.description AS floor,
				a.description AS area,
				g.description AS gateway,
				g.pi_serial AS gateway_serial,
				IF(gs.last_checked IS NULL, NULL, TIMESTAMPDIFF(SECOND, gs.last_checked, NOW())) AS last_checked,
				gs.last_data_received AS last_received,
				gs.message AS message,
				g.type AS gateway_type,
				gs.status AS status,
				gs.ignore AS ignored
			FROM gateway_status AS gs
			JOIN gateway AS g ON g.id = gs.gateway_id
			JOIN area AS a ON a.id = g.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			JOIN client AS c ON c.id = b.client_id
			WHERE client_id <> 0 $condition
			ORDER BY client, building, floor, area, gateway;
		");

		$result = array_map(function($r) {
			return [
				'id' => $r->id,
				'client' => $r->client,
				'building' => $r->building,
				'floor' => $r->floor,
				'area' => $r->area,
				'gateway' => $r->gateway,
				'gateway_serial' => $r->gateway_serial,
				'gateway_type' => $r->gateway_type,
				'message' => $r->message,
				'last_checked' => App::time_since($r->last_checked, true),
				'last_received' => $r->last_received ?: 'None',
				'status' => $r->status,
				'ignored' => $r->ignored == 1
			];
		}, $list ?: []);

		return $this->success($result);
	}

	public function get_collector_history() {
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		$id = App::get('id', 0, true);

		$r = App::sql()->query_row(
			"SELECT
				g.id AS id,
				c.name AS client,
				b.description AS building,
				f.description AS floor,
				a.description AS area,
				g.description AS gateway,
				g.pi_serial AS gateway_serial,
				IF(gs.last_checked IS NULL, NULL, TIMESTAMPDIFF(SECOND, gs.last_checked, NOW())) AS last_checked,
				gs.last_data_received AS last_received,
				gs.message AS message,
				g.type AS gateway_type,
				gs.status AS status,
				gs.ignore AS ignored
			FROM gateway_status AS gs
			JOIN gateway AS g ON g.id = gs.gateway_id
			JOIN area AS a ON a.id = g.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			JOIN client AS c ON c.id = b.client_id
			WHERE client_id <> 0 AND g.id = '$id'
			ORDER BY client, building, floor, area, gateway
			LIMIT 1;
		");

		if(!$r) return $this->error('Collector not found.');

		$result = [
			'details' => [
				'id' => $r->id,
				'client' => $r->client,
				'building' => $r->building,
				'floor' => $r->floor,
				'area' => $r->area,
				'gateway' => $r->gateway,
				'gateway_serial' => $r->gateway_serial,
				'gateway_type' => $r->gateway_type,
				'message' => $r->message,
				'last_checked' => App::time_since($r->last_checked, true),
				'last_received' => $r->last_received ?: 'None',
				'status' => $r->status,
				'ignored' => $r->ignored == 1
			]
		];

		$history = App::sql()->query(
			"SELECT
				datetime, status, message
			FROM gateway_status_history
			WHERE gateway_id = '$id'
			ORDER BY datetime DESC
			LIMIT 250;
		");

		$result['history'] = $history ?: [];

		$chart_from = date('Y-m-d H:i:s', strtotime('-7 days'));
		$chart_to = date('Y-m-d H:i:s');

		$chart = App::sql()->query(
			"SELECT
				datetime AS x,
				IF(status = 'ok', 1, 0) AS y
			FROM gateway_status_history
			WHERE gateway_id = '$id' AND datetime BETWEEN '$chart_from' AND '$chart_to'
			ORDER BY datetime;
		", MySQL::QUERY_ASSOC) ?: [];

		$before = App::sql()->query_row(
			"SELECT
				datetime AS x,
				IF(status = 'ok', 1, 0) AS y
			FROM gateway_status_history
			WHERE gateway_id = '$id' AND datetime < '$chart_from'
			ORDER BY datetime DESC
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if($before) {
			$before['x'] = $chart_from;
			array_unshift($chart, $before);
		}

		$last = App::sql()->query_row(
			"SELECT
				datetime AS x,
				IF(status = 'ok', 1, 0) AS y
			FROM gateway_status_history
			WHERE gateway_id = '$id'
			ORDER BY datetime DESC
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if($last) {
			$last['x'] = $chart_to;
			$chart[] = $last;
		}

		$result['chart'] = $chart;

		return $this->success($result);
	}

	public function set_collector_ignore_flag() {
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		list($id, $flag) = App::get(['id', 'flag'], 0, true);

		if(!$id) return $this->error('Invalid request.');

		App::sql()->update("UPDATE gateway_status SET `ignore` = '$flag' WHERE gateway_id = '$id';");

		return $this->success();
	}

}
