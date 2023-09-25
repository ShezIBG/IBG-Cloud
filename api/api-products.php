<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function list_entities() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$show_archived = App::get('archived', 0);

		$filters = App::json();
		$filters = App::ensure($filters, ['archived', 'is_manufacturer', 'is_supplier'], 0);

		$query_filters = [];
		if(!$filters['archived']) $query_filters[] = 'archived = 0';
		if($filters['is_manufacturer']) $query_filters[] = 'is_manufacturer = 1';
		if($filters['is_supplier']) $query_filters[] = 'is_supplier = 1';

		$query_filters = join(' AND ', $query_filters);
		if($query_filters) $query_filters = "AND $query_filters";

		$list = App::sql()->query(
			"SELECT
				e.id, e.name,
				e.is_manufacturer, e.is_supplier, e.is_owner,
				e.email_address, e.phone_number, e.mobile_number,
				e.posttown, e.postcode,
				e.info_url,
				e.archived,
				COALESCE(mpc.cnt, 0) AS manufacturer_product_count,
				COALESCE(spc.cnt, 0) AS supplier_product_count

			FROM product_entity AS e

			LEFT JOIN (
				SELECT manufacturer_id, COUNT(id) AS cnt FROM product GROUP BY manufacturer_id
			) AS mpc ON mpc.manufacturer_id = e.id

			LEFT JOIN (
				SELECT supplier_id, COUNT(product_id) AS cnt FROM product_suppliers GROUP BY supplier_id
			) AS spc ON spc.supplier_id = e.id

			WHERE
				e.owner_level = '$this->selected_product_owner_level' AND e.owner_id = '$this->selected_product_owner_id'
				$query_filters

			ORDER BY e.is_owner DESC, e.name;
		");

		$owner_record = App::sql()->query_row("SELECT id FROM product_entity WHERE owner_level = '$this->selected_product_owner_level' AND owner_id = '$this->selected_product_owner_id' AND is_owner = 1;");

		return $this->success([
			'list' => $list ?: [],
			'product_owners' => $this->product_owners,
			'owner_has_entity' => !!$owner_record,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_entity() {
		$id = App::get('id', 0, true);

		$details = App::select('product_entity', $id);
		if(!$details) return $this->error('Entity not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$manufacturer_product_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product WHERE manufacturer_id = '$id';")->cnt;
		$supplier_product_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product_suppliers WHERE supplier_id = '$id';")->cnt;

		$owner_record = App::sql()->query_row("SELECT id FROM product_entity WHERE owner_level = '$details[owner_level]' AND owner_id = '$details[owner_id]' AND is_owner = 1;");

		$manufacturers = App::sql()->query(
			"SELECT
				pe.id, pe.name, pe.posttown, pe.postcode, peds.is_primary
			FROM product_entity_default_suppliers AS peds
			JOIN product_entity AS pe ON pe.id = peds.manufacturer_id
			WHERE peds.supplier_id = '$id'
			ORDER BY pe.name;
		", MySQL::QUERY_ASSOC) ?: [];

		$suppliers = App::sql()->query(
			"SELECT
				pe.id, pe.name, pe.posttown, pe.postcode, peds.is_primary
			FROM product_entity_default_suppliers AS peds
			JOIN product_entity AS pe ON pe.id = peds.supplier_id
			WHERE peds.manufacturer_id = '$id'
			ORDER BY peds.is_primary DESC, pe.name;
		", MySQL::QUERY_ASSOC) ?: [];

		$details['manufacturers'] = $manufacturers;
		$details['suppliers'] = $suppliers;

		return $this->success([
			'details' => $details,
			'manufacturer_product_count' => $manufacturer_product_count,
			'supplier_product_count' => $supplier_product_count,
			'owner_has_entity' => !!$owner_record
		]);
	}

	public function new_entity() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$owner_record = App::sql()->query_row("SELECT id FROM product_entity WHERE owner_level = '$this->selected_product_owner_level' AND owner_id = '$this->selected_product_owner_id' AND is_owner = 1;");

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'is_manufacturer' => 0,
				'is_supplier' => 0,
				'is_owner' => 0,
				'manufacturers' => [],
				'suppliers' => []
			],
			'manufacturer_product_count' => 0,
			'supplier_product_count' => 0,
			'owner_has_entity' => !!$owner_record
		]);
	}

	public function archive_entity() {
		$id = App::get('id', 0, true);

		$details = App::select('product_entity', $id);
		if(!$details) return $this->error('Entity not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$product_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product WHERE manufacturer_id = '$id';")->cnt;
		if($product_count > 0) return $this->error('Entity is in use.');

		App::update('product_entity', $id, [ 'archived' => 1 ]);
		return $this->success();
	}

	public function unarchive_entity() {
		$id = App::get('id', 0, true);

		$details = App::select('product_entity', $id);
		if(!$details) return $this->error('Entity not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		App::update('product_entity', $id, [ 'archived' => 0 ]);
		return $this->success();
	}

	public function save_entity() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;

		$record = App::keep($record, [
			'owner_level', 'owner_id', 'name',
			'is_manufacturer', 'is_supplier', 'is_owner',
			'email_address', 'phone_number', 'mobile_number',
			'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode',
			'info_url',
			'suppliers', 'manufacturers', 'removed_suppliers', 'removed_manufacturers',
			'update_new', 'update_remove', 'update_primary'
		]);

		$record = App::ensure($record, ['owner_level', 'owner_id', 'name', 'email_address', 'phone_number', 'mobile_number', 'address_line_1', 'address_line_2', 'address_line_3', 'posttown', 'postcode', 'info_url'], '');
		$record = App::ensure($record, ['is_manufacturer', 'is_supplier', 'is_owner'], 0);
		$record = App::ensure($record, ['suppliers', 'manufacturers', 'removed_suppliers', 'removed_manufacturers'], []);
		$record = App::ensure($record, ['update_new', 'update_remove'], 1);
		$record = App::ensure($record, ['update_primary'], 0);

		$suppliers = $record['suppliers'];
		$manufacturers = $record['manufacturers'];
		$removed_suppliers = $record['removed_suppliers'];
		$removed_manufacturers = $record['removed_manufacturers'];
		$update_new = $record['update_new'];
		$update_remove = $record['update_remove'];
		$update_primary = $record['update_primary'];

		unset($record['suppliers']);
		unset($record['manufacturers']);
		unset($record['removed_suppliers']);
		unset($record['removed_manufacturers']);
		unset($record['update_new']);
		unset($record['update_remove']);
		unset($record['update_primary']);

		// Check permissions
		if($id !== 'new') {
			$original = App::select('product_entity', $id);
			if(!$original) return $this->error('Entity not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		$record['is_manufacturer'] = $record['is_manufacturer'] ? 1 : 0;
		$record['is_supplier'] = $record['is_supplier'] ? 1 : 0;

		if($record['name'] === '') return $this->error('Please enter entity name.');
		if(!$record['is_manufacturer'] && !$record['is_supplier']) return $this->error('Entity must be a manufacturer and/or a supplier.');

		// Insert/update record
		$id = App::upsert('product_entity', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		//
		// Save supplier and manufacturer records
		//

		App::sql()->delete("DELETE FROM product_entity_default_suppliers WHERE manufacturer_id = '$id' OR supplier_id = '$id';");

		if($record['is_manufacturer']) {
			foreach($suppliers as $s) {
				$s = App::keep($s, ['id', 'is_primary']);
				$s = App::ensure($s, ['id', 'is_primary'], 0);

				App::insert_ignore('product_entity_default_suppliers', [
					'manufacturer_id' => $id,
					'supplier_id' => $s['id'] === 'new' ? $id : $s['id'],
					'is_primary' => $s['is_primary'] ? 1 : 0
				]);
			}
		}

		if($record['is_supplier']) {
			foreach($manufacturers as $m) {
				$m = App::keep($m, ['id', 'is_primary']);
				$m = App::ensure($m, ['id', 'is_primary'], 0);

				$m_id = $m['id'] === 'new' ? $id : $m['id'];

				App::insert_ignore('product_entity_default_suppliers', [
					'manufacturer_id' => $m_id,
					'supplier_id' => $id,
					'is_primary' => $m['is_primary'] ? 1 : 0
				]);

				if($m['is_primary']) {
					// Make sure this supplier is the only primary set for manufacturer
					App::sql()->update(
						"UPDATE product_entity_default_suppliers
						SET is_primary = 0
						WHERE manufacturer_id = '$m_id' AND supplier_id <> '$id';
					");
				}
			}
		}

		//
		// Add new suppliers to existing products
		//

		if($update_new) {
			$m_id = $id;
			$list = App::sql()->query("SELECT id FROM product WHERE manufacturer_id = '$m_id' AND is_placeholder = 0 AND is_bundle = 0;", MySQL::QUERY_ASSOC) ?: [];

			foreach($suppliers as $s) {
				if(isset($s['id']) && isset($s['added']) && $s['added']) {
					$s_id = $s['id'] === 'new' ? $id : $s['id'];

					foreach($list as $p) {
						App::insert_ignore('product_suppliers', [
							'product_id' => $p['id'],
							'supplier_id' => $s_id
						]);
					}
				}
			}

			foreach($manufacturers as $m) {
				if(isset($m['id']) && isset($m['added']) && $m['added']) {
					$m_id = $m['id'] === 'new' ? $id : $m['id'];
					$s_id = $id;

					$list = App::sql()->query("SELECT id FROM product WHERE manufacturer_id = '$m_id' AND is_placeholder = 0 AND is_bundle = 0;", MySQL::QUERY_ASSOC) ?: [];

					foreach($list as $p) {
						App::insert_ignore('product_suppliers', [
							'product_id' => $p['id'],
							'supplier_id' => $s_id
						]);
					}
				}
			}
		}

		//
		// Remove suppliers from existing products
		// (always remove corresponding records if entity is no longer a supplier or manufacturer)
		//

		if($update_remove) {
			foreach($removed_manufacturers as $m) {
				if(isset($m['id'])) {
					$m_id = $m['id'] === 'new' ? $id : $m['id'];
					$s_id = $id;

					App::sql()->delete(
						"DELETE FROM product_suppliers
						WHERE supplier_id = '$s_id'
							AND product_id IN (SELECT id FROM product WHERE manufacturer_id = '$m_id');
					");
				}
			}

			foreach($removed_suppliers as $s) {
				if(isset($s['id'])) {
					$s_id = $s['id'] === 'new' ? $id : $s['id'];
					$m_id = $id;

					App::sql()->delete(
						"DELETE FROM product_suppliers
						WHERE supplier_id = '$s_id'
							AND product_id IN (SELECT id FROM product WHERE manufacturer_id = '$m_id');
					");
				}
			}
		}

		if(!$record['is_supplier']) {
			// Entity is not a suppier, remove supplier records
			App::sql()->delete("DELETE FROM product_suppliers WHERE supplier_id = '$id';");
		}

		if(!$record['is_manufacturer']) {
			// Entity is not a manufacturer, unassign products
			App::sql()->update("UPDATE product SET manufacturer_id = NULL WHERE manufacturer_id = '$id';");
		}

		//
		// Override primary flags for existing products
		//

		if($update_primary) {
			foreach($suppliers as $s) {
				$s = App::keep($s, ['id', 'is_primary']);
				$s = App::ensure($s, ['id', 'is_primary'], 0);

				if($s['is_primary']) {
					$s_id = $s['id'] === 'new' ? $id : $s['id'];
					$m_id = $id;

					App::sql()->update(
						"UPDATE product_suppliers AS ps
						JOIN product AS p ON p.id = ps.product_id
						SET ps.is_primary = IF(ps.supplier_id = '$s_id', 1, 0)
						WHERE p.manufacturer_id = '$m_id';
					");
				}
			}

			foreach($manufacturers as $m) {
				$m = App::keep($m, ['id', 'is_primary']);
				$m = App::ensure($m, ['id', 'is_primary'], 0);

				if($m['is_primary']) {
					$m_id = $m['id'] === 'new' ? $id : $m['id'];
					$s_id = $id;

					App::sql()->update(
						"UPDATE product_suppliers AS ps
						JOIN product AS p ON p.id = ps.product_id
						SET ps.is_primary = IF(ps.supplier_id = '$s_id', 1, 0)
						WHERE p.manufacturer_id = '$m_id';
					");
				}
			}
		}

		//
		// Make sure every product has one primary supplier set
		//

		App::sql()->update(
			"UPDATE product_suppliers AS ps
			JOIN (
				SELECT
					tps.product_id,
					MIN(tps.supplier_id) AS supplier_id,
					SUM(tps.is_primary) AS primary_count
				FROM product_suppliers AS tps
				GROUP BY tps.product_id
				HAVING primary_count = 0
			) AS t ON t.product_id = ps.product_id
			SET ps.is_primary = 1
			WHERE ps.is_primary = 0 AND ps.supplier_id = t.supplier_id;
		");

		return $this->success($id);
	}

	public function list_categories() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$list = App::sql()->query(
			"SELECT
				t.id, t.name, COALESCE(pc.cnt, 0) AS product_count
			FROM product_category AS t

			LEFT JOIN (
				SELECT category_id, count(id) AS cnt FROM product GROUP BY category_id
			) AS pc ON pc.category_id = t.id

			WHERE t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id'

			ORDER BY name;"
		);

		return $this->success([
			'list' => $list ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_category() {
		$id = App::get('id', 0, true);

		$details = App::select('product_category', $id);
		if(!$details) return $this->error('Category not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$product_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product WHERE category_id = '$id';")->cnt;

		return $this->success([
			'details' => $details,
			'product_count' => $product_count
		]);
	}

	public function new_category() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id
			]
		]);
	}

	public function delete_category() {
		$id = App::get('id', 0, true);

		$details = App::select('product_category', $id);
		if(!$details) return $this->error('Category not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$product_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product WHERE category_id = '$id';")->cnt;
		if($product_count > 0) return $this->error('Category is in use.');

		App::delete('product_category', $id);
		return $this->success();
	}

	public function save_category() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_level', 'owner_id', 'name']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'name'], '');

		// Check permissions
		if($id !== 'new') {
			$original = App::select('product_category', $id);
			if(!$original) return $this->error('Category not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['name'] === '') {
			return $this->error('Please enter category name.');
		}

		// Insert/update record
		$id = App::upsert('product_category', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function list_tag_groups() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$list = App::sql()->query(
			"SELECT * FROM product_tag_group
			WHERE owner_level = '$this->selected_product_owner_level' AND owner_id = '$this->selected_product_owner_id'
			ORDER BY name;
		");

		$tags = App::sql()->query(
			"SELECT * FROM product_tag
			WHERE group_id IN (
				SELECT id FROM product_tag_group
				WHERE owner_level = '$this->selected_product_owner_level' AND owner_id = '$this->selected_product_owner_id'
			)
			ORDER BY group_id, name;
		");

		return $this->success([
			'list' => $list ?: [],
			'tags' => $tags ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_tag_group() {
		$id = App::get('id', 0, true);

		$details = App::select('product_tag_group', $id);
		if(!$details) return $this->error('Tag group not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$tags = App::sql()->query(
			"SELECT
				t.id, t.name,
				COUNT(pt.product_id) AS product_count
			FROM product_tag AS t
			LEFT JOIN product_tags AS pt ON pt.tag_id = t.id
			WHERE group_id = '$id'
			GROUP BY t.id, t.name
			ORDER BY t.name;"
		);

		$product_count = App::sql()->query_row(
			"SELECT COUNT(*) AS cnt
			FROM product_tag AS t
			JOIN product_tags AS pt ON pt.tag_id = t.id
			WHERE t.group_id = '$id';"
		)->cnt;

		return $this->success([
			'details' => $details,
			'tags' => $tags ?: [],
			'product_count' => $product_count
		]);
	}

	public function new_tag_group() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'colour' => '#666666'
			]
		]);
	}

	public function delete_tag_group() {
		$id = App::get('id', 0, true);

		$details = App::select('product_tag_group', $id);
		if(!$details) return $this->error('Tag group not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$product_count = App::sql()->query_row(
			"SELECT COUNT(*) AS cnt
			FROM product_tag AS t
			JOIN product_tags AS pt ON pt.tag_id = t.id
			WHERE t.group_id = '$id';"
		)->cnt;

		if($product_count > 0) return $this->error('Tags are in use.');

		App::sql()->delete("DELETE FROM product_tag WHERE group_id = '$id';");
		App::delete('product_tag_group', $id);
		return $this->success();
	}

	public function save_tag_group() {
		$data = App::json();

		// Check if ID is set
		$id = $data['details']['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data['details'];
		$record = App::keep($record, ['owner_level', 'owner_id', 'name', 'colour']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'name', 'colour'], '');

		if($id !== 'new') {
			$original = App::select('product_tag_group', $id);
			if(!$original) return $this->error('Tag group not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['name'] === '') return $this->error('Please enter tag group name.');
		if($record['colour'] === '') return $this->error('Please enter tag group colour.');

		// Check tags
		foreach($data['added'] as $tag) if(!isset($tag['name']) || $tag['name'] === '') return $this->error('Please enter tag name.');
		foreach($data['modified'] as $tag) if(!isset($tag['name']) || $tag['name'] === '') return $this->error('Please enter tag name.');

		// Insert/update record
		$id = App::upsert('product_tag_group', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Update tags

		foreach($data['added'] as $tag) {
			$tag = App::keep($tag, ['name']);
			$tag['group_id'] = $id;
			App::insert('product_tag', $tag);
		}

		foreach($data['modified'] as $tag) {
			$tag_id = $tag['id'];
			$tag = App::keep($tag, ['name']);
			App::update('product_tag', $tag_id, $tag);
		}

		foreach($data['deleted'] as $tag) {
			$tag_id = $tag['id'];
			App::delete('product_tag', $tag_id);
		}

		return $this->success($id);
	}

	public function list_base_units() {
		if(!$this->resolve_product_owners()) return $this->access_denied();
		$owner_level = $this->selected_product_owner_level;
		$owner_id = $this->selected_product_owner_id;

		$list = App::sql()->query(
			"SELECT
				t.id, t.name, t.description, t.decimal_places, t.is_default,
				COALESCE(pc.cnt, 0) AS product_count
			FROM product_unit AS t

			LEFT JOIN (
				SELECT unit_id, COUNT(id) AS cnt FROM product GROUP BY unit_id
			) AS pc ON pc.unit_id = t.id

			WHERE t.owner_level = '$owner_level' AND t.owner_id = '$owner_id' AND t.base_unit_id IS NULL
			ORDER BY name;"
		);

		$units = App::sql()->query(
			"SELECT * FROM product_unit
			WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND base_unit_id IS NOT NULL
			ORDER BY base_unit_id, base_amount;
		");

		return $this->success([
			'list' => $list ?: [],
			'units' => $units ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function set_default_unit() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();
		$owner_level = $this->selected_product_owner_level;
		$owner_id = $this->selected_product_owner_id;

		$default_unit = App::get('unit_id', 0, true);

		App::sql()->update(
			"UPDATE product_unit
			SET is_default = IF(id = '$default_unit', 1, 0)
			WHERE owner_level = '$owner_level' AND owner_id = '$owner_id';
		");

		return $this->success();
	}

	public function get_base_unit() {
		$id = App::get('id', 0, true);

		$details = App::sql()->query_row("SELECT * FROM product_unit WHERE id = '$id' AND base_unit_id IS NULL LIMIT 1;", MySQL::QUERY_ASSOC);
		if(!$details) return $this->error('Base unit not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$units = App::sql()->query(
			"SELECT
				u.id, u.name, u.description, u.decimal_places, u.base_amount
			FROM product_unit AS u
			WHERE u.base_unit_id = '$id'
			ORDER BY u.base_amount;"
		);

		return $this->success([
			'details' => $details,
			'units' => $units ?: []
		]);
	}

	public function new_base_unit() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'decimal_places' => 0
			]
		]);
	}

	public function save_base_unit() {
		$data = App::json();

		// Check if ID is set
		$id = $data['details']['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data['details'];
		$record = App::keep($record, ['owner_level', 'owner_id', 'name', 'description', 'decimal_places']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'name', 'description'], '');
		$record = App::ensure($record, ['decimal_places'], '0');

		// Check permissions
		if($id !== 'new') {
			$original = App::sql()->query_row("SELECT * FROM product_unit WHERE id = '$id' AND base_unit_id IS NULL LIMIT 1;", MySQL::QUERY_ASSOC);
			if(!$original) return $this->error('Base unit not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['name'] === '') return $this->error('Please enter base unit name.');

		// Check units
		foreach($data['added'] as $unit) {
			if(!isset($unit['name']) || $unit['name'] === '') return $this->error('Please enter unit name.');
			if(!isset($unit['base_amount']) || $unit['base_amount'] == 0) return $this->error('Conversion rate cannot be zero.');
		}
		foreach($data['modified'] as $unit) {
			if(!isset($unit['name']) || $unit['name'] === '') return $this->error('Please enter unit name.');
			if(!isset($unit['base_amount']) || $unit['base_amount'] == 0) return $this->error('Conversion rate cannot be zero.');
		}

		// Insert/update record
		$id = App::upsert('product_unit', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Update conversion units

		foreach($data['added'] as $unit) {
			$unit = App::keep($unit, ['name', 'description', 'decimal_places', 'base_amount']);
			$unit = App::ensure($unit, ['name', 'description'], '');
			$unit = App::ensure($unit, ['decimal_places'], 0);
			$unit = App::ensure($unit, ['base_amount'], 1);
			$unit['base_unit_id'] = $id;
			$unit['owner_level'] = $record['owner_level'];
			$unit['owner_id'] = $record['owner_id'];
			App::insert('product_unit', $unit);
		}

		foreach($data['modified'] as $unit) {
			$unit_id = $unit['id'];
			$unit = App::keep($unit, ['name', 'description', 'decimal_places', 'base_amount']);
			$unit = App::ensure($unit, ['name', 'description'], '');
			$unit = App::ensure($unit, ['decimal_places'], 0);
			$unit = App::ensure($unit, ['base_amount'], 1);
			$unit['owner_level'] = $record['owner_level'];
			$unit['owner_id'] = $record['owner_id'];
			App::update('product_unit', $unit_id, $unit);
		}

		foreach($data['deleted'] as $unit) {
			$unit_id = $unit['id'];
			App::delete('product_unit', $unit_id);
		}

		return $this->success($id);
	}

	public function list_labour_types() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$types = App::sql()->query(
			"SELECT
				t.id, t.description, t.hourly_cost, t.hourly_price, t.category_id,
				IF(t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id', 1, 0) AS editable,
				COALESCE(sp.company_name, si.company_name) AS owner_name,
				COUNT(DISTINCT p.product_id) AS product_count
			FROM product_labour_type AS t
			LEFT JOIN product_labour AS p ON p.labour_type_id = t.id

			LEFT JOIN service_provider AS sp ON t.owner_level = 'SP' AND t.owner_id = sp.id
			LEFT JOIN system_integrator AS si ON t.owner_level = 'SI' AND t.owner_id = si.id
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$this->selected_product_owner_level' AND pr.reseller_id = '$this->selected_product_owner_id'
			WHERE (t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id') OR pr.owner_level IS NOT NULL

			GROUP BY t.id, t.description, t.hourly_cost, t.hourly_price, t.category_id
			ORDER BY t.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$categories = App::sql()->query(
			"SELECT
				t.*,
				IF(t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id', 1, 0) AS editable,
				COALESCE(sp.company_name, si.company_name) AS owner_name
			FROM product_labour_category AS t

			LEFT JOIN service_provider AS sp ON t.owner_level = 'SP' AND t.owner_id = sp.id
			LEFT JOIN system_integrator AS si ON t.owner_level = 'SI' AND t.owner_id = si.id
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$this->selected_product_owner_level' AND pr.reseller_id = '$this->selected_product_owner_id'
			WHERE (t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id') OR pr.owner_level IS NOT NULL

			ORDER BY description;
		", MySQL::QUERY_ASSOC) ?: [];

		$perm = Permission::get($this->selected_product_owner_level, $this->selected_product_owner_id);
		$pricing = $perm->check(Permission::STOCK_LABOUR_PRICE);

		if(!$pricing) {
			foreach($types as &$item) {
				$item['hourly_cost'] = 0;
				$item['hourly_price'] = 0;
				$item['editable'] = 0;
			}
			unset($item);

			foreach($categories as &$item) {
				$item['editable'] = 0;
			}
			unset($item);
		}

		return $this->success([
			'types' => $types,
			'categories' => $categories,
			'pricing' => $pricing,
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_labour_category() {
		$id = App::get('id', 0, true);

		$details = App::select('product_labour_category', $id);
		if(!$details) return $this->error('Labour category not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$item_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product_labour_type WHERE category_id = '$id';", MySQL::QUERY_ASSOC)['cnt'];

		return $this->success([
			'details' => $details,
			'item_count' => $item_count
		]);
	}

	public function save_labour_category() {
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
			$original = App::select('product_labour_category', $id);
			if(!$original) return $this->error('Labour category not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') {
			return $this->error('Please enter labour category description.');
		}

		// Insert/update record
		$id = App::upsert('product_labour_category', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function delete_labour_category() {
		$id = App::get('id', 0, true);

		$details = App::select('product_labour_category', $id);
		if(!$details) return $this->error('Labour category not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$item_count = App::sql()->query("SELECT COUNT(*) AS cnt FROM product_labour_type WHERE category_id = '$id';", MySQL::QUERY_ASSOC)['cnt'];
		if($item_count > 0) return $this->error("You can only delete empty categories.");

		App::delete('product_labour_category', $id);

		return $this->success();
	}

	public function get_labour_type() {
		$id = App::get('id', 0, true);

		$details = App::select('product_labour_type', $id);
		if(!$details) return $this->error('Labour type not found.');

		$perm = Permission::get($details['owner_level'], $details['owner_id']);
		if(!$perm->check(Permission::STOCK_ENABLED) || !$perm->check(Permission::STOCK_LABOUR_PRICE)) return $this->access_denied();

		$categories = App::sql()->query(
			"SELECT * FROM product_labour_category
			WHERE owner_level = '$details[owner_level]' AND owner_id = '$details[owner_id]'
			ORDER BY description;
		");

		return $this->success([
			'details' => $details,
			'categories' => $categories ?: []
		]);
	}

	public function new_labour_type() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$categories = App::sql()->query(
			"SELECT * FROM product_labour_category
			WHERE owner_level = '$this->selected_product_owner_level' AND owner_id = '$this->selected_product_owner_id'
			ORDER BY description;
		");

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'hourly_cost' => 0,
				'hourly_price' => 0
			],
			'categories' => $categories ?: []
		]);
	}

	public function save_labour_type() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, ['owner_level', 'owner_id', 'description', 'hourly_cost', 'hourly_price', 'category_id']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'description'], '');
		$record = App::ensure($record, ['hourly_cost', 'hourly_price'], 0);

		// Check permissions
		if($id !== 'new') {
			$original = App::select('product_labour_type', $id);
			if(!$original) return $this->error('Labour type not found.');

			$perm = Permission::get($original['owner_level'], $original['owner_id']);
			if(!$perm->check(Permission::STOCK_ENABLED) || !$perm->check(Permission::STOCK_LABOUR_PRICE)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') return $this->error('Please enter labour type description.');

		// Insert/update record
		$id = App::upsert('product_labour_type', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function list_subscription_types() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$types = App::sql()->query(
			"SELECT
				t.id, t.description, psp.unit_cost, psp.distribution_price, psp.reseller_price, psp.trade_price, psp.retail_price, t.frequency, t.category_id,
				IF(t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id', 1, 0) AS editable,
				COALESCE(sp.company_name, si.company_name) AS owner_name,
				COUNT(p.product_id) AS product_count
			FROM product_subscription_type AS t
			JOIN product_subscription_price AS psp ON psp.subscription_type_id = t.id AND psp.seller_level = '$this->selected_product_owner_level' AND psp.seller_id = '$this->selected_product_owner_id'
			LEFT JOIN product_subscription AS p ON p.subscription_type_id = t.id
			LEFT JOIN service_provider AS sp ON t.owner_level = 'SP' AND t.owner_id = sp.id
			LEFT JOIN system_integrator AS si ON t.owner_level = 'SI' AND t.owner_id = si.id
			GROUP BY
				t.id, t.description, psp.unit_cost, psp.distribution_price, psp.reseller_price, psp.trade_price, psp.retail_price, t.frequency, t.category_id,
				IF(t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id', 1, 0), COALESCE(sp.company_name, si.company_name)
			ORDER BY t.description;
		", MySQL::QUERY_ASSOC);

		$categories = App::sql()->query(
			"SELECT
				t.*, IF(t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id', 1, 0) AS editable
			FROM product_subscription_category AS t

			LEFT JOIN service_provider AS sp ON t.owner_level = 'SP' AND t.owner_id = sp.id
			LEFT JOIN system_integrator AS si ON t.owner_level = 'SI' AND t.owner_id = si.id
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$this->selected_product_owner_level' AND pr.reseller_id = '$this->selected_product_owner_id'
			WHERE (t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id') OR pr.owner_level IS NOT NULL

			ORDER BY t.description;
		", MySQL::QUERY_ASSOC);

		$perm = Permission::get($this->selected_product_owner_level, $this->selected_product_owner_id);
		$pricing = $perm->check(Permission::STOCK_SUBSCRIPTION_PRICE);

		if(!$pricing) {
			foreach($types as &$item) {
				$item['unit_cost'] = 0;
				$item['distribution_price'] = 0;
				$item['reseller_price'] = 0;
				$item['trade_price'] = 0;
				$item['retail_price'] = 0;
				$item['editable'] = 0;
			}
			unset($item);

			foreach($categories as &$item) {
				$item['editable'] = 0;
			}
			unset($item);
		}

		return $this->success([
			'types' => $types ?: [],
			'categories' => $categories ?: [],
			'pricing' => $pricing,
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_subscription_category() {
		$id = App::get('id', 0, true);

		$details = App::select('product_subscription_category', $id);
		if(!$details) return $this->error('Subscription category not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$item_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product_subscription_type WHERE category_id = '$id';", MySQL::QUERY_ASSOC)['cnt'];

		return $this->success([
			'details' => $details,
			'item_count' => $item_count
		]);
	}

	public function save_subscription_category() {
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
			$original = App::select('product_subscription_category', $id);
			if(!$original) return $this->error('Subscription category not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') return $this->error('Please enter subscription category description.');

		// Insert/update record
		$id = App::upsert('product_subscription_category', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		return $this->success($id);
	}

	public function delete_subscription_category() {
		$id = App::get('id', 0, true);

		$details = App::select('product_subscription_category', $id);
		if(!$details) return $this->error('Subscription category not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$item_count = App::sql()->query("SELECT COUNT(*) AS cnt FROM product_subscription_type WHERE category_id = '$id';", MySQL::QUERY_ASSOC);
		$item_count = $item_count ? $item_count['cnt'] : 0;
		if($item_count > 0) return $this->error("You can only delete empty categories.");

		App::delete('product_subscription_category', $id);

		return $this->success();
	}

	public function get_subscription_type() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$id = App::get('id', 0, true);
		$seller_level = $this->selected_product_owner_level;
		$seller_id = $this->selected_product_owner_id;

		$details = App::select('product_subscription_type', $id);
		if(!$details) return $this->error('Subscription type not found.');

		$perm = Permission::get($details['owner_level'], $details['owner_id']);
		if(!$perm->check(Permission::STOCK_ENABLED) || !$perm->check(Permission::STOCK_SUBSCRIPTION_PRICE)) return $this->access_denied();
		$is_owner = $seller_level == $details['owner_level'] && $seller_id = $details['owner_id'];

		$prices = App::sql()->query_row("SELECT unit_cost, pricing_structure_id, distribution_price, reseller_price, trade_price, retail_price FROM product_subscription_price WHERE subscription_type_id = '$id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';", MySQL::QUERY_ASSOC);
		if(!$prices) return $this->error('Subscription type not found.');
		$details = array_merge($details, $prices);

		$pricing_structures = App::sql()->query("SELECT * FROM product_pricing_structure WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' ORDER BY description;");

		$rp = [
			'distribution_price' => 0,
			'reseller_price' => 0,
			'trade_price' => 0,
			'retail_price' => 0
		];

		if(!$is_owner) {
			// Calculate recommended pricing based on owner's price
			$reseller = App::sql()->query_row("SELECT price_tier FROM product_reseller WHERE owner_level = '$details[owner_level]' AND owner_id = '$details[owner_id]' AND reseller_level = '$seller_level' AND reseller_id = '$seller_id';", MySQL::QUERY_ASSOC);
			if($reseller) {
				$tier = $reseller['price_tier'];
				$price = App::sql()->query_row("SELECT * FROM product_subscription_price WHERE subscription_type_id = '$id' AND seller_level = '$details[owner_level]' AND seller_id = '$details[owner_id]';", MySQL::QUERY_ASSOC);
				$rp = [];
				$rp['cost'] = $tier === 'cost' ? $price['unit_cost'] : $price["{$tier}_price"];
				$rp['distribution_price'] = in_array($tier, ['cost', 'distribution']) ? $price['distribution_price'] : $rp['cost'];
				$rp['reseller_price'] = in_array($tier, ['cost', 'distribution', 'reseller']) ? $price['reseller_price'] : $rp['cost'];
				$rp['trade_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade']) ? $price['trade_price'] : $rp['cost'];
				$rp['retail_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade', 'retail']) ? $price['retail_price'] : $rp['cost'];
				unset($rp['cost']);
			}
		}

		$categories = App::sql()->query(
			"SELECT t.*
			FROM product_subscription_category AS t
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$this->selected_product_owner_level' AND pr.reseller_id = '$this->selected_product_owner_id'
			WHERE (t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id') OR pr.owner_level IS NOT NULL
			ORDER BY t.description;
		");

		return $this->success([
			'details' => $details,
			'editable' => ($details['owner_level'] == $this->selected_product_owner_level) && ($details['owner_id'] == $this->selected_product_owner_id),
			'categories' => $categories ?: [],
			'pricing_structures' => $pricing_structures ?: [],
			'recommended_pricing' => $rp
		]);
	}

	public function new_subscription_type() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();
		$seller_level = $this->selected_product_owner_level;
		$seller_id = $this->selected_product_owner_id;

		$categories = App::sql()->query(
			"SELECT t.*
			FROM product_subscription_category AS t
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$this->selected_product_owner_level' AND pr.reseller_id = '$this->selected_product_owner_id'
			WHERE (t.owner_level = '$this->selected_product_owner_level' AND t.owner_id = '$this->selected_product_owner_id') OR pr.owner_level IS NOT NULL
			ORDER BY t.description;
		");

		$pricing_structures = App::sql()->query("SELECT * FROM product_pricing_structure WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' ORDER BY description;");

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'unit_cost' => 0,
				'pricing_structure_id' => null,
				'distribution_price' => 0,
				'reseller_price' => 0,
				'trade_price' => 0,
				'retail_price' => 0,
				'frequency' => 'monthly'
			],
			'editable' => true,
			'categories' => $categories ?: [],
			'pricing_structures' => $pricing_structures ?: [],
			'recommended_pricing' => [
				'distribution_price' => 0,
				'reseller_price' => 0,
				'trade_price' => 0,
				'retail_price' => 0
			]
		]);
	}

	public function save_subscription_type() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();
		$seller_level = $this->selected_product_owner_level;
		$seller_id = $this->selected_product_owner_id;

		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		$is_new = $id === 'new';
		if(!$id) return $this->access_denied();

		// Check if owner is editing
		$is_owner = ($data['owner_level'] == $seller_level) && ($data['owner_id'] == $seller_id);

		// Create records
		$record = $data;
		$record = App::keep($record, ['owner_level', 'owner_id', 'description', 'category_id', 'frequency']);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'description', 'frequency'], '');

		$price = $data;
		$price = App::keep($price, ['unit_cost', 'pricing_structure_id', 'distribution_price', 'reseller_price', 'trade_price', 'retail_price']);
		$price = App::ensure($price, ['unit_cost', 'distribution_price', 'reseller_price', 'trade_price', 'retail_price'], 0);
		$price = App::ensure($price, ['pricing_structure_id'], null);

		// Check permissions
		if($id === 'new' && !$is_owner) return $this->access_denied();

		if($is_owner) {
			// Data validation
			if($record['description'] === '') return $this->error('Please enter subscription type description.');
			if($record['frequency'] === '') return $this->error('Please select frequency.');

			// Insert/update record
			$id = App::upsert('product_subscription_type', $id, $record);
			if(!$id) return $this->error('Error saving data.');
		}

		// Save prices
		if($is_new) {
			$price['subscription_type_id'] = $id;
			$price['seller_level'] = $record['owner_level'];
			$price['seller_id'] = $record['owner_id'];
			App::insert('product_subscription_price', $price);
		} else {
			$price_wrap = App::escape_and_wrap($price);

			if($is_owner) {
				App::sql()->update(
					"UPDATE product_subscription_price
					SET
						unit_cost = $price_wrap[unit_cost],
						pricing_structure_id = $price_wrap[pricing_structure_id],
						distribution_price = $price_wrap[distribution_price],
						reseller_price = $price_wrap[reseller_price],
						trade_price = $price_wrap[trade_price],
						retail_price = $price_wrap[retail_price]
					WHERE subscription_type_id = '$id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';
				");
			} else {
				// If not the owner, cost is fixed

				App::sql()->update(
					"UPDATE product_subscription_price
					SET
						pricing_structure_id = $price_wrap[pricing_structure_id],
						distribution_price = $price_wrap[distribution_price],
						reseller_price = $price_wrap[reseller_price],
						trade_price = $price_wrap[trade_price],
						retail_price = $price_wrap[retail_price]
					WHERE subscription_type_id = '$id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';
				");
			}
		}

		if($is_owner) {
			// Update reseller costs
			$resellers = App::sql()->query("SELECT reseller_level, reseller_id, price_tier FROM product_reseller WHERE owner_level = '$seller_level' AND owner_id = '$seller_id';", MySQL::QUERY_ASSOC) ?: [];
			foreach($resellers as $r) {
				$tier = $r['price_tier'];
				$rp = [];
				$rp['cost'] = $tier === 'cost' ? $price['unit_cost'] : $price["{$tier}_price"];
				$rp['distribution_price'] = in_array($tier, ['cost', 'distribution']) ? $price['distribution_price'] : $rp['cost'];
				$rp['reseller_price'] = in_array($tier, ['cost', 'distribution', 'reseller']) ? $price['reseller_price'] : $rp['cost'];
				$rp['trade_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade']) ? $price['trade_price'] : $rp['cost'];
				$rp['retail_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade', 'retail']) ? $price['retail_price'] : $rp['cost'];

				if($is_new) {
					// Get/create recommended pricing structure for reseller
					$recommended = App::sql()->query_row(
						"SELECT id FROM product_pricing_structure
						WHERE owner_level = '$r[reseller_level]' AND owner_id = '$r[reseller_id]'
						AND distribution_method = 'recommended' AND reseller_method = 'recommended' AND trade_method = 'recommended' AND retail_method = 'recommended'
						LIMIT 1;
					", MySQL::QUERY_ASSOC);

					if($recommended) {
						$recommended_id = $recommended['id'];
					} else {
						$recommended_id = App::insert('product_pricing_structure', [
							'owner_level' => $r['reseller_level'],
							'owner_id' => $r['reseller_id'],
							'description' => 'Recommended price',
							'distribution_method' => 'recommended',
							'reseller_method' => 'recommended',
							'trade_method' => 'recommended',
							'retail_method' => 'recommended'
						]) ?: null;
					}

					// Create record for reseller
					App::insert('product_subscription_price', [
						'subscription_type_id' => $id,
						'seller_level' => $r['reseller_level'],
						'seller_id' => $r['reseller_id'],
						'unit_cost' => $rp['cost'],
						'pricing_structure_id' => $recommended_id,
						'distribution_price' => $rp['distribution_price'],
						'reseller_price' => $rp['reseller_price'],
						'trade_price' => $rp['trade_price'],
						'retail_price' => $rp['retail_price']
					]);
				} else {
					// Update reseller cost
					App::sql()->update(
						"UPDATE product_subscription_price
						SET unit_cost = $rp[cost]
						WHERE subscription_type_id = '$id' AND seller_level = '$r[reseller_level]' AND seller_id = '$r[reseller_id]';
					");
				}
			}
		}

		// Update pricing
		$pricing = new ProductPricing();
		$pricing->apply_subscription_change($id);

		return $this->success($id);
	}

	public function list_pricing_structures() {
		if(!$this->resolve_product_owners()) return $this->access_denied();
		$owner_level = $this->selected_product_owner_level;
		$owner_id = $this->selected_product_owner_id;

		$list = App::sql()->query(
			"SELECT
				ps.*,
				pcnt.cnt AS product_count,
				scnt.cnt AS subscription_count
			FROM product_pricing_structure AS ps

			LEFT JOIN (
				SELECT pricing_structure_id, COUNT(*) AS cnt
				FROM product_price
				WHERE pricing_structure_id IS NOT NULL
				GROUP BY pricing_structure_id
			) AS pcnt ON pcnt.pricing_structure_id = ps.id

			LEFT JOIN (
				SELECT pricing_structure_id, COUNT(*) AS cnt
				FROM product_subscription_price
				WHERE pricing_structure_id IS NOT NULL
				GROUP BY pricing_structure_id
			) AS scnt ON scnt.pricing_structure_id = ps.id

			WHERE ps.owner_level = '$owner_level' AND ps.owner_id = '$owner_id'
			ORDER BY ps.description;
		");

		return $this->success([
			'list' => $list ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_pricing_structure() {
		$id = App::get('id', 0, true);

		$details = App::select('product_pricing_structure', $id);
		if(!$details) return $this->error('Pricing structure not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$product_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product_price WHERE pricing_structure_id = '$id';")->cnt;
		$subscription_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product_subscription_price WHERE pricing_structure_id = '$id';")->cnt;

		return $this->success([
			'details' => $details,
			'product_count' => $product_count,
			'subscription_count' => $subscription_count
		]);
	}

	public function new_pricing_structure() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $this->selected_product_owner_level,
				'owner_id' => $this->selected_product_owner_id,
				'distribution_method' => 'custom',
				'distribution_round' => null,
				'distribution_round_to_nearest' => 1,
				'reseller_method' => 'custom',
				'reseller_round' => null,
				'reseller_round_to_nearest' => 1,
				'trade_method' => 'custom',
				'trade_round' => null,
				'trade_round_to_nearest' => 1,
				'retail_method' => 'custom',
				'retail_round' => null,
				'retail_round_to_nearest' => 1
			]
		]);
	}

	public function delete_pricing_structure() {
		$id = App::get('id', 0, true);

		$details = App::select('product_pricing_structure', $id);
		if(!$details) return $this->error('Pricing structure not found.');
		if(!Permission::get($details['owner_level'], $details['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$product_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product_price WHERE pricing_structure_id = '$id';")->cnt;
		$subscription_count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM product_subscription_price WHERE pricing_structure_id = '$id';")->cnt;
		if($product_count > 0 || $subscription_count > 0) return $this->error('Pricing structure is in use.');

		App::delete('product_pricing_structure', $id);
		return $this->success();
	}

	public function save_pricing_structure() {
		$data = App::json();

		// Check if ID is set
		$id = $data['id'];
		if(!$id) return $this->access_denied();

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'owner_level', 'owner_id', 'description',
			'distribution_method', 'distribution_value', 'distribution_round', 'distribution_round_to_nearest', 'distribution_minimum_price',
			'reseller_method', 'reseller_value', 'reseller_round', 'reseller_round_to_nearest', 'reseller_minimum_price',
			'trade_method', 'trade_value', 'trade_round', 'trade_round_to_nearest', 'trade_minimum_price',
			'retail_method', 'retail_value', 'retail_round', 'retail_round_to_nearest', 'retail_minimum_price'
		]);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'description'], '');
		$record = App::ensure($record, ['distribution_method', 'reseller_method', 'trade_method', 'retail_method'], 'custom');
		$record = App::ensure($record, [
			'distribution_value', 'distribution_round', 'distribution_round_to_nearest', 'distribution_minimum_price',
			'reseller_value', 'reseller_round', 'reseller_round_to_nearest', 'reseller_minimum_price',
			'trade_value', 'trade_round', 'trade_round_to_nearest', 'trade_minimum_price',
			'retail_value', 'retail_round', 'retail_round_to_nearest', 'retail_minimum_price'
		], null);

		// Check permissions
		if($id !== 'new') {
			$original = App::select('product_pricing_structure', $id);
			if(!$original) return $this->error('Pricing structure not found.');
			if(!Permission::get($original['owner_level'], $original['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();
		}
		if(!Permission::get($record['owner_level'] ?: 'E', $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if($record['description'] === '') {
			return $this->error('Please enter pricing structure description.');
		}

		$array = [
			'distribution' => 'Distribution',
			'reseller' => 'Reseller',
			'trade' => 'Trade',
			'retail' => 'Retail'
		];

		foreach($array as $type => $type_cap) {
			if($record["{$type}_method"] !== 'custom' && $record["{$type}_method"] !== 'recommended' && ($record["{$type}_value"] === null || $record["{$type}_value"] === "")) {
				return $this->error("Please enter a value to calculate $type price.");
			}
			if($record["{$type}_method"] === 'margin' && $record["{$type}_value"] >= 100) return $this->error("$type_cap margin must be less than 100%.");

			// Clear N/A fields
			if($record["{$type}_method"] == 'custom' || $record["{$type}_method"] == 'recommended') $record["{$type}_value"] = null;
			if($record["{$type}_method"] == 'custom') {
				$record["{$type}_minimum_price"] = null;
				$record["{$type}_round"] = null;
			}

			// Add default rounding value if none is set
			if($record["{$type}_round"] !== null && (!$record["{$type}_round_to_nearest"] || $record["{$type}_round_to_nearest"] <= 0)) return $this->error('Please enter rounding value.');
		}

		// Insert/update record
		$id = App::upsert('product_pricing_structure', $id, $record);
		if(!$id) return $this->error('Error saving data.');

		// Update pricing
		$pricing = new ProductPricing();
		$pricing->apply_pricing_structure_change($id);

		return $this->success($id);
	}

	public function list_resellers() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$list = App::sql()->query(
			"SELECT
				CONCAT(pr.owner_level, '-', pr.owner_id) AS owner,
				CONCAT(pr.reseller_level, '-', pr.reseller_id) AS reseller,
				pr.price_tier,
				IF(sp.company_name IS NOT NULL, CONCAT(sp.company_name, ' (SP)'), si.company_name) AS description
			FROM product_reseller AS pr
			LEFT JOIN service_provider AS sp ON sp.id = pr.reseller_id AND pr.reseller_level = 'SP'
			LEFT JOIN system_integrator AS si ON si.id = pr.reseller_id AND pr.reseller_level = 'SI'
			WHERE pr.owner_level = '$this->selected_product_owner_level' AND pr.owner_id = '$this->selected_product_owner_id'
			ORDER BY pr.reseller_level, description;"
		);

		return $this->success([
			'list' => $list ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	public function get_reseller() {
		$owner = App::get('owner', '', true);
		$reseller = App::get('reseller', '', true);
		if(!$owner || !$reseller) return $this->access_denied();

		list($owner_level, $owner_id) = explode('-', $owner);
		list($reseller_level, $reseller_id) = explode('-', $reseller);
		if(!Permission::get($owner_level, $owner_id)->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		$details = App::sql()->query_row(
			"SELECT
				CONCAT(owner_level, '-', owner_id) AS owner,
				CONCAT(reseller_level, '-', reseller_id) AS reseller,
				price_tier
			FROM product_reseller
			WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND reseller_level = '$reseller_level' AND reseller_id = '$reseller_id'
		", MySQL::QUERY_ASSOC);

		$resellers = null;
		if($owner_level === 'SP') {
			$resellers = App::sql()->query("SELECT CONCAT('SI-', id) AS id, company_name AS description FROM system_integrator WHERE service_provider_id = '$owner_id' ORDER BY description;");
		} else if(Permission::get_eticom()->check(Permission::ADMIN)) {
			$resellers = App::sql()->query("SELECT CONCAT('SI-', id) AS id, company_name AS description FROM system_integrator ORDER BY description;");
		}

		return $this->success([
			'details' => $details,
			'resellers' => $resellers
		]);
	}

	public function new_reseller() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$owner_level = $this->selected_product_owner_level;
		$owner_id = $this->selected_product_owner_id;

		$resellers = null;
		if($owner_level === 'SP') {
			$resellers = App::sql()->query("SELECT CONCAT('SI-', id) AS id, company_name AS description FROM system_integrator WHERE service_provider_id = '$owner_id' ORDER BY description;");
		} else if(Permission::get_eticom()->check(Permission::ADMIN)) {
			$resellers = App::sql()->query("SELECT CONCAT('SI-', id) AS id, company_name AS description FROM system_integrator ORDER BY description;");
		}

		if(!$resellers) return $this->error("No resellers found.");

		return $this->success([
			'details' => [
				'owner' => $this->selected_product_owner,
				'reseller' => null,
				'price_tier' => 'reseller',
				'new' => true
			],
			'resellers' => $resellers
		]);
	}

	public function delete_reseller() {
		$owner = App::get('owner', '', true);
		$reseller = App::get('reseller', '', true);
		if(!$owner || !$reseller) return $this->access_denied();

		list($owner_level, $owner_id) = explode('-', $owner);
		list($reseller_level, $reseller_id) = explode('-', $reseller);
		if(!Permission::get($owner_level, $owner_id)->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		App::sql()->delete("DELETE FROM product_reseller WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND reseller_level = '$reseller_level' AND reseller_id = '$reseller_id';");
		App::sql()->delete(
			"DELETE pp
			FROM product_price AS pp
			JOIN product AS p ON p.id = pp.product_id
			WHERE pp.seller_level = '$reseller_level' AND pp.seller_id = '$reseller_id' AND p.owner_level = '$owner_level' AND p.owner_id = '$owner_id';
		");
		App::sql()->delete(
			"DELETE psp
			FROM product_subscription_price AS psp
			JOIN product_subscription_type AS st ON st.id = psp.subscription_type_id
			WHERE psp.seller_level = '$reseller_level' AND psp.seller_id = '$reseller_id' AND st.owner_level = '$owner_level' AND st.owner_id = '$owner_id';
		");

		return $this->success();
	}

	public function save_reseller() {
		$data = App::json();

		// Create record
		$data = $data;
		$data = App::keep($data, ['owner', 'reseller', 'price_tier']);
		$data = App::ensure($data, ['owner', 'reseller'], '');
		$data = App::ensure($data, ['price_tier'], null);
		$data = App::escape($data);

		if(!$data['owner'] || !$data['reseller']) return $this->access_denied();
		list($owner_level, $owner_id) = explode('-', $data['owner']);
		list($reseller_level, $reseller_id) = explode('-', $data['reseller']);
		if(!Permission::get($owner_level, $owner_id)->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Data validation
		if(!$data['price_tier']) return $this->error('Please select a price point to sell at.');

		// Insert/update record
		$tier = $data['price_tier'];
		$data = App::wrap($data);

		// Get/create recommended pricing structure for reseller
		$recommended = App::sql()->query_row(
			"SELECT id FROM product_pricing_structure
			WHERE owner_level = '$reseller_level' AND owner_id = '$reseller_id'
			AND distribution_method = 'recommended' AND reseller_method = 'recommended' AND trade_method = 'recommended' AND retail_method = 'recommended'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if($recommended) {
			$recommended_id = $recommended['id'];
		} else {
			$recommended_id = App::insert('product_pricing_structure', [
				'owner_level' => $reseller_level,
				'owner_id' => $reseller_id,
				'description' => 'Recommended price',
				'distribution_method' => 'recommended',
				'reseller_method' => 'recommended',
				'trade_method' => 'recommended',
				'retail_method' => 'recommended'
			]) ?: null;
		}

		$exists = App::sql()->query_row("SELECT * from product_reseller WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND reseller_level = '$reseller_level' AND reseller_id = '$reseller_id' LIMIT 1;");
		if($exists) {
			// Update
			App::sql()->update(
				"UPDATE product_reseller
				SET price_tier = $data[price_tier]
				WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND reseller_level = '$reseller_level' AND reseller_id = '$reseller_id';
			");

			// Check if all products have their pricing records set.
			// If not, create pricing records as if it was a new reseller
			// This is to work around a bug where mass import didn't create product_price records in the first place.

			$resold = App::sql()->query(
				"SELECT p.id
				FROM product AS p
				LEFT JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$reseller_level' AND pp.seller_id = '$reseller_id'
				WHERE p.owner_level = '$owner_level' AND p.owner_id = '$owner_id' AND p.sold_to_reseller = 1 AND pp.product_id IS NULL;
			");

			if($resold) {
				// Found some products without pricing records set, create them.
				foreach($resold as $p) {
					App::insert_ignore('product_price', [
						'product_id' => $p->id,
						'seller_level' => $reseller_level,
						'seller_id' => $reseller_id,
						'unit_cost' => 0,
						'pricing_structure_id' => $recommended_id,
						'distribution_price' => 0,
						'reseller_price' => 0,
						'trade_price' => 0,
						'retail_price' => 0
					]);
					error_log("Fixed reseller pricing record for product $p->id.");
				}

				$cnt = count($resold);
				error_log("Finished fixing $cnt product pricing records.");
			}

		} else {
			// Insert
			App::sql()->insert(
				"INSERT INTO product_reseller (owner_level, owner_id, reseller_level, reseller_id, price_tier)
				VALUES ('$owner_level', '$owner_id', '$reseller_level', '$reseller_id', $data[price_tier]);
			");

			// Create product_price records for new reseller
			$resold = App::sql()->query("SELECT id FROM product WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND sold_to_reseller = 1;") ?: [];
			foreach($resold as $p) {
				App::insert_ignore('product_price', [
					'product_id' => $p->id,
					'seller_level' => $reseller_level,
					'seller_id' => $reseller_id,
					'unit_cost' => 0,
					'pricing_structure_id' => $recommended_id,
					'distribution_price' => 0,
					'reseller_price' => 0,
					'trade_price' => 0,
					'retail_price' => 0
				]);
			}

			// Create product_subscription_price records for new reseller
			$list = App::sql()->query("SELECT id FROM product_subscription_type WHERE owner_level = '$owner_level' AND owner_id = '$owner_id';") ?: [];
			foreach($list as $p) {
				App::insert_ignore('product_subscription_price', [
					'subscription_type_id' => $p->id,
					'seller_level' => $reseller_level,
					'seller_id' => $reseller_id,
					'unit_cost' => 0,
					'pricing_structure_id' => $recommended_id,
					'distribution_price' => 0,
					'reseller_price' => 0,
					'trade_price' => 0,
					'retail_price' => 0
				]);
			}
		}

		// Update pricing
		$pricing = new ProductPricing();
		$pricing->apply_reseller_change($owner_level, $owner_id);

		return $this->success();
	}

	public function list_products() {
		if(!$this->resolve_product_owners()) return $this->access_denied();

		$order = App::get('order', 'sku', true);
		if($order[0] === '-') $order = substr($order, 1).' DESC';

		if($order === 'model') $order = 'manufacturer_name, model';
		if($order === 'model DESC') $order = 'manufacturer_name DESC, model DESC';

		$condition = [];
		if(isset($_GET['unit'])) {
			$value = App::get('unit', '', true);
			$condition[] = "p.unit_id = '$value'";
		}
		if(isset($_GET['is_placeholder'])) {
			$value = App::get('is_placeholder', 0, true);
			$condition[] = "p.is_placeholder = '$value'";
		}
		if(isset($_GET['is_bundle'])) {
			$value = App::get('is_bundle', 0, true);
			$condition[] = "p.is_bundle = '$value'";
		}
		if(isset($_GET['is_stocked'])) {
			$value = App::get('is_stocked', 0, true);
			$condition[] = "p.is_stocked = '$value'";
		}
		if(isset($_GET['sold_to_customer'])) {
			$value = App::get('sold_to_customer', 0, true);
			$condition[] = "p.sold_to_customer = '$value'";
		}
		if(isset($_GET['sku_match'])) {
			$value = App::get('sku_match', '', true);
			$condition[] = "(p.sku = '$value' OR p.manufacturer_sku = '$value')";
		}

		$discontinued = 0;
		if(isset($_GET['discontinued'])) {
			$discontinued = App::get('discontinued', 0, true);
		}
		if(!$discontinued) $condition[] = "p.discontinued = 0";

		$condition = implode(' AND ', $condition);
		if($condition) $condition = "WHERE $condition";

		$list = App::sql()->query(
			"SELECT
				p.*,
				pp.unit_cost, pp.pricing_structure_id, pp.distribution_price, pp.reseller_price, pp.trade_price, pp.retail_price,
				m.name AS manufacturer_name,
				c.name AS category_name,
				u.name AS unit_name,
				ps.description AS pricing_structure_description,
				tg.tags,
				IF(p.owner_level = '$this->selected_product_owner_level' AND p.owner_id = '$this->selected_product_owner_id', 1, 0) AS own_product,
				COALESCE(sp.company_name, si.company_name) AS owner_name,
				uc.path AS image_url,
				wh.whinfo
			FROM product AS p
			JOIN product_price AS pp ON p.id = pp.product_id AND pp.seller_level = '$this->selected_product_owner_level' AND pp.seller_id = '$this->selected_product_owner_id'
			LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id
			LEFT JOIN product_category AS c ON c.id = p.category_id
			LEFT JOIN product_unit AS u ON u.id = p.unit_id
			LEFT JOIN product_pricing_structure AS ps ON ps.id = pp.pricing_structure_id
			LEFT JOIN (SELECT product_id, GROUP_CONCAT(tag_id, ' ') AS tags FROM product_tags GROUP BY product_id) AS tg ON tg.product_id = p.id
			LEFT JOIN user_content AS uc ON uc.id = p.image_id
			LEFT JOIN service_provider AS sp ON p.owner_level = 'SP' AND p.owner_id = sp.id
			LEFT JOIN system_integrator AS si ON p.owner_level = 'SI' AND p.owner_id = si.id
			LEFT JOIN (
				SELECT
					swp.product_id,
					GROUP_CONCAT(
						CONCAT(
							sw.description,
							' - ',
							IF(swp.min_qty IS NOT NULL, CONCAT('Min: ', TRIM(swp.min_qty) + 0, ' '), ''),
							IF(swp.max_qty IS NOT NULL, CONCAT('Max: ', TRIM(swp.max_qty) + 0, ' '), ''),
							IF(swp.location_id IS NOT NULL, CONCAT('Location: ', stl.description, ' '), '')
						)
						ORDER BY sw.description
						SEPARATOR '|'
					) AS whinfo
				FROM stock_warehouse_product AS swp
				JOIN stock_warehouse AS sw ON sw.id = swp.warehouse_id
				LEFT JOIN stock_location AS stl ON stl.id = swp.location_id
				GROUP BY swp.product_id
			) AS wh ON wh.product_id = p.id
			$condition
			ORDER BY $order;
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($list as &$item) {
			if($item['image_url']) {
				$item['image_url'] = UserContent::url_by_path($item['image_url']);
			}

			$item['whinfo'] = implode("\n", explode('|', $item['whinfo']) ?: []);
		}
		unset($item);

		$tags = App::sql()->query("SELECT t.id, t.name, tg.colour FROM product_tag AS t JOIN product_tag_group AS tg ON tg.id = t.group_id;");

		$duplicate_sku_list = App::sql()->query(
			"SELECT sku, COUNT(id) AS cnt
			FROM product
			WHERE owner_level = '$this->selected_product_owner_level' AND owner_id = '$this->selected_product_owner_id'
			GROUP BY sku
			HAVING cnt > 1
			ORDER BY sku;
		", MySQL::QUERY_ASSOC) ?: [];

		return $this->success([
			'list' => $list,
			'duplicate_sku' => array_map(function ($item) { return $item['sku']; }, $duplicate_sku_list),
			'tags' => $tags ?: [],
			'product_owners' => $this->product_owners,
			'selected_product_owner' => $this->selected_product_owner
		]);
	}

	private function get_bom_product_info($id, $seller_level, $seller_id) {
		$id = App::escape($id);

		$product = App::sql()->query_row(
			"SELECT
				p.id, p.sku, pm.name AS manufacturer_name, p.model, p.unit_id, p.is_placeholder,
				pp.unit_cost, pp.distribution_price, pp.reseller_price, pp.trade_price, pp.retail_price,
				uc.path AS image_url
			FROM product AS p
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
			LEFT JOIN product_entity AS pm ON p.manufacturer_id = pm.id
			LEFT JOIN user_content AS uc ON uc.id = p.image_id
			WHERE p.id = '$id'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$product) return false;

		if($product['image_url']) $product['image_url'] = UserContent::url_by_path($product['image_url']);

		$unit_id = $product['unit_id'];
		$units = App::sql()->query(
			"SELECT
				id, name, description, decimal_places,
				COALESCE(base_amount, 1) AS units,
				IF(base_unit_id IS NULL, 1, 0) AS is_base_unit
			FROM product_unit
			WHERE id = '$unit_id' OR base_unit_id = '$unit_id'
			ORDER BY is_base_unit DESC, units;
		", MySQL::QUERY_ASSOC);

		if(!$units) return false;

		$product['units'] = $units;

		return $product;
	}

	private function bom_has_loop($parent_id, $product_id, $visited = []) {
		if(!$parent_id || $parent_id === 'new') return false;

		if($parent_id == $product_id) return true;
		if(isset($visited[$parent_id])) return true;

		$parent_id = App::escape($parent_id);

		$visited[$parent_id] = true;
		$next = App::sql()->query("SELECT DISTINCT parent_id FROM product_bom WHERE product_id = '$parent_id';") ?: [];

		foreach($next as $p) {
			$result = $this->bom_has_loop($p->parent_id, $product_id, $visited);
			if($result) return true;
		}

		return false;
	}

	public function get_bom_product() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		list($id, $product_id) = App::get(['id', 'product_id'], 0, true);

		if(!$id) return $this->error('Product not found.');
		if(!$product_id) return $this->error('Product not found.');

		$info = $this->get_bom_product_info($product_id, $this->selected_product_owner_level, $this->selected_product_owner_id);
		if(!$info) return $this->error('Product not found.');
		if(!isset($info['units'][0])) return $this->error('Product has no unit of measure.');

		if($this->bom_has_loop($id, $product_id)) return $this->error('Circular reference detected.');

		return $this->success([
			'id' => 'new',
			'product_id' => $product_id,
			'quantity' => 1,
			'unit_id' => $info['units'][0]['id'],
			'is_separable' => false,
			'info' => $info
		]);
	}

	public function get_product() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$sql = App::sql();
		$id = App::get('id', 0, true);
		$seller_level = $this->selected_product_owner_level;
		$seller_id = $this->selected_product_owner_id;

		$details = $sql->query_row(
			"SELECT
				p.*,
				pp.unit_cost, pp.pricing_structure_id, pp.distribution_price, pp.reseller_price, pp.trade_price, pp.retail_price, pp.recommended_labour
			FROM product AS p
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
			WHERE p.id = '$id' LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$details) return $this->error('Product not found.');

		$owner_level = $details['owner_level'];
		$owner_id = $details['owner_id'];
		$is_owner = ($owner_level == $seller_level) && ($owner_id == $seller_id);

		$owner_filter = "
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$seller_level' AND pr.reseller_id = '$seller_id'
			WHERE ((t.owner_level = '$seller_level' AND t.owner_id = '$seller_id') OR pr.owner_level IS NOT NULL)
		";

		$manufacturers = [];
		$categories = [];
		$base_units = [];

		if($is_owner) {
			$manufacturers = $sql->query("SELECT id, name FROM product_entity WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' AND is_manufacturer = 1 AND archived = 0 ORDER BY name;");
			$categories = $sql->query("SELECT id, name FROM product_category WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' ORDER BY name;");
			$base_units = $sql->query("SELECT id, description, name FROM product_unit WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' AND base_unit_id IS NULL ORDER BY description;");
		} else {
			if($details['manufacturer_id']) $manufacturers = $sql->query("SELECT id, name FROM product_entity WHERE id = '$details[manufacturer_id]';");
			if($details['category_id']) $categories = $sql->query("SELECT id, name FROM product_category WHERE id = '$details[category_id]';");
			if($details['unit_id']) $base_units = $sql->query("SELECT id, description, name FROM product_unit WHERE id = '$details[unit_id]';");
		}

		$labour_types = $sql->query("SELECT t.* FROM product_labour_type AS t $owner_filter ORDER BY description;", MySQL::QUERY_ASSOC) ?: [];
		$labour_categories = $sql->query("SELECT t.* FROM product_labour_category AS t $owner_filter ORDER BY description;");
		$subscription_types = $sql->query("SELECT * FROM product_subscription_type JOIN product_subscription_price ON subscription_type_id = id AND seller_level = '$seller_level' AND seller_id = '$seller_id' ORDER BY description;", MySQL::QUERY_ASSOC) ?: [];
		$subscription_categories = $sql->query("SELECT t.* FROM product_subscription_category AS t $owner_filter ORDER BY description;");
		$pricing_structures = $sql->query("SELECT * FROM product_pricing_structure WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' ORDER BY description;");
		$modules = $sql->query("SELECT t.id, t.description FROM project_module AS t $owner_filter ORDER BY display_order;");
		$systems = $sql->query("SELECT t.id, t.description, t.module_id FROM project_system AS t $owner_filter ORDER BY description;");

		// Pricing permissions
		$perm = Permission::get($seller_level, $seller_id);
		$labour_pricing = $perm->check(Permission::STOCK_LABOUR_PRICE);
		$subscription_pricing = $perm->check(Permission::STOCK_SUBSCRIPTION_PRICE);

		if(!$labour_pricing) {
			foreach($labour_types as &$item) {
				$item['hourly_cost'] = 0;
				$item['hourly_price'] = 0;
			}
			unset($item);
		}

		if(!$subscription_pricing) {
			foreach($subscription_types as &$item) {
				$item['unit_cost'] = 0;
				$item['distribution_price'] = 0;
				$item['reseller_price'] = 0;
				$item['trade_price'] = 0;
				$item['retail_price'] = 0;
			}
			unset($item);
		}

		$warehouses = $sql->query(
			"SELECT
				w.id,
				w.description,
				COALESCE(wp.min_qty, 0) AS min_qty,
				COALESCE(wp.max_qty, 0) AS max_qty,
				wp.location_id
			FROM stock_warehouse AS w
			LEFT JOIN stock_warehouse_product AS wp ON wp.warehouse_id = w.id AND wp.product_id = '$id'
			WHERE w.owner_level = '$seller_level' AND w.owner_id = '$seller_id' AND w.archived = 0
			ORDER BY w.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$wlist = [];
		foreach($warehouses as $w) {
			$wlist[] = $w['id'];
		}
		$wlist = implode(',', $wlist);

		$warehouse_locations = [];
		if($wlist) {
			$warehouse_locations = $sql->query("SELECT id, warehouse_id, description FROM stock_location WHERE warehouse_id IN ($wlist) AND archived = 0 ORDER BY warehouse_id, rack, bay, level;");
		}

		$image_url = null;
		if($details['image_id']) {
			$uc = new UserContent($details['image_id']);
			if($uc->info) $image_url = $uc->get_url();
		}

		$labour = $sql->query(
			"SELECT
				id, labour_type_id, labour_hours,
				IF(seller_level = '$seller_level' AND seller_id = '$seller_id', 1, 0) AS editable
			FROM product_labour
			WHERE product_id = '$id' AND ((seller_level = '$owner_level' AND seller_id = '$owner_id') OR (seller_level = '$seller_level' AND seller_id = '$seller_id'))
			ORDER BY editable;
		", MySQL::QUERY_ASSOC);

		$subscription = $sql->query(
			"SELECT
				id, subscription_type_id, quantity, selection,
				IF(seller_level = '$seller_level' AND seller_id = '$seller_id', 1, 0) AS editable
			FROM product_subscription
			WHERE product_id = '$id' AND ((seller_level = '$owner_level' AND seller_id = '$owner_id') OR (seller_level = '$seller_level' AND seller_id = '$seller_id'))
			ORDER BY editable, selection, id;
		", MySQL::QUERY_ASSOC);

		if(!$is_owner) {
			$alternatives = [];
			$placeholders = [];
			$accessories = [];
			$bom = [];
			$used_bom = [];
			$used_placeholder = [];
			$used_system = [];
			$suppliers = [];
			$bundle = null;

		} else {
			$alternatives = $sql->query(
				"SELECT
					p.id, p.sku, pm.name AS manufacturer_name, p.model, p.unit_id, pp.unit_cost,
					uc.path AS image_url,
					SUM(IF(p.id = pa.product_id, -1, 1)) AS relationship
				FROM product AS p
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$seller_level' AND pp.seller_id = '$seller_id'
				JOIN product_alternatives AS pa ON (pa.product_id = p.id OR pa.alternative_product_id = p.id) AND (pa.product_id = '$id' OR pa.alternative_product_id = '$id')
				LEFT JOIN product_entity AS pm ON p.manufacturer_id = pm.id
				LEFT JOIN user_content AS uc ON uc.id = p.image_id
				WHERE p.id <> '$id'
				GROUP BY p.id, p.sku, manufacturer_name, p.model, p.unit_id
				ORDER BY relationship DESC, p.sku;
			", MySQL::QUERY_ASSOC) ?: [];

			$placeholders = $sql->query(
				"SELECT
					p.id, p.sku, pm.name AS manufacturer_name, p.model, p.unit_id, ppr.unit_cost, uc.path AS image_url
				FROM product_placeholders AS pp
				JOIN product AS p ON p.id = pp.product_id
				JOIN product_price AS ppr ON ppr.product_id = p.id AND ppr.seller_level = '$seller_level' AND ppr.seller_id = '$seller_id'
				LEFT JOIN product_entity AS pm ON p.manufacturer_id = pm.id
				LEFT JOIN user_content AS uc ON uc.id = p.image_id
				WHERE pp.placeholder_id = '$id'
				ORDER BY p.sku;
			", MySQL::QUERY_ASSOC) ?: [];

			$accessories = $sql->query(
				"SELECT
					p.id, p.sku, pm.name AS manufacturer_name, p.model,
					uc.path AS image_url,
					pa.system_id, pa.default_quantity
				FROM product_accessories AS pa
				JOIN product AS p ON p.id = pa.accessory_id
				LEFT JOIN product_entity AS pm ON p.manufacturer_id = pm.id
				LEFT JOIN user_content AS uc ON uc.id = p.image_id
				WHERE pa.product_id = '$id'
				ORDER BY p.sku;
			", MySQL::QUERY_ASSOC) ?: [];

			foreach($alternatives as &$item) if($item['image_url']) $item['image_url'] = UserContent::url_by_path($item['image_url']); unset($item);
			foreach($placeholders as &$item) if($item['image_url']) $item['image_url'] = UserContent::url_by_path($item['image_url']); unset($item);
			foreach($accessories as &$item) if($item['image_url']) $item['image_url'] = UserContent::url_by_path($item['image_url']); unset($item);

			$bom = $sql->query(
				"SELECT id, product_id, quantity, unit_id, is_separable
				FROM product_bom
				WHERE parent_id = '$id';
			", MySQL::QUERY_ASSOC) ?: [];
			foreach($bom as &$item) {
				$item['info'] = $this->get_bom_product_info($item['product_id'], $owner_level, $owner_id);
			}
			unset($item);

			$used_bom = $sql->query(
				"SELECT
					p.id, p.sku, pm.name AS manufacturer_name, p.model
				FROM product_bom AS bom
				JOIN product AS p ON p.id = bom.parent_id
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				WHERE bom.product_id = '$id';
			", MySQL::QUERY_ASSOC);

			$used_placeholder = $sql->query(
				"SELECT
					p.id, p.sku, pm.name AS manufacturer_name, p.model
				FROM product_placeholders AS pp
				JOIN product AS p ON p.id = pp.placeholder_id
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				WHERE pp.product_id = '$id';
			", MySQL::QUERY_ASSOC);

			$used_system = $sql->query(
				"SELECT
					ps.id, ps.description
				FROM project_system_products AS psp
				JOIN project_system AS ps ON ps.id = psp.system_id
				WHERE psp.product_id = '$id';
			", MySQL::QUERY_ASSOC);

			$suppliers = $sql->query(
				"SELECT
					ps.supplier_id AS id,
					ps.sku, ps.is_primary,
					e.name, e.posttown, e.postcode
				FROM product_suppliers AS ps
				JOIN product_entity AS e ON e.id = ps.supplier_id
				WHERE ps.product_id = '$id'
				ORDER BY ps.is_primary DESC, e.name;
			", MySQL::QUERY_ASSOC);

			$bundle = Bundle::for_product($id);
			$bundle = $bundle->get_object();
		}

		$rp = [
			'cost' => 0,
			'distribution_price' => 0,
			'reseller_price' => 0,
			'trade_price' => 0,
			'retail_price' => 0
		];

		// If it's not the owner, calculate recommended pricing based on sharing settings and the owner prices
		if(!$is_owner) {
			$price = App::sql()->query_row("SELECT * FROM product_price WHERE product_id = '$id' AND seller_level = '$owner_level' AND seller_id = '$owner_id';", MySQL::QUERY_ASSOC);
			$tier = App::sql()->query_row("SELECT price_tier FROM product_reseller WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND reseller_level = '$seller_level' AND reseller_id = '$seller_id';", MySQL::QUERY_ASSOC);
			$tier = $tier ? $tier['price_tier'] : 'retail';

			$rp['cost'] = $tier === 'cost' ? $price['unit_cost'] : $price["{$tier}_price"];
			$rp['distribution_price'] = in_array($tier, ['cost', 'distribution']) ? $price['distribution_price'] : $rp['cost'];
			$rp['reseller_price'] = in_array($tier, ['cost', 'distribution', 'reseller']) ? $price['reseller_price'] : $rp['cost'];
			$rp['trade_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade']) ? $price['trade_price'] : $rp['cost'];
			$rp['retail_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade', 'retail']) ? $price['retail_price'] : $rp['cost'];
		}

		$details['alternatives'] = $alternatives;
		$details['placeholders'] = $placeholders;
		$details['accessories'] = $accessories;
		$details['bom'] = $bom ?: [];
		$details['labour'] = $labour ?: [];
		$details['subscription'] = $subscription ?: [];
		$details['recommended_price'] = $rp;
		$details['warehouses'] = $warehouses ?: [];
		$details['suppliers'] = $suppliers ?: [];
		$details['bundle'] = $bundle;

		return $this->success([
			'details' => $details,
			'editable' => $is_owner,
			'labour_pricing' => $labour_pricing,
			'subscription_pricing' => $subscription_pricing,
			'image_url' => $image_url,
			'barcode_url' => APP_URL.'/barcode?output=png&wf=1&h=20&code=',
			'list' => [
				'manufacturers' => $manufacturers ?: [],
				'categories' => $categories ?: [],
				'labour_types' => $labour_types ?: [],
				'labour_categories' => $labour_categories ?: [],
				'subscription_types' => $subscription_types ?: [],
				'subscription_categories' => $subscription_categories ?: [],
				'base_units' => $base_units ?: [],
				'pricing_structures' => $pricing_structures ?: [],
				'systems' => $systems ?: [],
				'modules' => $modules ?: [],
				'warehouse_locations' => $warehouse_locations ?: []
			],
			'used_by' => [
				'bom' => $used_bom ?: [],
				'placeholder' => $used_placeholder ?: [],
				'system' => $used_system ?: []
			]
		]);
	}

	public function new_product() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$sql = App::sql();
		$seller_level = $this->selected_product_owner_level;
		$seller_id = $this->selected_product_owner_id;

		$owner_filter = "
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = '$seller_level' AND pr.reseller_id = '$seller_id'
			WHERE ((t.owner_level = '$seller_level' AND t.owner_id = '$seller_id') OR pr.owner_level IS NOT NULL)
		";

		$manufacturers = $sql->query("SELECT id, name FROM product_entity WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' AND is_manufacturer = 1 AND archived = 0 ORDER BY name;");
		$categories = $sql->query("SELECT id, name FROM product_category WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' ORDER BY name;");
		$base_units = $sql->query("SELECT id, description, name FROM product_unit WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' AND base_unit_id IS NULL ORDER BY description;");

		$labour_types = $sql->query("SELECT t.* FROM product_labour_type AS t $owner_filter ORDER BY description;", MySQL::QUERY_ASSOC) ?: [];
		$labour_categories = $sql->query("SELECT t.* FROM product_labour_category AS t $owner_filter ORDER BY description;");
		$subscription_types = $sql->query("SELECT * FROM product_subscription_type JOIN product_subscription_price ON subscription_type_id = id AND seller_level = '$seller_level' AND seller_id = '$seller_id' ORDER BY description;", MySQL::QUERY_ASSOC) ?: [];
		$subscription_categories = $sql->query("SELECT t.* FROM product_subscription_category AS t $owner_filter ORDER BY description;");
		$pricing_structures = $sql->query("SELECT * FROM product_pricing_structure WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' ORDER BY description;");
		$modules = $sql->query("SELECT t.id, t.description FROM project_module AS t $owner_filter ORDER BY display_order;");
		$systems = $sql->query("SELECT t.id, t.description, t.module_id FROM project_system AS t $owner_filter ORDER BY description;");

		// Pricing permissions
		$perm = Permission::get($seller_level, $seller_id);
		$labour_pricing = $perm->check(Permission::STOCK_LABOUR_PRICE);
		$subscription_pricing = $perm->check(Permission::STOCK_SUBSCRIPTION_PRICE);

		if(!$labour_pricing) {
			foreach($labour_types as &$item) {
				$item['hourly_cost'] = 0;
				$item['hourly_price'] = 0;
			}
			unset($item);
		}

		if(!$subscription_pricing) {
			foreach($subscription_types as &$item) {
				$item['unit_cost'] = 0;
				$item['distribution_price'] = 0;
				$item['reseller_price'] = 0;
				$item['trade_price'] = 0;
				$item['retail_price'] = 0;
			}
			unset($item);
		}

		$warehouses = $sql->query(
			"SELECT
				w.id,
				w.description,
				0 AS min_qty,
				0 AS max_qty,
				NULL AS location_id
			FROM stock_warehouse AS w
			WHERE w.owner_level = '$seller_level' AND w.owner_id = '$seller_id' AND w.archived = 0
			ORDER BY w.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$wlist = [];
		foreach($warehouses as $w) {
			$wlist[] = $w['id'];
		}
		$wlist = implode(',', $wlist);

		$warehouse_locations = [];
		if($wlist) {
			$warehouse_locations = $sql->query("SELECT id, warehouse_id, description FROM stock_location WHERE warehouse_id IN ($wlist) AND archived = 0 ORDER BY warehouse_id, rack, bay, level;");
		}

		$default_unit = null;
		$r = App::sql()->query_row("SELECT id FROM product_unit WHERE owner_level = '$seller_level' AND owner_id = '$seller_id' AND is_default = 1 LIMIT 1;", MySQL::QUERY_ASSOC);
		if($r) $default_unit = $r['id'];

		$bundle = new Bundle();

		return $this->success([
			'details' => [
				'id' => 'new',
				'owner_level' => $seller_level,
				'owner_id' => $seller_id,
				'manufacturer_id' => null,
				'manufacturer_sku' => null,
				'category_id' => null,
				'unit_id' => $default_unit,
				'pricing_structure_id' => null,
				'sold_to_customer' => 1,
				'sold_to_reseller' => 1,
				'is_stocked' => 1,
				'recommended_labour' => 1,
				'alternatives' => [],
				'placeholders' => [],
				'accessories' => [],
				'bom' => [],
				'labour' => [],
				'subscription' => [],
				'recommended_price' => [
					'cost' => 0,
					'distribution_price' => 0,
					'reseller_price' => 0,
					'trade_price' => 0,
					'retail_price' => 0
				],
				'warehouses' => $warehouses ?: [],
				'suppliers' => [],
				'bundle' => $bundle->get_object()
			],
			'editable' => true,
			'labour_pricing' => $labour_pricing,
			'subscription_pricing' => $subscription_pricing,
			'barcode_url' => APP_URL.'/barcode?output=png&wf=1&h=20&code=',
			'list' => [
				'manufacturers' => $manufacturers ?: [],
				'categories' => $categories ?: [],
				'labour_types' => $labour_types ?: [],
				'labour_categories' => $labour_categories ?: [],
				'subscription_types' => $subscription_types ?: [],
				'subscription_categories' => $subscription_categories ?: [],
				'base_units' => $base_units ?: [],
				'pricing_structures' => $pricing_structures ?: [],
				'modules' => $modules ?: [],
				'systems' => $systems ?: [],
				'warehouse_locations' => $warehouse_locations ?: []
			],
			'used_by' => [
				'bom' => [],
				'placeholder' => [],
				'system' => []
			]
		]);
	}

	public function save_product() {
		if(!$this->resolve_product_owners(false)) return $this->access_denied();

		$sql = App::sql();
		$data = App::json();
		$seller_level = $this->selected_product_owner_level;
		$seller_id = $this->selected_product_owner_id;

		// Check if ID is set
		$id = $data['id'];
		$id = $sql->escape($id);
		$is_new = $id === 'new';
		if(!$id) return $this->access_denied();

		// Check if owner is editing
		$is_owner = ($data['owner_level'] == $seller_level) && ($data['owner_id'] == $seller_id);

		// Create record
		$record = $data;
		$record = App::keep($record, [
			'owner_level', 'owner_id',
			'sku', 'manufacturer_id', 'manufacturer_sku', 'model', 'category_id', 'short_description', 'long_description', 'unit_id',
			'width', 'height', 'depth',
			'has_bom', 'is_placeholder', 'is_bundle', 'is_stocked', 'sold_to_customer', 'sold_to_reseller',
			'image_id', 'discontinued'
		]);
		$record = App::ensure($record, ['owner_level', 'owner_id', 'sku', 'manufacturer_sku', 'model', 'short_description', 'long_description'], '');
		$record = App::ensure($record, ['manufacturer_id', 'category_id', 'unit_id', 'image_id'], null);
		$record = App::ensure($record, ['has_bom', 'is_placeholder', 'is_bundle', 'is_stocked', 'sold_to_customer', 'sold_to_reseller', 'discontinued'], 0);

		$extra_data = App::keep($data, ['manufacturer_name', 'category_name']);
		$extra_data = App::ensure($extra_data, ['manufacturer_name', 'category_name'], '');

		// Price data
		$price = $data;
		$price = App::keep($price, ['unit_cost', 'pricing_structure_id', 'distribution_price', 'reseller_price', 'trade_price', 'retail_price', 'recommended_labour']);
		$price = App::ensure($price, ['unit_cost', 'distribution_price', 'reseller_price', 'trade_price', 'retail_price', 'recommended_labour'], 0);
		$price = App::ensure($price, ['pricing_structure_id'], null);

		// Check permissions
		if($is_new && !$is_owner) return $this->access_denied();

		$original = null;
		if(!$is_new) {
			$original = App::select('product', $id);
			if(!$original) return $this->error('Product not found.');
			$owner_level = $original['owner_level'];
			$owner_id = $original['owner_id'];
		} else {
			$owner_level = $seller_level;
			$owner_id = $seller_id;
		}

		if($is_owner) {
			// Data validation
			if($record['manufacturer_id'] === 'new' && !$extra_data['manufacturer_name']) return $this->error('Please enter the new manufacturer\'s name.');
			if($record['category_id'] === 'new' && !$extra_data['category_name']) return $this->error('Please enter the new category\'s name.');

			if($record['model'] === '' && $record['short_description'] === '') {
				return $this->error('Please enter model or short description.');
			}

			if(!$record['unit_id']) return $this->error('Please select unit of measure.');

			if($id !== 'new' && $record['is_placeholder'] == 1) {
				// Make sure item has no placeholders itself
				$placeholder = $sql->query("SELECT placeholder_id FROM product_placeholders WHERE product_id = '$id';");
				if($placeholder) return $this->error('Item cannot be a placeholder, as it already has one or more placeholders itself.');
			}

			// Check for circular references in the BOM
			if($record['has_bom'] == 1) {
				$list = $data['bom'] ?: [];
				foreach($list as $item) {
					$item_product_id = $item['product_id'];
					if($this->bom_has_loop($id, $item_product_id)) return $this->error('Circular reference detected.');
				}
			}

			// Remove usage from previous user content
			$register_image = true;

			if($id !== 'new') {
				$image_id = $original['image_id'];
				if($image_id == $record['image_id']) {
					// No change
					$register_image = false;
				} else if($image_id) {
					$uc = new UserContent($image_id);
					$uc->remove_usage();
				}
			}

			// Create manufacturer/category if needed
			if($record['manufacturer_id'] === 'new') {
				$record['manufacturer_id'] = App::insert('product_entity', [
					'owner_level' => $owner_level,
					'owner_id' => $owner_id,
					'name' => $extra_data['manufacturer_name'],
					'is_manufacturer' => 1
				]);
			}
			if($record['category_id'] === 'new') {
				$record['category_id'] = App::insert('product_category', [
					'owner_level' => $owner_level,
					'owner_id' => $owner_id,
					'name' => $extra_data['category_name']
				]);
			}

			// Insert/update record
			$id = App::upsert('product', $id, $record);
			if(!$id) return $this->error('Error saving data.');
		}

		// Save prices
		if($is_new) {
			$price['product_id'] = $id;
			$price['seller_level'] = $seller_level;
			$price['seller_id'] = $seller_id;
			App::insert('product_price', $price);
		} else {
			$price = App::escape_and_wrap($price);
			$unit_cost = $price['unit_cost'];
			$pricing_structure_id = $price['pricing_structure_id'];
			$distribution_price = $price['distribution_price'];
			$reseller_price = $price['reseller_price'];
			$trade_price = $price['trade_price'];
			$retail_price = $price['retail_price'];
			$recommended_labour = $price['recommended_labour'];

			if($is_owner) {
				App::sql()->update(
					"UPDATE product_price
					SET
						unit_cost = $unit_cost,
						pricing_structure_id = $pricing_structure_id,
						distribution_price = $distribution_price,
						reseller_price = $reseller_price,
						trade_price = $trade_price,
						retail_price = $retail_price,
						recommended_labour = $recommended_labour
					WHERE product_id = '$id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';
				");
			} else {
				// Don't update cost if item is not owned
				App::sql()->update(
					"UPDATE product_price
					SET
						pricing_structure_id = $pricing_structure_id,
						distribution_price = $distribution_price,
						reseller_price = $reseller_price,
						trade_price = $trade_price,
						retail_price = $retail_price,
						recommended_labour = $recommended_labour
					WHERE product_id = '$id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';
				");
			}
		}

		// Save warehouse settings
		$warehouse_list = $sql->query("SELECT id FROM stock_warehouse WHERE owner_level = '$seller_level' AND owner_id = '$seller_id';", MySQL::QUERY_ASSOC);
		if($warehouse_list) {
			$wlist = [];
			foreach($warehouse_list as $w) {
				$wlist[] = $w['id'];
			}
			$wids = "'".implode("','", $wlist)."'";

			$sql->delete("DELETE FROM stock_warehouse_product WHERE product_id = '$id' AND warehouse_id IN ($wids)");

			$list = $data['warehouses'];
			foreach($list as $item) {
				if(!$item['min_qty'] || !$item['max_qty']) {
					$item['min_qty'] = null;
					$item['max_qty'] = null;
				}

				if($item['min_qty'] || $item['max_qty'] || $item['location_id']) {
					if(in_array($item['id'], $wlist)) {
						App::insert('stock_warehouse_product', [
							'warehouse_id' => $item['id'],
							'product_id' => $id,
							'min_qty' => $item['min_qty'],
							'max_qty' => $item['max_qty'],
							'location_id' => $item['location_id']
						]);
					}
				}
			}
		}

		if($is_owner) {
			// Add/remove price records
			if($record['sold_to_reseller'] && (!$original || !$original['sold_to_reseller'])) {
				// Reseller flag is switched on, create price records
				$resellers = App::sql()->query("SELECT reseller_level, reseller_id FROM product_reseller WHERE owner_level = '$record[owner_level]' AND owner_id = '$record[owner_id]';", MySQL::QUERY_ASSOC) ?: [];
				foreach($resellers as $r) {
					// Get/create recommended pricing structure for reseller
					$recommended = App::sql()->query_row(
						"SELECT id FROM product_pricing_structure
						WHERE owner_level = '$r[reseller_level]' AND owner_id = '$r[reseller_id]'
						AND distribution_method = 'recommended' AND reseller_method = 'recommended' AND trade_method = 'recommended' AND retail_method = 'recommended'
						LIMIT 1;
					", MySQL::QUERY_ASSOC);

					if($recommended) {
						$recommended_id = $recommended['id'];
					} else {
						$recommended_id = App::insert('product_pricing_structure', [
							'owner_level' => $r['reseller_level'],
							'owner_id' => $r['reseller_id'],
							'description' => 'Recommended price',
							'distribution_method' => 'recommended',
							'reseller_method' => 'recommended',
							'trade_method' => 'recommended',
							'retail_method' => 'recommended'
						]) ?: null;
					}

					// Create new product_price record for reseller
					App::insert('product_price', [
						'product_id' => $id,
						'seller_level' => $r['reseller_level'],
						'seller_id' => $r['reseller_id'],
						'unit_cost' => 0,
						'pricing_structure_id' => $recommended_id,
						'distribution_price' => 0,
						'reseller_price' => 0,
						'trade_price' => 0,
						'retail_price' => 0
					]);
				}
			} else if(!$record['sold_to_reseller'] && $original && $original['sold_to_reseller']) {
				// Reseller flag is switched off, remove reseller price records
				App::sql()->delete("DELETE FROM product_price WHERE product_id = '$id' AND (seller_level <> '$record[owner_level]' OR seller_id <> '$record[owner_id]');");
			}

			// Add usage to new image if any
			if($record['image_id'] && $register_image) {
				$uc = new UserContent($record['image_id']);
				$uc->add_usage();
			}

			$unit_id = App::escape($record['unit_id']);

			// Save placeholders
			$sql->delete("DELETE FROM product_placeholders WHERE placeholder_id = '$id';");

			if($record['is_placeholder'] == 1) {
				$list = $data['placeholders'] ?: [];
				foreach($list as $item) {
					$item_id = App::escape($item['id']);
					$invalid = $sql->query_row("SELECT id FROM product WHERE id = '$item_id' AND (is_placeholder = 1 OR unit_id <> '$unit_id');");

					if($id == $item_id) continue;
					if($invalid) continue;

					App::insert('product_placeholders', [
						'placeholder_id' => $id,
						'product_id' => $item_id
					]);
				}
			}

			// Save alternatives
			$sql->delete("DELETE FROM product_alternatives WHERE product_id = '$id' OR alternative_product_id = '$id';");

			if($record['is_placeholder'] == 0 && $record['is_bundle'] == 0 && $record['is_stocked'] == 1) {
				$list = $data['alternatives'] ?: [];
				foreach($list as $item) {
					$item_id = App::escape($item['id']);
					$relationship = $item['relationship'];
					$invalid = $sql->query_row("SELECT id FROM product WHERE id = '$item_id' AND (is_placeholder = 1 OR unit_id <> '$unit_id');");

					if($id == $item_id) continue;
					if($invalid) continue;

					if($relationship == 0 || $relationship == 1) {
						App::insert('product_alternatives', [
							'product_id' => $id,
							'alternative_product_id' => $item_id
						]);
					}

					if($relationship == 0 || $relationship == -1) {
						App::insert('product_alternatives', [
							'product_id' => $item_id,
							'alternative_product_id' => $id
						]);
					}
				}
			}

			// Save accessories
			$sql->delete("DELETE FROM product_accessories WHERE product_id = '$id';");

			if($record['is_placeholder'] != 1) {
				$list = $data['accessories'] ?: [];
				foreach($list as $item) {
					$item_id = App::escape($item['id']);
					$invalid = $sql->query_row("SELECT id FROM product WHERE id = '$item_id' AND is_placeholder = 1;");

					if($id == $item_id) continue;
					if($invalid) continue;

					App::insert('product_accessories', [
						'product_id' => $id,
						'accessory_id' => $item_id,
						'system_id' => $item['system_id'] ?: null,
						'default_quantity' => is_numeric($item['default_quantity']) ? $item['default_quantity'] : 0
					]);
				}
			}

			// Save BOM
			if($record['has_bom'] == 0) {
				$sql->delete("DELETE FROM product_bom WHERE parent_id = '$id';");
			} else {
				if(isset($data['bom_deleted'])) {
					$list = $data['bom_deleted'] ?: [];
					foreach($list as $item) {
						if($item['id'] !== 'new') App::delete('product_bom', $item['id']);
					}
				}

				$list = $data['bom'] ?: [];
				foreach($list as $item) {
					$item_id = App::escape($item['id']);
					$item_product_id = $item['product_id'];
					$item_quantity = $item['quantity'] ?: 0;
					$item_unit_id = $item['unit_id'];
					$item_is_separable = $item['is_separable'] ? 1 : 0;

					if($id == $item_product_id) continue;
					if($item_unit_id == null || $item_product_id == null) continue;
					$exists = $item_id === 'new' ? false : $sql->query_row("SELECT id FROM product_bom WHERE id = '$item_id';");

					if(!$exists) $item_id = 'new';
					$item_id = App::upsert('product_bom', $item_id, [
						'parent_id' => $id,
						'product_id' => $item_product_id,
						'quantity' => $item_quantity,
						'unit_id' => $item_unit_id,
						'is_separable' => $item_is_separable
					]);
				}
			}

			// Save suppliers
			$list = $data['suppliers'] ?: [];
			$sql->delete("DELETE FROM product_suppliers WHERE product_id = '$id';");
			if(!$record['is_placeholder'] && !$record['is_bundle']) {
				foreach($list as $item) {
					$item = App::ensure($item, ['supplier_id', 'is_primary'], 0);
					$item = App::ensure($item, ['sku'], null);

					if($item['id']) {
						App::insert('product_suppliers', [
							'product_id' => $id,
							'supplier_id' => $item['id'],
							'sku' => $item['sku'] ?: null,
							'is_primary' => $item['is_primary'] ? 1 : 0
						]);
					}
				}
			}

			// Save bundle options
			if($record['is_bundle'] && isset($data['bundle'])) {
				$bundle_data = $data['bundle'];
				$bundle = Bundle::for_product($id);
				$bundle->load_object($bundle_data);

				if(isset($bundle_data['record']['new_version']) && $bundle_data['record']['new_version']) {
					$bundle->save_new_version();
				} else {
					$bundle->save();
				}
			}
		}

		// Save labour
		if(isset($data['labour_deleted'])) {
			$list = $data['labour_deleted'] ?: [];
			foreach($list as $item) {
				if($item['id'] !== 'new') {
					$rec = App::select('product_labour', $item['id']);
					if($rec && $rec['seller_level'] == $seller_level && $rec['seller_id'] == $seller_id) {
						App::delete('product_labour', $item['id']);
					}
				}
			}
		}

		$list = $data['labour'] ?: [];
		foreach($list as $item) {
			$item_id = App::escape($item['id']);
			$item_labour_type_id = $item['labour_type_id'];
			$item_labour_hours = $item['labour_hours'] ?: 0;

			// Details have been cleared, ignore/delete item
			if($item_labour_type_id === null || $item_labour_hours == 0) {
				if($item_id === 'new') {
					continue;
				} else {
					$rec = App::select('product_labour', $item_id);
					if($rec && $rec['seller_level'] == $seller_level && $rec['seller_id'] == $seller_id) {
						App::delete('product_labour', $item_id);
					}
					continue;
				}
			}

			$exists = $item_id === 'new' ? false : $sql->query_row("SELECT id, seller_level, seller_id FROM product_labour WHERE id = '$item_id';", MySQL::QUERY_ASSOC);
			if($exists) {
				if($exists['seller_level'] != $seller_level || $exists['seller_id'] != $seller_id) continue;
			} else {
				$item_id = 'new';
			}

			$item_id = App::upsert('product_labour', $item_id, [
				'product_id' => $id,
				'labour_type_id' => $item_labour_type_id,
				'labour_hours' => $item_labour_hours,
				'seller_level' => $seller_level,
				'seller_id' => $seller_id
			]);
		}

		// Save subscriptions
		if(isset($data['subscription_deleted'])) {
			$list = $data['subscription_deleted'] ?: [];
			foreach($list as $item) {
				if($item['id'] !== 'new') {
					$rec = App::select('product_subscription', $item['id']);
					if($rec && $rec['seller_level'] == $seller_level && $rec['seller_id'] == $seller_id) {
						App::delete('product_subscription', $item['id']);
					}
				}
			}
		}

		$list = $data['subscription'] ?: [];
		foreach($list as $item) {
			$item_id = App::escape($item['id']);
			$item_subscription_type_id = $item['subscription_type_id'];
			$item_quantity = $item['quantity'] ?: 0;
			$item_selection = $item['selection'] ?: 'fixed';

			// Details have been cleared, ignore/delete item
			if($item_subscription_type_id === null || $item_quantity == 0) {
				if($item_id === 'new') {
					continue;
				} else {
					$rec = App::select('product_subscription', $item_id);
					if($rec && $rec['seller_level'] == $seller_level && $rec['seller_id'] == $seller_id) {
						App::delete('product_subscription', $item_id);
					}
					continue;
				}
			}

			$exists = $item_id === 'new' ? false : $sql->query_row("SELECT id, seller_level, seller_id FROM product_subscription WHERE id = '$item_id';", MySQL::QUERY_ASSOC);
			if($exists) {
				if($exists['seller_level'] != $seller_level || $exists['seller_id'] != $seller_id) continue;
			} else {
				$item_id = 'new';
			}

			$item_id = App::upsert('product_subscription', $item_id, [
				'product_id' => $id,
				'subscription_type_id' => $item_subscription_type_id,
				'quantity' => $item_quantity,
				'selection' => $item_selection,
				'seller_level' => $seller_level,
				'seller_id' => $seller_id
			]);
		}

		if($is_owner) {
			if($record['discontinued']) {
				// Product is discontinued, break all usage links
				$sql->delete("DELETE FROM product_bom WHERE product_id = '$id';");
				$sql->delete("DELETE FROM product_placeholders WHERE product_id = '$id';");
				$sql->delete("DELETE FROM product_alternatives WHERE product_id = '$id' OR alternative_product_id = '$id';");
				$sql->delete("DELETE FROM project_system_products WHERE product_id = '$id';");
			}
		}

		// Update pricing
		$pricing = new ProductPricing();
		$pricing->apply_product_change($id);

		return $this->success($id);
	}

	function clone_product() {
		$sql = App::sql();
		$data = App::json();

		$data = App::keep($data, ['id', 'sku', 'model', 'short_description', 'long_description', 'clone']);
		$data = App::ensure($data, ['id', 'sku', 'model', 'short_description', 'long_description', 'clone'], '');

		// Check if ID is set
		$id = $data['id'];
		$id = $sql->escape($id);
		if(!$id) return $this->access_denied();

		// Data validation
		if(!$data['clone']) return $this->error('Clone flags not set.');

		// Check permissions
		$record = App::select('product', $id);
		if(!$record) return $this->error('Product not found.');
		if(!Permission::get($record['owner_level'], $record['owner_id'])->check(Permission::STOCK_ENABLED)) return $this->access_denied();

		// Create product record
		unset($record['id']);
		$record['sku'] = $data['sku'];
		$record['model'] = $data['model'];
		$record['short_description'] = $data['short_description'];
		$record['long_description'] = $data['long_description'];
		$record['discontinued'] = 0;

		// Create new product record
		$new_id = App::insert('product', $record);
		if(!$new_id) return $this->error('Error saving data.');

		// Copy prices
		$sql->insert(
			"INSERT INTO product_price (product_id, seller_level, seller_id, unit_cost, pricing_structure_id, distribution_price, reseller_price, trade_price, retail_price)
			SELECT '$new_id' AS product_id, seller_level, seller_id, unit_cost, pricing_structure_id, distribution_price, reseller_price, trade_price, retail_price FROM product_price WHERE product_id = '$id';
		");

		// Copy optional items

		if($data['clone']['accessories']) {
			$sql->insert(
				"INSERT INTO product_accessories (product_id, accessory_id, system_id, default_quantity)
				SELECT '$new_id' AS product_id, accessory_id, system_id, default_quantity FROM product_accessories WHERE product_id = '$id';
			");
		}

		if($data['clone']['alternatives']) {
			$sql->insert(
				"INSERT INTO product_alternatives (product_id, alternative_product_id)
				SELECT '$new_id' AS product_id, alternative_product_id FROM product_alternatives WHERE product_id = '$id';
			");
			$sql->insert(
				"INSERT INTO product_alternatives (alternative_product_id, product_id)
				SELECT '$new_id' AS alternative_product_id, product_id FROM product_alternatives WHERE alternative_product_id = '$id';
			");
		}

		if($data['clone']['bom']) {
			$sql->insert(
				"INSERT INTO product_bom (parent_id, product_id, quantity, unit_id, is_separable)
				SELECT '$new_id' AS parent_id, product_id, quantity, unit_id, is_separable FROM product_bom WHERE parent_id = '$id';
			");
		}

		if($data['clone']['placeholders']) {
			$sql->insert(
				"INSERT INTO product_placeholders (placeholder_id, product_id)
				SELECT '$new_id' AS placeholder_id, product_id FROM product_placeholders WHERE placeholder_id = '$id';
			");
		}

		if($data['clone']['labour']) {
			$sql->insert(
				"INSERT INTO product_labour (product_id, seller_level, seller_id, labour_type_id, labour_hours)
				SELECT '$new_id' AS product_id, seller_level, seller_id, labour_type_id, labour_hours FROM product_labour WHERE product_id = '$id';
			");
		}

		if($data['clone']['subscription']) {
			$sql->insert(
				"INSERT INTO product_subscription (product_id, seller_level, seller_id, subscription_type_id, quantity, selection)
				SELECT '$new_id' AS product_id, seller_level, seller_id, subscription_type_id, quantity, selection FROM product_subscription WHERE product_id = '$id';
			");
		}

		if($data['clone']['warehouses']) {
			$whlist = App::sql()->query("SELECT id FROM stock_warehouse WHERE owner_level = '$record[owner_level]' AND owner_id = '$record[owner_id]';", MySQL::QUERY_ASSOC) ?: [];
			$whlist = array_map(function ($w) { return $w['id']; }, $whlist);

			if(count($whlist) > 0) {
				$whlist = implode(',', $whlist);

				$sql->insert(
					"INSERT INTO stock_warehouse_product (warehouse_id, product_id, min_qty, max_qty, location_id)
					SELECT warehouse_id, '$new_id' AS product_id, min_qty, max_qty, location_id  FROM stock_warehouse_product WHERE product_id = '$id' AND warehouse_id IN ($whlist);
				");
			}
		}

		if($data['clone']['bundle']) {
			$bundle = Bundle::for_product($id);
			$bundle->id = 0;
			$bundle->record['product_id'] = $new_id;
			$bundle->record['user_id'] = App::user()->id;
			$bundle->save();
		}

		return $this->success($new_id);
	}

}
