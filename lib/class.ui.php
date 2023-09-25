<?php

class UIElement {

	public $id;
	public $record;

	public static function by_name($name) {
		$r = App::sql()->query_row("SELECT id FROM ui_element WHERE name = '$name' LIMIT 1;");
		if(!$r) return null;
		return new UIElement($r->id);
	}

	public function __construct($id) {
		$this->id = $id;
		$this->record = App::select('ui_element', $id);
	}

	public function get_columns() {
		$user = App::user();

		// Get columns from user's selected preset
		$r = App::sql()->query(
			"SELECT
				DISTINCT c.name
			FROM ui_element_preset AS p
			JOIN ui_element_preset_columns AS pc ON pc.preset_id = p.id
			JOIN ui_element_column AS c ON c.id = pc.column_id
			WHERE p.element_id = '$this->id' AND p.user_id = '$user->id' AND p.is_selected = 1 AND pc.is_visible = 1
			ORDER BY pc.display_order;
		");

		if(!$r) {
			// Get default preset for the element
			$r = App::sql()->query(
				"SELECT
					DISTINCT c.name
				FROM ui_element_preset AS p
				JOIN ui_element_preset_columns AS pc ON pc.preset_id = p.id
				JOIN ui_element_column AS c ON c.id = pc.column_id
				WHERE p.element_id = '$this->id' AND p.user_id IS NULL AND pc.is_visible = 1
				ORDER BY pc.display_order;
			");
		}

		if(!$r) return [];

		return array_map(function($item) {
			return $item->name;
		}, $r);
	}

	public function get_data() {
		$user = App::user();

		$presets = App::sql()->query(
			"SELECT
				id, user_id, description, is_selected
			FROM ui_element_preset
			WHERE element_id = '$this->id' AND (user_id = '$user->id' OR user_id IS NULL)
			ORDER BY NOT ISNULL(user_id), description;
		", MySQL::QUERY_ASSOC) ?: [];

		return [
			'name' => $this->record['name'],
			'description' => $this->record['description'],
			'presets' => array_map(function($preset) {

				// Add column definitions
				$preset['columns'] = App::sql()->query(
					"SELECT
						c.id,
						c.name,
						c.description,
						pc.is_visible
					FROM ui_element_preset_columns AS pc
					JOIN ui_element_column AS c ON c.id = pc.column_id
					WHERE pc.preset_id = '$preset[id]'
					ORDER BY pc.display_order;
				", MySQL::QUERY_ASSOC) ?: [];

				return $preset;

			}, $presets)
		];
	}

}
