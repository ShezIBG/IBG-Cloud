<?php

class Project {
	public $id, $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::select('project', $id);
	}

	public function validate() {
		if(!$this->info) return false;

		$user = App::user();
		$si_id = $this->info['system_integrator_id'];
		$perm = Permission::get_system_integrator($si_id);
		if(!$perm->check(Permission::SALES_ENABLED)) return false;
		if($this->info['is_public'] !== 1 && $user->id != $this->info['user_id'] && !$perm->check(Permission::SALES_ALL_RECORDS)) return false;

		return true;
	}

	public function can_show_pricing() {
		if(!$this->info) return false;

		$si_id = $this->info['system_integrator_id'];
		$perm = Permission::get_system_integrator($si_id);
		return $perm->check(Permission::SALES_PRICING);
	}

	public function exclude_labour() {
		return !!$this->info['exclude_labour'];
	}

	public function exclude_subscriptions() {
		return !!$this->info['exclude_subscriptions'];
	}

	public function get_proposal() {
		$result = [
			'project_id' => $this->id,
			'proposal' => [
				'text_introduction' => null,
				'text_solution' => null,
				'text_payment' => null,
				'text_payback' => null,
				'text_terms' => null,
				'text_summary' => null,
				'text_quotation' => null,
				'text_subscriptions' => null,
				'show_quantities' => 1,
				'show_subtotals' => 0,
				'show_acceptance' => 1,
				'preferred_payment' => null
			],
			'modules' => []
		];

		$proposal = App::sql()->query_row("SELECT * FROM project_proposal WHERE project_id = '$this->id';", MySQL::QUERY_ASSOC);
		if($proposal) $result['proposal'] = $proposal;

		$modules = App::sql()->query(
			"SELECT
				m.id, m.description, m.icon, m.colour,
				ppm.text_features
			FROM project_module AS m
			LEFT JOIN project_proposal_module AS ppm ON ppm.project_id = '$this->id' AND ppm.module_id = m.id
			WHERE m.id IN (
				SELECT DISTINCT s.module_id
				FROM project_system_assign AS ps
				JOIN project_system AS s ON s.id = ps.system_id
				WHERE project_id = '$this->id'
			)
			ORDER BY m.display_order;
		", MySQL::QUERY_ASSOC, false);

		if($modules) $result['modules'] = $modules;

		return $result;
	}

	/**
	 * Applies adjustment calculation to a record using the specified type and amount.
	 * Directly updates the passed $record array.
	 * If $type and $amount is ommitted, base_unit_price will be copied to unit_price unchanged.
	 */
	private function adjust_record(&$record, $type = null, $amount = 0) {
		$cost = $record['unit_cost'] ?: 0;
		$price = $record['base_unit_price'] ?: 0;
		$amount = $amount ?: 0;

		switch($type) {
			case 'fixed_price':
				$price = $amount;
				break;

			case 'fixed_markup':
				$price = $cost * (1 + $amount / 100);
				break;

			case 'fixed_margin':
				if ($amount >= 100) {
					$price = 0;
				} else {
					$price = $cost / (1 - ($amount / 100));
				}
				break;

			case 'fixed_profit':
				$price = $cost + $amount;
				break;

			case 'adjustment_percentage':
				if($amount != 0) $price *= 1 + ($amount / 100);
				break;

			case 'adjustment_pounds':
				$price += $amount;
				break;
		}

		$record['unit_price'] = $price;
	}

	/**
	 * Applies price adjustment rules to project lines. Can process a single line if ID is passed, processes all lines otherwise.
	 * Adjustment must be applied after project line is saved in the database.
	 */
	public function apply_price_adjustments($line_id = null) {
		$line_filter = "pl.project_id = '$this->id'";

		if($line_id) {
			// Process single line
			$line_id = App::escape($line_id);
			$line_filter = "pl.id = '$line_id' AND pl.project_id = '$this->id'";
		}

		$list = App::sql()->query(
			"SELECT
				pl.id,
				pl.unit_cost,
				pl.base_unit_price,
				pa.type,
				pa.amount
			FROM project_line AS pl
			LEFT JOIN project_line AS ppl ON ppl.id = pl.parent_id
			LEFT JOIN project_product_price_adjustment AS pa ON pa.project_id = '$this->id' AND pa.system_id = COALESCE(ppl.system_id, pl.system_id) AND pa.product_id = pl.product_id
			WHERE $line_filter;
		", MySQL::QUERY_ASSOC, false) ?: [];

		foreach($list as $record) {
			$this->adjust_record($record, $record['type'], $record['amount']);
			App::update('project_line', $record['id'], [
				'unit_price' => $record['unit_price']
			]);
		}
	}

}
