<?php

require_once 'shared-api.php';
include ('json_serialize.php');
class API extends SharedAPI {

	public function get_first_isp_id() {
		$isp = Permission::find_system_integrators([ 'with' => Permission::ISP_ENABLED ]);
		if(!$isp) return $this->access_denied();

		return $this->success($isp[0]->system_integrator_id);
	}

	public function get_navigation() {
		$isp = new ISP(App::get('isp', 0));
		if(!$isp->validate()) return $this->access_denied();

		$nav = [
			[ 'name' => $isp->record['company_name'], 'header' => true ],
			[ 'name' => 'Overview', 'icon' => 'md md-home', 'route' => "/isp/$isp->id/overview" ],
			[ 'name' => 'Packages', 'icon' => 'md md-import-export', 'route' => "/isp/$isp->id/package" ],
			[ 'name' => 'Clients', 'icon' => 'md md-work', 'route' => "/isp/$isp->id/client" ],
			[ 'name' => 'Sites', 'icon' => 'md md-place', 'route' => "/isp/$isp->id/site" ],
			[ 'name' => 'Customers', 'icon' => 'md md-person', 'route' => "/isp/$isp->id/customer" ],
			[ 'name' => 'Contracts', 'icon' => 'md md-account-balance', 'route' => "/isp/$isp->id/contract" ],
			[ 'name' => 'Invoices', 'icon' => 'md md-insert-drive-file', 'route' => "/isp/$isp->id/invoice" ]
		];

		$list = Permission::list_system_integrators([ 'with' => Permission::ISP_ENABLED ]);
		if($list && count($list) > 1) {
			$nav[] = [ 'name' => 'Select other ISP', 'header' => true ];
			foreach($list as $si) {
				if($si->id != $isp->id) {
					$nav[] = [ 'name' => $si->company_name, 'icon' => 'md md-wifi-tethering', 'route' => "/isp/$si->id" ];
				}
			}
		}

		return $this->success([
			'menu' => $nav
		]);
	}

	public function get_overview() {
		$isp = App::get('isp', 0);
		if(!$isp) return $this->access_denied();

		$isp = new ISP($isp);
		if(!$isp->validate()) return $this->access_denied();

		$client_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM client WHERE system_integrator_id = '$isp->id';");
		$client_count = ($client_count ? $client_count->cnt : 0) ?: 0;

		$building_count = App::sql()->query_row(
			"SELECT COUNT(*) AS cnt
			FROM building AS b
			JOIN client AS c ON c.id = b.client_id
			WHERE c.system_integrator_id = '$isp->id' AND b.module_isp = 1;
		");
		$building_count = ($building_count ? $building_count->cnt : 0) ?: 0;

		$customer_count = App::sql()->query_row(
			"SELECT COUNT(*) AS cnt
			FROM customer
			WHERE owner_type = 'SI' AND owner_id = '$isp->id';
		");
		$customer_count = ($customer_count ? $customer_count->cnt : 0) ?: 0;

		return $this->success([
			'olt' => isp_info($isp->list_olt(), ['overview']),
			'client_count' => $client_count,
			'building_count' => $building_count,
			'customer_count' => $customer_count
		]);
	}

	public function list_clients() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		return $this->success(isp_info($isp->list_clients()));
	}

	public function get_client() {
		$id = App::get('id', 0);
		$client = new ISPClient($id);
		if(!$client->validate()) return $this->error('Client not found.');

		$isp = $client->get_isp();
		if(!$isp) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Clients', 'route' => "/isp/$isp->id/client" ],
			[ 'description' => $client->record['name'], 'route' => "/isp/$isp->id/client/$client->id" ],
		];

		return $this->success([
			'details' => $client->record,
			'breadcrumbs' => $crumbs
		]);
	}

	public function list_buildings() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		$buildings = [];
		if(isset($data['client'])) {
			$client = $isp->get_client($data['client']);
			if(!$client) return $this->access_denied();

			$buildings = isp_info($client->list_buildings());
		} else {
			$buildings = isp_info($isp->list_buildings());
		}

		$building_ids = [];
		foreach($buildings as $b) {
			$building_ids[] = $b['id'];
		}

		if(count($building_ids)) {
			$building_ids = implode(',', $building_ids);
			$list = App::sql('isp')->query(
				"SELECT
					hes.building_id,
					SUM(onu_cnt.cnt) AS onu_count,
					SUM(package_cnt.cnt) AS package_count
				FROM hes
				JOIN olt ON olt.hes_id = hes.id
				JOIN (
					SELECT olt_id, COUNT(*) AS cnt
					FROM onu
					GROUP BY olt_id
				) AS onu_cnt ON onu_cnt.olt_id = olt.id
				JOIN (
					SELECT olt_id, COUNT(*) AS cnt
					FROM olt_service
					GROUP BY olt_id
				) AS package_cnt ON package_cnt.olt_id = olt.id
				WHERE hes.building_id IN ($building_ids)
				GROUP BY hes.building_id;
			", MySQL::QUERY_ASSOC) ?: [];

			$counts = [];
			foreach($list as $item) {
				$counts[$item['building_id']] = $item;
			}

			foreach($buildings as &$b) {
				if(isset($counts[$b['id']])) {
					$c = $counts[$b['id']];
					$b['onu_count'] = $c['onu_count'];
					$b['package_count'] = $c['package_count'];
				} else {
					$b['onu_count'] = 0;
					$b['package_count'] = 0;
				}
			}
			unset($b);
		}

		return $this->success($buildings);
	}

	public function get_building() {
		$id = App::get('id', 0);
		$building = new ISPBuilding($id);
		if(!$building->validate()) return $this->error('Building not found.');

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Sites', 'route' => "/isp/$isp->id/site" ],
			[ 'description' => $client->record['name'], 'route' => "/isp/$isp->id/client/$client->id" ],
			[ 'description' => $building->record['description'], 'route' => "/isp/$isp->id/site/$building->id" ],
		];

