<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_navigation() {
		$sales_enabled = !!Permission::find_system_integrators([ 'with' => Permission::SALES_ENABLED ]);
		if(!$sales_enabled) return $this->access_denied();

		$sales_full = !!Permission::find_system_integrators([ 'with' => Permission::SALES_ALL_RECORDS ]);

		$nav = [
			[ 'name' => 'Sales', 'header' => true ],
			[ 'name' => 'Overview', 'icon' => 'md md-home', 'route' => '/sales/overview' ],
			[ 'name' => $sales_full ? 'Customers' : 'My customers', 'icon' => 'md md-person', 'route' => '/sales/customer' ],
			[ 'name' => $sales_full ? 'Projects' : 'My projects', 'icon' => 'md md-location-city', 'route' => '/sales/project' ],
			[ 'name' => 'Configuration', 'header' => true ],
			[ 'name' => 'Modules and Systems', 'icon' => 'md md-now-widgets', 'route' => '/sales/project-system' ]
		];

		return $this->success([
			'menu' => $nav
		]);
	}

	public function get_overview() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		if($this->selected_product_owner_level === PermissionLevel::SERVICE_PROVIDER) {
			$si_filter = array_map(function($item) { return $item->id; }, App::sql()->query("SELECT id FROM system_integrator WHERE service_provider_id = '$this->selected_product_owner_id';") ?: []);
		} else {
			$si_filter = [$this->selected_product_owner_id];
		}

		$user = App::user();

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_ENABLED ]);
		$si_all = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_all = array_intersect($si_all, $si_filter);
		$si_all = implode(',', $si_all);

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_ALL_RECORDS ]);
		$si_full = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_full = array_intersect($si_full, $si_filter);
		$si_full = implode(',', $si_full);

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_PRICING ]);
		$si_pricing = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_pricing = array_intersect($si_pricing, $si_filter);

		if(!$si_all) return $this->access_denied();

		$condition = "(p.system_integrator_id IN ($si_all) AND (p.user_id = '$user->id' OR p.is_public = 1))";
		if($si_full) {
			// We have some SIs with full access
			$condition = "(p.system_integrator_id IN ($si_full) OR $condition)";
		}

		$projects = App::sql()->query(
			"SELECT
				p.id,
				p.project_no,
				u.name AS user_name,
				p.created,
				p.description,
				sc.name AS customer_name,
				p.posttown,
				p.postcode,
				p.stage,
				p.is_public,
				si.id AS owner_id,
				si.company_name AS owner_name,
				COALESCE(teq.total_price, 0) + COALESCE(tlab.total_price, 0) AS grand_total,
				p.contact_name, p.contact_position, p.contact_email, p.contact_mobile,
				sc.contact_name AS customer_contact_name,
				sc.contact_position AS customer_contact_position,
				sc.contact_email AS customer_contact_email,
				sc.contact_mobile AS customer_contact_mobile
			FROM project AS p
			JOIN system_integrator AS si ON si.id = p.system_integrator_id
			LEFT JOIN userdb AS u ON u.id = p.user_id
			LEFT JOIN sales_customer AS sc ON sc.id = p.customer_id

			LEFT JOIN (
				SELECT
					project_id,
					SUM(unit_price * quantity) AS total_price,
					SUM(unit_cost * quantity) AS total_cost
				FROM project_line
				GROUP BY project_id
			) AS teq ON p.id = teq.project_id

			LEFT JOIN (
				SELECT
					ln.project_id,
					SUM(ln.quantity * lab.labour_hours) AS total_hours,
					SUM(ln.quantity * lab.labour_hours * lab.hourly_price) AS total_price,
					SUM(ln.quantity * lab.labour_hours * lab.hourly_cost) AS total_cost
				FROM project_line AS ln
				JOIN project_labour AS lab ON lab.line_id = ln.id
				GROUP BY ln.project_id
			) AS tlab ON p.id = tlab.project_id

			WHERE
				p.stage <> 'cancelled'
				AND $condition
			ORDER BY created DESC, description
			LIMIT 10;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($projects as &$p) {
			if(!$p['grand_total']) $p['grand_total'] = 0;
			if(!in_array($p['owner_id'], $si_pricing)) {
				$p['grand_total'] = null;
			}
		}
		unset($p);

		return $this->success([
			'projects' => $projects,
			'pricing' => !!count($si_pricing),
			'si' => $this->selected_product_owner_level === PermissionLevel::SYSTEM_INTEGRATOR,
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function list_customers() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		if($this->selected_product_owner_level === PermissionLevel::SERVICE_PROVIDER) {
			$si_filter = array_map(function($item) { return $item->id; }, App::sql()->query("SELECT id FROM system_integrator WHERE service_provider_id = '$this->selected_product_owner_id';") ?: []);
		} else {
			$si_filter = [$this->selected_product_owner_id];
		}

		$user = App::user();

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_ENABLED ]);
		$si_all = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_all = array_intersect($si_all, $si_filter);
		$si_all = implode(',', $si_all);

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_ALL_RECORDS ]);
		$si_full = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_full = array_intersect($si_full, $si_filter);
		$si_full = implode(',', $si_full);

		if(!$si_all) return $this->access_denied();

		$condition = "(sc.system_integrator_id IN ($si_all) AND sc.user_id = '$user->id')";
		if($si_full) {
			// We have some SIs with full access
			$condition = "sc.system_integrator_id IN ($si_full) OR $condition";
		}

		$projects = App::sql()->query(
			"SELECT
				sc.id,
				sc.name,
				u.name AS user_name,
				c.name AS client_name,
				sc.posttown,
				sc.postcode,
				si.company_name AS owner_name
			FROM sales_customer AS sc
			JOIN system_integrator AS si ON si.id = sc.system_integrator_id
			LEFT JOIN userdb AS u ON u.id = sc.user_id
			LEFT JOIN client AS c ON c.id = sc.client_id
			WHERE $condition
			ORDER BY sc.name;
		", MySQL::QUERY_ASSOC);

		return $this->success([
			'list' => $projects ?: [],
			'si' => $this->selected_product_owner_level === PermissionLevel::SYSTEM_INTEGRATOR,
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function new_customer_lists() {
		$si_id = App::get('si', 0);
		if(!$si_id) return $this->access_denied();
		if(!Permission::get_system_integrator($si_id)->check(Permission::SALES_ENABLED)) return $this->access_denied();

		$result = [
			'list' => [
				'users' => $this->get_si_users_array($si_id)
			]
		];

		return $this->success($result);
	}

	public function new_customer() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$user = App::user();

		$si_list = Permission::list_system_integrators([ 'with' => Permission::SALES_ENABLED ]);
		if(!$si_list) return $this->access_denied();

		$si_id = $si_list[0]->id;
		if($this->selected_product_owner_level === PermissionLevel::SYSTEM_INTEGRATOR) $si_id = $this->selected_product_owner_id;

		$list_si = [];
		foreach($si_list as $si) {
			$list_si[] = [
				'id' => $si->id,
				'company_name' => $si->company_name
			];
		}

		$result = [
			'breadcrumbs' => [
				[ 'description' => 'Customers', 'route' => '/sales/customer' ],
				[ 'description' => 'New Customer' ]
			],
			'details' => [
				'id' => 'new',
				'system_integrator_id' => $si_id,
				'user_id' => $user->id
			],
			'list' => [
				'si' => $list_si,
				'users' => $this->get_si_users_array($si_id)
			]
		];

		return $this->success($result);
	}

	public function get_customer() {
		$user = App::user();
		$id = App::get('id', 0, true);

		$record = App::select('sales_customer', $id);
		if(!$record) return $this->access_denied();

		$perm = Permission::get_system_integrator($record['system_integrator_id']);
		if(!$perm->check(Permission::SALES_ENABLED)) return $this->access_denied();
		if($user->id != $record['user_id'] && !$perm->check(Permission::SALES_ALL_RECORDS)) return $this->access_denied();

		$si_list = Permission::list_system_integrators([ 'with' => Permission::SALES_ENABLED ]);
		if(!$si_list) return $this->access_denied();

		$list_si = [];
		foreach($si_list as $si) {
			$list_si[] = [
				'id' => $si->id,
				'company_name' => $si->company_name
			];
		}

		$result = [
			'breadcrumbs' => [
				[ 'description' => 'Customers', 'route' => '/sales/customer' ],
				[ 'description' => $record['name'] ]
			],
			'details' => $record,
			'list' => [
				'si' => $list_si,
				'users' => $this->get_si_users_array($record['system_integrator_id'])
			]
		];

		return $this->success($result);
	}

	public function save_customer() {
		$user = App::user();
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'system_integrator_id', 'user_id', 'client_id', 'name',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode', 'phone_number',
			'contact_name', 'contact_position', 'contact_email', 'contact_mobile',
			'notes'
		]);
		$record = App::ensure($record, ['name'], '');
		$record = App::ensure($record, ['system_integrator_id'], 0);

		if($id !== 'new') unset($record['system_integrator_id']); // Cannot be changed once record is created

		// Data validation
		if($record['name'] === '') return $this->error('Please enter customer name.');
		if($id === 'new' && !$record['system_integrator_id']) return $this->error('Please select system integrator.');

		// Check permissions
		if($id !== 'new') {
			// Check if user has access to the record
			$original = App::select('sales_customer', $id);
			if(!$original) return $this->access_denied();
			$perm = Permission::get_system_integrator($original['system_integrator_id']);
 		} else {
			// Check if user has access to selected SI
			$perm = Permission::get_system_integrator($record['system_integrator_id']);
		}
		if(!$perm->check(Permission::SALES_ENABLED)) return $this->access_denied();
		if($user->id != $record['user_id'] && !$perm->check(Permission::SALES_ALL_RECORDS)) return $this->access_denied();

		// Insert/update record
		$id = App::upsert('sales_customer', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function list_projects() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$data = App::json();
		$data = App::keep($data, ['stage', 'visibility']);
		$data = App::ensure($data, ['stage', 'visibility'], []);
		$data = App::escape($data);

		$data['stage'] = App::escape($data['stage']);
		$data['visibility'] = App::escape($data['visibility']);
		$data['visibility'][] = 2; // Prevent empty array with a value with no hits

		if($this->selected_product_owner_level === PermissionLevel::SERVICE_PROVIDER) {
			$si_filter = array_map(function($item) { return $item->id; }, App::sql()->query("SELECT id FROM system_integrator WHERE service_provider_id = '$this->selected_product_owner_id';") ?: []);
		} else {
			$si_filter = [$this->selected_product_owner_id];
		}

		$user = App::user();

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_ENABLED ]);
		$si_all = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_all = array_intersect($si_all, $si_filter);
		$si_all = implode(',', $si_all);

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_ALL_RECORDS ]);
		$si_full = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_full = array_intersect($si_full, $si_filter);
		$si_full = implode(',', $si_full);

		$si_list = Permission::find_system_integrators([ 'with' => Permission::SALES_PRICING ]);
		$si_pricing = array_map(function($item) { return $item->system_integrator_id; }, $si_list ?: []);
		$si_pricing = array_intersect($si_pricing, $si_filter);

		if(!$si_all) return $this->access_denied();

		$access_condition = "(p.system_integrator_id IN ($si_all) AND (p.user_id = '$user->id' OR p.is_public = 1))";
		if($si_full) {
			// We have some SIs with full access
			$access_condition = "(p.system_integrator_id IN ($si_full) OR $access_condition)";
		}

		$filters = [$access_condition];
		if(is_array($data['stage'])) $filters[] = "p.stage IN ('".implode("','", $data['stage'])."')";
		if(is_array($data['visibility'])) $filters[] = "p.is_public IN ('".implode("','", $data['visibility'])."')";
		$filters = implode(' AND ', $filters);

		$projects = App::sql()->query(
			"SELECT
				p.id,
				p.project_no,
				u.name AS user_name,
				p.created,
				p.description,
				sc.name AS customer_name,
				p.posttown,
				p.postcode,
				p.stage,
				p.is_public,
				si.id AS owner_id,
				si.company_name AS owner_name,
				COALESCE(teq.total_price, 0) + COALESCE(tlab.total_price, 0) AS grand_total,
				p.contact_name, p.contact_position, p.contact_email, p.contact_mobile,
				sc.contact_name AS customer_contact_name,
				sc.contact_position AS customer_contact_position,
				sc.contact_email AS customer_contact_email,
				sc.contact_mobile AS customer_contact_mobile
			FROM project AS p
			JOIN system_integrator AS si ON si.id = p.system_integrator_id
			LEFT JOIN userdb AS u ON u.id = p.user_id
			LEFT JOIN sales_customer AS sc ON sc.id = p.customer_id

			LEFT JOIN (
				SELECT
					project_id,
					SUM(unit_price * quantity) AS total_price,
					SUM(unit_cost * quantity) AS total_cost
				FROM project_line
				GROUP BY project_id
			) AS teq ON p.id = teq.project_id

			LEFT JOIN (
				SELECT
					ln.project_id,
					SUM(ln.quantity * lab.labour_hours) AS total_hours,
					SUM(ln.quantity * lab.labour_hours * lab.hourly_price) AS total_price,
					SUM(ln.quantity * lab.labour_hours * lab.hourly_cost) AS total_cost
				FROM project_line AS ln
				JOIN project_labour AS lab ON lab.line_id = ln.id
				GROUP BY ln.project_id
			) AS tlab ON p.id = tlab.project_id

			WHERE $filters
			ORDER BY created DESC, description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($projects as &$p) {
			if(!$p['grand_total']) $p['grand_total'] = 0;
			if(!in_array($p['owner_id'], $si_pricing)) {
				$p['grand_total'] = null;
			}
		}
		unset($p);

		return $this->success([
			'list' => $projects,
			'pricing' => !!count($si_pricing),
			'si' => $this->selected_product_owner_level === PermissionLevel::SYSTEM_INTEGRATOR,
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	private function get_si_users_array($si_id) {
		$user = App::user();
		$sql = App::sql();
		$si_id = $sql->escape($si_id);

		$result = [
			['id' => null, 'name' => 'Unassigned']
		];

		if(!Permission::get_system_integrator($si_id)->check(Permission::SALES_ALL_RECORDS)) {
			// Can only create records for itself
			$result[] = [ 'id' => $user->id, 'name' => $user->info->name ];
			return $result;
		}

		// Get SI info
		$r = $sql->query_row("SELECT service_provider_id FROM system_integrator WHERE id = '$si_id';");
		if(!$r) return [];
		$sp_id = $r->service_provider_id;

		// Get full user list
		$users = $sql->query(
			"SELECT DISTINCT u.id, u.name
			FROM userdb AS u
			JOIN user_role_assignment AS ura ON u.id = ura.user_id AND (
				ura.assigned_level = 'E' OR
				(ura.assigned_level = 'SP' AND ura.assigned_id = '$sp_id') OR
				(ura.assigned_level = 'SI' AND ura.assigned_id = '$si_id')
			)
			ORDER BY u.name;
		") ?: [];

		foreach($users as $r) {
			// Must check manually if user has sales access to SI
			$u = new User($r->id);
			$perm = new Permission($sql->query_row(Permission::select_merge_least_permissive([ 'level' => PermissionLevel::SYSTEM_INTEGRATOR, 'id' => $si_id, 'user' => $u ])));
			if($perm->check(Permission::SALES_ENABLED)) $result[] = $r;
		}

		return $result;
	}

	private function get_si_customers_array($si_id) {
		$user = App::user();
		$si_id = App::escape($si_id);

		if(Permission::get_system_integrator($si_id)->check(Permission::SALES_ALL_RECORDS)) {
			// Get all SI customers
			$result = App::sql()->query("SELECT id, name FROM sales_customer WHERE system_integrator_id = '$si_id' ORDER BY name;") ?: [];
		} else {
			// Get user's SI customers
			$result = App::sql()->query("SELECT id, name FROM sales_customer WHERE system_integrator_id = '$si_id' AND user_id = '$user->id' ORDER BY name;") ?: [];
		}

		array_unshift($result, [ 'id' => 'new', 'name' => 'New customer' ]);
		return $result;
	}

	private function get_si_list() {
		$si_list = Permission::list_system_integrators([ 'with' => Permission::SALES_ENABLED ]) ?: [];

		$result = [];
		foreach($si_list as $si) {
			$result[] = [
				'id' => $si->id,
				'company_name' => $si->company_name
			];
		}

		return $result;
	}

	public function new_project_lists() {
		$si_id = App::get('si', 0);
		if(!$si_id) return $this->access_denied();
		if(!Permission::get_system_integrator($si_id)->check(Permission::SALES_ENABLED)) return $this->access_denied();

		$result = [
			'list' => [
				'users' => $this->get_si_users_array($si_id),
				'customers' => $this->get_si_customers_array($si_id)
			]
		];

		return $this->success($result);
	}

	public function new_project() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$user = App::user();

		$si_list = $this->get_si_list();
		if(count($si_list) === 0) return $this->access_denied();

		$si_id = $si_list[0]['id'];
		if($this->selected_product_owner_level === PermissionLevel::SYSTEM_INTEGRATOR) $si_id = $this->selected_product_owner_id;

		$result = [
			'details' => [
				'id' => 'new',
				'system_integrator_id' => $si_id,
				'user_id' => $user->id,
				'customer_id' => 'new',
				'customer' => new stdClass(),
				'stage' => 'lead',
				'stage_notes' => 'Initial project stage',
				'price_tier' => 'retail',
				'subscription_price_tier' => 'retail',
				'exclude_labour' => 0,
				'exclude_subscriptions' => 0,
				'vat_rate' => 20,
				'quote_date' => null,
				'expiry_date' => null
			],
			'has_labour' => false,
			'has_subscriptions' => false,
			'list' => [
				'si' => $si_list,
				'users' => $this->get_si_users_array($si_id),
				'customers' => $this->get_si_customers_array($si_id)
			]
		];

		return $this->success($result);
	}

	public function get_project() {
		$user = App::user();

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$record = App::select('project', $id);
		if(!$record) return $this->access_denied();

		$si_id = $record['system_integrator_id'];
		$perm = Permission::get_system_integrator($si_id);
		if(!$perm->check(Permission::SALES_ENABLED)) return $this->access_denied();
		if($record['is_public'] !== 1 && $user->id != $record['user_id'] && !$perm->check(Permission::SALES_ALL_RECORDS)) return $this->access_denied();
		$pricing = $perm->check(Permission::SALES_PRICING);

		$record['customer'] = new stdClass();

		$has_labour = !!App::sql()->query("SELECT t.id FROM project_labour AS t JOIN project_line AS pl ON pl.id = t.line_id AND pl.project_id = '$id';");
		$has_subscriptions = !!App::sql()->query("SELECT t.id FROM project_subscription AS t JOIN project_line AS pl ON pl.id = t.line_id AND pl.project_id = '$id';");

		$result = [
			'details' => $record,
			'has_labour' => $has_labour,
			'has_subscriptions' => $has_subscriptions,
			'pricing' => $pricing,
			'list' => [
				'si' => $this->get_si_list(),
				'users' => $this->get_si_users_array($si_id),
				'customers' => $this->get_si_customers_array($si_id)
			]
		];

		return $this->success($result);
	}

	public function save_project() {
		$user = App::user();
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'system_integrator_id', 'customer_id', 'user_id', 'project_no', 'description', 'stage', 'price_tier', 'subscription_price_tier',
			'is_public', 'exclude_labour', 'exclude_subscriptions', 'vat_rate',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode', 'phone_number',
			'contact_name', 'contact_position', 'contact_email', 'contact_mobile', 'quote_date', 'expiry_date'
		]);
		$record = App::ensure($record, ['system_integrator_id', 'project_no', 'is_public', 'exclude_labour', 'exclude_subscriptions', 'vat_rate'], 0);
		$record = App::ensure($record, ['description', 'customer_id', 'price_tier', 'subscription_price_tier'], '');
		$record = App::ensure($record, [
			'user_id',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode', 'phone_number',
			'contact_name', 'contact_position', 'contact_email', 'contact_mobile', 'quote_date', 'expiry_date'
		], null);
		if($id !== 'new') {
			unset($record['system_integrator_id']);
		}

		// Purge note fields if they're empty strings
		$stage_notes = isset($data['stage_notes']) ? App::escape($data['stage_notes']) : null;
		$stage = isset($record['stage']) ? App::escape($record['stage']) : null;

		$original = null;
		if($id === 'new') {
			$si_id = $data['system_integrator_id'];

			// Set project number to max + 1
			$project_no = App::sql()->query_row("SELECT MAX(project_no) AS maxno FROM project WHERE system_integrator_id = '$si_id';", MySQL::QUERY_ASSOC);
			$project_no = $project_no ? $project_no['maxno'] + 1 : 1;
			$record['project_no'] = $project_no;
		} else {
			$original = App::select('project', $id);
			if(!$original) return $this->access_denied();
			$si_id = $original['system_integrator_id'];
			if($stage !== null && $stage === $original['stage']) $stage = null;
		}
		if(!Permission::get_system_integrator($si_id)->check(Permission::SALES_ENABLED)) return $this->access_denied();
		if($record['is_public'] !== 1 && $user->id != $record['user_id'] && !Permission::get_system_integrator($si_id)->check(Permission::SALES_ALL_RECORDS)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') return $this->error('Please enter project description.');
		if($id === 'new' && !$record['system_integrator_id']) return $this->error('Please select system integrator');
		if(!$record['customer_id']) return $this->error('Please select customer.');
		if(!$record['price_tier']) return $this->error('Please select a product price tier.');
		if(!$record['subscription_price_tier']) return $this->error('Please select a subscription price tier.');
		if(!is_numeric($record['project_no'])) return $this->error('Project no. must be a whole number.');

		// Customer
		if($record['customer_id'] === 'new') {
			$customer = isset($data['customer']) ? $data['customer'] : [];
			$customer = App::keep($customer, ['client_id', 'name']);
			$customer = App::ensure($customer, ['name'], '');

			// New customer will be assigned to the same SI/user
			$customer['system_integrator_id'] = $si_id;
			$customer['user_id'] = $record['user_id'];

			// Due to form simplification, copy address and contact info from project record
			$customer = array_merge($customer, [
				'address_line_1' => $record['address_line_1'],
				'address_line_2' => $record['address_line_2'],
				'address_line_3' => $record['address_line_3'],
				'posttown' => $record['posttown'],
				'postcode' => $record['postcode'],
				'phone_number' => $record['phone_number'],
				'contact_name' => $record['contact_name'],
				'contact_position' => $record['contact_position'],
				'contact_email' => $record['contact_email'],
				'contact_mobile' => $record['contact_mobile']
			]);

			if($customer['name'] === '') return $this->error('Please enter customer name.');

			$customer_id = App::insert('sales_customer', $customer);
			if(!$customer_id) return $this->error('Error saving data.');
			$record['customer_id'] = $customer_id;
		}

		// Check if exclusions can be changed and if labour/subscription purge is required
		$purge_labour = false;
		$purge_subscriptions = false;
		if($id !== 'new' && $original) {
			if($original['stage'] === 'lead' || $original['stage'] === 'survey' || $stage === 'lead' || $stage === 'survey') {
				if($record['exclude_labour']) $purge_labour = true;
				if($record['exclude_subscriptions']) $purge_subscriptions = true;
			} else {
				unset($record['exclude_labour']);
				unset($record['exclude_subscriptions']);
			}
		}

		// Insert/update record
		if($id === 'new') $record['created'] = App::now();
		$id = App::upsert('project', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Save stage history
		if($stage) {
			App::insert('project_stage_history', [
				'project_id' => $id,
				'user_id' => $user->id,
				'datetime' => App::now(),
				'stage' => $stage,
				'notes' => $stage_notes
			]);
		}

		// Purge labour/subscriptions if needed
		if($purge_labour) {
			App::sql()->delete("DELETE t FROM project_labour AS t JOIN project_line AS pl ON pl.id = t.line_id AND pl.project_id = '$id';");
		}

		if($purge_subscriptions) {
			App::sql()->delete("DELETE t FROM project_subscription AS t JOIN project_line AS pl ON pl.id = t.line_id AND pl.project_id = '$id';");
		}

		return $this->success($id);
	}

	public function get_project_systems() {
		$id = App::get('id', 0, true);

		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		$si_id = $project->info['system_integrator_id'];

		$modules = App::sql()->query(
			"SELECT DISTINCT pm.id, pm.description, pm.icon, pm.colour
			FROM project_module AS pm
			LEFT JOIN product_reseller AS pr ON pr.owner_level = pm.owner_level AND pr.owner_id = pm.owner_id AND pr.reseller_level = 'SI' AND pr.reseller_id = '$si_id'
			WHERE (pm.owner_level = 'SI' AND pm.owner_id = '$si_id') OR pr.owner_level IS NOT NULL
			ORDER BY display_order;
		");

		$systems = App::sql()->query(
			"SELECT
				s.id, s.description, s.module_id,
				IF(psa.project_id, 1, 0) AS in_project,
				COUNT(pl.id) AS product_count
			FROM project_system AS s
			LEFT JOIN project_system_assign AS psa ON psa.system_id = s.id AND psa.project_id = '$id'
			LEFT JOIN project_line AS pl ON pl.system_id = s.id AND pl.project_id = '$id' AND pl.parent_id IS NULL
			LEFT JOIN product_reseller AS pr ON pr.owner_level = s.owner_level AND pr.owner_id = s.owner_id AND pr.reseller_level = 'SI' AND pr.reseller_id = '$si_id'
			WHERE (s.owner_level = 'SI' AND s.owner_id = '$si_id') OR pr.owner_level IS NOT NULL OR psa.project_id IS NOT NULL
			GROUP BY s.id, s.description, IF(psa.project_id, 1, 0)
			ORDER BY s.description;
		", MySQL::QUERY_ASSOC);

		return $this->success([
			'modules' => $modules ?: [],
			'systems' => $systems ?: [],
			'system_integrator_id' => $si_id
		]);
	}

	public function update_project_systems() {
		$sql = App::sql();
		$data = App::json();

		$id = isset($data['id']) ? $data['id'] : '';
		$id = $sql->escape($id);
		$add = (isset($data['add']) ? $data['add'] : []) ?: [];
		$remove = (isset($data['remove']) ? $data['remove'] : []) ?: [];

		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		foreach($add as $system_id) {
			App::insert_ignore('project_system_assign', [
				'project_id' => $id,
				'system_id' => $system_id
			]);
		}

		foreach($remove as $system_id) {
			$system_id = $sql->escape($system_id);
			$lines = $sql->query("SELECT id FROM project_line WHERE project_id = '$id' AND system_id = '$system_id' AND parent_id IS NULL;", MySQL::QUERY_ASSOC) ?: [];
			foreach($lines as $line) {
				$line_id = $line['id'];

				// Delete all slots and extra labour/subscription records
				$sql->delete("DELETE FROM project_labour WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");
				$sql->delete("DELETE FROM project_subscription WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");
				$sql->delete("DELETE FROM project_line_slots WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");
				$sql->delete("DELETE FROM project_line_bundle_answers WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");

				// Delete line and all accessories
				$sql->delete("DELETE FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id';");
			}

			$sql->delete("DELETE FROM project_system_assign WHERE project_id = '$id' AND system_id = '$system_id';");
		}

		$result = [];
		$systems = $sql->query("SELECT system_id FROM project_system_assign WHERE project_id = '$id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($systems as $s) {
			$result[] = $s['system_id'];
		}

		return $this->success($result);
	}

	public function get_system() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$record = App::select('project_system', $id);
		if(!$record) return $this->access_denied();

		$owner_level = $record['owner_level'];
		$owner_id = $record['owner_id'];
		$seller_level = 'SI';
		$seller_id = App::get('si', 0, true);
		if(!Permission::get($seller_level, $seller_id)->check(Permission::SALES_ENABLED)) return $this->access_denied();

		$editable = Permission::get($owner_level, $owner_id)->check(Permission::SALES_ENABLED);

		// Get list of products
		$list = App::sql()->query(
			"SELECT
				p.id, p.sku, p.model, p.short_description,
				m.name AS manufacturer_name,
				IF(psp.system_id IS NOT NULL, 1, 0) AS in_system,
				psp.sort_order,
				uc.path AS image_url
			FROM product AS p
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
			LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id
			LEFT JOIN project_system_products AS psp ON psp.product_id = p.id AND psp.system_id = '$id'
			LEFT JOIN user_content AS uc ON uc.id = p.image_id
			ORDER BY p.sku;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($list as &$item) {
			if($item['image_url']) {
				$item['image_url'] = UserContent::url_by_path($item['image_url']);
			}
		}
		unset($item);

		return $this->success([
			'details' => $record,
			'editable' => $editable,
			'products' => $list
		]);
	}

	public function save_system() {
		if(!Permission::any()->check(Permission::SALES_ENABLED)) return $this->access_denied();

		$sql = App::sql();
		$data = App::json();
		$id = isset($data['id']) ? $data['id'] : '';

		if(!$id) return $this->access_denied();

		$record = $data;
		$record = App::keep($record, ['owner_level', 'owner_id', 'description', 'module_id']);
		$record = App::ensure($record, ['description'], '');
		$record = App::ensure($record, ['module_id'], null);

		if(!$record['description']) return $this->error('Please enter system description.');
		if(!$record['module_id']) return $this->error('Please select a module.');

		// Insert/update record
		if($id === 'new') {
			if(!Permission::get($record['owner_level'], $record['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();
			$owner_access = true;
		} else {
			$original = App::select('project_system', $id);
			$owner_access = Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::SALES_ENABLED);
		}

		if($owner_access) {
			$id = App::upsert('project_system', $id, $record);
			if(!$id) return $this->error('Error saving data.');
		}

		// Add/remove products
		$add = (isset($data['add']) ? $data['add'] : []) ?: [];
		$remove = (isset($data['remove']) ? $data['remove'] : []) ?: [];

		foreach($add as $product_id) {
			App::insert_ignore('project_system_products', [
				'system_id' => $id,
				'product_id' => $product_id
			]);
		}

		foreach($remove as $product_id) {
			$product_id = $sql->escape($product_id);
			$sql->delete("DELETE FROM project_system_products WHERE system_id = '$id' AND product_id = '$product_id';");
		}

		// Update product order
		$order = (isset($data['order']) ? $data['order'] : []) ?: [];
		foreach($order as $sort_order => $product_id) {
			if(is_numeric($sort_order)) {
				$sql->update("UPDATE project_system_products SET sort_order = '$sort_order' WHERE system_id = '$id' AND product_id = '$product_id';");
			}
		}

		return $this->success($id);
	}

	public function add_project_line() {
		$sql = App::sql();
		$data = App::json();

		$data = App::ensure($data, ['id', 'structure_id', 'system_id', 'product_id'], '');
		$data = App::ensure($data, ['quantity'], 1);
		$data = App::ensure($data, ['forced', 'toolbox'], 0);

		$id = $data['id'];
		$product_id = $data['product_id'];
		$structure_id = $data['structure_id'];
		$system_id = $data['system_id'];
		$quantity = $data['quantity'];
		$forced = $data['forced'];
		$toolbox = $data['toolbox'];

		$id = App::escape($id);
		$product_id = App::escape($product_id);
		$structure_id = App::escape($structure_id);
		$system_id = App::escape($system_id);
		$quantity = App::escape($quantity);

		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		if(!$structure_id) return $this->error('Please select an area.');
		$area = $sql->query_row("SELECT id FROM project_structure WHERE id = '$structure_id' AND type = 'area';", MySQL::QUERY_ASSOC);
		if(!$area) return $this->error('Area not found.');

		if(!$system_id) return $this->error('Please select a system.');
		$system = $sql->query_row("SELECT project_id FROM project_system_assign WHERE system_id = '$system_id' AND project_id = '$id';", MySQL::QUERY_ASSOC);
		if(!$system) return $this->error('System not assigned to project.');

		$product = App::select('product', $product_id);
		if(!$product) return $this->error('Product not found.');

		$seller_level = 'SI';
		$seller_id = $project->info['system_integrator_id'];

		$price = $sql->query_row("SELECT * FROM product_price WHERE product_id = '$product_id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';", MySQL::QUERY_ASSOC);
		if(!$price) return $this->error('Product not found.');

		// See if items need to be on a single line
		// Single line is forced if:
		//  - Item has a placeholder in the BOM (selectable slots)
		//  - Item has any accessories
		//  - Item is a bundle
		$is_single = 0;
		$placeholder_in_bom = $sql->query_row(
			"SELECT bom.id FROM product_bom AS bom
			JOIN product AS p ON p.id = bom.product_id AND p.is_placeholder = 1
			WHERE bom.parent_id = '$product_id'
			LIMIT 1;
		");
		$has_accessories = $sql->query_row("SELECT accessory_id FROM product_accessories WHERE product_id = '$product_id' LIMIT 1;");
		if($placeholder_in_bom || $has_accessories || $product['is_bundle']) {
			$is_single = 1;
		}

		// Add to toolbox if needed
		if($toolbox) {
			App::insert_ignore('project_system_products', [
				'product_id' => $product_id,
				'system_id' => $system_id,
				'sort_order' => 9999
			]);
		}

		// See if a similar line already exists
		$line_id = 0;
		if(!$forced && !$is_single) {
			$line = $sql->query_row(
				"SELECT id FROM project_line
				WHERE
					project_id = '$id'
					AND product_id = '$product_id'
					AND structure_id = '$structure_id'
					AND system_id = '$system_id'
					AND parent_id IS NULL
				LIMIT 1;
			", MySQL::QUERY_ASSOC);

			if($line) $line_id = $line['id'];
		}

		if($line_id) {
			// Line found, add quantity
			$sql->update("UPDATE project_line SET quantity = quantity + '$quantity' WHERE id = '$line_id';");
			return $this->success($line_id);

		} else {
			$bundle_id = null;
			if($product['is_bundle']) {
				$bundle = Bundle::for_product($product_id);
				if($bundle->id) $bundle_id = $bundle->id;
			}

			// Not found or forced, create new line
			$record = [
				'project_id' => $id,
				'product_id' => $product_id,
				'structure_id' => $structure_id,
				'system_id' => $system_id,
				'unit_cost' => $price['unit_cost'] ?: 0,
				'quantity' => $quantity,
				'is_single' => $is_single,
				'bundle_id' => $bundle_id
			];

			switch($project->info['price_tier']) {
				case 'cost': $record['base_unit_price'] = $price['unit_cost']; break;
				case 'distribution': $record['base_unit_price'] = $price['distribution_price']; break;
				case 'reseller': $record['base_unit_price'] = $price['reseller_price']; break;
				case 'trade': $record['base_unit_price'] = $price['trade_price']; break;
				case 'retail': $record['base_unit_price'] = $price['retail_price']; break;
				default: $record['base_unit_price'] = 0; break;
			}

			$result = App::insert('project_line', $record);
			if(!$result) return $this->error('Error saving data.');

			// Apply line price adjustments
			$project->apply_price_adjustments($result);

			if(!$project->exclude_labour()) {
				// Copy labour types
				$labour_condition = "(pl.seller_level = '$seller_level' AND pl.seller_id = '$seller_id')";
				if($price['recommended_labour']) $labour_condition .= " OR (pl.seller_level = p.owner_level AND pl.seller_id = p.owner_id)";

				$types = $sql->query(
					"SELECT
						pl.id AS product_labour_id,
						pl.labour_type_id, pl.labour_hours,
						plt.hourly_cost, plt.hourly_price
					FROM product_labour AS pl
					JOIN product AS p ON p.id = pl.product_id
					JOIN product_labour_type AS plt ON plt.id = pl.labour_type_id
					WHERE pl.product_id = '$product_id' AND ($labour_condition);
				", MySQL::QUERY_ASSOC) ?: [];

				foreach($types as $type) {
					$type['line_id'] = $result;
					App::insert('project_labour', $type);
				}
			}

			if(!$project->exclude_subscriptions()) {
				// Copy subscription types
				$price_field = $project->info['subscription_price_tier'] === 'cost' ? 'unit_cost' : $project->info['subscription_price_tier'].'_price';

				$types = $sql->query(
					"SELECT
						ps.id AS product_subscription_id,
						ps.subscription_type_id, ps.quantity,
						psp.unit_cost, psp.$price_field AS unit_price, pst.frequency
					FROM product_subscription AS ps
					JOIN product AS p ON p.id = ps.product_id
					JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
					JOIN product_subscription_price AS psp ON psp.subscription_type_id = pst.id AND psp.seller_level = '$seller_level' AND psp.seller_id = '$seller_id'
					WHERE ps.product_id = '$product_id' AND ps.selection IN ('fixed', 'optional') AND ((ps.seller_level = '$seller_level' AND ps.seller_id = '$seller_id') OR (ps.seller_level = p.owner_level AND ps.seller_id = p.owner_id));
				", MySQL::QUERY_ASSOC) ?: [];

				foreach($types as $type) {
					$type['line_id'] = $result;
					App::insert('project_subscription', $type);
				}
			}

			// Add default accessories
			$price_field = 'retail_price';
			switch($project->info['price_tier']) {
				case 'cost': $price_field = 'unit_cost'; break;
				case 'distribution': $price_field = 'distribution_price'; break;
				case 'reseller': $price_field = 'reseller_price'; break;
				case 'trade': $price_field = 'trade_price'; break;
				case 'retail': $price_field = 'retail_price'; break;
			}

			$accessories = $sql->query(
				"SELECT
					'new' AS line_id,
					p.id AS product_id, p.sku, p.model, pm.name AS manufacturer_name,
					pa.system_id, ps.description AS system_description,
					pa.default_quantity AS quantity,
					pp.unit_cost AS unit_cost,
					pp.$price_field AS base_unit_price,
					pp.$price_field AS unit_price,
					pu.name AS unit_name,
					pu.decimal_places AS unit_decimal_places
				FROM product_accessories AS pa
				JOIN product AS p ON p.id = pa.accessory_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				LEFT JOIN project_system AS ps ON ps.id = pa.system_id
				LEFT JOIN product_unit AS pu ON pu.id = p.unit_id
				WHERE pa.product_id = '$product_id' AND pa.default_quantity > 0
				ORDER BY p.sku;
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($accessories as $a) {
				$a = App::ensure($a, ['line_id', 'product_id', 'quantity', 'unit_cost', 'base_unit_price', 'unit_price'], 0);

				// Create accessory line
				$a_line_id = App::insert('project_line', [
					'project_id' => $project->id,
					'parent_id' => $result,
					'product_id' => $a['product_id'],
					'structure_id' => $record['structure_id'],
					'system_id' => $a['system_id'] ?: $record['system_id'],
					'is_system_fixed' => $a['system_id'] ? 1 : 0,
					'unit_cost' => $a['unit_cost'] ?: 0,
					'base_unit_price' => $a['base_unit_price'] ?: 0,
					'unit_price' => $a['unit_price'] ?: 0,
					'quantity' => $a['quantity']
				]);

				if($a_line_id) {
					$a_product_id = App::escape($a['product_id']);

					if(!$project->exclude_labour()) {
						$sql->insert(
							"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
							SELECT
								'$a_line_id' AS line_id,
								pl.labour_type_id,
								pl.labour_hours,
								plt.hourly_cost,
								plt.hourly_price,
								pl.id
							FROM product_labour AS pl
							JOIN product AS p ON p.id = pl.product_id
							JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
							JOIN product_labour_type AS plt ON plt.id = pl.labour_type_id
							WHERE pl.product_id = '$a_product_id' AND ((pl.seller_level = '$seller_level' AND pl.seller_id = '$seller_id') OR (pp.recommended_labour = 1 AND pl.seller_level = p.owner_level AND pl.seller_id = p.owner_id));
						");
					}

					if(!$project->exclude_subscriptions()) {
						$subscription_price_field = $project->info['subscription_price_tier'] === 'cost' ? 'unit_cost' : $project->info['subscription_price_tier'].'_price';

						$sql->insert(
							"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
							SELECT
								'$a_line_id' AS line_id,
								ps.subscription_type_id,
								ps.quantity,
								psp.unit_cost,
								psp.$subscription_price_field,
								pst.frequency,
								ps.id
							FROM product_subscription AS ps
							JOIN product AS p ON p.id = ps.product_id
							JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
							JOIN product_subscription_price AS psp ON psp.subscription_type_id = pst.id AND psp.seller_level = 'SI' AND psp.seller_id = '$seller_id'
							WHERE ps.product_id = '$a_product_id' AND ps.selection = 'fixed' AND ((ps.seller_level = '$seller_level' AND ps.seller_id = '$seller_id') OR (ps.seller_level = p.owner_level AND ps.seller_id = p.owner_id));
						");
					}

					// Apply line price adjustments
					$project->apply_price_adjustments($a_line_id);
				}
			}

			return $this->success($result);
		}
	}

	public function increase_project_line() {
		$data = App::json();
		$data = App::keep($data, ['id', 'quantity']);
		$data = App::ensure($data, ['id', 'quantity'], 0);

		$id = $data['id'];
		$quantity = $data['quantity'];
		if(!$id) return $this->access_denied();

		$record = App::select('project_line', $id);
		if(!$record) return $this->access_denied();

		$project = new Project($record['project_id']);
		if(!$project->validate()) return $this->access_denied();

		if($record['is_single']) return $this->error('Quantity of complex products cannot be changed.');
		if($quantity == 0 || $record['quantity'] + $quantity < 1) return $this->success($record['quantity']);

		App::sql()->update("UPDATE project_line SET quantity = quantity + '$quantity' WHERE id = '$id';");
		$record = App::select('project_line', $id);
		if(!$record) return $this->error('Product not found.');
		return $this->success($record['quantity']);
	}

	public function copy_project_line() {
		$sql = App::sql();
		$id = App::get('id', 0, true);

		if(!$id) return $this->access_denied();

		$record = App::select('project_line', $id);
		if(!$record) return $this->access_denied();
		if($record['parent_id'] !== null) return $this->error("Cannot copy an accessory line.");

		$project = new Project($record['project_id']);
		if(!$project->validate()) return $this->access_denied();

		$new_id = $sql->insert(
			"INSERT INTO project_line (project_id, product_id, structure_id, system_id, unit_cost, base_unit_price, unit_price, quantity, is_single, bundle_id, is_bundle_item)
			SELECT project_id, product_id, structure_id, system_id, unit_cost, base_unit_price, unit_price, quantity, is_single, bundle_id, is_bundle_item FROM project_line WHERE id = '$id';
		");

		if(!$new_id) return $this->error('Product not found.');

		$sql->insert(
			"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
			SELECT '$new_id' AS line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id FROM project_labour WHERE line_id = '$id';
		");

		$sql->insert(
			"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
			SELECT '$new_id' AS line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id FROM project_subscription WHERE line_id = '$id';
		");

		$sql->insert(
			"INSERT INTO project_line_slots (line_id, slot_no, placeholder_id, quantity, product_id)
			SELECT '$new_id' AS line_id, slot_no, placeholder_id, quantity, product_id FROM project_line_slots WHERE line_id = '$id';
		");

		$sql->insert(
			"INSERT INTO project_line_bundle_answers (line_id, question_id, answer)
			SELECT '$new_id' AS line_id, question_id, answer FROM project_line_bundle_answers WHERE line_id = '$id';
		");

		// Loop through and add all accessories (including their labour and subscription records)
		$accessories = $sql->query("SELECT * FROM project_line WHERE parent_id = '$id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($accessories as $a) {
			$record = $a;
			$record['parent_id'] = $new_id;
			unset($record['id']);
			$new_a_id = App::insert('project_line', $record);
			$old_a_id = $a['id'];

			$sql->insert(
				"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
				SELECT '$new_a_id' AS line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id FROM project_labour WHERE line_id = '$old_a_id';
			");

			$sql->insert(
				"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
				SELECT '$new_a_id' AS line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id FROM project_subscription WHERE line_id = '$old_a_id';
			");
		}

		return $this->success($new_id);
	}

	public function new_project_line() {
		$sql = App::sql();
		$data = App::json();

		$data = App::keep($data, ['project_id', 'product_id', 'structure_id', 'system_id', 'is_single', 'description']);
		$data = App::ensure($data, ['project_id', 'structure_id', 'system_id', 'is_single'], 0);
		$data = App::ensure($data, ['product_id', 'description'], null);

		$project_id = App::escape($data['project_id']);
		$product_id = App::escape($data['product_id']);
		$structure_id = App::escape($data['structure_id']);
		$system_id = App::escape($data['system_id']);

		if(!$project_id) return $this->access_denied();
		if(!$structure_id) return $this->error('Please select an area.');
		if(!$system_id) return $this->error('Please select a system.');

		$project = new Project($project_id);
		if(!$project->validate()) return $this->access_denied();

		$price_field = 'retail_price';
		switch($project->info['price_tier']) {
			case 'cost': $price_field = 'unit_cost'; break;
			case 'distribution': $price_field = 'distribution_price'; break;
			case 'reseller': $price_field = 'reseller_price'; break;
			case 'trade': $price_field = 'trade_price'; break;
			case 'retail': $price_field = 'retail_price'; break;
		}

		$subscription_price_field = 'retail_price';
		switch($project->info['subscription_price_tier']) {
			case 'cost': $subscription_price_field = 'unit_cost'; break;
			case 'distribution': $subscription_price_field = 'distribution_price'; break;
			case 'reseller': $subscription_price_field = 'reseller_price'; break;
			case 'trade': $subscription_price_field = 'trade_price'; break;
			case 'retail': $subscription_price_field = 'retail_price'; break;
		}

		$seller_level = 'SI';
		$seller_id = $project->info['system_integrator_id'];

		$owner_filter = "
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$seller_level' AND pr.reseller_id = '$seller_id'
			WHERE ((t.owner_level = '$seller_level' AND t.owner_id = '$seller_id') OR pr.owner_level IS NOT NULL)
		";

		if($product_id) {
			$details = $sql->query_row(
				"SELECT
					p.id AS product_id,
					p.owner_level,
					p.owner_id,
					pst.id AS structure_id,
					ps.id AS system_id,
					pp.unit_cost AS unit_cost,
					pp.$price_field AS base_unit_price,
					pp.$price_field AS unit_price,
					pp.recommended_labour,

					p.sku,
					p.model,
					p.image_id,
					pm.name AS manufacturer_name,
					pu.name AS unit_name,
					pu.decimal_places AS unit_decimal_places,
					b.id AS bundle_id,

					ps.description AS system_description,
					pst.description AS structure_description

				FROM product AS p
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				LEFT JOIN project_structure AS pst ON pst.id = '$structure_id'
				LEFT JOIN project_system AS ps ON ps.id = '$system_id'
				LEFT JOIN product_unit AS pu ON pu.id = p.unit_id
				LEFT JOIN bundle AS b ON b.product_id = p.id AND is_latest = 1 AND p.is_bundle = 1
				WHERE p.id = '$product_id'
				LIMIT 1;
			", MySQL::QUERY_ASSOC);
		} else {
			$details = $sql->query_row(
				"SELECT
					NULL AS product_id,
					NULL AS owner_level,
					NULL AS owner_id,
					pst.id AS structure_id,
					ps.id AS system_id,
					0 AS unit_cost,
					0 AS base_unit_price,
					0 AS unit_price,
					0 AS recommended_labour,

					'' AS sku,
					'' AS model,
					NULL AS image_id,
					'' AS manufacturer_name,
					'' AS unit_name,
					4 AS unit_decimal_places,
					NULL AS bundle_id,

					ps.description AS system_description,
					pst.description AS structure_description

				FROM project_structure AS pst
				LEFT JOIN project_system AS ps ON ps.id = '$system_id'
				WHERE pst.id = '$structure_id';
			", MySQL::QUERY_ASSOC);
		}
		if(!$details) return $this->error('Product not found');

		$details = array_merge($details, [
			'id' => 'new',
			'parent_id' => null,
			'project_id' => $project_id,
			'quantity' => 1,
			'is_single' => $data['is_single'] == 1 ? 1 : 0,
			'description' => $data['description'],
			'show_accessories' => 0
		]);

		$url = '';
		if($details['image_id']) {
			$uc = new UserContent($details['image_id']);
			$url = $uc->get_url();
		}
		$details['image_url'] = $url;

		if($project->exclude_labour()) {
			$labour = [];
			$labour_types = [];
			$labour_categories = [];
			$details['labour'] = [];
		} else {
			$labour_condition = "(pl.seller_level = '$seller_level' AND pl.seller_id = '$seller_id')";
			if($details['recommended_labour']) $labour_condition .= " OR (pl.seller_level = p.owner_level AND pl.seller_id = p.owner_id)";

			$labour = $sql->query(
				"SELECT
					'new' AS id, pl.labour_type_id, pl.labour_hours, plt.hourly_cost, plt.hourly_price, pl.id AS product_labour_id
				FROM product_labour AS pl
				JOIN product AS p ON p.id = pl.product_id
				JOIN product_labour_type AS plt ON plt.id = pl.labour_type_id
				WHERE pl.product_id = '$product_id' AND ($labour_condition);
			", MySQL::QUERY_ASSOC);
			$details['labour'] = $labour ?: [];

			$labour_types = $sql->query("SELECT t.* FROM product_labour_type AS t $owner_filter ORDER BY t.description;");
			$labour_categories = $sql->query("SELECT t.* FROM product_labour_category AS t $owner_filter ORDER BY t.description;");
		}

		if($project->exclude_subscriptions()) {
			$subscription_setup = [];
			$subscription_types = [];
			$subscription_categories = [];
			$details['subscription'] = [];
		} else {
			$subscription_setup = $sql->query(
				"SELECT id, subscription_type_id, quantity, selection
				FROM product_subscription
				WHERE product_id = '$product_id' AND ((seller_level = '$seller_level' AND seller_id = '$seller_id') OR (seller_level = '$details[owner_level]' AND seller_id = '$details[owner_id]'));
			", MySQL::QUERY_ASSOC);
			$details['subscription'] = [];

			$subscription_types = $sql->query(
				"SELECT st.*, psp.unit_cost, psp.$subscription_price_field AS unit_price
				FROM product_subscription_type AS st
				JOIN product_subscription_price AS psp ON psp.subscription_type_id = st.id AND psp.seller_level = '$seller_level' AND psp.seller_id = '$seller_id'
				ORDER BY description;
			");
			$subscription_categories = $sql->query("SELECT t.* FROM product_subscription_category AS t $owner_filter ORDER BY t.description;");
		}

		$floors = [];
		$list = $sql->query("SELECT id, description FROM project_structure WHERE project_id = '$project_id' AND type = 'floor';", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $f) {
			$f['areas'] = [];
			$fid = $f['id'];
			$areas = $sql->query("SELECT id, description FROM project_structure WHERE project_id = '$project_id' AND type = 'area' AND parent_id = '$fid';", MySQL::QUERY_ASSOC) ?: [];
			foreach($areas as $a) {
				$f['areas'][] = $a;
			}
			$floors[] = $f;
		}

		$systems = $sql->query(
			"SELECT
				ps.id, ps.description, ps.module_id
			FROM project_system_assign AS psa
			JOIN project_system AS ps ON ps.id = psa.system_id
			WHERE project_id = '$project_id'
			ORDER BY ps.description;
		", MySQL::QUERY_ASSOC);

		$modules = $sql->query("SELECT t.id, t.description FROM project_module AS t $owner_filter ORDER BY t.display_order;");

		// Prepare item slots
		$slots = [];
		$slot_products = [];
		$placeholders = $sql->query(
			"SELECT
				bom.product_id, bom.quantity, bom.is_separable, p.model
			FROM product_bom AS bom
			JOIN product AS p ON p.id = bom.product_id
			WHERE p.is_placeholder = 1 AND bom.parent_id = '$product_id';
		", MySQL::QUERY_ASSOC) ?: [];

		$slot_index = 0;
		foreach($placeholders as $p) {
			$p_id = $p['product_id'];
			$product_list = $sql->query(
				"SELECT
					p.id, p.sku, p.model, pm.name AS manufacturer_name
				FROM product_placeholders AS pp
				JOIN product AS p ON p.id = pp.product_id
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				WHERE pp.placeholder_id = '$p_id'
				ORDER BY pm.name, p.sku;
			", MySQL::QUERY_ASSOC);

			if($p['is_separable']) {
				for($i = 0; $i < $p['quantity']; $i++) {
					$slots[] = [
						'placeholder_id' => $p_id,
						'quantity' => 1,
						'product_id' => null,
						'description' => $p['model']
					];
					$slot_products[] = $product_list ?: [];
				}
			} else {
				$slots[] = [
					'placeholder_id' => $p_id,
					'quantity' => $p['quantity'],
					'product_id' => null,
					'description' => $p['model']
				];
				$slot_products[] = $product_list ?: [];
			}
		}
		$details['slots'] = $slots;

		// Get accessories
		$accessories = $sql->query(
			"SELECT
				'new' AS line_id,
				p.id AS product_id, p.sku, p.model, pm.name AS manufacturer_name,
				pa.system_id, ps.description AS system_description,
				pa.default_quantity AS quantity,
				pp.unit_cost AS unit_cost,
				pp.$price_field AS base_unit_price,
				pp.$price_field AS unit_price,
				pu.name AS unit_name,
				pu.decimal_places AS unit_decimal_places
			FROM product_accessories AS pa
			JOIN product AS p ON p.id = pa.accessory_id
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
			LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
			LEFT JOIN project_system AS ps ON ps.id = pa.system_id
			LEFT JOIN product_unit AS pu ON pu.id = p.unit_id
			WHERE pa.product_id = '$product_id'
			ORDER BY p.sku;
		", MySQL::QUERY_ASSOC);

		$details['accessories'] = $accessories ?: [];

		// Add bundle

		$bundle = null;
		if($details['bundle_id']) {
			$bundle = new Bundle($details['bundle_id']);
			$bundle = $bundle->validate() ? $bundle->get_object() : null;
		}

		if($bundle) $bundle['answers'] = [];

		return $this->success([
			'project' => $project->info,
			'pricing' => $project->can_show_pricing(),
			'details' => $details,
			'floors' => $floors,
			'systems' => $systems ?: [],
			'modules' => $modules ?: [],
			'labour_types' => $labour_types ?: [],
			'labour_categories' => $labour_categories ?: [],
			'subscription_setup' => $subscription_setup ?: [],
			'subscription_types' => $subscription_types ?: [],
			'subscription_categories' => $subscription_categories ?: [],
			'slot_products' => $slot_products,
			'exclude_labour' => $project->exclude_labour(),
			'exclude_subscriptions' => $project->exclude_subscriptions(),
			'bundle' => $bundle
		]);
	}

	public function get_project_line() {
		$sql = App::sql();
		$id = App::get('id', 0);
		if(!$id) return $this->access_denied();

		$id = App::escape($id);

		$details = $sql->query_row(
			"SELECT
				pl.*,
				p.owner_level,
				p.owner_id,

				p.sku,
				p.model,
				p.short_description,
				p.image_id,
				pm.name AS manufacturer_name,
				pu.name AS unit_name,
				pu.decimal_places AS unit_decimal_places,

				ps.description AS system_description,
				pst.description AS structure_description

			FROM project_line AS pl
			LEFT JOIN product AS p ON p.id = pl.product_id
			LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
			LEFT JOIN project_structure AS pst ON pst.id = pl.structure_id
			LEFT JOIN project_system AS ps ON ps.id = pl.system_id
			LEFT JOIN product_unit AS pu ON pu.id = p.unit_id
			WHERE pl.id = '$id';
		", MySQL::QUERY_ASSOC);

		if(!$details) return $this->error('Record not found');

		$project_id = $details['project_id'];
		$project = new Project($project_id);
		if(!$project->validate()) return $this->access_denied();

		$price_field = 'retail_price';
		switch($project->info['price_tier']) {
			case 'cost': $price_field = 'unit_cost'; break;
			case 'distribution': $price_field = 'distribution_price'; break;
			case 'reseller': $price_field = 'reseller_price'; break;
			case 'trade': $price_field = 'trade_price'; break;
			case 'retail': $price_field = 'retail_price'; break;
		}

		$subscription_price_field = 'retail_price';
		switch($project->info['subscription_price_tier']) {
			case 'cost': $subscription_price_field = 'unit_cost'; break;
			case 'distribution': $subscription_price_field = 'distribution_price'; break;
			case 'reseller': $subscription_price_field = 'reseller_price'; break;
			case 'trade': $subscription_price_field = 'trade_price'; break;
			case 'retail': $subscription_price_field = 'retail_price'; break;
		}

		$seller_level = 'SI';
		$seller_id = $project->info['system_integrator_id'];

		$owner_filter = "
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$seller_level' AND pr.reseller_id = '$seller_id'
			WHERE ((t.owner_level = '$seller_level' AND t.owner_id = '$seller_id') OR pr.owner_level IS NOT NULL)
		";

		$product_id = $details['product_id'];

		$url = '';
		if($details['image_id']) {
			$uc = new UserContent($details['image_id']);
			$url = $uc->get_url();
		}
		$details['image_url'] = $url;

		if($project->exclude_labour()) {
			$labour = [];
			$labour_types = [];
			$labour_categories = [];
			$details['labour'] = [];
		} else {
			$labour = $sql->query(
				"SELECT
					id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id
				FROM project_labour
				WHERE line_id = '$id'
				ORDER BY product_labour_id DESC, id;
			", MySQL::QUERY_ASSOC);
			$details['labour'] = $labour ?: [];

			$labour_types = $sql->query("SELECT t.* FROM product_labour_type AS t $owner_filter ORDER BY t.description;");
			$labour_categories = $sql->query("SELECT t.* FROM product_labour_category AS t $owner_filter ORDER BY t.description;");
		}

		if($project->exclude_subscriptions()) {
			$subscription_setup = [];
			$subscription = [];
			$subscription_types = [];
			$subscription_categories = [];
			$details['subscription'] = [];
		} else {
			$subscription_setup = $sql->query(
				"SELECT id, subscription_type_id, quantity, selection
				FROM product_subscription
				WHERE product_id = '$product_id' AND ((seller_level = '$seller_level' AND seller_id = '$seller_id') OR (seller_level = '$details[owner_level]' AND seller_id = '$details[owner_id]'));
			", MySQL::QUERY_ASSOC);

			$subscription = $sql->query(
				"SELECT
					id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id
				FROM project_subscription
				WHERE line_id = '$id'
				ORDER BY product_subscription_id DESC, id;
			", MySQL::QUERY_ASSOC);
			$details['subscription'] = $subscription ?: [];

			$subscription_types = $sql->query(
				"SELECT st.*, psp.unit_cost, psp.$subscription_price_field AS unit_price
				FROM product_subscription_type AS st
				JOIN product_subscription_price AS psp ON psp.subscription_type_id = st.id AND psp.seller_level = '$seller_level' AND psp.seller_id = '$seller_id'
				ORDER BY description;
			");
			$subscription_categories = $sql->query("SELECT t.* FROM product_subscription_category AS t $owner_filter ORDER BY t.description;");
		}

		$slots = [];
		if($details['parent_id'] === null) {
			$slots = $sql->query(
				"SELECT
					pls.placeholder_id, pls.quantity, pls.product_id,
					p.model AS description
				FROM project_line_slots AS pls
				LEFT JOIN product AS p ON p.id = pls.placeholder_id
				WHERE pls.line_id = '$id'
				ORDER BY pls.slot_no;
			", MySQL::QUERY_ASSOC) ?: [];
		}
		$details['slots'] = $slots;

		$slot_products = [];
		foreach($slots as $s) {
			$p_id = $s['placeholder_id'];
			$product_list = $sql->query(
				"SELECT
					p.id, p.sku, p.model, pm.name AS manufacturer_name
				FROM product_placeholders AS pp
				JOIN product AS p ON p.id = pp.product_id
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				WHERE pp.placeholder_id = '$p_id'
				ORDER BY pm.name, p.sku;
			", MySQL::QUERY_ASSOC);

			$slot_products[] = $product_list ?: [];
		}

		$floors = [];
		$list = $sql->query("SELECT id, description FROM project_structure WHERE project_id = '$project_id' AND type = 'floor';", MySQL::QUERY_ASSOC) ?: [];
		foreach($list as $f) {
			$f['areas'] = [];
			$fid = $f['id'];
			$areas = $sql->query("SELECT id, description FROM project_structure WHERE project_id = '$project_id' AND type = 'area' AND parent_id = '$fid';", MySQL::QUERY_ASSOC) ?: [];
			foreach($areas as $a) {
				$f['areas'][] = $a;
			}
			$floors[] = $f;
		}

		$systems = $sql->query(
			"SELECT
				ps.id, ps.description, ps.module_id
			FROM project_system_assign AS psa
			JOIN project_system AS ps ON ps.id = psa.system_id
			WHERE project_id = '$project_id'
			ORDER BY ps.description;
		", MySQL::QUERY_ASSOC);

		$modules = $sql->query("SELECT t.id, t.description FROM project_module AS t $owner_filter ORDER BY t.display_order;");

		// Get accessories
		$accessories = [];
		$extra_lines = null;
		if($details['parent_id'] === null) {
			$accessories = $sql->query(
				"SELECT
					COALESCE(CAST(pl.id AS CHAR(50)), 'new') AS line_id,
					p.id AS product_id, p.sku, p.model, pm.name AS manufacturer_name,
					pa.system_id, ps.description AS system_description,
					COALESCE(pl.quantity, 0) AS quantity,
					pp.unit_cost AS unit_cost,
					pp.$price_field AS base_unit_price,
					pp.$price_field AS unit_price,
					pu.name AS unit_name,
					pu.decimal_places AS unit_decimal_places,
					uc.path AS image_url
				FROM product_accessories AS pa
				JOIN product AS p ON p.id = pa.accessory_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				LEFT JOIN project_system AS ps ON ps.id = pa.system_id
				LEFT JOIN project_line AS pl ON pl.parent_id = '$id' AND pl.product_id = p.id AND pl.is_bundle_item = 0
				LEFT JOIN product_unit AS pu ON pu.id = p.unit_id
				LEFT JOIN user_content AS uc ON uc.id = p.image_id
				WHERE pa.product_id = '$product_id'
				ORDER BY p.sku;
			", MySQL::QUERY_ASSOC) ?: [];

			$extra_lines = $sql->query(
				"SELECT
					pl.id AS line_id,
					pl.product_id, p.sku, p.model, pm.name AS manufacturer_name,
					ps.id AS system_id, ps.description AS system_description,
					pl.quantity,
					pp.unit_cost AS unit_cost,
					pp.$price_field AS base_unit_price,
					pp.$price_field AS unit_price,
					pu.name AS unit_name,
					pu.decimal_places AS unit_decimal_places,
					uc.path AS image_url
				FROM project_line AS pl
				JOIN product AS p ON p.id = pl.product_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				LEFT JOIN project_system AS ps ON ps.id = pl.system_id AND pl.is_system_fixed = 1
				LEFT JOIN product_unit AS pu ON pu.id = p.unit_id
				LEFT JOIN user_content AS uc ON uc.id = p.image_id
				WHERE
					pl.parent_id = '$id' AND pl.is_bundle_item = 0
					AND pl.product_id NOT IN (SELECT accessory_id FROM product_accessories WHERE product_id = '$product_id')
				ORDER BY p.sku;
			", MySQL::QUERY_ASSOC);
		}

		if($extra_lines) $accessories = array_merge($accessories, $extra_lines);
		foreach($accessories as &$item) {
			if($item['image_url']) {
				$item['image_url'] = UserContent::url_by_path($item['image_url']);
			}
		}
		unset($item);

		$details['accessories'] = $accessories;

		// Add bundle

		$bundle = null;
		if($details['bundle_id']) {
			$bundle = new Bundle($details['bundle_id']);
			$bundle = $bundle->validate() ? $bundle->get_object() : null;
		}

		if($bundle) $bundle['answers'] = App::sql()->query("SELECT * FROM project_line_bundle_answers WHERE line_id = '$id';") ?: [];

		return $this->success([
			'project' => $project->info,
			'pricing' => $project->can_show_pricing(),
			'details' => $details,
			'floors' => $floors,
			'systems' => $systems ?: [],
			'modules' => $modules ?: [],
			'labour_types' => $labour_types ?: [],
			'labour_categories' => $labour_categories ?: [],
			'subscription_setup' => $subscription_setup ?: [],
			'subscription_types' => $subscription_types ?: [],
			'subscription_categories' => $subscription_categories ?: [],
			'slot_products' => $slot_products,
			'exclude_labour' => $project->exclude_labour(),
			'exclude_subscriptions' => $project->exclude_subscriptions(),
			'bundle' => $bundle
		]);
	}

	public function save_project_line() {
		$sql = App::sql();
		$user = App::user();
		$data = App::json();

		$record = $data;
		$record = App::keep($record, ['id', 'structure_id', 'system_id', 'quantity', 'project_id', 'product_id', 'unit_cost', 'base_unit_price', 'unit_price', 'is_single', 'notes', 'description', 'show_accessories', 'bundle_id']);
		$record = App::ensure($record, ['id', 'quantity', 'project_id', 'unit_cost', 'base_unit_price', 'unit_price', 'is_single', 'show_accessories'], 0);
		$record = App::ensure($record, ['product_id', 'notes', 'description', 'bundle_id'], null);

		$id = $record['id'];
		unset($record['id']);
		$new_record = $id === 'new';

		if(!$record['product_id']) {
			// If line has no product, set base_unit_price to match unit_price
			$record['base_unit_price'] = $record['unit_price'];
		}

		if(!$new_record) {
			// These fields can only be saved when adding a new record
			if($record['product_id']) {
				unset($record['unit_cost']);
				unset($record['base_unit_price']);
				unset($record['unit_price']);
			}

			unset($record['project_id']);
			unset($record['product_id']);
			unset($record['is_single']);
		}

		if(!$id) return $this->access_denied();

		if($new_record) {
			$project_id = $record['project_id'];
			$project = new Project($project_id);
			if(!$project->validate()) return $this->access_denied();

			$id = App::insert('project_line', $record);
			if(!$id) return $this->error('Error saving data.');
		} else {
			$original = App::select('project_line', $id);
			if(!$original) return $this->error('Line not found.');
			$project_id = $original['project_id'];
			$project = new Project($project_id);
			if(!$project->validate()) return $this->access_denied();

			App::update('project_line', $id, $record);
		}

		// Get project record
		$subscription_price_field = $project->info['subscription_price_tier'] === 'cost' ? 'unit_cost' : $project->info['subscription_price_tier'].'_price';
		$price_field = $project->info['price_tier'] === 'cost' ? 'unit_cost' : $project->info['price_tier'].'_price';

		$seller_level = 'SI';
		$seller_id = $project->info['system_integrator_id'];

		// Save labour
		if(!$project->exclude_labour()) {
			if(isset($data['labour_deleted'])) {
				$list = $data['labour_deleted'] ?: [];
				foreach($list as $item) {
					$item_id = App::escape($item['id']);
					if($item['id'] !== 'new') $sql->delete("DELETE FROM project_labour WHERE id = '$item_id' AND product_labour_id IS NULL;");
				}
			}

			$list = $data['labour'] ?: [];
			foreach($list as $item) {
				$item_id = App::escape($item['id']);
				$item_labour_type_id = $item['labour_type_id'];
				$item_labour_hours = $item['labour_hours'] ?: 0;
				$item_hourly_cost = $item['hourly_cost'] ?: 0;
				$item_hourly_price = $item['hourly_price'] ?: 0;
				$item_product_labour_id = $item['product_labour_id'] ?: null;

				// Details have been cleared, ignore/delete item
				if($item_labour_type_id === null || $item_labour_hours == 0) {
					if($item_id === 'new') {
						continue;
					} else {
						App::delete('project_labour', $item_id);
						continue;
					}
				}

				$exists = $item_id === 'new' ? false : $sql->query_row("SELECT id FROM project_labour WHERE id = '$item_id';");

				if($item_id !== 'new' && $exists) {
					App::update('project_labour', $item_id, [
						'labour_type_id' => $item_labour_type_id,
						'labour_hours' => $item_labour_hours,
						'hourly_cost' => $item_hourly_cost,
						'hourly_price' => $item_hourly_price
					]);
				} else {
					App::insert('project_labour', [
						'line_id' => $id,
						'labour_type_id' => $item_labour_type_id,
						'labour_hours' => $item_labour_hours,
						'hourly_cost' => $item_hourly_cost,
						'hourly_price' => $item_hourly_price,
						'product_labour_id' => $new_record ? $item_product_labour_id : null
					]);
				}
			}
		}

		// Save subscriptions
		if(!$project->exclude_subscriptions()) {
			if(isset($data['subscription_deleted'])) {
				$list = $data['subscription_deleted'] ?: [];
				foreach($list as $item) {
					$item_id = App::escape($item['id']);
					if($item['id'] !== 'new') $sql->delete("DELETE FROM project_subscription WHERE id = '$item_id';");
				}
			}

			$list = $data['subscription'] ?: [];
			foreach($list as $item) {
				$item_id = App::escape($item['id']);
				$item_subscription_type_id = $item['subscription_type_id'];
				$item_quantity = $item['quantity'] ?: 0;
				$item_unit_cost = $item['unit_cost'] ?: 0;
				$item_unit_price = $item['unit_price'] ?: 0;
				$item_frequency = $item['frequency'] ?: 'monthly';
				$item_product_subscription_id = $item['product_subscription_id'] ?: null;

				// Details have been cleared, ignore/delete item
				if($item_subscription_type_id === null || $item_quantity == 0) {
					if($item_id === 'new') {
						continue;
					} else {
						App::delete('project_subscription', $item_id);
						continue;
					}
				}

				$exists = $item_id === 'new' ? false : $sql->query_row("SELECT id FROM project_subscription WHERE id = '$item_id';");

				if($item_id !== 'new' && $exists) {
					App::update('project_subscription', $item_id, [
						'subscription_type_id' => $item_subscription_type_id,
						'quantity' => $item_quantity,
						'unit_cost' => $item_unit_cost,
						'unit_price' => $item_unit_price,
						'frequency' => $item_frequency
					]);
				} else {
					App::insert('project_subscription', [
						'line_id' => $id,
						'subscription_type_id' => $item_subscription_type_id,
						'quantity' => $item_quantity,
						'unit_cost' => $item_unit_cost,
						'unit_price' => $item_unit_price,
						'frequency' => $item_frequency,
						'product_subscription_id' => $item_product_subscription_id
					]);
				}
			}
		}

		// Save slots
		$sql->delete("DELETE FROM project_line_slots WHERE line_id = '$id';");

		if(!$data['slots']) $data['slots'] = [];
		foreach($data['slots'] as $i => $s) {
			App::insert('project_line_slots', [
				'line_id' => $id,
				'slot_no' => $i,
				'placeholder_id' => $s['placeholder_id'],
				'quantity' => $s['quantity'],
				'product_id' => $s['product_id']
			]);
		}

		// Save accessories
		if(!$data['accessories']) $data['accessories'] = [];
		foreach($data['accessories'] as $a) {
			$a = App::ensure($a, ['line_id', 'product_id', 'quantity', 'unit_cost', 'base_unit_price', 'unit_price'], 0);

			if($a['quantity'] == 0) {
				// Quantity zero, delete accessory line if not new
				if($a['line_id'] !== 'new') {
					App::delete('project_line', $a['line_id']);

					// Also delete associated labour and subscription records
					$a_line_id = App::escape($a['line_id']);
					$sql->delete("DELETE FROM project_labour WHERE line_id = '$a_line_id';");
					$sql->delete("DELETE FROM project_subscription WHERE line_id = '$a_line_id';");
				}
			} else {
				if(!$a['product_id']) continue;

				if($a['line_id'] === 'new') {
					// Create accessory line
					$a_line_id = App::insert('project_line', [
						'project_id' => $project_id,
						'parent_id' => $id,
						'product_id' => $a['product_id'],
						'structure_id' => $record['structure_id'],
						'system_id' => $a['system_id'] ?: $record['system_id'],
						'is_system_fixed' => $a['system_id'] ? 1 : 0,
						'unit_cost' => $a['unit_cost'] ?: 0,
						'base_unit_price' => $a['base_unit_price'] ?: 0,
						'unit_price' => $a['unit_price'] ?: 0,
						'quantity' => $a['quantity']
					]);

					if($a_line_id) {
						$a_product_id = App::escape($a['product_id']);

						if(!$project->exclude_labour()) {
							$sql->insert(
								"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
								SELECT
									'$a_line_id' AS line_id,
									pl.labour_type_id,
									pl.labour_hours,
									plt.hourly_cost,
									plt.hourly_price,
									pl.id
								FROM product_labour AS pl
								JOIN product AS p ON p.id = pl.product_id
								JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
								JOIN product_labour_type AS plt ON plt.id = pl.labour_type_id
								WHERE pl.product_id = '$a_product_id' AND ((pl.seller_level = '$seller_level' AND pl.seller_id = '$seller_id') OR (pp.recommended_labour = 1 AND pl.seller_level = p.owner_level AND pl.seller_id = p.owner_id));
							");
						}

						if(!$project->exclude_subscriptions()) {
							$sql->insert(
								"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
								SELECT
									'$a_line_id' AS line_id,
									ps.subscription_type_id,
									ps.quantity,
									psp.unit_cost,
									psp.$subscription_price_field,
									pst.frequency,
									ps.id
								FROM product_subscription AS ps
								JOIN product AS p ON p.id = ps.product_id
								JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
								JOIN product_subscription_price AS psp ON psp.subscription_type_id = pst.id AND psp.seller_level = 'SI' AND psp.seller_id = '$seller_id'
								WHERE ps.product_id = '$a_product_id' AND ps.selection = 'fixed' AND ((ps.seller_level = '$seller_level' AND ps.seller_id = '$seller_id') OR (ps.seller_level = p.owner_level AND ps.seller_id = p.owner_id));
							");
						}

						// Apply line price adjustments
						$project->apply_price_adjustments($a_line_id);
					}
				} else {
					// Update accessory line
					App::update('project_line', $a['line_id'], [
						'structure_id' => $record['structure_id'],
						'system_id' => $a['system_id'] ?: $record['system_id'],
						'is_system_fixed' => $a['system_id'] ? 1 : 0,
						'unit_cost' => $a['unit_cost'] ?: 0,
						'base_unit_price' => $a['unit_price'] ?: 0,
						'unit_price' => $a['unit_price'] ?: 0,
						'quantity' => $a['quantity']
					]);

					// Apply line price adjustments
					$project->apply_price_adjustments($a['line_id']);
				}
			}
		}

		// Save bundle answers and products

		if($record['bundle_id']) {
			// Delete existing bundle products and answers
			$sql->delete("DELETE FROM project_labour WHERE line_id IN (SELECT id FROM project_line WHERE parent_id = '$id' AND is_bundle_item = 1);");
			$sql->delete("DELETE FROM project_subscription WHERE line_id IN (SELECT id FROM project_line WHERE parent_id = '$id' AND is_bundle_item = 1);");
			$sql->delete("DELETE FROM project_line WHERE parent_id = '$id' AND is_bundle_item = 1;");
			$sql->delete("DELETE FROM project_line_bundle_answers WHERE line_id = '$id';");

			// Save answers
			if(!$data['bundle_answers']) $data['bundle_answers'] = [];
			if(!$data['bundle_products']) $data['bundle_products'] = [];

			foreach($data['bundle_answers'] as $a) {
				$a = App::keep($a, ['question_id', 'answer']);
				$a = App::ensure($a, ['question_id', 'answer'], 0);
				$a['line_id'] = $id;
				App::insert_ignore('project_line_bundle_answers', $a);
			}

			foreach($data['bundle_products'] as $a) {
				$a = App::keep($a, ['product_id', 'quantity']);
				$a = App::ensure($a, ['product_id', 'quantity'], 0);
				$a['product_id'] = App::escape($a['product_id']);

				if(!$a['product_id'] || $a['quantity'] <= 0) continue;

				// Resolve product price
				$pr = $sql->query_row(
					"SELECT unit_cost, $price_field AS unit_price FROM product_price
					WHERE product_id = '$a[product_id]' AND seller_level = '$seller_level' AND seller_id = '$seller_id';
				", MySQL::QUERY_ASSOC) ?: [];
				$pr = App::ensure($pr, ['unit_cost', 'unit_price'], 0);

				$a_line_id = App::insert('project_line', [
					'project_id' => $project_id,
					'parent_id' => $id,
					'product_id' => $a['product_id'],
					'structure_id' => $record['structure_id'],
					'system_id' => $record['system_id'],
					'is_system_fixed' => 0,
					'unit_cost' => $pr['unit_cost'],
					'base_unit_price' => $pr['unit_price'],
					'unit_price' => $pr['unit_price'],
					'quantity' => $a['quantity'],
					'is_bundle_item' => 1
				]);

				if($a_line_id) {
					if(!$project->exclude_labour()) {
						$sql->insert(
							"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
							SELECT
								'$a_line_id' AS line_id,
								pl.labour_type_id,
								pl.labour_hours,
								plt.hourly_cost,
								plt.hourly_price,
								pl.id
							FROM product_labour AS pl
							JOIN product AS p ON p.id = pl.product_id
							JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
							JOIN product_labour_type AS plt ON plt.id = pl.labour_type_id
							WHERE pl.product_id = '$a[product_id]' AND ((pl.seller_level = '$seller_level' AND pl.seller_id = '$seller_id') OR (pp.recommended_labour = 1 AND pl.seller_level = p.owner_level AND pl.seller_id = p.owner_id));
						");
					}

					if(!$project->exclude_subscriptions()) {
						$sql->insert(
							"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
							SELECT
								'$a_line_id' AS line_id,
								ps.subscription_type_id,
								ps.quantity,
								psp.unit_cost,
								psp.$subscription_price_field,
								pst.frequency,
								ps.id
							FROM product_subscription AS ps
							JOIN product AS p ON p.id = ps.product_id
							JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
							JOIN product_subscription_price AS psp ON psp.subscription_type_id = pst.id AND psp.seller_level = 'SI' AND psp.seller_id = '$seller_id'
							WHERE ps.product_id = '$a[product_id]' AND ps.selection = 'fixed' AND ((ps.seller_level = '$seller_level' AND ps.seller_id = '$seller_id') OR (ps.seller_level = p.owner_level AND ps.seller_id = p.owner_id));
						");
					}

					// Apply line price adjustments
					$project->apply_price_adjustments($a_line_id);
				}
			}
		}

		// Apply line price adjustments to main project line
		$project->apply_price_adjustments($id);

		return $this->success($id);
	}

	public function delete_project_line() {
		$sql = App::sql();
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$line = App::select('project_line', $id);
		if(!$line) return $this->error('Product not found.');

		$project = new Project($line['project_id']);
		if(!$project->validate()) return $this->access_denied();

		// Delete all slots and extra labour/subscription records
		$sql->delete("DELETE FROM project_labour WHERE line_id IN (SELECT id FROM project_line WHERE id = '$id' OR parent_id = '$id');");
		$sql->delete("DELETE FROM project_subscription WHERE line_id IN (SELECT id FROM project_line WHERE id = '$id' OR parent_id = '$id');");
		$sql->delete("DELETE FROM project_line_slots WHERE line_id IN (SELECT id FROM project_line WHERE id = '$id' OR parent_id = '$id');");
		$sql->delete("DELETE FROM project_line_bundle_answers WHERE line_id IN (SELECT id FROM project_line WHERE id = '$id' OR parent_id = '$id');");

		// Delete line and all accessories
		$sql->delete("DELETE FROM project_line WHERE id = '$id' OR parent_id = '$id';");

		return $this->success();
	}

	public function get_project_lines() {
		$sql = App::sql();
		$data = App::json();

		$data = App::ensure($data, ['id'], '');
		$data = App::ensure($data, ['show_all_systems'], 0);
		$data = App::ensure($data, ['structure_list', 'module_list', 'system_list'], []);

		$id = $data['id'];
		$structure_list = $data['structure_list'] ?: [];
		$module_list = $data['module_list'] ?: [];
		$system_list = $data['system_list'];
		$show_all_systems = $data['show_all_systems'];

		$id = App::escape($id);
		$structure_list = App::escape($structure_list);
		$module_list = App::escape($module_list);
		$system_list = App::escape($system_list);

		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();
		$pricing = $project->can_show_pricing();

		$project_info = $sql->query_row(
			"SELECT
				p.id, p.description, p.price_tier, p.subscription_price_tier, p.stage, p.system_integrator_id,
				SUM(pl.quantity * (pl.unit_price + COALESCE(labour.unit_labour_price, 0))) AS total
			FROM project AS p
			LEFT JOIN project_line AS pl ON pl.project_id = p.id
			LEFT JOIN (
					SELECT
						sub_pl.id AS line_id,
						SUM(sub_plb.labour_hours * sub_plb.hourly_cost) AS unit_labour_cost,
						SUM(sub_plb.labour_hours * sub_plb.hourly_price) AS unit_labour_price
					FROM project_line AS sub_pl
					JOIN project_labour AS sub_plb ON sub_plb.line_id = sub_pl.id
					WHERE sub_pl.project_id = '$id'
					GROUP BY sub_pl.id

				) AS labour ON labour.line_id = pl.id
			WHERE p.id = '$id'
			GROUP BY p.id, p.description, p.price_tier, p.subscription_price_tier
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$project_info) return $this->error('Project not found.');
		if(!$pricing) $project_info['total'] = 0;

		$seller_level = 'SI';
		$seller_id = $project_info['system_integrator_id'];

		$discontinued = $sql->query(
			"SELECT DISTINCT p.id
			FROM project_line AS pl
			JOIN product AS p ON p.id = pl.product_id
			WHERE pl.project_id = '$id' AND p.discontinued = 1
			ORDER BY p.id;
		", MySQL::QUERY_ASSOC);
		$discontinued = array_map(function ($item) { return $item['id']; }, $discontinued ?: []);

		$modules = $sql->query(
			"SELECT
				DISTINCT m.id, m.description, m.icon, m.colour
			FROM project_system AS s
			JOIN project_system_assign AS psa ON psa.project_id = '$id' AND psa.system_id = s.id
			JOIN project_module AS m ON m.id = s.module_id
			ORDER BY m.display_order;
		", MySQL::QUERY_ASSOC);

		$systems = $sql->query(
			"SELECT
				s.id, s.description, s.module_id,
				SUM(pl.quantity * (pl.unit_price + COALESCE(labour.unit_labour_price, 0))) AS total
			FROM project_system AS s
			JOIN project_system_assign AS psp ON psp.project_id = '$id' AND psp.system_id = s.id
			LEFT JOIN project_line AS ppl ON ppl.project_id = psp.project_id AND ppl.system_id = s.id AND ppl.parent_id IS NULL
			LEFT JOIN project_line AS pl ON pl.id = ppl.id OR pl.parent_id = ppl.id
			LEFT JOIN (
					SELECT
						sub_pl.id AS line_id,
						SUM(sub_plb.labour_hours * sub_plb.hourly_cost) AS unit_labour_cost,
						SUM(sub_plb.labour_hours * sub_plb.hourly_price) AS unit_labour_price
					FROM project_line AS sub_pl
					JOIN project_labour AS sub_plb ON sub_plb.line_id = sub_pl.id
					WHERE sub_pl.project_id = '$id'
					GROUP BY sub_pl.id

				) AS labour ON labour.line_id = pl.id
			GROUP BY s.id, s.description, s.module_id
			ORDER BY s.description;
		", MySQL::QUERY_ASSOC) ?: [];

		if(!$pricing) {
			foreach($systems as &$item) {
				$item['total'] = 0;
			}
			unset($item);
		}

		// Expand selected modules into system list
		foreach($module_list as $mid) {
			$list = $sql->query(
				"SELECT s.id
				FROM project_system AS s
				JOIN project_system_assign AS psa ON psa.project_id = '$id' AND psa.system_id = s.id
				WHERE s.module_id = '$mid';
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($list as $s) {
				if(!in_array($s['id'], $system_list)) $system_list[] = $s['id'];
			}
		}

		$all_systems = false;
		if(count($system_list) === 0) {
			$all_systems = true;
			foreach($systems as $s) {
				$system_list[] = $s['id'];
			}
			if(count($system_list) === 0) $system_list[] = 0;
		}

		$structure = $sql->query(
			"SELECT
				s.id, s.type, s.description,
				f.id AS floor_id,
				SUM(pl.quantity * (pl.unit_price + COALESCE(labour.unit_labour_price, 0))) AS total
			FROM project_structure AS s
			LEFT JOIN project_structure AS f ON f.type = 'floor' AND (f.id = s.id OR f.id = s.parent_id)
			LEFT JOIN project_line AS pl ON pl.structure_id = s.id
			LEFT JOIN (
					SELECT
						sub_pl.id AS line_id,
						SUM(sub_plb.labour_hours * sub_plb.hourly_cost) AS unit_labour_cost,
						SUM(sub_plb.labour_hours * sub_plb.hourly_price) AS unit_labour_price
					FROM project_line AS sub_pl
					JOIN project_labour AS sub_plb ON sub_plb.line_id = sub_pl.id
					WHERE sub_pl.project_id = '$id'
					GROUP BY sub_pl.id

				) AS labour ON labour.line_id = pl.id
			WHERE s.project_id = '$id' AND s.type IN ('floor', 'area')
			GROUP BY s.id, s.type, s.description, f.id
			ORDER BY f.id, s.id;
		", MySQL::QUERY_ASSOC) ?: [];

		if(!$pricing) {
			foreach($structure as &$item) {
				$item['total'] = 0;
			}
			unset($item);
		}

		$condition = 'psp.system_id IN ('.implode(', ', $system_list).')';

		// See if items need to be on a single line
		// Single line is forced if:
		//  - Item has a placeholder in the BOM (selectable slots)
		//  - Item has any accessories

		$toolbox = $sql->query(
			"SELECT
				p.id, p.sku, p.model, p.short_description, p.image_id,
				pm.name AS manufacturer_name,
				ps.description AS system_description,
				psp.system_id,
				bnd.bundle_id,

				IF(bph.product_id IS NULL AND acc.product_id IS NULL AND p.is_bundle = 0, 0, 1) AS is_single

			FROM project_system_products AS psp
			JOIN product AS p ON p.id = psp.product_id
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
			LEFT JOIN (
				SELECT product_id, MIN(id) AS bundle_id
				FROM bundle
				WHERE is_latest = 1
				GROUP BY product_id
			) AS bnd ON bnd.product_id = p.id
			LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
			LEFT JOIN project_system AS ps ON ps.id = psp.system_id

			LEFT JOIN (
				SELECT DISTINCT bph_bom.parent_id AS product_id
				FROM product_bom AS bph_bom
				JOIN product AS bph_p ON bph_p.id = bph_bom.product_id AND bph_p.is_placeholder = 1
			) AS bph ON bph.product_id = p.id

			LEFT JOIN (
				SELECT product_id, COUNT(accessory_id) FROM product_accessories GROUP BY product_id
			) AS acc ON acc.product_id = p.id

			WHERE $condition
			ORDER BY psp.sort_order, ps.description, p.sku, p.model, p.short_description;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($toolbox as &$p) {
			$url = '';
			if($p['image_id']) {
				$uc = new UserContent($p['image_id']);
				$url = $uc->get_url();
			}
			$p['image_url'] = $url;
		}
		unset($p);

		// Make sure we only have areas on the structure list
		$new_structures = [];
		foreach($structure_list as $sid) {
			$sid = App::escape($sid);
			$areas = $sql->query("SELECT id FROM project_structure WHERE type = 'area' AND parent_id = '$sid';", MySQL::QUERY_ASSOC);
			if($areas) {
				foreach($areas as $a) {
					$new_structures[] = $a['id'];
				}
			} else {
				$new_structures[] = $sid;
			}
		}
		$structure_list = $new_structures;

		$condition = '';
		$extra_join = '';
		if(count($structure_list) > 0) {
			$condition .= ' AND ppl.structure_id IN ('.implode(', ', $structure_list).')';
		}
		if(!$show_all_systems && !$all_systems) {
			$sysids = implode(', ', $system_list);
			$condition .= " AND ppl.system_id IN ($sysids)";
		}

		$lines = $sql->query(
			"SELECT
				pl.*,

				p.sku,
				p.model,
				p.short_description,
				p.image_id,
				uc.path AS image_url,
				pm.name AS manufacturer_name,
				pu.name AS unit_name,

				ps.description AS system_description,
				pst.description AS structure_description,

				COALESCE(labour.unit_labour_hours, 0) AS unit_labour_hours,
				COALESCE(labour.unit_labour_cost, 0) AS unit_labour_cost,
				COALESCE(labour.unit_labour_price, 0) AS unit_labour_price,

				COALESCE(accl.accessories_labour_hours, 0) AS accessories_labour_hours,
				COALESCE(accl.accessories_labour_cost, 0) AS accessories_labour_cost,
				COALESCE(accl.accessories_labour_price, 0) AS accessories_labour_price,
				COALESCE(acc.accessories_count, 0) AS accessories_count,
				COALESCE(acc.accessories_cost, 0) AS accessories_cost,
				COALESCE(acc.accessories_price, 0) AS accessories_price,

				COALESCE(subcount.cnt, 0) AS subscription_count,
				COALESCE(pl.parent_id, pl.id) AS sort_parent

			FROM project_line AS pl
			JOIN project_line AS ppl ON ppl.id = COALESCE(pl.parent_id, pl.id)
			LEFT JOIN product AS p ON p.id = pl.product_id
			LEFT JOIN user_content AS uc ON uc.id = p.image_id
			LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
			LEFT JOIN project_structure AS pst ON pst.id = pl.structure_id
			LEFT JOIN project_system AS ps ON ps.id = pl.system_id
			LEFT JOIN product_unit AS pu ON pu.id = p.unit_id
			LEFT JOIN (
					SELECT
						sub_pl.id AS line_id,
						SUM(sub_plb.labour_hours) AS unit_labour_hours,
						SUM(sub_plb.labour_hours * sub_plb.hourly_cost) AS unit_labour_cost,
						SUM(sub_plb.labour_hours * sub_plb.hourly_price) AS unit_labour_price
					FROM project_line AS sub_pl
					JOIN project_labour AS sub_plb ON sub_plb.line_id = sub_pl.id
					WHERE sub_pl.project_id = '$id'
					GROUP BY sub_pl.id

				) AS labour ON labour.line_id = pl.id

			LEFT JOIN (
					SELECT
						sub_pl.parent_id AS line_id,
						SUM(sub_plb.labour_hours * sub_pl.quantity) AS accessories_labour_hours,
						SUM(sub_plb.labour_hours * sub_plb.hourly_cost * sub_pl.quantity) AS accessories_labour_cost,
						SUM(sub_plb.labour_hours * sub_plb.hourly_price * sub_pl.quantity) AS accessories_labour_price
					FROM project_line AS sub_pl
					JOIN project_labour AS sub_plb ON sub_plb.line_id = sub_pl.id
					WHERE sub_pl.project_id = '$id' AND sub_pl.parent_id IS NOT NULL
					GROUP BY sub_pl.parent_id

				) AS accl ON accl.line_id = pl.id

			LEFT JOIN (
					SELECT
						sub_pl.parent_id AS line_id,
						COUNT(sub_pl.id) AS accessories_count,
						SUM(sub_pl.unit_cost * sub_pl.quantity) AS accessories_cost,
						SUM(sub_pl.unit_price * sub_pl.quantity) AS accessories_price
					FROM project_line AS sub_pl
					WHERE sub_pl.project_id = '$id' AND sub_pl.parent_id IS NOT NULL
					GROUP BY sub_pl.parent_id

				) AS acc ON acc.line_id = pl.id

			LEFT JOIN (
					SELECT
						sub_pl.id,
						COUNT(*) AS cnt
					FROM project_line AS sub_pl
					JOIN project_subscription AS sub_ps ON sub_pl.id = sub_ps.line_id
					WHERE sub_pl.project_id = '$id'
					GROUP BY sub_pl.id

				) AS subcount ON subcount.id = pl.id

			$extra_join

			WHERE pl.project_id = '$id' $condition
			ORDER BY sort_parent, pl.id;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($lines as &$p) {
			$url = '';
			if($p['image_id']) {
				$url = UserContent::url_by_path($p['image_url']);
			}
			$p['image_url'] = $url;
		}
		unset($p);

		return $this->success([
			'project' => $project_info,
			'pricing' => $pricing,
			'modules' => $modules ?: [],
			'systems' => $systems ?: [],
			'structure' => $structure ?: [],
			'toolbox' => $toolbox,
			'lines' => $lines ?: [],
			'columns' => UIElement::by_name('project_editor')->get_columns(),
			'discontinued' => $discontinued ?: [],
			'exclude_labour' => $project->exclude_labour(),
			'exclude_subscriptions' => $project->exclude_subscriptions()
		]);
	}

	private function get_structure_parent_list($project_id, $type) {
		$project_id = App::escape($project_id);

		switch($type) {
			case 'area':
				return App::sql()->query("SELECT id, description FROM project_structure WHERE project_id = '$project_id' AND type = 'floor' ORDER BY description;") ?: [];
				break;

			default:
				return null;
		}
	}

	private function get_structure_child_count($structure_id) {
		$r = App::sql()->query_row("SELECT COUNT(id) AS cnt FROM project_structure WHERE parent_id = '$structure_id';");
		return $r ? ($r->cnt ?: 0) : 0;
	}

	private function get_structure_project_lines($structure_id) {
		$r = App::sql()->query(
			"SELECT pl.id FROM project_line AS pl
			LEFT JOIN project_structure AS s ON s.id = pl.structure_id
			WHERE (s.id = '$structure_id' OR s.parent_id = '$structure_id') AND pl.parent_id IS NULL;
		", MySQL::QUERY_ASSOC) ?: [];
		return array_map(function($item) {
			return $item['id'];
		}, $r);
	}

	private function get_structure_product_count($structure_id) {
		$r = App::sql()->query_row(
			"SELECT COUNT(pl.id) AS cnt FROM project_line AS pl
			LEFT JOIN project_structure AS s ON s.id = pl.structure_id
			WHERE s.id = '$structure_id' OR s.parent_id = '$structure_id';
		");
		return $r ? ($r->cnt ?: 0) : 0;
	}

	public function new_structure() {
		$data = App::json();
		$data = App::keep($data, ['project_id', 'type', 'parent_id']);
		$data = App::ensure($data, ['project_id', 'type', 'parent_id'], null);

		$project = new Project($data['project_id']);
		if(!$project->validate()) return $this->access_denied();

		$record = $data;
		$record['id'] = 'new';

		switch($data['type']) {
			case 'floor':
				$record['parent_id'] = null;
				break;

			case 'area':
				break;

			default:
				return $this->error('Invalid structure type.');
		}

		return $this->success([
			'details' => $record,
			'parents' => $this->get_structure_parent_list($data['project_id'], $data['type']),
			'children' => 0,
			'products' => 0
		]);
	}

	public function get_structure() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$record = App::select('project_structure', $id);
		if(!$record) return $this->access_denied();

		$project = new Project($record['project_id']);
		if(!$project->validate()) return $this->access_denied();

		return $this->success([
			'details' => $record,
			'parents' => $this->get_structure_parent_list($record['project_id'], $record['type']),
			'children' => $this->get_structure_child_count($id),
			'products' => $this->get_structure_product_count($id)
		]);
	}

	public function save_structure() {
		$data = App::json();
		$data = App::keep($data, ['id', 'project_id', 'type', 'parent_id', 'description']);
		$data = App::ensure($data, ['id', 'project_id', 'type', 'parent_id'], null);
		$data = App::ensure($data, ['description'], '');

		$id = $data['id'];
		unset($data['id']);

		if(!$id || !$data['project_id'] || !$data['type']) return $this->access_denied();
		if(!$data['description']) return $this->error('Please enter description.');

		$project = new Project($data['project_id']);
		if(!$project->validate()) return $this->access_denied();

		$id = App::upsert('project_structure', $id, $data);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function delete_structure() {
		$id = App::get('id', 0);
		if(!$id) return $this->access_denied();

		$record = App::select('project_structure', $id);
		if(!$record) $this->access_denied();

		$project = new Project($record['project_id']);
		if(!$project->validate()) return $this->access_denied();

		$sql = App::sql();

		// Delete project lines
		$list = $this->get_structure_project_lines($id);
		foreach($list as $line_id) {
			// Delete all slots and extra labour/subscription records
			$sql->delete("DELETE FROM project_labour WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");
			$sql->delete("DELETE FROM project_subscription WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");
			$sql->delete("DELETE FROM project_line_slots WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");
			$sql->delete("DELETE FROM project_line_bundle_answers WHERE line_id IN (SELECT id FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id');");

			// Delete line and all accessories
			$sql->delete("DELETE FROM project_line WHERE id = '$line_id' OR parent_id = '$line_id';");
		}

		// Delete areas
		$sql->delete("DELETE FROM project_structure WHERE id = '$id' OR parent_id = '$id';");

		return $this->success();
	}

	public function get_project_summary() {
		$sql = App::sql();
		$user = App::user();

		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		$project_info = $sql->query_row(
			"SELECT
				p.id,
				p.description,
				p.stage,
				c.name AS customer_name
			FROM project AS p
			JOIN sales_customer AS c ON c.id = p.customer_id
			WHERE p.id = '$id';
		", MySQL::QUERY_ASSOC);

		$equipment = $sql->query_row(
			"SELECT
				SUM(unit_price * quantity) AS total_price,
				SUM(unit_cost * quantity) AS total_cost
			FROM project_line
			WHERE project_id = '$id';
		", MySQL::QUERY_ASSOC);

		$labour = $sql->query_row(
			"SELECT
				SUM(ln.quantity * lab.labour_hours) AS total_hours,
				SUM(ln.quantity * lab.labour_hours * lab.hourly_price) AS total_price,
				SUM(ln.quantity * lab.labour_hours * lab.hourly_cost) AS total_cost
			FROM project_line AS ln
			JOIN project_labour AS lab ON lab.line_id = ln.id
			WHERE ln.project_id = '$id';
		", MySQL::QUERY_ASSOC);

		$labour_types = $sql->query(
			"SELECT
				tp.id, tp.description,
				cat.description AS category_description,
				SUM(ln.quantity * lab.labour_hours) AS total_hours,
				SUM(ln.quantity * lab.labour_hours * lab.hourly_price) AS total_price
			FROM project_line AS ln
			JOIN project_labour AS lab ON lab.line_id = ln.id
			JOIN product_labour_type AS tp ON tp.id = lab.labour_type_id
			JOIN product_labour_category AS cat ON cat.id = tp.category_id
			WHERE ln.project_id = '$id'
			GROUP BY tp.id, tp.description
			ORDER BY cat.description, tp.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$labour_categories = $sql->query(
			"SELECT
				cat.id, cat.description,
				SUM(ln.quantity * lab.labour_hours) AS total_hours,
				SUM(ln.quantity * lab.labour_hours * lab.hourly_price) AS total_price
			FROM project_line AS ln
			JOIN project_labour AS lab ON lab.line_id = ln.id
			JOIN product_labour_type AS tp ON tp.id = lab.labour_type_id
			JOIN product_labour_category AS cat ON cat.id = tp.category_id
			WHERE ln.project_id = '$id'
			GROUP BY cat.id, cat.description
			ORDER BY cat.description, tp.description;
		", MySQL::QUERY_ASSOC) ?: [];

		if(!$project->can_show_pricing()) {
			if($equipment) {
				$equipment['total_cost'] = 0;
				$equipment['total_price'] = 0;
			}

			if($labour) {
				$labour['total_cost'] = 0;
				$labour['total_price'] = 0;
			}

			foreach($labour_types as &$item) {
				$item['total_price'] = 0;
			}
			unset($item);

			foreach($labour_categories as &$item) {
				$item['total_price'] = 0;
			}
			unset($item);
		}

		// Calculate subscriptions

		$subscription_monthly = App::sql()->query(
			"SELECT
				pst.id,
				pst.description,
				SUM(pl.quantity * ps.quantity * ps.unit_cost) AS cost,
				SUM(pl.quantity * ps.quantity * ps.unit_price) AS subtotal,
				SUM(pl.quantity * ps.quantity) AS quantity
			FROM project_line AS pl
			JOIN project_subscription AS ps ON ps.line_id = pl.id
			JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
			WHERE pl.project_id = '$id' AND ps.frequency = 'monthly'
			GROUP BY pst.id, pst.description
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY pst.description;
		", MySQL::QUERY_ASSOC) ?: [];

		if(!$project->can_show_pricing()) {
			foreach($subscription_monthly as &$item) {
				$item['cost'] = 0;
				$item['subtotal'] = 0;
			}
			unset($item);
		}

		$subscription_monthly_subtotal = 0;
		$subscription_monthly_cost = 0;
		foreach($subscription_monthly as $l) {
			$subscription_monthly_subtotal += $l['subtotal'];
			$subscription_monthly_cost += $l['cost'];
		}

		$subscription_annual = App::sql()->query(
			"SELECT
				pst.id,
				pst.description,
				SUM(pl.quantity * ps.quantity * ps.unit_cost) AS cost,
				SUM(pl.quantity * ps.quantity * ps.unit_price) AS subtotal,
				SUM(pl.quantity * ps.quantity) AS quantity
			FROM project_line AS pl
			JOIN project_subscription AS ps ON ps.line_id = pl.id
			JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
			WHERE pl.project_id = '$id' AND ps.frequency = 'annual'
			GROUP BY pst.id, pst.description
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY pst.description;
		", MySQL::QUERY_ASSOC) ?: [];

		if(!$project->can_show_pricing()) {
			foreach($subscription_annual as &$item) {
				$item['cost'] = 0;
				$item['subtotal'] = 0;
			}
			unset($item);
		}

		$subscription_annual_subtotal = 0;
		$subscription_annual_cost = 0;
		foreach($subscription_annual as $l) {
			$subscription_annual_subtotal += $l['subtotal'];
			$subscription_annual_cost += $l['cost'];
		}

		$subscriptions = [];
		if($subscription_monthly) {
			$subscriptions[] = [
				'frequency' => 'Monthly',
				'items' => $subscription_monthly ?: [],
				'cost' => $subscription_monthly_cost,
				'total' => $subscription_monthly_subtotal
			];
		}
		if($subscription_annual) {
			$subscriptions[] = [
				'frequency' => 'Annual',
				'items' => $subscription_annual ?: [],
				'cost' => $subscription_annual_cost,
				'total' => $subscription_annual_subtotal
			];
		}

		$result = [
			'project' => $project_info,
			'equipment' => $equipment,
			'labour' => $labour,
			'labour_types' => $labour_types ?: [],
			'labour_categories' => $labour_categories ?: [],
			'subscriptions' => $subscriptions,
			'pricing' => $project->can_show_pricing()
		];

		return $this->success($result);
	}

	public function get_project_cost_summary() {
		$sql = App::sql();
		$user = App::user();

		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate() || !$project->can_show_pricing()) return $this->access_denied();

		$project_info = $sql->query_row(
			"SELECT
				p.id,
				p.description,
				p.stage,
				p.project_no,
				c.name AS customer_name
			FROM project AS p
			JOIN sales_customer AS c ON c.id = p.customer_id
			WHERE p.id = '$id';
		", MySQL::QUERY_ASSOC);

		$systems = $sql->query(
			"SELECT
				sys.id, sys.description,
				SUM(ln.quantity * ln.unit_cost) AS equipment_cost,
				SUM(ln.quantity * ln.unit_price) AS equipment_price,
				SUM(COALESCE(lab.labour_cost, 0)) AS labour_cost,
				SUM(COALESCE(lab.labour_price, 0)) AS labour_price,
				SUM(COALESCE(lab.labour_adjustment_cost, 0)) AS labour_adjustment_cost,
				SUM(COALESCE(lab.labour_adjustment_price, 0)) AS labour_adjustment_price,
				SUM(ln.quantity * ln.unit_cost + COALESCE(lab.labour_cost, 0) + COALESCE(lab.labour_adjustment_cost, 0)) AS project_cost,
				SUM(ln.quantity * ln.unit_price + COALESCE(lab.labour_price, 0) + COALESCE(lab.labour_adjustment_price, 0)) AS project_price
			FROM project_line AS ln
			JOIN project_system AS sys ON sys.id = ln.system_id
			LEFT JOIN (
				SELECT
					lab_ln.id,
					SUM(IF(product_labour_id IS NOT NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_cost, 0)) AS labour_cost,
					SUM(IF(product_labour_id IS NOT NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_price, 0)) AS labour_price,
					SUM(IF(product_labour_id IS NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_cost, 0)) AS labour_adjustment_cost,
					SUM(IF(product_labour_id IS NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_price, 0)) AS labour_adjustment_price
				FROM project_line AS lab_ln
				JOIN project_labour AS lab_lab ON lab_lab.line_id = lab_ln.id
				WHERE lab_ln.project_id = '$id'
				GROUP BY lab_ln.id
			) AS lab ON lab.id = ln.id

			WHERE ln.project_id = '$id'
			GROUP BY sys.id, sys.description
			ORDER BY sys.description;
		", MySQL::QUERY_ASSOC);

		$total = $sql->query_row(
			"SELECT
				SUM(ln.quantity * ln.unit_cost) AS equipment_cost,
				SUM(ln.quantity * ln.unit_price) AS equipment_price,
				SUM(COALESCE(lab.labour_cost, 0)) AS labour_cost,
				SUM(COALESCE(lab.labour_price, 0)) AS labour_price,
				SUM(COALESCE(lab.labour_adjustment_cost, 0)) AS labour_adjustment_cost,
				SUM(COALESCE(lab.labour_adjustment_price, 0)) AS labour_adjustment_price,
				SUM(ln.quantity * ln.unit_cost + COALESCE(lab.labour_cost, 0) + COALESCE(lab.labour_adjustment_cost, 0)) AS project_cost,
				SUM(ln.quantity * ln.unit_price + COALESCE(lab.labour_price, 0) + COALESCE(lab.labour_adjustment_price, 0)) AS project_price
			FROM project_line AS ln
			JOIN project_system AS sys ON sys.id = ln.system_id
			LEFT JOIN (
				SELECT
					lab_ln.id,
					SUM(IF(product_labour_id IS NOT NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_cost, 0)) AS labour_cost,
					SUM(IF(product_labour_id IS NOT NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_price, 0)) AS labour_price,
					SUM(IF(product_labour_id IS NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_cost, 0)) AS labour_adjustment_cost,
					SUM(IF(product_labour_id IS NULL, lab_ln.quantity * lab_lab.labour_hours * lab_lab.hourly_price, 0)) AS labour_adjustment_price
				FROM project_line AS lab_ln
				JOIN project_labour AS lab_lab ON lab_lab.line_id = lab_ln.id
				WHERE lab_ln.project_id = '$id'
				GROUP BY lab_ln.id
			) AS lab ON lab.id = ln.id

			WHERE ln.project_id = '$id';
		", MySQL::QUERY_ASSOC);

		$result = [
			'project' => $project_info,
			'systems' => $systems ?: [],
			'total' => $total ?: [
				'equipment_cost' => 0,
				'equipment_price' => 0,
				'labour_cost' => 0,
				'labour_price' => 0,
				'labour_adjustment_cost' => 0,
				'labour_adjustment_price' => 0
			],
			'exclude_labour' => $project->exclude_labour(),
			'exclude_subscriptions' => $project->exclude_subscriptions()
		];

		return $this->success($result);
	}

	public function get_project_po_request() {
		$sql = App::sql();
		$user = App::user();

		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate() || !$project->can_show_pricing()) return $this->access_denied();

		$project_info = $sql->query_row(
			"SELECT
				p.id,
				p.description,
				p.stage,
				p.project_no,
				p.system_integrator_id,
				c.name AS customer_name
			FROM project AS p
			JOIN sales_customer AS c ON c.id = p.customer_id
			WHERE p.id = '$id';
		", MySQL::QUERY_ASSOC);

		$suppliers = $sql->query(
			"SELECT
				id, name
			FROM product_entity
			WHERE id IN (
				SELECT DISTINCT ps.supplier_id
				FROM project_line AS ln

				JOIN (
					SELECT
						ps_p.id,
						IF(ps_p.owner_level = 'SI' AND ps_p.owner_id = '$project_info[system_integrator_id]', ps_s.supplier_id, ps_o.id) AS supplier_id
					FROM product AS ps_p
					LEFT JOIN product_suppliers AS ps_s ON ps_s.product_id = ps_p.id AND ps_s.is_primary = 1
					LEFT JOIN product_entity AS ps_o ON ps_p.owner_level = ps_o.owner_level AND ps_p.owner_id = ps_o.owner_id AND ps_o.is_owner = 1
				) AS ps ON ps.id = ln.product_id

				WHERE ln.project_id = '$id'

			)
			ORDER BY name;
		", MySQL::QUERY_ASSOC);

		$products = $sql->query(
			"SELECT
				p.id, p.model,
				COALESCE(ps.sku, p.sku) AS sku,
				m.name AS manufacturer_name, ps.supplier_id,
				p.short_description AS description,
				ln.unit_cost,
				SUM(ln.quantity) AS quantity,
				SUM(ln.quantity * ln.unit_cost) AS total
			FROM project_line AS ln
			JOIN product AS p ON p.id = ln.product_id

			LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id

			LEFT JOIN (
				SELECT
					ps_p.id,
					IF(ps_p.owner_level = 'SI' AND ps_p.owner_id = '$project_info[system_integrator_id]', ps_s.supplier_id, ps_o.id) AS supplier_id,
					IF(ps_p.owner_level = 'SI' AND ps_p.owner_id = '$project_info[system_integrator_id]', ps_s.sku, NULL) AS sku
				FROM product AS ps_p
				LEFT JOIN product_suppliers AS ps_s ON ps_s.product_id = ps_p.id AND ps_s.is_primary = 1
				LEFT JOIN product_entity AS ps_o ON ps_p.owner_level = ps_o.owner_level AND ps_p.owner_id = ps_o.owner_id AND ps_o.is_owner = 1
			) AS ps ON ps.id = ln.product_id

			WHERE ln.project_id = '$id'
			GROUP BY p.id, p.model, COALESCE(ps.sku, p.sku), m.name, ps.supplier_id, p.short_description, ln.unit_cost
			ORDER BY sku;
		", MySQL::QUERY_ASSOC);

		$result = [
			'project' => $project_info,
			'suppliers' => $suppliers ?: [],
			'products' => $products ?: []
		];

		return $this->success($result);
	}

	public function get_project_itemised_quotation() {
		$sql = App::sql();
		$user = App::user();

		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate() || !$project->can_show_pricing()) return $this->access_denied();

		$customer_id = $project->info['customer_id'];

		$customer = $sql->query_row(
			"SELECT
				name,
				address_line_1,
				address_line_2,
				address_line_3,
				posttown,
				postcode
			FROM sales_customer
			WHERE id = '$customer_id';
		", MySQL::QUERY_ASSOC);

		$products = $sql->query(
			"SELECT
				p.id, p.manufacturer_id, p.model, p.sku, p.image_id,
				COALESCE(p.short_description, ln.description) AS description,
				ln.unit_price,
				SUM(ln.quantity) AS quantity,
				SUM(ln.quantity * ln.unit_price) AS total
			FROM project_line AS ln
			LEFT JOIN product AS p ON p.id = ln.product_id
			WHERE ln.project_id = '$id'
			GROUP BY p.id, p.manufacturer_id, p.model, p.sku, p.image_id, p.short_description, ln.unit_price
			ORDER BY p.sku;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($products as &$p) {
			$url = '';
			if($p['image_id']) {
				$uc = new UserContent($p['image_id']);
				$url = $uc->get_url();
			}
			$p['image_url'] = $url;
		}
		unset($p);

		$result = [
			'project' => $project->info,
			'customer' => $customer,
			'products' => $products
		];

		return $this->success($result);
	}

	public function get_project_proposal() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$project = new Project($id);
		if(!$project->validate() || !$project->can_show_pricing()) return $this->access_denied();

		$result = $project->get_proposal();
		$result['print_url'] = APP_URL.'/ajax/get/print_project_proposal?id='.$id;
		$result['download_url'] = APP_URL.'/ajax/get/print_project_proposal?id='.$id.'&download=1';
		$result['print_simple_url'] = APP_URL.'/ajax/get/print_project_quotation?id='.$id;
		$result['download_simple_url'] = APP_URL.'/ajax/get/print_project_quotation?id='.$id.'&download=1';
		$result['print_itemised_url'] = APP_URL.'/ajax/get/print_project_quotation?variant=itemised&id='.$id;
		$result['download_itemised_url'] = APP_URL.'/ajax/get/print_project_quotation?variant=itemised&id='.$id.'&download=1';
		$result['print_area_url'] = APP_URL.'/ajax/get/print_project_area_summary?id='.$id;
		$result['download_area_url'] = APP_URL.'/ajax/get/print_project_area_summary?id='.$id.'&download=1';
		
		return $this->success($result);
	}

	public function save_project_proposal() {
		$data = App::json();

		$id = App::escape($data['project_id']);
		if(!$id) return $this->access_denied();

		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		$proposal = $data['proposal'];
		$proposal = App::keep($proposal, ['text_introduction', 'text_solution', 'text_payment', 'text_payback', 'text_terms', 'text_summary', 'text_quotation', 'text_subscriptions', 'show_quantities', 'show_subtotals', 'show_acceptance', 'preferred_payment']);
		$proposal = App::ensure($proposal, ['text_introduction', 'text_solution', 'text_payment', 'text_payback', 'text_terms', 'text_summary', 'text_quotation', 'text_subscriptions'], '');
		$proposal = App::ensure($proposal, ['show_quantities', 'show_subtotals'], 0);
		$proposal['project_id'] = $id;

		App::sql()->delete("DELETE FROM project_proposal WHERE project_id = '$id';");
		App::insert('project_proposal', $proposal);

		if(!$data['modules']) $data['modules'] = [];

		foreach($data['modules'] as $module) {
			$module_id = App::escape($module['id']);
			$module = App::keep($module, ['text_features']);
			$module = App::ensure($module, ['text_features'], '');
			$module['project_id'] = $id;
			$module['module_id'] = $module_id;

			App::sql()->delete("DELETE FROM project_proposal_module WHERE project_id = '$id' AND module_id = '$module_id';");
			App::insert('project_proposal_module', $module);
		}

		return $this->success();
	}

	public function get_unsynced_project_lines() {
		$sql = App::sql();

		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		$price_field = 'retail_price';
		switch($project->info['price_tier']) {
			case 'cost': $price_field = 'unit_cost'; break;
			case 'distribution': $price_field = 'distribution_price'; break;
			case 'reseller': $price_field = 'reseller_price'; break;
			case 'trade': $price_field = 'trade_price'; break;
			case 'retail': $price_field = 'retail_price'; break;
		}

		$subscription_price_field = 'retail_price';
		switch($project->info['subscription_price_tier']) {
			case 'cost': $subscription_price_field = 'unit_cost'; break;
			case 'distribution': $subscription_price_field = 'distribution_price'; break;
			case 'reseller': $subscription_price_field = 'reseller_price'; break;
			case 'trade': $subscription_price_field = 'trade_price'; break;
			case 'retail': $subscription_price_field = 'retail_price'; break;
		}

		$seller_level = 'SI';
		$seller_id = $project->info['system_integrator_id'];

		$result_price = [];
		$result_labour = [];
		$result_subscription = [];

		// Check lines for product price/cost changes

		$lines = $sql->query(
			"SELECT ln.id, ln.parent_id
			FROM project_line AS ln
			JOIN product AS p ON p.id = ln.product_id
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
			WHERE ln.project_id = '$id' AND (ln.unit_cost <> pp.unit_cost OR ln.base_unit_price <> pp.$price_field);
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($lines as $l) {
			if(!in_array($l['id'], $result_price)) $result_price[] = $l['id'];
			if($l['parent_id'] !== null && !in_array($l['parent_id'], $result_price)) $result_price[] = $l['parent_id'];
		}

		if(!$project->exclude_labour()) {
			// Check labour prices

			$lines = $sql->query(
				"SELECT DISTINCT ln.id, ln.parent_id
				FROM project_line AS ln
				JOIN project_labour AS lab ON lab.line_id = ln.id
				JOIN product_labour_type AS lt ON lt.id = lab.labour_type_id
				WHERE ln.project_id = '$id' AND (lab.hourly_cost <> lt.hourly_cost OR lab.hourly_price <> lt.hourly_price);
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($lines as $l) {
				if(!in_array($l['id'], $result_labour)) $result_labour[] = $l['id'];
				if($l['parent_id'] !== null && !in_array($l['parent_id'], $result_labour)) $result_labour[] = $l['parent_id'];
			}

			// Check labour hours and type of automatic records. Also catches deleted product labour records.

			$lines = $sql->query(
				"SELECT DISTINCT ln.id, ln.parent_id
				FROM project_line AS ln
				JOIN product AS p ON p.id = ln.product_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				JOIN project_labour AS lab ON lab.line_id = ln.id AND lab.product_labour_id IS NOT NULL
				LEFT JOIN product_labour AS plab ON plab.id = lab.product_labour_id AND ((plab.seller_level = '$seller_level' AND plab.seller_id = '$seller_id') OR (pp.recommended_labour = 1 AND plab.seller_level = p.owner_level AND plab.seller_id = p.owner_id))
				WHERE ln.project_id = '$id' AND (plab.id IS NULL OR lab.labour_hours <> plab.labour_hours OR lab.labour_type_id <> plab.labour_type_id);
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($lines as $l) {
				if(!in_array($l['id'], $result_labour)) $result_labour[] = $l['id'];
				if($l['parent_id'] !== null && !in_array($l['parent_id'], $result_labour)) $result_labour[] = $l['parent_id'];
			}

			// Look for new product labour records that haven't been added to the project yet

			$lines = $sql->query(
				"SELECT DISTINCT ln.id, ln.parent_id
				FROM project_line AS ln
				JOIN product AS p ON p.id = ln.product_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				JOIN product_labour AS plab ON plab.product_id = ln.product_id AND ((plab.seller_level = '$seller_level' AND plab.seller_id = '$seller_id') OR (pp.recommended_labour = 1 AND plab.seller_level = p.owner_level AND plab.seller_id = p.owner_id))
				LEFT JOIN project_labour AS lab ON lab.line_id = ln.id AND lab.product_labour_id = plab.id
				WHERE ln.project_id = '$id' AND lab.id IS NULL;
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($lines as $l) {
				if(!in_array($l['id'], $result_labour)) $result_labour[] = $l['id'];
				if($l['parent_id'] !== null && !in_array($l['parent_id'], $result_labour)) $result_labour[] = $l['parent_id'];
			}
		}

		if(!$project->exclude_subscriptions()) {
			// Check subscription prices and frequencies

			$lines = $sql->query(
				"SELECT DISTINCT ln.id, ln.parent_id
				FROM project_line AS ln
				JOIN project_subscription AS sub ON sub.line_id = ln.id
				JOIN product_subscription_type AS st ON st.id = sub.subscription_type_id
				JOIN product_subscription_price AS psp ON psp.subscription_type_id = st.id AND psp.seller_level = '$seller_level' AND psp.seller_id = '$seller_id'
				WHERE ln.project_id = '$id' AND (sub.unit_cost <> psp.unit_cost OR sub.unit_price <> psp.$subscription_price_field OR sub.frequency <> st.frequency);
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($lines as $l) {
				if(!in_array($l['id'], $result_subscription)) $result_subscription[] = $l['id'];
				if($l['parent_id'] !== null && !in_array($l['parent_id'], $result_subscription)) $result_subscription[] = $l['parent_id'];
			}

			// Check subscription quantities

			$lines = $sql->query(
				"SELECT DISTINCT ln.id, ln.parent_id
				FROM project_line AS ln
				JOIN product AS p ON p.id = ln.product_id
				JOIN project_subscription AS sub ON sub.line_id = ln.id AND sub.product_subscription_id IS NOT NULL
				LEFT JOIN product_subscription AS psub ON psub.id = sub.product_subscription_id AND ((psub.seller_level = '$seller_level' AND psub.seller_id = '$seller_id') OR (psub.seller_level = p.owner_level AND psub.seller_id = p.owner_id))
				WHERE ln.project_id = '$id' AND (psub.id IS NULL OR sub.quantity <> psub.quantity OR sub.subscription_type_id <> psub.subscription_type_id);
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($lines as $l) {
				if(!in_array($l['id'], $result_subscription)) $result_subscription[] = $l['id'];
				if($l['parent_id'] !== null && !in_array($l['parent_id'], $result_subscription)) $result_subscription[] = $l['parent_id'];
			}

			// Check fixed records that haven't been added

			$lines = $sql->query(
				"SELECT DISTINCT ln.id, ln.parent_id
				FROM project_line AS ln
				JOIN product AS p ON p.id = ln.product_id
				JOIN product_subscription AS psub ON psub.product_id = ln.product_id AND psub.selection = 'fixed' AND ((psub.seller_level = '$seller_level' AND psub.seller_id = '$seller_id') OR (psub.seller_level = p.owner_level AND psub.seller_id = p.owner_id))
				LEFT JOIN project_subscription AS sub ON sub.line_id = ln.id AND sub.product_subscription_id = psub.id
				WHERE ln.project_id = '$id' AND sub.id IS NULL;
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($lines as $l) {
				if(!in_array($l['id'], $result_subscription)) $result_subscription[] = $l['id'];
				if($l['parent_id'] !== null && !in_array($l['parent_id'], $result_subscription)) $result_subscription[] = $l['parent_id'];
			}
		}

		return $this->success([
			'price' => $result_price,
			'labour' => $result_labour,
			'subscription' => $result_subscription
		]);
	}

	public function sync_project_lines() {
		$sql = App::sql();

		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		$price_field = 'retail_price';
		switch($project->info['price_tier']) {
			case 'cost': $price_field = 'unit_cost'; break;
			case 'distribution': $price_field = 'distribution_price'; break;
			case 'reseller': $price_field = 'reseller_price'; break;
			case 'trade': $price_field = 'trade_price'; break;
			case 'retail': $price_field = 'retail_price'; break;
		}

		$subscription_price_field = 'retail_price';
		switch($project->info['subscription_price_tier']) {
			case 'cost': $subscription_price_field = 'unit_cost'; break;
			case 'distribution': $subscription_price_field = 'distribution_price'; break;
			case 'reseller': $subscription_price_field = 'reseller_price'; break;
			case 'trade': $subscription_price_field = 'trade_price'; break;
			case 'retail': $subscription_price_field = 'retail_price'; break;
		}

		$seller_level = 'SI';
		$seller_id = $project->info['system_integrator_id'];

		// Update lines with product price/cost changes

		$sql->update(
			"UPDATE project_line AS ln
			JOIN product AS p ON p.id = ln.product_id
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
			SET ln.unit_cost = pp.unit_cost, ln.base_unit_price = pp.$price_field
			WHERE ln.project_id = '$id' AND (ln.unit_cost <> pp.unit_cost OR ln.base_unit_price <> pp.$price_field);
		");

		if(!$project->exclude_labour()) {
			// Remove deleted product labour records

			$sql->delete(
				"DELETE lab
				FROM project_labour AS lab
				JOIN project_line AS ln ON ln.id = lab.line_id
				JOIN product AS p ON p.id = ln.product_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				LEFT JOIN product_labour AS plab ON plab.id = lab.product_labour_id AND ((plab.seller_level = '$seller_level' AND plab.seller_id = '$seller_id') OR (pp.recommended_labour = 1 AND plab.seller_level = p.owner_level AND plab.seller_id = p.owner_id))
				WHERE ln.project_id = '$id' AND lab.product_labour_id IS NOT NULL AND plab.id IS NULL;
			");

			// Update labour hours and type of automatic records.

			$lines = $sql->update(
				"UPDATE project_line AS ln
				JOIN project_labour AS lab ON lab.line_id = ln.id AND lab.product_labour_id IS NOT NULL
				JOIN product_labour AS plab ON plab.id = lab.product_labour_id
				SET lab.labour_hours = plab.labour_hours, lab.labour_type_id = plab.labour_type_id
				WHERE ln.project_id = '$id' AND (lab.labour_hours <> plab.labour_hours OR lab.labour_type_id <> plab.labour_type_id);
			");

			// Update labour prices

			$sql->update(
				"UPDATE project_line AS ln
				JOIN project_labour AS lab ON lab.line_id = ln.id
				JOIN product_labour_type AS lt ON lt.id = lab.labour_type_id
				SET lab.hourly_cost = lt.hourly_cost, lab.hourly_price = lt.hourly_price
				WHERE ln.project_id = '$id' AND (lab.hourly_cost <> lt.hourly_cost OR lab.hourly_price <> lt.hourly_price);
			");

			// Insert new product labour records that haven't been added to the project yet

			$lines = $sql->insert(
				"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
				SELECT ln.id, plab.labour_type_id, plab.labour_hours, plt.hourly_cost, plt.hourly_price, plab.id
				FROM project_line AS ln
				JOIN product AS p ON p.id = ln.product_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				JOIN product_labour AS plab ON plab.product_id = ln.product_id AND ((plab.seller_level = '$seller_level' AND plab.seller_id = '$seller_id') OR (pp.recommended_labour = 1 AND plab.seller_level = p.owner_level AND plab.seller_id = p.owner_id))
				JOIN product_labour_type AS plt ON plt.id = plab.labour_type_id
				LEFT JOIN project_labour AS lab ON lab.line_id = ln.id AND lab.product_labour_id = plab.id
				WHERE ln.project_id = '$id' AND lab.id IS NULL
				ORDER BY ln.id, plab.id;
			");
		}

		if(!$project->exclude_subscriptions()) {
			// Remove deleted product subscription records

			$sql->delete(
				"DELETE sub
				FROM project_subscription AS sub
				JOIN project_line AS ln ON ln.id = sub.line_id
				JOIN product AS p ON p.id = ln.product_id
				LEFT JOIN product_subscription AS psub ON psub.id = sub.product_subscription_id AND ((psub.seller_level = '$seller_level' AND psub.seller_id = '$seller_id') OR (psub.seller_level = p.owner_level AND psub.seller_id = p.owner_id))
				WHERE ln.project_id = '$id' AND sub.product_subscription_id IS NOT NULL AND psub.id IS NULL;
			");

			// Update quantity and type of automatic records.

			$lines = $sql->update(
				"UPDATE project_line AS ln
				JOIN project_subscription AS sub ON sub.line_id = ln.id AND sub.product_subscription_id IS NOT NULL
				JOIN product_subscription AS psub ON psub.id = sub.product_subscription_id
				SET sub.quantity = psub.quantity, sub.subscription_type_id = psub.subscription_type_id
				WHERE ln.project_id = '$id' AND (sub.quantity <> psub.quantity OR sub.subscription_type_id <> psub.subscription_type_id);
			");

			// Update subscription prices and frequencies

			$sql->update(
				"UPDATE project_line AS ln
				JOIN project_subscription AS sub ON sub.line_id = ln.id
				JOIN product_subscription_type AS st ON st.id = sub.subscription_type_id
				JOIN product_subscription_price AS psp ON psp.subscription_type_id = st.id AND psp.seller_level = '$seller_level' AND psp.seller_id = '$seller_id'
				SET sub.unit_cost = psp.unit_cost, sub.unit_price = psp.$subscription_price_field, sub.frequency = st.frequency
				WHERE ln.project_id = '$id' AND (sub.unit_cost <> psp.unit_cost OR sub.unit_price <> psp.$subscription_price_field OR sub.frequency <> st.frequency);
			");

			// Insert new fixed product subscription records that haven't been added to the project yet

			$lines = $sql->insert(
				"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
				SELECT ln.id, psub.subscription_type_id, psub.quantity, psp.unit_cost, psp.$subscription_price_field, pst.frequency, psub.id
				FROM project_line AS ln
				JOIN product AS p ON p.id = ln.product_id
				JOIN product_subscription AS psub ON psub.product_id = ln.product_id AND psub.selection = 'fixed' AND ((psub.seller_level = '$seller_level' AND psub.seller_id = '$seller_id') OR (psub.seller_level = p.owner_level AND psub.seller_id = p.owner_id))
				JOIN product_subscription_type AS pst ON pst.id = psub.subscription_type_id
				JOIN product_subscription_price AS psp ON psp.subscription_type_id = pst.id AND psp.seller_level = '$seller_level' AND psp.seller_id = '$seller_id'
				LEFT JOIN project_subscription AS sub ON sub.line_id = ln.id AND sub.product_subscription_id = psub.id
				WHERE ln.project_id = '$id' AND sub.id IS NULL
				ORDER BY ln.id, psub.id;
			");
		}

		// Apply line price adjustments
		$project->apply_price_adjustments();

		return $this->success();
	}

	public function get_project_stage_history() {
		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		$result = App::sql()->query(
			"SELECT
				h.*,
				u.name AS user_name,
				u.email_addr AS user_email
			FROM project_stage_history AS h
			JOIN userdb AS u ON u.id = h.user_id
			WHERE h.project_id = '$id'
			ORDER BY h.datetime DESC;
		", MySQL::QUERY_ASSOC);

		return $this->success($result ?: []);
	}

	public function clone_project() {
		$sql = App::sql();
		$data = App::json();

		$data = App::keep($data, ['id', 'description', 'clone']);
		$data = App::ensure($data, ['id', 'description', 'clone'], '');

		// Check if ID is set
		$id = $data['id'];
		$id = $sql->escape($id);
		if(!$id) return $this->access_denied();

		// Data validation
		if(!$data['clone']) return $this->error('Clone flags not set.');

		// Check permissions
		$project = new Project($id);
		if(!$project->validate()) return $this->access_denied();

		// Create project record
		$record = $project->info;
		unset($record['id']);
		$record['description'] = $data['description'];
		$record['created'] = App::now();

		// Set project number to max + 1
		$si_id = $record['system_integrator_id'];
		$project_no = App::sql()->query_row("SELECT MAX(project_no) AS maxno FROM project WHERE system_integrator_id = '$si_id';", MySQL::QUERY_ASSOC);
		$project_no = $project_no ? $project_no['maxno'] + 1 : 1;
		$record['project_no'] = $project_no;

		// Create new project record
		$new_id = App::insert('project', $record);
		if(!$new_id) return $this->error('Error saving data.');

		// Copy optional items

		if($data['clone']['systems']) {
			$sql->insert(
				"INSERT INTO project_system_assign (project_id, system_id)
				SELECT '$new_id' AS project_id, system_id FROM project_system_assign WHERE project_id = '$id';
			");
		}

		$structure_list = [];
		if($data['clone']['structure']) {
			$clone_structure_item = function($rec) use (&$clone_structure_item, $sql, $id, $new_id, &$structure_list) {
				// Update record
				$rec_id = $rec['id'];
				unset($rec['id']);
				$rec['project_id'] = $new_id;
				$parent_id = $rec['parent_id'];
				if($parent_id && isset($structure_list[$parent_id])) {
					$rec['parent_id'] = $structure_list[$parent_id];
				} else {
					$parent_id = null;
				}

				// Insert new structure record
				$structure_list[$rec_id] = App::insert('project_structure', $rec);

				// Clone children
				$children = $sql->query("SELECT * FROM project_structure WHERE parent_id = '$rec_id';", MySQL::QUERY_ASSOC) ?: [];
				foreach($children as $child_rec) {
					$clone_structure_item($child_rec);
				}
			};

			$root = $sql->query("SELECT * FROM project_structure WHERE project_id = '$id' AND parent_id IS NULL;", MySQL::QUERY_ASSOC) ?: [];
			foreach($root as $rec) {
				$clone_structure_item($rec);
			}
		}

		if($data['clone']['products']) {
			$clone_project_line = function($rec) use ($sql, $new_id, $structure_list) {
				// Update record
				$line_id = $rec['id'];
				unset($rec['id']);
				$rec['project_id'] = $new_id;

				$structure_id = $rec['structure_id'];
				if($structure_id && isset($structure_list[$structure_id])) {
					$rec['structure_id'] = $structure_list[$structure_id];
				} else {
					return;
				}

				// Insert new line record
				$new_line_id = App::insert('project_line', $rec);
				if(!$new_line_id) return;

				$sql->insert(
					"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
					SELECT '$new_line_id' AS line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id FROM project_labour WHERE line_id = '$line_id';
				");

				$sql->insert(
					"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
					SELECT '$new_line_id' AS line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id FROM project_subscription WHERE line_id = '$line_id';
				");

				$sql->insert(
					"INSERT INTO project_line_slots (line_id, slot_no, placeholder_id, quantity, product_id)
					SELECT '$new_line_id' AS line_id, slot_no, placeholder_id, quantity, product_id FROM project_line_slots WHERE line_id = '$line_id';
				");

				$sql->insert(
					"INSERT INTO project_line_bundle_answers (line_id, question_id, answer)
					SELECT '$new_line_id' AS line_id, question_id, answer FROM project_line_bundle_answers WHERE line_id = '$line_id';
				");

				// Loop through and add all accessories (including their labour and subscription records)
				$accessories = $sql->query("SELECT * FROM project_line WHERE parent_id = '$line_id';", MySQL::QUERY_ASSOC) ?: [];
				foreach($accessories as $a) {
					$record = $a;
					$record['parent_id'] = $new_line_id;
					$record['project_id'] = $new_id;
					unset($record['id']);

					$structure_id = $record['structure_id'];
					if($structure_id && isset($structure_list[$structure_id])) {
						$record['structure_id'] = $structure_list[$structure_id];
					} else {
						return;
					}

					$new_a_id = App::insert('project_line', $record);
					$old_a_id = $a['id'];

					$sql->insert(
						"INSERT INTO project_labour (line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id)
						SELECT '$new_a_id' AS line_id, labour_type_id, labour_hours, hourly_cost, hourly_price, product_labour_id FROM project_labour WHERE line_id = '$old_a_id';
					");

					$sql->insert(
						"INSERT INTO project_subscription (line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id)
						SELECT '$new_a_id' AS line_id, subscription_type_id, quantity, unit_cost, unit_price, frequency, product_subscription_id FROM project_subscription WHERE line_id = '$old_a_id';
					");
				}
			};

			$root = $sql->query("SELECT * FROM project_line WHERE project_id = '$id' AND parent_id IS NULL;", MySQL::QUERY_ASSOC) ?: [];
			foreach($root as $rec) {
				$clone_project_line($rec);
			}
		}

		if($data['clone']['proposal']) {
			$sql->insert(
				"INSERT INTO project_proposal (project_id, text_introduction, text_solution, text_payment, text_payback, text_terms, text_summary, text_quotation, text_subscriptions, show_quantities, show_subtotals, show_acceptance, preferred_payment)
				SELECT '$new_id' AS project_id, text_introduction, text_solution, text_payment, text_payback, text_terms, text_summary, text_quotation, text_subscriptions, show_quantities, show_subtotals, show_acceptance, preferred_payment FROM project_proposal WHERE project_id = '$id';
			");

			$sql->insert(
				"INSERT INTO project_proposal_module (project_id, module_id, text_features)
				SELECT '$new_id' AS project_id, module_id, text_features FROM project_proposal_module WHERE project_id = '$id';
			");
		}

		// Copy over project price adjustment rules
		$sql->insert(
			"INSERT INTO project_product_price_adjustment (project_id, system_id, product_id, type, amount)
			SELECT '$new_id' AS project_id, system_id, product_id, type, amount FROM project_product_price_adjustment WHERE project_id = '$id';
		");

		return $this->success($new_id);
	}

	public function get_project_price_adjustments() {
		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate() || !$project->can_show_pricing()) return $this->access_denied();

		$list = App::sql()->query(
			"SELECT
				COALESCE(ppl.system_id, pl.system_id) AS system_id,
				ps.description AS system_description,
				pl.product_id,
				p.sku,
				p.model,
				p.short_description,
				pa.type,
				pa.amount,
				SUM(pl.unit_cost * pl.quantity) / SUM(pl.quantity) AS unit_cost,
				SUM(pl.base_unit_price * pl.quantity) / SUM(pl.quantity) AS base_unit_price,
				SUM(pl.quantity) AS quantity
			FROM project_line AS pl
			LEFT JOIN project_line AS ppl ON ppl.id = pl.parent_id
			LEFT JOIN project_product_price_adjustment AS pa ON pa.project_id = '$id' AND pa.system_id = COALESCE(ppl.system_id, pl.system_id) AND pa.product_id = pl.product_id
			JOIN product AS p ON p.id = pl.product_id
			JOIN project_system AS ps ON ps.id = COALESCE(ppl.system_id, pl.system_id)
			WHERE pl.project_id = '$id'
			GROUP BY COALESCE(ppl.system_id, pl.system_id), ps.description, pl.product_id, p.sku, p.model, p.short_description, pa.type, pa.amount
			ORDER BY system_description, system_id, p.sku, p.model, p.short_description;
		", MySQL::QUERY_ASSOC, false);

		$editable = in_array($project->info['stage'], ['lead', 'survey']);

		return $this->success([
			'list' => $list ?: [],
			'editable' => $editable
		]);
	}

	public function save_project_price_adjustments() {
		$data = App::json() ?: [];

		$id = App::get('id', 0, true);
		$project = new Project($id);
		if(!$project->validate() || !$project->can_show_pricing()) return $this->access_denied();

		// Delete existing adjustment records
		App::sql()->delete("DELETE FROM project_product_price_adjustment WHERE project_id = '$id';");

		// Save new adjustment records
		foreach($data as $item) {
			if($item['type']) {
				App::insert('project_product_price_adjustment', [
					'project_id' => $id,
					'system_id' => $item['system_id'],
					'product_id' => $item['product_id'],
					'type' => $item['type'],
					'amount' => $item['amount'] ?: 0
				]);
			}
		}

		// Apply adjustments to whole project
		$project->apply_price_adjustments();

		return $this->success();
	}

	public function list_project_systems() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$modules = App::sql()->query(
			"SELECT
				t.id, t.description, t.icon, t.colour,
				IF(t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id', 1, 0) AS editable,
				COALESCE(sp.company_name, si.company_name) AS owner_name
			FROM project_module AS t

			LEFT JOIN service_provider AS sp ON t.owner_level = 'SP' AND t.owner_id = sp.id
			LEFT JOIN system_integrator AS si ON t.owner_level = 'SI' AND t.owner_id = si.id
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$this->selected_product_owner_level' AND pr.reseller_id = '$this->selected_product_owner_id'
			WHERE (t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id') OR pr.owner_level IS NOT NULL

			ORDER BY t.display_order;
		");

		$systems = App::sql()->query(
			"SELECT
				t.id, t.description, t.module_id,
				IF(t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id', 1, 0) AS editable,
				COALESCE(sp.company_name, si.company_name) AS owner_name
			FROM project_system AS t

			LEFT JOIN service_provider AS sp ON t.owner_level = 'SP' AND t.owner_id = sp.id
			LEFT JOIN system_integrator AS si ON t.owner_level = 'SI' AND t.owner_id = si.id
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$this->selected_product_owner_level' AND pr.reseller_id = '$this->selected_product_owner_id'
			WHERE (t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id') OR pr.owner_level IS NOT NULL

			ORDER BY t.description;
		");

		return $this->success([
			'modules' => $modules ?: [],
			'systems' => $systems ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_project_system() {
		$id = App::get('id', 0, true);

		$details = App::select('project_system', $id);
		if(!$details) return $this->error('System not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();

		return $this->success([
			'details' => $details,
			'breadcrumbs' => [
				[ 'description' => 'Modules and Systems', 'route' => '/sales/project-system' ],
				[ 'description' => $details['description'] ]
			]
		]);
	}

	public function new_project_system() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'description' => 'New System',
				'module_id' => null
			],
			'breadcrumbs' => [
				[ 'description' => 'Modules and Systems', 'route' => '/sales/project-system' ],
				[ 'description' => 'New System' ]
			]
		]);
	}

	public function save_project_system() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_level', 'owner_id', 'description', 'module_id']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'description'], '');
		$record = App::ensure($record, ['module_id'], null);

		// Check permissions
		if($id !== 'new') {
			$original = App::select('project_system', $id);
			if(!$original) return $this->error('System not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') return $this->error('Please enter system description.');
		if($record['module_id'] === null) return $this->error('Please select a module.');

		// Insert/update record
		if($id !== 'new') unset($record['module_id']);

		$id = App::upsert('project_system', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function move_project_module_up() {
		// Check if ID is set
		$id = App::get('id');
		if(!$id) return $this->access_denied();

		$module = App::select('project_module', $id);
		if(!$module) return $this->error('Module not found');
		if(!Permission::get($module['owner_level'], $module['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();

		// Get module above
		$this_order = $module['display_order'];
		$other_module = App::sql()->query_row("SELECT id, display_order FROM project_module WHERE display_order < '$this_order' ORDER BY display_order DESC;", MySQL::QUERY_ASSOC);

		// Other module not found, no change
		// Don't throw error, it's ok
		if(!$other_module) return $this->success();

		// Swap display_order fields
		App::update('project_module', $id, ['display_order' => $other_module['display_order']]);
		App::update('project_module', $other_module['id'], ['display_order' => $this_order]);

		return $this->success();
	}

	public function move_project_module_down() {
		// Check if ID is set
		$id = App::get('id');
		if(!$id) return $this->access_denied();

		$module = App::select('project_module', $id);
		if(!$module) return $this->error('Module not found');
		if(!Permission::get($module['owner_level'], $module['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();

		// Get module below
		$this_order = $module['display_order'];
		$other_module = App::sql()->query_row("SELECT id, display_order FROM project_module WHERE display_order > '$this_order' ORDER BY display_order;", MySQL::QUERY_ASSOC);

		// Other module not found, no change
		// Don't throw error, it's ok
		if(!$other_module) return $this->success();

		// Swap display_order fields
		App::update('project_module', $id, ['display_order' => $other_module['display_order']]);
		App::update('project_module', $other_module['id'], ['display_order' => $this_order]);

		return $this->success();
	}

	public function get_project_module() {
		$id = App::get('id', 0, true);

		$details = App::select('project_module', $id);
		if(!$details) return $this->error('Module not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();

		$details['assets'] = [];
		$assets = App::sql()->query("SELECT user_content_id FROM project_module_assets WHERE module_id = '$id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($assets as $a) {
			$uc_id = $a['user_content_id'];
			$uc = new UserContent($uc_id);
			if($uc->info) {
				$details['assets'][] = [
					'user_content_id' => $uc_id,
					'url' => $uc->get_url()
				];
			}
		}

		return $this->success([
			'details' => $details,
			'breadcrumbs' => [
				[ 'description' => 'Modules and Systems', 'route' => '/sales/project-system' ],
				[ 'description' => $details['description'] ]
			]
		]);
	}

	public function new_project_module() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'description' => 'New Module',
				'icon' => 'eticon eticon-dot',
				'colour' => '#666666',
				'text_colour' => '#ffffff',
				'proposal_text' => '',
				'proposal_content' => '',
				'assets' => []
			],
			'breadcrumbs' => [
				[ 'description' => 'Modules and Systems', 'route' => '/sales/project-system' ],
				[ 'description' => 'New Module' ]
			]
		]);
	}

	public function save_project_module() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		$is_new = $id === 'new';
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_level', 'owner_id', 'description', 'icon', 'colour', 'text_colour', 'proposal_text', 'proposal_content']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'description', 'icon', 'colour', 'text_colour', 'proposal_text', 'proposal_content'], '');

		// Check permissions
		if($id !== 'new') {
			$original = App::select('project_module', $id);
			if(!$original) return $this->error('Module not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::SALES_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') return $this->error('Please enter module description.');
		if($record['icon'] === '') return $this->error('Please enter module icon.');
		if($record['colour'] === '') return $this->error('Please enter module colour.');

		// Insert/update record
		$id = App::upsert('project_module', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Default display_order to item id
		App::update('project_module', $id, ['display_order' => $id]);

		// Delete old assets
		App::sql()->delete("DELETE FROM project_module_assets WHERE module_id = '$id';");

		// Save module assets
		$assets = $data['assets'] ?: [];
		foreach($assets as $a) {
			$asset_id = App::escape($a['user_content_id']);
			App::sql()->insert("INSERT INTO project_module_assets (module_id, user_content_id) VALUES ('$id', '$asset_id');");
		}

		return $this->success($id);
	}

}
