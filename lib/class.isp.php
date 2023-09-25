<?php

/**
 * Returns instance or null. Pass ISP class name and query result or id directly.
 * Everything will be validated automatically.
 */
function isp_instance($classname, $id) {
	if(is_object($id)) {
		if(isset($id->id)) {
			$id = $id->id;
		} else {
			return null;
		}
	} else if(is_array($id)) {
		if(isset($id['id'])) {
			if(count($id) === 1) {
				// ID was passed alone as an array, extract
				$id = $id['id'];
			} else {
				// The full record was passed, do nothing and let it through
			}
		} else {
			return null;
		}
	} else if(!$id) {
		return null;
	}

	$item = new $classname($id);
	return $item->validate() ? $item : null;
}

/**
 * Returns list of instances or empty array. Pass ISP class name and query result or id list directly.
 * Everything will be validated automatically.
 */
function isp_instance_list($classname, $list) {
	$result = [];
	if($list) {
		foreach($list as $i) {
			$item = isp_instance($classname, $i);
			if($item !== null) $result[] = $item;
		}
	}
	return $result;
}

function isp_records($list) {
	return array_map(function($item) { return $item->record; }, $list ?: []);
}

function isp_info($list, $options = []) {
	return array_map(function($item) use ($options) { return $item->get_info($options); }, $list ?: []);
}