		return $this->success([
			'details' => $building->record,
			'breadcrumbs' => $crumbs
		]);
	}

	public function list_areas() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();
		if(!isset($data['building'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		$building = $isp->get_building($data['building']);
		if(!$building) return $this->error('Site not found.');

		$list = App::sql('isp')->query(
			"SELECT
				onu.area_id,
				COUNT(*) as cnt
			FROM hes
			JOIN olt ON olt.hes_id = hes.id
			JOIN onu ON onu.olt_id = olt.id
			WHERE hes.building_id = '$building->id' AND onu.area_id <> olt.area_id
			GROUP BY onu.area_id
			HAVING cnt = 1;
		") ?: [];

		$areas = [];
		foreach($list as $item) {
			$areas[] = $item->area_id;
		}

		$result = [
			'areas' => [],
			'onus' => [],
			'customers' => []
		];

		// $list = App::sql('isp')->query("SELECT * FROM todo WHERE olt_id = '$this->id' ORDER BY datetime;", MySQL::QUERY_ASSOC);

		if(count($areas) > 0) {
			$areas = implode(',', $areas);

			$area_list = App::sql()->query(
				"SELECT
					a.id, a.description, a.isp_notes,
					f.description AS floor_description
				FROM area AS a
				JOIN floor AS f ON f.id = a.floor_id
				WHERE a.id IN ($areas)
				ORDER BY f.display_order, a.display_order, a.description;
			");

			$onu_list = App::sql('isp')->query(
				"SELECT
					onu.*,
					aolts.id AS active_package_id,
					aolts.description AS active_package_description,
					comm.cnt AS todo_count
				FROM onu
				LEFT JOIN (
					SELECT
						onu.id AS onu_id,
						MIN(olts.id) AS olts_id
					FROM onu
					JOIN olt ON olt.id = onu.olt_id
					JOIN hes ON hes.id = olt.hes_id
					JOIN olt_service AS olts ON olts.olt_id = olt.id
					JOIN onu_service AS onus ON onus.onu_table_id = onu.id AND olts.service_id = onus.olt_network_service_id AND olts.upstream_dba_profile_id = onus.profile_upstream_id AND olts.ethernet_profile_id = onus.profile_downstream_id AND onus.admin = 1
					WHERE hes.building_id = '$building->id'
					GROUP BY onu_id
				) AS active ON onu.id = active.onu_id
				LEFT JOIN olt_service AS aolts ON aolts.id = active.olts_id
				LEFT JOIN (
					SELECT
						onu.id,
						COUNT(todo.id) AS cnt
					FROM onu
					LEFT JOIN todo ON todo.onu_table_id = onu.id
					WHERE onu.area_id IN ($areas)
					GROUP BY onu.id
				) AS comm ON comm.id = onu.id
				WHERE onu.area_id IN ($areas);
			");

			$customer_list = App::sql()->query(
				"SELECT
					cu.id,
					c.area_id,
					cu.contact_name,
					cu.company_name,
					IF(custom.cnt IS NOT NULL, 1, 0) AS custom_pricing,
					pkg.isp_package_id AS contract_package_id
				FROM contract AS c
				LEFT JOIN (
					SELECT
						c_c.id,
						COUNT(c_cil.id) AS cnt,
						MIN(c_cil.isp_package_id) AS isp_package_id
					FROM contract AS c_c
					JOIN contract_invoice AS c_ci ON c_ci.contract_id = c_c.id
					JOIN contract_invoice_line AS c_cil ON c_cil.contract_invoice_id = c_ci.id
					WHERE c_c.owner_type = 'SI' AND c_c.owner_id = '$isp->id' AND c_cil.type = 'isp_package_custom'
					GROUP BY c_c.id
				) AS custom ON custom.id = c.id
				LEFT JOIN (
					SELECT
						c_c.id,
						MIN(c_cil.isp_package_id) AS isp_package_id
					FROM contract AS c_c
					JOIN contract_invoice AS c_ci ON c_ci.contract_id = c_c.id
					JOIN contract_invoice_line AS c_cil ON c_cil.contract_invoice_id = c_ci.id
					WHERE c_c.owner_type = 'SI' AND c_c.owner_id = '$isp->id' AND c_cil.type IN ('isp_package', 'isp_package_custom')
					GROUP BY c_c.id
				) AS pkg ON pkg.id = c.id
				JOIN customer AS cu ON cu.id = c.customer_id AND c.customer_type = 'CU'
				WHERE c.area_id IN ($areas) AND c.status IN ('pending', 'active', 'ending') AND c.owner_type = 'SI' AND c.owner_id = '$isp->id'
				ORDER BY cu.contact_name, cu.company_name;
			");

			$result['areas'] = $area_list ?: [];
			$result['onus'] = $onu_list ?: [];
			$result['customers'] = $customer_list ?: [];
		}

		return $this->success($result);
	}

	public function get_area() {
		$id = App::get('id', 0, true);
		$area = new ISPArea($id);
		if(!$area->validate()) return $this->access_denied();

		$building = $area->get_building();
		if(!$building) return $this->access_denied();

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp || !$isp->validate()) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Sites', 'route' => "/isp/$isp->id/site" ],
			[ 'description' => $client->record['name'], 'route' => "/isp/$isp->id/client/$client->id" ],
			[ 'description' => $building->record['description'], 'route' => "/isp/$isp->id/site/$building->id" ],
			[ 'description' => $area->record['description'], 'route' => '' ]
		];

		$area_info = $area->get_info(['expand']);

		// Quick work-around to avoid listing contracts from multiple owners
		$contracts = App::sql()->query("SELECT * FROM contract WHERE area_id = '$id' AND customer_type = 'CU' AND owner_type = 'SI' AND owner_id = '$isp->id';", MySQL::QUERY_ASSOC);
		$area_info['contracts'] = isp_info(isp_instance_list('Contract', $contracts));

		return $this->success([
			'area' => $area_info,
			'building' => $building->get_info(['packages']),
			'breadcrumbs' => $crumbs
		]);
	}

	public function set_onu_package() {
		//TODO: Security

		$onu_id = App::get('onu', 0);
		$package_id = App::get('pkg', 0);

		$onu = new ISPONU($onu_id);
		if(!$onu->validate()) return $this->access_denied();

		$package = null;
		if($package_id) {
			$package = new ISPPackage($package_id);
			if(!$package->validate()) return $this->access_denied();
		}

		$onu->set_package($package);

		return $this->success();
	}

	public function reboot_onu() {
		//TODO: Security

		$onu_id = App::get('onu', 0);

		$onu = new ISPONU($onu_id);
		if(!$onu->validate()) return $this->access_denied();

		$onu->reboot();

		return $this->success();
	}

	public function set_wifi_settings() {
		$data = App::json();
		$onu_id = $data['id'];
		$wifi_ssid = $data['wifi_ssid'];
		$wifi_password = $data['wifi_password'];

		$onu = new ISPONU($onu_id);
		if(!$onu->validate()) return $this->access_denied();

		if($onu->set_wifi_settings($wifi_ssid, $wifi_password)) {
			return $this->success();
		}

		return $this->error('Cannot update record.');
	}

	public function todo_wifi_settings() {
		$data = App::json();
		$onu_id = $data['id'];
		$wifi_ssid = $data['wifi_ssid'];
		$wifi_password = $data['wifi_password'];

		$onu = new ISPONU($onu_id);
		if(!$onu->validate()) return $this->access_denied();

		if($onu->todo_wifi_settings($wifi_ssid, $wifi_password)) {
			return $this->success();
		}

		return $this->error('Cannot update record.');
	}

	public function pending_wifi_settings(){
		$data = App::json();
		$onu_id = $data['id'];
		$wifi_ssid = $data['wifi_ssid'];
		$wifi_password = $data['wifi_password'];

		$onu = new ISPONU($onu_id);
		if(!$onu->validate()) return $this->access_denied();

		if($onu->set_pending_wifi_settings($wifi_ssid, $wifi_password)) {
			return $this->success();
		}

		return $this->error('Cannot update record.');
	}

	/** Remove Saved Pending Wifi Password */
	public function cancel_wifi_settings() {
		$data = App::json();
		$onu_id = $data['id'];

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		if(!$id) return $this->access_denied();

		//Create record
		$record = $data;
		$record = App::ensure($record, ['pending_wifi_password'], null);
		// $record = App::ensure($record, ['pending_wifi_ssid'], null)


		// Update record
		$list = App::upsert('onu@isp', $onu_id, $record);
		if(!$list) return $this->error('Error saving data.');
		return $this->success($list);
	}

	public function list_packages() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		if(isset($data['building'])) {
			$building = $isp->get_building($data['building']);
			if(!$building) return $this->error('Site not found.');
			$buildings = [$building];
		} else {
			$buildings = $isp->list_buildings();
		}

		$result = [];
		foreach($buildings as $building) {
			$packages = $building->list_packages();
			if(count($packages) > 0) {
				$data = $building->get_info();
				$data['packages'] = isp_info($packages, ['expand']);
				$result[] = $data;
			}
		}

		return $this->success($result);
	}

	public function get_package() {
		$id = App::get('id', 0);
		$p = new ISPPackage($id);
		if(!$p->validate()) return $this->access_denied();

		$olt = $p->get_olt();
		if(!$olt) return $this->access_denied();

		$hes = $olt->get_hes();
		if(!$hes) return $this->access_denied();

		$building = $hes->get_building();
		if(!$building) return $this->access_denied();

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp || !$isp->validate()) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Packages', 'route' => "/isp/$isp->id/package" ],
			[ 'description' => $client->record['name'], 'route' => "/isp/$isp->id/client/$client->id" ],
			[ 'description' => $building->record['description'], 'route' => "/isp/$isp->id/site/$building->id" ],
			[ 'description' => $p->record['description'], 'route' => '' ],
		];

		return $this->success([
			'details' => $p->get_info(['expand']),
			'breadcrumbs' => $crumbs
		]);
	}

	public function save_package() {
		// TODO: Security

		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, ['description', 'monthly_price']);
		$record = App::ensure($record, ['description'], '');
		$record = App::ensure($record, ['monthly_price'], 0);

		// Data validation
		if($record['description'] === '') return $this->error('Please enter package description.');

		// Insert/update record
		$id = App::upsert('olt_service@isp', $id, $record);
		if(!$id) return $this->error('Error saving data.');
		return $this->success($id);
	}

	public function list_customers() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		$archived = isset($data['archived']) ? ($data['archived'] ? 1 : 0) : 0;
		$active_contracts = isset($data['active_contracts']) ? ($data['active_contracts'] ? 1 : 0) : 0;

		$active_contracts_filter = '';
		if($active_contracts) $active_contracts_filter = 'AND acs.cnt > 0';

		$list = App::sql()->query(
			"SELECT
				c.id, c.contact_name, c.company_name, c.reference_no, c.email_address, c.posttown, c.postcode, c.archived, c.notes,
				COALESCE(tx.balance, 0) AS balance,
				COALESCE(tx.pending, 0) AS pending,
				-(COALESCE(tx.outstanding, 0)) AS outstanding,
				COALESCE(cc.cc_ok, 0) AS cc_ok,
				COALESCE(dd.dd_ok, 0) AS dd_ok,
				COALESCE(acs.cnt, 0) AS active_contract_count,
				acs.building_name
			FROM customer AS c

			LEFT JOIN payment_account AS pa ON pa.owner_type = c.owner_type AND pa.owner_id = c.owner_id AND pa.customer_type = 'CU' AND pa.customer_id = c.id
			LEFT JOIN (
				SELECT
					tpa.id,
					SUM(IF(ttx.status = 'ok', ttx.amount, 0)) AS balance,
					SUM(IF(ttx.status = 'pending', ttx.amount, 0)) AS pending,
					SUM(IF(ttx.status = 'ok' OR ttx.status = 'pending', ttx.amount, 0)) AS outstanding
				FROM payment_account AS tpa
				JOIN payment_transaction AS ttx ON ttx.account_id = tpa.id
				WHERE tpa.owner_type = 'SI' AND tpa.owner_id = '$isp->id' AND tpa.customer_type = 'CU'
				GROUP BY tpa.id
			) AS tx ON pa.id = tx.id

			LEFT JOIN (
				SELECT
					ccc.customer_id,
					MAX(IF(ccc.stripe_customer IS NULL OR ccc.stripe_customer = '' OR ccc.last4 IS NULL OR ccc.last4 = '', 0, 1)) AS cc_ok
				FROM payment_stripe_card AS ccc
				JOIN payment_gateway AS ccpg ON ccpg.id = ccc.payment_gateway_id AND ccpg.owner_type = 'SI' AND ccpg.owner_id = '$isp->id'
				WHERE ccc.customer_type = 'CU'
				GROUP BY ccc.customer_id
			) AS cc ON cc.customer_id = c.id

			LEFT JOIN (
				SELECT
					ddm.customer_id,
					MAX(IF(ddm.status = 'authorised', 1, 0)) AS dd_ok
				FROM payment_gocardless_mandate AS ddm
				JOIN payment_gateway AS ddpg ON ddpg.id = ddm.payment_gateway_id AND ddpg.owner_type = 'SI' AND ddpg.owner_id = '$isp->id'
				WHERE ddm.customer_type = 'CU'
				GROUP BY ddm.customer_id
			) AS dd ON dd.customer_id = c.id

			LEFT JOIN (
				SELECT
					acs_c.customer_id, COUNT(acs_c.id) AS cnt,
					GROUP_CONCAT(DISTINCT acs_b.description ORDER BY acs_b.description ASC SEPARATOR '\n') AS building_name
				FROM contract AS acs_c
				LEFT JOIN area AS acs_a ON acs_a.id = acs_c.area_id
				LEFT JOIN floor AS acs_f ON acs_f.id = acs_a.floor_id
				LEFT JOIN building AS acs_b ON acs_b.id = acs_f.building_id
				WHERE acs_c.owner_type = 'SI' AND acs_c.owner_id = '$isp->id' AND acs_c.customer_type = 'CU' AND acs_c.status IN ('active', 'ending')
				GROUP BY acs_c.customer_id
			) AS acs ON acs.customer_id = c.id

			WHERE c.owner_type = 'SI' AND c.owner_id = '$isp->id' AND c.archived = '$archived'
			$active_contracts_filter
			ORDER BY c.contact_name, c.company_name;
		");

		return $this->success([
			'list' => $list ?: [],
			/** Generate CSV of Customers */
			'csv_url' => APP_URL.'/ajax/get/customer_csv_export'
		]);
	}

	public function get_customer() {
		$id = App::get('id', 0);
		$customer = new Customer($id);
		if(!$customer->validate()) return $this->error('Customer not found.');

		$isp = null;
		if($customer->record['owner_type'] === 'SI') {
			$isp = new ISP($customer->record['owner_id']);
		}
		if(!$isp || !$isp->validate()) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Customers', 'route' => "/isp/$isp->id/customer" ],
			[ 'description' => $customer->get_name(), 'route' => "/isp/$isp->id/customer/$customer->id" ],
		];

		$pa = new PaymentAccount($customer->record['owner_type'], $customer->record['owner_id'], 'CU', $customer->id);
		$transactions = App::sql()->query(
			"SELECT
				*,
				IF(type = 'dd' AND status = 'pending', 1, 0) AS can_cancel
			FROM payment_transaction
			WHERE account_id = '$pa->id'
			ORDER BY create_datetime DESC, id DESC;
		");

		$cards = App::sql()->query(
			"SELECT
				c.payment_gateway_id, c.customer_type, c.customer_id, c.card_type, c.exp_month, c.exp_year, c.last4
			FROM payment_stripe_card AS c
			JOIN payment_gateway AS pg ON pg.id = c.payment_gateway_id
			WHERE
				pg.owner_type = 'SI' AND pg.owner_id = '$isp->id'
				AND c.customer_type = 'CU' AND c.customer_id = '$customer->id'
				AND c.stripe_customer IS NOT NULL AND c.last4 IS NOT NULL
				AND c.stripe_customer <> '' AND c.last4 <> '';
		") ?: [];

		$mandates = App::sql()->query(
			"SELECT
				m.payment_gateway_id, m.customer_type, m.customer_id, m.status, m.gocardless_mandate_id
			FROM payment_gocardless_mandate AS m
			JOIN payment_gateway AS pg ON pg.id = m.payment_gateway_id
			WHERE
				pg.owner_type = 'SI' AND pg.owner_id = '$isp->id'
				AND m.customer_type = 'CU' AND m.customer_id = '$customer->id'
				AND m.gocardless_mandate_id IS NOT NULL AND m.gocardless_mandate_id <> '';
		", MySQL::QUERY_ASSOC) ?: [];

		$active_contracts = App::sql()->query(
			"SELECT id
			FROM contract
			WHERE
				customer_type = 'CU' AND customer_id = '$customer->id'
				AND status IN ('unconfirmed', 'not_signed', 'pending', 'active', 'ending');
		") ?: [];

		$balance = $pa->get_balance();

		$active_mandate = false;
		foreach($mandates as $m) {
			if($m['status'] === 'authorised') $active_mandate = true;
		}

		$archive_warnings = [];
		if(count($active_contracts) > 0) $archive_warnings[] = 'Customer has an active contract.';
		if($balance !== 0) $archive_warnings[] = 'Customer has non-zero balance.';
		if(count($cards) > 0) $archive_warnings[] = 'Customer has a credit card on file.';
		if($active_mandate) $archive_warnings[] = 'Customer has an active Direct Debit mandate.';

		$record = $customer->record;
		unset($record['password']);

		return $this->success([
			'details' => $record,
			'archive_warnings' => $archive_warnings,
			'info' => $customer->get_info(),
			'account' => [
				'id' => $pa->id,
				'balance' => $balance,
				'outstanding' => $pa->get_outstanding(),
				'transactions' => $transactions ?: [],
				'cards' => $cards,
				'mandates' => $mandates,
				'url' => $pa->get_account_url()
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function new_customer() {
		$isp = App::get('isp', 0);

		$isp = new ISP($isp);
		if(!$isp->validate()) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Customers', 'route' => "/isp/$isp->id/customer" ],
			[ 'description' => 'New Customer', 'route' => '' ],
		];

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_type' => 'SI',
				'owner_id' => $isp->id,
				'allow_login' => 0
			],
			'archive_warnings' => [
				'Creating a new customer as archived.'
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function save_customer() {
		$data = App::json();
		$formname = $data['contact_name'];

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		$isp_id = isset($data['owner_id']) ? $data['owner_id'] : null;
		if(!$id || !$isp_id) {
			return $this->access_denied();
		}

		// Check permissions
		$isp = new ISP($isp_id);
		if(!$isp->validate()) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'contact_name', 'company_name', 'reference_no', 'email_address', 'phone_number', 'mobile_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'invoice_address_line_1', 'invoice_address_line_2', 'invoice_address_line_3', 'invoice_posttown', 'invoice_postcode',
			'notes', 'archived', 'password', 'allow_login'
		]);
		$record = App::ensure($record, ['contact_name', 'company_name', 'email_address', 'password', 'reference_no'], '');
		$record = App::ensure($record, ['archived', 'allow_login'], 0);
		
		$record['email_address'] = trim(strtolower($record['email_address']));
		$record['allow_login'] = $record['allow_login'] ? 1 : 0;
		
		


		// Data validation
		$record['archived'] = $record['archived'] ? 1 : 0;
		if($record['contact_name'] === '' && $record['company_name'] === '') {
			return $this->error('Please enter contact name or company name.');
		}
		
		if($record['password']) {
			$record['password'] = password_hash($record['password'], PASSWORD_DEFAULT);
		} else {
			unset($record['password']);
		}

		// Insert/update record
		$jah = $record['contact_name'];
		
		$just = App::sql()->query_row("SELECT * FROM customer WHERE contact_name LIKE '" . strtoupper(substr($jah,0,4)) . "%'");


		if(empty($record['reference_no'])){

			$number = 1;
			$name = explode(' ', $record['contact_name']);
			$firstname = $name[0];
			$record['reference_no'] = strtoupper(substr($firstname, 0, 4)) . str_pad($number, 3, '0', STR_PAD_LEFT);


			$row = App::sql()->query_row("SELECT COUNT(*) FROM customer WHERE reference_no LIKE '" . strtoupper(substr($firstname,0,4)) . "%' AND owner_id = '4'");
			$array = json_encode((array)$row);
			
		
			$remove_front = substr($array,12);
			$string_sum = rtrim($remove_front,"}");
			$sum = intval($string_sum);	



			$final = explode(' ', $just->contact_name);
			$justfinal = $final[0];
			$Ref_Name = substr($justfinal, 0,4);

			
			if($id === 'new') {
				$record['owner_type'] = 'SI';
				$record['owner_id'] = $isp->id;
				
			}

			if($sum >= 1){
				$number = $sum;

				$newvar = $number +1;
				$var = strval($newvar);
				
				$record['reference_no'] = strtoupper($Ref_Name). str_pad($var, 3, '0', STR_PAD_LEFT);
				
				$compare_bool = false;

				$check_existing_ref =  App::sql()->query("SELECT * FROM customer WHERE reference_no LIKE '" . strtoupper(substr($firstname,0,4)) . "%' AND owner_id = 4;");
				
				
				foreach($check_existing_ref as $item){
					if($item->reference_no == $record['reference_no']){
						
						
						do {
							$increvar = $newvar + 1;

							$var = strval($increvar);
							$record['reference_no'] = strtoupper($Ref_Name). str_pad($var, 3, '0', STR_PAD_LEFT);
							

						}while ($compare_bool == true);
					}
				}
			}

			$id = App::upsert('customer', $id, $record);
			if(!$id) return $this->error('Error saving data.');
			return $this->success($id);

		}
		
		$id = App::upsert('customer', $id, $record);
		if(!$id) return $this->error('Error saving data.');
		return $this->success($id);
	}

	public function new_transaction() {
		$data = App::json();

		// Check permissions
		$account_id = isset($data['account_id']) ? $data['account_id'] : null;
		$account = App::select('payment_account', $account_id);

		if(!$account) return $this->error('Account not found.');
		if($account['owner_type'] !== 'SI') return $this->error('Unsupported owner type.');

		$isp = new ISP($account['owner_id']);
		if(!$isp || !$isp->validate()) return $this->access_denied();

		$pa = new PaymentAccount($account['owner_type'], $account['owner_id'], $account['customer_type'], $account['customer_id']);

		// Create record
		$record = $data;
		$record = App::keep($record, ['type', 'amount', 'description', 'transaction_ref']);
		$record = App::ensure($record, ['type', 'description', 'transaction_ref'], '');
		$record = App::ensure($record, ['amount'], 0);

		// Data validation
		if(!$record['description']) return $this->error('Please enter description.');

		$amount = abs($record['amount']);
		if($amount == 0) return $this->error('Invalid amount.');

		switch($record['type']) {
			case 'cash':
			case 'other_credit':
				break;

			case 'refund':
			case 'other_debit':
				$amount = -$amount;
				break;

			default:
				return $this->error('Invalid transaction type.');
		}

		// Insert/update record
		$pa->add_manual_transaction($record['type'], $amount, $record['description'], $record['transaction_ref']);
		$pa->process_after_balance_changed();

		return $this->success();
	}

	public function cancel_transaction() {
		$id = App::get('id', 0, true);
		$txn = App::select('payment_transaction', $id);
		if(!$txn) return $this->error('Transaction not found.');

		$account = App::select('payment_account', $txn['account_id']);
		if(!$account) return $this->error('Customer account not found.');
		if($account['owner_type'] !== 'SI') return $this->error('Unsupported account type.');

		$isp_id = $account['owner_id'];
		$isp = new ISP($isp_id);
		if(!$isp->validate()) return $this->access_denied();

		$pg = new PaymentGateway($txn['payment_gateway_id']);
		if(!$pg->is_valid()) return $this->error('Invalid payment gateway.');

		try {
			$result = $pg->cancel_payment($txn['transaction_ref']);
			if(!$result) return $this->error('Unable to cancel payment.');
			return $this->success();
		} catch(Exception $ex) {
			return $this->error($ex->getMessage());
		}
	}

	public function list_contracts() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		$data = App::json();
		$data = App::keep($data, ['customer']);
		$data = App::ensure($data, ['customer']);
		$data = App::escape($data);

		$extra_condition = '';
		if($data['customer']) $extra_condition = "AND c.customer_type = 'CU' AND c.customer_id = '$data[customer]'";

		$list = App::sql()->query(
			"SELECT
				c.id, c.area_id, c.start_date, c.end_date, c.status, c.description,
				c.customer_type, c.customer_id,
				b.id AS building_id,
				a.description AS area_description,
				b.description AS building_description,
				CASE c.customer_type
					WHEN 'SP' THEN c_sp.company_name
					WHEN 'SI' THEN c_si.company_name
					WHEN 'HG' THEN c_hg.company_name
					WHEN 'C' THEN c_c.name
					WHEN 'CU' THEN IF(COALESCE(c_cu.contact_name, '') <> '' AND COALESCE(c_cu.company_name, '') <> '', CONCAT(c_cu.contact_name, ', ', c_cu.company_name), COALESCE(NULLIF(c_cu.contact_name, ''), c_cu.company_name))
					ELSE ''
				END AS customer_name

			FROM contract AS c

			LEFT JOIN area AS a ON a.id = c.area_id
			LEFT JOIN floor AS f ON f.id = a.floor_id
			LEFT JOIN building AS b ON b.id = f.building_id

			LEFT JOIN service_provider AS c_sp ON c.customer_type = 'SP' AND c.customer_id = c_sp.id
			LEFT JOIN system_integrator AS c_si ON c.customer_type = 'SI' AND c.customer_id = c_si.id
			LEFT JOIN holding_group AS c_hg ON c.customer_type = 'HG' AND c.customer_id = c_hg.id
			LEFT JOIN client AS c_c ON c.customer_type = 'C' AND c.customer_id = c_c.id
			LEFT JOIN customer AS c_cu ON c.customer_type = 'CU' AND c.customer_id = c_cu.id

			WHERE c.owner_type = 'SI' AND c.owner_id = '$isp->id' AND c.is_template = 0
			$extra_condition
			ORDER BY start_date;
		") ?: [];

		return $this->success($list);
	}

	public function list_contract_templates() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		return $this->success(isp_info($isp->list_contract_templates()));
	}

	public function get_contract() {
		$id = App::get('id', 0);
		if(!$id) return $this->access_denied();

		$contract = new Contract($id);
		if(!$contract->validate()) return $this->error('Contract not found.');

		$isp = new ISP($contract->record['owner_id']);
		if(!$isp->validate()) return $this->access_denied();

		$card_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = 'SI' AND owner_id = '$isp->id' AND type = 'stripe' ORDER BY description;", MySQL::QUERY_ASSOC);
		$dd_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = 'SI' AND owner_id = '$isp->id' AND type = 'gocardless' ORDER BY description;", MySQL::QUERY_ASSOC);
		$pdf_contracts = App::sql()->query("SELECT id, name FROM pdf_contract_template WHERE owner_type = 'SI' AND owner_id = '$isp->id' AND archived = 0 ORDER BY name;");

		$crumbs = [
			[ 'description' => 'Contracts', 'route' => "/isp/$isp->id/contract" ],
			[ 'description' => $contract->record['description'], 'route' => '' ],
		];

		$buildings = isp_info($isp->list_buildings(), ['areas', 'packages']) ?: [];

		return $this->success([
			'details' => $contract->get_info(['expand']),
			'list' => [
				'buildings' => $buildings,
				'card_gateways' => $card_gateways ?: [],
				'dd_gateways' => $dd_gateways ?: [],
				'pdf_contracts' => $pdf_contracts ?: []
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function new_contract() {
		$isp = App::get('isp', 0);
		$customer = App::get('customer', 0);
		$template_id = App::get('template', 0);

		$isp = new ISP($isp);
		if(!$isp->validate()) return $this->access_denied();

		$template = null;
		if($template_id) {
			$template = new Contract($template_id);
			if(!$template->validate() || $template->record['owner_type'] !== 'SI' && $template->record['owner_id'] !== $isp->id) $template = null;
		}

		$set_customer_address = false;
		$set_customer_invoice_address = false;

		if($customer) {
			$is_template = false;
			$customer = new Customer($customer);
			if(!$customer->validate()) return $this->error('Customer not found.');

			$crumbs = [
				[ 'description' => 'Customers', 'route' => "/isp/$isp->id/customer" ],
				[ 'description' => $customer->get_name(), 'route' => "/isp/$isp->id/customer/$customer->id" ],
				[ 'description' => 'New Contract', 'route' => '' ],
			];

			$record = $customer->record;
			if(!$record['address_line_1'] && !$record['address_line_2'] && !$record['address_line_3'] && !$record['posttown'] && !$record['postcode']) $set_customer_address = true;
			if(!$record['invoice_address_line_1'] && !$record['invoice_address_line_2'] && !$record['invoice_address_line_3'] && !$record['invoice_posttown'] && !$record['invoice_postcode']) $set_customer_invoice_address = true;
		} else {
			$is_template = true;
			$crumbs = [
				[ 'description' => 'Contracts', 'route' => "/isp/$isp->id/contract" ],
				[ 'description' => 'New Contract Template', 'route' => '' ],
			];
		}

		$start_date = date('Y-m-d');
		$end_date = date('Y-m-d', strtotime('+6 months', strtotime($start_date)));
		$end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));

		$card_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = 'SI' AND owner_id = '$isp->id' AND type = 'stripe' ORDER BY description;", MySQL::QUERY_ASSOC);
		$dd_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = 'SI' AND owner_id = '$isp->id' AND type = 'gocardless' ORDER BY description;", MySQL::QUERY_ASSOC);
		$pdf_contracts = App::sql()->query("SELECT id, name FROM pdf_contract_template WHERE owner_type = 'SI' AND owner_id = '$isp->id' AND archived = 0 ORDER BY name;");

		if($template) {
			$details = $template->get_info(['expand']);
			$details['id'] = 'new';

			foreach($details['invoices'] as &$invoice) {
				$invoice['id'] = 'new';
				foreach($invoice['lines'] as &$line) {
					$line['id'] = 'new';
				}
				unset($line);
			}
			unset($invoice);

			if(!$is_template) {
				$details['customer_type'] = 'CU';
				$details['customer_id'] = $customer->id;
				$details['is_template'] = 0;

				$details['reference_no'] = ($details['reference_no'] ?: '').($customer->record['reference_no'] ?: '');

				$start_date = date('Y-m-d');
				$end_date = null;
				if($details['term'] && $details['term_units']) {
					$end_date = date('Y-m-d', strtotime("+$details[term] $details[term_units]", strtotime($start_date)));
					$end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
				}

				$details['start_date'] = $start_date;
				$details['end_date'] = $end_date;
				$details['provides_access'] = 0;

				if($details['pdf_contract_id']) {
					if(in_array($details['status'], ['unconfirmed', 'pending'])) $details['status'] = 'not_signed';
				}
			}
		} else {
			$details = [
				'id' => 'new',
				'owner_type' => 'SI',
				'owner_id' => $isp->id,
				'customer_type' => $is_template ? null : 'CU',
				'customer_id' => $is_template ? null : $customer->id,
				'building_id' => null,
				'area_id' => null,
				'reference_no' => $customer ? $customer->record['reference_no'] : '',
				'description' => '',
				'status' => 'pending',
				'start_date' => $is_template ? null : $start_date,
				'end_date' => $is_template ? null : $end_date,
				'term' => 6,
				'term_units' => 'month',
				'contract_term' => 'variable',
				'is_template' => $is_template ? 1 : 0,
				'skip_past_invoices' => 0,
				'provides_access' => 0,
				'instant_activation_email' => 0,
				'invoices' => [],
				'pdf_contract_id' => null
			];
		}

		$details['set_customer_address'] = $set_customer_address;
		$details['set_customer_invoice_address'] = $set_customer_invoice_address;

		return $this->success([
			'details' => $details,
			'list' => [
				'buildings' => $is_template ? [] : isp_info($isp->list_buildings(), ['areas', 'packages']),
				'card_gateways' => $card_gateways ?: [],
				'dd_gateways' => $dd_gateways ?: [],
				'pdf_contracts' => $pdf_contracts ?: []
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function save_contract() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		$isp_id = isset($data['owner_id']) ? $data['owner_id'] : null;
		if(!$id || !$isp_id) {
			return $this->access_denied();
		}

		// Check permissions
		$isp = new ISP($isp_id);
		if(!$isp->validate()) return $this->access_denied();

		// Create records
		$record = $data;
		$record = App::keep($record, [
			'owner_type', 'owner_id', 'customer_type', 'customer_id', 'area_id',
			'reference_no', 'description', 'status',
			'start_date', 'end_date',
			'term', 'term_units', 'contract_term', 'is_template', 'skip_past_invoices', 'provides_access', 'instant_activation_email',
			'invoices', 'invoices_deleted',
			'set_customer_address', 'set_customer_invoice_address',
			'pdf_contract_id'
		]);
		$record = App::ensure($record, ['owner_type', 'owner_id', 'customer_type', 'customer_id', 'area_id', 'start_date', 'end_date', 'term_units', 'contract_term', 'pdf_contract_id'], null);
		$record = App::ensure($record, ['term', 'is_template', 'skip_past_invoices', 'provides_access', 'instant_activation_email'], 0);
		$record = App::ensure($record, ['reference_no', 'description'], '');
		$record = App::ensure($record, ['invoices', 'invoices_deleted'], []);
		$record = App::ensure($record, ['status'], 'pending');
		$record = App::ensure($record, ['set_customer_address', 'set_customer_invoice_address'], false);

		$record['skip_past_invoices'] = $record['skip_past_invoices'] ? 1 : 0;
		$record['provides_access'] = $record['provides_access'] ? 1 : 0;
		$record['instant_activation_email'] = $record['instant_activation_email'] ? 1 : 0;
		$has_dd = false;

		$set_customer_address = $record['set_customer_address'];
		$set_customer_invoice_address = $record['set_customer_invoice_address'];
		unset($record['set_customer_address']);
		unset($record['set_customer_invoice_address']);

		foreach($record['invoices'] as &$iv) {
			$invoice = $iv;

			$invoice = App::keep($invoice, [
				'id', 'description', 'frequency', 'card_payment_gateway', 'dd_payment_gateway',
				'cutoff_day', 'issue_day', 'payment_day',
				'initial_card_payment', 'retry_dd_times', 'charge_card_if_dd_fails', 'charge_card_after_days', 'auto_charge_saved_card', 'manual_authorisation', 'mandatory_dd',
				'vat_rate', 'lines', 'lines_deleted'
			]);
			$invoice = App::ensure($invoice, ['frequency', 'card_payment_gateway', 'dd_payment_gateway'], null);
			$invoice = App::ensure($invoice, ['cutoff_day', 'issue_day', 'payment_day', 'initial_card_payment', 'retry_dd_times', 'charge_card_if_dd_fails', 'charge_card_after_days', 'auto_charge_saved_card', 'manual_authorisation', 'mandatory_dd', 'vat_rate'], 0);
			$invoice = App::ensure($invoice, ['description'], '');
			$invoice = App::ensure($invoice, ['id'], 'new');
			$invoice = App::ensure($invoice, ['lines', 'lines_deleted'], []);

			$invoice['initial_card_payment'] = $invoice['initial_card_payment'] ? 1 : 0;
			$invoice['charge_card_if_dd_fails'] = $invoice['charge_card_if_dd_fails'] ? 1 : 0;
			$invoice['auto_charge_saved_card'] = $invoice['auto_charge_saved_card'] ? 1 : 0;
			$invoice['manual_authorisation'] = $invoice['manual_authorisation'] ? 1 : 0;
			$invoice['mandatory_dd'] = $invoice['mandatory_dd'] ? 1 : 0;

			if(!$invoice['card_payment_gateway']) {
				$invoice['initial_card_payment'] = 0;
				$invoice['charge_card_if_dd_fails'] = 0;
				$invoice['auto_charge_saved_card'] = 0;
			}
			if(!$invoice['dd_payment_gateway']) {
				$invoice['initial_card_payment'] = 0;
				$invoice['charge_card_if_dd_fails'] = 0;
				$invoice['retry_dd_times'] = 0;
			} else {
				$has_dd = true;
			}

			foreach($invoice['lines'] as &$l) {
				$line = $l;

				$line = App::keep($line, [
					'id', 'type', 'isp_package_id',
					'icon', 'description', 'unit_price', 'quantity',
					'pro_rata', 'charge_type'
				]);
				$line = App::ensure($line, ['isp_package_id'], null);
				$line = App::ensure($line, ['unit_price', 'quantity', 'pro_rata'], 0);
				$line = App::ensure($line, ['icon', 'description'], '');
				$line = App::ensure($line, ['id'], 'new');
				$line = App::ensure($line, ['type'], 'custom');
				$line = App::ensure($line, ['charge_type'], 'always');

				$line['pro_rata'] = $line['pro_rata'] ? 1 : 0;

				$l = $line;
			}
			unset($l);

			$iv = $invoice;
		}
		unset($iv);

		// Data validation
		if($record['description'] === '') return $this->error('Please enter contract description.');
		if(!$record['is_template']) {
			if(!$record['customer_type'] || !$record['customer_id']) return $this->error('Please select customer.');
			if($record['customer_type'] === 'CU' && !$record['area_id']) return $this->error('Please select area.');
			if(!$record['start_date'] || !$record['end_date']) return $this->error('Select contract start and end date.');
		}
		foreach($record['invoices'] as $invoice) {
			if($invoice['description'] === '') return $this->error('Please enter invoice description.');
			switch($invoice['frequency']) {
				case ContractInvoice::FREQUENCY_MONTHLY_ARREARS:
					if(!$invoice['issue_day']) return $this->error('Issue day must be set for montly (in arrears) invoice frequency.');
					if(!$invoice['payment_day']) return $this->error('Payment day must be set for montly (in arrears) invoice frequency.');
					break;

				case ContractInvoice::FREQUENCY_MONTHLY_ADVANCE:
					if(!$invoice['cutoff_day']) return $this->error('Cutoff day must be set for montly (in advance) invoice frequency.');
					if(!$invoice['issue_day']) return $this->error('Issue day must be set for montly (in advance) invoice frequency.');
					if(!$invoice['payment_day']) return $this->error('Payment day must be set for montly (in advance) invoice frequency.');
					break;
			}

			foreach($invoice['lines'] as $line) {
				switch($line['type']) {
					case 'custom':
						if($line['description'] === '' || !$line['unit_price'] || !$line['quantity']) return $this->error('Custom invoice lines must have description, unit price and quantity set.');
						break;

					case 'isp_package':
						if(!$record['is_template'] && !$line['isp_package_id']) return $this->error('Please select ISP package for invoice line.');
						break;

					case 'isp_package_custom':
						if(!$record['is_template'] && !$line['isp_package_id']) return $this->error('Please select ISP package for invoice line.');
						if(!$line['unit_price']) return $this->error('Custom ISP packages must have unit price set.');
						break;
				}
			}
		}

		// Insert/update record
		$contract_ended = false;
		$is_new = $id === 'new';
		if($is_new) {
			$record['owner_type'] = 'SI';
			$record['owner_id'] = $isp->id;
		} else {
			if($record['status'] === Contract::STATUS_ENDED) {
				$original = App::select('contract', $id);
				if(in_array($original['status'], [Contract::STATUS_ACTIVE, Contract::STATUS_ENDING])) $contract_ended = true;
			}
		}

		$invoices = $record['invoices'];
		$invoices_deleted = $record['invoices_deleted'];
		unset($record['invoices']);
		unset($record['invoices_deleted']);

		$id = App::upsert('contract', $id, $record);
		if(!$id) return $this->error('Error saving contract.');

		foreach($invoices_deleted as $invoice_id) {
			$invoice_id = App::escape($invoice_id);
			App::sql()->delete("DELETE FROM contract_invoice_line WHERE contract_invoice_id = '$invoice_id';");
			App::sql()->delete("DELETE FROM contract_invoice WHERE id = '$invoice_id';");
		}

		foreach($invoices as $invoice) {
			$invoice_id = $invoice['id'];
			$lines = $invoice['lines'];
			$lines_deleted = $invoice['lines_deleted'];
			unset($invoice['id']);
			unset($invoice['lines']);
			unset($invoice['lines_deleted']);

			$invoice['contract_id'] = $id;

			$invoice_id = App::upsert('contract_invoice', $invoice_id, $invoice);
			if(!$invoice_id) return $this->error('Error saving invoice.');

			foreach($lines_deleted as $line_id) {
				App::sql()->delete("DELETE FROM contract_invoice_line WHERE id = '$line_id';");
			}

			foreach($lines as $line) {
				$line_id = $line['id'];
				unset($line['id']);

				$line['contract_invoice_id'] = $invoice_id;
				$line_id = App::upsert('contract_invoice_line', $line_id, $line);
				if(!$line_id) return $this->error('Error saving invoice line.');
			}
		}

		if(!$record['is_template']) {
			if($is_new && $record['customer_type'] === 'CU' && ($set_customer_address || $set_customer_invoice_address)) {
				$area = App::select('area', $record['area_id']);
				if($area) {
					$floor = App::select('floor', $area['floor_id']);
					if($floor) {
						$building = App::select('building', $floor['building_id']);
						if($building) {
							$new_address = [
								'address_line_1' => $area['description'],
								'address_line_2' => $building['address'],
								'address_line_3' => '',
								'posttown' => $building['posttown'],
								'postcode' => $area['postcode'] ? $area['postcode'] : $building['postcode'] // Postcode only override (if any)
							];
							if($area['address_line_1'] || $area['address_line_2'] || $area['address_line_3'] || $area['posttown']) {
								// Full address override
								$new_address = [
									'address_line_1' => $area['address_line_1'],
									'address_line_2' => $area['address_line_2'],
									'address_line_3' => $area['address_line_3'],
									'posttown' => $area['posttown'],
									'postcode' => $area['postcode']
								];
							}

							if($set_customer_address) {
								App::update('customer', $record['customer_id'], $new_address);
							}
							if($set_customer_invoice_address) {
								App::update('customer', $record['customer_id'], [
									'invoice_address_line_1' => $new_address['address_line_1'],
									'invoice_address_line_2' => $new_address['address_line_2'],
									'invoice_address_line_3' => $new_address['address_line_3'],
									'invoice_posttown' => $new_address['posttown'],
									'invoice_postcode' => $new_address['postcode']
								]);
							}
						}
					}
				}
			}

			$contract = new Contract($id);

			// Contract has been ended manually, fire event
			if($contract_ended) $contract->event_contract_ended();

			$contract->process();

			// Make sure we create a payment account for customer
			$pa = new PaymentAccount($contract->record['owner_type'], $contract->record['owner_id'], $contract->record['customer_type'], $contract->record['customer_id']);

			// Send not signed email if it hasn't been sent yet
			if($record['status'] === Contract::STATUS_NOT_SIGNED) {
				$contract->send_not_signed_email();
			}

			// Send activation email if needed
			if($record['instant_activation_email']) {
				$contract->send_activation_email();
			}
		}

		return $this->success($id);
	}

	public function list_invoices() {
		$data = App::json();
		if(!isset($data['isp'])) return $this->access_denied();

		$isp = new ISP($data['isp']);
		if(!$isp->validate()) return $this->access_denied();

		$counter = null;

		$filters = [];
		if(isset($data['date_from']) && $data['date_from']) $filters[] = "AND bill_date >= '$data[date_from]'";
		if(isset($data['date_to']) && $data['date_to']) $filters[] = "AND bill_date <= '$data[date_to]'";
		if(isset($data['status'])) {
			$filters[] = "AND status IN ('".implode("','", $data['status'])."')";
		}
		$filters = implode(' ', $filters);

		if(isset($data['customer'])) {
			$customer = new Customer($data['customer']);
			if(!$customer->validate()) return $this->error('Customer not found.');

			$list = App::sql()->query(
				"SELECT * FROM invoice
				WHERE owner_type = 'SI' AND owner_id = '$isp->id' AND customer_type = 'CU' AND customer_id = '$customer->id'
				$filters
				ORDER BY bill_date DESC, id DESC;
			");
		} else {
			$list = App::sql()->query(
				"SELECT * FROM invoice
				WHERE owner_type = 'SI' AND owner_id = '$isp->id'
				$filters
				ORDER BY bill_date DESC, id DESC;
			");

			$counter = App::sql()->query_row(
				"SELECT * FROM invoice_counter
				WHERE owner_type = 'SI' AND owner_id = '$isp->id'
				LIMIT 1;
			");
		}

		return $this->success([
			'list' => $list ?: [],
			'counter' => $counter,
			'csv_url' => APP_URL.'/ajax/get/invoice_csv_export'
		]);
	}

	public function get_invoice_counter() {
		$owner_type = App::get('owner_type', null, true);
		$owner_id = App::get('owner_id', 0, true);
		if($owner_type !== 'SI' || !$owner_id) return $this->access_denied();

		$isp = new ISP($owner_id);
		if(!$isp->validate()) return $this->access_denied();

		$counter = App::sql()->query_row(
			"SELECT * FROM invoice_counter
			WHERE owner_type = '$owner_type' AND owner_id = '$owner_id'
			LIMIT 1;
		");

		if(!$counter) return $this->error('Invoice counter not found.');

		return $this->success($counter);
	}

	public function save_invoice_counter() {
		$data = App::json();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_type', 'owner_id', 'last_no']);
		$record = App::ensure($record, ['owner_type', 'owner_id'], null);
		$record = App::ensure($record, ['last_no'], 0);

		$owner_type = $record['owner_type'];
		$owner_id = $record['owner_id'];
		$last_no = $record['last_no'];

		// Check permissions
		if($owner_type !== 'SI') return $this->access_denied();
		$isp = new ISP($owner_id);
		if(!$isp->validate()) return $this->access_denied();

		App::sql()->update("UPDATE invoice_counter SET last_no = '$last_no' WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';");

		return $this->success();
	}

	public function get_invoice() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$invoice = App::select('invoice', $id);
		if(!$invoice) return $this->error('Invoice not found.');

		if($invoice['owner_type'] !== 'SI') return $this->access_denied();
		$isp = new ISP($invoice['owner_id']);
		if(!$isp->validate()) return $this->access_denied();

		$lines = App::sql()->query("SELECT * FROM invoice_line WHERE invoice_id = '$id';");

		$customer = Customer::resolve_details($invoice['customer_type'], $invoice['customer_id']) ?: Customer::resolve_details();

		$logo_url = '';
		if($isp->record['logo_on_light_id']) {
			$uc = new UserContent($isp->record['logo_on_light_id']);
			if($uc->info) $logo_url = $uc->get_url();
		}
		if($invoice['invoice_entity_id']) {
			$ie = App::select('invoice_entity', $invoice['invoice_entity_id']);
			if($ie && $ie['image_id']) {
				$uc = new UserContent($ie['image_id']);
				if($uc->info) $logo_url = $uc->get_url();
			}
		}

		$crumbs = [];
		$crumbs[] = [ 'description' => 'Invoices', 'route' => "/isp/$isp->id/invoice" ];
		if($customer['customer_type'] === 'CU') $crumbs[] = [ 'description' => $customer['name'], 'route' => "/isp/$isp->id/customer/$customer[customer_id]/invoices" ];
		$crumbs[] = [ 'description' => "Invoice #$invoice[invoice_no]", 'route' => '' ];

		return $this->success([
			'invoice' => $invoice,
			'lines' => $lines ?: [],
			'logo_url' => $logo_url,
			'breadcrumbs' => $crumbs,
			'print_url' => APP_URL.'/ajax/get/print_invoice?id='.$id
		]);
	}

	public function set_invoice_no() {
		$id = App::get('id', 0, true);
		$invoice_no = App::get('invoice_no', '', 0);
		if(!$id) return $this->access_denied();
		if(!$invoice_no) return $this->error('Invalid invoice number.');

		$invoice = new Invoice($id);
		if(!$invoice->validate()) return $this->error('Invoice not found.');

		if($invoice->record['owner_type'] !== 'SI') return $this->access_denied();
		$isp = new ISP($invoice->record['owner_id']);
		if(!$isp->validate()) return $this->access_denied();

		if($invoice->record['status'] !== 'not_approved') return $this->error('Invoice has already been approved.');

		App::update('invoice', $id, [ 'invoice_no' => $invoice_no ]);

		return $this->success();
	}

	public function set_invoice_status() {
		$id = App::get('id', 0, true);
		$status = App::get('status', '', true);
		if(!$id) return $this->access_denied();
		if(!in_array($status, ['paid', 'outstanding', 'cancelled'])) return $this->error('Invalid status.');

		$invoice = new Invoice($id);
		if(!$invoice->validate()) return $this->error('Invoice not found.');

		if($invoice->record['owner_type'] !== 'SI') return $this->access_denied();
		$isp = new ISP($invoice->record['owner_id']);
		if(!$isp->validate()) return $this->access_denied();

		switch($status) {
			case 'paid':
				$result = $invoice->mark_as_paid();
				if(!$result) return $this->error('Cannot mark invoice as paid.');
				$pa = $invoice->get_payment_account();
				if($pa) $pa->process_after_balance_changed();
				break;

			case 'outstanding':
				$result = $invoice->mark_as_outstanding();
				if(!$result) return $this->error('Cannot mark invoice as outstanding.');
				break;

			case 'cancelled':
				$result = $invoice->mark_as_cancelled();
				if(!$result) return $this->error('Cannot cancel invoice.');
				$pa = $invoice->get_payment_account();
				if($pa) $pa->process_after_balance_changed();
				break;
		}

		return $this->success();
	}

	public function approve_invoice() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$invoice = new Invoice($id);
		if(!$invoice->validate()) return $this->error('Invoice not found.');

		// Always allow Eticom
		if(!Permission::get_eticom()->check(Permission::ADMIN)) {
			if($invoice->record['owner_type'] !== 'SI') return $this->access_denied();
			$isp = new ISP($invoice->record['owner_id']);
			if(!$isp->validate()) return $this->access_denied();
		}

		$dd = true;
		$ci = App::select('contract_invoice', $invoice->record['contract_invoice_id']);
		if($ci && $ci['initial_card_payment']) {
			// Check if this is the first invoice. If so, don't charge.
			if($invoice->record['is_first']) $dd = false;
		}

		$invoice->approve_invoice($dd);

		return $this->success();
	}

	public function resend_invoice_email() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$invoice = new Invoice($id);
		if(!$invoice->validate()) return $this->error('Invoice not found.');

		// Always allow Eticom
		if(!Permission::get_eticom()->check(Permission::ADMIN)) {
			if($invoice->record['owner_type'] !== 'SI') return $this->access_denied();
			$isp = new ISP($invoice->record['owner_id']);
			if(!$isp->validate()) return $this->access_denied();
		}

		$invoice->resend_email();

		return $this->success();
	}

	public function delete_card() {
		$payment_gateway_id = App::get('payment_gateway_id', 0, true);
		$customer_type = App::get('customer_type', '', true);
		$customer_id = App::get('customer_id', 0, true);

		if(!$payment_gateway_id || !$customer_type || !$customer_id) return $this->error('Invalid parameters.');

		$pg = new PaymentGateway($payment_gateway_id);
		if(!$pg->is_valid() || $pg->record['owner_type'] !== 'SI' || !$pg->record['stripe_user_id']) return $this->access_denied();

		$isp = new ISP($pg->record['owner_id']);
		if(!$isp->validate()) return $this->access_denied();

		$card = App::sql()->query_row("SELECT * FROM payment_stripe_card WHERE payment_gateway_id = '$payment_gateway_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id';", MySQL::QUERY_ASSOC);
		if(!$card || !$card['stripe_customer']) return $this->error('Card not found.');

		\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

		try {
			$cu = \Stripe\Customer::retrieve($card['stripe_customer'], [
				'stripe_account' => $pg->record['stripe_user_id']
			]);
			$cu->delete();
		} catch(Exception $ex) {
			return $this->error('Cannot delete customer record.');
		}

		App::sql()->delete("DELETE FROM payment_stripe_card WHERE payment_gateway_id = '$payment_gateway_id' AND customer_type = '$customer_type' AND customer_id = '$customer_id';");

		return $this->success();
	}

	public function cancel_mandate() {
		$payment_gateway_id = App::get('payment_gateway_id', 0, true);
		$customer_type = App::get('customer_type', '', true);
		$customer_id = App::get('customer_id', 0, true);

		if(!$payment_gateway_id || !$customer_type || !$customer_id) return $this->error('Invalid parameters.');

		$pg = new PaymentGateway($payment_gateway_id);
		if(!$pg->is_valid() || $pg->record['owner_type'] !== 'SI') return $this->access_denied();

		$isp = new ISP($pg->record['owner_id']);
		if(!$isp->validate()) return $this->access_denied();

		$mandate = new PaymentGoCardlessMandate($payment_gateway_id, $customer_type, $customer_id);
		if(!$mandate->is_valid()) return $this->error('Mandate not found.');

		if(!$mandate->cancel()) return $this->error('Unable to cancel mandate.');

		return $this->success();
	}

	public function send_customer_email() {
		$data = App::json();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_type', 'owner_id', 'template_type', 'customers']);
		$record = App::ensure($record, ['owner_type', 'owner_id'], null);
		$record = App::ensure($record, ['template_type'], '');
		$record = App::ensure($record, ['customers'], []);

		$owner_type = $record['owner_type'];
		$owner_id = $record['owner_id'];
		$template_type = $record['template_type'];
		$customers = $record['customers'] ?: [];

		// Check permissions
		if($owner_type !== 'SI') return $this->access_denied();
		$isp = new ISP($owner_id);
		if(!$isp->validate()) return $this->access_denied();

		// Data validation
		if(!$template_type) {
			return $this->error('Please select an email template.');
		}

		foreach($customers as $customer_id) {
			$customer = Customer::resolve_details('CU', $customer_id);
			if($customer && $customer['email_address']) {
				$crec = App::select('customer', $customer_id);
				if($crec && $crec['owner_type'] === $owner_type && $crec['owner_id'] == $owner_id) {
					$pa = new PaymentAccount($owner_type, $owner_id, 'CU', $customer_id);

					// Select first customer contract for context
					$contract = null;
					$cdata = App::sql()->query_row("SELECT id FROM contract WHERE owner_type = '$crec[owner_type]' AND owner_id = '$crec[owner_id]' AND customer_type = 'CU' AND customer_id = '$crec[id]' AND status NOT IN ('cancelled', 'ended') LIMIT 1;", MySQL::QUERY_ASSOC);
					if($cdata) $contract = new Contract($cdata['id']);

					Mailer::send_from_template($owner_type, $owner_id, $template_type, $customer['email_address'], $customer['name'] ?: '', [
						'customer' => $customer,
						'payment_account' => $pa,
						'contract' => $contract,
						'invoice' => $contract ? $contract->get_last_period_invoice() : null
					]);
				}
			}
		}

		return $this->success();
	}

	public function update_area_note() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'notes'], null);

		$id = App::escape($data['id']);
		$notes = $data['notes'];

		if(!$id) return $this->access_denied();

		$area = new ISPArea($id);
		if(!$area->validate()) return $this->access_denied();

		$building = $area->get_building();
		if(!$building) return $this->access_denied();

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp || !$isp->validate()) return $this->access_denied();

		App::update('area', $id, [
			'isp_notes' => $notes
		]);

		return $this->success();
	}

	public function list_onu_types() {
		$id = App::get('id', 0, true);
		$building = new ISPBuilding($id);
		if(!$building->validate()) return $this->error('Building not found.');

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp) return $this->access_denied();

		$list = App::sql('isp')->query(
			"SELECT
				t.id,
				t.description,
				COUNT(o.id) AS onu_count
			FROM onu_type AS t
			LEFT JOIN onu AS o ON o.type_id = t.id
			WHERE t.building_id = '$id'
			GROUP BY t.id, t.description
			ORDER BY t.description;
		") ?: [];

		$buildings = null;
		$bid = App::sql('isp')->query("SELECT DISTINCT building_id FROM onu_type;", MySQL::QUERY_ASSOC);
		if($bid) {
			$bid = array_map(function($item) { return $item['building_id']; }, $bid);
			$bid = "'".implode("','", $bid)."'";
			$buildings = App::sql()->query("SELECT id, description FROM building WHERE id IN ($bid) AND id <> '$id' ORDER BY description;") ?: [];
		}

		return $this->success([
			'list' => $list,
			'buildings' => $buildings
		]);
	}

	public function copy_onu_types() {
		// TODO: Security on target

		$id = App::get('id', 0, true);
		$target_id = App::get('target_id', 0, true);
		$building = new ISPBuilding($id);
		if(!$building->validate()) return $this->error('Building not found.');

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp) return $this->access_denied();

		App::sql('isp')->insert(
			"INSERT INTO onu_type (building_id, description, info_offline, info_reboot)
			SELECT '$target_id', description, info_offline, info_reboot FROM onu_type WHERE building_id = '$id'
		");

		return $this->success();
	}

	public function new_onu_type() {
		$building_id = App::get('id', 0, true);
		if(!$building_id) return $this->access_denied();

		$building = new ISPBuilding($building_id);
		if(!$building->validate()) return $this->error('Building not found.');

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp) return $this->access_denied();

		$record = [
			'id' => 'new',
			'building_id' => $building_id
		];

		$asset_list = [];
		$assets = App::sql('isp')->query("SELECT user_content_id FROM onu_type_assets;", MySQL::QUERY_ASSOC) ?: [];
		foreach($assets as $a) {
			$uc_id = $a['user_content_id'];
			$uc = new UserContent($uc_id);
			if($uc->info) {
				$asset_list[] = [
					'user_content_id' => $uc_id,
					'url' => $uc->get_url()
				];
			}
		}

		$record['assets'] = $asset_list;

		$crumbs = [
			[ 'description' => 'Sites', 'route' => "/isp/$isp->id/site" ],
			[ 'description' => $client->record['name'], 'route' => "/isp/$isp->id/client/$client->id" ],
			[ 'description' => $building->record['description'], 'route' => "/isp/$isp->id/site/$building->id/onu-types" ],
			[ 'description' => 'New ONU Type', 'route' => '' ]
		];

		return $this->success([
			'details' => $record,
			'can_delete' => false,
			'breadcrumbs' => $crumbs
		]);
	}

	public function get_onu_type() {
		$id = App::get('id', '', true);
		if(!$id) return $this->access_denied();

		$record = App::select('onu_type@isp', $id);
		if(!$record) return $this->error('ONU type not found');

		$building = new ISPBuilding($record['building_id']);
		if(!$building->validate()) return $this->error('Building not found.');

		$client = $building->get_client();
		if(!$client) return $this->access_denied();

		$isp = $client->get_isp();
		if(!$isp) return $this->access_denied();

		$asset_list = [];
		$assets = App::sql('isp')->query("SELECT user_content_id FROM onu_type_assets;", MySQL::QUERY_ASSOC) ?: [];
		foreach($assets as $a) {
			$uc_id = $a['user_content_id'];
			$uc = new UserContent($uc_id);
			if($uc->info) {
				$asset_list[] = [
					'user_content_id' => $uc_id,
					'url' => $uc->get_url()
				];
			}
		}

		$record['assets'] = $asset_list;

		$can_delete = !App::sql('isp')->query_row("SELECT id FROM onu WHERE type_id = '$id' LIMIT 1;");

		$crumbs = [
			[ 'description' => 'Sites', 'route' => "/isp/$isp->id/site" ],
			[ 'description' => $client->record['name'], 'route' => "/isp/$isp->id/client/$client->id" ],
			[ 'description' => $building->record['description'], 'route' => "/isp/$isp->id/site/$building->id/onu-types" ],
			[ 'description' => $record['description'], 'route' => '' ]
		];

		return $this->success([
			'details' => $record,
			'can_delete' => $can_delete,
			'breadcrumbs' => $crumbs
		]);
	}

	public function save_onu_type() {
		// TODO: Security

		$data = App::json();

		// Create record
		$record = $data;
		$record = App::keep($record, ['id', 'building_id', 'description', 'info_offline', 'info_reboot']);
		$record = App::ensure($record, ['id', 'building_id', 'description', 'info_offline', 'info_reboot'], '');

		// Data validation
		if(!$record['description']) return $this->error('Please enter description.');

		// Insert/update record
		$id = $record['id'];
		unset($record['id']);
		$id = App::upsert('onu_type@isp', $id, $record);
		if(!$id) return $this->error('Error updating ONU type. Please try again later.');

		// Delete old assets
		App::sql('isp')->delete("DELETE FROM onu_type_assets;");

		// Save module assets
		$assets = $data['assets'] ?: [];
		foreach($assets as $a) {
			$asset_id = App::escape($a['user_content_id']);
			App::insert('onu_type_assets@isp', ['user_content_id' => $asset_id]);
		}

		return $this->success();
	}

	public function delete_onu_type() {
		// TODO: Security

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$exists = App::sql('isp')->query_row("SELECT id FROM onu WHERE type_id = '$id' LIMIT 1;");
		if($exists) return $this->error('Cannot delete, ONU type is in use.');

		App::delete('onu_type@isp', $id);

		return $this->success();
	}

}