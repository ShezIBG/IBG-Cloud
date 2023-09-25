<?php

class ProductPricing {

	public $simulation = false;
	public $changed_products = [];
	public $changed_pricing_structures = [];
	public $changed_subscriptions = [];

	private $processing_queue = [];
	private $pricing_structure_cache = [];

	private function clear() {
		$this->changed_products = [];
		$this->changed_pricing_structures = [];
		$this->changed_subscriptions = [];
		$this->processing_queue = [];
		$this->pricing_structure_cache = [];
	}

	public function get_total_simulation_changes() {
		return $this->simulation ? count($this->changed_products) + count($this->changed_pricing_structures) + count($this->changed_subscriptions) : 0;
	}

	private function get_pricing_structure_record($id) {
		if(!$id) return null;

		if($this->simulation) {
			if(isset($this->changed_pricing_structures[$id])) return $this->changed_pricing_structures[$id];
		}

		if(isset($this->pricing_structure_cache[$id])) return $this->pricing_structure_cache[$id];

		$record = App::sql()->query_row("SELECT * FROM product_pricing_structure WHERE id = '$id';", MySQL::QUERY_ASSOC, false);
		if(!$record) return null;

		$this->pricing_structure_cache[$record['id']] = $record;
		return $record;
	}

	private function get_product_record($id) {
		if(!$id) return null;

		if($this->simulation) {
			if(isset($this->changed_products[$id])) return $this->changed_products[$id];
		}

		$record = App::sql()->query_row("SELECT * FROM product WHERE id = '$id';", MySQL::QUERY_ASSOC, false);
		if(!$record) return null;

		$record['owner'] = "$record[owner_level]-$record[owner_id]";
		$record['prices'] = [];
		$prices = App::sql()->query("SELECT * FROM product_price WHERE product_id = '$id';", MySQL::QUERY_ASSOC, false) ?: [];
		foreach($prices as $p) {
			$p['seller'] = "$p[seller_level]-$p[seller_id]";
			$record['prices'][$p['seller']] = $p;
		}

		return $record;
	}

	private function get_subscription_record($id) {
		if(!$id) return null;

		if($this->simulation) {
			if(isset($this->changed_subscriptions[$id])) return $this->changed_subscriptions[$id];
		}

		$record = App::sql()->query_row("SELECT * FROM product_subscription_type WHERE id = '$id';", MySQL::QUERY_ASSOC, false);
		if(!$record) return null;

		$record['owner'] = "$record[owner_level]-$record[owner_id]";
		$record['prices'] = [];
		$prices = App::sql()->query("SELECT * FROM product_subscription_price WHERE subscription_type_id = '$id';", MySQL::QUERY_ASSOC, false) ?: [];
		foreach($prices as $p) {
			$p['seller'] = "$p[seller_level]-$p[seller_id]";
			$record['prices'][$p['seller']] = $p;
		}

		return $record;
	}

	private function queue_product($id) {
		if(!in_array($id, $this->processing_queue)) $this->processing_queue[] = $id;
	}

	/**
	 * IMPORTANT: $record must be in the same format as returned by get_product_record!
	 * It is legal to only have the changed prices in the prices sub-array
	 */
	public function simulate_product_change($record) {
		$this->simulation = true;
		$this->clear();

		$record['id'] = App::escape($record['id']);
		$id = $record['id'];

		// Check if there were any changes compared to the database
		$original = $this->get_product_record($id);

		$changed = false;
		foreach($original['prices'] as $seller => $price) {
			if(isset($record['prices'][$seller])) {
				if($original['prices'][$seller]['unit_cost'] != $record['prices'][$seller]['unit_cost']) $changed = true;
				if($original['prices'][$seller]['distribution_price'] != $record['prices'][$seller]['distribution_price']) $changed = true;
				if($original['prices'][$seller]['reseller_price'] != $record['prices'][$seller]['reseller_price']) $changed = true;
				if($original['prices'][$seller]['trade_price'] != $record['prices'][$seller]['trade_price']) $changed = true;
				if($original['prices'][$seller]['retail_price'] != $record['prices'][$seller]['retail_price']) $changed = true;
			} else {
				// Copy the original price record to make sure we have a full record in the cache
				$record['prices'][$seller] = $price;
			}
		}

		if($changed) {
			$this->changed_products[$id] = $record;
			$this->queue_product($id);
			$this->run();
		}
	}

