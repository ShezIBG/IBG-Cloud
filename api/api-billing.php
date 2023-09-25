<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	private function list_billing_owners() {
		$result = [];

		$list = Permission::list_service_providers([ 'with' => Permission::BILLING_ENABLED ]) ?: [];
		foreach($list as $item) $result[] = [ 'key' => "SP-$item->id", 'type' => 'SP', 'id' => $item->id, 'description' => $item->company_name ];

		$list = Permission::list_system_integrators([ 'with' => Permission::BILLING_ENABLED ]) ?: [];
		foreach($list as $item) $result[] = [ 'key' => "SI-$item->id", 'type' => 'SI', 'id' => $item->id, 'description' => $item->company_name ];

		$list = Permission::list_holding_groups([ 'with' => Permission::BILLING_ENABLED ]) ?: [];
		foreach($list as $item) $result[] = [ 'key' => "HG-$item->id", 'type' => 'HG', 'id' => $item->id, 'description' => $item->company_name ];

		$list = Permission::list_clients([ 'with' => Permission::BILLING_ENABLED ]) ?: [];
		foreach($list as $item) $result[] = [ 'key' => "C-$item->id", 'type' => 'C', 'id' => $item->id, 'description' => $item->name ];

		return $result;
	}

	private function owner_condition($owner) {
		switch($owner['type']) {
			case PermissionLevel::SERVICE_PROVIDER:		return "sp.id = '$owner[id]'";
			case PermissionLevel::SYSTEM_INTEGRATOR:	return "si.id = '$owner[id]'";
			case PermissionLevel::HOLDING_GROUP:		return "hg.id = '$owner[id]'";
			case PermissionLevel::CLIENT:				return "c.id = '$owner[id]'";
			default:									throw new AccessDeniedException();
		}
	}

	private function resolve_owner($owner = '') {
		if(!$owner) $owner = App::get('owner', '');
		if(!$owner) {
			try {
				$data = App::json();
				$owner = $data['owner'];
			} catch(Exception $ex) {
				$owner = '';
			}
		};

		if(!strpos($owner, '-')) throw new AccessDeniedException();
		$chunks = explode('-', $owner);
		$owner_type = $chunks[0];
		$owner_id = $chunks[1];

		if(!Permission::get($owner_type, $owner_id)->check(Permission::BILLING_ENABLED)) throw new AccessDeniedException();

		$record = null;

		switch($owner_type) {
			case PermissionLevel::SERVICE_PROVIDER:
				$record = App::select('service_provider', $owner_id);
				if($record) $desc = $record['company_name'];
				break;

			case PermissionLevel::SYSTEM_INTEGRATOR:
				$record = App::select('system_integrator', $owner_id);
				if($record) $desc = $record['company_name'];
				break;

			case PermissionLevel::HOLDING_GROUP:
				$record = App::select('holding_group', $owner_id);
				if($record) $desc = $record['company_name'];
				break;

			case PermissionLevel::CLIENT:
				$record = App::select('client', $owner_id);
				if($record) $desc = $record['name'];
				break;

		}

		if(!$record) throw new AccessDeniedException();

		return [ 'key' => "$owner_type-$owner_id", 'type' => $owner_type, 'id' => $owner_id, 'description' => $desc ];
	}

	private function get_account_info($owner_type, $owner_id, $customer_type, $customer_id, $force = false) {
		if(!$force && !PaymentAccount::exists($owner_type, $owner_id, $customer_type, $customer_id)) return null;

		$pa = new PaymentAccount($owner_type, $owner_id, $customer_type, $customer_id);

		$transactions = App::sql()->query(
			"SELECT
				*,
				IF(type = 'dd' AND status = 'pending', 1, 0) AS can_cancel
			FROM payment_transaction
			WHERE account_id = '$pa->id'
			ORDER BY create_datetime DESC, id DESC;
		") ?: [];

		$cards = App::sql()->query(
			"SELECT
				c.payment_gateway_id, c.customer_type, c.customer_id, c.card_type, c.exp_month, c.exp_year, c.last4
			FROM payment_stripe_card AS c
			JOIN payment_gateway AS pg ON pg.id = c.payment_gateway_id
			WHERE
				pg.owner_type = '$pa->owner_type' AND pg.owner_id = '$pa->owner_id'
				AND c.customer_type = '$pa->customer_type' AND c.customer_id = '$pa->customer_id'
				AND c.stripe_customer IS NOT NULL AND c.last4 IS NOT NULL
				AND c.stripe_customer <> '' AND c.last4 <> '';
		") ?: [];

		$mandates = App::sql()->query(
			"SELECT
				m.payment_gateway_id, m.customer_type, m.customer_id, m.status, m.gocardless_mandate_id
			FROM payment_gocardless_mandate AS m
			JOIN payment_gateway AS pg ON pg.id = m.payment_gateway_id
			WHERE
				pg.owner_type = '$pa->owner_type' AND pg.owner_id = '$pa->owner_id'
				AND m.customer_type = '$pa->customer_type' AND m.customer_id = '$pa->customer_id'
				AND m.gocardless_mandate_id IS NOT NULL AND m.gocardless_mandate_id <> '';
		", MySQL::QUERY_ASSOC) ?: [];

		$balance = $pa->get_balance();

		return [
			'id' => $pa->id,
			'balance' => $balance,
			'outstanding' => $pa->get_outstanding(),
			'transactions' => $transactions,
			'cards' => $cards,
			'mandates' => $mandates,
			'url' => $pa->get_account_url()
		];
	}

	public function get_first_owner() {
		$list = $this->list_billing_owners();
		if(!$list) return $this->access_denied();

		return $this->success($list[0]['key']);
	}

	public function get_navigation() {
		$owner = $this->resolve_owner();

		$dropdown = [];

		$owners = $this->list_billing_owners();
		foreach($owners as $item) {
			$icon = '';
			switch($item['type']) {
				case PermissionLevel::SERVICE_PROVIDER:		$icon = 'md md-filter-drama';	break;
				case PermissionLevel::SYSTEM_INTEGRATOR:	$icon = 'md md-local-shipping';	break;
				case PermissionLevel::HOLDING_GROUP:		$icon = 'md md-group-work';		break;
				case PermissionLevel::CLIENT:				$icon = 'md md-work';			break;
			}
			$dropdown[] = [ 'name' => $item['description'], 'icon' => $icon, 'route' => "/billing/$item[key]", 'selected' => $item['key'] == $owner['key'] ];
		}

		$nav = [];

		$nav[] = [ 'name' => $owner['description'], 'header' => true ];
		$nav[] = [ 'name' => 'Overview', 'icon' => 'md md-home', 'route' => "/billing/$owner[key]/overview" ];
		if(PermissionLevel::lte($owner['type'], PermissionLevel::SERVICE_PROVIDER)) $nav[] = [ 'name' => 'System Integrators', 'icon' => 'md md-local-shipping', 'route' => "/billing/$owner[key]/system-integrator" ];
		if(PermissionLevel::lte($owner['type'], PermissionLevel::SYSTEM_INTEGRATOR)) $nav[] = [ 'name' => 'Clients', 'icon' => 'md md-work', 'route' => "/billing/$owner[key]/client" ];
		$nav[] = [ 'name' => 'Sites', 'icon' => 'md md-place', 'route' => "/billing/$owner[key]/site" ];
		$nav[] = [ 'name' => 'Customers', 'icon' => 'md md-person', 'route' => "/billing/$owner[key]/customer" ];
		$nav[] = [ 'name' => 'Contracts', 'icon' => 'md md-account-balance', 'route' => "/billing/$owner[key]/contract" ];
		$nav[] = [ 'name' => 'Invoicing Entities', 'icon' => 'md md-folder', 'route' => "/billing/$owner[key]/invoice-entity" ];
		$nav[] = [ 'name' => 'Invoices', 'icon' => 'md md-insert-drive-file', 'route' => "/billing/$owner[key]/invoice" ];

		return $this->success([
			'dropdown' => $dropdown,
			'menu' => $nav
		]);
	}

	public function get_overview() {
		$owner = $this->resolve_owner();

		$system_integrators = null;
		$clients = null;
		$buildings = null;
		$customers = null;

		switch($owner['type']) {
			case PermissionLevel::SERVICE_PROVIDER:
				$system_integrators = App::sql()->query_row(
					"SELECT COUNT(*) AS cnt
					FROM system_integrator AS si
					WHERE si.service_provider_id = '$owner[id]';
				");
				$system_integrators = $system_integrators ? $system_integrators->cnt : 0;

				$clients = App::sql()->query_row(
					"SELECT COUNT(*) AS cnt
					FROM client AS c
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					WHERE si.service_provider_id = '$owner[id]';
				");
				$clients = $clients ? $clients->cnt : 0;

				$buildings = App::sql()->query_row(
					"SELECT COUNT(*) AS cnt
					FROM building AS b
					JOIN client AS c ON c.id = b.client_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					WHERE si.service_provider_id = '$owner[id]' AND b.is_tenanted = 1;
				");
				$buildings = $buildings ? $buildings->cnt : 0;
				break;

			case PermissionLevel::SYSTEM_INTEGRATOR:
				$clients = App::sql()->query_row(
					"SELECT COUNT(*) AS cnt
					FROM client AS c
					WHERE c.system_integrator_id = '$owner[id]';
				");
				$clients = $clients ? $clients->cnt : 0;

				$buildings = App::sql()->query_row(
					"SELECT COUNT(*) AS cnt
					FROM building AS b
					JOIN client AS c ON c.id = b.client_id
					WHERE c.system_integrator_id = '$owner[id]' AND b.is_tenanted = 1;
				");
				$buildings = $buildings ? $buildings->cnt : 0;
				break;

			case PermissionLevel::HOLDING_GROUP:
				$buildings = App::sql()->query_row(
					"SELECT COUNT(*) AS cnt
					FROM building AS b
					JOIN client AS c ON c.id = b.client_id
					WHERE c.holding_group_id = '$owner[id]' AND b.is_tenanted = 1;
				");
				$buildings = $buildings ? $buildings->cnt : 0;
				break;

			case PermissionLevel::CLIENT:
				$buildings = App::sql()->query_row(
					"SELECT COUNT(*) AS cnt
					FROM building AS b
					WHERE b.client_id = '$owner[id]' AND b.is_tenanted = 1;
				");
				$buildings = $buildings ? $buildings->cnt : 0;
				break;
		}

		$customers = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM customer WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]';");
		$customers = $customers ? $customers->cnt : 0;

		$totals = [];

		$end_date = date('Y-m-d');
		$start_date = date('Y-m-d', strtotime('-30 days', strtotime($end_date)));

		$r = App::sql()->query_row(
			"SELECT
				SUM(amount) AS revenue,
				SUM(IF(pt.type = 'dd', amount, 0)) AS revenue_dd,
				SUM(IF(pt.type = 'card', amount, 0)) AS revenue_card,
				SUM(IF(pt.type NOT IN ('dd', 'card'), amount, 0)) AS revenue_other
			FROM payment_transaction AS pt
			JOIN payment_account AS pa ON pa.id = pt.account_id
			WHERE
				pa.owner_type = '$owner[type]' AND pa.owner_id = '$owner[id]'
				AND pt.status = 'ok' AND pt.amount > 0;
		", MySQL::QUERY_ASSOC);
		$totals['revenue'] = ($r ? $r['revenue'] : 0) ?: 0;
		$totals['revenue_dd'] = ($r ? $r['revenue_dd'] : 0) ?: 0;
		$totals['revenue_card'] = ($r ? $r['revenue_card'] : 0) ?: 0;
		$totals['revenue_other'] = ($r ? $r['revenue_other'] : 0) ?: 0;

		$r = App::sql()->query_row(
			"SELECT
				SUM(amount) AS revenue_30,
				SUM(IF(pt.type = 'dd', amount, 0)) AS revenue_30_dd,
				SUM(IF(pt.type = 'card', amount, 0)) AS revenue_30_card,
				SUM(IF(pt.type NOT IN ('dd', 'card'), amount, 0)) AS revenue_30_other
			FROM payment_transaction AS pt
			JOIN payment_account AS pa ON pa.id = pt.account_id
			WHERE
				pa.owner_type = '$owner[type]' AND pa.owner_id = '$owner[id]'
				AND pt.status = 'ok' AND pt.amount > 0
				AND pt.create_datetime BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59';
		", MySQL::QUERY_ASSOC);
		$totals['revenue_30'] = ($r ? $r['revenue_30'] : 0) ?: 0;
		$totals['revenue_30_dd'] = ($r ? $r['revenue_30_dd'] : 0) ?: 0;
		$totals['revenue_30_card'] = ($r ? $r['revenue_30_card'] : 0) ?: 0;
		$totals['revenue_30_other'] = ($r ? $r['revenue_30_other'] : 0) ?: 0;

		$r = App::sql()->query_row(
			"SELECT
				SUM(IF(pt.status = 'pending', pt.amount, 0)) AS pending,
				SUM(IF(pt.status = 'pending', 1, 0)) AS pending_count,
				SUM(IF(pt.status = 'pending' AND pt.type = 'dd', pt.amount, 0)) AS pending_dd,
				SUM(IF(pt.status = 'pending' AND pt.type = 'card', pt.amount, 0)) AS pending_card,
				-SUM(pt.amount) AS outstanding,
				SUM(IF(pt.status = 'fail' AND pt.type = 'dd', 1, 0)) AS failed_dd,
				SUM(IF(pt.status = 'fail' AND pt.type = 'card', 1, 0)) AS failed_card
			FROM payment_transaction AS pt
			JOIN payment_account AS pa ON pa.id = pt.account_id
			WHERE
				pa.owner_type = '$owner[type]' AND pa.owner_id = '$owner[id]'
				AND pt.status IN ('ok', 'pending');
		", MySQL::QUERY_ASSOC);
		$totals['pending'] = ($r ? $r['pending'] : 0) ?: 0;
		$totals['pending_dd'] = ($r ? $r['pending_dd'] : 0) ?: 0;
		$totals['pending_card'] = ($r ? $r['pending_card'] : 0) ?: 0;
		$totals['pending_count'] = ($r ? $r['pending_count'] : 0) ?: 0;
		$totals['outstanding'] = ($r ? $r['outstanding'] : 0) ?: 0;
		$totals['failed_dd'] = ($r ? $r['failed_dd'] : 0) ?: 0;
		$totals['failed_card'] = ($r ? $r['failed_card'] : 0) ?: 0;

		$r = App::sql()->query_row(
			"SELECT
				COUNT(*) AS outstanding_invoices
			FROM invoice
			WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND status = 'outstanding';
		", MySQL::QUERY_ASSOC);
		$totals['outstanding_invoices'] = ($r ? $r['outstanding_invoices'] : 0) ?: 0;

		// Check if there is a GoCardless account that needs action

		$pg = App::sql()->query_row(
			"SELECT id
			FROM payment_gateway
			WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND archived = 0 AND type = 'gocardless' AND authorised = 1 AND gocardless_status = 'action_required'
			LIMIT 1;
		") ?: [];

		$gocardless_warning = false;
		$pg_url = '';
		if($pg) {
			$pg = new PaymentGateway($pg->id);
			$gocardless_warning = true;
			$pg_url = $pg->get_account_url_path();

			// TODO: Once the whole app is migrated to Angular, swap directly to the v3 address
			$pg_url = APP_URL.'/admin?path='.urlencode($pg_url);
		}

		return $this->success([
			'owner' => $owner,
			'system_integrators' => $system_integrators,
			'clients' => $clients,
			'buildings' => $buildings,
			'customers' => $customers,
			'totals' => $totals,
			'gocardless_warning' => $gocardless_warning,
			'pg_url' => $pg_url
		]);
	}

	public function list_system_integrators() {
		$owner = $this->resolve_owner();

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$list = App::sql()->query(
			"SELECT
				si.id, si.company_name, si.posttown, si.postcode, pa.id AS payment_account_id,
				COALESCE(tx.balance, 0) AS balance,
				COALESCE(tx.pending, 0) AS pending,
				-(COALESCE(tx.outstanding, 0)) AS outstanding,
				COALESCE(cc.cc_ok, 0) AS cc_ok,
				COALESCE(dd.dd_ok, 0) AS dd_ok
			FROM system_integrator AS si
			JOIN service_provider AS sp ON sp.id = si.service_provider_id

			LEFT JOIN payment_account AS pa ON pa.owner_type = '$owner[type]' AND pa.owner_id = '$owner[id]' AND pa.customer_type = 'SI' AND pa.customer_id = si.id
			LEFT JOIN (
				SELECT
					tpa.id,
					SUM(IF(ttx.status = 'ok', ttx.amount, 0)) AS balance,
					SUM(IF(ttx.status = 'pending', ttx.amount, 0)) AS pending,
					SUM(IF(ttx.status = 'ok' OR ttx.status = 'pending', ttx.amount, 0)) AS outstanding
				FROM payment_account AS tpa
				JOIN payment_transaction AS ttx ON ttx.account_id = tpa.id
				WHERE tpa.owner_type = '$owner[type]' AND tpa.owner_id = '$owner[id]' AND tpa.customer_type = 'SI'
				GROUP BY tpa.id
			) AS tx ON pa.id = tx.id

			LEFT JOIN (
				SELECT
					ccc.customer_id,
					MAX(IF(ccc.stripe_customer IS NULL OR ccc.stripe_customer = '' OR ccc.last4 IS NULL OR ccc.last4 = '', 0, 1)) AS cc_ok
				FROM payment_stripe_card AS ccc
				JOIN payment_gateway AS ccpg ON ccpg.id = ccc.payment_gateway_id AND ccpg.owner_type = '$owner[type]' AND ccpg.owner_id = '$owner[id]'
				WHERE ccc.customer_type = 'SI'
				GROUP BY ccc.customer_id
			) AS cc ON cc.customer_id = si.id

			LEFT JOIN (
				SELECT
					ddm.customer_id,
					MAX(IF(ddm.status = 'authorised', 1, 0)) AS dd_ok
				FROM payment_gocardless_mandate AS ddm
				JOIN payment_gateway AS ddpg ON ddpg.id = ddm.payment_gateway_id AND ddpg.owner_type = '$owner[type]' AND ddpg.owner_id = '$owner[id]'
				WHERE ddm.customer_type = 'SI'
				GROUP BY ddm.customer_id
			) AS dd ON dd.customer_id = si.id

			$condition
			ORDER BY si.company_name;
		", MySQL::QUERY_ASSOC);

		return $this->success($list);
	}

	public function get_system_integrator() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition[] = "si.id = '$id'";
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$record = App::sql()->query_row(
			"SELECT
				si.id, si.company_name,
				si.address_line_1, si.address_line_2, si.address_line_3, si.posttown, si.postcode,
				si.email_address, si.phone_number, si.mobile_number, si.vat_reg_number,
				si.bank_name, si.bank_sort_code, si.bank_account_number,
				si.invoice_address_line_1, si.invoice_address_line_2, si.invoice_address_line_3, si.invoice_posttown, si.invoice_postcode
			FROM system_integrator AS si
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$record) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'System Integrators', 'route' => "/billing/$owner[key]/system-integrator" ],
			[ 'description' => $record['company_name'], 'route' => "/billing/$owner[key]/system_integrator/$id" ],
		];

		return $this->success([
			'details' => $record,
			'account' => $this->get_account_info($owner['type'], $owner['id'], 'SI', $id),
			'breadcrumbs' => $crumbs
		]);
	}

	public function create_system_integrator_account() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition[] = "si.id = '$id'";
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$record = App::sql()->query_row(
			"SELECT
				si.id, si.company_name,
				si.address_line_1, si.address_line_2, si.address_line_3, si.posttown, si.postcode,
				si.phone_number, si.vat_reg_number,
				si.bank_name, si.bank_sort_code, si.bank_account_number,
				si.invoice_address_line_1, si.invoice_address_line_2, si.invoice_address_line_3, si.invoice_posttown, si.invoice_postcode
			FROM system_integrator AS si
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$record) return $this->access_denied();

		$pa = new PaymentAccount($owner['type'], $owner['id'], 'SI', $id);

		return $this->success();
	}

	public function list_clients() {
		$owner = $this->resolve_owner();

		$data = App::json();
		$data = App::ensure($data, ['si']);
		$data = App::escape($data);

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		if($data['si']) $condition[] = "si.id = '$data[si]'";
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$list = App::sql()->query(
			"SELECT
				c.id, c.name, c.posttown, c.postcode, pa.id AS payment_account_id,
				COALESCE(tx.balance, 0) AS balance,
				COALESCE(tx.pending, 0) AS pending,
				-(COALESCE(tx.outstanding, 0)) AS outstanding,
				COALESCE(cc.cc_ok, 0) AS cc_ok,
				COALESCE(dd.dd_ok, 0) AS dd_ok
			FROM client AS c
			LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			JOIN service_provider AS sp ON sp.id = si.service_provider_id

			LEFT JOIN payment_account AS pa ON pa.owner_type = '$owner[type]' AND pa.owner_id = '$owner[id]' AND pa.customer_type = 'C' AND pa.customer_id = c.id
			LEFT JOIN (
				SELECT
					tpa.id,
					SUM(IF(ttx.status = 'ok', ttx.amount, 0)) AS balance,
					SUM(IF(ttx.status = 'pending', ttx.amount, 0)) AS pending,
					SUM(IF(ttx.status = 'ok' OR ttx.status = 'pending', ttx.amount, 0)) AS outstanding
				FROM payment_account AS tpa
				JOIN payment_transaction AS ttx ON ttx.account_id = tpa.id
				WHERE tpa.owner_type = '$owner[type]' AND tpa.owner_id = '$owner[id]' AND tpa.customer_type = 'C'
				GROUP BY tpa.id
			) AS tx ON pa.id = tx.id

			LEFT JOIN (
				SELECT
					ccc.customer_id,
					MAX(IF(ccc.stripe_customer IS NULL OR ccc.stripe_customer = '' OR ccc.last4 IS NULL OR ccc.last4 = '', 0, 1)) AS cc_ok
				FROM payment_stripe_card AS ccc
				JOIN payment_gateway AS ccpg ON ccpg.id = ccc.payment_gateway_id AND ccpg.owner_type = '$owner[type]' AND ccpg.owner_id = '$owner[id]'
				WHERE ccc.customer_type = 'C'
				GROUP BY ccc.customer_id
			) AS cc ON cc.customer_id = c.id

			LEFT JOIN (
				SELECT
					ddm.customer_id,
					MAX(IF(ddm.status = 'authorised', 1, 0)) AS dd_ok
				FROM payment_gocardless_mandate AS ddm
				JOIN payment_gateway AS ddpg ON ddpg.id = ddm.payment_gateway_id AND ddpg.owner_type = '$owner[type]' AND ddpg.owner_id = '$owner[id]'
				WHERE ddm.customer_type = 'C'
				GROUP BY ddm.customer_id
			) AS dd ON dd.customer_id = c.id

			$condition
			ORDER BY c.name;
		", MySQL::QUERY_ASSOC) ?: [];

		return $this->success($list);
	}

	public function get_client() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition[] = "c.id = '$id'";
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$record = App::sql()->query_row(
			"SELECT
				c.id, c.name,
				c.email_address, c.phone_number, c.mobile_number, c.vat_reg_number,
				c.address_line_1, c.address_line_2, c.address_line_3, c.posttown, c.postcode,
				c.bank_name, c.bank_sort_code, c.bank_account_number,
				c.invoice_address_line_1, c.invoice_address_line_2, c.invoice_address_line_3, c.invoice_posttown, c.invoice_postcode,
				si.company_name AS system_integrator_name, si.id AS system_integrator_id
			FROM client AS c
			LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$record) return $this->access_denied();

		$crumbs = [];
		if($owner['type'] === PermissionLevel::SERVICE_PROVIDER) {
			$crumbs[] = [ 'description' => 'System Integrators', 'route' => "/billing/$owner[key]/system-integrator" ];
			$crumbs[] = [ 'description' => $record['system_integrator_name'], 'route' => "/billing/$owner[key]/system-integrator/$record[system_integrator_id]" ];
		}
		$crumbs[] = [ 'description' => 'Clients', 'route' => "/billing/$owner[key]/client" ];
		$crumbs[] = [ 'description' => $record['name'], 'route' => "/billing/$owner[key]/client/$id" ];

		return $this->success([
			'details' => $record,
			'account' => $this->get_account_info($owner['type'], $owner['id'], 'C', $id),
			'breadcrumbs' => $crumbs
		]);
	}

	public function create_client_account() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition[] = "c.id = '$id'";
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$record = App::sql()->query_row(
			"SELECT
				c.id, c.name, c.vat_reg_number,
				c.address_line_1, c.address_line_2, c.address_line_3, c.posttown, c.postcode,
				c.bank_name, c.bank_sort_code, c.bank_account_number,
				c.invoice_address_line_1, c.invoice_address_line_2, c.invoice_address_line_3, c.invoice_posttown, c.invoice_postcode,
				si.company_name AS system_integrator_name, si.id AS system_integrator_id
			FROM client AS c
			LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$record) return $this->access_denied();

		$pa = new PaymentAccount($owner['type'], $owner['id'], 'C', $id);

		return $this->success();
	}

	public function list_buildings() {
		$owner = $this->resolve_owner();
		$data = App::json();

		$data = App::ensure($data, ['client', 'si']);
		$data = App::escape($data);

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		if($data['si']) $condition[] = "si.id = '$data[si]'";
		if($data['client']) $condition[] = "c.id = '$data[client]'";
		$condition[] = 'b.is_tenanted = 1';
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$buildings = App::sql()->query(
			"SELECT
				b.id, b.description, b.postcode, b.posttown
			FROM building AS b
			JOIN client AS c ON c.id = b.client_id
			LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			ORDER BY b.description DESC;
		", MySQL::QUERY_ASSOC) ?: [];

		return $this->success($buildings);
	}

	public function get_building() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition[] = "b.id = '$id'";
		$condition[] = 'b.is_tenanted = 1';
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$record = App::sql()->query_row(
			"SELECT
				b.id, b.description, b.postcode, b.posttown, b.client_id, c.name AS client_name
			FROM building AS b
			JOIN client AS c ON c.id = b.client_id
			LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$record) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Sites', 'route' => "/billing/$owner[key]/site" ],
			[ 'description' => $record['client_name'], 'route' => "/billing/$owner[key]/client/$record[client_id]" ],
			[ 'description' => $record['description'], 'route' => "/billing/$owner[key]/site/$id" ],
		];

		return $this->success([
			'details' => $record,
			'breadcrumbs' => $crumbs
		]);
	}

	public function list_areas() {
		$owner = $this->resolve_owner();

		$data = App::json();

		if(!isset($data['building'])) return $this->access_denied();
		$building_id = App::escape($data['building']);

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition[] = "b.id = '$building_id'";
		$condition[] = 'b.is_tenanted = 1';
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$building = App::sql()->query_row(
			"SELECT
				b.id, b.description, b.postcode, b.posttown, b.client_id, c.name AS client_name
			FROM building AS b
			JOIN client AS c ON c.id = b.client_id
			LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$building) return $this->error('Building not found.');

		$area_list = App::sql()->query(
			"SELECT
				a.id, a.description,
				f.description AS floor_description,
				IF(mi.meter_e > 0, 1, 0) AS meter_e,
				IF(mi.meter_g > 0, 1, 0) AS meter_g,
				IF(mi.meter_w > 0, 1, 0) AS meter_w,
				IF(mi.meter_h > 0, 1, 0) AS meter_h
			FROM area AS a
			JOIN floor AS f ON f.id = a.floor_id
			LEFT JOIN (
				SELECT
					COALESCE(virtual_area_id, area_id) AS billing_area_id,
					SUM(IF(meter_type = 'E', 1, 0)) AS meter_e,
					SUM(IF(meter_type = 'G', 1, 0)) AS meter_g,
					SUM(IF(meter_type = 'W', 1, 0)) AS meter_w,
					SUM(IF(meter_type = 'H', 1, 0)) AS meter_h
				FROM meter
				GROUP BY billing_area_id
			) AS mi ON mi.billing_area_id = a.id
			WHERE f.building_id = '$building_id' AND (a.is_tenanted = 1 OR a.is_owner_occupied = 1)
			ORDER BY f.display_order, a.display_order, a.description;
		");

		$customer_list = App::sql()->query(
			"SELECT
				cu.id,
				c.area_id,
				cu.contact_name,
				cu.company_name,
				c.description,
				c.status
			FROM contract AS c
			JOIN customer AS cu ON cu.id = c.customer_id AND c.customer_type = 'CU'
			JOIN area AS a ON a.id = c.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$building_id' AND c.status IN ('pending', 'active', 'ending') AND c.owner_type = '$owner[type]' AND c.owner_id = '$owner[id]'
			ORDER BY cu.contact_name, cu.company_name;
		");

		return $this->success([
			'areas' => $area_list ?: [],
			'customers' => $customer_list ?: []
		]);
	}

	public function get_area() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0, true);

		$area = App::select('area', $id);
		if(!$area) return $this->error('Area not found.');

		$floor = App::select('floor', $area['floor_id']);
		if(!$floor) return $this->error('Floor not found.');

		$condition = [];
		$condition[] = $this->owner_condition($owner);
		$condition[] = "b.id = '$floor[building_id]'";
		$condition[] = 'b.is_tenanted = 1';
		$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

		$building = App::sql()->query_row(
			"SELECT
				b.id, b.description, b.postcode, b.posttown, b.client_id, c.name AS client_name
			FROM building AS b
			JOIN client AS c ON c.id = b.client_id
			LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			JOIN service_provider AS sp ON sp.id = si.service_provider_id
			$condition
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$building) return $this->access_denied();

		$client = App::select('client', $building['client_id']);
		if(!$client) return $this->error('Client not found.');

		// Add area details
		$area['floor_description'] = $floor['description'];
		$contracts = App::sql()->query("SELECT * FROM contract WHERE area_id = '$area[id]' AND owner_type = '$owner[type]' AND owner_id = '$owner[id]';", MySQL::QUERY_ASSOC);
		$area['contracts'] = isp_info(isp_instance_list('Contract', $contracts));

		$crumbs = [
			[ 'description' => 'Sites', 'route' => "/billing/$owner[key]/site" ],
			[ 'description' => $client['name'], 'route' => "/billing/$owner[key]/client/$client[id]" ],
			[ 'description' => $building['description'], 'route' => "/billing/$owner[key]/site/$building[id]" ],
			[ 'description' => $area['description'], 'route' => '' ]
		];

		return $this->success([
			'area' => $area,
			'breadcrumbs' => $crumbs
		]);
	}

	public function list_customers() {
		$owner = $this->resolve_owner();

		$data = App::json();

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
				COALESCE(acs.cnt, 0) AS active_contract_count
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
				WHERE tpa.owner_type = '$owner[type]' AND tpa.owner_id = '$owner[id]' AND tpa.customer_type = 'CU'
				GROUP BY tpa.id
			) AS tx ON pa.id = tx.id

			LEFT JOIN (
				SELECT
					ccc.customer_id,
					MAX(IF(ccc.stripe_customer IS NULL OR ccc.stripe_customer = '' OR ccc.last4 IS NULL OR ccc.last4 = '', 0, 1)) AS cc_ok
				FROM payment_stripe_card AS ccc
				JOIN payment_gateway AS ccpg ON ccpg.id = ccc.payment_gateway_id AND ccpg.owner_type = '$owner[type]' AND ccpg.owner_id = '$owner[id]'
				WHERE ccc.customer_type = 'CU'
				GROUP BY ccc.customer_id
			) AS cc ON cc.customer_id = c.id

			LEFT JOIN (
				SELECT
					ddm.customer_id,
					MAX(IF(ddm.status = 'authorised', 1, 0)) AS dd_ok
				FROM payment_gocardless_mandate AS ddm
				JOIN payment_gateway AS ddpg ON ddpg.id = ddm.payment_gateway_id AND ddpg.owner_type = '$owner[type]' AND ddpg.owner_id = '$owner[id]'
				WHERE ddm.customer_type = 'CU'
				GROUP BY ddm.customer_id
			) AS dd ON dd.customer_id = c.id

			LEFT JOIN (
				SELECT
					customer_id, COUNT(id) AS cnt
				FROM contract
				WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND customer_type = 'CU' AND status IN ('active', 'ending')
				GROUP BY customer_id
			) AS acs ON acs.customer_id = c.id

			WHERE c.owner_type = '$owner[type]' AND c.owner_id = '$owner[id]' AND c.archived = '$archived'
			$active_contracts_filter
			ORDER BY c.contact_name, c.company_name;
		");

		return $this->success($list ?: []);
	}

	public function list_customers_in_arrears() {
		$owner = $this->resolve_owner();

		$data = App::json();

		$active_contracts = isset($data['active_contracts']) ? ($data['active_contracts'] ? 1 : 0) : 0;

		$active_contracts_filter = '';
		if($active_contracts) $active_contracts_filter = 'AND acs.active_contract_count > 0';

		$list = App::sql()->query(
			"SELECT
				c.id, c.contact_name, c.company_name, c.reference_no, c.email_address,
				COALESCE(acs.active_contract_count, 0) AS active_contract_count,
				COALESCE(cc.cc_ok, 0) AS cc_ok,
				COALESCE(dd.dd_ok, 0) AS dd_ok,
				acs.active_contract_area,
				acs.active_contract_description,
				COALESCE(tx.balance, 0) AS balance,
				COALESCE(tx.pending, 0) AS pending,
				-(COALESCE(tx.outstanding, 0)) AS outstanding,
				COALESCE(inv.oustanding_invoice_count, 0) AS outstanding_invoice_count,
				inv.last_outstanding_invoice_date,
				inv.first_outstanding_invoice_date,
				tx.last_payment_date
			FROM customer AS c

			LEFT JOIN payment_account AS pa ON pa.owner_type = c.owner_type AND pa.owner_id = c.owner_id AND pa.customer_type = 'CU' AND pa.customer_id = c.id
			LEFT JOIN (
				SELECT
					tpa.id,
					SUM(IF(ttx.status = 'ok', ttx.amount, 0)) AS balance,
					SUM(IF(ttx.status = 'pending', ttx.amount, 0)) AS pending,
					SUM(IF(ttx.status = 'ok' OR ttx.status = 'pending', ttx.amount, 0)) AS outstanding,
					MAX(IF(ttx.amount > 0 AND ttx.status = 'ok', CAST(ttx.create_datetime AS DATE), NULL)) AS last_payment_date
				FROM payment_account AS tpa
				JOIN payment_transaction AS ttx ON ttx.account_id = tpa.id
				WHERE tpa.owner_type = '$owner[type]' AND tpa.owner_id = '$owner[id]' AND tpa.customer_type = 'CU'
				GROUP BY tpa.id
			) AS tx ON pa.id = tx.id

			LEFT JOIN (
				SELECT
					ccc.customer_id,
					MAX(IF(ccc.stripe_customer IS NULL OR ccc.stripe_customer = '' OR ccc.last4 IS NULL OR ccc.last4 = '', 0, 1)) AS cc_ok
				FROM payment_stripe_card AS ccc
				JOIN payment_gateway AS ccpg ON ccpg.id = ccc.payment_gateway_id AND ccpg.owner_type = '$owner[type]' AND ccpg.owner_id = '$owner[id]'
				WHERE ccc.customer_type = 'CU'
				GROUP BY ccc.customer_id
			) AS cc ON cc.customer_id = c.id

			LEFT JOIN (
				SELECT
					ddm.customer_id,
					MAX(IF(ddm.status = 'authorised', 1, 0)) AS dd_ok
				FROM payment_gocardless_mandate AS ddm
				JOIN payment_gateway AS ddpg ON ddpg.id = ddm.payment_gateway_id AND ddpg.owner_type = '$owner[type]' AND ddpg.owner_id = '$owner[id]'
				WHERE ddm.customer_type = 'CU'
				GROUP BY ddm.customer_id
			) AS dd ON dd.customer_id = c.id

			LEFT JOIN (
				SELECT
					cn_c.customer_id,
					GROUP_CONCAT(DISTINCT CONCAT(cn_b.description, ' / ', cn_a.description) ORDER BY cn_b.description, cn_f.display_order, cn_a.display_order SEPARATOR '\\n') AS active_contract_area,
					GROUP_CONCAT(DISTINCT cn_c.description ORDER BY cn_b.description, cn_f.display_order, cn_a.display_order SEPARATOR '\\n') AS active_contract_description,
					COUNT(cn_c.id) AS active_contract_count,
					MIN(cn_b.id * 1000 * 1000 + cn_f.display_order * 1000 + cn_a.display_order) AS display_order
				FROM contract AS cn_c
				LEFT JOIN area AS cn_a ON cn_a.id = cn_c.area_id
				LEFT JOIN floor AS cn_f ON cn_f.id = cn_a.floor_id
				LEFT JOIN building AS cn_b ON cn_b.id = cn_f.building_id
				WHERE cn_c.owner_type = '$owner[type]' AND cn_c.owner_id = '$owner[id]' AND cn_c.customer_type = 'CU' AND cn_c.status IN ('active', 'ending')
				GROUP BY cn_c.customer_id
			) AS acs ON acs.customer_id = c.id

			LEFT JOIN (
				SELECT
					inv_i.customer_id,
					COUNT(*) AS oustanding_invoice_count,
					MAX(bill_date) AS last_outstanding_invoice_date,
					MIN(bill_date) AS first_outstanding_invoice_date
				FROM invoice AS inv_i
				WHERE inv_i.owner_type = '$owner[type]' AND inv_i.owner_id = '$owner[id]' AND inv_i.customer_type = 'CU' AND inv_i.status = 'outstanding'
				GROUP BY inv_i.customer_id
			) AS inv ON inv.customer_id = c.id

			WHERE c.owner_type = '$owner[type]' AND c.owner_id = '$owner[id]' AND c.archived = '0' AND tx.balance < 0
			$active_contracts_filter
			ORDER BY acs.display_order, c.contact_name, c.company_name;
		");

		return $this->success([
			'list' => $list ?: [],
			'columns' => UIElement::by_name('billing_customers_in_arrears')->get_columns(),
			'csv_url' => APP_URL."/ajax/get/customers_in_arrears_csv?owner_type=$owner[type]&owner_id=$owner[id]"
		]);
	}

	public function get_customer() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0);
		$customer = new Customer($id);
		if(!$customer->validate()) return $this->error('Customer not found.');

		if($customer->record['owner_type'] != $owner['type'] || $customer->record['owner_id'] != $owner['id']) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Customers', 'route' => "/billing/$owner[key]/customer" ],
			[ 'description' => $customer->get_name(), 'route' => "/billing/$owner[key]/customer/$customer->id" ],
		];

		$account_info = $this->get_account_info($customer->record['owner_type'], $customer->record['owner_id'], 'CU', $customer->id);

		$active_mandate = false;
		if($account_info) {
			foreach($account_info['mandates'] as $m) {
				if($m['status'] === 'authorised') $active_mandate = true;
			}
		}

		$active_contracts = App::sql()->query(
			"SELECT id
			FROM contract
			WHERE
				customer_type = 'CU' AND customer_id = '$customer->id'
				AND status IN ('unconfirmed', 'not_signed', 'pending', 'active', 'ending');
		") ?: [];

		$archive_warnings = [];
		if(count($active_contracts) > 0) $archive_warnings[] = 'Customer has an active contract.';
		if($account_info) {
			if($account_info['balance'] != 0) $archive_warnings[] = 'Customer has non-zero balance.';
			if(count($account_info['cards']) > 0) $archive_warnings[] = 'Customer has a credit card on file.';
		}
		if($active_mandate) $archive_warnings[] = 'Customer has an active Direct Debit mandate.';

		return $this->success([
			'details' => $customer->record,
			'archive_warnings' => $archive_warnings,
			'info' => $customer->get_info(),
			'account' => $account_info,
			'breadcrumbs' => $crumbs
		]);
	}

	public function new_customer() {
		$owner = $this->resolve_owner();

		$crumbs = [
			[ 'description' => 'Customers', 'route' => "/billing/$owner[key]/customer" ],
			[ 'description' => 'New Customer', 'route' => '' ],
		];

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_type' => $owner['type'],
				'owner_id' => $owner['id']
			],
			'archive_warnings' => [
				'Creating a new customer as archived.'
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function save_customer() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		if(!$id) return $this->access_denied();

		$owner_type = isset($data['owner_type']) ? $data['owner_type'] : '';
		$owner_id = isset($data['owner_id']) ? $data['owner_id'] : '';
		$owner = $this->resolve_owner("$owner_type-$owner_id");

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'contact_name', 'company_name', 'reference_no', 'email_address', 'phone_number', 'mobile_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'invoice_address_line_1', 'invoice_address_line_2', 'invoice_address_line_3', 'invoice_posttown', 'invoice_postcode',
			'notes', 'archived'
		]);
		$record = App::ensure($record, ['contact_name', 'company_name'], '');
		$record = App::ensure($record, ['archived'], 0);

		$record['email_address'] = trim(strtolower($record['email_address']));

		// Data validation
		$record['archived'] = $record['archived'] ? 1 : 0;
		if($record['contact_name'] === '' && $record['company_name'] === '') {
			return $this->error('Please enter contact name or company name.');
		}

		// Insert/update record
		$is_new = false;
		if($id === 'new') {
			$is_new = true;
			$record['owner_type'] = $owner['type'];
			$record['owner_id'] = $owner['id'];
		}
		$id = App::upsert('customer', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Always create payment account for customers (that's the only reason you'd add them anyway)
		if($is_new) $pa = new PaymentAccount($owner['type'], $owner['id'], 'CU', $id);

		return $this->success($id);
	}

	public function list_invoice_entities() {
		$owner = $this->resolve_owner();

		$data = App::json();

		$archived = isset($data['archived']) ? ($data['archived'] ? 1 : 0) : 0;
		$active_contracts = isset($data['active_contracts']) ? ($data['active_contracts'] ? 1 : 0) : 0;

		$active_contracts_filter = '';
		if($active_contracts) $active_contracts_filter = 'AND acs.cnt > 0';

		$list = App::sql()->query(
			"SELECT
				ie.*,
				COALESCE(acs.cnt, 0) AS active_contract_count
			FROM invoice_entity AS ie

			LEFT JOIN (
				SELECT
					ci.invoice_entity_id, COUNT(DISTINCT c.id) AS cnt
				FROM contract AS c
				JOIN contract_invoice AS ci ON c.id = ci.contract_id
				WHERE c.owner_type = '$owner[type]' AND c.owner_id = '$owner[id]' AND c.status IN ('unconfirmed', 'not_signed', 'pending', 'active', 'ending')
				GROUP BY ci.invoice_entity_id
			) AS acs ON acs.invoice_entity_id = ie.id

			WHERE ie.owner_type = '$owner[type]' AND ie.owner_id = '$owner[id]' AND ie.archived = '$archived'
			$active_contracts_filter
			ORDER BY ie.name;
		", MySQL::QUERY_ASSOC) ?: [];

		return $this->success(
			array_map(function($item) {
				$image_id = $item['image_id'];
				$image_url = '';
				if($image_id) {
					$uc = new UserContent($image_id);
					if($uc->info) $image_url = $uc->get_url();
				}
				$item['image_url'] = $image_url;
				return $item;
			}, $list)
		);
	}

	public function get_invoice_entity() {
		$owner = $this->resolve_owner();

		$id = App::get('id', 0, true);
		$entity = App::select('invoice_entity', $id);
		if(!$entity) return $this->error('Invoicing entity not found.');

		if($entity['owner_type'] != $owner['type'] || $entity['owner_id'] != $owner['id']) return $this->access_denied();

		$crumbs = [
			[ 'description' => 'Invoicing Entities', 'route' => "/billing/$owner[key]/invoice-entity" ],
			[ 'description' => $entity['name'], 'route' => "/billing/$owner[key]/invoice-entity/$entity[id]" ],
		];

		$active_contracts = App::sql()->query(
			"SELECT c.id
			FROM contract AS c
			JOIN contract_invoice AS ci ON ci.contract_id = c.id
			WHERE
				c.owner_type = '$owner[type]' AND c.owner_id = '$owner[id]'
				AND ci.invoice_entity_id = '$id'
				AND c.status IN ('unconfirmed', 'not_signed', 'pending', 'active', 'ending');
		") ?: [];

		$archive_warnings = [];
		if(count($active_contracts) > 0) $archive_warnings[] = 'Invoicing entity has an active contract.';

		$image_url = '';
		if($entity['image_id']) {
			$uc = new UserContent($entity['image_id']);
			if($uc->info) $image_url = $uc->get_url();
		}

		return $this->success([
			'details' => $entity,
			'image_url' => $image_url,
			'archive_warnings' => $archive_warnings,
			'breadcrumbs' => $crumbs
		]);
	}

	public function new_invoice_entity() {
		$owner = $this->resolve_owner();

		$crumbs = [
			[ 'description' => 'Invoicing Entities', 'route' => "/billing/$owner[key]/invoice-entity" ],
			[ 'description' => 'New Invoicing Entity', 'route' => '' ],
		];

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_type' => $owner['type'],
				'owner_id' => $owner['id']
			],
			'image_url' => '',
			'archive_warnings' => [
				'Creating a new invoicing entity as archived.'
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function save_invoice_entity() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		if(!$id) return $this->access_denied();

		$owner_type = isset($data['owner_type']) ? $data['owner_type'] : '';
		$owner_id = isset($data['owner_id']) ? $data['owner_id'] : '';
		$owner = $this->resolve_owner("$owner_type-$owner_id");

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'name', 'image_id', 'vat_reg_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'bank_name', 'bank_sort_code', 'bank_account_number',
			'archived'
		]);
		$record = App::ensure($record, ['name'], '');
		$record = App::ensure($record, ['archived'], 0);

		// Data validation
		$record['archived'] = $record['archived'] ? 1 : 0;
		$record['bank_sort_code'] = str_replace('-','',$record['bank_sort_code']);
		if($record['name'] === '') {
			return $this->error('Please enter invoicing entity name.');
		}

		// Insert/update record
		$is_new = false;
		if($id === 'new') {
			$is_new = true;
			$record['owner_type'] = $owner['type'];
			$record['owner_id'] = $owner['id'];
		}
		$id = App::upsert('invoice_entity', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function new_transaction() {
		$data = App::json();

		// Check permissions
		$account_id = isset($data['account_id']) ? $data['account_id'] : null;
		$account = App::select('payment_account', $account_id);

		if(!$account) return $this->error('Account not found.');

		$owner = $this->resolve_owner("$account[owner_type]-$account[owner_id]");

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

		$owner = $this->resolve_owner("$account[owner_type]-$account[owner_id]");

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
		$owner = $this->resolve_owner();

		$data = App::json();
		$data = App::keep($data, ['customer', 'si', 'client']);
		$data = App::ensure($data, ['customer', 'si', 'client']);
		$data = App::escape($data);

		$extra_condition = '';
		if($data['customer']) $extra_condition = "AND c.customer_type = 'CU' AND c.customer_id = '$data[customer]'";
		if($data['si']) $extra_condition = "AND c.customer_type = 'SI' AND c.customer_id = '$data[si]'";
		if($data['client']) $extra_condition = "AND c.customer_type = 'C' AND c.customer_id = '$data[client]'";

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

			WHERE c.owner_type = '$owner[type]' AND c.owner_id = '$owner[id]' AND c.is_template = 0
			$extra_condition
			ORDER BY start_date;
		") ?: [];

		return $this->success($list);
	}

	public function list_contract_templates() {
		$owner = $this->resolve_owner();

		$list = App::sql()->query(
			"SELECT *
			FROM contract
			WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND is_template = 1
			ORDER BY start_date;
		", MySQL::QUERY_ASSOC);
		$list = isp_instance_list('Contract', $list);

		return $this->success(isp_info($list));
	}

	public function get_contract() {
		$id = App::get('id', 0);
		if(!$id) return $this->access_denied();

		$contract = new Contract($id);
		if(!$contract->validate()) return $this->error('Contract not found.');

		$owner = $this->resolve_owner($contract->record['owner_type'].'-'.$contract->record['owner_id']);

		$card_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND type = 'stripe' ORDER BY description;", MySQL::QUERY_ASSOC);
		$dd_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND type = 'gocardless' ORDER BY description;", MySQL::QUERY_ASSOC);

		$customer_type = $contract->record['customer_type'];
		$customer_id = $contract->record['customer_id'];
		$customer = Customer::resolve_details($customer_type, $customer_id) ?: Customer::resolve_details();

		$crumbs = [];
		$crumbs[] = [ 'description' => 'Contracts', 'route' => "/billing/$owner[key]/contract" ];
		switch($customer_type) {
			case 'SI':
				$crumbs[] = [ 'description' => $customer['name'], 'route' => "/billing/$owner[key]/system-integrator/$customer_id" ];
				break;

			case 'C':
				$crumbs[] = [ 'description' => $customer['name'], 'route' => "/billing/$owner[key]/client/$customer_id" ];
				break;

			case 'CU':
				$crumbs[] = [ 'description' => $customer['name'], 'route' => "/billing/$owner[key]/customer/$customer_id" ];
				break;
		}
		$crumbs[] = [ 'description' => $contract->record['description'].($contract->record['is_template'] ? ' (Template)' : ''), 'route' => '' ];

		$buildings = [];
		if($contract->record['area_id']) {
			$area_id = $contract->record['area_id'];
			$b = App::sql()->query_row("SELECT f.building_id FROM area AS a JOIN floor AS f ON f.id = a.floor_id WHERE a.id = '$area_id';");
			if($b) {
				$condition = [];
				$condition[] = $this->owner_condition($owner);
				$condition[] = "b.id = '$b->building_id'";
				$condition[] = 'b.is_tenanted = 1';
				$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

				$list = App::sql()->query(
					"SELECT
						b.id, b.description, b.address, b.postcode, b.posttown
					FROM building AS b
					JOIN client AS c ON c.id = b.client_id
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					$condition
					ORDER BY b.description DESC;
				", MySQL::QUERY_ASSOC) ?: [];

				foreach($list as $building) {
					$building['areas'] = App::sql()->query(
						"SELECT
							a.id, a.description,
							a.address_line_1, a.address_line_2, a.address_line_3, a.posttown, a.postcode,
							f.description AS floor_description
						FROM area AS a
						JOIN floor AS f ON f.id = a.floor_id
						WHERE f.building_id = '$building[id]' AND (a.is_tenanted = 1 OR a.is_owner_occupied = 1)
						ORDER BY f.display_order, a.display_order, a.description;
					") ?: [];

					$isp_building = new ISPBuilding($building['id']);
					$building['packages'] = $isp_building->validate() ? (isp_info($isp_building->list_packages(), ['expand']) ?: []) : [];

					$buildings[] = $building;
				}
			}
		}

		$ie_list = [0];
		foreach($contract->invoices as $invoice) {
			$ie_id = $invoice->record['invoice_entity_id'];
			if($ie_id && !in_array($ie_id, $ie_list)) $ie_list[] = $ie_id;
		}
		$ie_list = implode(',', $ie_list);
		$invoice_entities = App::sql()->query(
			"SELECT id, name FROM invoice_entity
			WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND (archived = 0 OR id IN ($ie_list))
			ORDER BY name;
		");

		return $this->success([
			'details' => $contract->get_info(['expand']),
			'list' => [
				'buildings' => $buildings,
				'card_gateways' => $card_gateways ?: [],
				'dd_gateways' => $dd_gateways ?: [],
				'invoice_entities' => $invoice_entities ?: []
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function new_contract() {
		$owner = $this->resolve_owner();

		$customer_type = App::get('customer_type', '');
		$customer_id = App::get('customer_id', 0);
		$template_id = App::get('template', 0);

		$template = null;
		if($template_id) {
			$template = new Contract($template_id);
			if(!$template->validate() || $template->record['owner_type'] != $owner['type'] && $template->record['owner_id'] != $owner['id']) $template = null;
		}

		$reference_no = '';
		$set_customer_address = false;
		$set_customer_invoice_address = false;

		if($customer_type && $customer_id) {
			$is_template = false;
			$crumbs = [];

			switch($customer_type) {
				case 'SI':
					$condition = [];
					$condition[] = $this->owner_condition($owner);
					$condition[] = "si.id = '$customer_id'";
					$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

					$record = App::sql()->query_row(
						"SELECT
							si.id, si.company_name
						FROM system_integrator AS si
						JOIN service_provider AS sp ON sp.id = si.service_provider_id
						$condition
						LIMIT 1;
					", MySQL::QUERY_ASSOC);
					if(!$record) return $this->access_denied();

					$crumbs = [
						[ 'description' => 'System Integrators', 'route' => "/billing/$owner[key]/system-integrator" ],
						[ 'description' => $record['company_name'], 'route' => "/billing/$owner[key]/system-integrator/$customer_id" ],
						[ 'description' => 'New Contract', 'route' => '' ],
					];
					break;

				case 'C':
					$condition = [];
					$condition[] = $this->owner_condition($owner);
					$condition[] = "c.id = '$customer_id'";
					$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

					$record = App::sql()->query_row(
						"SELECT
							c.id, c.name,
							si.company_name AS system_integrator_name, si.id AS system_integrator_id
						FROM client AS c
						LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
						JOIN system_integrator AS si ON si.id = c.system_integrator_id
						JOIN service_provider AS sp ON sp.id = si.service_provider_id
						$condition
						LIMIT 1;
					", MySQL::QUERY_ASSOC);
					if(!$record) return $this->access_denied();

					if($owner['type'] === 'SP') {
						$crumbs[] = [ 'description' => 'System Integrators', 'route' => "/billing/$owner[key]/system-integrator" ];
						$crumbs[] = [ 'description' => $record['system_integrator_name'], 'route' => "/billing/$owner[key]/system-integrator/$record[system_integrator_id]" ];
					}
					$crumbs[] = [ 'description' => 'Clients', 'route' => "/billing/$owner[key]/client" ];
					$crumbs[] = [ 'description' => $record['name'], 'route' => "/billing/$owner[key]/client/$customer_id" ];
					$crumbs[] = [ 'description' => 'New Contract', 'route' => '' ];
					break;

				case 'CU':
					$record = App::select('customer', $customer_id);
					if(!$record) return $this->error('Customer not found.');
					if($record['owner_type'] != $owner['type'] || $record['owner_id'] != $owner['id']) return $this->access_denied();

					$reference_no = $record['reference_no'] ?: '';
					$customer_name = ($record['contact_name'] && $record['company_name'] ? "$record[contact_name], $record[company_name]" : ($record['contact_name'] ?: $record['company_name'])) ?: '';

					$crumbs = [
						[ 'description' => 'Customers', 'route' => "/billing/$owner[key]/customer" ],
						[ 'description' => $customer_name, 'route' => "/billing/$owner[key]/customer/$customer_id" ],
						[ 'description' => 'New Contract', 'route' => '' ],
					];

					if(!$record['address_line_1'] && !$record['address_line_2'] && !$record['address_line_3'] && !$record['posttown'] && !$record['postcode']) $set_customer_address = true;
					if(!$record['invoice_address_line_1'] && !$record['invoice_address_line_2'] && !$record['invoice_address_line_3'] && !$record['invoice_posttown'] && !$record['invoice_postcode']) $set_customer_invoice_address = true;
					break;

				default:
					return $this->error('Invalid customer type.');
			}
		} else {
			$is_template = true;
			$crumbs = [
				[ 'description' => 'Contracts', 'route' => "/billing/$owner[key]/contract" ],
				[ 'description' => 'New Contract Template', 'route' => '' ],
			];
		}

		$start_date = date('Y-m-d');
		$end_date = date('Y-m-d', strtotime('+6 months', strtotime($start_date)));
		$end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));

		$card_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND type = 'stripe' ORDER BY description;", MySQL::QUERY_ASSOC);
		$dd_gateways = App::sql()->query("SELECT id, description FROM payment_gateway WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND type = 'gocardless' ORDER BY description;", MySQL::QUERY_ASSOC);

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
				$details['customer_type'] = $customer_type;
				$details['customer_id'] = $customer_id;
				$details['is_template'] = 0;

				$details['reference_no'] = ($details['reference_no'] ?: '').($reference_no);

				$start_date = date('Y-m-d');
				$end_date = null;
				if($details['term'] && $details['term_units']) {
					$end_date = date('Y-m-d', strtotime("+$details[term] $details[term_units]", strtotime($start_date)));
					$end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
				}

				$details['start_date'] = $start_date;
				$details['end_date'] = $end_date;

				if($customer_type !== 'SI' && $customer_type !== 'HG' && $customer_type !== 'C') $details['provides_access'] = 0;

				if($details['pdf_contract_id']) {
					if(in_array($details['status'], ['unconfirmed', 'pending'])) $details['status'] = 'not_signed';
				}
			}
		} else {
			$details = [
				'id' => 'new',
				'owner_type' => $owner['type'],
				'owner_id' => $owner['id'],
				'customer_type' => $is_template ? null : $customer_type,
				'customer_id' => $is_template ? null : $customer_id,
				'building_id' => null,
				'area_id' => null,
				'reference_no' => $reference_no,
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
				'invoices' => []
			];
		}

		$details['set_customer_address'] = $set_customer_address;
		$details['set_customer_invoice_address'] = $set_customer_invoice_address;

		// Building list

		$buildings = [];

		if(!$is_template && $customer_type === 'CU') {
			$condition = [];
			$condition[] = $this->owner_condition($owner);
			$condition[] = 'b.is_tenanted = 1';
			$condition = $condition ? 'WHERE '.implode(' AND ', $condition) : '';

			$list = App::sql()->query(
				"SELECT
					b.id, b.description, b.address, b.postcode, b.posttown
				FROM building AS b
				JOIN client AS c ON c.id = b.client_id
				LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
				JOIN system_integrator AS si ON si.id = c.system_integrator_id
				JOIN service_provider AS sp ON sp.id = si.service_provider_id
				$condition
				ORDER BY b.description DESC;
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($list as $building) {
				$building['areas'] = App::sql()->query(
					"SELECT
						a.id, a.description,
						a.address_line_1, a.address_line_2, a.address_line_3, a.posttown, a.postcode,
						f.description AS floor_description
					FROM area AS a
					JOIN floor AS f ON f.id = a.floor_id
					WHERE f.building_id = '$building[id]' AND (a.is_tenanted = 1 OR a.is_owner_occupied = 1)
					ORDER BY f.display_order, a.display_order, a.description;
				") ?: [];

				$isp_building = new ISPBuilding($building['id']);
				$building['packages'] = $isp_building->validate() ? (isp_info($isp_building->list_packages(), ['expand']) ?: []) : [];

				$buildings[] = $building;
			}
		}

		$invoice_entities = App::sql()->query(
			"SELECT id, name FROM invoice_entity
			WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]' AND archived = 0
			ORDER BY name;
		");

		return $this->success([
			'details' => $details,
			'list' => [
				'buildings' => $buildings,
				'card_gateways' => $card_gateways ?: [],
				'dd_gateways' => $dd_gateways ?: [],
				'invoice_entities' => $invoice_entities ?: []
			],
			'breadcrumbs' => $crumbs
		]);
	}

	public function save_contract() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		$owner_type = isset($data['owner_type']) ? $data['owner_type'] : null;
		$owner_id = isset($data['owner_id']) ? $data['owner_id'] : null;
		$owner = $this->resolve_owner("$owner_type-$owner_id");
		if(!$id) return $this->access_denied();

		// Create records
		$record = $data;
		$record = App::keep($record, [
			'owner_type', 'owner_id', 'customer_type', 'customer_id', 'area_id',
			'reference_no', 'description', 'status',
			'start_date', 'end_date',
			'term', 'term_units', 'contract_term', 'is_template', 'skip_past_invoices', 'provides_access', 'instant_activation_email',
			'invoices', 'invoices_deleted',
			'set_customer_address', 'set_customer_invoice_address'
		]);
		$record = App::ensure($record, ['owner_type', 'owner_id', 'customer_type', 'customer_id', 'area_id', 'start_date', 'end_date', 'term_units', 'contract_term'], null);
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
				'id', 'invoice_entity_id', 'description', 'frequency', 'card_payment_gateway', 'dd_payment_gateway',
				'cutoff_day', 'issue_day', 'payment_day',
				'initial_card_payment', 'retry_dd_times', 'charge_card_if_dd_fails', 'charge_card_after_days', 'auto_charge_saved_card', 'manual_authorisation', 'mandatory_dd',
				'vat_rate', 'lines', 'lines_deleted'
			]);
			$invoice = App::ensure($invoice, ['invoice_entity_id', 'frequency', 'card_payment_gateway', 'dd_payment_gateway'], null);
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

					case 'isp_routers':
						if($line['description'] === '' || !$line['unit_price']) return $this->error('Active router lines must have description and unit price set.');
						break;

					case 'utility_e':
					case 'utility_g':
					case 'utility_w':
					case 'utility_h':
						if($line['description'] === '' || !$line['unit_price']) return $this->error('Utility invoice lines must have description and unit price set.');
						break;
				}
			}
		}

		// Insert/update record
		$contract_ended = false;
		$is_new = $id === 'new';
		if($is_new) {
			$record['owner_type'] = $owner['type'];
			$record['owner_id'] = $owner['id'];
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
		$owner = $this->resolve_owner();

		$data = App::json();
		$data = App::keep($data, ['date_from', 'date_to', 'status', 'customer', 'si', 'client']);
		$data = App::ensure($data, ['date_from', 'date_to', 'status', 'customer', 'si', 'client']);
		$data = App::escape($data);

		$counter = true;

		$filters = [];
		if($data['date_from']) $filters[] = "bill_date >= '$data[date_from]'";
		if($data['date_to']) $filters[] = "bill_date <= '$data[date_to]'";
		if(is_array($data['status'])) $filters[] = "status IN ('".implode("','", $data['status'])."')";

		if($data['customer']) {
			$counter = false;
			$filters[] = "customer_type = 'CU' AND customer_id = '$data[customer]'";
		}
		if($data['si']) {
			$counter = false;
			$filters[] = "customer_type = 'SI' AND customer_id = '$data[si]'";
		}
		if($data['client']) {
			$counter = false;
			$filters[] = "customer_type = 'C' AND customer_id = '$data[client]'";
		}

		$filters = 'AND '.implode(' AND ', $filters);

		$list = App::sql()->query(
			"SELECT * FROM invoice
			WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]'
			$filters
			ORDER BY bill_date DESC, id DESC;
		");

		if($counter) {
			$counter = App::sql()->query_row(
				"SELECT * FROM invoice_counter
				WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]'
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
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$owner = $this->resolve_owner("$owner_type-$owner_id");

		$counter = App::sql()->query_row(
			"SELECT * FROM invoice_counter
			WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]'
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
		$owner = $this->resolve_owner("$owner_type-$owner_id");

		App::sql()->update("UPDATE invoice_counter SET last_no = '$last_no' WHERE owner_type = '$owner[type]' AND owner_id = '$owner[id]';");

		return $this->success();
	}

	public function get_invoice() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$invoice = App::select('invoice', $id);
		if(!$invoice) return $this->error('Invoice not found.');

		$owner = $this->resolve_owner("$invoice[owner_type]-$invoice[owner_id]");

		$lines = App::sql()->query("SELECT * FROM invoice_line WHERE invoice_id = '$id';");

		$customer = Customer::resolve_details($invoice['customer_type'], $invoice['customer_id']) ?: Customer::resolve_details();
		$owner_details = Customer::resolve_details($owner['type'], $owner['id']) ?: Customer::resolve_details();

		$logo_url = '';
		if($owner_details['logo_on_light_id']) {
			$uc = new UserContent($owner_details['logo_on_light_id']);
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
		$crumbs[] = [ 'description' => 'Invoices', 'route' => "/billing/$owner[key]/invoice" ];
		switch($customer['customer_type']) {
			case 'SI':
				$crumbs[] = [ 'description' => $customer['name'], 'route' => "/billing/$owner[key]/system-integrator/$customer[customer_id]/invoices" ];
				break;

			case 'C':
				$crumbs[] = [ 'description' => $customer['name'], 'route' => "/billing/$owner[key]/client/$customer[customer_id]/invoices" ];
				break;

			case 'CU':
				$crumbs[] = [ 'description' => $customer['name'], 'route' => "/billing/$owner[key]/customer/$customer[customer_id]/invoices" ];
				break;
		}
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

		$owner_type = $invoice->record['owner_type'];
		$owner_id = $invoice->record['owner_id'];
		$owner = $this->resolve_owner("$owner_type-$owner_id");

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

		$owner_type = $invoice->record['owner_type'];
		$owner_id = $invoice->record['owner_id'];
		$owner = $this->resolve_owner("$owner_type-$owner_id");

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

		$owner_type = $invoice->record['owner_type'];
		$owner_id = $invoice->record['owner_id'];
		$owner = $this->resolve_owner("$owner_type-$owner_id");

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

		$owner_type = $invoice->record['owner_type'];
		$owner_id = $invoice->record['owner_id'];
		$owner = $this->resolve_owner("$owner_type-$owner_id");

		$invoice->resend_email();

		return $this->success();
	}

	public function delete_card() {
		$payment_gateway_id = App::get('payment_gateway_id', 0, true);
		$customer_type = App::get('customer_type', '', true);
		$customer_id = App::get('customer_id', 0, true);

		if(!$payment_gateway_id || !$customer_type || !$customer_id) return $this->error('Invalid parameters.');

		$pg = new PaymentGateway($payment_gateway_id);
		if(!$pg->is_valid() || !$pg->record['stripe_user_id']) return $this->access_denied();

		$owner_type = $pg->record['owner_type'];
		$owner_id = $pg->record['owner_id'];
		$owner = $this->resolve_owner("$owner_type-$owner_id");

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
		if(!$pg->is_valid()) return $this->access_denied();

		$owner_type = $pg->record['owner_type'];
		$owner_id = $pg->record['owner_id'];
		$owner = $this->resolve_owner("$owner_type-$owner_id");

		$mandate = new PaymentGoCardlessMandate($payment_gateway_id, $customer_type, $customer_id);
		if(!$mandate->is_valid()) return $this->error('Mandate not found.');

		if(!$mandate->cancel()) return $this->error('Unable to cancel mandate.');

		return $this->success();
	}

	public function send_customer_email() {
		$data = App::json();

		// TODO: Make it support all customer types (SP, SI, HG, C)

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
		$owner = $this->resolve_owner("$owner_type-$owner_id");

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

}
