<?php

class PermissionLevel {
	const ETICOM = 'E';
	const SERVICE_PROVIDER = 'SP';
	const SYSTEM_INTEGRATOR = 'SI';
	const HOLDING_GROUP = 'HG';
	const CLIENT = 'C';
	const BUILDING = 'B';
	const AREA = 'A';

	private static $list = [
		self::ETICOM,
		self::SERVICE_PROVIDER,
		self::SYSTEM_INTEGRATOR,
		self::HOLDING_GROUP,
		self::CLIENT,
		self::BUILDING,
		self::AREA
	];

	private static $info = [
		self::ETICOM => [
			'order' => 1,
			'description' => 'Eticom',
			'icon' => 'md md-grade'
		],
		self::SERVICE_PROVIDER => [
			'order' => 2,
			'description' => 'Service Provider',
			'icon' => 'md md-filter-drama'
		],
		self::SYSTEM_INTEGRATOR => [
			'order' => 3,
			'description' => 'System Integrator',
			'icon' => 'md md-local-shipping'
		],
		self::HOLDING_GROUP => [
			'order' => 4,
			'description' => 'Holding Group',
			'icon' => 'md md-group-work'
		],
		self::CLIENT => [
			'order' => 5,
			'description' => 'Client',
			'icon' => 'md md-work'
		],
		self::BUILDING => [
			'order' => 6,
			'description' => 'Building',
			'icon' => 'md md-place'
		],
		self::AREA => [
			'order' => 7,
			'description' => 'Area',
			'icon' => 'md md-dashboard'
		]
	];

	public static function all() {
		return self::$list;
	}

	public static function info($level) {
		return isset(self::$info[$level]) ? self::$info[$level] : null;
	}

	public static function order($level) {
		return isset(self::$info[$level]) ? self::$info[$level]['order'] : -1;
	}

	public static function range($level_from, $level_to) {
		$from = self::order($level_from);
		$to = self::order($level_to);
		if($from == -1 || $to == -1) return [];

		if($from > $to) list($from, $to) = [$to, $from];
		return array_slice(self::$list, $from, $to - $from + 1);
	}

	public static function description($level) {
		$info = self::info($level);
		return $info ? $info['description'] : '';
	}

	public static function icon($level) {
		$info = self::info($level);
		return $info ? $info['icon'] : '';
	}

	public static function eq($a, $b) { return $a == $b; }
	public static function lt($a, $b) { return self::order($a) < self::order($b); }
	public static function lte($a, $b) { return self::eq($a, $b) || self::lt($a, $b); }
	public static function gt($a, $b) { return self::order($a) > self::order($b); }
	public static function gte($a, $b) { return self::eq($a, $b) || self::gt($a, $b); }
}

class Permission {

	// DUE TO MYSQL'S BIT_AND OPERATION, YOU CAN'T USE THE FULL 64 BITS FOR PERMISSIONS
	// This means the highest permission level must be 2^61
	// Permission field 2^64-1 (all 64 bits set to 1) will be detected as 0 = no access
	// Same issue with 2^63-1 which is sometimes returned.
	// SHEZ Permissions added are not ID values instead represent a BIT value based on the db fields.
	const MYSQL_BIT_AND_BUG = ['18446744073709551615', '9223372036854775807'];

	const ADMIN = [0, 'is_admin', 1];

	const ELECTRICITY_ENABLED = [Module::ELECTRICITY, 'electricity_permissions', 1, 'Electricity Dashboard'];
	const ELECTRICITY_COST = [Module::ELECTRICITY, 'electricity_permissions', 2, 'Show costs'];

	const GAS_ENABLED = [Module::GAS, 'gas_permissions', 1, 'Gas Dashboard'];

	const WATER_ENABLED = [Module::WATER, 'water_permissions', 1, 'Water Dashboard'];

	const RENEWABLES_ENABLED = [Module::RENEWABLES, 'renewables_permissions', 1, 'Renewables Dashboard'];

	const METERS_ENABLED = [Module::METERS, 'meters_permissions', 1, 'Multi Meter Manager'];
	const METERS_ADD_READING = [Module::METERS, 'meters_permissions', 2, 'Add manual meter readings'];

	const EMERGENCY_ENABLED = [Module::EMERGENCY, 'emergency_permissions', 1, 'Emergency Lights Dashboard'];
	const EMERGENCY_EDIT_GROUP_DESCRIPTION = [Module::EMERGENCY, 'emergency_permissions', 2, 'Change light group description'];
	const EMERGENCY_EDIT_GROUP_SCHEDULE = [Module::EMERGENCY, 'emergency_permissions', 4, 'Change light group schedule'];
	const EMERGENCY_EDIT_GROUP_ASSIGNMENT = [Module::EMERGENCY, 'emergency_permissions', 8, 'Assign/unassign light groups'];
	const EMERGENCY_LIGHT_MAINTENANCE = [Module::EMERGENCY, 'emergency_permissions', 16, 'Maintain/repair emergency lights'];

	const BUILDING_ENABLED = [Module::BUILDING, 'building_permissions', 1, 'Building Manager'];

	const REPORTS_ENABLED = [Module::REPORTS, 'reports_permissions', 1, 'Reports'];

	const SALES_ENABLED = [Module::SALES, 'sales_permissions', 1, 'Sales'];
	const SALES_ALL_RECORDS = [Module::SALES, 'sales_permissions', 2, 'Full access to all customers/projects'];
	const SALES_PRICING = [Module::SALES, 'sales_permissions', 4, 'Access to project pricing'];

	const ISP_ENABLED = [Module::ISP, 'isp_permissions', 1, 'ISP'];

	const CLIMATE_ENABLED = [Module::CLIMATE, 'climate_permissions', 1, 'Climate Control'];

	const RELAY_ENABLED = [Module::RELAY, 'relay_permissions', 1, 'Relay Control'];

	const STOCK_ENABLED = [Module::STOCK, 'stock_permissions', 1, 'Stock'];
	const STOCK_LABOUR_PRICE = [Module::STOCK, 'stock_permissions', 2, 'Access to labour pricing'];
	const STOCK_SUBSCRIPTION_PRICE = [Module::STOCK, 'stock_permissions', 4, 'Access to subscription pricing'];
	const STOCK_VALUE = [Module::STOCK, 'stock_permissions', 8, 'Value of stock'];