	public function simulate_pricing_structure_change($record) {
		$this->simulation = true;
		$this->clear();

		$record['id'] = App::escape($record['id']);
		$id = $record['id'];

		// Check if there were any changes compared to the database
		$original = $this->get_pricing_structure_record($id);

		$changed = false;
		foreach(['distribution', 'reseller', 'trade', 'retail'] as $tier) {
			if($original["${tier}_method"] != $record["${tier}_method"]) $changed = true;
			if($original["${tier}_value"] != $record["${tier}_value"]) $changed = true;
			if($original["${tier}_round"] != $record["${tier}_round"]) $changed = true;
			if($original["${tier}_round_to_nearest"] != $record["${tier}_round_to_nearest"]) $changed = true;
			if($original["${tier}_minimum_price"] != $record["${tier}_minimum_price"]) $changed = true;
		}

		if($changed) {
			$this->changed_pricing_structures[$id] = $record;

			// Recalculate prices of all products that use this pricing structure
			$list = App::sql()->query("SELECT DISTINCT product_id FROM product_price WHERE pricing_structure_id = '$id';") ?: [];
			foreach($list as $item) {
				$this->queue_product($item->product_id);
			}
			$this->run();

			// Recalculate prices of all subscriptions that use this pricing structure
			$list = App::sql()->query("SELECT DISTINCT subscription_type_id FROM product_subscription_price WHERE pricing_structure_id = '$id';") ?: [];
			foreach($list as $item) {
				$this->recalculate_subscription($item->subscription_type_id);
			}
		}
	}

	public function apply_product_change($id) {
		$this->simulation = false;
		$this->clear();

		$id = App::escape($id);

		// For data consistency, always recalculate the current product as well
		$this->queue_product($id);
		$this->trigger_cost_change($id);
		$this->trigger_price_change($id);
		$this->run();
	}

	public function apply_subscription_change($id) {
		$this->simulation = false;
		$this->clear();

		$id = App::escape($id);

		$this->recalculate_subscription($id);
	}

	public function apply_pricing_structure_change($id) {
		$this->simulation = false;
		$this->clear();

		$id = App::escape($id);

		// Recalculate prices of all products that use this pricing structure
		$list = App::sql()->query("SELECT DISTINCT product_id FROM product_price WHERE pricing_structure_id = '$id';") ?: [];
		foreach($list as $item) {
			$this->queue_product($item->product_id);
		}

		$this->run();

		// Recalculate prices of all subscriptions that use this pricing structure
		$list = App::sql()->query("SELECT DISTINCT subscription_type_id FROM product_subscription_price WHERE pricing_structure_id = '$id';") ?: [];
		foreach($list as $item) {
			$this->recalculate_subscription($item->subscription_type_id);
		}
	}

	public function apply_reseller_change($owner_level, $owner_id) {
		$this->simulation = false;
		$this->clear();

		// TODO: What if resellers are removed

		// Recalculate prices of all products that are re-sold by owner
		$list = App::sql()->query("SELECT id FROM product WHERE owner_level = '$owner_level' AND owner_id = '$owner_id' AND sold_to_reseller = 1;") ?: [];
		foreach($list as $item) {
			$this->queue_product($item->id);
		}
		$this->run();

		// Recalculate prices of all subscriptions specified by owner
		$list = App::sql()->query("SELECT id FROM product_subscription_type WHERE owner_level = '$owner_level' AND owner_id = '$owner_id';") ?: [];
		foreach($list as $item) {
			$this->recalculate_subscription($item->id);
		}
	}

	private function run() {
		// Work through the products in the processing queue until empty
		while(count($this->processing_queue) > 0) {
			$product_id = $this->processing_queue[0];
			$this->recalculate_product($product_id);
			array_shift($this->processing_queue);
		}
	}

	private function trigger_cost_change($id) {
		// Recalculate costs of all products with this in the BOM/placeholder list
		$list = App::sql()->query("SELECT DISTINCT parent_id FROM product_bom WHERE product_id = '$id';") ?: [];
		foreach($list as $bom) {
			$this->queue_product($bom->parent_id);
		}

		$list = App::sql()->query("SELECT DISTINCT placeholder_id FROM product_placeholders WHERE product_id = '$id';") ?: [];
		foreach($list as $placeholder) {
			$this->queue_product($placeholder->placeholder_id);
		}
	}