class ISP {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('system_integrator', $id);
		}
	}

	public function validate() {
		if(!$this->record) return false;
		$perm = Permission::get_system_integrator($this->id);
		return $perm->check(Permission::ISP_ENABLED);
	}

	public function get_info($options = []) {
		return $this->record;
	}

	public function list_clients() {
		$list = App::sql()->query("SELECT * FROM client WHERE system_integrator_id = '$this->id' ORDER BY name;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPClient', $list);
	}

	public function get_client($id) {
		$id = App::escape($id);
		$item = App::sql()->query_row("SELECT * FROM client WHERE id = '$id' AND system_integrator_id = '$this->id';", MySQL::QUERY_ASSOC);
		return isp_instance('ISPClient', $item);
	}

	public function list_buildings() {
		$list = App::sql()->query(
			"SELECT b.*
			FROM building AS b
			JOIN client AS c ON c.id = b.client_id
			WHERE c.system_integrator_id = '$this->id' AND b.module_isp = 1
			ORDER BY b.description;
		", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPBuilding', $list);
	}

	public function list_olt() {
		$result = [];
		foreach($this->list_buildings() as $building) {
			$result = array_merge($result, $building->list_olt());
		}
		return $result;
	}

	public function get_building($id) {
		$id = App::escape($id);
		$item = App::sql()->query_row(
			"SELECT b.*
			FROM building AS b
			JOIN client AS c ON c.id = b.client_id
			WHERE c.system_integrator_id = '$this->id' AND b.id = '$id' AND b.module_isp = 1;
		", MySQL::QUERY_ASSOC);
		return isp_instance('ISPBuilding', $item);
	}

	public function list_customers() {
		$list = App::sql()->query(
			"SELECT *
			FROM customer
			WHERE owner_type = 'SI' AND owner_id = '$this->id'
			ORDER BY contact_name, company_name;
		", MySQL::QUERY_ASSOC);
		return isp_instance_list('Customer', $list);
	}

	public function get_customer($id) {
		$id = App::escape($id);
		$item = App::sql()->query_row(
			"SELECT *
			FROM customer
			WHERE owner_type = 'SI' AND owner_id = '$this->id' AND id = '$id';
		", MySQL::QUERY_ASSOC);
		return isp_instance('Customer', $item);
	}

	public function list_contract_templates() {
		$list = App::sql()->query(
			"SELECT *
			FROM contract
			WHERE owner_type = 'SI' AND owner_id = '$this->id' AND is_template = 1
			ORDER BY start_date;
		", MySQL::QUERY_ASSOC);
		return isp_instance_list('Contract', $list);
	}

}

class ISPClient {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('client', $id);
		}
		unset($this->record['logo_img']);
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		return $this->record;
	}

	public function get_isp() {
		return isp_instance('ISP', $this->record['system_integrator_id']);
	}

	public function list_buildings() {
		$list = App::sql()->query("SELECT * FROM building WHERE client_id = '$this->id' AND module_isp = 1 ORDER BY description;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPBuilding', $list);
	}

	public function get_building($id) {
		$id = App::escape($id);
		$item = App::sql()->query_row("SELECT * FROM building WHERE id = '$id' AND client_id = '$this->id' AND module_isp = 1;", MySQL::QUERY_ASSOC);
		return isp_instance('ISPBuilding', $item);
	}

}

class ISPBuilding {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('building', $id);
		}
	}

	public function validate() {
		if(!$this->record) return false;
		return !!$this->record['module_isp'];
	}

	public function get_info($options = []) {
		$info = $this->record;

		if(in_array('areas', $options)) $info['areas'] = isp_info($this->list_areas());
		if(in_array('packages', $options)) $info['packages'] = isp_info($this->list_packages(), ['expand']);
		if(in_array('onus', $options)) $info['onus'] = isp_info($this->list_onu());

		return $info;
	}

	public function get_client() {
		return isp_instance('ISPClient', $this->record['client_id']);
	}

	public function get_isp() {
		$client = $this->get_client();
		return $client ? $client->get_isp() : null;
	}

	public function list_hes() {
		$list = App::sql('isp')->query("SELECT * FROM hes WHERE building_id = '$this->id' ORDER BY description;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPHES', $list);
	}

	public function get_hes($id) {
		$id = App::escape($id);
		$item = App::sql('isp')->query_row("SELECT * FROM hes WHERE id = '$id' AND building_id = '$this->id';", MySQL::QUERY_ASSOC);
		return isp_instance('ISPHES', $item);
	}

	public function list_olt() {
		$result = [];
		foreach($this->list_hes() as $hes) {
			$result = array_merge($result, $hes->list_olt());
		}
		return $result;
	}

	public function get_olt($id) {
		foreach($this->list_hes() as $hes) {
			$olt = $hes->get_olt($id);
			if($olt) return $olt;
		}
		return null;
	}

	public function list_onu() {
		$list = App::sql('isp')->query(
			"SELECT onu.*
			FROM hes
			JOIN olt ON olt.hes_id = hes.id
			JOIN onu ON onu.olt_id = olt.id
			WHERE hes.building_id = '$this->id'
			ORDER BY hes.description, olt.description, onu.description;
		", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPONU', $list);
	}

	public function get_onu($id) {
		foreach($this->list_hes() as $hes) {
			$onu = $hes->get_onu($id);
			if($onu) return $onu;
		}
		return null;
	}

	public function list_packages() {
		$result = [];
		foreach($this->list_olt() as $olt) {
			$result = array_merge($result, $olt->list_packages());
		}
		return $result;
	}

	public function get_package($id) {
		foreach($this->list_olt() as $olt) {
			$package = $olt->get_package($id);
			if($package) return $package;
		}
		return null;
	}

	public function list_areas() {
		$list = App::sql('isp')->query(
			"SELECT DISTINCT onu.area_id
			FROM hes
			JOIN olt ON olt.hes_id = hes.id
			JOIN onu ON onu.olt_id = olt.id
			WHERE hes.building_id = '$this->id';
		") ?: [];

		$areas = [];
		foreach($list as $item) {
			$areas[] = $item->area_id;
		}

		if(count($areas) === 0) return [];
		$areas = implode(',', $areas);

		$list = App::sql()->query(
			"SELECT a.*
			FROM area AS a
			JOIN floor AS f ON f.id = a.floor_id
			WHERE a.id IN ($areas)
			ORDER BY f.display_order, a.display_order, a.description;
		", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPArea', $list);
	}

	public function get_area($id) {
		$id = App::escape($id);
		$item = App::sql('isp')->query_row(
			"SELECT DISTINCT onu.area_id
			FROM hes
			JOIN olt ON olt.hes_id = hes.id
			JOIN onu ON onu.olt_id = olt.id
			WHERE hes.building_id = '$this->id' AND onu.area_id = '$id';
		");
		return isp_instance('ISPArea', $item);
	}

}

class ISPHES {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('hes@isp', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		return $this->record;
	}

	public function get_building() {
		return isp_instance('ISPBuilding', $this->record['building_id']);
	}

	public function list_olt() {
		$list = App::sql('isp')->query("SELECT * FROM olt WHERE hes_id = '$this->id' ORDER BY description;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPOLT', $list);
	}

	public function get_olt($id) {
		$id = App::escape($id);
		$item = App::sql('isp')->query_row("SELECT * FROM olt WHERE id = '$id' AND hes_id = '$this->id';", MySQL::QUERY_ASSOC);
		return isp_instance('ISPOLT', $item);
	}

	public function list_onu() {
		$result = [];
		foreach($this->list_olt() as $olt) {
			$result = array_merge($result, $olt->list_onu());
		}
		return $result;
	}

	public function get_onu($id) {
		foreach($this->list_olt() as $olt) {
			$onu = $olt->get_onu($id);
			if($onu) return $onu;
		}
		return null;
	}

}

class ISPOLT {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('olt@isp', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		$data = $this->record;

		if(in_array('overview', $options)) {
			$board = App::sql('isp')->query_row("SELECT * FROM olt_board WHERE olt_id = '$this->id' LIMIT 1;");
			$data['board'] = $board ?: null;

			$hes = $this->get_hes();
			$data['hes'] = $hes ? $hes->get_info() : null;

			$building = $hes ? $hes->get_building() : null;
			$data['building'] = $building ? $building->get_info() : null;

			$client = $building ? $building->get_client() : null;
			$data['client'] = $client ? $client->get_info() : null;

			$data['commands'] = $this->list_commands();
		}

		return $data;
	}

	public function get_hes() {
		return isp_instance('ISPHES', $this->record['hes_id']);
	}

	public function list_onu() {
		$list = App::sql('isp')->query("SELECT * FROM onu WHERE olt_id = '$this->id' ORDER BY slot, port, onu;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPONU', $list);
	}

	public function get_onu($id) {
		$id = App::escape($id);
		$item = App::sql('isp')->query_row("SELECT * FROM onu WHERE id = '$id' AND olt_id = '$this->id';", MySQL::QUERY_ASSOC);
		return isp_instance('ISPONU', $item);
	}

	public function list_packages() {
		$list = App::sql('isp')->query("SELECT * FROM olt_service WHERE olt_id = '$this->id' ORDER BY id;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPPackage', $list);
	}

	public function get_package($id) {
		$id = App::escape($id);
		$item = App::sql('isp')->query_row("SELECT * FROM olt_service WHERE id = '$id' AND olt_id = '$this->id';", MySQL::QUERY_ASSOC);
		return isp_instance('ISPPackage', $item);
	}

	public function list_upstream_profile() {
		$list = App::sql('isp')->query("SELECT * FROM profile_upstream WHERE olt_id = '$this->id' ORDER BY name;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPUpstreamProfile', $list);
	}

	public function get_upstream_profile($id) {
		$id = App::escape($id);
		$item = App::sql('isp')->query_row("SELECT * FROM profile_upstream WHERE id = '$id' AND olt_id = '$this->id';", MySQL::QUERY_ASSOC);
		return isp_instance('ISPUpstreamProfile', $item);
	}

	public function list_downstream_profile() {
		$list = App::sql('isp')->query("SELECT * FROM profile_downstream WHERE olt_id = '$this->id' ORDER BY name;", MySQL::QUERY_ASSOC);
		return isp_instance_list('ISPDownstreamProfile', $list);
	}

	public function get_downstream_profile($id) {
		$id = App::escape($id);
		$item = App::sql('isp')->query_row("SELECT * FROM profile_downstream WHERE id = '$id' AND olt_id = '$this->id';", MySQL::QUERY_ASSOC);
		return isp_instance('ISPDownstreamProfile', $item);
	}

	public function list_commands() {
		$list = App::sql('isp')->query("SELECT * FROM todo WHERE olt_id = '$this->id' ORDER BY datetime;", MySQL::QUERY_ASSOC);
		return $list ?: [];
	}

}

class ISPONU {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('onu@isp', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		$data = $this->record;

		$p = $this->get_active_package();
		$data['active_package'] = $p ? $p->get_info() : null;

		$data['commands'] = $this->list_commands();
		return $data;
	}

	public function get_olt() {
		return isp_instance('ISPOLT', $this->record['olt_id']);
	}

	public function get_area() {
		return isp_instance('ISPArea', $this->record['area_id']);
	}

	public function get_active_package() {
		$olt = $this->get_olt();
		if(!$olt) return null;

		$p = App::sql('isp')->query_row(
			"SELECT
				olts.*
			FROM olt_service AS olts
			JOIN onu_service AS onus ON onus.onu_table_id = '$this->id' AND olts.service_id = onus.olt_network_service_id AND olts.upstream_dba_profile_id = onus.profile_upstream_id AND olts.ethernet_profile_id = onus.profile_downstream_id AND onus.admin = 1
			WHERE olts.olt_id = '$olt->id'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if(!$p) return null;

		return isp_instance('ISPPackage', $p);
	}

	public function list_commands() {
		// Get building timezone
		$area_id = $this->record['area_id'];
		$tz = App::sql()->query_row(
			"SELECT b.timezone
			FROM area AS a
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			WHERE a.id = '$area_id'
			LIMIT 1;
		", MySQL::QUERY_ASSOC);
		if($tz) $tz = $tz['timezone'];

		$list = App::sql('isp')->query("SELECT * FROM todo WHERE onu_table_id = '$this->id' ORDER BY datetime;", MySQL::QUERY_ASSOC) ?: [];

		if($tz) {
			return array_map(function($item) use ($tz) {
				$item['datetime'] = App::timezone($item['datetime'], 'UTC', $tz);

				return $item;
			}, $list);
		} else {
			return $list;
		}
	}

	public function set_package($package = null) {
		$user = App::user();
		$user_id = $user ? $user->id : 0;
		$package = $package ? $package->id : 0;
		$olt_id = $this->record['olt_id'];

		App::insert('todo@isp', [
			'user_id' => $user_id,
			'olt_id' => $olt_id,
			'onu_table_id' => $this->id,
			'cmd' => "set_internet_package_on_onu($user_id, $this->id, $package)"
		]);
	}

	public function reboot() {
		$user = App::user();
		$user_id = $user ? $user->id : 0;
		$olt_id = $this->record['olt_id'];

		App::insert('todo@isp', [
			'user_id' => $user_id,
			'olt_id' => $olt_id,
			'onu_table_id' => $this->id,
			'cmd' => "reboot_onu($user_id, $this->id)"
		]);
	}

	public function set_wifi_settings($wifi_ssid, $wifi_password) {
		// Remove special characters from SSID and password
		$wifi_ssid = preg_replace('/[^A-Za-z0-9\-]/', '', $wifi_ssid);
		$wifi_password = preg_replace('/[^A-Za-z0-9\-]/', '', $wifi_password);

		if(!$wifi_ssid || !$wifi_password) return false;

		$user = App::user();
		$user_id = $user ? $user->id : 0;
		$olt_id = $this->record['olt_id'];

		$record = App::select('onu@isp', $this->id);
		if(!$record) return false;

		if($record['pending_wifi_ssid'] || $record['pending_wifi_password']) return false;
		if($wifi_ssid === $record['wifi_ssid'] && $wifi_password === $record['wifi_password']) return true;

		// Update pending state and send command

		// App::insert('todo@isp', [
		// 	'user_id' => $user_id,
		// 	'olt_id' => $olt_id,
		// 	'onu_table_id' => $this->id,
		// 	'cmd' => "set_new_onu_ssid_and_password($user_id, $this->id, $wifi_ssid, $wifi_password)"
		// ]);
		// App::sql()->insert(
		// 	"INSERT INTO payment_stripe_card (payment_gateway_id, customer_type, customer_id, stripe_customer, card_type, exp_month, exp_year, last4)
		// 	VALUES ('$pg->id', '$pa->customer_type', '$pa->customer_id', '$customer_id', '$card_type', '$card_exp_month', '$card_exp_year', '$card_last4');
		// ");

		$update_fields = [];
		if($wifi_ssid != $record['wifi_ssid']) $update_fields['pending_wifi_ssid'] = $wifi_ssid;
		if($wifi_password != $record['wifi_password']) $update_fields['pending_wifi_password'] = $wifi_password;

		if(count($update_fields) && !App::update('onu@isp', $this->id, $update_fields)) return false;

		return true;
	}

	// public function set_pending_wifi_settings($pending_wifi_ssid, $pending_wifi_password){
	// 	// Remove special characters from SSID and password
	// 	$pending_wifi_ssid = preg_replace('/[^A-Za-z0-9\-]/', '', $pending_wifi_ssid);
	// 	$pending_wifi_password = preg_replace('/[^A-Za-z0-9\-]/', '', $pending_wifi_password);

	// 	$user = App::user();
	// 	$user_id = $user ? $user->id : 0;
	// 	$olt_id = $this->record['olt_id'];

	// 	$record = App::select('onu@isp', $this->id);
	// 	if(!$record) return false;

	// 	// if(!$wifi_ssid || !$wifi_password) return false;

	// 	$update_fields = [];
	// 	 $update_fields['pending_wifi_ssid'] = $pending_wifi_ssid;
	// 	 $update_fields['pending_wifi_password'] = $pending_wifi_password;

	// 	if(count($update_fields) && !App::update('onu@isp', $this->id, $update_fields)) return false;

	// 	return true;
	// }

	public function set_pending_wifi_settings($wifi_ssid, $wifi_password) {
		// Remove special characters from SSID and password
		$wifi_ssid = preg_replace('/[^A-Za-z0-9\-]/', '', $wifi_ssid);
		$wifi_password = preg_replace('/[^A-Za-z0-9\-]/', '', $wifi_password);

		if(!$wifi_ssid || !$wifi_password) return false;

		$user = App::user();
		$user_id = $user ? $user->id : 0;
		$olt_id = $this->record['olt_id'];

		$record = App::select('onu@isp', $this->id);
		if(!$record) return false;

		// if($record['pending_wifi_ssid'] || $record['pending_wifi_password']) return false;
		// if($wifi_ssid === $record['wifi_ssid'] && $wifi_password === $record['wifi_password']) return true;

		// Update pending state and send command

		// App::insert('todo@isp', [
		// 	'user_id' => $user_id,
		// 	'olt_id' => $olt_id,
		// 	'onu_table_id' => $this->id,
		// 	'cmd' => "set_new_onu_ssid_and_password($user_id, $this->id, $wifi_ssid, $wifi_password)"
		// ]);

		$update_fields = [];
		if($wifi_ssid != $record['wifi_ssid']) $update_fields['pending_wifi_ssid'] = $wifi_ssid;
		if($wifi_password != $record['wifi_password']) $update_fields['pending_wifi_password'] = $wifi_password;

		if(count($update_fields) && !App::update('onu@isp', $this->id, $update_fields)) return false;

		return true;
	}

	public function todo_wifi_settings($wifi_ssid, $wifi_password) {
		// Remove special characters from SSID and password
		$wifi_ssid = preg_replace('/[^A-Za-z0-9\-]/', '', $wifi_ssid);
		$wifi_password = preg_replace('/[^A-Za-z0-9\-]/', '', $wifi_password);

		if(!$wifi_ssid || !$wifi_password) return false;

		$user = App::user();
		$user_id = $user ? $user->id : 0;
		$olt_id = $this->record['olt_id'];

		$record = App::select('onu@isp', $this->id);
		if(!$record) return false;

		// if($record['pending_wifi_ssid'] || $record['pending_wifi_password']) return false;
		// if($wifi_ssid === $record['wifi_ssid'] && $wifi_password === $record['wifi_password']) return true;

		// Update pending state and send command

		App::insert('todo@isp', [
			'user_id' => $user_id,
			'olt_id' => $olt_id,
			'onu_table_id' => $this->id,
			'cmd' => "set_new_onu_ssid_and_password($user_id, $this->id, $wifi_ssid, $wifi_password)"
		]);

		$update_fields = [];
		if($wifi_ssid != $record['wifi_ssid']) $update_fields['pending_wifi_ssid'] = $wifi_ssid;
		if($wifi_password != $record['wifi_password']) $update_fields['pending_wifi_password'] = $wifi_password;

		if(count($update_fields) && !App::update('onu@isp', $this->id, $update_fields)) return false;

		return true;
	}

	

}

class ISPArea {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('area', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		$data = $this->record;

		if(in_array('expand', $options)) {
			$floor_id = $this->record['floor_id'];
			$f = App::sql()->query_row("SELECT description FROM floor WHERE id = '$floor_id';");
			$data['floor_description'] = $f ? $f->description : '';

			$onu = $this->get_onu();
			$data['onu'] = $onu ? $onu->get_info() : null;

			$contracts = App::sql()->query("SELECT * FROM contract WHERE area_id = '$this->id' AND customer_type = 'CU';", MySQL::QUERY_ASSOC);
			$data['contracts'] = isp_info(isp_instance_list('Contract', $contracts));
		}

		return $data;
	}

	public function get_building() {
		$floor_id = $this->record['floor_id'];
		if(!$floor_id) return null;

		$id = App::sql()->query_row("SELECT building_id FROM floor WHERE id = '$floor_id';");
		return $id ? isp_instance('ISPBuilding', $id->building_id) : null;
	}

	public function get_onu() {
		$onu = App::sql('isp')->query_row("SELECT * FROM onu WHERE area_id = '$this->id' LIMIT 1;", MySQL::QUERY_ASSOC);
		if(!$onu) return null;
		return isp_instance('ISPONU', $onu);
	}

}

class ISPPackage {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('olt_service@isp', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		$data = $this->record;

		if(in_array('expand', $options)) {
			$up = new ISPUpstreamProfile($this->record['upstream_dba_profile_id']);
			if(!$up->validate()) $up = null;

			$down = new ISPDownstreamProfile($this->record['ethernet_profile_id']);
			if(!$down->validate()) $down = null;

			$data['upstream_profile'] = $up ? $up->get_info() : null;
			$data['downstream_profile'] = $down ? $down->get_info() : null;
		}

		return $data;
	}

	public function get_olt() {
		return isp_instance('ISPOLT', $this->record['olt_id']);
	}

}

class ISPUpstreamProfile {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('profile_upstream@isp', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		return $this->record;
	}

	public function get_olt() {
		return isp_instance('ISPOLT', $this->record['olt_id']);
	}

}

class ISPDownstreamProfile {

	public $id = null;
	public $record = null;

	public function __construct($id) {
		if(is_array($id)) {
			// A full record was passed
			$this->id = $id['id'];
			$this->record = $id;
		} else {
			// An ID was passed
			$this->id = App::escape($id);
			$this->record = App::select('profile_downstream@isp', $id);
		}
	}

	public function validate() {
		return !!$this->record;
	}

	public function get_info($options = []) {
		return $this->record;
	}

	public function get_olt() {
		return isp_instance('ISPOLT', $this->record['olt_id']);
	}

}