	const SMOOTHPOWER_ENABLED = [Module::SMOOTHPOWER, 'smoothpower_permissions', 1, 'SmoothPower'];

	const LIGHTING_ENABLED = [Module::LIGHTING, 'lighting_permissions', 1, 'Lighting Control'];

	const BILLING_ENABLED = [Module::BILLING, 'billing_permissions', 1, 'Billing'];

	const SETTINGS_ENABLED = [Module::SETTINGS, 'settings_permissions', 1, 'Settings'];

	const CONTROL_ENABLED = [Module::CONTROL, 'control_permissions', 1, 'KNX Control'];

	const SECURITY_ENABLED = [Module::SECURITY, 'security_permissions', 1, 'Security Control'];

	const SURVEILLANCE_ENABLED = [Module::SURVEILLANCE, 'surveillance_permissions', 1, 'Surveillance Control'];

	const FIRE_ENABLED = [Module::FIRE, 'fire_permissions', 1, 'Fire Control'];

	const EVCHARGER_ENABLED = [Module::EVCHARGER, 'evcharger_permissions', 1, 'Ev Charger'];
	
	const ACCESS_ENABLED = [Module::ACCESS, 'access_permissions', 1, 'Access'];

	const MULTISENSE_ENABLED = [Module::MULTISENSE, 'multisense_permissions', 1, 'MultiSense'];

	// Permissions in the order shown on the UI
	// They must be sorted by module, with the ENABLED flag on top!
	private static $permission_ui_list = [
		self::ELECTRICITY_ENABLED,
		self::ELECTRICITY_COST,
		self::GAS_ENABLED,
		self::WATER_ENABLED,
		self::RENEWABLES_ENABLED,
		self::METERS_ENABLED,
		self::METERS_ADD_READING,
		self::EMERGENCY_ENABLED,
		self::EMERGENCY_EDIT_GROUP_DESCRIPTION,
		self::EMERGENCY_EDIT_GROUP_SCHEDULE,
		self::EMERGENCY_EDIT_GROUP_ASSIGNMENT,
		self::EMERGENCY_LIGHT_MAINTENANCE,
		self::BUILDING_ENABLED,
		self::REPORTS_ENABLED,
		self::SALES_ENABLED,
		self::SALES_ALL_RECORDS,
		self::SALES_PRICING,
		self::ISP_ENABLED,
		self::CLIMATE_ENABLED,
		self::RELAY_ENABLED,
		self::STOCK_ENABLED,
		self::STOCK_LABOUR_PRICE,
		self::STOCK_SUBSCRIPTION_PRICE,
		self::STOCK_VALUE,
		self::SMOOTHPOWER_ENABLED,
		self::LIGHTING_ENABLED,
		self::BILLING_ENABLED,
		self::CONTROL_ENABLED,
		self::SECURITY_ENABLED,
		self::SURVEILLANCE_ENABLED,
		self::SETTINGS_ENABLED,
		self::FIRE_ENABLED,
		self::ACCESS_ENABLED,
		self::EVCHARGER_ENABLED,
		self::MULTISENSE_ENABLED

	];

	private static $permission_fields = [
		'electricity_permissions',
		'gas_permissions',
		'water_permissions',
		'renewables_permissions',
		'meters_permissions',
		'emergency_permissions',
		'building_permissions',
		'reports_permissions',
		'sales_permissions',
		'isp_permissions',
		'climate_permissions',
		'relay_permissions',
		'stock_permissions',
		'smoothpower_permissions',
		'lighting_permissions',
		'billing_permissions',
		'control_permissions',
		'security_permissions',
		'surveillance_permissions',
		'settings_permissions',
		'fire_permissions',
		'access_permissions',
		'evcharger_permissions',
		'multisense_permissions'
		
	];

	// The building record itself can disable certain modules depending on their configuration
	// These fields are usually evaluated when the configuration is updated
	private static $building_dependencies = [
		'electricity_permissions' => 'module_electricity',
		'gas_permissions' => 'module_gas',
		'water_permissions' => 'module_water',
		'renewables_permissions' => 'module_renewables',
		'meters_permissions' => 'module_meters',
		'emergency_permissions' => 'module_emergency',
		'climate_permissions' => 'module_climate',
		'relay_permissions' => 'module_relay',
		'smoothpower_permissions' => 'module_smoothpower',
		'lighting_permissions' => 'module_lighting',
		'building_permissions' => 'module_building',
		'control_permissions' => 'module_control',
		'reports_permissions' => 'module_reports',
		'surveillance_permissions'=>'module_surveillance',
		'security_permissions'=>'module_security',
		'fire_permissions'=>'module_fire',
		'access_permissions'=>'module_access',
		'evcharger_permissions' => 'module_evcharger',
		'multisense_permissions' => 'module_multisense'
	];

	private static $sp_dependencies = [ 'billing_permissions' => 'module_billing' ];
	private static $si_dependencies = [ 'billing_permissions' => 'module_billing', 'sales_permissions' => 'module_sales', 'stock_permissions' => 'module_stock' ];
	private static $hg_dependencies = [ 'billing_permissions' => 'module_billing' ];
	private static $c_dependencies = [ 'billing_permissions' => 'module_billing' ];

	private static $get_cache = [];

	public static function get_field_list() {
		return self::$permission_fields;
	}

