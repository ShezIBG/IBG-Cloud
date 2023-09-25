<?php

require_once 'shared-api.php';

class API extends SharedAPI {
	// Updated with Henrys changes to the configurator - monitoring bus type.
	/**
	 * Entity names ALWAYS match their tables. The only exception are special
	 * custom entities which cannot have any CRUD operations applied to them.
	 *
	 * Tables in this array must be in the order of deletion from top to bottom,
	 * i.e. tables cannot have foreign keys pointing to a table above them.
	 *
	 * This table MUST have a definitive list of all possible entities!
	 *
	 * Properties:
	 *    db       The database it's queried from, valid parameter of App::sql()
	 *    crud     Boolean, true if CRUD operations can be applied to them
	 */
	private static $entities = [
		'mbus_device' => [ 'db' => 'app', 'crud' => true ],
		'rs485' => [ 'db' => 'app', 'crud' => true ],
		'mbus_master' => [ 'db' => 'app', 'crud' => true ],
		'ct_category' => [ 'db' => 'app', 'crud' => true ],
		'category' => [ 'db' => 'app', 'crud' => true ],
		'ct' => [ 'db' => 'app', 'crud' => true ],
		'abb_meter' => [ 'db' => 'app', 'crud' => true ],
		'pm12' => [ 'db' => 'app', 'crud' => true ],
		'gateway' => [ 'db' => 'app', 'crud' => true ],
		'router' => [ 'db' => 'app', 'crud' => true ],
		'meter' => [ 'db' => 'app', 'crud' => true ],
		'calculated_meter' => [ 'db' => 'app', 'crud' => true ],
		'breaker' => [ 'db' => 'app', 'crud' => true ],
		'dist_board' => [ 'db' => 'app', 'crud' => true ],
		'tenanted_area' => [ 'db' => 'app', 'crud' => true ],
		'onu' => [ 'db' => 'isp', 'crud' => true ],
		'olt' => [ 'db' => 'isp', 'crud' => true ],
		'hes' => [ 'db' => 'isp', 'crud' => true ],
		'coolplug' => [ 'db' => 'climate', 'crud' => true ],
		'coolhub' => [ 'db' => 'climate', 'crud' => true ],
		'relay_end_device' => [ 'db' => 'relay', 'crud' => true ],
		'relay_pin' => [ 'db' => 'relay', 'crud' => true ],
		'relay_device' => [ 'db' => 'relay', 'crud' => true ],
		'building_server' => [ 'db' => 'app', 'crud' => true ],
		'area' => [ 'db' => 'app', 'crud' => true ],
		'floor' => [ 'db' => 'app', 'crud' => true ],
		'building' => [ 'db' => 'app', 'crud' => true ],
		'em_light' => [ 'db' => 'app', 'crud' => true ],
		'floorplan' => [ 'db' => 'app', 'crud' => true ],
		'floorplan_item' => [ 'db' => 'app', 'crud' => true ],
		'floorplan_assignment' => [ 'db' => 'app', 'crud' => true ],
		'smoothpower' => [ 'db' => 'app', 'crud' => true ],
		'dali_light' => [ 'db' => 'dali', 'crud' => true ],

		'mbus_catalogue' => [ 'db' => 'app', 'crud' => false ],
		'rs485_catalogue' => [ 'db' => 'app', 'crud' => false ],
		'em_light_type' => [ 'db' => 'app', 'crud' => false ],
		'tenant' => [ 'db' => 'app', 'crud' => false ],
		'configurator_history' => [ 'db' => 'app', 'crud' => false ],
		'userdb' => [ 'db' => 'app', 'crud' => false ],
		'user_content' => [ 'db' => 'app', 'crud' => false ],
		'weather' => [ 'db' => 'app', 'crud' => false ],
		'ac_manufacturer' => [ 'db' => 'climate', 'crud' => false ],
		'ac_model_series' => [ 'db' => 'climate', 'crud' => false ],
		'onu_type' => [ 'db' => 'isp', 'crud' => false ]
	];

