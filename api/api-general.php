<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function upload_user_content() {
		list($w, $h) = App::get(['w', 'h'], 0);
		$c = App::get('c', 'image|document');

		if($c) $c = explode('|', $c);
		if($w || $h) $c = ['image'];

		$ret = UserContent::upload('userfile', ['category' => ['image', 'document']]);
		if(!$ret['files']) return $this->error($ret['errors'][0]);

		return $this->success([
			'errors' => $ret['errors'],
			'files'  => array_map(function($user_content) use ($w, $h) {
				if($w || $h) $user_content->shrink_image($w, $h);
				return $user_content->get_info();
			}, $ret['files'])
		]);
	}

	public function upload_user_content_url() {
		list($w, $h) = App::get(['w', 'h'], 0);
		$c = App::get('c', 'image|document');

		if($c) $c = explode('|', $c);
		if($w || $h) $c = ['image'];

		$data = App::json();
		if(!isset($data['url']) || !$data['url']) return $this->error('No URL set.');

		$ret = UserContent::upload_url($data['url'], ['category' => ['image', 'document']]);
		if(!$ret['files']) return $this->error($ret['errors'][0]);

		return $this->success([
			'errors' => $ret['errors'],
			'files'  => array_map(function($user_content) use ($w, $h) {
				if($w || $h) $user_content->shrink_image($w, $h);
				return $user_content->get_info();
			}, $ret['files'])
		]);
	}

	public function upload_smoothpower_update() {
		$ret = UserContent::upload_smoothpower_update('userfile');
		if(!$ret['files']) return $this->error($ret['errors'][0]);

		return $this->success([
			'errors' => $ret['errors'],
			'files' => $ret['files']
		]);
	}

	public function get_ui_element_data() {
		$name = App::get('name', 0, true);
		$element = UIElement::by_name($name);
		if(!$element) return $this->error('Element not found.');

		return $this->success($element->get_data());
	}

	public function delete_ui_element_data() {
		$preset_id = App::get('preset_id', 0, true);
		$user = App::user();

		$record = App::select('ui_element_preset', $preset_id);
		if(!$record) return $this->error('Preset not found.');

		if($record['user_id'] != $user->id) return $this->access_denied();

		App::sql()->delete("DELETE FROM ui_element_preset_columns WHERE preset_id = '$preset_id';");
		App::delete('ui_element_preset', $preset_id);

		return $this->success();
	}

	public function save_ui_element_data() {
		$data = App::json();
		$data = App::keep($data, ['element_name', 'preset_id', 'description', 'columns']);
		$data = App::ensure($data, ['element_name', 'preset_id', 'description'], '');
		$data = App::ensure($data, ['columns'], []);

		$user = App::user();

		$element_name = $data['element_name'];
		$preset_id = $data['preset_id'];
		$description = $data['description'];
		$columns = $data['columns'];

		$element = UIElement::by_name($element_name);
		if(!$element) return $this->error('Element not found.');

		$save_columns = false;

		if(!$preset_id) {
			// Add new preset
			$preset_id = App::insert('ui_element_preset', [
				'element_id' => $element->id,
				'user_id' => $user->id,
				'description' => $description
			]);

			if(!$preset_id) return $this->error('Error creating preset data.');

			$save_columns = true;
		} else {
			// Update existing preset
			$record = App::select('ui_element_preset', $preset_id);
			if($record['element_id'] !== $element->id) return $this->error('Invalid preset.');

			if($record['user_id'] == $user->id) {
				// User owns the preset, carry on saving details
				App::update('ui_element_preset', $preset_id, [
					'description' => $description
				]);

				// Remove existing columns
				App::sql()->delete("DELETE FROM ui_element_preset_columns WHERE preset_id = '$preset_id';");

				$save_columns = true;
			}
		}

		// Save column settings
		if($save_columns) {
			$index = 0;
			foreach($columns as $column) {
				$column = App::ensure($column, ['id', 'is_visible'], 0);

				if($column['id']) {
					$index += 1;
					App::insert('ui_element_preset_columns', [
						'preset_id' => $preset_id,
						'column_id' => $column['id'],
						'display_order' => $index,
						'is_visible' => $column['is_visible'] ? 1 : 0
					]);
				}
			}
		}

		// Update selected preset for the user
		App::sql()->update(
			"UPDATE ui_element_preset
			SET is_selected = IF(id = '$preset_id', 1, 0)
			WHERE element_id = '$element->id' AND user_id = '$user->id';
		");

		return $this->success();
	}

}
