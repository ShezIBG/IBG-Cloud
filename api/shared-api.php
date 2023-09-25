<?php

class AccessDeniedException extends Exception {};

/**
 * The base class for all api-module.php files. Mostly validation and helper methods to send JSON responses back.
 */
class SharedAPI {
	public $validation_error = null;
	public $building = null;

	protected $product_owners = null;
	protected $selected_product_owner = null;
	protected $selected_product_owner_level = null;
	protected $selected_product_owner_id = null;

	/**
	 * Selected product owner always comes from GET product_owner field (if not already set). This method fills in
	 * the protected variables and sets/nulls out the selected product owner depending on access. If $update flag is true,
	 * it will automatically select the first owner if current selection is invalid.
	 */
	protected function resolve_product_owners($update = true) {
		$this->product_owners = [];

		$list = Permission::list_system_integrators([ 'with_any' => true, 'with' => [Permission::SALES_ENABLED, Permission::STOCK_ENABLED] ]) ?: [];
		foreach($list as $si) {
			$this->product_owners[] = [
				'id' => "SI-$si->id",
				'description' => "$si->company_name",
				'owner_level' => 'SI',
				'owner_id' => $si->id
			];
		}

		// No access to any owners, null out all and return false
		if(count($this->product_owners) === 0) {
			$this->product_owners = null;
			$this->selected_product_owner = null;
			$this->selected_product_owner_level = null;
			$this->selected_product_owner_id = null;
			return false;
		}

		// Try to get selection from standard product_owner field if not set
		if(!$this->selected_product_owner) $this->selected_product_owner = App::get('product_owner', null);

		// See if the current selection is in the list
		if($this->selected_product_owner) {
			$ok = false;
			foreach($this->product_owners as $owner) {
				if($this->selected_product_owner === $owner['id']) $ok = true;
			}
			if(!$ok) $this->selected_product_owner = null;
		}

		// If selection is not set, select first valid owner if update is enabled
		if(!$this->selected_product_owner && $update) {
			$this->selected_product_owner = $this->product_owners[0]['id'];
		}

		if(!$this->selected_product_owner) {
			// No selection, which means we failed to validate
			$this->selected_product_owner_level = null;
			$this->selected_product_owner_id = null;
			return false;
		} else {
			// There IS a selection, split level and ID
			$chunks = explode('-', $this->selected_product_owner);
			$this->selected_product_owner_level = $chunks[0];
			$this->selected_product_owner_id = (int)$chunks[1];
			return true;
		}
	}

	protected function validate($objects) {
		if(!is_array($objects)) $objects = [$objects];

		foreach($objects as $item) {
			$obj = explode(':', $item);

			switch($obj[0]) {
				case 'module':
					$module = new Module($obj[1]);
					if(!$module->validate()) {
						$this->validation_error = $this->error('Module not found.');
						return false;
					}
					break;

				case 'building':
					if(empty($obj[1])) return $this->error('Building not found.');
					$this->building = new Building($obj[1]);
					if(!$this->building->validate()) {
						$this->validation_error = $this->error('Building not found.');
						return false;
					}
					break;

				default:
					$this->validation_error = $this->error("Unknown validation rule '$obj[0]'.");
					return false;
			}
		}

		return true;
	}

	protected function access_denied() {
		http_response_code(403);
		return App::encode_result('FAIL', 'Access denied.', null);
	}

	protected function error($error, $data = null) {
		http_response_code(400);
		return App::encode_result('FAIL', $error, $data);
	}

	protected function success($result = null, $message = '') {
		return App::encode_result('OK', $message, $result);
	}

	// Do nothing. Used to keep the session alive
	public function ping() {
		return $this->success();
	}

}