	public static function select($options = []) {
		// Resolve options
		$user = App::user();
		$level = PermissionLevel::BUILDING;
		$with_any = false;
		$with = [];
		$id = 0;

		if(isset($options['user'])) $user = $options['user'];
		if(isset($options['level'])) $level = $options['level'];
		if(isset($options['id'])) $id = App::escape($options['id']);
		$filter_level = $level;
		if(isset($options['filter_level'])) $filter_level = $options['filter_level'];
		if(isset($options['with'])) {
			$with = $options['with'];
			if(!is_array($with[0])) $with = [$with];
		}
		if(isset($options['with_any'])) {
			$with_any = !!$options['with_any'];
		}

		$user_id = 0;
		if($user) $user_id = $user->id;

		//
		// Build query
		//
		// The query is a 3-level sub-query:
		//
		// OUTER SELECT (            <- Merges inner select permission values with higher level admins to make sure access is not greater
		//     INNER_SELECT (        <- Extracts permission values for the role found in initial select (overlays building dependencies if needed)
		//         INITIAL_SELECT    <- Finds the most specific level that a role has been defined for
		//     )
		// )
		//

		// List of level-dependent fields in the final result
		$outer_fields = '';
		if(PermissionLevel::gte($level, PermissionLevel::AREA)) $outer_fields .= 'perm.area_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::BUILDING)) $outer_fields .= 'perm.building_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::CLIENT)) $outer_fields .= 'perm.client_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::HOLDING_GROUP)) $outer_fields .= 'perm.holding_group_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::SYSTEM_INTEGRATOR)) $outer_fields .= 'perm.system_integrator_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::SERVICE_PROVIDER)) $outer_fields .= 'perm.service_provider_id, ';

		// Merges permissions for all admin roles from the user's effective level up
		// This makes it possible to globally deny certain permissions at a higher level
		$outer_merge = [];
		foreach(self::$permission_fields as $perm) {
			$outer_merge[] = "perm.$perm & BIT_AND(ar.$perm) AS $perm";
		}
		$outer_merge = implode(', ',$outer_merge);

		// Selects permissions from the user's effective role at the specified level
		$inner_permissions = [];
		foreach(self::$permission_fields as $perm) {
			if(($level === PermissionLevel::BUILDING || $level === PermissionLevel::AREA) && isset(self::$building_dependencies[$perm])) {
				$dep = self::$building_dependencies[$perm];
				$inner_permissions[] = "IF(b.$dep, ur.$perm, 0) AS $perm";
			} else if($level === PermissionLevel::SERVICE_PROVIDER && isset(self::$sp_dependencies[$perm])) {
				$dep = self::$sp_dependencies[$perm];
				$inner_permissions[] = "IF(sp.$dep, ur.$perm, 0) AS $perm";
			} else if($level === PermissionLevel::SYSTEM_INTEGRATOR && isset(self::$si_dependencies[$perm])) {
				$dep = self::$si_dependencies[$perm];
				$inner_permissions[] = "IF(si.$dep, ur.$perm, 0) AS $perm";
			} else if($level === PermissionLevel::HOLDING_GROUP && isset(self::$hg_dependencies[$perm])) {
				$dep = self::$hg_dependencies[$perm];
				$inner_permissions[] = "IF(hg.$dep, ur.$perm, 0) AS $perm";
			} else if($level === PermissionLevel::CLIENT && isset(self::$c_dependencies[$perm])) {
				$dep = self::$c_dependencies[$perm];
				$inner_permissions[] = "IF(c.$dep, ur.$perm, 0) AS $perm";
			} else {
				$inner_permissions[] = "ur.$perm";
			}
		}
		$inner_permissions = implode(', ',$inner_permissions);

		// List of level-dependent fields in the initial (innermost) select
		$initial_fields = '';
		if(PermissionLevel::gte($level, PermissionLevel::AREA)) $initial_fields .= 'a.id AS area_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::BUILDING)) $initial_fields .= 'b.id AS building_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::CLIENT)) $initial_fields .= 'c.id AS client_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::HOLDING_GROUP)) $initial_fields .= 'hg.id AS holding_group_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::SYSTEM_INTEGRATOR)) $initial_fields .= 'si.id AS system_integrator_id, ';
		if(PermissionLevel::gte($level, PermissionLevel::SERVICE_PROVIDER)) $initial_fields .= 'sp.id AS service_provider_id, ';

		// Table includes depending on level
		$initial_tables = '';
		switch($level) {
			case PermissionLevel::AREA:
				$initial_tables = "
					FROM area AS a
					JOIN floor AS f ON f.id = a.floor_id
					JOIN building AS b ON b.id = f.building_id
					JOIN client AS c ON c.id = b.client_id
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
				";
				break;
			case PermissionLevel::BUILDING:
				$initial_tables = "
					FROM building AS b
					JOIN client AS c ON c.id = b.client_id
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
				";
				break;
			case PermissionLevel::CLIENT:
				$initial_tables = "
					FROM client AS c
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
				";
				break;
			case PermissionLevel::HOLDING_GROUP:
				$initial_tables = "
					FROM holding_group AS hg
					JOIN system_integrator AS si ON si.id = hg.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
				";
				break;
			case PermissionLevel::SYSTEM_INTEGRATOR:
				$initial_tables = "
					FROM system_integrator AS si
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
				";
				break;
			case PermissionLevel::SERVICE_PROVIDER:
				$initial_tables = "
					FROM service_provider AS sp
				";
				break;
			case PermissionLevel::ETICOM:
				// Ugly hack, but works
				// We need a single row for Eticom we can join stuff to
				$initial_tables = "
					FROM (SELECT 1) AS eticom
				";
				break;
		}

		// Initial role join depending on level
		$initial_role_join = '';
		if(PermissionLevel::gte($level, PermissionLevel::AREA)) $initial_role_join .= "(ura.assigned_level = 'A' AND ura.assigned_id = a.id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::BUILDING)) $initial_role_join .= "(ura.assigned_level = 'B' AND ura.assigned_id = b.id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::CLIENT)) $initial_role_join .= "(ura.assigned_level = 'C' AND ura.assigned_id = c.id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::HOLDING_GROUP)) $initial_role_join .= "(ura.assigned_level = 'HG' AND ura.assigned_id = hg.id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::SYSTEM_INTEGRATOR)) $initial_role_join .= "(ura.assigned_level = 'SI' AND ura.assigned_id = si.id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::SERVICE_PROVIDER)) $initial_role_join .= "(ura.assigned_level = 'SP' AND ura.assigned_id = sp.id) OR ";

		// Optional filter for the initial (innermost) select
		// Used to select a single ID if needed
		// Available tables (depending on level):
		//    building AS `b`
		//    client AS `c`
		//    holding_group AS `hg`
		//    system_integrator AS `si`
		//    service_provider AS `sp`
		$initial_filter = [];
		if($id) {
			switch($filter_level) {
				case PermissionLevel::AREA:
					$initial_filter[] = "a.id = '$id'";
					break;
				case PermissionLevel::BUILDING:
					$initial_filter[] = "b.id = '$id'";
					break;
				case PermissionLevel::CLIENT:
					$initial_filter[] = "c.id = '$id'";
					break;
				case PermissionLevel::HOLDING_GROUP:
					$initial_filter[] = "hg.id = '$id'";
					break;
				case PermissionLevel::SYSTEM_INTEGRATOR:
					$initial_filter[] = "si.id = '$id'";
					break;
				case PermissionLevel::SERVICE_PROVIDER:
					$initial_filter[] = "sp.id = '$id'";
					break;
			}
		}
		if(count($initial_filter)) {
			$initial_filter = 'WHERE '.implode(' AND ', $initial_filter);
		} else {
			$initial_filter = '';
		}

		// Initial group by clause
		$initial_group = [];
		if(PermissionLevel::gte($level, PermissionLevel::AREA)) $initial_group[] = 'area_id';
		if(PermissionLevel::gte($level, PermissionLevel::BUILDING)) $initial_group[] = 'building_id';
		if(PermissionLevel::gte($level, PermissionLevel::CLIENT)) $initial_group[] = 'client_id';
		if(PermissionLevel::gte($level, PermissionLevel::HOLDING_GROUP)) $initial_group[] = 'holding_group_id';
		if(PermissionLevel::gte($level, PermissionLevel::SYSTEM_INTEGRATOR)) $initial_group[] = 'system_integrator_id';
		if(PermissionLevel::gte($level, PermissionLevel::SERVICE_PROVIDER)) $initial_group[] = 'service_provider_id';
		$initial_group = count($initial_group) ? 'GROUP BY '.implode(', ', $initial_group) : '';

		// Inner role join depending on level
		$inner_role_join = '';
		if(PermissionLevel::gte($level, PermissionLevel::AREA)) $inner_role_join .= "(lvl.assigned_level = 7 AND ura.assigned_level = 'A' AND ura.assigned_id = lvl.area_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::BUILDING)) $inner_role_join .= "(lvl.assigned_level = 6 AND ura.assigned_level = 'B' AND ura.assigned_id = lvl.building_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::CLIENT)) $inner_role_join .= "(lvl.assigned_level = 5 AND ura.assigned_level = 'C' AND ura.assigned_id = lvl.client_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::HOLDING_GROUP)) $inner_role_join .= "(lvl.assigned_level = 4 AND ura.assigned_level = 'HG' AND ura.assigned_id = lvl.holding_group_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::SYSTEM_INTEGRATOR)) $inner_role_join .= "(lvl.assigned_level = 3 AND ura.assigned_level = 'SI' AND ura.assigned_id = lvl.system_integrator_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::SERVICE_PROVIDER)) $inner_role_join .= "(lvl.assigned_level = 2 AND ura.assigned_level = 'SP' AND ura.assigned_id = lvl.service_provider_id) OR ";

		// Inner dependency join to overlay permission dependencies
		$inner_dep_join = '';
		if($level === PermissionLevel::BUILDING || $level === PermissionLevel::AREA) $inner_dep_join = 'JOIN building AS b ON b.id = lvl.building_id';
		if($level === PermissionLevel::SERVICE_PROVIDER) $inner_dep_join = 'JOIN service_provider AS sp ON sp.id = lvl.service_provider_id';
		if($level === PermissionLevel::SYSTEM_INTEGRATOR) $inner_dep_join = 'JOIN system_integrator AS si ON si.id = lvl.system_integrator_id';
		if($level === PermissionLevel::HOLDING_GROUP) $inner_dep_join = 'JOIN holding_group AS hg ON hg.id = lvl.holding_group_id';
		if($level === PermissionLevel::CLIENT) $inner_dep_join = 'JOIN client AS c ON c.id = lvl.client_id';

		// Outer role join depending on level
		$outer_role_join = '';
		if(PermissionLevel::gte($level, PermissionLevel::AREA)) $outer_role_join .= "(ar.owner_level = 'A' AND ar.owner_id = perm.area_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::BUILDING)) $outer_role_join .= "(ar.owner_level = 'B' AND ar.owner_id = perm.building_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::CLIENT)) $outer_role_join .= "(ar.owner_level = 'C' AND ar.owner_id = perm.client_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::HOLDING_GROUP)) $outer_role_join .= "(ar.owner_level = 'HG' AND ar.owner_id = perm.holding_group_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::SYSTEM_INTEGRATOR)) $outer_role_join .= "(ar.owner_level = 'SI' AND ar.owner_id = perm.system_integrator_id) OR ";
		if(PermissionLevel::gte($level, PermissionLevel::SERVICE_PROVIDER)) $outer_role_join .= "(ar.owner_level = 'SP' AND ar.owner_id = perm.service_provider_id) OR ";

		// Outer select group by clause
		$outer_group = '';
		if(PermissionLevel::gte($level, PermissionLevel::AREA)) $outer_group .= "perm.area_id, ";
		if(PermissionLevel::gte($level, PermissionLevel::BUILDING)) $outer_group .= "perm.building_id, ";
		if(PermissionLevel::gte($level, PermissionLevel::CLIENT)) $outer_group .= "perm.client_id, ";
		if(PermissionLevel::gte($level, PermissionLevel::HOLDING_GROUP)) $outer_group .= "perm.holding_group_id, ";
		if(PermissionLevel::gte($level, PermissionLevel::SYSTEM_INTEGRATOR)) $outer_group .= "perm.system_integrator_id, ";
		if(PermissionLevel::gte($level, PermissionLevel::SERVICE_PROVIDER)) $outer_group .= "perm.service_provider_id, ";

		// Final filter of outer results after grouping
		$outer_having = [];
		if(count($with)) {
			foreach($with as $perm) {
				$field = $perm[1];
				$value = $perm[2];
				$outer_having[] = "$field & $value > 0";
			}
		}
		if(count($outer_having)) {
			$outer_having = 'HAVING '.implode($with_any ? ' OR ' : ' AND ', $outer_having);
		} else {
			$outer_having = '';
		}

		$q = "SELECT
				$outer_fields
				perm.assigned_level,
				perm.user_role_id,
				perm.is_admin,
				$outer_merge
			FROM (
				SELECT
					lvl.*,
					ura.user_role_id,
					ur.is_admin, ur.owner_level,
					$inner_permissions
				FROM (
					SELECT
						$initial_fields
						MAX(ura.assigned_level+0) AS assigned_level

					$initial_tables

					JOIN user_role_assignment AS ura ON ura.user_id = '$user_id' AND (
						$initial_role_join
						(ura.assigned_level = 'E')
					)
					$initial_filter
					$initial_group
				) AS lvl
				JOIN user_role_assignment AS ura ON ura.user_id = '$user_id' AND (
					$inner_role_join
					(lvl.assigned_level = 1 AND ura.assigned_level = 'E')
				)
				JOIN user_role AS ur ON ur.id = ura.user_role_id
				$inner_dep_join
				WHERE user_role_id <> 0
			) AS perm
			JOIN user_role AS ar ON (ar.is_admin AND ar.is_level_default = 0 AND ar.owner_level + 0 <= perm.owner_level + 0 AND (
				$outer_role_join
				(ar.owner_level = 'E')
			)) OR (ar.is_level_default = 1 AND ar.owner_level + 0 = perm.owner_level + 0)
			GROUP BY
				$outer_group
				perm.assigned_level,
				perm.user_role_id

			$outer_having
		";

		return $q;
	}

	public static function select_merge_least_permissive($options) {
		// Resolve options
		$level = PermissionLevel::BUILDING;
		if(isset($options['level'])) $level = $options['level'];

		$merge_fields = [];
		foreach(self::$permission_fields as $perm) {
			$merge_fields[] = "BIT_AND(merge.$perm) AS $perm";
		}
		$merge_fields = implode(', ',$merge_fields);

		$select = self::select($options);

		$q = "SELECT
				COUNT(*) AS merge_count,
				BIT_AND(merge.is_admin) AS is_admin,
				$merge_fields
			FROM ($select) AS merge";

		return $q;
	}

	public static function select_merge_most_permissive($options) {
		// Resolve options
		$level = PermissionLevel::BUILDING;
		if(isset($options['level'])) $level = $options['level'];

		$merge_fields = [];
		foreach(self::$permission_fields as $perm) {
			$merge_fields[] = "BIT_OR(merge.$perm) AS $perm";
		}
		$merge_fields = implode(', ',$merge_fields);

		$select = self::select($options);

		$q = "SELECT
				COUNT(*) AS merge_count,
				BIT_OR(merge.is_admin) AS is_admin,
				$merge_fields
			FROM ($select) AS merge";

		return $q;
	}

	public static function clear_cache() {
		self::$get_cache = [];
	}

	public static function any($options = []) {
		$cache_id = 'GENERAL_ANY';
		if(isset(self::$get_cache[$cache_id])) return self::$get_cache[$cache_id];

		// TODO: Make it more efficient (at the moment it pretty much selects EVERYTHING most of the time)

		// Look for any permission records from the bottom up
		// Fix: doesn't really matter in which order, have to loop through all anyway
		$sql = App::sql();
		$level_list = array_reverse(PermissionLevel::all());
		$perm = null;
		foreach($level_list as $level) {
			$filter = new Permission($sql->query_row("SELECT * FROM user_role WHERE owner_level = '$level' AND is_level_default = 2 LIMIT 1;", MySQL::QUERY_ASSOC));
			if($filter->has_access()) {
				$result = $sql->query_row(self::select_merge_most_permissive(array_merge($options, [ 'level' => $level ])));
				if($result && $result->merge_count > 0) {
					$level_perm = new Permission($result);
					$level_perm->mix_least_permissive($filter);
					if($perm) {
						$perm->mix_most_permissive($level_perm);
					} else {
						$perm = $level_perm;
					}
				}
			}
		}

		if(!$perm) $perm = new Permission(null);
		self::$get_cache[$cache_id] = $perm;
		return $perm;
	}

	public static function all($options = []) {
		$cache_id = 'GENERAL_ALL';
		if(isset(self::$get_cache[$cache_id])) return self::$get_cache[$cache_id];

		// TODO: Make it more efficient (at the moment it pretty much selects EVERYTHING most of the time)

		// Look for any permission records from the bottom up
		// Fix: doesn't really matter in which order, have to loop through all anyway
		$sql = App::sql();
		$level_list = array_reverse(PermissionLevel::all());
		$perm = null;
		foreach($level_list as $level) {
			$result = $sql->query_row(self::select_merge_least_permissive(array_merge($options, [ 'level' => $level ])));
			$filter = new Permission($sql->query_row("SELECT * FROM user_role WHERE owner_level = '$level' AND is_level_default = 2 LIMIT 1;", MySQL::QUERY_ASSOC));
			if($result && $result->merge_count > 0) {
				$level_perm = new Permission($result);
				$level_perm->mix_least_permissive($filter);
				if($perm) {
					$perm->mix_least_permissive($level_perm);
				} else {
					$perm = $level_perm;
				}
			}
		}

		if(!$perm) $perm = new Permission(null);
		self::$get_cache[$cache_id] = $perm;
		return $perm;
	}

	public static function get($level, $id = 0) {
		$cache_id = $level.$id;
		if(isset(self::$get_cache[$cache_id])) return self::$get_cache[$cache_id];

		$result = new Permission(App::sql()->query_row(self::select_merge_least_permissive([ 'level' => $level, 'id' => $id ])));
		self::$get_cache[$cache_id] = $result;
		return $result;
	}

	public static function get_area($id) { return self::get(PermissionLevel::AREA, $id); }
	public static function get_building($id) { return self::get(PermissionLevel::BUILDING, $id); }
	public static function get_client($id) { return self::get(PermissionLevel::CLIENT, $id); }
	public static function get_holding_group($id) { return self::get(PermissionLevel::HOLDING_GROUP, $id); }
	public static function get_system_integrator($id) { return self::get(PermissionLevel::SYSTEM_INTEGRATOR, $id); }
	public static function get_service_provider($id) { return self::get(PermissionLevel::SERVICE_PROVIDER, $id); }
	public static function get_eticom() { return self::get(PermissionLevel::ETICOM); }

	public static function find($options = []) {
		return App::sql()->query(self::select($options));
	}

	public static function find_areas($options = []) { return self::find(array_merge($options, [ 'level' => PermissionLevel::AREA ])); }
	public static function find_buildings($options = []) { return self::find(array_merge($options, [ 'level' => PermissionLevel::BUILDING ])); }
	public static function find_clients($options = []) { return self::find(array_merge($options, [ 'level' => PermissionLevel::CLIENT ])); }
	public static function find_holding_groups($options = []) { return self::find(array_merge($options, [ 'level' => PermissionLevel::HOLDING_GROUP ])); }
	public static function find_system_integrators($options = []) { return self::find(array_merge($options, [ 'level' => PermissionLevel::SYSTEM_INTEGRATOR ])); }
	public static function find_service_providers($options = []) { return self::find(array_merge($options, [ 'level' => PermissionLevel::SERVICE_PROVIDER ])); }

	public static function list_all($options = [], $condition = '') {
		$q = self::select($options);
		$q = "SELECT * FROM ($q) AS listres";

		if($condition) $condition = "AND ($condition)";

		switch($options['level']) {
			case PermissionLevel::AREA:
				$q .= " JOIN area ON area.id = listres.area_id $condition ORDER BY area.description";
				break;
			case PermissionLevel::BUILDING:
				$q .= " JOIN building ON building.id = listres.building_id $condition ORDER BY building.description";
				break;
			case PermissionLevel::CLIENT:
				$q .= " JOIN client ON client.id = listres.client_id $condition ORDER BY client.name";
				break;
			case PermissionLevel::HOLDING_GROUP:
				$q .= " JOIN holding_group ON holding_group.id = listres.holding_group_id $condition ORDER BY holding_group.company_name";
				break;
			case PermissionLevel::SYSTEM_INTEGRATOR:
				$q .= " JOIN system_integrator ON system_integrator.id = listres.system_integrator_id $condition ORDER BY system_integrator.company_name";
				break;
			case PermissionLevel::SERVICE_PROVIDER:
				$q .= " JOIN service_provider ON service_provider.id = listres.service_provider_id $condition ORDER BY service_provider.company_name";
				break;
		}
		$q .= ';';
		return App::sql()->query($q);
	}

	public static function list_areas($options = [], $condition = '') { return self::list_all(array_merge($options, [ 'level' => PermissionLevel::AREA ]), $condition); }
	public static function list_buildings($options = [], $condition = '') { return self::list_all(array_merge($options, [ 'level' => PermissionLevel::BUILDING ]), $condition); }
	public static function list_clients($options = [], $condition = '') { return self::list_all(array_merge($options, [ 'level' => PermissionLevel::CLIENT ]), $condition); }
	public static function list_holding_groups($options = [], $condition = '') { return self::list_all(array_merge($options, [ 'level' => PermissionLevel::HOLDING_GROUP ]), $condition); }
	public static function list_system_integrators($options = [], $condition = '') { return self::list_all(array_merge($options, [ 'level' => PermissionLevel::SYSTEM_INTEGRATOR ]), $condition); }
	public static function list_service_providers($options = [], $condition = '') { return self::list_all(array_merge($options, [ 'level' => PermissionLevel::SERVICE_PROVIDER ]), $condition); }

	public static function get_level_chain($level, $id) {
		$q = '';
		switch($level) {
			case PermissionLevel::AREA:
				$q = "SELECT
						a.id AS A_id,
						a.description AS A_description,
						b.id AS B_id,
						b.description AS B_description,
						c.id AS C_id,
						c.name AS C_description,
						hg.id AS HG_id,
						hg.company_name AS HG_description,
						si.id AS SI_id,
						si.company_name AS SI_description,
						sp.id AS SP_id,
						sp.company_name AS SP_description
					FROM area AS a
					JOIN floor AS f ON f.id = a.floor_id
					JOIN building AS b ON b.id = f.building_id
					JOIN client AS c ON c.id = b.client_id
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE a.id = '$id';
				";
				break;
			case PermissionLevel::BUILDING:
				$q = "SELECT
						b.id AS B_id,
						b.description AS B_description,
						c.id AS C_id,
						c.name AS C_description,
						hg.id AS HG_id,
						hg.company_name AS HG_description,
						si.id AS SI_id,
						si.company_name AS SI_description,
						sp.id AS SP_id,
						sp.company_name AS SP_description
					FROM building AS b
					JOIN client AS c ON c.id = b.client_id
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE b.id = '$id';
				";
				break;
			case PermissionLevel::CLIENT:
				$q = "SELECT
						c.id AS C_id,
						c.name AS C_description,
						hg.id AS HG_id,
						hg.company_name AS HG_description,
						si.id AS SI_id,
						si.company_name AS SI_description,
						sp.id AS SP_id,
						sp.company_name AS SP_description
					FROM client AS c
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE c.id = '$id';
				";
				break;
			case PermissionLevel::HOLDING_GROUP:
				$q = "SELECT
						hg.id AS HG_id,
						hg.company_name AS HG_description,
						si.id AS SI_id,
						si.company_name AS SI_description,
						sp.id AS SP_id,
						sp.company_name AS SP_description
					FROM holding_group AS hg
					JOIN system_integrator AS si ON si.id = hg.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE hg.id = '$id';
				";
				break;
			case PermissionLevel::SYSTEM_INTEGRATOR:
				$q = "SELECT
						si.id AS SI_id,
						si.company_name AS SI_description,
						sp.id AS SP_id,
						sp.company_name AS SP_description
					FROM system_integrator AS si
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE si.id = '$id';
				";
				break;
			case PermissionLevel::SERVICE_PROVIDER:
				$q = "SELECT
						sp.id AS SP_id,
						sp.company_name AS SP_description
					FROM service_provider AS sp
					WHERE sp.id = '$id';
				";
				break;
			case PermissionLevel::ETICOM:
				// No need to select eticom, it's ALWAYS just Eticom
				break;
		}

		if(!$q) return null;
		return App::sql()->query_row($q);
	}

	public static function get_admin_chain($level, $id, $user = null) {
		// Used primarily for displaying breadcrumb navigation
		// Given a permission object, returns a chain of objects with descriptions
		// up to the highest admin level

		if(!$user) $user = App::user();
		if(!$user) return []; // Unauthorised
		$id = App::escape($id);

		$r = self::get_level_chain($level, $id);
		$result = [ PermissionLevel::ETICOM => [ 'id' => 0, 'description' => 'Eticom', 'admin' => 0 ] ];

		if($r) {
			foreach(PermissionLevel::all() as $p) {
				if($p !== PermissionLevel::ETICOM && isset($r->{$p.'_id'})) {
					$p_id = $r->{$p.'_id'};
					$p_description = isset($r->{$p.'_description'}) ? $r->{$p.'_description'} : '';
					if($p_id) {
						$result[$p] = [
							'id' => $p_id,
							'description' => $p_description,
							'admin' => 0
						];
					}
				}
			}
		} else {
			if($level !== PermissionLevel::ETICOM) return [];
		}

		// We have a list of level names, populate admin flags
		$filter = [];
		foreach($result as $p => $v) {
			$pid = $v['id'];
			$filter[] = "(ura.assigned_level = '$p' AND ura.assigned_id = '$pid')";
		}
		$filter = implode(' OR ', $filter);

		$q = "SELECT
				ura.assigned_level, ur.is_admin
			FROM user_role_assignment AS ura
			JOIN user_role AS ur ON ur.id = ura.user_role_id
			WHERE ura.user_id = '$user->id' AND ($filter);
		";

		$r = App::sql()->query($q);
		$level_change = [];
		if($r) {
			foreach($r as $row) {
				$p = $row->assigned_level;
				$level_change[$p] = $row->is_admin;
			}
		}

		// Fill in level gaps which are not directly set
		$admin = 0;
		foreach(PermissionLevel::all() as $p) {
			if(isset($level_change[$p])) $admin = $level_change[$p];
			if(isset($result[$p])) $result[$p]['admin'] = $admin;
		}

		return $result;
	}

	// Returns true if user has higher admin privileges than what's passed
	// For example if client level is passed, it returns true if user is a holding group admin, but NOT if client admin is his highest level
	public static function is_higher_admin($level, $id, $user = null) {
		$chain = self::get_admin_chain($level, $id, $user);
		foreach($chain as $p => $v) {
			if($v['admin']) {
				if($p == PermissionLevel::ETICOM || PermissionLevel::lt($p, $level)) return true;
			}
		}
		return false;
	}

	public static function get_level_admin_permissions($level, $id = 0, $skip_current = false) {
		// Given a permission level, return all permission objects of matching or higher level admins
		// This function is independent of the actual logged in user

		$q = '';
		switch($level) {
			case PermissionLevel::AREA:
				$q = "SELECT
						a.id AS A_id,
						b.id AS B_id,
						c.id AS C_id,
						hg.id AS HG_id,
						si.id AS SI_id,
						sp.id AS SP_id
					FROM area AS a
					JOIN floor AS f ON f.id = a.floor_id
					JOIN building AS b ON b.id = f.building_id
					JOIN client AS c ON c.id = b.client_id
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE a.id = '$id';
				";
				break;
			case PermissionLevel::BUILDING:
				$q = "SELECT
						b.id AS B_id,
						c.id AS C_id,
						hg.id AS HG_id,
						si.id AS SI_id,
						sp.id AS SP_id
					FROM building AS b
					JOIN client AS c ON c.id = b.client_id
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE b.id = '$id';
				";
				break;
			case PermissionLevel::CLIENT:
				$q = "SELECT
						c.id AS C_id,
						hg.id AS HG_id,
						si.id AS SI_id,
						sp.id AS SP_id
					FROM client AS c
					LEFT JOIN holding_group AS hg ON hg.id = c.holding_group_id
					JOIN system_integrator AS si ON si.id = c.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE c.id = '$id';
				";
				break;
			case PermissionLevel::HOLDING_GROUP:
				$q = "SELECT
						hg.id AS HG_id,
						si.id AS SI_id,
						sp.id AS SP_id
					FROM holding_group AS hg
					JOIN system_integrator AS si ON si.id = hg.system_integrator_id
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE hg.id = '$id';
				";
				break;
			case PermissionLevel::SYSTEM_INTEGRATOR:
				$q = "SELECT
						si.id AS SI_id,
						sp.id AS SP_id
					FROM system_integrator AS si
					JOIN service_provider AS sp ON sp.id = si.service_provider_id
					WHERE si.id = '$id';
				";
				break;
			case PermissionLevel::SERVICE_PROVIDER:
				$q = "SELECT
						sp.id AS SP_id
					FROM service_provider AS sp
					WHERE sp.id = '$id';
				";
				break;
			case PermissionLevel::ETICOM:
				// No need to select eticom, it's ALWAYS just Eticom
				break;
		}

		$filters = [];

		if($q) {
			$r = App::sql()->query_row($q);
			if(!$r) return [];

			foreach(PermissionLevel::all() as $p) {
				if(isset($r->{$p.'_id'})) {
					$pid = $r->{$p.'_id'};
					$filters[] = "(owner_level = '$p' AND owner_id = '$pid')";
				}
			}
		}

		$filters[] = "(owner_level = 'E' AND owner_id = 0)";
		$filters = implode(' OR ', $filters);

		$q = "SELECT * FROM user_role WHERE is_admin = 1 AND is_level_default = 0 AND ($filters);";
		$r = App::sql()->query($q);

		$result = [];
		if($r) {
			foreach($r as $record) {
				if(!$skip_current || $record->owner_level != $level) {
					$result[] = new Permission($record);
				}
			}
		}

		return $result;
	}

	public static function get_permission_level_defaults() {
		$sql = App::sql();
		$result = [];

		foreach(PermissionLevel::all() as $level) {
			$record = $sql->query_row("SELECT * FROM user_role WHERE owner_level = '$level' AND is_level_default = 1 LIMIT 1;") ?: null;
			$result[$level] = new Permission($record);
		}

		return $result;
	}

	public static function get_permission_level_filter($level) {
		return new Permission(
			App::sql()->query_row("SELECT * FROM user_role WHERE owner_level = '$level' AND is_level_default = 2 LIMIT 1;", MySQL::QUERY_ASSOC)
		);
	}

	//
	// Instance
	//

	private $permissions = [];

	private static function mysql_bug_normalize($v) {
		if(in_array($v, self::MYSQL_BIT_AND_BUG)) return 0;
		return $v;
	}

	public function __construct($record = null) {
		// Initialise all permissions to zero
		$this->clear_all_permissions();

		// If record is false or null, return with all zero permissions
		if(!$record) return;

		// Process permissions from record
		if(is_array($record)) {
			foreach(self::$permission_fields as $perm) {
				if(isset($record[$perm])) $this->permissions[$perm] = self::mysql_bug_normalize($record[$perm]);
			}
			if(isset($record['is_admin'])) $this->permissions['is_admin'] = self::mysql_bug_normalize($record['is_admin']);
		} else {
			foreach(self::$permission_fields as $perm) {
				if(isset($record->$perm)) $this->permissions[$perm] = self::mysql_bug_normalize($record->$perm);
			}
			if(isset($record->is_admin)) $this->permissions['is_admin'] = self::mysql_bug_normalize($record->is_admin);
		}
	}

	public function clone_object() {
		return new self($this->permissions);
	}

	public function check($perm) {
		if(!is_array($perm)) return false;
		return ($this->permissions[$perm[1]] & $perm[2]) > 0;
	}


	public function check_all($array) {
		if(!is_array($array)) return false;
		foreach($array as $perm) {
			$result = $this->check($perm);
			if(!$result) return false;
		}
		return true;
	}

	public function check_any($array) {
		if(!is_array($array)) return false;
		foreach($array as $perm) {
			$result = $this->check($perm);
			if($result) return true;
		}
		return false;
	}

	public function api_check($perm, $permArray) {
		if(!is_array($perm)) return false;
		return ($permArray[$perm[1]] & $perm[2]) > 0;
	}

	public function has_no_access() {
		foreach($this->permissions as $p) {
			if($p > 0) return false;
		}
		return true;
	}

	public function has_access() {
		return !$this->has_no_access();
	}

	public function mix_least_permissive($permissions) {
		if(!is_array($permissions)) $permissions = [$permissions];

		foreach($permissions as $permission) {
			foreach(self::$permission_fields as $perm) {
				$this->permissions[$perm] &= $permission->permissions[$perm];
			}
			$this->permissions['is_admin'] &= $permission->permissions['is_admin'];
		}
	}

	public function mix_most_permissive($permissions) {
		if(!is_array($permissions)) $permissions = [$permissions];

		foreach($permissions as $permission) {
			foreach(self::$permission_fields as $perm) {
				$this->permissions[$perm] |= $permission->permissions[$perm];
			}
			$this->permissions['is_admin'] |= $permission->permissions['is_admin'];
		}
	}

	public function clear_all_permissions() {
		// Initialise all permissions to zero
		foreach(self::$permission_fields as $perm) {
			$this->permissions[$perm] = 0;
		}
		$this->permissions['is_admin'] = 0;
	}

	public function allow_all_permissions() {
		$this->clear_all_permissions();

		foreach(self::$permission_ui_list as $perm) {
			$field = $perm[1];
			$flag = $perm[2];
			$this->permissions[$field] |= $flag;
		}
		$this->permissions['is_admin'] = 1;
	}

	public function allow_permission($perm) {
		$field = $perm[1];
		$flag = $perm[2];
		$this->permissions[$field] |= $flag;
	}

	public function allow_field($field, $flag) {
		$this->permissions[$field] |= $flag;
	}

	public function get_ui($root = false, $defaults = null) {
		$result = [];

		$level_list = array_reverse(PermissionLevel::all());

		$module = null;
		$index = -1;
		foreach(self::$permission_ui_list as $perm) {
			// Only show permissions that are not denied for the current role/record
			// This is in line with the rule that you can't add a role/user that is higher level than you.
			if(!$root && !$this->check($perm)) continue;

			$module_id = $perm[0];
			$field = $perm[1];
			$flag = $perm[2];
			$description = $perm[3];

			$min_level = null;
			if($defaults !== null) {
				$min_level = PermissionLevel::AREA;
				foreach($level_list as $l) {
					if($defaults[$l]->check($perm)) {
						$min_level = $l;
						break;
					}
				}
			}

			if($flag == 1) {
				// This is the main ENABLED flag, which must be shorted on top in the UI list
				// Initialise module
				$module = new Module($module_id);
				if($module->info) {
					$index++;
					$result[$index] = [
						'module_id' => $module->id,
						'icon' => $module->info->icon ? 'eticon '.$module->info->icon : '',
						'color' => $module->info->color,
						'toggle' => [
							'field' => $field,
							'flag' => $flag,
							'min_level' => $min_level,
							'description' => $description
						],
						'options' => []
					];
				} else {
					$module = null;
				}
			} else {
				// This is an extra option, check if we're on the right module before adding
				if(!$module || $module->id != $module_id) continue;

				$result[$index]['options'][] = [
					'field' => $field,
					'flag' => $flag,
					'min_level' => $min_level,
					'description' => $description
				];
			}
		}

		return $result;
	}

	public function get_fields() {
		return $this->permissions;
	}

	public function get_enabled_module_ids() {
		$result = [];
		foreach(self::$permission_ui_list as $perm) {
			// print_r(self::$permission_ui_list); exit;
			if($perm[2] == 1) {
				
				// This is an enabled flag, check if we have access
				if($this->check($perm)) $result[] = $perm[0];
			}
		}
		return $result;
	}

	public function get_disabled_modules_ids()
	{
		$result = [];
		// print_r($perm); exit;
		foreach(self::$permission_ui_list as $perm) {
			//print_r($perm);
			if($perm[2] == 1 ) {
				// This is an enabled flag, check if we have access
				if(!$this->check($perm)) $result[] = $perm[0];
			}
		}
		return $result;
	}

	public function is_module_enabled($module_id) {
		foreach(self::$permission_ui_list as $perm) {
			if($perm[0] == $module_id && $perm[2] == 1) {
				// This is the module enabled flag, check if we have access
				return $this->check($perm);
			}
		}
		return false;
	}

}