	private function trigger_price_change($id) {
		// Recalculate prices of all products with this in the BOM list but only if it uses a pricing structure that sums the BOM
		// We only need to look at owner price records, as others don't have access to the BOM
		$list = App::sql()->query(
			"SELECT DISTINCT bom.parent_id
			FROM product_bom AS bom
			JOIN product AS p ON p.id = bom.parent_id
			JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = p.owner_level AND pp.seller_id = p.owner_id
			JOIN product_pricing_structure AS ps ON ps.id = pp.pricing_structure_id AND (
				ps.distribution_method = 'recommended'
				OR ps.reseller_method = 'recommended'
				OR ps.trade_method = 'recommended'
				OR ps.retail_method = 'recommended'
			)
			WHERE bom.product_id = '$id';
		") ?: [];

		foreach($list as $bom) {
			$this->queue_product($bom->parent_id);
		}
	}

	private function calculate_bom_pricing($product_id) {
		$bp = [
			'cost' => 0,
			'distribution_price' => 0,
			'reseller_price' => 0,
			'trade_price' => 0,
			'retail_price' => 0
		];

		$product = $this->get_product_record($product_id);

		if($this->simulation) {
			// Slow method, taking into account changed items
			$result = App::sql()->query(
				"SELECT
					bom.product_id,
					COALESCE(bom.quantity, 0) AS quantity,
					COALESCE(pu.base_amount, 1) AS base_amount
				FROM product_bom AS bom
				JOIN product_unit AS pu ON pu.id = bom.unit_id
				WHERE bom.parent_id = '$product_id';
			") ?: [];

			foreach($result as $row) {
				$record = $this->get_product_record($row->product_id);
				if($record && isset($record['prices'][$product['owner']])) {
					$price = $record['prices'][$product['owner']];
					$bp['cost'] += ($price['unit_cost'] ?: 0) * $row->quantity * $row->base_amount;
					$bp['distribution_price'] += ($price['distribution_price'] ?: 0) * $row->quantity * $row->base_amount;
					$bp['reseller_price'] += ($price['reseller_price'] ?: 0) * $row->quantity * $row->base_amount;
					$bp['trade_price'] += ($price['trade_price'] ?: 0) * $row->quantity * $row->base_amount;
					$bp['retail_price'] += ($price['retail_price'] ?: 0) * $row->quantity * $row->base_amount;
				}
			}

		} else {
			// Use fast calculation from database
			$result = App::sql()->query_row(
				"SELECT
					SUM(COALESCE(pp.unit_cost, 0) * COALESCE(bom.quantity, 0) * COALESCE(pu.base_amount, 1)) AS cost,
					SUM(COALESCE(pp.distribution_price, 0) * COALESCE(bom.quantity, 0) * COALESCE(pu.base_amount, 1)) AS distribution_price,
					SUM(COALESCE(pp.reseller_price, 0) * COALESCE(bom.quantity, 0) * COALESCE(pu.base_amount, 1)) AS reseller_price,
					SUM(COALESCE(pp.trade_price, 0) * COALESCE(bom.quantity, 0) * COALESCE(pu.base_amount, 1)) AS trade_price,
					SUM(COALESCE(pp.retail_price, 0) * COALESCE(bom.quantity, 0) * COALESCE(pu.base_amount, 1)) AS retail_price
				FROM product_bom AS bom
				JOIN product AS p ON p.id = bom.product_id
				JOIN product_price AS pp ON pp.product_id = p.id AND pp.seller_level = '$product[owner_level]' AND pp.seller_id = '$product[owner_id]'
				JOIN product_unit AS pu ON pu.id = bom.unit_id
				WHERE bom.parent_id = '$product_id';
			", MySQL::QUERY_ASSOC, false);
			if($result) $bp = $result;
		}

		return $bp;
	}

	private function calculate_placeholder_cost($product_id, $seller_level, $seller_id) {
		if($this->simulation) {
			// Slow method, taking into account changed items
			$result = App::sql()->query(
				"SELECT
					p.id
				FROM product_placeholders AS pp
				JOIN product AS p ON p.id = pp.product_id
				WHERE pp.placeholder_id = '$product_id';
			");
			if(!$result) return 0;

			$cost = 0;
			$seller = "{$seller_level}-{$seller_id}";
			foreach($result as $row) {
				$record = $this->get_product_record($row->id);
				if($record && isset($record['prices'][$seller])) {
					$item_cost = $record['prices'][$seller]['unit_cost'] ?: 0;
					if($cost < $item_cost) $cost = $item_cost;
				}
			}

			return $cost;

		} else {
			// Use fast calculation from database
			$result = App::sql()->query_row(
				"SELECT
					MAX(ppr.unit_cost) AS cost
				FROM product_placeholders AS pp
				JOIN product AS p ON p.id = pp.product_id
				JOIN product_price AS ppr ON ppr.product_id = p.id AND ppr.seller_level = '$seller_level' AND ppr.seller_id = '$seller_id'
				WHERE pp.placeholder_id = '$product_id';
			");

			return $result ? ($result->cost ?: 0) : 0;
		}
	}

	/**
	 * Recalculate prices in $record based on the passed pricing $structure and the pre-calculated 'unit_cost'.
	 * It can be used to recalculate either product or subscription prices.
	 * It just does the price calculation, doesn't check any other record fields.
	 * $rp holds the recommended prices, which need to be calculated before calling this function.
	 * $rp can be either the BOM price totals or calculated from owner pricing, depending on what's needed.
	 *
	 * $record will be updated in place, no value is returned from the function.
	 * Apart from the price fields, no other variables will be changed.
	 */
	public static function apply_pricing_structure(&$record, $structure, $rp = null) {
		if($rp === null) {
			$rp = [
				'cost' => 0,
				'distribution_price' => 0,
				'reseller_price' => 0,
				'trade_price' => 0,
				'retail_price' => 0
			];
		}

		if($structure) {
			$cost = $record['unit_cost'];

			foreach(['distribution', 'reseller', 'trade', 'retail'] as $tier) {
				$method = $structure["${tier}_method"];
				$value = $structure["${tier}_value"] ?: 0;
				$round = $structure["${tier}_round"];
				$nearest = $structure["${tier}_round_to_nearest"] ?: 0;
				$minimum = $structure["${tier}_minimum_price"] ?: 0;

				$price = $record["${tier}_price"];

				switch ($method) {
					case 'custom':
						// Leave it as-is
						continue;

					case 'recommended':
						$price = $rp["${tier}_price"];
						break;

					case 'markup':
						$price = $cost * (1 + $value / 100);
						break;

					case 'margin':
						if ($value >= 100) {
							$price = 0;
						} else {
							$price = $cost / (1 - ($value / 100));
						}
						break;

					case 'profit':
						$price = $cost + $value;
						break;
				}

				if ($round) {
					if ($nearest > 0) {
						$price /= $nearest;
						switch ($round) {
							case 'round': $price = round($price); break;
							case 'floor': $price = floor($price); break;
							case 'ceiling': $price = ceil($price); break;
						}
						$price *= $nearest;
					}
				}

				if ($method !== 'custom' && $method !== 'recommended') $price = max($price, $minimum);

				$record["${tier}_price"] = round($price, 4);
			}
		}
	}

	private function recalculate_product($id) {
		$original = $this->get_product_record($id);
		if(!$original) return;

		$record = $original;
		$cost_changed = false;
		$price_changed = false;

		$calculate_price = function($seller_level, $seller_id) use ($id, $original, &$record, &$cost_changed, &$price_changed) {
			$seller = "{$seller_level}-{$seller_id}";
			$price_record = &$record['prices'][$seller];
			$original_price_record = $original['prices'][$seller];
			$is_owner = $record['owner'] === $seller;

			$rp = [
				'distribution_price' => 0,
				'reseller_price' => 0,
				'trade_price' => 0,
				'retail_price' => 0
			];

			// Calculate cost and recommended pricing based on sharing settings and the owner prices
			if(!$is_owner) {
				$tier = App::sql()->query_row("SELECT price_tier FROM product_reseller WHERE owner_level = '$record[owner_level]' AND owner_id = '$record[owner_id]' AND reseller_level = '$seller_level' AND reseller_id = '$seller_id';", MySQL::QUERY_ASSOC, false);
				$tier = $tier ? $tier['price_tier'] : 'retail';

				if(isset($record['prices'][$record['owner']])) {
					$price = $record['prices'][$record['owner']];

					$rp['cost'] = $tier === 'cost' ? $price['unit_cost'] : $price["{$tier}_price"];
					$rp['distribution_price'] = in_array($tier, ['cost', 'distribution']) ? $price['distribution_price'] : $rp['cost'];
					$rp['reseller_price'] = in_array($tier, ['cost', 'distribution', 'reseller']) ? $price['reseller_price'] : $rp['cost'];
					$rp['trade_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade']) ? $price['trade_price'] : $rp['cost'];
					$rp['retail_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade', 'retail']) ? $price['retail_price'] : $rp['cost'];
				}

				// For non-owners, cost is always automatic based on sharing settings
				$cost = $rp['cost'];

			} else if($record['has_bom']) {
				$rp = $this->calculate_bom_pricing($id);
				$cost = $rp['cost'];
			} else if($record['is_placeholder']) {
				$cost = $this->calculate_placeholder_cost($id, $seller_level, $seller_id);
			} else {
				$cost = $price_record['unit_cost'];
			}

			$price_record['unit_cost'] = round($cost, 4);

			// Calculate prices
			$structure = $this->get_pricing_structure_record($price_record['pricing_structure_id']);
			ProductPricing::apply_pricing_structure($price_record, $structure, $rp);

			// Detect changes if any
			if($price_record['unit_cost'] != $original_price_record['unit_cost']) $cost_changed = true;

			if($price_record['distribution_price'] != $original_price_record['distribution_price']) $price_changed = true;
			if($price_record['reseller_price'] != $original_price_record['reseller_price']) $price_changed = true;
			if($price_record['trade_price'] != $original_price_record['trade_price']) $price_changed = true;
			if($price_record['retail_price'] != $original_price_record['retail_price']) $price_changed = true;
		};

		// Recalculate owner prices first
		$calculate_price($record['owner_level'], $record['owner_id']);

		// Recalculate all other levels
		foreach($record['prices'] as $seller => $p) {
			if($record['owner'] !== $seller) {
				$calculate_price($p['seller_level'], $p['seller_id']);
			}
		}

		// Save all price levels if changed
		if($cost_changed || $price_changed) {
			if($this->simulation) {
				$this->changed_products[$id] = $record;
			} else {
				foreach($record['prices'] as $seller => $p) {
					$unit_cost = $p['unit_cost'] ?: 0;
					$distribution_price = $p['distribution_price'] ?: 0;
					$reseller_price = $p['reseller_price'] ?: 0;
					$trade_price = $p['trade_price'] ?: 0;
					$retail_price = $p['retail_price'] ?: 0;

					$seller_level = $p['seller_level'];
					$seller_id = $p['seller_id'];

					if($seller_level && $seller_id) {
						App::sql()->update(
							"UPDATE product_price SET
								unit_cost = '$unit_cost',
								distribution_price = '$distribution_price',
								reseller_price = '$reseller_price',
								trade_price = '$trade_price',
								retail_price = '$retail_price'
							WHERE product_id = '$id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';
						");
					}
				}
			}
		}

		// Trigger change events
		if($cost_changed) $this->trigger_cost_change($id);
		if($price_changed) $this->trigger_price_change($id);
	}

	private function recalculate_subscription($id) {
		$original = $this->get_subscription_record($id);
		if(!$original) return;

		$record = $original;
		$cost_changed = false;
		$price_changed = false;

		$calculate_price = function($seller_level, $seller_id) use ($id, $original, &$record, &$cost_changed, &$price_changed) {
			$seller = "{$seller_level}-{$seller_id}";
			$price_record = &$record['prices'][$seller];
			$original_price_record = $original['prices'][$seller];
			$is_owner = $record['owner'] === $seller;

			$rp = [
				'distribution_price' => 0,
				'reseller_price' => 0,
				'trade_price' => 0,
				'retail_price' => 0
			];

			// Calculate cost and recommended pricing based on sharing settings and the owner prices
			if(!$is_owner) {
				$tier = App::sql()->query_row("SELECT price_tier FROM product_reseller WHERE owner_level = '$record[owner_level]' AND owner_id = '$record[owner_id]' AND reseller_level = '$seller_level' AND reseller_id = '$seller_id';", MySQL::QUERY_ASSOC, false);
				$tier = $tier ? $tier['price_tier'] : 'retail';

				if(isset($record['prices'][$record['owner']])) {
					$price = $record['prices'][$record['owner']];

					$rp['cost'] = $tier === 'cost' ? $price['unit_cost'] : $price["{$tier}_price"];
					$rp['distribution_price'] = in_array($tier, ['cost', 'distribution']) ? $price['distribution_price'] : $rp['cost'];
					$rp['reseller_price'] = in_array($tier, ['cost', 'distribution', 'reseller']) ? $price['reseller_price'] : $rp['cost'];
					$rp['trade_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade']) ? $price['trade_price'] : $rp['cost'];
					$rp['retail_price'] = in_array($tier, ['cost', 'distribution', 'reseller', 'trade', 'retail']) ? $price['retail_price'] : $rp['cost'];
				}

				// For non-owners, cost is always automatic based on sharing settings
				$cost = $rp['cost'];

			} else {
				$cost = $price_record['unit_cost'];
			}

			$price_record['unit_cost'] = round($cost, 4);

			// Calculate prices
			$structure = $this->get_pricing_structure_record($price_record['pricing_structure_id']);
			ProductPricing::apply_pricing_structure($price_record, $structure, $rp);

			// Detect changes if any
			if($price_record['unit_cost'] != $original_price_record['unit_cost']) $cost_changed = true;

			if($price_record['distribution_price'] != $original_price_record['distribution_price']) $price_changed = true;
			if($price_record['reseller_price'] != $original_price_record['reseller_price']) $price_changed = true;
			if($price_record['trade_price'] != $original_price_record['trade_price']) $price_changed = true;
			if($price_record['retail_price'] != $original_price_record['retail_price']) $price_changed = true;
		};

		// Recalculate owner prices first
		$calculate_price($record['owner_level'], $record['owner_id']);

		// Recalculate all other levels
		foreach($record['prices'] as $seller => $p) {
			if($record['owner'] !== $seller) {
				$calculate_price($p['seller_level'], $p['seller_id']);
			}
		}

		// Save all price levels if changed
		if($cost_changed || $price_changed) {
			if($this->simulation) {
				$this->changed_subscriptions[$id] = $record;
			} else {
				foreach($record['prices'] as $seller => $p) {
					$unit_cost = $p['unit_cost'] ?: 0;
					$distribution_price = $p['distribution_price'] ?: 0;
					$reseller_price = $p['reseller_price'] ?: 0;
					$trade_price = $p['trade_price'] ?: 0;
					$retail_price = $p['retail_price'] ?: 0;

					$seller_level = $p['seller_level'];
					$seller_id = $p['seller_id'];

					if($seller_level && $seller_id) {
						App::sql()->update(
							"UPDATE product_subscription_price SET
								unit_cost = '$unit_cost',
								distribution_price = '$distribution_price',
								reseller_price = '$reseller_price',
								trade_price = '$trade_price',
								retail_price = '$retail_price'
							WHERE subscription_type_id = '$id' AND seller_level = '$seller_level' AND seller_id = '$seller_id';
						");
					}
				}
			}
		}
	}

}

class Bundle {

	public $id;
	public $record;

	public $products;

	public $questions;
	public $question_products;
	public $question_counters;
	public $question_select_options;

	public $counters;
	public $counter_products;

	/** Returns the latest bundle for a product (or a new empty bundle if not found) */
	public static function for_product($id) {
		$id = App::escape($id);
		$r = App::sql()->query_row("SELECT id FROM bundle WHERE product_id = '$id' AND is_latest = 1 LIMIT 1;");

		if($r) {
			return new Bundle($r->id);
		} else {
			return new Bundle(0, $id);
		}
	}

	public function __construct($id = 0, $product_id = null) {
		$this->id = App::escape($id) ?: 0;

		if($id) {
			$this->record = App::select('bundle', $this->id);

			if($this->record) {
				$this->products = App::sql()->query(
					"SELECT
						bp.*,
						p.model,
						p.short_description,
						m.name AS manufacturer_name,
						uc.path AS image_url
					FROM bundle_products AS bp
					JOIN product AS p ON p.id = bp.product_id
					LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id
					LEFT JOIN user_content AS uc ON uc.id = p.image_id
					WHERE bp.bundle_id = '$this->id'
					ORDER BY p.short_description;
				", MySQL::QUERY_ASSOC) ?: [];

				$this->questions = App::sql()->query(
					"SELECT
						q.*,
						uc.path AS image_url
					FROM bundle_question AS q
					LEFT JOIN user_content AS uc ON uc.id = q.image_id
					WHERE q.bundle_id = '$this->id'
					ORDER BY q.parent_id, q.display_order;
				", MySQL::QUERY_ASSOC) ?: [];

				$this->question_products = App::sql()->query(
					"SELECT
						qp.*,
						p.model,
						p.short_description,
						m.name AS manufacturer_name,
						uc.path AS image_url
					FROM bundle_question_products AS qp
					JOIN product AS p ON p.id = qp.product_id
					LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id
					LEFT JOIN user_content AS uc ON uc.id = p.image_id
					WHERE qp.bundle_id = '$this->id'
					ORDER BY qp.question_id, p.short_description;
				", MySQL::QUERY_ASSOC) ?: [];

				$this->question_counters = App::sql()->query("SELECT * FROM bundle_question_counters WHERE bundle_id = '$this->id';") ?: [];

				$this->question_select_options = App::sql()->query("SELECT * FROM bundle_question_select_options WHERE bundle_id = '$this->id' ORDER BY question_id, display_order;", MySQL::QUERY_ASSOC) ?: [];

				$this->counters = App::sql()->query("SELECT * FROM bundle_counter WHERE bundle_id = '$this->id' ORDER BY description;", MySQL::QUERY_ASSOC) ?: [];

				$this->counter_products = App::sql()->query(
					"SELECT
						cp.*,
						p.model,
						p.short_description,
						m.name AS manufacturer_name,
						uc.path AS image_url
					FROM bundle_counter_products AS cp
					JOIN product AS p ON p.id = cp.product_id
					LEFT JOIN product_entity AS m ON m.id = p.manufacturer_id
					LEFT JOIN user_content AS uc ON uc.id = p.image_id
					WHERE cp.bundle_id = '$this->id'
					ORDER BY cp.counter_id, p.short_description;
				", MySQL::QUERY_ASSOC) ?: [];

				// Resolve product images

				foreach($this->products as &$item) if($item['image_url']) $item['image_url'] = UserContent::url_by_path($item['image_url']); unset($item);
				foreach($this->question_products as &$item) if($item['image_url']) $item['image_url'] = UserContent::url_by_path($item['image_url']); unset($item);
				foreach($this->counter_products as &$item) if($item['image_url']) $item['image_url'] = UserContent::url_by_path($item['image_url']); unset($item);

				// Resolve question images

				foreach($this->questions as &$item) if($item['image_url']) $item['image_url'] = UserContent::url_by_path($item['image_url']); unset($item);
			}
		} else {
			$user = App::user();

			$this->record = [
				'product_id' => $product_id,
				'version' => 1,
				'user_id' => $user->id,
				'is_latest' => 1,
				'last_question_id' => 0,
				'last_counter_id' => 0
			];

			$this->products = [];
			$this->questions = [];
			$this->question_products = [];
			$this->question_counters = [];
			$this->question_select_options = [];
			$this->counters = [];
			$this->counter_products = [];
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_object() {
		return [
			'record' => $this->record,
			'products' => $this->products,
			'questions' => $this->questions,
			'question_products' => $this->question_products,
			'question_counters' => $this->question_counters,
			'question_select_options' => $this->question_select_options,
			'counters' => $this->counters,
			'counter_products' => $this->counter_products
		];
	}

	public function load_object($o) {
		$rec = $o['record'];
		$rec = App::ensure($rec, ['last_question_id', 'last_counter_id'], 0);

		$this->record['last_question_id'] = $rec['last_question_id'];
		$this->record['last_counter_id'] = $rec['last_counter_id'];

		$this->products = $o['products'];
		$this->questions = $o['questions'];
		$this->question_products = $o['question_products'];
		$this->question_counters = $o['question_counters'];
		$this->question_select_options = $o['question_select_options'];
		$this->counters = $o['counters'];
		$this->counter_products = $o['counter_products'];
	}

	/** Writes all changes to the database, overwriting the current revision. */
	public function save() {
		if(!$this->validate()) return false;
		if(!$this->id) return !!$this->save_new_version();

		if(!$this->record['product_id']) return false;

		$rec = $this->record;
		$rec = App::keep($rec, ['product_id', 'last_question_id', 'last_counter_id']);
		$rec = App::ensure($rec, ['last_question_id', 'last_counter_id'], 0);
		App::update('bundle', $this->id, $rec);

		$this->write_records_to_id($this->id);

		return true;
	}

	/** Saves bundle as a new version and returns new bundle ID. */
	public function save_new_version() {
		if(!$this->validate()) return null;

		if(!$this->record['product_id']) return null;

		$user = App::user();

		$rec = $this->record;
		$rec = App::keep($rec, ['product_id', 'last_question_id', 'last_counter_id']);
		$rec = App::ensure($rec, ['last_question_id', 'last_counter_id'], 0);

		$max_version = 0;
		$r = App::sql()->query_row("SELECT MAX(version) AS version FROM bundle WHERE product_id = '$rec[product_id]';");
		$max_version = $r ? ($r->version ?: 0) : 0;

		$rec['user_id'] = $user->id;
		$rec['version'] = $max_version + 1;

		$id = App::insert('bundle', $rec);
		if(!$id) return null;

		App::sql()->update("UPDATE bundle SET is_latest = IF(id = '$id', 1, 0) WHERE product_id = '$rec[product_id]';");

		$this->write_records_to_id($id);

		return $id;
	}

	private function write_records_to_id($id) {
		//
		// Bundle products
		//

		App::sql()->delete("DELETE FROM bundle_products WHERE bundle_id = '$id';");
		foreach($this->products as $rec) {
			$rec = App::keep($rec, ['product_id', 'quantity']);
			$rec = App::ensure($rec, ['product_id', 'quantity'], 0);
			if($rec['product_id'] && $rec['quantity'] > 0) {
				$rec['bundle_id'] = $id;
				App::insert('bundle_products', $rec);
			}
		}

		//
		// Bundle counters
		//

		$counter_list = [];

		App::sql()->delete("DELETE FROM bundle_counter WHERE bundle_id = '$id';");
		foreach($this->counters as $rec) {
			$rec = App::keep($rec, ['counter_id', 'description']);
			$rec = App::ensure($rec, ['counter_id'], 0);
			$rec = App::ensure($rec, ['description'], '');
			if($rec['counter_id']) {
				$counter_list[] = $rec['counter_id'];
				$rec['bundle_id'] = $id;
				App::insert('bundle_counter', $rec);
			}
		}

		App::sql()->delete("DELETE FROM bundle_counter_products WHERE bundle_id = '$id';");
		foreach($this->counter_products as $rec) {
			$rec = App::keep($rec, ['counter_id', 'product_id', 'quantity', 'multiply_by_counter', 'range_start', 'range_end']);
			$rec = App::ensure($rec, ['counter_id', 'product_id', 'quantity', 'multiply_by_counter', 'range_start', 'range_end'], 0);
			if($rec['counter_id'] && in_array($rec['counter_id'], $counter_list)) {
				$rec['bundle_id'] = $id;
				App::insert('bundle_counter_products', $rec);
			}
		}

		//
		// Bundle questions
		//

		// Sanitise question records
		$questions = [];
		foreach($this->questions as $rec) {
			$rec = App::keep($rec, ['question_id', 'parent_id', 'question', 'type', 'image_id', 'is_required', 'default_value', 'min_value', 'max_value', 'parent_mode', 'parent_value', 'parent_max_value', 'display_order']);
			$rec = App::ensure($rec, ['question_id', 'default_value', 'display_order'], 0);
			$rec = App::ensure($rec, ['parent_id', 'question', 'image_id', 'min_value', 'max_value', 'parent_value', 'parent_max_value'], null);
			$rec = App::ensure($rec, ['type'], 'numeric');
			$rec = App::ensure($rec, ['parent_mode'], 'set');
			if($rec['question_id']) $questions[] = $rec;
		}

		// Build list of questions to make sure we have a fully connected tree (removed dead islands)
		$question_list = [];
		foreach($questions as $rec) if($rec['parent_id'] === null) $question_list[] = $rec['question_id'];

		$left = array_filter($questions, function($item) { return $item['parent_id'] !== null; });
		do {
			$stop = true;
			$left = array_filter($left, function($item) use (&$question_list, &$stop) {
				if(in_array($item['parent_id'], $question_list)) {
					$question_list[] = $item['question_id'];
					$stop = false;
					return false;
				} else {
					return true;
				}
			});
		} while(!$stop);

		$questions = array_filter($questions, function($rec) use (&$question_list) { return in_array($rec['question_id'], $question_list); });

		// Add question records
		App::sql()->delete("DELETE FROM bundle_question WHERE bundle_id = '$id';");
		foreach($questions as $rec) {
			$rec['bundle_id'] = $id;
			App::insert('bundle_question', $rec);
		}

		App::sql()->delete("DELETE FROM bundle_question_select_options WHERE bundle_id = '$id';");
		foreach($this->question_select_options as $rec) {
			$rec = App::keep($rec, ['question_id', 'value', 'description', 'display_order']);
			$rec = App::ensure($rec, ['question_id', 'value', 'display_order'], 0);
			$rec = App::ensure($rec, ['description'], '');
			if($rec['value'] && in_array($rec['question_id'], $question_list)) {
				$rec['bundle_id'] = $id;
				App::insert('bundle_question_select_options', $rec);
			}
		}

		App::sql()->delete("DELETE FROM bundle_question_products WHERE bundle_id = '$id';");
		foreach($this->question_products as $rec) {
			$rec = App::keep($rec, ['question_id', 'product_id', 'quantity', 'multiply_by_question_id', 'question_mode', 'question_value', 'question_max_value']);
			$rec = App::ensure($rec, ['question_id', 'product_id', 'quantity'], 0);
			$rec = App::ensure($rec, ['multiply_by_question_id', 'question_value', 'question_max_value'], null);
			$rec = App::ensure($rec, ['question_mode'], 'set');
			if($rec['product_id'] && in_array($rec['question_id'], $question_list)) {
				$rec['bundle_id'] = $id;
				App::insert('bundle_question_products', $rec);
			}
		}

		App::sql()->delete("DELETE FROM bundle_question_counters WHERE bundle_id = '$id';");
		foreach($this->question_counters as $rec) {
			$rec = App::keep($rec, ['question_id', 'counter_id', 'value', 'multiply_by_question_id']);
			$rec = App::ensure($rec, ['question_id', 'counter_id', 'value'], 0);
			$rec = App::ensure($rec, ['multiply_by_question_id'], null);
			if($rec['value'] != 0 && in_array($rec['question_id'], $question_list) && in_array($rec['counter_id'], $counter_list)) {
				$rec['bundle_id'] = $id;
				App::insert('bundle_question_counters', $rec);
			}
		}
	}

}