	private function get_building_array($building) {
		$result = [];
		$building_id = $building->id;
		$areas = [];

		$add_entities = function($entity, $query) use (&$result, &$areas) {
			if(isset(self::$entities[$entity])) {
				$data = App::sql(self::$entities[$entity]['db'])->query($query, MySQL::QUERY_ASSOC) ?: [];
				foreach($data as $item) {
					$item['entity'] = $entity;
					$result[] = $item;
					if($entity === 'area') $areas[] = $item['id'];
				}
			}
		};

		$add_entities('building', "SELECT * FROM building WHERE id = '$building_id';");
		$add_entities('floor', "SELECT * FROM floor WHERE building_id = '$building_id';");
		$add_entities('area', "SELECT a.* FROM area AS a JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('dist_board', "SELECT db.* FROM dist_board AS db JOIN area AS a ON a.id = db.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('breaker', "SELECT b.* FROM breaker AS b JOIN dist_board AS db ON b.db_id = db.id JOIN area AS a ON a.id = db.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('meter', "SELECT m.* FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('router', "SELECT r.* FROM router AS r JOIN area AS a ON a.id = r.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('gateway', "SELECT g.* FROM gateway AS g JOIN area AS a ON a.id = g.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('pm12', "SELECT p.* FROM pm12 AS p JOIN area AS a ON a.id = p.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('abb_meter', "SELECT abb.* FROM abb_meter AS abb JOIN area AS a ON a.id = abb.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('ct', "SELECT ct.* FROM ct JOIN pm12 AS p ON p.id = ct.pm12_id JOIN area AS a ON a.id = p.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('ct', "SELECT ct.* FROM ct JOIN abb_meter AS abb ON abb.id = ct.abb_meter_id JOIN area AS a ON a.id = abb.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");

		// When selecting categories, makes sure it's not deletable if it is assigned to any CTs in another building
		$add_entities('category',
			"SELECT
				c.id,
				c.parent_category_id,
				c.description,
				c.editable,
				IF(COUNT(ctc.id) = 0 AND c.deletable = 1, 1 ,0) AS deletable,
				c.client_id
			FROM category AS c
			LEFT JOIN ct_category AS ctc ON ctc.category_id = c.id AND ctc.building_id <> '$building_id'
			WHERE c.client_id = 0 OR c.client_id = '{$building->info->client_id}'
			GROUP BY c.id, c.parent_category_id, c.description, c.editable, c.deletable, c.client_id;
		");

		// Add meter calculations attached to meters
		$add_entities('calculated_meter',
			"SELECT DISTINCT cm.*
			FROM calculated_meter AS cm
			JOIN meter AS m ON m.id = cm.calculated_meter_id
			JOIN area AS a ON a.id = m.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$building_id';
		");

		$add_entities('ct_category', "SELECT * FROM ct_category WHERE building_id = '$building_id';");
		$add_entities('mbus_catalogue', "SELECT * FROM mbus_catalogue;");
		$add_entities('mbus_master', "SELECT m.* FROM mbus_master AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('mbus_device', "SELECT m.* FROM mbus_device AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('rs485_catalogue', "SELECT * FROM rs485_catalogue;");
		$add_entities('rs485', "SELECT r.* FROM rs485 AS r JOIN area AS a ON a.id = r.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('em_light_type', "SELECT * FROM em_light_type;");
		$add_entities('em_light', "SELECT e.* FROM em_light AS e JOIN area AS a ON a.id = e.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('floorplan', "SELECT * FROM floorplan WHERE building_id = '$building_id';");
		$add_entities('floorplan_assignment', "SELECT fa.* FROM floorplan_assignment AS fa JOIN floorplan AS f ON f.id = fa.floorplan_id WHERE f.building_id = '$building_id';");
		$add_entities('floorplan_item', "SELECT fi.* FROM floorplan_item AS fi JOIN floorplan AS f ON f.id = fi.floorplan_id WHERE f.building_id = '$building_id';");
		$add_entities('tenanted_area', "SELECT t.* FROM tenanted_area AS t JOIN area AS a ON a.id = t.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		$add_entities('smoothpower', "SELECT sp.* FROM smoothpower AS sp WHERE sp.building_id = '$building_id';");

		// ISP devices
		$add_entities('hes', "SELECT hes.* FROM hes WHERE hes.building_id = '$building_id';");
		if(count($areas)) {
			$area_list = implode(',', $areas);
			$add_entities('olt', "SELECT * FROM olt WHERE area_id IN ($area_list);");
			$add_entities('onu', "SELECT * FROM onu WHERE area_id IN ($area_list);");
			$add_entities('onu_type', "SELECT * FROM onu_type WHERE building_id = '$building_id' ORDER BY description;");
		}

		// Climate
		$add_entities('building_server', "SELECT bs.* FROM building_server AS bs JOIN area AS a ON a.id = bs.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id';");
		if(count($areas)) {
			$area_list = implode(',', $areas);
			$add_entities('coolhub', "SELECT * FROM coolhub WHERE area_id IN ($area_list);");
			$add_entities('coolplug', "SELECT * FROM coolplug WHERE area_id IN ($area_list);");
			$add_entities('ac_manufacturer', "SELECT * FROM ac_manufacturer;");
			$add_entities('ac_model_series', "SELECT * FROM ac_model_series;");
		}

		// Relay
		if(count($areas)) {
			$area_list = implode(',', $areas);
			$add_entities('relay_device', "SELECT * FROM relay_device WHERE area_id IN ($area_list);");
			$add_entities('relay_end_device', "SELECT * FROM relay_end_device WHERE area_id IN ($area_list);");
			$add_entities('relay_pin', "SELECT rp.* FROM relay_pin AS rp JOIN relay_device AS rd ON rd.id = rp.relay_device_id WHERE rd.area_id IN ($area_list);");
		}

		// DALI
		if(count($areas) && Lighting::check_database($building_id)) {
			$add_entities('dali_light', "SELECT * FROM ve_dali_${building_id}.dali_light;");
		}

		$add_entities('tenant',
			"SELECT t.id, l.area_id, t.name AS description
			FROM tenant_lease AS l
			JOIN tenant AS t ON t.id = l.tenant_id
			JOIN area AS a ON a.id = l.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$building_id' AND l.status IN ('current - active', 'current - expiring', 'current - ending');
		");

		$add_entities('configurator_history',
			"SELECT id, building_id, user_id, update_date, count_added, count_modified, count_deleted
			FROM configurator_history
			WHERE building_id = '$building_id'
			ORDER BY update_date DESC;"
		);

		$add_entities('userdb',
			"SELECT id, name, email_addr, active
			FROM userdb
			WHERE id IN (SELECT DISTINCT user_id FROM configurator_history WHERE building_id = '$building_id');"
		);

		// Add user content with generated full URLs
		$ucs = App::sql()->query("SELECT uc.* FROM user_content AS uc JOIN floorplan AS f ON f.image_id = uc.id WHERE f.building_id = '$building_id';", MySQL::QUERY_ASSOC) ?: [];
		foreach($ucs as $record) {
			$uc = new UserContent($record['id']);
			if($uc->info) {
				$record['entity'] = 'user_content';
				$record['generated_url'] = $uc->get_url();
				$result[] = $record;
			}
		}

		// Add weather data

		$weather = new WeatherService($building->id);

		$today = date('Y-m-d');
		$weather_today = $weather->get_hourly_weather_plot($today);
		$current_temp = 0;
		$icon = '';
		$summary = '';
		if($weather_today) {
			$details = $weather_today[$today][date('G')];
			if($details) {
				$current_temp = floor($details->temperature);
				$summary = $details->summary;
				$icon = $details->icon;
			}
		}

		$date_from = date('Y-m-d', strtotime('+1 day', strtotime($today)));
		$date_to = date('Y-m-d', strtotime('+3 day', strtotime($today)));
		$forecast = $weather->get_daily_weather_data($date_from, $date_to);

		$result[] = [
			'entity' => 'weather',
			'id' => 1,
			'temperature' => floor($current_temp),
			'day' => date('D', strtotime($today)),
			'icon' => $icon,
			'summary' => $summary,
			'forecast' => array_map(function($w) {
				return [
					'day' => date('D', strtotime($w->date)),
					'icon' => $w->icon,
					'temperature' => floor($w->temperatureMax)
				];
			}, $forecast ?: [])
		];

		return $result;
	}

	function get_building_data() {
		$building_id = App::get('building_id', 0, true);
		if(!$building_id) return $this->error('Building not found.');

		$building = new Building($building_id);
		if(!$building->validate()) return $this->error('Building not found.');

		// Check user levels and toolbox overrides
		$r = App::sql()->query_row(
			"SELECT
				c.system_integrator_id,
				si.service_provider_id
			FROM client AS c
			JOIN system_integrator AS si ON si.id = c.system_integrator_id
			WHERE c.id = '{$building->info->client_id}';
		");
		if(!$r) return $this->access_denied();

		$si_id = $r->system_integrator_id;
		$sp_id = $r->service_provider_id;

		$si_admin = Permission::get_system_integrator($si_id)->check(Permission::ADMIN);
		$sp_admin = Permission::get_service_provider($sp_id)->check(Permission::ADMIN);
		$e_admin = Permission::get_eticom()->check(Permission::ADMIN);

		// User has to be at least an SI admin
		if(!$si_admin) return $this->access_denied();

		// Load toolboxes
		$e_toolbox = App::sql()->query_row("SELECT toolbox_json FROM configurator_toolbox WHERE owner_level = 'E' AND owner_id = 0;", MySQL::QUERY_ASSOC);
		$sp_toolbox = App::sql()->query_row("SELECT toolbox_json FROM configurator_toolbox WHERE owner_level = 'SP' AND owner_id = '$sp_id';", MySQL::QUERY_ASSOC);
		$si_toolbox = App::sql()->query_row("SELECT toolbox_json FROM configurator_toolbox WHERE owner_level = 'SI' AND owner_id = '$si_id';", MySQL::QUERY_ASSOC);

		// Load list of all SmoothPower boxes for SI
		$sp_list = App::sql()->query("SELECT * FROM smoothpower WHERE system_integrator_id = '$si_id' ORDER BY serial;", MySQL::QUERY_ASSOC) ?: [];

		$toolbox = null;
		$options = [
			'olt_discovery' => false
		];
		if($e_admin) {
			$toolbox = $e_toolbox;
			$options['olt_discovery'] = true;
		} else if($sp_admin) {
			$toolbox = $sp_toolbox ?: $e_toolbox;
		} else if($si_admin) {
			$toolbox = ($si_toolbox ?: $sp_toolbox) ?: $e_toolbox;
		}

		if($toolbox) {
			$toolbox = json_decode($toolbox['toolbox_json'], true);
		} else {
			$toolbox = [];
		}

		return $this->success([
			'toolbox' => $toolbox,
			'options' => $options,
			'smoothpower_units' => $sp_list,
			'building' => $this->get_building_array($building)
		]);
	}

	function commit_changes() {
		$user = App::user();

		//
		// Simulation step
		//

		$needs_dali_db = false;

		$id = 1;
		$data = App::json();
		$queries = [];
		$newids = [];
		$stop = count($data['added']) + count($data['modified']) == 0;
		$steps = 0;

		$building_id = $data['building_id'];
		if(!$building_id) return $this->error('Building ID not set.', $data);

		$building = new Building($building_id);
		if(!$building->validate()) return $this->error('Building not found.');

		// Check if user has SI level access for the building
		$r = App::sql()->query_row("SELECT system_integrator_id FROM client WHERE id = '{$building->info->client_id}';");
		$si_admin = $r ? Permission::get_system_integrator($r->system_integrator_id)->check(Permission::ADMIN) : false;
		if(!$si_admin) return $this->access_denied();

		// Delete all items (simulated)
		foreach(self::$entities as $type => $entity_info) {
			if($entity_info['crud']) {
				$data['deleted'] = array_filter($data['deleted'], function($entity) use ($type, &$needs_dali_db) {
					if($entity['entity'] === 'dali_light') $needs_dali_db = true;
					return $entity['entity'] != $type;
				});
			}
		}

		while(!$stop) {
			$stop = true;
			$steps += 1;

			// Attempt to resolve new IDs from the previous step
			$data['added'] = array_filter($data['added'], function($entity) use (&$newids, &$id, &$queries, &$data, &$stop, &$needs_dali_db) {
				if($entity['entity'] === 'dali_light') $needs_dali_db = true;

				$resolved = true;
				$self_ref = [];
				foreach($entity as $key => $value) {
					if($key != 'id' && $value && substr($value, 0, 6) == 'newid_') {
						// This is a new ID, try to resolve
						if($entity[$key] == $entity['id']) {
							$self_ref[] = $key;
						} else if(isset($newids[$value])) {
							$entity[$key] = $newids[$value];
						} else {
							// Can't resolve in this step
							$resolved = false;
						}
					}
				}

				if($resolved) {
					// All resolved, insert new record (simulated)
					$newids[$entity['id']] = $id;
					$entity['id'] = $id;
					$id++;
					$stop = false;

					if(count($self_ref) == 0) {
						$queries[] = $entity;
					} else {
						// Only self references left, move to modified array to update in next step
						$mod = [
							'entity' => $entity['entity'],
							'id' => $entity['id']
						];
						foreach($self_ref as $key) {
							$mod[$key] = $mod['id'];
						}
						$data['modified'][] = $mod;
					}
				}

				return !$resolved;
			});

			$data['modified'] = array_filter($data['modified'], function($entity) use (&$newids, &$stop) {
				if($entity['entity'] === 'dali_light') $needs_dali_db = true;

				$resolved = true;
				foreach($entity as $key => $value) {
					if($key != 'id' && $value && substr($value, 0, 6) == 'newid_') {
						// This is a new ID, try to resolve
						if(isset($newids[$value])) {
							$entity[$key] = $newids[$value];
						} else {
							// Can't resolve in this step
							$resolved = false;
						}
					}
				}

				if($resolved) {
					// All resolved, update record (simulated)
					$stop = false;
				}

				return !$resolved;
			});
		}

		if(count($data['added']) + count($data['modified']) + count($data['deleted'])) {
			return $this->error('Unable to resolve records', $data);
		}

		//
		// Check and create VE Dali DB if needed
		//

		if($needs_dali_db) {
			if(!Lighting::check_database($building_id)) {
				if(Lighting::create_database($building_id)) {
					if(!Lighting::check_database($building_id)) {
						return $this->error('Failed to verify VE DALI DB.');
					}
				} else {
					return $this->error('Failed to create VE DALI DB.');
				}
			}
		}

		//
		// Database update step
		//

		App::sql('app')->start_transaction();
		App::sql('isp')->start_transaction();

		// Create history entry
		$data = App::json();

		$cnt_added = count($data['added']);
		$cnt_modified = count($data['modified']);
		$cnt_deleted = count($data['deleted']);
		$history_id = App::sql()->insert("INSERT INTO configurator_history (user_id, building_id, count_added, count_modified, count_deleted) VALUES ('$user->id', '$building->id', '$cnt_added', '$cnt_modified', '$cnt_deleted');");

		if(!$history_id) {
			App::sql('app')->rollback_transaction();
			App::sql('isp')->rollback_transaction();
			return $this->error('Cannot register transaction.');
		}

		$snapshot = json_encode($this->get_building_array($building));
		$snapshot = App::escape($snapshot);
		App::sql()->update("UPDATE configurator_history SET json_before = '$snapshot' WHERE id = '$history_id';");

		$snapshot = json_encode($data);
		$snapshot = App::escape($snapshot);
		App::sql()->update("UPDATE configurator_history SET json_commit = '$snapshot' WHERE id = '$history_id';");

		$queries = [];
		$newids = [];
		$stop = count($data['added']) + count($data['modified']) == 0;
		$steps = 0;

		$dali_updates = [];
		$discover_olts = [];

		// Delete all items
		foreach(self::$entities as $type => $entity_info) {
			if($entity_info['crud']) {
				$data['deleted'] = array_filter($data['deleted'], function($entity) use (&$queries, $type, $entity_info, $building_id) {
					if($entity['entity'] != $type) return true;

					$sql = App::sql($entity_info['db']);
					$table = $entity['entity'];
					if($entity_info['db'] === 'dali') $table = "ve_dali_${building_id}.${table}";
					$id = $sql->escape($entity['id']);

					if($type === 'smoothpower') {
						// SmoothPower records are never deleted, only unassigned.
						$q = "UPDATE smoothpower SET building_id = NULL, area_id = NULL, router_id = NULL WHERE id = '$id';";
						$queries[] = $q;
						$sql->update($q);
					} else {
						// All other entity records can be removed from the database.
						$q = "DELETE FROM $table WHERE id = '$id';";
						$queries[] = $q;
						$sql->delete($q);
					}

					if($type === 'gateway') {
						// Remove monitoring history for gateway items
						$sql->delete("DELETE FROM gateway_status WHERE gateway_id = '$id';");
						$sql->delete("DELETE FROM gateway_status_history WHERE gateway_id = '$id';");
					} else if($type === 'dali_light') {
						$vedb = "ve_dali_${building_id}";
						$sql->delete("DELETE FROM $vedb.dali_group_light WHERE dali_light_id = '$id';");
					}

					return false;
				});
			}
		}

		while(!$stop) {
			$stop = true;
			$steps += 1;

			// Attempt to resolve new IDs from the previous step
			$data['added'] = array_filter($data['added'], function($entity) use (&$newids, &$queries, &$data, &$stop, &$discover_olts, $building_id) {
				$resolved = true;
				$self_ref = [];
				foreach($entity as $key => $value) {
					if($key != 'id' && $value && substr($value, 0, 6) == 'newid_') {
						// This is a new ID, try to resolve
						if($entity[$key] == $entity['id']) {
							$self_ref[] = $key;
						} else if(isset($newids[$value])) {
							$entity[$key] = $newids[$value];
						} else {
							// Can't resolve in this step
							$resolved = false;
						}
					}
				}

				if($resolved) {
					// All resolved, insert new record
					$table = $entity['entity'];
					$fields = [];
					$values = [];

					if(!isset(self::$entities[$table])) return true; // No entity info, leave it
					$entity_info = self::$entities[$table];
					if(!$entity_info['crud']) return true; // No CRUD allowed, leave it
					if($entity_info['db'] === 'dali') $table = "ve_dali_${building_id}.${table}";

					$sql = App::sql($entity_info['db']);

					$olt_discovery = false;
					foreach($entity as $field => $value) {
						if($field != 'entity' && $field != 'id' && !in_array($field, $self_ref)) {
							// Don't treat olt discovery as a database field
							if($table === 'olt' && $field === 'discovery') {
								if($value) $olt_discovery = true;
								continue;
							}

							$fields[] = preg_replace('/[^A-Za-z0-9_]+/', '', $field);
							if($value === null) {
								$values[] = 'NULL';
							} else {
								$values[] = "'".$sql->escape($value)."'";
							}
						}
					}

					$fields = implode(', ', $fields);
					$values = implode(', ', $values);
					$q = "INSERT INTO $table ($fields) VALUES ($values);";

					// print_r($q); exit;
					$queries[] = $q;

					$id = $sql->insert($q);
					if(!$id) return true; // Database error

					if($olt_discovery) {
						// Add new ID to OLT discovery array
						if(!in_array($id, $discover_olts)) $discover_olts[] = $id;
					}

					$newids[$entity['id']] = $id;
					$entity['id'] = $id;
					$stop = false;

					if(count($self_ref) > 0) {
						// Only self references left, move to modified array to update in next step
						$mod = [
							'entity' => $entity['entity'],
							'id' => $entity['id']
						];
						foreach($self_ref as $key) {
							$mod[$key] = $mod['id'];
						}
						$data['modified'][] = $mod;
					}
				}

				return !$resolved;
			});

			$data['modified'] = array_filter($data['modified'], function($entity) use (&$newids, &$queries, &$dali_updates, &$stop, &$discover_olts, $building_id) {
				$resolved = true;
				foreach($entity as $key => $value) {
					if($key != 'id' && $value && substr($value, 0, 6) == 'newid_') {
						// This is a new ID, try to resolve
						if(isset($newids[$value])) {
							$entity[$key] = $newids[$value];
						} else {
							// Can't resolve in this step
							$resolved = false;
						}
					}
				}

				if($resolved) {
					// All resolved, update record
					if(!isset($entity['id'])) return true; // No id set

					$table = $entity['entity'];

					if(!isset(self::$entities[$table])) return true; // No entity info, leave it
					$entity_info = self::$entities[$table];
					if(!$entity_info['crud']) return true; // No CRUD allowed, leave it
					if($entity_info['db'] === 'dali') $table = "ve_dali_${building_id}.${table}";

					$sql = App::sql($entity_info['db']);

					$id = $sql->escape($entity['id']);
					$sets = [];

					$olt_discovery = false;
					foreach($entity as $field => $value) {
						if($field != 'entity' && $field != 'id') {
							// Don't treat olt discovery as a database field
							if($table === 'olt' && $field === 'discovery') {
								if($value) $olt_discovery = true;
								continue;
							}

							$s = preg_replace('/[^A-Za-z0-9_]+/', '', $field).' = ';
							if($value === null) {
								$s .= 'NULL';
							} else {
								$s .= "'".$sql->escape($value)."'";
							}
							$sets[] = $s;

							// Special handling of DALI address updates
							if($table === 'em_light' && $field === 'dali_address') {
								if($value === null) {
									$dali_updates["$id"] = 'NULL';
								} else {
									$dali_updates["$id"] = "'".$sql->escape($value)."'";
								}
							}
						}
					}

					if(count($sets) > 0) {
						$sets = implode(', ', $sets);
						$q = "UPDATE $table SET $sets WHERE id = '$id';";
						$queries[] = $q;

						$r = $sql->update($q);
						if(!$r) return true; // Database error
					}

					if($olt_discovery) {
						if(!in_array($id, $discover_olts)) $discover_olts[] = $id;
					}

					$stop = false;
				}

				return !$resolved;
			});
		}

		// Update em_schedule with new DALI addresses
		foreach($dali_updates as $id => $value) {
			App::sql()->update("UPDATE em_schedule SET dali_address = $value WHERE em_light_id = '$id';");

			// Update EM server
			$light = App::select('em_light', $id);
			if($light) {
				$gw = App::select('gateway', $light['gateway_id']);
				if($gw && $gw['monitoring_server_id']) {
					$msql = App::sql("monitoring:$gw[monitoring_server_id]");
					if($msql) {
						$msql->update("UPDATE em_schedule_$gw[pi_serial] SET dali_address = $value WHERE em_light_id = '$id';");
					}
				}
			}
		}

		// Create OLT discovery commands
		foreach($discover_olts as $olt_id) {
			App::insert('todo@isp', [
				'user_id' => $user ? $user->id : 0,
				'olt_id' => $olt_id,
				'cmd' => "update_elanet_onu_table($olt_id)"
			]);
		}

		$snapshot = json_encode($this->get_building_array($building));
		$snapshot = App::escape($snapshot);
		App::sql()->update("UPDATE configurator_history SET json_after = '$snapshot' WHERE id = '$history_id';");

		App::sql('app')->commit_transaction();
		App::sql('isp')->commit_transaction();

		if(count($data['added']) + count($data['modified']) + count($data['deleted'])) {
			return $this->error('Something bad happened. Please call developer.', ['data' => $data, 'queries' => $queries, 'severe' => true]);
		}

		$building->evaluate_modules();
		$building->claim_meters();

		return $this->success(null, 'Building updated successfully.');
	}

}