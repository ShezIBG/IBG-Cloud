<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function list_system_integrators() {
		return $this->success(Permission::list_system_integrators([ 'with' => Permission::SALES_ENABLED ]) ?: []);
	}

	public function sync_system_integrator() {
		$id = App::get('id', 0, true);

		if(!Permission::get_system_integrator($id)->check(Permission::SALES_ENABLED)) return $this->access_denied();

		$result = [];

		$product_subselect = "SELECT product_id FROM product_price WHERE seller_level = 'SI' AND seller_id = '$id'";

		$owner_filter = "
			LEFT JOIN product_reseller AS pr ON pr.owner_level = t.owner_level AND pr.owner_id = t.owner_id AND pr.reseller_level = 'SI' AND pr.reseller_id = '$id'
			WHERE ((t.owner_level = 'SI' AND t.owner_id = '$id') OR pr.owner_level IS NOT NULL)
		";

		//
		// bundle
		//

		$list = App::sql()->query("SELECT id FROM bundle WHERE product_id IN ($product_subselect);", MySQL::QUERY_ASSOC) ?: [];
		$result['bundle'] = [];
		foreach($list as $item) {
			$bundle = new Bundle($item['id']);
			if($bundle->validate()) $result['bundle'][] = [
				'id' => $bundle->id,
				'json' => json_encode($bundle->get_object())
			];
			unset($bundle);
		}

		//
		// product
		//

		$result['product'] = App::sql()->query(
			"SELECT
				p.id,
				'$id' AS system_integrator_id,
				p.sku AS sku,
				e.name AS manufacturer_name,
				p.model,
				p.short_description,
				p.long_description,
				u.name AS unit_name,
				u.decimal_places AS unit_decimal_places,
				p.is_bundle,
				b.id AS bundle_id,
				p.image_id,
				IF(p.is_bundle = 0, 1, 0) AS hidden,
				sp.system_id,
				IF(p.is_bundle = 1 OR bom.cnt > 0 OR acc.cnt > 0, 1, 0) AS is_single,
				pp.unit_cost,
				pp.distribution_price,
				pp.reseller_price,
				pp.trade_price,
				pp.retail_price
			FROM product AS p
			JOIN product_entity AS e ON e.id = p.manufacturer_id
			JOIN product_unit AS u ON u.id = p.unit_id
			LEFT JOIN project_system_products AS sp ON sp.product_id = p.id
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = 'SI' AND pp.seller_id = '$id'
			LEFT JOIN bundle AS b ON b.product_id = p.id AND b.is_latest = 1
			LEFT JOIN (
				SELECT bom_b.parent_id, COUNT(*) AS cnt FROM product_bom AS bom_b
				JOIN product AS bom_p ON bom_p.id = bom_b.product_id AND bom_p.is_placeholder = 1
				GROUP BY bom_b.parent_id
			) AS bom ON bom.parent_id = p.id
			LEFT JOIN (
				SELECT product_id, COUNT(*) AS cnt FROM product_accessories GROUP BY product_id
			) AS acc ON acc.product_id = p.id;
		") ?: [];

		//
		// product_accessories
		//

		$result['product_accessories'] = App::sql()->query("SELECT product_id, accessory_id, system_id, default_quantity FROM product_accessories WHERE product_id IN ($product_subselect);") ?: [];

		//
		// product_labour
		//

		$result['product_labour'] = App::sql()->query("SELECT id, product_id, labour_type_id, labour_hours FROM product_labour WHERE product_id IN ($product_subselect);") ?: [];

		//
		// product_labour_type
		//

		$result['product_labour_type'] = App::sql()->query(
			"SELECT
				t.id,
				'$id' AS system_integrator_id,
				lc.description AS category_description,
				t.description,
				t.hourly_cost,
				t.hourly_price
			FROM product_labour_type AS t
			JOIN product_labour_category AS lc ON lc.id = t.category_id
			$owner_filter;
		") ?: [];

		//
		// product_subscription
		//

		$result['product_subscription'] = App::sql()->query("SELECT id, product_id, quantity, selection FROM product_subscription WHERE product_id IN ($product_subselect) AND seller_level = 'SI' AND seller_id = '$id';") ?: [];

		//
		// product_subscription_type
		//

		$result['product_subscription_type'] = App::sql()->query(
			"SELECT
				t.id,
				'$id' AS system_integrator_id,
				c.description AS category_description,
				t.description,
				t.frequency,
				p.unit_cost,
				p.distribution_price,
				p.reseller_price,
				p.trade_price,
				p.retail_price
			FROM product_subscription_type AS t
			JOIN product_subscription_category AS c ON c.id = t.category_id
			JOIN product_subscription_price AS p ON p.subscription_type_id = t.id AND p.seller_level = 'SI' AND p.seller_id = '$id'
		") ?: [];

		//
		// project_system
		//

		$result['project_system'] = App::sql()->query(
			"SELECT
				t.id, t.description,
				m.description AS module_description
			FROM project_system AS t
			JOIN project_module AS m ON m.id = t.module_id
			$owner_filter;
		") ?: [];

		//
		// user_content
		//

		$list = App::sql()->query(
			"SELECT id, path AS url
			FROM user_content
			WHERE id IN (
				SELECT image_id FROM product WHERE id IN ($product_subselect)

				UNION

				SELECT image_id FROM bundle_question WHERE bundle_id IN (SELECT id FROM bundle WHERE product_id IN ($product_subselect))
			);
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($list as &$item) {
			$item['url'] = UserContent::url_by_path($item['url']);
		}
		unset($item);

		$result['user_content'] = $list;

		return $this->success($result);
	}

	public function project_to_device() {
		$user = App::user();

		$id = App::get('id', 0, true);
		if(!$id) return $this->access_denied();

		$record = App::select('project', $id);
		if(!$record) return $this->access_denied();

		$si_id = $record['system_integrator_id'];
		$perm = Permission::get_system_integrator($si_id);
		if(!$perm->check(Permission::SALES_ENABLED)) return $this->access_denied();
		if($record['is_public'] !== 1 && $user->id != $record['user_id'] && !$perm->check(Permission::SALES_ALL_RECORDS)) return $this->access_denied();

		$project = App::sql()->query_row(
			"SELECT
				p.id,
				p.system_integrator_id,
				p.created,
				p.project_no,
				p.price_tier,
				p.subscription_price_tier,
				p.exclude_labour,
				p.exclude_subscriptions,
				p.vat_rate,
				p.description,
				c.name AS customer_name,
				p.address_line_1,
				p.address_line_2,
				p.address_line_3,
				p.posttown,
				p.postcode,
				p.phone_number,
				p.contact_name,
				p.contact_position,
				p.contact_email,
				p.contact_mobile
			FROM project AS p
			LEFT JOIN sales_customer AS c ON c.id = p.customer_id
			WHERE p.id = '$id';
		", MySQL::QUERY_ASSOC);

		$project_line = App::sql()->query(
			"SELECT
				id,
				parent_id,
				product_id,
				bundle_id,
				structure_id,
				system_id,
				image_id, x, y,
				NULL AS local_image_id,

				unit_cost,
				base_unit_price,
				unit_price,
				quantity,
				is_single,
				is_bundle_item,
				description,
				notes
			FROM project_line
			WHERE project_id = '$id';
		", MySQL::QUERY_ASSOC) ?: [];

		$project_labour = App::sql()->query(
			"SELECT
				id,
				line_id,
				labour_type_id,
				labour_hours,
				hourly_cost,
				hourly_price,
				product_labour_id
			FROM project_labour
			WHERE line_id IN (SELECT id FROM project_line WHERE project_id = '$id');
		", MySQL::QUERY_ASSOC) ?: [];

		$project_subscription = App::sql()->query(
			"SELECT
				id,
				line_id,
				subscription_type_id,
				quantity,
				unit_cost,
				unit_price,
				frequency,
				product_subscription_id
			FROM project_subscription
			WHERE line_id IN (SELECT id FROM project_line WHERE project_id = '$id');
		", MySQL::QUERY_ASSOC) ?: [];

		$project_line_bundle_answers = App::sql()->query(
			"SELECT line_id, question_id, answer
			FROM project_line_bundle_answers
			WHERE line_id IN (SELECT id FROM project_line WHERE project_id = '$id');
		", MySQL::QUERY_ASSOC) ?: [];

		$project_structure = App::sql()->query(
			"SELECT id, type, parent_id, description, image_id, x, y, NULL AS local_image_id
			FROM project_structure
			WHERE project_id = '$id';
		", MySQL::QUERY_ASSOC) ?: [];

		$project_images = App::sql()->query("SELECT structure_id, image_id FROM project_images WHERE project_id = '$id';") ?: [];

		$project_systems = App::sql()->query("SELECT system_id FROM project_system_assign WHERE project_id = '$id';", MySQL::QUERY_ASSOC) ?: [];
		$project_systems = array_map(function($r) { return $r['system_id']; }, $project_systems);

		// Sync project photos
		$user_content = App::sql()->query(
			"SELECT id, path AS url
			FROM user_content
			WHERE id IN (
				SELECT DISTINCT image_id FROM project_images WHERE project_id = '$id'
			);
		", MySQL::QUERY_ASSOC) ?: [];

		foreach($user_content as &$item) {
			$item['url'] = UserContent::url_by_path($item['url']);
		}
		unset($item);

		return $this->success([
			'project' => $project,
			'project_line' => $project_line,
			'project_labour' => $project_labour,
			'project_subscription' => $project_subscription,
			'project_line_bundle_answers' => $project_line_bundle_answers,
			'project_structure' => $project_structure,
			'project_systems' => $project_systems,
			'project_images' => $project_images,
			'user_content' => $user_content
		]);
	}

}
