<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	private function get_breadcrumbs($level, $id = 0) {
		$levels = Permission::get_admin_chain($level, $id);
		$crumbs = [];

		foreach($levels as $p => $v) {
			if($v['admin']) {
				$id = $v['id'];
				$route = '';
				switch($p) {
					case PermissionLevel::ETICOM:
						$route = "/settings/eticom";
						break;
					case PermissionLevel::SERVICE_PROVIDER:
						$route = "/settings/service-provider/$id";
						break;
					case PermissionLevel::SYSTEM_INTEGRATOR:
						$route = "/settings/system-integrator/$id";
						break;
					case PermissionLevel::HOLDING_GROUP:
						$route = "/settings/holding-group/$id";
						break;
					case PermissionLevel::CLIENT:
						$route = "/settings/client/$id";
						break;
					case PermissionLevel::BUILDING:
						$route = "/settings/site/$id";
						break;
				}

				$crumbs[] = [
					'description' => $v['description'],
					'route' => $route
				];
			}
		}
 
		return $crumbs;
	}

	private function get_admin_permissions($level, $id, $child_level = false) {
		// Get permission and UI object for level admin role
		$level = App::escape($level);
		$id = App::escape($id) ?: 0;

		if(!$level || (!$id && $level != PermissionLevel::ETICOM)) return null;
		if($child_level) {
			// This is a check for creating a new child object
			// Check if user has admin access to the current level
			if(!Permission::get($level, $id)->check(Permission::ADMIN)) return null;
		} else {
			// This is a check for editing the current object's admin permissions
			// Check if user has a higher level admin access
			if(!Permission::is_higher_admin($level, $id)) return null;
		}

		$r = App::sql()->query_row("SELECT * FROM user_role WHERE is_admin = 1 AND is_level_default = 0 AND owner_level = '$level' AND owner_id = '$id';");
		$permission = new Permission($r);
		$permission->mix_least_permissive(Permission::get_permission_level_defaults()[$child_level ?: $level]);

		$ui_permission = Permission::get($level, $id);
		$ui_permission->mix_least_permissive(Permission::get_level_admin_permissions($level, $id, !$child_level));
		$ui_permission->mix_least_permissive(Permission::get_permission_level_defaults()[$child_level ?: $level]);

		return [
			'record' => $permission->get_fields(),
			'ui' => $ui_permission->get_ui($level == PermissionLevel::ETICOM)
		];
	}

	private function save_admin_permissions($fields, $level, $id) {
		if(!$fields) return;
		if(!isset($fields['record'])) return;
		if(!Permission::get($level, $id)->check(Permission::ADMIN)) return;
		if(!Permission::is_higher_admin($level, $id)) return;

		$permission = new Permission($fields['record']);

		$r = App::sql()->query_row("SELECT id FROM user_role WHERE is_admin = 1 AND is_level_default = 0 AND owner_level = '$level' AND owner_id = '$id';");
		if($r) {
			$result = App::update('user_role', $r->id, $permission->get_fields());
		} else {
			$record = $permission->get_fields();
			$record = array_merge($record, [
				'owner_level' => $level,
				'owner_id' => $id ?: 0,
				'is_admin' => 1,
				'description' => PermissionLevel::description($level).' Admin'
			]);
			$result = App::insert('user_role', $record);
		}
	}

	private function get_user_access($id) {
		$user = App::user();

		$data_access = false; // Can update name/email/password
		$role_access = false; // Can change user's roles

		// If none of the above access levels are true, it means no access

		if($id == $user->id) {
			// This is the logged in user's record, has data access
			$data_access = true;
		} else {
			// Check if we have admin access on ALL permission levels the user has set
			// We can only edit the user's data if we have full admin privileges over the user
			$levels = App::sql()->query("SELECT DISTINCT assigned_level AS level, assigned_id AS id FROM user_role_assignment WHERE user_id = '$id';");
			$data_access = true;
			if($levels) {
				foreach($levels as $l) {
					if(!Permission::get($l->level, $l->id)->check(Permission::ADMIN)) {
						$data_access = false;
						break;
					}
				}
			}
		}

		if($id == $user->id) {
			// User can't change his/her own permissions
			$role_access = false;
		} else if(Permission::get_eticom()->check(Permission::ADMIN)) {
			$role_access = true;
		} else {
			// Enumerate all admin roles for the logged in user
			// Allow role access if edited user doesn't have higher admin role on AT LEAST ONE admin branch
			$edited_user = new User($id);

			// Fix: If user role is set to 0 (No Access), return the assigned level instead of Eticom Root
			// This makes sure user permissions are editable on the assigned level.
			$admin_levels = App::sql()->query(
				"SELECT
					ura.assigned_level AS level,
					ura.assigned_id AS id,
					IF(eur.id = 0, ura.assigned_level, eur.owner_level) AS edited_user_level
				FROM user_role_assignment AS ura
				JOIN user_role AS ur ON ur.id = ura.user_role_id AND ur.is_admin = 1 AND ur.is_level_default = 0
				LEFT JOIN user_role_assignment AS eura ON eura.user_id = '$id' AND eura.assigned_level = ura.assigned_level AND eura.assigned_id = ura.assigned_id
				LEFT JOIN user_role AS eur ON eur.id = eura.user_role_id
				WHERE ura.user_id = '$user->id';"
			);

			$role_access = false;
			if($admin_levels) {
				foreach($admin_levels as $l) {
					if($l->edited_user_level) {
						if(PermissionLevel::lt($l->edited_user_level, $l->level)) continue; // User has a role from a higher level, can't edit
					}

					if(Permission::is_higher_admin($l->level, $l->id, $edited_user)) {
						continue; // User has a higher admin level, can't edit
					}

					// At this point, all checks have passed
					// User is allowed to edit edited user's role
					$role_access = true;
					break;
				}
			}
		}

		return [
			'data_access' => $data_access,
			'role_access' => $role_access
		];
	}

	// Given a level and an ID, return info to show in the UI when assigning user roles
	// This includes all possible roles to show in a dropdown
	private function get_role_level_details($level, $id, $current_value = 0) {
		if($level != PermissionLevel::ETICOM) {
			$chain = Permission::get_level_chain($level, $id);
			if(!$chain) return null;

			// Get parents
			$parents = ['E' => 0];
			$description = 'Unknown '.PermissionLevel::description($level);

			foreach(PermissionLevel::all() as $p) {
				if($p == $level) {
					if(isset($chain->{$p.'_description'})) $description = $chain->{$p.'_description'};
				} else {
					if(isset($chain->{$p.'_id'})) $parents[$p] = $chain->{$p.'_id'};
				}
			}
		} else {
			$parents = null;
			$description = 'Eticom';
		}

		$filter = [ "(owner_level = '$level' AND owner_id = '$id')" ];
		if($parents) {
			foreach($parents as $parent_level => $parent_id) {
				if(Permission::get($parent_level, $parent_id)->check(Permission::ADMIN)) {
					$filter[] = "(owner_level = '$parent_level' AND owner_id = '$parent_id')";
				}
			}
		}
		$filter = implode(' OR ', $filter);

		$q = "SELECT
				id, owner_level, owner_id, description, is_admin
			FROM user_role
			WHERE id <> 0 AND is_level_default = 0 AND ($filter)
			ORDER BY owner_level DESC, is_admin DESC, description;";

		$list = App::sql()->query($q) ?: [];

		$groups = [
			$level => [
				[ 'id' => 0, 'description' => 'No Access', 'is_admin' => false ]
			]
		];
		foreach($list as $role) {
			if(Permission::get($role->owner_level, $role->owner_id)->check(Permission::ADMIN)) {
				$info = [
					'id' => $role->id,
					'description' => $role->description,
					'is_admin' => $role->is_admin == 1
				];
				if(!isset($groups[$role->owner_level])) {
					$groups[$role->owner_level] = [$info];
				} else {
					$groups[$role->owner_level][] = $info;
				}
			}
		}

		$roles = [];
		foreach($groups as $group_level => $items) {
			$roles[] = [
				'group' => PermissionLevel::Description($group_level),
				'items' => $items
			];
		}

		return [
			'level' => $level,
			'level_index' => PermissionLevel::order($level),
			'id' => (int)$id,
			'description' => $description,
			'icon' => PermissionLevel::icon($level),
			'parents' => $parents,
			'selected' => $current_value,
			'original' => $current_value,
			'roles' => $roles
		];
	}

	public function get_navigation() {
		$user = App::user();
		if(!$user) return $this->access_denied();

		// Basic settings

		$nav = [
			[ 'name' => 'Settings', 'header' => true ],
			[ 'name' => 'User Profile', 'icon' => 'md md-person', 'route' => '/settings/user-profile' ]
		];

		// Find the highest admin level for the admin navigation items
		$r = App::sql()->query_row(
			"SELECT
				ura.assigned_level AS level,
				COUNT(*) AS cnt
			FROM user_role_assignment AS ura
			JOIN user_role AS ur ON ur.id = ura.user_role_id
			WHERE ura.user_id = '$user->id' AND ur.is_admin = 1
			GROUP BY level
			HAVING cnt > 0
			ORDER BY level
			LIMIT 1;"
		);

		// Permission based settings screens

		$show_products = false;
		if($r) {
			$level = $r->level;
			$level_cnt = $r->cnt;

			foreach(PermissionLevel::all() as $p) {
				if(PermissionLevel::lt($p, $level)) {
					// No access at this level
					continue;

				} else if($level_cnt <= 3 && PermissionLevel::eq($p, $level)) {
					// Top level admin access (3 or less items)
					switch($p) {
						case PermissionLevel::ETICOM:
							$show_products = true;
							$nav[] = [ 'name' => 'Eticom', 'icon' => PermissionLevel::icon($p), 'route' => '/settings/eticom' ];
							break;

						case PermissionLevel::SERVICE_PROVIDER:
							$list = Permission::list_service_providers([ 'with' => Permission::ADMIN ]);
							if($list) {
								$show_products = true;
								foreach($list as $r) {
									$nav[] = [ 'name' => $r->company_name ?: '', 'icon' => PermissionLevel::icon($p), 'route' => '/settings/service-provider/'.$r->id ];
								}
							}
							break;

						case PermissionLevel::SYSTEM_INTEGRATOR:
							$list = Permission::list_system_integrators([ 'with' => Permission::ADMIN ]);
							if($list) {
								$show_products = true;
								foreach($list as $r) {
									$nav[] = [ 'name' => $r->company_name ?: '', 'icon' => PermissionLevel::icon($p), 'route' => '/settings/system-integrator/'.$r->id ];
								}
							}
							break;

						case PermissionLevel::HOLDING_GROUP:
							$list = Permission::list_holding_groups([ 'with' => Permission::ADMIN ]);
							if($list) {
								foreach($list as $r) {
									$nav[] = [ 'name' => $r->company_name ?: '', 'icon' => PermissionLevel::icon($p), 'route' => '/settings/holding-group/'.$r->id ];
								}
							}
							break;

						case PermissionLevel::CLIENT:
							$list = Permission::list_clients([ 'with' => Permission::ADMIN ]);
							if($list) {
								foreach($list as $r) {
									$nav[] = [ 'name' => $r->name ?: '', 'icon' => PermissionLevel::icon($p), 'route' => '/settings/client/'.$r->id ];
								}
							}
							break;

						case PermissionLevel::BUILDING:
							$list = Permission::list_buildings([ 'with' => Permission::ADMIN ]);
							if($list) {
								foreach($list as $r) {
									$nav[] = [ 'name' => $r->description, 'icon' => PermissionLevel::icon($p), 'route' => '/settings/site/'.$r->id ];
								}
							}
							break;
					}

				} else {
					// Child admin access (or more than 3 items)
					switch($p) {
						case PermissionLevel::ETICOM:
							$show_products = true;
							$nav[] = [ 'name' => 'Eticom', 'icon' => 'md md-grade', 'route' => '/settings/eticom' ];
							break;

						case PermissionLevel::SERVICE_PROVIDER:
							$show_products = true;
							$nav[] = [ 'name' => 'Service Providers', 'icon' => 'md md-filter-drama', 'route' => '/settings/service-provider' ];
							break;

						case PermissionLevel::SYSTEM_INTEGRATOR:
							$show_products = true;
							$nav[] = [ 'name' => 'System Integrators', 'icon' => 'md md-local-shipping', 'route' => '/settings/system-integrator' ];
							break;

						case PermissionLevel::HOLDING_GROUP:
							$nav[] = [ 'name' => 'Holding Groups', 'icon' => 'md md-group-work', 'route' => '/settings/holding-group' ];
							break;

						case PermissionLevel::CLIENT:
							$nav[] = [ 'name' => 'Clients', 'icon' => 'md md-work', 'route' => '/settings/client' ];
							break;

						case PermissionLevel::BUILDING:
							$nav[] = [ 'name' => 'Sites', 'icon' => 'md md-place', 'route' => '/settings/site' ];
							break;
					}
				}
			}
		}

		if(Permission::get_eticom()->check(Permission::ADMIN)) {
			$nav[] = [ 'name' => 'Users', 'icon' => 'md md-person', 'route' => '/settings/user' ];
		}

		// SmoothPower
		if(Permission::get_eticom()->check(Permission::ADMIN)) {
			$nav[] = [ 'name' => 'Smooth Power', 'header' => true ];
			$nav[] = [ 'name' => 'Software Updates', 'icon' => 'md md-sd-card', 'route' => '/settings/smoothpower-updates' ];
		}

		// Monitoring
		if(Permission::get_eticom()->check(Permission::ADMIN)) {
			$r = App::sql()->query_row(
				"SELECT
					SUM(IF(gs.ignore <> 1 AND gs.status <> 'ok', 1, 0)) AS error,
					MIN(TIMESTAMPDIFF(SECOND, gs.last_checked, NOW())) AS last_check
				FROM gateway_status AS gs
				JOIN gateway AS g ON g.id = gs.gateway_id
				JOIN area AS a ON a.id = g.area_id
				JOIN floor AS f ON f.id = a.floor_id
				JOIN building AS b ON b.id = f.building_id
				WHERE client_id <> 0;
			");

			$badge = '';
			$badge_icon = '';
			if($r) {
				if($r->last_check === null || $r->last_check > 900) $badge_icon = 'md md-warning';
				if($r->error) $badge = $r->error;
			}

			$nav[] = [ 'name' => 'Monitoring', 'header' => true ];
			$nav[] = [ 'name' => 'Collectors', 'icon' => 'ei ei-gateway', 'route' => '/settings/monitor-collectors', 'badge' => $badge, 'badgeIcon' => $badge_icon ];
		}

		return $this->success($nav);
	}

	public function get_eticom() {
		if(!Permission::get_eticom()->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$result = [
			'breadcrumbs' => $this->get_breadcrumbs(PermissionLevel::ETICOM)
		];

		return $this->success($result);
	}

	public function list_service_providers() {
		$result = [
			'list' => Permission::list_service_providers([ 'with' => Permission::ADMIN ])
		];
		return $this->success($result);
	}

	public function new_service_provider() {
		$permission = Permission::get_eticom();
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs(PermissionLevel::ETICOM);
		$crumbs[] = [ 'description' => 'New Service Provider' ];

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => [ 'id' => 'new' ],
			'permissions' => $this->get_admin_permissions(PermissionLevel::ETICOM, 0, PermissionLevel::SERVICE_PROVIDER)
		];

		return $this->success($result);
	}

	public function get_service_provider() {
		$id = App::get('id', 0, true);
		if(!$id || !Permission::get_service_provider($id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$result = [
			'breadcrumbs' => $this->get_breadcrumbs(PermissionLevel::SERVICE_PROVIDER, $id)
		];

		$q = "SELECT
				id, company_name, email_address, phone_number, mobile_number, vat_reg_number,
				address_line_1, address_line_2, address_line_3, posttown, postcode,
				invoice_address_line_1, invoice_address_line_2, invoice_address_line_3, invoice_posttown, invoice_postcode,
				bank_name, bank_sort_code, bank_account_number
			FROM service_provider
			WHERE id = '$id';
		";
		$result['details'] = App::sql()->query_row($q) ?: null;
		$result['permissions'] = $this->get_admin_permissions(PermissionLevel::SERVICE_PROVIDER, $id);

		$result['payment_accounts'] = array_map(
			function($item) {
				$pa = PaymentAccount::from_id($item->id);
				if(!$pa) return null;
				return $pa->get_details();
			},
			App::sql()->query("SELECT id FROM payment_account WHERE customer_type = 'SP' AND customer_id = '$id';") ?: []
		);

		return $this->success($result);
	}

	public function save_service_provider() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) {
			return $this->access_denied();
		}

		// Check permissions
		$perm = $id === 'new' ? Permission::get_eticom() : Permission::get_service_provider($id);
		if(!$perm->check(Permission::ADMIN)) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'company_name', 'email_address', 'phone_number', 'mobile_number', 'vat_reg_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'invoice_address_line_1', 'invoice_address_line_2', 'invoice_address_line_3', 'invoice_posttown', 'invoice_postcode',
			'bank_name', 'bank_sort_code', 'bank_account_number'
		]);
		$record = App::ensure($record, ['company_name', 'bank_name', 'bank_sort_code', 'bank_account_number'], '');

		// Data validation
		if($record['company_name'] === '') {
			return $this->error('Please enter company name.');
		}
		$record['bank_sort_code'] = str_replace('-','',$record['bank_sort_code']);

		// Insert/update record
		$id = App::upsert('service_provider', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		if(isset($data['permissions'])) $this->save_admin_permissions($data['permissions'], PermissionLevel::SERVICE_PROVIDER, $id);

		return $this->success($id);
	}

	public function list_system_integrators() {
		list($filter, $id) = App::get(['filter', 'id'], '');
		$options = [ 'with' => Permission::ADMIN ];
		if($filter && $id) {
			$options['filter_level'] = $filter;
			$options['id'] = $id;
		}

		$result = [
			'list' => Permission::list_system_integrators($options)
		];
		return $this->success($result);
	}

	public function new_system_integrator() {
		$level = App::get('level', '', true);
		$id = App::get('id', 0, true);
		if(!$id || $level != PermissionLevel::SERVICE_PROVIDER) {
			return $this->access_denied();
		}

		$permission = Permission::get($level, $id);
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs($level, $id);
		$crumbs[] = [ 'description' => 'New System Integrator' ];

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => [
				'id' => 'new',
				'service_provider_id' => $id,
				'logo_on_light_id' => null,
				'logo_on_dark_id' => null
			],
			'logo_on_light_url' => '',
			'logo_on_dark_url' => '',
			'permissions' => $this->get_admin_permissions($level, $id, PermissionLevel::SYSTEM_INTEGRATOR)
		];

		return $this->success($result);
	}

	public function get_system_integrator() {
		$id = App::get('id', 0, true);
		if(!$id || !Permission::get_system_integrator($id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$result = [
			'breadcrumbs' => $this->get_breadcrumbs(PermissionLevel::SYSTEM_INTEGRATOR, $id)
		];

		$q = "SELECT
				id, service_provider_id, company_name,
				email_address, phone_number, mobile_number, vat_reg_number,
				address_line_1, address_line_2, address_line_3, posttown, postcode,
				invoice_address_line_1, invoice_address_line_2, invoice_address_line_3, invoice_posttown, invoice_postcode,
				bank_name, bank_sort_code, bank_account_number,
				proposal_strapline, proposal_footer, proposal_cover_footer, logo_on_light_id, logo_on_dark_id
			FROM system_integrator
			WHERE id = '$id';
		";
		$result['details'] = App::sql()->query_row($q, MySQL::QUERY_ASSOC) ?: null;
		$result['permissions'] = $this->get_admin_permissions(PermissionLevel::SYSTEM_INTEGRATOR, $id);
		$result['isp'] = Permission::get_system_integrator($id)->check(Permission::ISP_ENABLED);

		// Resolve logo images
		$image_url = null;
		if($result['details']['logo_on_light_id']) {
			$uc = new UserContent($result['details']['logo_on_light_id']);
			if($uc->info) $image_url = $uc->get_url();
		}
		$result['logo_on_light_url'] = $image_url;

		$image_url = null;
		if($result['details']['logo_on_dark_id']) {
			$uc = new UserContent($result['details']['logo_on_dark_id']);
			if($uc->info) $image_url = $uc->get_url();
		}
		$result['logo_on_dark_url'] = $image_url;

		$result['payment_accounts'] = array_map(
			function($item) {
				$pa = PaymentAccount::from_id($item->id);
				if(!$pa) return null;
				return $pa->get_details();
			},
			App::sql()->query("SELECT id FROM payment_account WHERE customer_type = 'SI' AND customer_id = '$id';") ?: []
		);

		return $this->success($result);
	}

	public function save_system_integrator() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? App::escape($data['id']) : null;
		$service_provider_id = isset($data['service_provider_id']) ? $data['service_provider_id'] : null;
		if(!$id || !$service_provider_id) {
			return $this->access_denied();
		}

		// Check permissions
		$perm = $id === 'new' ? Permission::get_service_provider($service_provider_id) : Permission::get_system_integrator($id);
		if(!$perm->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'company_name',
			'email_address', 'phone_number', 'mobile_number', 'vat_reg_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'invoice_address_line_1', 'invoice_address_line_2', 'invoice_address_line_3', 'invoice_posttown', 'invoice_postcode',
			'bank_name', 'bank_sort_code', 'bank_account_number',
			'proposal_strapline', 'proposal_footer', 'proposal_cover_footer', 'logo_on_light_id', 'logo_on_dark_id'
		]);
		$record = App::ensure($record, ['company_name', 'bank_name', 'bank_sort_code', 'bank_account_number', 'proposal_strapline', 'proposal_footer', 'proposal_cover_footer'], '');

		// Data validation
		if($record['company_name'] === '') {
			return $this->error('Please enter company name.');
		}
		$record['bank_sort_code'] = str_replace('-','',$record['bank_sort_code']);

		// Remove usage from previous user content
		$register_image = true;
		if($id !== 'new') {
			$original = App::sql()->query_row("SELECT logo_on_light_id, logo_on_dark_id FROM system_integrator WHERE id = '$id';");
			if($original) {
				if($original->logo_on_light_id == $record['logo_on_light_id']) {
					// No change
					$register_image = false;
				} else if($original->logo_on_light_id) {
					$uc = new UserContent($original->logo_on_light_id);
					$uc->remove_usage();
				}
				if($original->logo_on_dark_id == $record['logo_on_dark_id']) {
					// No change
					$register_image = false;
				} else if($original->logo_on_dark_id) {
					$uc = new UserContent($original->logo_on_dark_id);
					$uc->remove_usage();
				}
			}
		}

		// Insert/update record
		if($id === 'new') $record['service_provider_id'] = $service_provider_id;
		$id = App::upsert('system_integrator', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		if(isset($data['permissions'])) $this->save_admin_permissions($data['permissions'], PermissionLevel::SYSTEM_INTEGRATOR, $id);

		// Add usage to new logo images if any
		if($register_image) {
			if($record['logo_on_light_id']) {
				$uc = new UserContent($record['logo_on_light_id']);
				$uc->add_usage();
			}
			if($record['logo_on_dark_id']) {
				$uc = new UserContent($record['logo_on_dark_id']);
				$uc->add_usage();
			}
		}

		return $this->success($id);
	}

	public function list_holding_groups() {
		list($filter, $id) = App::get(['filter', 'id'], '');
		$options = [ 'with' => Permission::ADMIN ];
		if($filter && $id) {
			$options['filter_level'] = $filter;
			$options['id'] = $id;
		}

		$result = [
			'list' => Permission::list_holding_groups($options) ?: []
		];
		return $this->success($result);
	}

	public function new_holding_group() {
		$level = App::get('level', '', true);
		$id = App::get('id', 0, true);
		if(!$id || $level != PermissionLevel::SYSTEM_INTEGRATOR) {
			return $this->access_denied();
		}

		$permission = Permission::get($level, $id);
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs($level, $id);
		$crumbs[] = [ 'description' => 'New Holding Group' ];

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => [
				'id' => 'new',
				'system_integrator_id' => $id
			],
			'permissions' => $this->get_admin_permissions($level, $id, PermissionLevel::HOLDING_GROUP)
		];

		return $this->success($result);
	}

	public function get_holding_group() {
		$id = App::get('id', 0, true);
		if(!$id || !Permission::get_holding_group($id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$result = [
			'breadcrumbs' => $this->get_breadcrumbs(PermissionLevel::HOLDING_GROUP, $id)
		];

		$q = "SELECT
				id, system_integrator_id, company_name,
				email_address, phone_number, mobile_number, vat_reg_number,
				address_line_1, address_line_2, address_line_3, posttown, postcode,
				invoice_address_line_1, invoice_address_line_2, invoice_address_line_3, invoice_posttown, invoice_postcode,
				bank_name, bank_sort_code, bank_account_number
			FROM holding_group
			WHERE id = '$id';
		";
		$result['details'] = App::sql()->query_row($q) ?: null;
		$result['permissions'] = $this->get_admin_permissions(PermissionLevel::HOLDING_GROUP, $id);

		$result['payment_accounts'] = array_map(
			function($item) {
				$pa = PaymentAccount::from_id($item->id);
				if(!$pa) return null;
				return $pa->get_details();
			},
			App::sql()->query("SELECT id FROM payment_account WHERE customer_type = 'HG' AND customer_id = '$id';") ?: []
		);

		return $this->success($result);
	}

	public function save_holding_group() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		$system_integrator_id = isset($data['system_integrator_id']) ? $data['system_integrator_id'] : null;
		if(!$id || !$system_integrator_id) {
			return $this->access_denied();
		}

		// Check permissions
		$perm = $id === 'new' ? Permission::get_system_integrator($system_integrator_id) : Permission::get_holding_group($id);
		if(!$perm->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'company_name',
			'email_address', 'phone_number', 'mobile_number', 'vat_reg_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'invoice_address_line_1', 'invoice_address_line_2', 'invoice_address_line_3', 'invoice_posttown', 'invoice_postcode',
			'bank_name', 'bank_sort_code', 'bank_account_number'
		]);
		$record = App::ensure($record, ['company_name', 'bank_name', 'bank_sort_code', 'bank_account_number'], '');

		// Data validation
		if($record['company_name'] === '') {
			return $this->error('Please enter holding group name.');
		}
		$record['bank_sort_code'] = str_replace('-','',$record['bank_sort_code']);

		// Insert/update record
		if($id === 'new') $record['system_integrator_id'] = $system_integrator_id;
		$id = App::upsert('holding_group', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		if(isset($data['permissions'])) $this->save_admin_permissions($data['permissions'], PermissionLevel::HOLDING_GROUP, $id);

		return $this->success($id);
	}

	public function list_clients() {
		list($filter, $id) = App::get(['filter', 'id'], '', true);
		$options = [ 'with' => Permission::ADMIN ];
		if($filter && $id) {
			$options['filter_level'] = $filter;
			$options['id'] = $id;
		}

		$result = [
			'list' => Permission::list_clients($options) ?: []
		];

		// Remove BLOB fields. Binary fields cannot be encoded into JSON, as it will fail silently
		foreach($result['list'] as &$item) {
			unset($item->logo_image);
			unset($item->logo_img);
		}
		unset($item);

		if($filter === PermissionLevel::HOLDING_GROUP) {
			// Frontend needs to know if user is an SI admin
			$si_admin = false;
			$r = App::sql()->query_row("SELECT system_integrator_id FROM holding_group WHERE id = '$id';");
			if($r) {
				$perm = Permission::get_system_integrator($r->system_integrator_id);
				$si_admin = $perm->check(Permission::ADMIN);
			}
			$result['system_integrator_admin'] = $si_admin;
		}

		return $this->success($result);
	}

	public function new_client() {
		$level = App::get('level', '', true);
		$id = App::get('id', 0, true);
		if(!$id || ($level != PermissionLevel::SYSTEM_INTEGRATOR && $level != PermissionLevel::HOLDING_GROUP)) {
			return $this->access_denied();
		}

		$si_id = null;
		$hg_id = null;
		if($level == PermissionLevel::SYSTEM_INTEGRATOR) {
			// Creating under SI, without HG
			$si_id = $id;
		} else {
			// Creating under HG, resolve SI
			$hg_id = $id;
			$r = App::sql()->query_row("SELECT system_integrator_id FROM holding_group WHERE id = '$hg_id';");
			if($r) $si_id = $r->system_integrator_id;
			if(!$si_id) return $this->access_denied();
		}

		$permission = Permission::get(PermissionLevel::SYSTEM_INTEGRATOR, $si_id);
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs($level, $id);
		$crumbs[] = [ 'description' => 'New Client' ];

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => [
				'id' => 'new',
				'image_id' => null,
				'system_integrator_id' => $si_id,
				'holding_group_id' => $hg_id
			],
			'image_url' => null,
			'permissions' => $this->get_admin_permissions($level, $id, PermissionLevel::CLIENT)
		];

		return $this->success($result);
	}

	public function get_client() {
		$id = App::get('id', 0, true);
		if(!$id || !Permission::get_client($id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$result = [
			'breadcrumbs' => $this->get_breadcrumbs(PermissionLevel::CLIENT, $id)
		];

		$q = "SELECT
				id, holding_group_id, system_integrator_id, name, image_id,
				email_address, phone_number, mobile_number, vat_reg_number,
				address_line_1, address_line_2, address_line_3, posttown, postcode,
				invoice_address_line_1, invoice_address_line_2, invoice_address_line_3, invoice_posttown, invoice_postcode,
				bank_name, bank_sort_code, bank_account_number,
				module_billing
			FROM client
			WHERE id = '$id';
		";
		$result['details'] = App::sql()->query_row($q, MySQL::QUERY_ASSOC) ?: null;
		$result['permissions'] = $this->get_admin_permissions(PermissionLevel::CLIENT, $id);

		$perm = Permission::get_system_integrator($result['details']['system_integrator_id']);
		$si_admin = $perm->check(Permission::ADMIN);
		$result['system_integrator_admin'] = $si_admin;

		// Resolve client image
		$image_url = null;
		if($result['details']['image_id']) {
			$uc = new UserContent($result['details']['image_id']);
			if($uc->info) $image_url = $uc->get_url();
		}
		$result['image_url'] = $image_url;

		$result['payment_accounts'] = array_map(
			function($item) {
				$pa = PaymentAccount::from_id($item->id);
				if(!$pa) return null;
				return $pa->get_details();
			},
			App::sql()->query("SELECT id FROM payment_account WHERE customer_type = 'C' AND customer_id = '$id';") ?: []
		);

		return $this->success($result);
	}

	public function save_client() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? App::escape($data['id']) : null;
		$system_integrator_id = isset($data['system_integrator_id']) ? App::escape($data['system_integrator_id']) : null;
		$holding_group_id = isset($data['holding_group_id']) ? App::escape($data['holding_group_id']) : null;
		if(!$id || !$system_integrator_id) {
			return $this->access_denied();
		}

		// Check permissions
		$perm = $id === 'new' ? Permission::get_system_integrator($system_integrator_id) : Permission::get_client($id);
		if(!$perm->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'name', 'image_id',
			'email_address', 'phone_number', 'mobile_number', 'vat_reg_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'invoice_address_line_1', 'invoice_address_line_2', 'invoice_address_line_3', 'invoice_posttown', 'invoice_postcode',
			'bank_name', 'bank_sort_code', 'bank_account_number'
		]);
		$record = App::ensure($record, ['name'], '');
		$record = App::ensure($record, ['image_id'], null);
		$record['bank_sort_code'] = str_replace('-','',$record['bank_sort_code']);

		// Data validation
		if($record['name'] === '') {
			return $this->error('Please enter client name.');
		}

		if($id === 'new' && $holding_group_id) {
			// Make sure user has admin access at the selected holding group and that the holding group is owned by the same system integrator
			$perm = Permission::get_holding_group($holding_group_id);
			if(!$perm->check(Permission::ADMIN)) return $this->access_denied();

			$hg = App::sql()->query_row("SELECT id FROM holding_group WHERE id = '$holding_group_id' AND system_integrator_id = '$system_integrator_id';");
			if(!$hg) return $this->error('Holding group must be owned by the same system integrator.');
		}

		// Remove usage from previous user content
		$register_image = true;

		if($id !== 'new') {
			$original = App::sql()->query_row("SELECT image_id FROM client WHERE id = '$id';");
			if($original) {
				$image_id = $original->image_id;
				if($image_id == $record['image_id']) {
					// No change
					$register_image = false;
				} else if($image_id) {
					$uc = new UserContent($image_id);
					$uc->remove_usage();
					$record['logo_img'] = null;
				}
			}
		}

		// Insert/update record
		if($id === 'new') {
			$record['system_integrator_id'] = $system_integrator_id;
			if($holding_group_id) $record['holding_group_id'] = $holding_group_id;
		}
		$id = App::upsert('client', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Add usage to new image if any
		if($record['image_id'] && $register_image) {
			$uc = new UserContent($record['image_id']);
			$uc->add_usage();

			// Update the BLOB field for John's report `client`.`logo_img`
			$originalpath = $uc->get_path();
			$filename = basename($originalpath);
			$fullpath = "/tmp/$filename";
			copy($originalpath, $fullpath);

			$size = filesize($fullpath);
			$dim = max(getimagesize($fullpath));

			while($size > 65535 && $dim > 10) {
				$dim = (int)($dim * 0.9);
				smart_resize_image($fullpath, null, $dim, $dim, true, $fullpath);

				$size = filesize($fullpath);
				$dim = max(getimagesize($fullpath));
			}

			if($dim > 10 && $size <= 65535) {
				$stmt = mysqli_prepare(App::sql()->linkId, "UPDATE client SET logo_img = ? WHERE id = '$id';");
				$null = NULL;
				$stmt->bind_param("b", $null);
				$stmt->send_long_data(0, file_get_contents($fullpath));
				$stmt->execute();
			}
		}

		if(isset($data['permissions'])) $this->save_admin_permissions($data['permissions'], PermissionLevel::CLIENT, $id);

		return $this->success($id);
	}

	public function list_buildings() {
		list($filter, $id) = App::get(['filter', 'id'], '', true);

		$options = [ 'with' => Permission::ADMIN ];
		if($filter && $id) {
			$options['filter_level'] = $filter;
			$options['id'] = $id;
		}

		$result = [
			'list' => Permission::list_buildings($options)
		];

		if(Permission::get_eticom()->check(Permission::ADMIN)) {
			$result['system_integrator_admin'] = true;
			$result['configurator_base_url'] = APP_URL.'/configurator?id=';
		} else if($filter === PermissionLevel::CLIENT) {
			// Frontend needs to know if user is an SI admin
			$si_admin = false;
			$r = App::sql()->query_row("SELECT system_integrator_id FROM client WHERE id = '$id';");
			if($r) {
				$perm = Permission::get_system_integrator($r->system_integrator_id);
				$si_admin = $perm->check(Permission::ADMIN);
			}
			$result['system_integrator_admin'] = $si_admin;
			$result['configurator_base_url'] = APP_URL.'/configurator?id=';
		}

		return $this->success($result);
	}

	public function list_building_floors() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		return $this->success([
			'list' => App::sql()->query("SELECT * FROM floor WHERE building_id = '$id' ORDER BY display_order;") ?: []
		]);
	}

	public function list_floor_areas() {
		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		return $this->success([
			'list' => App::sql()->query("SELECT * FROM area WHERE floor_id = '$id' ORDER BY display_order;") ?: []
		]);
	}

	public function list_areas() {
		list($filter, $id) = App::get(['filter', 'id'], '', true);

		$options = [ 'with' => Permission::ADMIN ];
		if($filter && $id) {
			$options['filter_level'] = $filter;
			$options['id'] = $id;
		}

		return $this->success([
			'list' => Permission::list_areas($options)
		]);
	}

	public function get_building() {
		$id = App::get('id', 0, true);
		if(!$id || !Permission::get_building($id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$result = [
			'breadcrumbs' => $this->get_breadcrumbs(PermissionLevel::BUILDING, $id)
		];

		$q = "SELECT
				id, client_id, description, building_type, timezone,
				address, posttown, postcode, latitude, longitude,
				image_id, burn_rate_widget_title,
				em_lighting_function_test_frequency, em_lighting_duration_test_frequency,
				debug, is_demo, allow_report,
				module_electricity
			FROM building
			WHERE id = '$id';
		";
		$result['details'] = App::sql()->query_row($q, MySQL::QUERY_ASSOC) ?: null;
		$result['permissions'] = $this->get_admin_permissions(PermissionLevel::BUILDING, $id);

		if(!$result['details']) return $this->error('Building not found.');

		// Frontend needs to know if user is an SI admin
		$si_admin = false;
		$client_id = $result['details']['client_id'];
		$r = App::sql()->query_row("SELECT system_integrator_id FROM client WHERE id = '$client_id';");
		if($r) {
			$perm = Permission::get_system_integrator($r->system_integrator_id);
			$si_admin = $perm->check(Permission::ADMIN);
		}
		$result['system_integrator_admin'] = $si_admin;
		$result['configurator_url'] = APP_URL.'/configurator?id='.$id;

		// Get category settings
		$list = App::sql()->query("SELECT category_id FROM building_category_settings WHERE building_id = '$id' AND hide_from_electricity_widget = 1;", MySQL::QUERY_ASSOC) ?: [];
		$hidden_categories = [];
		foreach($list as $category) {
			$hidden_categories[] = $category['category_id'];
		}
		$result['details']['hidden_categories'] = $hidden_categories;

		// Resolve building image
		$image_url = null;
		if($result['details']['image_id']) {
			$uc = new UserContent($result['details']['image_id']);
			if($uc->info) $image_url = $uc->get_url();
		}
		$result['image_url'] = $image_url;

		// Get list of CT categories
		$categories = App::sql()->query(
			"SELECT
				id, description
			FROM category
			WHERE
				id IN (SELECT DISTINCT category_id FROM ct_category WHERE building_id = '$id')
				AND id <> 11
			ORDER BY description;
		", MySQL::QUERY_ASSOC);
		$result['categories'] = $categories ?: [];

		return $this->success($result);
	}

	public function new_building() {
		$level = App::get('level', '', true);
		$id = App::get('id', 0, true);
		if(!$id || $level != PermissionLevel::CLIENT) return $this->access_denied();

		// Resolve system integrator
		$si_id = null;
		$r = App::sql()->query_row("SELECT system_integrator_id FROM client WHERE id = '$id';");
		if($r) $si_id = $r->system_integrator_id;
		if(!$si_id) return $this->access_denied();

		$permission = Permission::get(PermissionLevel::SYSTEM_INTEGRATOR, $si_id);
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs($level, $id);
		$crumbs[] = [ 'description' => 'New Site' ];

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => [
				'id' => 'new',
				'client_id' => $id,
				'module_electricity' => 1,
				'hidden_categories' => []
			],
			'image_url' => null,
			'permissions' => $this->get_admin_permissions($level, $id, PermissionLevel::BUILDING),
			'categories' => []
		];

		return $this->success($result);
	}

	public function save_building() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? App::escape($data['id']) : null;
		$client_id = isset($data['client_id']) ? App::escape($data['client_id']) : null;
		if(!$id || !$client_id) return $this->access_denied();

		// Resolve system integrator
		$system_integrator_id = null;
		$r = App::sql()->query_row("SELECT system_integrator_id FROM client WHERE id = '$client_id';");
		if($r) $system_integrator_id = $r->system_integrator_id;
		if(!$system_integrator_id) return $this->access_denied(); // Unable to resolve associated SI

		// Check permissions
		$si_perm = Permission::get_system_integrator($system_integrator_id);
		$perm = $id === 'new' ? $si_perm : Permission::get_building($id);
		if(!$perm->check(Permission::ADMIN)) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'description', 'building_type', 'timezone',
			'address', 'posttown', 'postcode', 'latitude', 'longitude',
			'image_id', 'burn_rate_widget_title',
			'em_lighting_function_test_frequency', 'em_lighting_duration_test_frequency',
			'allow_report'
		]);
		if(isset($record['allow_report']) && !$si_perm->check(Permission::ADMIN)) unset($record['allow_report']);
		$record = App::ensure($record, ['description'], '');
		$record = App::ensure($record, ['image_id'], null);
		$record = App::ensure($record, ['latitude', 'longitude'], 0);

		// Null location fields if both are zero
		if($record['latitude'] == 0 && $record['longitude'] == 0) {
			$record['latitude'] = null;
			$record['longitude'] = null;
		}

		// Data validation
		if($record['description'] === '') {
			return $this->error('Please enter site description.');
		}

		// Remove usage from previous user content
		$register_image = true;

		if($id !== 'new') {
			$original = App::sql()->query_row("SELECT image_id FROM building WHERE id = '$id';");
			if($original) {
				$image_id = $original->image_id;
				if($image_id == $record['image_id']) {
					// No change
					$register_image = false;
				} else if($image_id) {
					$uc = new UserContent($image_id);
					$uc->remove_usage();
				}
			}
		}

		// Insert/update record
		if($id === 'new') $record['client_id'] = $client_id;
		$id = App::upsert('building', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Add usage to new image if any
		if($record['image_id'] && $register_image) {
			$uc = new UserContent($record['image_id']);
			$uc->add_usage();
		}

		if(isset($data['permissions'])) $this->save_admin_permissions($data['permissions'], PermissionLevel::BUILDING, $id);

		// Update CT settings
		if(isset($data['hidden_categories']) && is_array($data['hidden_categories'])) {
			App::sql()->update("UPDATE building_category_settings SET hide_from_electricity_widget = 0 WHERE building_id = '$id';");
			foreach($data['hidden_categories'] as $cat_id) {
				App::sql()->insert(
					"INSERT INTO building_category_settings (building_id, category_id, hide_from_electricity_widget) VALUES ('$id', '$cat_id', 1)
					ON DUPLICATE KEY UPDATE hide_from_electricity_widget = 1;
				");
			}
		}

		return $this->success($id);
	}

	public function list_users() {
		$user = App::user();
		$level = App::get('filter', '', true);
		$no_access = App::get('no_access', '');
		$id = App::get('id', 0, true);

		if((!$id && $level !== PermissionLevel::ETICOM) || !Permission::get($level, $id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Select all users which have roles set from the current level
		// It means that users with higher roles specified on lower levels will not be shown on the lower level
		// e.g. user will not show up for client A if it has "Eticom User" role specified on client A's level, but it WILL show up directly under Eticom

		if($level === 'B') {
			// Show area users on building level
			if($no_access) {
				$access_condition = "(ur.owner_level IN ('B', 'A') OR ur.id = 0)";
			} else {
				$access_condition = "(ur.owner_level IN ('B', 'A') AND ur.id <> 0)";
			}

			$q = "SELECT
					u.id, u.name, u.email_addr, u.mobile_no, u.active,
					ur.description AS role_description,
					MIN(IF(u.id = '$user->id', '1', '')) AS is_me,
					MIN(IF(ur.id = '0', '1', '')) AS no_access,
					GROUP_CONCAT(a.description ORDER BY a.description SEPARATOR ', ') AS area_description
				FROM user_role_assignment AS ura
				JOIN user_role AS ur ON ur.id = ura.user_role_id
				JOIN userdb AS u ON u.id = ura.user_id
				LEFT JOIN area AS a ON ura.assigned_level = 'A' AND ura.assigned_id = a.id
				LEFT JOIN floor AS f ON f.id = a.floor_id
				WHERE ((ura.assigned_level = 'B' AND ura.assigned_id = '$id') OR f.building_id = '$id') AND $access_condition
				GROUP BY
					u.id, u.name, u.email_addr, u.mobile_no, u.active,
					ur.description
				ORDER BY u.name;
			";
		} else {
			if($no_access) {
				$access_condition = '(ur.owner_level = ura.assigned_level OR ur.id = 0)';
			} else {
				$access_condition = '(ur.owner_level = ura.assigned_level AND ur.id <> 0)';
			}

			$q = "SELECT
					u.id, u.name, u.email_addr, u.mobile_no, u.active,
					ur.description AS role_description,
					IF(u.id = '$user->id', '1', '') AS is_me,
					IF(ur.id = '0', '1', '') AS no_access
				FROM user_role_assignment AS ura
				JOIN user_role AS ur ON ur.id = ura.user_role_id
				JOIN userdb AS u ON u.id = ura.user_id
				WHERE ura.assigned_level = '$level' AND ura.assigned_id = '$id' AND $access_condition
				ORDER BY u.name;
			";
		}

		$result = [
			'list' => App::sql()->query($q) ?: []
		];

		return $this->success($result);
	}

	public function list_all_users() {
		$user = App::user();
		$no_access = App::get('no_access', '');

		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		if($no_access) {
			$access_condition = '';
		} else {
			$access_condition = "WHERE ur.id IS NOT NULL AND ur.id <> '0'";
		}

		// Select all users, listing their highest permission level along with info about the first permission object set

		$q = "SELECT
				u.id, u.name, u.email_addr, u.mobile_no, u.active,
				COALESCE(ur.description, 'No Access') AS role_description,
				IF(u.id = '$user->id', '1', '') AS is_me,
				IF(ur.id IS NULL OR ur.id = '0', '1', '') AS no_access,
				highest.level, highest.level_id,
				IF(highest.level = 'E', 'Eticom', COALESCE(sp.company_name, si.company_name, hg.company_name, c.name, b.description, a.description)) AS level_description
			FROM userdb AS u
			LEFT JOIN (
				SELECT
					minlevel.user_id,
					ura.assigned_level AS level,
					MIN(ura.assigned_id) AS level_id
				FROM (SELECT user_id, MIN(assigned_level + 0) AS level FROM user_role_assignment WHERE user_role_id <> 0 GROUP BY user_id) AS minlevel
				JOIN user_role_assignment AS ura ON ura.user_id = minlevel.user_id AND ura.assigned_level = minlevel.level AND user_role_id <> 0
				GROUP BY minlevel.user_id, minlevel.level
			) AS highest ON u.id = highest.user_id
			LEFT JOIN user_role_assignment AS ura ON u.id = ura.user_id AND ura.assigned_level = highest.level AND ura.assigned_id = highest.level_id
			LEFT JOIN user_role AS ur ON ur.id = ura.user_role_id

			LEFT JOIN service_provider AS sp ON highest.level = 'SP' AND highest.level_id = sp.id
			LEFT JOIN system_integrator AS si ON highest.level = 'SI' AND highest.level_id = si.id
			LEFT JOIN holding_group AS hg ON highest.level = 'HG' AND highest.level_id = hg.id
			LEFT JOIN client AS c ON highest.level = 'C' AND highest.level_id = c.id
			LEFT JOIN building AS b ON highest.level = 'B' AND highest.level_id = b.id
			LEFT JOIN area AS a ON highest.level = 'A' AND highest.level_id = a.id

			$access_condition

			ORDER BY u.name;
		";

		$list = App::sql()->query($q, MySQL::QUERY_ASSOC) ?: [];

		$result = [];
		foreach($list as $item) {
			$level = $item['level'];
			$item['level_name'] = PermissionLevel::description($level);
			$item['level_icon'] = PermissionLevel::icon($level);
			$result[] = $item;
		}

		return $this->success([
			'list' => $result
		]);
	}

	public function list_user_roles() {
		$level = App::get('filter', '', true);
		$id = App::get('id', 0, true);
		if((!$id && $level !== PermissionLevel::ETICOM) || !Permission::get($level, $id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$q = "SELECT
				id, description, is_admin
			FROM user_role
			WHERE id <> 0 AND owner_level = '$level' AND owner_id = '$id' AND is_level_default = 0
			ORDER BY is_admin DESC, description;
		";

		$result = [
			'list' => App::sql()->query($q) ?: []
		];

		return $this->success($result);
	}

	public function get_user_role_defaults() {
		// Editing role defaults is for Eticom root users only!
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs(PermissionLevel::ETICOM, 0);
		$crumbs[] = [ 'description' => 'Permission levels' ];

		$ui_permission = new Permission();
		$ui_permission->allow_all_permissions();

		$levels = [];
		foreach(PermissionLevel::all() as $l) {
			$levels[] = [
				'id' => $l,
				'description' => PermissionLevel::description($l),
				'filter' => Permission::get_permission_level_filter($l)->get_fields()
			];
		}

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => $ui_permission->get_ui(true, Permission::get_permission_level_defaults()),
			'levels' => $levels
		];

		return $this->success($result);
	}

	public function save_user_role_defaults() {
		$data = App::json();

		// Editing role defaults is for Eticom root users only!
		if(!Permission::get_eticom()->check(Permission::ADMIN)) return $this->access_denied();

		//
		// Process defaults
		//

		foreach(PermissionLevel::all() as $level) {

			$perm = new Permission();
			$perm->allow_permission(Permission::ADMIN);

			foreach($data['details'] as $module) {
				$field = $module['toggle']['field'];
				$flag = $module['toggle']['flag'];
				$min_level = $module['toggle']['min_level'];
				if(PermissionLevel::lte($level, $min_level)) $perm->allow_field($field, $flag);

				foreach($module['options'] as $option) {
					$field = $option['field'];
					$flag = $option['flag'];
					$min_level = $option['min_level'];
					if(PermissionLevel::lte($level, $min_level)) $perm->allow_field($field, $flag);
				}
			}

			// Get ID of level defaults record
			$record = App::sql()->query_row("SELECT id FROM user_role WHERE owner_level = '$level' AND is_level_default = 1 LIMIT 1;");

			// Insert/update record
			if($record) {
				$result = App::update('user_role', $record->id, array_merge($perm->get_fields(), [
					'is_admin' => 1
				]));
			} else {
				$result = App::insert('user_role', array_merge($perm->get_fields(), [
					'owner_level' => $level,
					'owner_id' => 0,
					'is_admin' => 1,
					'is_level_default' => 1,
					'description' => 'Permission level defaults'
				]));
			}
		}

		//
		// Process level filters
		//

		foreach($data['levels'] as $level) {
			$l = $level['id'];
			$perm = new Permission($level['filter']);
			$perm->allow_permission(Permission::ADMIN);

			// Get ID of level defaults record
			$record = App::sql()->query_row("SELECT id FROM user_role WHERE owner_level = '$l' AND is_level_default = 2 LIMIT 1;");

			// Insert/update record
			if($record) {
				$result = App::update('user_role', $record->id, array_merge($perm->get_fields(), [
					'is_admin' => 1
				]));
			} else {
				$result = App::insert('user_role', array_merge($perm->get_fields(), [
					'owner_level' => $l,
					'owner_id' => 0,
					'is_admin' => 1,
					'is_level_default' => 2,
					'description' => 'Permission level filter'
				]));
			}
		}

		return $this->success();
	}

	public function get_user_role() {
		$id = App::get('id', 0, true);
		if(!$id) {
			return $this->access_denied();
		}

		$record = App::sql()->query_row("SELECT * FROM user_role WHERE id = '$id' AND is_level_default = 0;");
		if(!$record) return $this->access_denied();

		$permission = Permission::get($record->owner_level, $record->owner_id);
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();
		if($record->is_admin && !Permission::is_higher_admin($record->owner_level, $record->owner_id)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs($record->owner_level, $record->owner_id);
		$crumbs[] = [ 'description' => $record->description ];

		$ui_permission = $permission->clone_object();
		$ui_permission->mix_least_permissive(Permission::get_level_admin_permissions($record->owner_level, $record->owner_id, !!$record->is_admin));
		$ui_permission->mix_least_permissive(Permission::get_permission_level_defaults()[$record->owner_level]);

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => $record,
			'ui' => $ui_permission->get_ui($record->owner_level == PermissionLevel::ETICOM && $record->is_admin)
		];

		return $this->success($result);
	}

	public function new_user_role() {
		$level = App::get('level', '', true);
		$id = App::get('id', 0, true);
		if((!$id && $level != PermissionLevel::ETICOM) || !$level) {
			return $this->access_denied();
		}

		$permission = Permission::get($level, $id);
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$crumbs = $this->get_breadcrumbs($level, $id);
		$crumbs[] = [ 'description' => 'New User Role' ];

		$ui_permission = $permission->clone_object();
		$ui_permission->mix_least_permissive(Permission::get_level_admin_permissions($level, $id));
		$ui_permission->mix_least_permissive(Permission::get_permission_level_defaults()[$level]);

		$result = [
			'breadcrumbs' => $crumbs,
			'details' => array_merge($permission->get_fields(), [
				'id' => 'new',
				'owner_level' => $level,
				'owner_id' => $id,
				'is_admin' => 0,
				'description' => ''
			]),
			'ui' => $ui_permission->get_ui()
		];

		return $this->success($result);
	}

	public function save_user_role() {
		$data = App::json();

		// Check if ID is set
		$id = isset($data['id']) ? $data['id'] : null;
		$owner_level = isset($data['owner_level']) ? $data['owner_level'] : null;
		$owner_id = isset($data['owner_id']) ? $data['owner_id'] : null;
		if(!$id || !$owner_level || $owner_id === null) {
			return $this->access_denied();
		}

		// Check permissions
		$perm = Permission::get($owner_level, $owner_id);
		if(!$perm->check(Permission::ADMIN)) return $this->access_denied();
		$is_admin = isset($data['is_admin']) ? $data['is_admin'] : false;
		if($is_admin && !Permission::is_higher_admin($owner_level, $owner_id)) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, array_merge(['owner_level', 'owner_id', 'description'], Permission::get_field_list()));

		// Data validation
		if($record['description'] === '') {
			return $this->error('Please enter user role name.');
		}

		// Insert/update record
		$id = App::upsert('user_role', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function new_user() {
		$user = App::user();
		$data = App::json();

		$level = isset($data['level']) ? App::escape($data['level']) : '';
		$level_id = isset($data['id']) ? App::escape($data['id']) : '';
		if($level) {
			// User must be an admin on the selected permission level
			if(!Permission::get($level, $level_id)->check(Permission::ADMIN)) return $this->access_denied();
		}

		if(!isset($data['email'])) return $this->error('Please enter email address.');
		$email = trim(strtolower($data['email']));
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return $this->error('Please enter a valid email address.');

		$crumbs = $level ? $this->get_breadcrumbs($level, $level_id) : [];
		$crumbs[] = [ 'description' => 'New User' ];

		$result = [
			'breadcrumbs' => $crumbs,
			'access' => [
				'data' => true,
				'role' => true
			]
		];

		$result['details'] = [
			'id' => 'new',
			'name' => '',
			'email_addr' => $email,
			'mobile_no' => ''
		];
		$result['levels'] = [];

		if($level) {
			$level_details = $this->get_role_level_details($level, $level_id);
			if($level_details) {
				// Set new flag
				$level_details['original'] = null;

				$result['levels'][] = $level_details;
			}
		}

		return $this->success($result);
	}

	public function get_user() {
		$user = App::user();
		$id = App::get('id', 0, true);
		$level = App::get('level', '', true);
		$level_id = App::get('level_id', 0, true);
		if(!$id) return $this->access_denied();

		if($level) {
			// User must be an admin on the selected permission level
			if(!Permission::get($level, $level_id)->check(Permission::ADMIN)) return $this->access_denied();
		}

		$access = $this->get_user_access($id);
		$data_access = $access['data_access'];
		$role_access = $access['role_access'];

		$q = "SELECT
				id, name, email_addr, mobile_no
			FROM userdb
			WHERE id = '$id';
		";
		$details = App::sql()->query_row($q);

		$crumbs = $level ? $this->get_breadcrumbs($level, $level_id) : [];
		$crumbs[] = [ 'description' => $details->name ];

		$result = [
			'breadcrumbs' => $crumbs,
			'access' => [
				'data' => $data_access,
				'role' => $role_access
			]
		];

		if(!$details) {
			return $this->access_denied();
		} else if($data_access || $role_access) {
			$result['details'] = $details;
		} else {
			$result['details'] = [
				'id' => $details->id,
				'name' => $details->name,
				'email_addr' => $details->email_addr
			];
		}

		if($role_access) {
			$levels = [];

			// Select all roles set for the user on all levels
			$assignments = App::sql()->query(
				"SELECT
					ura.assigned_level, ura.assigned_id, ura.user_role_id,
					ur.owner_level AS role_owner_level,
					ur.owner_id AS role_owner_id
				FROM user_role_assignment AS ura
				JOIN user_role AS ur ON ur.id = ura.user_role_id
				WHERE user_id = '$id';"
			) ?: [];

			$found = false;
			foreach($assignments as $a) {
				// Check if we have found the current level
				if($level && $level == $a->assigned_level && $level_id == $a->assigned_id) $found = true;

				// User needs to be an admin on both the assignment level and on the currently selected role's level
				$allowed = Permission::get($a->assigned_level, $a->assigned_id)->check(Permission::ADMIN);
				if($a->user_role_id != 0) $allowed = $allowed && Permission::get($a->role_owner_level, $a->role_owner_id)->check(Permission::ADMIN);
				if($allowed) {
					$level_details = $this->get_role_level_details($a->assigned_level, $a->assigned_id, $a->user_role_id);
					if($level_details) $levels[] = $level_details;
				}
			}

			if($level && !$found) {
				// There is no role set on the current level, add blank role
				$level_details = $this->get_role_level_details($level, $level_id);
				if($level_details) {
					$level_details['original'] = null; // Set new flag
					$levels[] = $level_details;
				}
			}

			$result['levels'] = $levels;
		}

		$result['current_user'] = $user->id == $id;

		return $this->success($result);
	}

	public function save_user() {
		$user = App::user();
		$data = App::json();

		// Check if ID is set
		if(!isset($data['details'])) return $this->access_denied();

		$id = isset($data['details']['id']) ? $data['details']['id'] : null;
		if(!$id) return $this->access_denied();

		$orig_user = null;

		if($id == 'new') {
			// Check if user has admin access at any level
			$r = App::sql()->query("SELECT * FROM user_role_assignment AS ura JOIN user_role AS ur ON ur.id = ura.user_role_id AND ur.is_admin = 1 WHERE ura.user_id = '$user->id' LIMIT 1;");
			if(!$r) return $this->access_denied();

			$data_access = true;
			$role_access = true;
		} else {
			// Check if user has data or role access
			$access = $this->get_user_access($id);
			$data_access = $access['data_access'];
			$role_access = $access['role_access'];

			$orig_user = new User($id);
			if(!$orig_user->info) return $this->access_denied();
		}

		if(!$data_access && !$role_access) return $this->access_denied();

		if($data_access) {
			$new_session_password = false;

			// Create record
			$record = $data['details'];
			$record = App::keep($record, ['name', 'email_addr', 'mobile_no', 'password', 'new_password', 'new_password_conf']);
			$record = App::ensure($record, ['name', 'email_addr', 'password', 'new_password', 'new_password_conf'], '');

			// Data validation
			if($record['name'] === '') return $this->error('Please enter the user\'s full name.');
			if($record['email_addr'] === '' || !filter_var($record['email_addr'], FILTER_VALIDATE_EMAIL)) return $this->error('Please enter a valid email address.');

			// Process password update (if any)
			if($record['new_password'] !== '' || $record['new_password_conf'] !== '') {
				// Password is changing
				if($user->id == $id && !password_verify($record['password'], $orig_user->info->password)) return $this->error('Old password is incorrect.');
				unset($record['password']);
				if($record['new_password'] != $record['new_password_conf']) return $this->error('Please enter the same password twice to confirm.');
				if(strlen($record['new_password']) < 7) return $this->error('Password must be at least 7 characters long.');

				$record['password'] = password_hash($record['new_password'], PASSWORD_DEFAULT);

				// If the current user is changing his own password, set up session variables correctly
				if($_SESSION[SESSION_NAME_USER_ID] == $id) {
					$new_session_password = $record['password'];
				}

				// Invalidate all tokens if user has changed his password
				if($user->id == $id) {
					$user->revoke_tokens();
				}

				unset($record['new_password']);
				unset($record['new_password_conf']);
			} else {
				// No change, unset fields
				unset($record['password']);
				unset($record['new_password']);
				unset($record['new_password_conf']);

				if($id === 'new') return $this->error('Please enter password for the new user.');
			}

			// If email is changing or it's a new user, make sure email address is unique
			$record['email_addr'] = trim(strtolower($record['email_addr']));
			$email = $record['email_addr'];
			$email = App::escape($email);
			if($id === 'new') {
				$r = App::sql()->query("SELECT id FROM userdb WHERE email_addr = '$email';");
				if($r) return $this->error('That email address is already in use by another user. Please enter a unique email address.');
			} else if($orig_user->info->email_addr !== $record['email_addr']) {
				$r = App::sql()->query("SELECT id FROM userdb WHERE email_addr = '$email' AND id <> '$id';");
				if($r) return $this->error('That email address is already in use by another user. Please enter a unique email address.');
			} else {
				// No need to update email
				unset($record['email_addr']);
			}

			// Insert/update record
			$id = App::upsert('userdb', $id, $record);
			if(!$id) return $this->error('Error saving data.');

			if($new_session_password) $_SESSION[SESSION_NAME_PASSWORD] = $new_session_password;
		}

		if($role_access && $id !== 'new') {
			// Update user roles
			foreach($data['roles']['deleted'] as $item) {
				$level = App::escape($item['level']);
				$level_id = App::escape($item['id']);
				App::sql()->delete("DELETE FROM user_role_assignment WHERE user_id = '$id' AND assigned_level = '$level' AND assigned_id = '$level_id';");
			}
			foreach($data['roles']['modified'] as $item) {
				$level = App::escape($item['level']);
				$level_id = App::escape($item['id']);
				$role_id = App::escape($item['role_id']);
				App::sql()->update("UPDATE user_role_assignment SET user_role_id = '$role_id' WHERE user_id = '$id' AND assigned_level = '$level' AND assigned_id = '$level_id';");
			}
			foreach($data['roles']['added'] as $item) {
				$level = App::escape($item['level']);
				$level_id = App::escape($item['id']);
				$role_id = App::escape($item['role_id']);
				App::sql()->insert("INSERT INTO user_role_assignment (user_id, assigned_level, assigned_id, user_role_id) VALUES ('$id', '$level', '$level_id', '$role_id');");
			}
		}

		return $this->success($id);
	}

	public function get_permission_level_details() {
		$level = App::get('level', '', true);
		$id = App::get('id', 0, true);
		if(!$level) return $this->access_denied();

		if(!Permission::get($level, $id)->check(Permission::ADMIN)) return $this->access_denied();

		$details = $this->get_role_level_details($level, $id);
		if(!$details) return $this->access_denied();

		// Set new flag
		$details['original'] = null;

		return $this->success($details);
	}

	public function get_select_levels() {
		$user = App::user();

		// Find the highest admin level for the admin navigation items
		$highest_admin = App::sql()->query_row(
			"SELECT
				ura.assigned_level AS level,
				COUNT(*) AS cnt
			FROM user_role_assignment AS ura
			JOIN user_role AS ur ON ur.id = ura.user_role_id
			WHERE ura.user_id = '$user->id' AND ur.is_admin = 1
			GROUP BY level
			HAVING cnt > 0
			ORDER BY level
			LIMIT 1;"
		);

		$result = [];
		if($highest_admin) {
			$top = $highest_admin->level;
			foreach(PermissionLevel::all() as $l) {
				if(PermissionLevel::lte($top, $l)) $result[] = $l;
			}
		}

		return $this->success($result);
	}

	public function get_user_id_by_email() {
		$data = App::json();

		if(!isset($data['email'])) return $this->error('Please enter a valid email address.');
		$email = trim(strtolower($data['email']));

		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return $this->error('Please enter a valid email address.');

		$email = App::escape($email);
		$r = App::sql()->query_row("SELECT id FROM userdb WHERE email_addr = '$email' LIMIT 1;");
		return $this->success($r ? $r->id : 'new');
	}

	public function get_current_user_id() {
		return $this->success(App::user()->id);
	}

	public function get_new_user_crumbs() {
		$level = App::get('level', '');
		$level_id = App::get('id', 0);

		$crumbs = $level ? $this->get_breadcrumbs($level, $level_id) : [];
		$crumbs[] = [ 'description' => 'New User' ];

		return $this->success([ 'breadcrumbs' => $crumbs ]);
	}

	public function update_smtp() {
		$data = App::json();

		$data = App::keep($data, ['owner_type', 'owner_id', 'host', 'port', 'user', 'password', 'secure', 'default_from_address']);
		$data = App::ensure($data, ['owner_type', 'owner_id', 'host', 'port', 'user', 'password', 'secure', 'default_from_address'], '');

		$owner_type = $data['owner_type'];
		$owner_id = $data['owner_id'];
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Data validation

		if(!$data['host']) return $this->error('Please enter SMTP host.');
		if(!$data['port']) return $this->error('Please enter port number.');
		if(!$data['default_from_address']) return $this->error('Please enter default from address.');

		$owner_type = App::escape($owner_type);
		$owner_id = App::escape($owner_id);

		App::sql()->delete("DELETE FROM email_smtp WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';");
		App::insert('email_smtp', $data);

		return $this->success();
	}

	public function list_email_templates() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$perm = Permission::get($owner_type, $owner_id);
		if(!$perm->check(Permission::SETTINGS_ENABLED) && !$perm->check(Permission::ISP_ENABLED)) {
			return $this->access_denied();
		}

		$list = App::sql()->query(
			"SELECT *
			FROM email_template
			WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';
		", MySQL::QUERY_ASSOC) ?: [];

		$smtp = App::sql()->query_row(
			"SELECT *
			FROM email_smtp
			WHERE owner_type = '$owner_type' AND owner_id = '$owner_id'
			LIMIT 1;
		");

		$custom = [
			'custom_1' => '',
			'custom_2' => '',
			'custom_3' => '',
			'custom_4' => '',
			'custom_5' => '',
			'custom_6' => '',
			'custom_7' => '',
			'custom_8' => '',
			'custom_9' => '',
			'custom_10' => ''
		];

		$found = [];

		foreach($list as $t) {
			$found[$t['template_type']] = true;
			if(isset($custom[$t['template_type']])) {
				$custom[$t['template_type']] = $t['subject'];
			}
		}

		$custom_list = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

		return $this->success([
			'list' => $list,
			'templates' => [
				[
					'id' => 'isp_not_signed',
					'name' => 'Signature required',
					'description' => 'Welcome email sent when a new contract is created with the "Not Signed" status.',
					'is_set' => isset($found['isp_not_signed'])
				],
				[
					'id' => 'isp_welcome',
					'name' => 'Welcome',
					'description' => 'Welcome email for contracts without Direct Debit. Sent when the first invoice is approved.',
					'is_set' => isset($found['isp_welcome'])
				],
				[
					'id' => 'isp_welcome_dd',
					'name' => 'Welcome (with Direct Debit)',
					'description' => 'Welcome email for contracts with Direct Debit. Sent when the first invoice is approved.',
					'is_set' => isset($found['isp_welcome_dd'])
				],
				[
					'id' => 'isp_activate',
					'name' => 'Activation',
					'description' => 'Sent when a contract and all associated services get activated.',
					'is_set' => isset($found['isp_activate'])
				],
				[
					'id' => 'isp_invoice',
					'name' => 'Invoice',
					'description' => 'Customer invoice email for contracts without Direct Debit.',
					'is_set' => isset($found['isp_invoice'])
				],
				[
					'id' => 'isp_invoice_dd',
					'name' => 'Invoice (with Direct Debit)',
					'description' => 'Customer invoice email for contracts with Direct Debit.',
					'is_set' => isset($found['isp_invoice_dd'])
				],
				[
					'id' => 'isp_dd_fail',
					'name' => 'Direct Debit failure',
					'description' => 'Customer email when Direct Debit fails and there is no card payment fallback.',
					'is_set' => isset($found['isp_dd_fail'])
				],
				[
					'id' => 'isp_dd_fail_card',
					'name' => 'Direct Debit failure (will charge card)',
					'description' => 'Customer email when Direct Debit fails and card is about to be charged.',
					'is_set' => isset($found['isp_dd_fail_card'])
				],
				[
					'id' => 'isp_card_fail',
					'name' => 'Automatic card payment failed',
					'description' => 'Customer email when automatic card payment has failed (after a failed Direct Debit).',
					'is_set' => isset($found['isp_card_fail'])
				],
				[
					'id' => 'isp_card_expires',
					'name' => 'Card is about to expire',
					'description' => 'Warns the customer that their card will expire at the end of the month.',
					'is_set' => isset($found['isp_card_expires'])
				],
				[
					'id' => 'isp_card_expired',
					'name' => 'Card expired and removed',
					'description' => 'Warns the customer that their card has expired and has been removed from the system.',
					'is_set' => isset($found['isp_card_expired'])
				],
				[
					'id' => 'isp_dd_cancelled',
					'name' => 'Direct Debit cancelled',
					'description' => 'Warns the customer that their Direct Debit mandate has been cancelled.',
					'is_set' => isset($found['isp_dd_cancelled'])
				]
			],
			'templates_custom' => array_map(function($i) use ($custom, $found) {
				return [
					'id' => "custom_$i",
					'name' => "#$i".($custom["custom_$i"] ? ' - '.$custom["custom_$i"] : ''),
					'description' => $custom["custom_$i"],
					'is_set' => isset($found["custom_$i"])
				];
			}, $custom_list),
			'smtp' => $smtp ?: [
				'owner_type' => $owner_type,
				'owner_id' => $owner_id,
				'host' => '',
				'port' => '587',
				'user' => '',
				'password' => '',
				'secure' => 'tls',
				'default_from_address' => ''
			]
		]);
	}

	public function get_email_template() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$template = App::get('template', '', true);
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$record = App::sql()->query_row("SELECT * FROM email_template WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND template_type = '$template';", MySQL::QUERY_ASSOC);
		if(!$record) $record = [
			'owner_type' => $owner_type,
			'owner_id' => $owner_id,
			'template_type' => $template,
			'from_address' => '',
			'subject' => '',
			'body' => ''
		];

		$asset_list = [];
		$assets = App::sql()->query("SELECT user_content_id FROM email_assets WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';", MySQL::QUERY_ASSOC) ?: [];
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

		return $this->success([
			'details' => $record
		]);
	}

	public function save_email_template() {
		$data = App::json();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_type', 'owner_id', 'template_type', 'from_address', 'subject', 'body']);
		$record = App::ensure($record, ['owner_type', 'owner_id', 'template_type', 'from_address', 'subject', 'body'], '');

		// Check permissions
		$owner_type = $record['owner_type'];
		$owner_id = $record['owner_id'];
		$template_type = $record['template_type'];
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Insert/update record
		$owner_type = App::escape($owner_type);
		$owner_id = App::escape($owner_id);
		$template_type = App::escape($template_type);
		App::sql()->delete("DELETE FROM email_template WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND template_type = '$template_type';");
		App::insert('email_template', $record);

		// Delete old assets
		App::sql()->delete("DELETE FROM email_assets WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';");

		// Save module assets
		$assets = $data['assets'] ?: [];
		foreach($assets as $a) {
			$asset_id = App::escape($a['user_content_id']);
			App::sql()->insert("INSERT INTO email_assets (owner_type, owner_id, user_content_id) VALUES ('$owner_type', '$owner_id', '$asset_id');");
		}

		return $this->success();
	}

	public function delete_email_template() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$template = App::get('template', '', true);
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}
		App::sql()->delete("DELETE FROM email_template WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND template_type = '$template';");

		return $this->success();
	}

	public function list_contract_templates() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$perm = Permission::get($owner_type, $owner_id);
		if(!$perm->check(Permission::SETTINGS_ENABLED) && !$perm->check(Permission::ISP_ENABLED)) {
			return $this->access_denied();
		}

		$list = App::sql()->query(
			"SELECT *
			FROM pdf_contract_template
			WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND archived = 0;
		", MySQL::QUERY_ASSOC) ?: [];

		return $this->success([
			'list' => $list
		]);
	}

	public function new_contract_template() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$record = [
			'id' => 'new',
			'owner_type' => $owner_type,
			'owner_id' => $owner_id,
			'name' => '',
			'html' => '',
			'archived' => 0
		];

		$asset_list = [];
		$assets = App::sql()->query("SELECT user_content_id FROM email_assets WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';", MySQL::QUERY_ASSOC) ?: [];
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

		return $this->success([
			'details' => $record
		]);
	}

	public function get_contract_template() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$id = App::get('id', '', true);
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		$record = App::sql()->query_row("SELECT * FROM pdf_contract_template WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND id = '$id';", MySQL::QUERY_ASSOC);
		if(!$record) return $this->access_denied();

		$asset_list = [];
		$assets = App::sql()->query("SELECT user_content_id FROM email_assets WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';", MySQL::QUERY_ASSOC) ?: [];
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

		return $this->success([
			'details' => $record
		]);
	}

	public function save_contract_template() {
		$data = App::json();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_type', 'owner_id', 'id', 'name', 'html']);
		$record = App::ensure($record, ['owner_type', 'owner_id', 'id', 'name', 'html'], '');

		// Check permissions
		$owner_type = $record['owner_type'];
		$owner_id = $record['owner_id'];
		$id = $record['id'];
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Insert/update record
		$owner_type = App::escape($owner_type);
		$owner_id = App::escape($owner_id);
		$id = App::escape($id);
		unset($record['id']);
		App::upsert('pdf_contract_template', $id, $record);

		// Delete old assets
		App::sql()->delete("DELETE FROM email_assets WHERE owner_type = '$owner_type' AND owner_id = '$owner_id';");

		// Save module assets
		$assets = $data['assets'] ?: [];
		foreach($assets as $a) {
			$asset_id = App::escape($a['user_content_id']);
			App::sql()->insert("INSERT INTO email_assets (owner_type, owner_id, user_content_id) VALUES ('$owner_type', '$owner_id', '$asset_id');");
		}

		return $this->success();
	}

	public function delete_contract_template() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$id = App::get('id', '', true);
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		App::update('pdf_contract_template', $id, [
			'archived' => 1
		]);

		return $this->success();
	}

	public function list_payment_gateways() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		// Refresh GoCardless verification status

		$list = App::sql()->query(
			"SELECT id
			FROM payment_gateway
			WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND archived = 0 AND type = 'gocardless' AND authorised = 1;
		") ?: [];

		foreach($list as $gc) {
			try {
				$pg = new PaymentGateway($gc->id);
				$pg->get_gocardless_verification_status();
			} catch(Exception $ex) { }
		}

		$settings = PaymentGateway::get_gocardless_settings();

		// Return list of payment gateways

		$q = "SELECT
				id, type, description, date_created, authorised,
				gocardless_status
			FROM payment_gateway
			WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND archived = 0
			ORDER BY type, description;
		";

		return $this->success([
			'list' => App::sql()->query($q) ?: [],
			'gocardless_verification_url' => $settings['verification_url']
		]);
	}

	public function new_payment_gateway() {
		$owner_type = App::get('owner_type', '', true);
		$owner_id = App::get('owner_id', 0, true);
		$type = App::get('type', '');
		if((!$owner_id && $owner_type !== PermissionLevel::ETICOM) || !Permission::get($owner_type, $owner_id)->check(Permission::ADMIN)) {
			return $this->access_denied();
		}

		if(!PaymentGateway::is_valid_type($type)) return $this->error('Invalid payment gateway type.');

		return $this->success([
			'record' => PaymentGateway::new_record($owner_type, $owner_id, $type)
		]);
	}

	public function get_payment_gateway() {
		$id = App::get('id', 0);
		$pg = new PaymentGateway($id);
		if(!$pg->is_valid() || !$pg->user_has_access()) return $this->access_denied();

		return $this->success([
			'record' => $pg->record
		]);
	}

	public function save_payment_gateway() {
		$id = App::get('id', 0);
		$data = App::json();

		$record = App::keep($data, ['owner_type', 'owner_id', 'type', 'description', 'allow_part_payment', 'part_minimum_pence']);
		$record = App::ensure($record, ['owner_type', 'owner_id', 'type', 'description'], '');
		$record = App::ensure($record, ['allow_part_payment', 'part_minimum_pence'], 0);

		// Data validation
		if(!PaymentGateway::is_valid_type($record['type'])) return $this->error('Invalid payment gateway type.');
		if(!$record['description']) return $this->error('Please enter payment gateway description.');

		if($record['type'] !== 'stripe') {
			$record['allow_part_payment'] = 0;
			$record['part_minimum_pence'] = 0;
		}

		if($id === 'new') {

			// Create new payment gateway
			if((!$record['owner_id'] && $record['owner_type'] !== PermissionLevel::ETICOM) || !Permission::get($record['owner_type'], $record['owner_id'])->check(Permission::ADMIN)) {
				return $this->access_denied();
			}
			$pg = PaymentGateway::create($record);
			if(!$pg) return $this->error('Error creating payment gateway.');

			return $this->success($pg->id);

		} else {

			// Update existing payment gateway
			$pg = new PaymentGateway($id);
			if(!$pg->is_valid() || !$pg->user_has_access()) return $this->access_denied();
			App::update('payment_gateway', $id, [
				'description' => $record['description'],
				'allow_part_payment' => $record['allow_part_payment'],
				'part_minimum_pence' => $record['part_minimum_pence']
			]);

			return $this->success($id);

		}
	}

	public function authorise_payment_gateway() {
		$id = App::get('id', 0);
		$pg = new PaymentGateway($id);
		if(!$pg->is_valid() || !$pg->user_has_access()) return $this->access_denied();
		if($pg->is_authorised()) return $this->error('Payment gateway has already been authorised.');
		if($pg->is_archived()) return $this->error('Payment gateway has been archived.');

		$url = $pg->get_authorisation_url();
		if(!$url) return $this->error('Error authorising payment gateway.');

		return $this->success($url);
	}

	public function list_smoothpower_updates() {
		$permission = Permission::get_eticom();
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$channels = [
			[ 'id' => 'release', 'description' => 'Release packages' ],
			[ 'id' => 'test', 'description' => 'Test packages' ]
		];
		$list = [];

		$sub_dir = '/smoothpower_update';
		$dir = USER_CONTENT_PATH.$sub_dir;

		foreach($channels as $channel) {
			$updates = App::sql()->query("SELECT * FROM smoothpower_update WHERE channel = '$channel[id]' ORDER BY version DESC;", MySQL::QUERY_ASSOC) ?: [];
			foreach($updates as &$update) {
				$update['build_datetime'] = date('Y-m-d H:i:s', $update['version']);
				$update['filebytes'] = filesize("$dir/smoothpower-$update[version].tar.gz");
				$update['filesize'] = App::human_filesize($update['filebytes']);
			}
			unset($update);
			$list[$channel['id']] = $updates;
		}

		return $this->success([
			'channels' => $channels,
			'list' => $list
		]);
	}

	public function set_smoothpower_rollback() {
		$permission = Permission::get_eticom();
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$id = App::get('id', 0, true);
		$value = App::get('value', 0, true);

		App::update('smoothpower_update', $id, [
			'rollback' => $value
		]);

		return $this->success();
	}

	public function set_smoothpower_channel() {
		$permission = Permission::get_eticom();
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$id = App::get('id', 0, true);
		$value = App::get('value', 0, true);

		$record = App::select('smoothpower_update', $id);

		$existing = App::sql()->query("SELECT id FROM smoothpower_update WHERE version = '$record[version]' AND channel = '$value';");
		if($existing) return $this->error("Version $record[version] is already in the channel.");

		$new_id = App::insert('smoothpower_update', [
			'version' => $record['version'],
			'channel' => $value,
			'datetime' => App::now(),
			'rollback' => $record['rollback'],
			'notes' => $record['notes']
		]);

		return $this->success($new_id);
	}

	public function delete_smoothpower_update() {
		$permission = Permission::get_eticom();
		if(!$permission->check(Permission::ADMIN)) return $this->access_denied();

		$id = App::get('id', 0, true);
		$record = App::select('smoothpower_update', $id);
		if(!$record) return $this->error('Package not found.');

		App::delete('smoothpower_update', $id);

		$version = $record['version'];
		$left = App::sql()->query("SELECT id FROM smoothpower_update WHERE version = '$version';");
		if(!$left) {
			// All instances of this version has been deleted, remove package file.
			$sub_dir = '/smoothpower_update';
			$dir = USER_CONTENT_PATH.$sub_dir;
			$fullpath = "$dir/smoothpower-$version.tar.gz";
			unlink($fullpath);
		}

		return $this->success();
	}

}
