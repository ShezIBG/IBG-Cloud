<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_navigation() {
		$user = App::user();
		if(!$user) return $this->access_denied();

		$nav = [
			[ 'name' => 'Stock', 'header' => true ],
			[ 'name' => 'View By Product', 'icon' => 'md md-shopping-cart', 'route' => '/stock/view-products' ],
			[ 'name' => 'View By Location', 'icon' => 'md md-grid-on', 'route' => '/stock/view-locations' ],
			[ 'name' => 'Movements', 'header' => true ],
			[ 'name' => 'Goods In', 'icon' => 'md md-arrow-forward', 'route' => '/stock/goods-in' ],
			[ 'name' => 'Goods Out', 'icon' => 'md md-arrow-back', 'route' => '/stock/goods-out' ],
			[ 'name' => 'Configuration', 'header' => true ],
			[ 'name' => 'Products', 'icon' => 'md md-shopping-cart', 'route' => '/stock/product' ],
			[ 'name' => 'Product Catalogue', 'icon' => 'md md-settings', 'route' => '/stock/product-config' ],
			[ 'name' => 'Stock Locations', 'icon' => 'md md-settings', 'route' => '/stock/warehouse' ],
			[ 'name' => 'SmoothPower', 'header' => true ],
			[ 'name' => 'SmoothPower Units', 'icon' => 'eticon eticon-smooth-power', 'route' => '/stock/smoothpower' ],
		];

		return $this->success($nav);
	}

	public function list_warehouses() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$owner_level = $this->selected_product_owner_level;
		$owner_id = $this->selected_product_owner_id;

		$list = App::sql()->query("SELECT * FROM stock_warehouse WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND archived = 0 ORDER BY description;");

		return $this->success([
			'list' => $list ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_warehouse() {
		$id = App::get('id', 0, true);

		$details = App::select('stock_warehouse', $id);
		if(!$details) return $this->error('Warehouse not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$location_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM stock_location WHERE warehouse_id = '$id' AND archived = 0;")->cnt;

		return $this->success([
			'details' => $details,
			'location_count' => $location_count
		]);
	}

	public function new_warehouse() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id
			]
		]);
	}

	public function save_warehouse() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_level', 'owner_id', 'description']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'description'], '');

		// Check permissions
		if($id !== 'new') {
			$original = App::select('stock_warehouse', $id);
			if(!$original) return $this->error('Warehouse not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') return $this->error('Please enter warehouse description.');

		// Insert/update record
		$id = App::upsert('stock_warehouse', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function delete_warehouse() {
		$id = App::get('id', 0, true);

		$details = App::select('stock_warehouse', $id);
		if(!$details) return $this->error('Warehouse not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$location_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM stock_location WHERE warehouse_id = '$id' AND archived = 0;")->cnt;
		if($location_count > 0) return $this->error('Warehouse is in use.');

		App::update('stock_warehouse', $id, [ 'archived' => 1 ]);
		return $this->success();
	}

	private function expand_ranges($array) {
		$result = [];

		foreach($array as $v) {
			if(strpos($v, '-')) {
				$split = explode('-', $v);
				$result = array_merge($result, range($split[0], $split[1]));
			} else {
				$result[] = $v;
			}
		}

		return $result;
	}

	public function list_stock_locations() {
		$warehouse_id = App::get('id', 0, true);

		$record = App::select('stock_warehouse', $warehouse_id);
		if(!$record) return $this->error('Warehouse not found.');
		if(!Permission::get($record['owner_level'], $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$list = App::sql()->query(
			"SELECT
				l.*,
				pc.cnt AS product_count
			FROM stock_location AS l
			LEFT JOIN (
				SELECT
					location_id,
					COUNT(product_id) AS cnt
				FROM stock_warehouse_product
				WHERE warehouse_id = '$warehouse_id'
				GROUP BY location_id
			) AS pc ON pc.location_id = l.id
			WHERE l.warehouse_id = '$warehouse_id' AND l.archived = 0
			ORDER BY l.rack, l.bay, l.level;
		");

		return $this->success([
			'list' => $list ?: [],
			'print_url' => APP_URL.'/ajax/get/print_stock_location?warehouse='.$warehouse_id,
			'breadcrumbs' => [
				[ 'description' => 'Warehouses', 'route' => '/stock/warehouse' ],
				[ 'description' => $record['description'].' stock locations' ]
			]
		]);
	}

	public function create_stock_location() {
		$data = App::json();

		$warehouse_id = $data['warehouse_id'];

		$record = App::select('stock_warehouse', $warehouse_id);
		if(!$record) return $this->error('Warehouse not found.');
		if(!Permission::get($record['owner_level'], $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Extract ranges

		$racks = explode(',', strtoupper($data['rack']));
		$bays = explode(',', strtoupper($data['bay']));
		$levels = explode(',', strtoupper($data['level']));
		$delim = isset($data['delim']) ? ($data['delim'] ?: '') : '';

		$racks = array_map('trim', $racks);
		$bays = array_map('trim', $bays);
		$levels = array_map('trim', $levels);

		$racks = $this->expand_ranges($racks);
		$bays = $this->expand_ranges($bays);
		$levels = $this->expand_ranges($levels);

		foreach($racks as $r) {
			foreach($bays as $b) {
				foreach($levels as $l) {
					App::insert('stock_location', [
						'warehouse_id' => $warehouse_id,
						'rack' => $r,
						'bay' => $b !== '' ? $b : null,
						'level' => $l !== '' ? $l : null,
						'delim' => $delim
					]);
				}
			}
		}

		// Generate descriptions with delimiters
		App::sql()->update("UPDATE stock_location SET description = TRIM(BOTH delim FROM CONCAT(COALESCE(rack, ''), COALESCE(CONCAT(delim, bay), ''), COALESCE(CONCAT(delim, level), ''))) WHERE description IS NULL;");

		return $this->success();
	}

	public function set_location_label() {
		$data = App::json();

		$warehouse_id = App::escape($data['warehouse_id']);

		$record = App::select('stock_warehouse', $warehouse_id);
		if(!$record) return $this->error('Warehouse not found.');
		if(!Permission::get($record['owner_level'], $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$rack = App::escape($data['rack']);
		$bay = App::escape($data['bay']);
		$level = App::escape($data['level']);
		$label = App::escape($data['label']);

		$q = "UPDATE stock_location SET label = '$label' WHERE warehouse_id = '$warehouse_id'";
		if($rack !== null && $rack !== '') $q .= " AND rack = '$rack'";
		if($bay !== null && $bay !== '') $q .= " AND bay = '$bay'";
		if($level !== null && $level !== '') $q .= " AND level = '$level'";
		$q .= ';';

		App::sql()->update($q);

		return $this->success();
	}

	public function delete_stock_location() {
		$id = App::get('id', 0, true);

		$loc = App::select('stock_location', $id);
		if(!$loc) return $this->error('Location not found.');

		$record = App::select('stock_warehouse', $loc['warehouse_id']);
		if(!$record) return $this->error('Warehouse not found.');
		if(!Permission::get($record['owner_level'], $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$stock = App::sql()->query("SELECT * FROM stock WHERE location_id = '$id' AND qty <> 0;");
		if($stock) return $this->error("Stock is kept at location.");

		App::sql()->delete("DELETE FROM stock WHERE location_id = '$id' AND qty = 0;");
		App::sql()->update("UPDATE stock_warehouse_product SET location_id = NULL WHERE location_id = '$id';");
		App::sql()->delete("DELETE FROM stock_warehouse_product WHERE min_qty IS NULL AND max_qty IS NULL AND location_id IS NULL;");
		App::update('stock_location', $id, [ 'archived' => 1 ]);

		return $this->success();
	}

	public function get_stock_product_info() {
		$id = App::get('id', 0, true);
		$warehouse_id = App::get('warehouse', 0, true);

		$warehouse = App::select('stock_warehouse', $warehouse_id);
		if(!$warehouse) return $this->error('Warehouse not found');

		$product = App::select('product', $id);
		if(!$product) return $this->error('Product not found.');

		$manufacturer = App::select('product_entity', $product['manufacturer_id']);
		$manufacturer_name = $manufacturer ? $manufacturer['name'] : '';

		$image_url = '';
		if($product['image_id']) {
			$uc = new UserContent($product['image_id']);
			$image_url = $uc->get_url();
		}

		$info = App::sql()->query_row(
			"SELECT min_qty, max_qty, location_id
			FROM stock_warehouse_product
			WHERE warehouse_id = '$warehouse_id' AND product_id = '$id'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$info) $info = [
			'min_qty' => null,
			'max_qty' => null,
			'location_id' => null,
		];

		$stock = App::sql()->query(
			"SELECT s.*
			FROM stock AS s
			JOIN stock_location AS l ON l.id = s.location_id
			WHERE s.warehouse_id = '$warehouse_id' AND s.product_id = '$id' AND s.qty <> 0
			ORDER BY l.rack, l.bay, l.level;
		", MySQL::QUERY_ASSOC) ?: [];
		$qty = 0;
		foreach($stock as $s) {
			$qty += $s['qty'];
		}

		return $this->success([
			'id' => $product['id'],
			'sku' => $product['sku'],
			'manufacturer_name' => $manufacturer_name,
			'manufacturer_sku' => $product['manufacturer_sku'],
			'model' => $product['model'],
			'min_qty' => $info['min_qty'],
			'max_qty' => $info['max_qty'],
			'stock_qty' => $qty,
			'location_id' => $info['location_id'],
			'stock' => $stock ?: [],
			'image_url' => $image_url
		]);
	}

	public function submit_goods_in() {
		$data = App::json();

		try {
			App::sql()->start_transaction();

			$warehouse_id = App::escape($data['warehouse_id']);

			$txn = App::insert('stock_transaction', [
				'user_id' => App::user()->id,
				'type' => 'in',
				'notes' => $data['notes']
			]);

			if(!$txn) throw new Exception('Cannot register transaction.');

			foreach($data['items'] as $item) {
				$product_id = App::escape($item['product_id']);
				$location_id = App::escape($item['location_id']);
				$quantity = App::escape($item['quantity']);

				if(!$product_id) throw new Exception('Invalid product.');
				if(!$location_id) throw new Exception('Location not set.');
				if($quantity <= 0) throw new Exception('Quantity is zero or negative.');

				App::sql()->query(
					"INSERT INTO stock_warehouse_product (warehouse_id, product_id, location_id) VALUES ('$warehouse_id', '$product_id', '$location_id')
					ON DUPLICATE KEY UPDATE location_id = COALESCE(location_id, '$location_id');
				");

				App::insert('stock_transaction_item', [
					'transaction_id' => $txn,
					'warehouse_id' => $warehouse_id,
					'location_id' => $location_id,
					'product_id' => $product_id,
					'adjustment' => $quantity
				]);

				Stock::add($warehouse_id, $product_id, $location_id, $quantity);
			}

			App::sql()->commit_transaction();
		} catch(Exception $ex) {
			App::sql()->rollback_transaction();
			return $this->error($ex->getMessage());
		}

		return $this->success();
	}

	public function submit_goods_out() {
		$data = App::json();

		try {
			App::sql()->start_transaction();

			$warehouse_id = App::escape($data['warehouse_id']);

			$txn = App::insert('stock_transaction', [
				'user_id' => App::user()->id,
				'type' => 'out',
				'notes' => $data['notes']
			]);

			if(!$txn) throw new Exception('Cannot register transaction.');

			foreach($data['items'] as $item) {
				$product_id = App::escape($item['product_id']);
				$location_id = App::escape($item['location_id']);
				$quantity = App::escape($item['quantity']);

				if(!$product_id) throw new Exception('Invalid product.');
				if(!$location_id) throw new Exception('Location not set.');
				if($quantity <= 0) throw new Exception('Quantity is zero or negative.');

				App::insert('stock_transaction_item', [
					'transaction_id' => $txn,
					'warehouse_id' => $warehouse_id,
					'location_id' => $location_id,
					'product_id' => $product_id,
					'adjustment' => -$quantity
				]);

				Stock::add($warehouse_id, $product_id, $location_id, -$quantity);
			}

			App::sql()->commit_transaction();
		} catch(Exception $ex) {
			App::sql()->rollback_transaction();
			return $this->error($ex->getMessage());
		}

		return $this->success();
	}

	public function list_stock_by_product() {
		$warehouse_id = App::get('warehouse_id', 0, true);
		if(!$warehouse_id) return $this->error('Warehouse not found.');

		$warehouse = App::select('stock_warehouse', $warehouse_id);
		if(!$warehouse) return $this->error('Warehouse not found.');

		$owner_level = $warehouse['owner_level'];
		$owner_id = $warehouse['owner_id'];
		$perm = Permission::get($owner_level, $owner_id);
		if(!$perm->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		$show_cost = $perm->check(Permission::STOCK_VALUE);

		$stock = App::sql()->query(
			"SELECT
				p.id,
				p.sku,
				p.manufacturer_sku,
				p.model,
				m.name AS manufacturer_name,
				wp.min_qty,
				wp.max_qty,
				COALESCE(SUM(s.qty), 0) AS qty,
				COALESCE(MAX(pp.unit_cost), 0) AS unit_cost,
				COALESCE(SUM(s.qty), 0) * COALESCE(MAX(pp.unit_cost), 0) AS total_cost,
				GROUP_CONCAT(DISTINCT l.description SEPARATOR ', ') AS locations,
				uc.path AS image_url
			FROM product AS p
			LEFT JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$owner_level' AND pp.seller_id = '$owner_id'
			LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id
			LEFT JOIN stock_warehouse_product AS wp ON wp.product_id = p.id AND wp.warehouse_id = '$warehouse_id'
			LEFT JOIN stock AS s ON s.product_id = p.id AND s.warehouse_id = '$warehouse_id'
			LEFT JOIN stock_location AS l ON l.id = s.location_id
			LEFT JOIN user_content AS uc ON uc.id = p.image_id
			WHERE p.is_stocked = 1
			GROUP BY p.id, p.sku, p.manufacturer_sku, p.model, m.name, wp.min_qty, wp.max_qty
			HAVING qty > 0 OR wp.min_qty IS NOT NULL OR wp.max_qty IS NOT NULL
			ORDER BY p.sku, m.name, p.model, l.rack, l.bay, l.level;
		", MySQL::QUERY_ASSOC) ?: [];

		$total_stock_cost = 0;
		foreach($stock as &$item) {
			if($item['image_url']) {
				$item['image_url'] = UserContent::url_by_path($item['image_url']);
			}

			$total_stock_cost += $item['total_cost'];
		}
		unset($item);

		return $this->success([
			'list' => $stock ?: [],
			'show_cost' => $show_cost,
			'total_stock_cost' => $total_stock_cost
		]);
	}

	public function list_stock_by_location() {
		$warehouse_id = App::get('warehouse_id', 0, true);
		if(!$warehouse_id) return $this->error('Warehouse not found.');

		$warehouse = App::select('stock_warehouse', $warehouse_id);
		if(!$warehouse) return $this->error('Warehouse not found.');

		$owner_level = $warehouse['owner_level'];
		$owner_id = $warehouse['owner_id'];
		$perm = Permission::get($owner_level, $owner_id);
		if(!$perm->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		$show_cost = $perm->check(Permission::STOCK_VALUE);

		$stock = App::sql()->query(
			"SELECT
				p.id,
				p.sku,
				p.manufacturer_sku,
				p.model,
				m.name AS manufacturer_name,
				s.qty,
				COALESCE(pp.unit_cost, 0) AS unit_cost,
				COALESCE(pp.unit_cost, 0) * s.qty AS total_cost,
				l.description AS location,
				uc.path AS image_url
			FROM product AS p
			LEFT JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$owner_level' AND pp.seller_id = '$owner_id'
			LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id
			LEFT JOIN stock AS s ON s.product_id = p.id AND s.warehouse_id = '$warehouse_id'
			LEFT JOIN stock_location AS l ON l.id = s.location_id
			LEFT JOIN user_content AS uc ON uc.id = p.image_id
			WHERE p.is_stocked = 1 AND s.qty > 0
			ORDER BY p.sku, m.name, p.model, l.rack, l.bay, l.level;
		", MySQL::QUERY_ASSOC) ?: [];

		$total_stock_cost = 0;
		foreach($stock as &$item) {
			if($item['image_url']) {
				$item['image_url'] = UserContent::url_by_path($item['image_url']);
			}

			$total_stock_cost += $item['total_cost'];
		}
		unset($item);

		return $this->success([
			'list' => $stock ?: [],
			'show_cost' => $show_cost,
			'total_stock_cost' => $total_stock_cost
		]);
	}

	public function list_smoothpower_units() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$owner_level = $this->selected_product_owner_level;
		$owner_id = $this->selected_product_owner_id;

		$can_edit = !!Permission::get_eticom()->check(Permission::ADMIN);
		$can_install = false;

		switch($owner_level) {
			case PermissionLevel::SERVICE_PROVIDER:
				$condition = "si.service_provider_id = '$owner_id'";
				$can_install = !!Permission::get_service_provider($owner_id)->check(Permission::ADMIN);
				break;

			case PermissionLevel::SYSTEM_INTEGRATOR:
				$condition = "si.id = '$owner_id'";
				$can_install = !!Permission::get_system_integrator($owner_id)->check(Permission::ADMIN);
				break;

			default:
				return $this->access_denied();
		}

		$list = App::sql()->query(
			"SELECT
				sm.id,
				sm.serial,
				sm.system_integrator_id,
				si.company_name AS system_integrator_name,
				sm.building_id,
				b.description AS building_name,
				b.client_id,
				c.name AS client_name,
				NULL AS status,
				NULL AS surge_status,
				NULL AS temp_top,
				NULL AS temp_bottom,
				NULL AS voltage_input,
				NULL AS voltage_output,
				NULL AS voltage_reduction
			FROM smoothpower AS sm
			LEFT JOIN system_integrator AS si ON si.id = sm.system_integrator_id
			LEFT JOIN building AS b ON b.id = sm.building_id
			LEFT JOIN client AS c ON c.id = b.client_id
			WHERE $condition
			ORDER BY sm.serial;"
		);

		return $this->success([
			'list' => $list ?: [],
			'can_edit' => $can_edit,
			'can_install' => $can_install,
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

}
