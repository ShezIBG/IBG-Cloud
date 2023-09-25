<?php

class Building {

	public $info;
	public $id;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM building WHERE id = '$id';");
	}

	public function validate() {
		return $this->info && Permission::get_building($this->id)->has_access();
	}

	/**
	 * Updates building_id and floor_id in the meter table. Those fields are only for convenience.
	 * This method has to be called after changes in the configurator, as those fields are not updated there.
	 *
	 * The original plan was to remove those fields altogether, but John asked for them back to simplify his queries.
	 */
	public function claim_meters() {
		App::sql()->update(
			"UPDATE meter AS m
			JOIN area AS a ON a.id = m.area_id
			JOIN floor AS f ON f.id = a.floor_id
			SET
				m.building_id = f.building_id,
				m.floor_id = f.id
			WHERE f.building_id = '$this->id';
		");
	}

	public function evaluate_modules() {
		$sql = App::sql();

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM pm12 AS t JOIN area AS a ON a.id = t.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id';");
		$pm12_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM abb_meter AS t JOIN area AS a ON a.id = t.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id';");
		$abb_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id';");
		$meter_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.meter_type = 'G' AND m.monitoring_device_type <> 'none' AND m.is_supply_meter = 1;");
		$auto_gas_meter_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.meter_type = 'W' AND m.monitoring_device_type <> 'none' AND m.is_supply_meter = 1;");
		$auto_water_meter_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.meter_type = 'E' AND m.meter_direction = 'generation' AND m.monitoring_device_type <> 'none';");
		$auto_renewable_meter_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM gateway AS t JOIN area AS a ON a.id = t.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND t.type = 'DLC64';");
		$dlc64_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM em_light AS t JOIN area AS a ON a.id = t.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id';");
		$em_light_count = $count ? $count->cnt : 0;

		$count = $sql->query_row("SELECT COUNT(*) AS cnt FROM area AS a JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND (a.is_tenanted = 1 OR a.is_owner_occupied = 1);");
		$tenanted_area_count = $count ? $count->cnt : 0;

		$count = App::sql('isp')->query_row("SELECT COUNT(*) AS cnt FROM hes WHERE building_id = '$this->id';");
		$hes_count = $count ? $count->cnt : 0;

		$count = App::sql('climate')->query_row("SELECT COUNT(*) AS cnt FROM coolhub WHERE building_id = '$this->id';");
		$coolhub_count = $count ? $count->cnt : 0;

		$count = App::sql('relay')->query_row("SELECT COUNT(*) AS cnt FROM relay_end_device WHERE building_id = '$this->id';");
		$relay_count = $count ? $count->cnt : 0;

		$light_count = 0;
		if(Lighting::check_database($this->id)) {
			$count = App::sql('dali')->query_row("SELECT COUNT(*) AS cnt FROM ve_dali_$this->id.dali_light;");
			$light_count = $count ? $count->cnt : 0;
		}

		$count = App::sql()->query_row("SELECT COUNT(*) AS cnt FROM smoothpower WHERE building_id = '$this->id';");
		$smoothpower_count = $count ? $count->cnt : 0;

		$module_electricity = $pm12_count || $abb_count;
		$module_gas = !!$auto_gas_meter_count;
		$module_water = !!$auto_water_meter_count;
		$module_renewables = !!$auto_renewable_meter_count;
		$module_meters = !!$meter_count;
		$module_emergency = $dlc64_count || $em_light_count;
		$module_building = !!$tenanted_area_count;
		$module_reports = $module_electricity;
		$module_isp = !!$hes_count;
		$module_climate = !!$coolhub_count;
		$module_relay = !!$relay_count;
		$module_smoothpower = !!$smoothpower_count;
		$module_lighting = !!$light_count;

		$this->update([
			'module_electricity' => $module_electricity ? 1 : 0,
			'module_gas' => $module_gas ? 1 : 0,
			'module_water' => $module_water ? 1 : 0,
			'module_renewables' => $module_renewables ? 1 : 0,
			'module_meters' => $module_meters ? 1 : 0,
			'module_emergency' => $module_emergency ? 1 : 0,
			'module_building' => $module_building ? 1 : 0,
			'module_reports' => $module_reports ? 1 : 0,
			'module_isp' => $module_isp ? 1 : 0,
			'module_climate' => $module_climate ? 1 : 0,
			'module_relay' => $module_relay ? 1 : 0,
			'module_smoothpower' => $module_smoothpower ? 1 : 0,
			'module_lighting' => $module_lighting ? 1 : 0,
			'is_tenanted' => !!$tenanted_area_count ? 1 : 0
		]);
	}

	public function update($fields) {
		$result = App::update('building', $this->id, $fields);

		// Reload details
		$this->__construct($this->id);

		return $result;
	}

	public static function get_area_size_units() {
		return [
			[
				'description' => 'Square ft',
				'value'       => 'square feet'
			],
			[
				'description' => 'Square metres',
				'value'       => 'square meters'
			]
		];
	}

	public function get_floors($filter = [], $fields = ['floor.*'], $joins = []) {
		return App::sql()->query("SELECT ".implode(', ', $fields)." FROM floor ".implode(' ', $joins)." WHERE floor.building_id = $this->id ".App::sql()->build_filter_string($filter)." ORDER BY floor.display_order");
	}

	public function get_tenanted_areas($filter = [], $fields = [], $joins = [], $order_by = "ORDER BY area.display_order") {
		return $this->get_areas($filter, array_merge(['area.*', 'tenanted_area.id as tenanted_id', 'tenant_type'], $fields), array_merge(["INNER JOIN tenanted_area ON tenanted_area.area_id = area.id AND (area.is_tenanted = 1 OR area.is_owner_occupied = 1)"], $joins), "area.id", $order_by);
	}

	public function get_tenant_info($tenant_id) {
		return App::sql()->query("SELECT * FROM tenant WHERE id = $tenant_id")[0];
	}

	public function get_tenants($show_inactive = false, $fields = ['*']) {
		return App::sql()->query('SELECT '.implode(', ', $fields).' FROM tenant WHERE client_id = '.$this->info->client_id.($show_inactive ? '' : ' AND active = 1').' ORDER BY name');
	}

	public function get_areas($filter = [], $fields = ['area.*'], $joins = [], $group_by = '', $order_by = "ORDER BY area.display_order") {
		return App::sql()->query("SELECT ".implode(', ', $fields)." FROM area ".implode(' ', $joins)." ".App::sql()->build_filter_string($filter, null, "WHERE").($group_by ? " GROUP BY $group_by" : "")." ".$order_by);
	}

	public function get_meters($filter = [], $fields = ['meter.*'], $joins = ['JOIN area ON area.id = meter.area_id', 'JOIN floor ON floor.id = area.floor_id'], $orderby = '') {
		
		return App::sql()->query(
			"SELECT ".implode(', ', $fields)."
			FROM meter
			".implode(' ', $joins)."
			".App::sql()->build_filter_string($filter, null, "WHERE")." ".$orderby
		);
	}

	public function update_holidays($holidays) {
		$area_id = 0;

		// Delete existing holidays, as they will be re-created
		App::sql()->delete("DELETE FROM building_holiday WHERE building_id = $this->id AND area_id = $area_id;");

		foreach($holidays as $holiday) {
			App::sql()->insert("INSERT INTO building_holiday(building_id, description, date, open_time, close_time, closed_all_day, area_id) VALUES ($this->id, '$holiday->description', '$holiday->date', '$holiday->open_time', '$holiday->close_time', $holiday->closed_all_day, $area_id)");
		}

		Climate::rebuild_building_holiday_schedule($this->id);
		Relay::rebuild_building_holiday_schedule($this->id);
		Control::rebuild_building_holiday_schedule($this->id);

		return true;
	}

	public function update_working_hours($open_times, $close_times, $closed_all_days = []) {
		$days = Eticom::get_days();
		foreach($days as $day) {
			$open_time = isset($open_times[$day]) ? $open_times[$day] : '00:00';
			$close_time = isset($close_times[$day]) ? $close_times[$day] : '00:00';
			$closed_all_day = isset($closed_all_days[$day]) && $closed_all_days[$day] ? 1 : 0;

			$query = "INSERT INTO building_working_hours(building_id, day, open_time, close_time, closed_all_day)
				SELECT * FROM (SELECT $this->id AS building_id, '$day' AS day, '$open_time' AS open_time, '$close_time' AS close_time, $closed_all_day AS closed_all_day) AS tmp
				WHERE NOT EXISTS(
					SELECT day FROM building_working_hours WHERE building_id = $this->id AND day = '$day'
				) LIMIT 1";

			$result = App::sql()->insert($query);
			if (!$result) App::sql()->update("UPDATE building_working_hours SET open_time = '$open_time', close_time = '$close_time', closed_all_day = $closed_all_day WHERE building_id = $this->id AND day = '$day'");
		}

		return true;
	}

	public function get_working_hours() {
		return App::sql()->query("SELECT * FROM building_working_hours WHERE building_id = $this->id ");
	}

	public function get_holidays() {
		return App::sql()->query("SELECT * FROM building_holiday WHERE building_id = $this->id AND area_id = 0 ORDER BY date;");
	}

	public function get_working_hours_plot($date = null) {
		$date = App::resolve_date_range($date)[0];
		$dow = date('l', strtotime($date));

		// Initialise array
		$plot = [];
		for($i = 0; $i < 24; $i++) $plot[$i] = false;

		$dp = $this->get_working_days_plot($date);
		if(!$dp[$date]) return $plot;

		$wh = App::sql()->query_row("SELECT open_time, close_time FROM building_working_hours WHERE building_id = '$this->id' AND closed_all_day = 0 AND day = '$dow' LIMIT 1;");
		if(!$wh) return $plot;

		$time_from = (int) explode(':', $wh->open_time)[0];

		// Don't include hour in the plot if it's on the hour
		$time_to = explode(':', $wh->close_time);
		if((int)$time_to[1] === 0 && (int)$time_to[2] === 0) {
			$time_to = (int)$time_to[0] - 1;
		} else {
			$time_to = (int)$time_to[0];
		}

		// Swap times if they're in the wrong order
		if($time_to < $time_from) list($time_from, $time_to) = [$time_to, $time_from];

		for($i = $time_from; $i <= $time_to; $i++) $plot[$i] = true;

		return $plot;
	}

	public function get_working_days_plot($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);

		$wd = App::sql()->query("SELECT day FROM building_working_hours WHERE building_id = '$this->id' AND closed_all_day = 1;");
		$dow = array_map(function($row) { return $row->day; }, $wd ?: []);

		// Initialise array with all days in range
		$plot = [];
		$day = $date_from;
		while(true) {
			$plot[$day] = in_array(date('l', strtotime($day)), $dow) ? false : true;

			if($day == $date_to) break;
			$day = date('Y-m-d', strtotime('+1 day', strtotime($day)));
		}

		// Loop through holidays and block days when the building is closed all day
		$hol = App::sql()->query("SELECT date FROM building_holiday WHERE building_id = '$this->id' AND closed_all_day = 1 AND date BETWEEN '$date_from' AND '$date_to';");
		if($hol) {
			foreach($hol as $row) {
				if($row->date && isset($plot[$row->date])) {
					$plot[$row->date] = false;
				}
			}
		}

		return $plot;
	}

	public function html_building_manager_unit_list() {
		$status_current = "'".Lease::STATUS_CURRENT_ACTIVE."', '".Lease::STATUS_CURRENT_EXPIRING."', '".Lease::STATUS_CURRENT_ENDING."'";
		$status_future = Lease::STATUS_FUTURE;

		$area_list = App::sql()->query(
			"SELECT
				cl.tenant_id AS tenant_id,
				t.company AS tenant_company,
				a.description AS unit_name,
				f.description AS floor_name,
				ta.id AS tenanted_id,
				cl.id AS current_lease,
				fl.id AS future_lease,
				cl.status AS current_status
			FROM
				building AS b
			JOIN floor AS f ON f.building_id = b.id
			JOIN area AS a ON a.floor_id = f.id
			JOIN tenanted_area AS ta ON ta.area_id = a.id AND (a.is_tenanted = 1 OR a.is_owner_occupied = 1)
			LEFT JOIN tenant_lease AS cl ON cl.area_id = a.id AND cl.status IN ($status_current)
			LEFT JOIN tenant_lease AS fl ON fl.area_id = a.id AND fl.status = '$status_future'
			LEFT JOIN tenant AS t ON t.id = cl.tenant_id
			WHERE b.id = '$this->id'
			ORDER BY f.display_order, a.display_order
		") ?: [];

		$result = '';

		foreach($area_list as $area) {
			$tenant_id      = $area->tenant_id;
			$tenant_company = $tenant_id ? $area->tenant_company : 'Vacant';
			$unit_name      = $area->unit_name;
			$floor_name     = $area->floor_name;
			$tenanted_id    = $area->tenanted_id;
			$current_lease  = $area->current_lease;
			$future_lease   = $area->future_lease;
			$current_status = $area->current_status;

			if ($current_lease && ($current_status != Lease::STATUS_CURRENT_ENDING || $future_lease)) {
				$icon = 'eticon-circle-tick green';
				$icon_title = 'Unit has active lease';
			} else if ($future_lease) {
				$icon = 'eticon-circle-ellipsis grey';
				$icon_title = 'Unit has upcoming lease';
			} else {
				$icon = 'eticon-alert yellow';
				$icon_title = 'Unit is empty';
			}

			$result .= '
				<li class="list-item">
					<a href="#" class="unit" data-view="building_unit?tenanted_id='.$tenanted_id.'" data-title="'.$this->info->description.' / '.$floor_name.' / '.$unit_name.'">
						<i class="eticon '.$icon.' pull-right" title="'.$icon_title.'"></i>
						<div class="name">'.$unit_name.'</div>
						<div class="desc">'.$tenant_company.'</div>
					</a>
				</li>
			';
		}

		return $result;
	}

	public function html_building_manager_tenant_list() {
		$status_current = "'".Lease::STATUS_CURRENT_ACTIVE."', '".Lease::STATUS_CURRENT_EXPIRING."', '".Lease::STATUS_CURRENT_ENDING."'";
		$status_future = Lease::STATUS_FUTURE;
		$client_id = $this->info->client_id;

		$tenant_list = App::sql()->query(
			"SELECT
				t.id AS tenant_id,
				IF(t.company IS NOT NULL AND t.company <> '', t.company, t.name) AS tenant_name,
				IF(ca.description IS NOT NULL, ca.description, fa.description) AS unit_name,
				IF(cl.area_id IS NOT NULL, 2, IF(fl.area_id IS NOT NULL, 1, 0)) AS has_lease
			FROM
				tenant AS t
			LEFT JOIN (
				SELECT tenant_id, MIN(area_id) AS area_id FROM tenant_lease
				WHERE status IN ($status_current)
				GROUP BY tenant_id
			) AS cl ON t.id = cl.tenant_id
			LEFT JOIN (
				SELECT tenant_id, MIN(area_id) AS area_id FROM tenant_lease
				WHERE status = '$status_future'
				GROUP BY tenant_id
			) AS fl ON t.id = fl.tenant_id
			LEFT JOIN area AS ca ON ca.id = cl.area_id
			LEFT JOIN area AS fa ON fa.id = fl.area_id
			WHERE t.client_id = '$client_id'
			ORDER BY has_lease DESC, tenant_name;
		") ?: [];

		$result = '';

		foreach($tenant_list as $tenant) {
			$tenant_id   = $tenant->tenant_id;
			$tenant_name = $tenant->tenant_name ?: '&nbsp;';
			$unit_name   = $tenant->unit_name ?: '&nbsp;';
			$has_lease   = $tenant->has_lease;

			$text_class = '';
			if ($has_lease == 2) {
				$icon = 'eticon-circle-tick green';
				$icon_title = 'Current tenant';
			} else if ($has_lease == 1) {
				$icon = 'eticon-circle-ellipsis grey';
				$icon_title = 'Future tenant';
			} else {
				$icon = 'eticon-clock grey';
				$icon_title = 'No current/future lease';
				$unit_name = 'No lease';
				$text_class = ' txt-color-greyLighter';
			}

			$result .= '
				<li class="list-item">
					<a href="#" class="unit" data-view="building_tenant?building_id='.$this->id.'&tenant_id='.$tenant_id.'" data-title="'.$tenant_name.'">
						<i class="eticon '.$icon.' pull-right" title="'.$icon_title.'"></i>
						<div class="name'.$text_class.'">'.$tenant_name.'</div>
						<div class="desc'.$text_class.'">'.$unit_name.'</div>
					</a>
				</li>
			';
		}

		return $result;
	}

	public function get_mmm_meter_list_html($meter_type_filter = '', $building_structure = false, $virtual = true, $generated = false) {
		$ui = new SmartUI();
		$building = $this;
		$area_access = $building->get_area_ids_with_permission(Permission::METERS_ENABLED);
		if(!$area_access) return;
		$area_access = '('.implode(',', $area_access).')';
		$building_access = Permission::get_building($this->id)->check(Permission::METERS_ENABLED);

		$floors_data = [];

		$get_meters = function($filter, $show_sub) use ($building, &$get_meters, $building_structure) {
			$structure = [];
			if ($meters = $building->get_meters($filter, ['meter.*'], [], 'ORDER BY meter.meter_type, meter.description')) {
				foreach($meters as $meter) {
					$meter_filter = [ 'parent_id' => "='$meter->id'"];
					
					if(isset($filter['meter_type']) && $filter['meter_type']) $meter_filter['meter_type'] = $filter['meter_type'];

					$submeters = $show_sub ? $get_meters($meter_filter, true) : [];

					$meter_class = $meter->parent_id ? ' Sub' : '';

					$icon_class = '';
					switch($meter->meter_type) {
						case 'E':
							$icon_class = 'eticon eticon-bolt eticon-bolt-color';
							break;

						case 'G':
							$icon_class = 'eticon eticon-flame eticon-flame-color';
							break;

						case 'W':
							$icon_class = 'eticon eticon-droplet eticon-droplet-color';
							break;

						case 'H':
							$icon_class = 'eticon eticon-heat eticon-heat-color';
							break;
					}

					if($meter->meter_direction == 'generation') $icon_class = 'eticon eticon-leaf eticon-leaf-color';

					$desc = '';
					if($meter->parent_id && !$building_structure) {
						$m = new Meter($meter->id);
						$tn = $m->get_tenant_name();
						if($tn) {
							$desc = '<strong>'.$meter->description.'</strong> <i>('.$tn.')</i>';
						} else {
							$desc = '<strong>'.$meter->description.'</strong>';
						}
					} else {
						$desc = '<strong>'.$meter->description.'</strong>';
					}

					$structure[] = [
						'content'  => '<span class="label bg-color-greyDark"><i class="'.$icon_class.'"></i>'.$meter_class.'</span> <a href="#" data-exec="selectMeter" data-building-id="'.$this->id.'" data-meter-id="'.$meter->id.'" class="list_item">'.$desc.'</a>',
						'attr'     => [ 'data-li' => 'm'.$meter->id ],
						'subitems' => $submeters
					];
				}
			}
			return $structure;
		};

		$get_areas = function($floor) use ($building, $get_meters, $meter_type_filter, $building_structure, $generated, $area_access, $virtual) {
			$areas_structure = [];
			$client_id = $building->info->client_id;

			$areas = App::sql()->query(
				"SELECT
					a.id, a.description,
					IF(cu.id IS NULL,
						t.company,
						IF(COALESCE(cu.contact_name, '') <> '' AND COALESCE(cu.company_name, '') <> '', CONCAT(cu.contact_name, ', ', cu.company_name), COALESCE(NULLIF(cu.contact_name, ''), cu.company_name))
					) AS tenant_name
				FROM area AS a
				LEFT JOIN tenanted_area AS ta ON ta.area_id = a.id
				LEFT JOIN tenant AS t ON t.id = ta.tenant_id
				LEFT JOIN (
					SELECT
						c.area_id,
						MIN(c.customer_id) AS customer_id
					FROM contract AS c
					WHERE c.owner_type = 'C' AND c.owner_id = '$client_id' AND c.customer_type = 'CU' AND c.area_id IS NOT NULL AND c.status IN ('active', 'ending')
					GROUP BY c.area_id
				) AS c ON c.area_id = a.id
				LEFT JOIN customer AS cu ON cu.id = c.customer_id
				WHERE a.floor_id = '$floor->id' AND a.id IN $area_access
				ORDER BY a.display_order;
			") ?: [];
			foreach($areas as $area) {
				if($virtual) {
					$meter_filter = [ 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "='$area->id'", "'$area->id'" => "IN $area_access" ];

				} else {
					$meter_filter = [ 'meter.area_id' => "='$area->id'", 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "IN $area_access" ];
				}
				if(!$building_structure) $meter_filter['parent_id'] = 'IS NULL';
				if($meter_type_filter) {
					$meter_filter['meter_type'] = "= '$meter_type_filter'";
					$meter_filter['meter_direction'] = $generated ? "= 'generation'" : "<> 'generation'";
				}

				$device_content = $get_meters($meter_filter, !$building_structure);

				// print_r($area); exit;
				if($device_content && count($device_content)) {
					$area_html = '<span class="label label-default">Area</span> <strong>'.$area->description.'</strong>';
					if($area->tenant_name) $area_html .= ' <i>- '.$area->tenant_name.'</i>';
					$areas_structure[] = [
						'content'  => $area_html,
						'attr'     => [ 'data-li' => 'a'.$area->id, 'data-building' => $building->id, 'data-floor' => $floor->id, 'data-area' => $area->id ],
						'class'    => 'item-type-area',
						'subitems' => $device_content
					];
				}
			}

			return $areas_structure;
		};
		
		//Added by Shez for Demo user
		$User_id = USER::Current_user();
		
		if($building_access) {
			if($User_id == 251){
				$floors_data[] = [
					'content'  => '<span class="label label-default">Building</span> <a href="#" data-exec="selectMeter" data-building-id="'.$this->id.'" data-meter-id="0" class="list_item"><strong>Demo Building</strong></a>',
					'attr'     => [ 'data-li' => 'b'.$building->id, 'data-building' => $building->id ],
					'class'    => 'item-type-floor'
				];
			}else{
				$floors_data[] = [
					'content'  => '<span class="label label-default">Building</span> <a href="#" data-exec="selectMeter" data-building-id="'.$this->id.'" data-meter-id="0" class="list_item"><strong>'.$building->info->description.'</strong></a>',
					'attr'     => [ 'data-li' => 'b'.$building->id, 'data-building' => $building->id ],
					'class'    => 'item-type-floor'
				];
			}
		}

		if ($area_access && $floors = $building->get_floors()) {
			foreach($floors as $floor) {
				$areas = $get_areas($floor);
				if($areas && count($areas)) {
					$floors_data[] = [
						'content'  => '<span class="label label-default">Block</span> <strong class="txt-color-greyDark">'.$floor->description.'</strong>',
						'attr'     => [ 'data-li' => 'f'.$floor->id, 'data-building' => $building->id, 'data-floor' => $floor->id ],
						'class'    => 'item-type-floor',
						'subitems' => $areas
					];
				}
			}

			if($floors_data && count($floors_data)) {
				$content = SmartUI::print_list($floors_data, [ 'id' => 'meter-list' ], true);
			} else {
				$content = '<p>This building has no meters.</p>';
			}

			$content = '
				<div class="padding-10">
					<div class="tree" id="devices-tree">
						'.$content.'
					</div>
				</div>
			';
		} else {
			$content = $ui->print_warning('No blocks on this building yet.', null, true);
		}

		return $content;
	}

	public function get_tenant_list_html($meter_type_filter = '', $building_structure = false, $virtual = true, $generated = false) {
		$ui = new SmartUI();
		$building = $this;
		$area_access = $building->get_area_ids_with_permission(Permission::TENANT_ENABLED);
		if(!$area_access) return;
		$area_access = '('.implode(',', $area_access).')';
		$building_access = Permission::get_building($this->id)->check(Permission::TENANT_ENABLED);

		$floors_data = [];

		$get_meters = function($filter, $show_sub) use ($building, &$get_meters, $building_structure) {
			$structure = [];
			if ($meters = $building->get_meters($filter, ['meter.*'], [], 'ORDER BY meter.meter_type, meter.description')) {
				foreach($meters as $meter) {
					$meter_filter = [ 'parent_id' => "='$meter->id'"];
					
					if(isset($filter['meter_type']) && $filter['meter_type']) $meter_filter['meter_type'] = $filter['meter_type'];

					$submeters = $show_sub ? $get_meters($meter_filter, true) : [];

					$meter_class = $meter->parent_id ? ' Sub' : '';

					$icon_class = '';
					switch($meter->meter_type) {
						case 'E':
							$icon_class = 'eticon eticon-bolt eticon-bolt-color';
							break;

						case 'G':
							$icon_class = 'eticon eticon-flame txt-color-blue';
							break;

						case 'W':
							$icon_class = 'eticon eticon-droplet txt-color-blueWater';
							break;

						case 'H':
							$icon_class = 'eticon eticon-heat txt-color-red';
							break;
					}

					if($meter->meter_direction == 'generation') $icon_class = 'eticon eticon-leaf txt-color-green';

					$desc = '';
					if($meter->parent_id && !$building_structure) {
						$m = new Meter($meter->id);
						$tn = $m->get_tenant_name();
						if($tn) {
							$desc = '<strong>'.$meter->description.'</strong> <i>('.$tn.')</i>';
						} else {
							$desc = '<strong>'.$meter->description.'</strong>';
						}
					} else {
						$desc = '<strong>'.$meter->description.'</strong>';
					}

					$structure[] = [
						'content'  => '<span class="label bg-color-greyDark"><i class="'.$icon_class.'"></i>'.$meter_class.'</span> <a href="#" data-exec="selectMeter" data-building-id="'.$this->id.'" data-meter-id="'.$meter->id.'" class="list_item" onclick="getData('.$meter->id.')" id="meterId">'.$desc.'</a>',
						'attr'     => [ 'data-li' => 'm'.$meter->id ],
						'subitems' => $submeters
					];
				}
			}
			return $structure;
		};

		$get_areas = function($floor) use ($building, $get_meters, $meter_type_filter, $building_structure, $generated, $area_access, $virtual) {
			$areas_structure = [];
			$client_id = $building->info->client_id;

			$areas = App::sql()->query(
				"SELECT
					a.id, a.description,
					IF(cu.id IS NULL,
						t.company,
						IF(COALESCE(cu.contact_name, '') <> '' AND COALESCE(cu.company_name, '') <> '', CONCAT(cu.contact_name, ', ', cu.company_name), COALESCE(NULLIF(cu.contact_name, ''), cu.company_name))
					) AS tenant_name
				FROM area AS a
				LEFT JOIN tenanted_area AS ta ON ta.area_id = a.id
				LEFT JOIN tenant AS t ON t.id = ta.tenant_id
				LEFT JOIN (
					SELECT
						c.area_id,
						MIN(c.customer_id) AS customer_id
					FROM contract AS c
					WHERE c.owner_type = 'C' AND c.owner_id = '$client_id' AND c.customer_type = 'CU' AND c.area_id IS NOT NULL AND c.status IN ('active', 'ending')
					GROUP BY c.area_id
				) AS c ON c.area_id = a.id
				LEFT JOIN customer AS cu ON cu.id = c.customer_id
				WHERE a.floor_id = '$floor->id' AND a.id IN $area_access
				ORDER BY a.display_order;
			") ?: [];
			foreach($areas as $area) {
				if($virtual) {
					$meter_filter = [ 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "='$area->id'", "'$area->id'" => "IN $area_access" ];

				} else {
					$meter_filter = [ 'meter.area_id' => "='$area->id'", 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "IN $area_access" ];
				}
				if(!$building_structure) $meter_filter['parent_id'] = 'IS NULL';
				if($meter_type_filter) {
					$meter_filter['meter_type'] = "= '$meter_type_filter'";
					$meter_filter['meter_direction'] = $generated ? "= 'generation'" : "<> 'generation'";
				}

				$device_content = $get_meters($meter_filter, !$building_structure);

				// print_r($area); exit;
				if($device_content && count($device_content)) {
					$area_html = '<span class="label label-default">Area</span> <strong>'.$area->description.'</strong>';
					if($area->tenant_name) $area_html .= ' <i>- '.$area->tenant_name.'</i>';
					$areas_structure[] = [
						'content'  => $area_html,
						'attr'     => [ 'data-li' => 'a'.$area->id, 'data-building' => $building->id, 'data-floor' => $floor->id, 'data-area' => $area->id ],
						'class'    => 'item-type-area',
						'subitems' => $device_content
					];
				}
			}

			return $areas_structure;
		};

		if($building_access) {
			if($User_id == 251){
				$floors_data[] = [
					'content'  => '<span class="label label-default">Building</span> <a href="#" data-exec="selectMeter" data-building-id="'.$this->id.'" data-meter-id="0" class="list_item"><strong>Demo Building</strong></a>',
					'attr'     => [ 'data-li' => 'b'.$building->id, 'data-building' => $building->id ],
					'class'    => 'item-type-floor'
				];}
			else{
				$floors_data[] = [
					'content'  => '<span class="label label-default">Building</span> <a href="#" data-exec="selectMeter" data-building-id="'.$this->id.'" data-meter-id="0" class="list_item"><strong>'.$building->info->description.'</strong></a>',
					'attr'     => [ 'data-li' => 'b'.$building->id, 'data-building' => $building->id ],
					'class'    => 'item-type-floor'
				];
			}
		}

		if ($area_access && $floors = $building->get_floors()) {
			foreach($floors as $floor) {
				$areas = $get_areas($floor);
				if($areas && count($areas)) {
					$floors_data[] = [
						'content'  => '<span class="label label-default">Block</span> <strong class="txt-color-greyDark">'.$floor->description.'</strong>',
						'attr'     => [ 'data-li' => 'f'.$floor->id, 'data-building' => $building->id, 'data-floor' => $floor->id ],
						'class'    => 'item-type-floor',
						'subitems' => $areas
					];
				}
			}

			if($floors_data && count($floors_data)) {
				$content = SmartUI::print_list($floors_data, [ 'id' => 'meter-list' ], true);
			} else {
				$content = '<p>This building has no meters.</p>';
			}

			$content = '
				<div class="padding-10">
					<div class="tree" id="devices-tree">
						'.$content.'
					</div>
				</div>
			';
		} else {
			$content = $ui->print_warning('No blocks on this building yet.', null, true);
		}

		return $content;
	}

	public function get_mmm_building_meters_html($meter_type = 'E', $meter_id = null) {
		$generated = $meter_type === 'EG';
		$meter_type = $meter_type[0];

		$data = [];
		$data_period = [];
		$max_readings = 0;
		$max_usage = 1;

		$period_year = 0;
		$period_month = 0;
		$last_complete_period = MeterPeriod::get_last_period_date($this->id, $meter_type, true, $generated);
		if($last_complete_period) {
			list($period_year, $period_month) = $last_complete_period;
		}

		$show_cost = Permission::get_building($this->id)->check(Permission::METERS_ENABLED);
		if($meter_type === 'H') $show_cost = false; // Heat meter cost not supported

		$add_meter_reading = function($m, $level = 0) use (&$data, &$data_period, &$max_readings, &$max_usage, $period_year, $period_month) {
			$md = [
				'id' => $m->id,
				'description' => $m->info->description,
				'meter_type' => $m->info->meter_type,
				'sub_meter' => $m->info->parent_id ? 1 : 0,
				'level' => $level,
				'add' => '',
				'reading_date' => 'Never',
				'reading_1' => '&ndash;',
				'reading_2' => '&ndash;',
				'reading_3' => '&ndash;',
				'reading_total' => '&ndash;',
				'is_automatic' => $m->is_automatic()
			];

			$r = $m->get_latest_reading();
			if($r) {
				$md['reading_date'] = $r->reading_date != null ? App::format_datetime('d/m/Y', $r->reading_date, 'Y-m-d') : 'Never';
				$md['reading_1'] = $r->reading_1 != null ? $r->reading_1 : '&ndash;';
				$md['reading_2'] = $r->reading_2 != null ? $r->reading_2 : '&ndash;';
				$md['reading_3'] = $r->reading_3 != null ? $r->reading_3 : '&ndash;';
				$md['reading_total'] = $r->reading_total != null ? $r->reading_total : ((($r->reading_1?:0) + ($r->reading_2?:0) + ($r->reading_3?:0)) ?: '&ndash;');

				if($max_readings < 3 && $md['reading_3'] != '&ndash;') $max_readings = 3;
				if($max_readings < 2 && $md['reading_2'] != '&ndash;') $max_readings = 2;
				if($max_readings < 1 && $md['reading_1'] != '&ndash;') $max_readings = 1;
			}

			$max_usage = max($max_usage, $m->get_number_of_readings());

			$data[] = (object)$md;

			if($period_year && $period_month) {
				$md = [
					'id' => $m->id,
					'description' => $m->info->description,
					'meter_type' => $m->info->meter_type,
					'sub_meter' => $m->info->parent_id ? 1 : 0,
					'level' => $level,
					'usage_1' => '&ndash;',
					'usage_2' => '&ndash;',
					'usage_3' => '&ndash;',
					'usage_total' => '&ndash;',
					'total_cost' => '&ndash;'
				];

				$p = MeterPeriod::get_period($m->id, $period_year, $period_month);
				if($p) {
					$md['usage_1'] = $p->info->usage_1 != null ? $p->info->usage_1 : '&ndash;';
					$md['usage_2'] = $p->info->usage_2 != null ? $p->info->usage_2 : '&ndash;';
					$md['usage_3'] = $p->info->usage_3 != null ? $p->info->usage_3 : '&ndash;';
					$md['usage_total'] = ((($p->info->usage_1?:0) + ($p->info->usage_2?:0) + ($p->info->usage_3?:0)) ?: '&ndash;');
					$md['total_cost'] = $p->get_total_cost() ?: '&ndash;';
				}

				$data_period[] = (object)$md;
			}
		};

		$unit = '';

		$process_meter = function($meter, $level = 0) use ($add_meter_reading, $meter_type, $generated, $meter_id, &$process_meter, &$unit) {
			// Add meter to the data
			$m = new Meter($meter->id);
			if($m->validate()) $add_meter_reading($m, $level);

			// Resolve reading unit HTML
			$unit = $m->get_reading_unit(true);

			// Only show a single level if meter is selected
			// Remove this check for full sub-tree
			if($meter_id && $level == 1) return;

			// Get all sub-meters and add to data
			$gen_condition = $generated ? "meter_direction = 'generation'" : "meter_direction <> 'generation'";
			$sub_meters = App::sql()->query("SELECT id FROM meter WHERE meter_type = '$meter_type' AND $gen_condition AND parent_id = '$m->id' ORDER BY description;") ?: [];
			foreach($sub_meters as $sub_meter) {
				$process_meter($sub_meter, $level + 1);
			}
		};

		// Get all top level meters for the building (or selected main meter)
		if($meter_id) {
			$main_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.id = '$meter_id';") ?: [];
			//$main_meters = 555;
		
		} else {
			$gen_condition = $generated ? "m.meter_direction = 'generation'" : "m.meter_direction <> 'generation'";
			$main_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.meter_type = '$meter_type' AND $gen_condition AND m.parent_id IS NULL ORDER BY m.description;") ?: [];
		

		}
		   
	
		

		foreach($main_meters as $main_meter) {
			$process_meter($main_meter);
		}

		$ui = new SmartUI();
		$content = '';
	
		$meters_table = $ui->create_datatable($data, [
			'hover'     => true,
			'bordered'  => false,
			'in_widget' => false
		]);
		$meters_table->class = 'table-padding-8';
		$meters_table->id = "meters-{$meter_type}-table";
		if($generated) $meters_table->id = "meters-EG-table";
		$meters_table
			->col('description', 'Meter')
			->col('add', '')
			->col('reading_date', [
				'title' => 'Last read',
				'attr'  => [ 'style' => 'text-align: center;' ]
			])
			->col('reading_1', [
				'title' => "Day ($unit)",
				'attr'  => [ 'style' => 'text-align: right;' ]
			])
			->col('reading_2', [
				'title' => "Night ($unit)",
				'attr'  => [ 'style' => 'text-align: right;' ]
			])
			->col('reading_3', [
				'title' => "Evening ($unit)",
				'attr'  => [ 'style' => 'text-align: right;' ]
			])
			->col('reading_total', [
				'title' => "Reading Total",
				'attr'  => [ 'style' => 'text-align: right;' ]
			]);

		$meters_table
			->cell('description', function($row, $value) {
				$desc = '';
				if($row->level) {
					$desc .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $row->level - 1);
					$desc .= '<i class="eticon eticon-arrow-up eticon-arrow-up-color"></i>&nbsp;&nbsp;&nbsp;';
				}
				$desc .= $value;
				return $desc;
			})
			->cell('add', function($row, $value) {
				return $row->is_automatic ? '' : '<a href="#" class="txt-color-green add-meter-reading" data-meter-id="'.$row->id.'"><i class="eticon eticon-plus"></i></a>';
			})
			->cell('reading_date', function($row, $value) {
				return '<div class="text-center fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_1', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_2', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_3', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_total', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			});

		$content .= '<h2>Latest meter readings</h2>';
		$hidden = ['id', 'meter_type', 'sub_meter', 'level', 'is_automatic'];
		if($max_readings < 1) $hidden[] = 'reading_1';
		if($max_readings < 2) $hidden[] = 'reading_2';
		if($max_readings < 3) $hidden[] = 'reading_3';
		$meters_table->hidden = $hidden;
		$content .= $meters_table->print_html(true);

		if($period_year && $period_month) {
			$period_table = $ui->create_datatable($data_period, [
				'hover'     => true,
				'bordered'  => false,
				'in_widget' => false
			]);
			$period_table->class = 'table-padding-8';
			$period_table->id = "meters-{$meter_type}-table";
			if($generated) $period_table->id = "meters-EG-table";
			$period_table
				->col('description', 'Meter')
				->col('usage_1', [
					'title' => "Day ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('usage_2', [
					'title' => "Night ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('usage_3', [
					'title' => "Evening ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('usage_total', [
					'title' => $generated ? "Total Generated" : "Total Used",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('total_cost', [
					'title' => 'Total Cost',
					'attr'  => [ 'style' => 'text-align: right;' ]
				]);

			$period_table
				->cell('description', function($row, $value) {
					$desc = '';
					if($row->level) {
						$desc .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $row->level - 1);
						$desc .= '<i class="eticon eticon-arrow-up eticon-arrow-up-color"></i>&nbsp;&nbsp;&nbsp;';
					}
					$desc .= $value;
					return $desc;
				})
				->cell('usage_1', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('usage_2', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('usage_3', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('usage_total', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('total_cost', function($row, $value) {
					if(is_numeric($value)) {
						return '<div class="text-right fixed-numbers">&pound;'.App::format_number($value, 2, 2).'</div>';
					} else {
						return '<div class="text-right fixed-numbers">'.$value.'</div>';
					}
				});

			$monthName = date('F', mktime(0, 0, 0, $period_month, 10));
			$used_or_gen = $generated ? 'generated' : 'used';
			$content .= "<h2>Energy $used_or_gen in $monthName $period_year period</h2>";
			$hidden = ['id', 'meter_type', 'sub_meter', 'level'];
			if($max_usage < 2) $hidden[] = 'usage_2';
			if($max_usage < 3) $hidden[] = 'usage_3';
			if(!$show_cost) $hidden[] = 'total_cost';
			$period_table->hidden = $hidden;
			$content .= $period_table->print_html(true);
		}

		return $content;
	}

	public function get_tenant_building_meters_html($meter_type = 'E', $meter_id = null) {
		$generated = $meter_type === 'EG';
		$meter_type = $meter_type[0];

		$data = [];
		$data_period = [];
		$max_readings = 0;
		$max_usage = 1;

		$period_year = 0;
		$period_month = 0;
		$last_complete_period = MeterPeriod::get_last_period_date($this->id, $meter_type, true, $generated);
		if($last_complete_period) {
			list($period_year, $period_month) = $last_complete_period;
		}

		$show_cost = Permission::get_building($this->id)->check(Permission::TENANT_ENABLED);
		if($meter_type === 'H') $show_cost = false; // Heat meter cost not supported

		$add_meter_reading = function($m, $level = 0) use (&$data, &$data_period, &$max_readings, &$max_usage, $period_year, $period_month) {
			$md = [
				'id' => $m->id,
				'description' => $m->info->description,
				'meter_type' => $m->info->meter_type,
				'sub_meter' => $m->info->parent_id ? 1 : 0,
				'level' => $level,
				'add' => '',
				'reading_date' => 'Never',
				'reading_1' => '&ndash;',
				'reading_2' => '&ndash;',
				'reading_3' => '&ndash;',
				'reading_total' => '&ndash;',
				'is_automatic' => $m->is_automatic()
			];

			$r = $m->get_latest_reading();
			if($r) {
				$md['reading_date'] = $r->reading_date != null ? App::format_datetime('d/m/Y', $r->reading_date, 'Y-m-d') : 'Never';
				$md['reading_1'] = $r->reading_1 != null ? $r->reading_1 : '&ndash;';
				$md['reading_2'] = $r->reading_2 != null ? $r->reading_2 : '&ndash;';
				$md['reading_3'] = $r->reading_3 != null ? $r->reading_3 : '&ndash;';
				$md['reading_total'] = $r->reading_total != null ? $r->reading_total : ((($r->reading_1?:0) + ($r->reading_2?:0) + ($r->reading_3?:0)) ?: '&ndash;');

				if($max_readings < 3 && $md['reading_3'] != '&ndash;') $max_readings = 3;
				if($max_readings < 2 && $md['reading_2'] != '&ndash;') $max_readings = 2;
				if($max_readings < 1 && $md['reading_1'] != '&ndash;') $max_readings = 1;
			}

			$max_usage = max($max_usage, $m->get_number_of_readings());

			$data[] = (object)$md;

			if($period_year && $period_month) {
				$md = [
					'id' => $m->id,
					'description' => $m->info->description,
					'meter_type' => $m->info->meter_type,
					'sub_meter' => $m->info->parent_id ? 1 : 0,
					'level' => $level,
					'usage_1' => '&ndash;',
					'usage_2' => '&ndash;',
					'usage_3' => '&ndash;',
					'usage_total' => '&ndash;',
					'total_cost' => '&ndash;'
				];

				$p = MeterPeriod::get_period($m->id, $period_year, $period_month);
				if($p) {
					$md['usage_1'] = $p->info->usage_1 != null ? $p->info->usage_1 : '&ndash;';
					$md['usage_2'] = $p->info->usage_2 != null ? $p->info->usage_2 : '&ndash;';
					$md['usage_3'] = $p->info->usage_3 != null ? $p->info->usage_3 : '&ndash;';
					$md['usage_total'] = ((($p->info->usage_1?:0) + ($p->info->usage_2?:0) + ($p->info->usage_3?:0)) ?: '&ndash;');
					$md['total_cost'] = $p->get_total_cost() ?: '&ndash;';
				}

				$data_period[] = (object)$md;
			}
		};

		$unit = '';

		$process_meter = function($meter, $level = 0) use ($add_meter_reading, $meter_type, $generated, $meter_id, &$process_meter, &$unit) {
			// Add meter to the data
			$m = new Meter($meter->id);
			if($m->validate()) $add_meter_reading($m, $level);

			// Resolve reading unit HTML
			$unit = $m->get_reading_unit(true);

			// Only show a single level if meter is selected
			// Remove this check for full sub-tree
			if($meter_id && $level == 1) return;

			// Get all sub-meters and add to data
			$gen_condition = $generated ? "meter_direction = 'generation'" : "meter_direction <> 'generation'";
			$sub_meters = App::sql()->query("SELECT id FROM meter WHERE meter_type = '$meter_type' AND $gen_condition AND parent_id = '$m->id' ORDER BY description;") ?: [];
			foreach($sub_meters as $sub_meter) {
				$process_meter($sub_meter, $level + 1);
			}
		};

		// Get all top level meters for the building (or selected main meter)
		if($meter_id) {
			$main_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.id = '$meter_id';") ?: [];
			//$main_meters = 555;
		
		} else {
			$gen_condition = $generated ? "m.meter_direction = 'generation'" : "m.meter_direction <> 'generation'";
			$main_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.meter_type = '$meter_type' AND $gen_condition AND m.parent_id IS NULL ORDER BY m.description;") ?: [];
		

		}
		   
	
		

		foreach($main_meters as $main_meter) {
			$process_meter($main_meter);
		}

		$ui = new SmartUI();
		$content = '';
	
		$meters_table = $ui->create_datatable($data, [
			'hover'     => true,
			'bordered'  => false,
			'in_widget' => false
		]);
		$meters_table->class = 'table-padding-8';
		$meters_table->id = "meters-{$meter_type}-table";
		if($generated) $meters_table->id = "meters-EG-table";
		$meters_table
			->col('description', 'Meter')
			->col('add', '')
			->col('reading_date', [
				'title' => 'Last read',
				'attr'  => [ 'style' => 'text-align: center;' ]
			])
			->col('reading_1', [
				'title' => "Day ($unit)",
				'attr'  => [ 'style' => 'text-align: right;' ]
			])
			->col('reading_2', [
				'title' => "Night ($unit)",
				'attr'  => [ 'style' => 'text-align: right;' ]
			])
			->col('reading_3', [
				'title' => "Evening ($unit)",
				'attr'  => [ 'style' => 'text-align: right;' ]
			])
			->col('reading_total', [
				'title' => "Reading Total",
				'attr'  => [ 'style' => 'text-align: right;' ]
			]);

		$meters_table
			->cell('description', function($row, $value) {
				$desc = '';
				if($row->level) {
					$desc .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $row->level - 1);
					$desc .= '<i class="eticon eticon-arrow-up" style="color: #ccc;"></i>&nbsp;&nbsp;&nbsp;';
				}
				$desc .= $value;
				return $desc;
			})
			->cell('add', function($row, $value) {
				return $row->is_automatic ? '' : '<a href="#" class="txt-color-green add-meter-reading" data-meter-id="'.$row->id.'"><i class="eticon eticon-plus"></i></a>';
			})
			->cell('reading_date', function($row, $value) {
				return '<div class="text-center fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_1', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_2', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_3', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			})
			->cell('reading_total', function($row, $value) {
				return '<div class="text-right fixed-numbers">'.$value.'</div>';
			});

		$content .= '<h2>Latest meter readings</h2>';
		$hidden = ['id', 'meter_type', 'sub_meter', 'level', 'is_automatic'];
		if($max_readings < 1) $hidden[] = 'reading_1';
		if($max_readings < 2) $hidden[] = 'reading_2';
		if($max_readings < 3) $hidden[] = 'reading_3';
		$meters_table->hidden = $hidden;
		$content .= $meters_table->print_html(true);

		if($period_year && $period_month) {
			$period_table = $ui->create_datatable($data_period, [
				'hover'     => true,
				'bordered'  => false,
				'in_widget' => false
			]);
			$period_table->class = 'table-padding-8';
			$period_table->id = "meters-{$meter_type}-table";
			if($generated) $period_table->id = "meters-EG-table";
			$period_table
				->col('description', 'Meter')
				->col('usage_1', [
					'title' => "Day ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('usage_2', [
					'title' => "Night ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('usage_3', [
					'title' => "Evening ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('usage_total', [
					'title' => $generated ? "Total Generated" : "Total Used",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('total_cost', [
					'title' => 'Total Cost',
					'attr'  => [ 'style' => 'text-align: right;' ]
				]);

			$period_table
				->cell('description', function($row, $value) {
					$desc = '';
					if($row->level) {
						$desc .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $row->level - 1);
						$desc .= '<i class="eticon eticon-arrow-up" style="color: #ccc;"></i>&nbsp;&nbsp;&nbsp;';
					}
					$desc .= $value;
					return $desc;
				})
				->cell('usage_1', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('usage_2', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('usage_3', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('usage_total', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('total_cost', function($row, $value) {
					if(is_numeric($value)) {
						return '<div class="text-right fixed-numbers">&pound;'.App::format_number($value, 2, 2).'</div>';
					} else {
						return '<div class="text-right fixed-numbers">'.$value.'</div>';
					}
				});

			$monthName = date('F', mktime(0, 0, 0, $period_month, 10));
			$used_or_gen = $generated ? 'generated' : 'used';
			$content .= "<h2>Energy $used_or_gen in $monthName $period_year period</h2>";
			$hidden = ['id', 'meter_type', 'sub_meter', 'level'];
			if($max_usage < 2) $hidden[] = 'usage_2';
			if($max_usage < 3) $hidden[] = 'usage_3';
			if(!$show_cost) $hidden[] = 'total_cost';
			$period_table->hidden = $hidden;
			$content .= $period_table->print_html(true);
		}

		return $content;
	}

	public function get_mmm_meter_readings_html($meter_id, $partial = false) {
		$meter = new Meter($meter_id);

		$show_cost = Permission::get_building($this->id)->check(Permission::METERS_ENABLED);
		if($meter->info->meter_type === 'H') $show_cost = false; // Heat meter cost not supported

		$is_generated = $meter->info->meter_direction === 'generation';
		$meter_type = $meter->info->meter_type;
		if($is_generated) $meter_type .= 'G';
		$color = '#4F81A0';
		switch($meter_type) {	
			case 'E':  $icon_class = 'eticon eticon-bolt eticon-bolt-color';        $color = '#bede18'; break;	
			case 'G':  $icon_class = 'eticon eticon-flame txt-color-blue';         $color = '#adb2b7'; break;	
			case 'W':  $icon_class = 'eticon eticon-droplet txt-color-blueWater';  $color = '#0097ce'; break;	
			case 'H':  $icon_class = 'eticon eticon-heat txt-color-red';           $color = '#F08080'; break;	
			case 'EG': $icon_class = 'eticon eticon-leaf txt-color-green';         $color = '#deeb74'; break;
		}

		$data = [];
		$max_readings = 0;
		$max_usage = $meter->get_number_of_readings();

		// Select all meter readings
		$readings = $meter->get_all_readings() ?: [];
		// var_dump($readings); exit;
		foreach($readings as $r) {
			$md = [
				'reading_date' => $r->reading_date != null ? App::format_datetime('d/m/Y', $r->reading_date, 'Y-m-d') : '&ndash;',
				'reading_1' => $r->reading_1 != null ? $r->reading_1 : '&ndash;',
				'reading_2' => $r->reading_2 != null ? $r->reading_2 : '&ndash;',
				'reading_3' => $r->reading_3 != null ? $r->reading_3 : '&ndash;',
				'reading_total' => $r->reading_total != null ? $r->reading_total : ((($r->reading_1?:0) + ($r->reading_2?:0) + ($r->reading_3?:0)) ?: '&ndash;'),
				'imported_since' => $r->imported_since != null ? App::format_datetime('d/m/Y', $r->imported_since, 'Y-m-d') : '&ndash;',
				'imported_rate_1' => $r->imported_rate_1 != null ? $r->imported_rate_1 : '&ndash;',
				'imported_rate_2' => $r->imported_rate_2 != null ? $r->imported_rate_2 : '&ndash;',
				'imported_rate_3' => $r->imported_rate_3 != null ? $r->imported_rate_3 : '&ndash;',
				'imported_total' => $r->imported_total != null ? $r->imported_total : ((($r->imported_rate_1?:0) + ($r->imported_rate_2?:0) + ($r->imported_rate_3?:0)) ?: '&ndash;'),
				'total_cost' => $r->cost_total_total ?: '&ndash;',
				'initial_reading' => $r->initial_reading
			];

			if($max_readings < 3 && $md['reading_3'] != '&ndash;') $max_readings = 3;
			if($max_readings < 2 && $md['reading_2'] != '&ndash;') $max_readings = 2;
			if($max_readings < 1 && $md['reading_1'] != '&ndash;') $max_readings = 1;

			$data[] = (object)$md;
		}

		$unit = $meter->get_reading_unit(true);

		$ui = new SmartUI();
		$content = '';

		$used_or_gen = $is_generated ? 'generated' : 'used';
		$cap_used_or_gen = $is_generated ? 'Generated' : 'Used';
		$content .= "<h2>Energy $used_or_gen in past 12 months</h2>";
		$xaxis = [ 'axisLabel' => 'Period', 'ticks' => [], 'min' => -0.5, 'max' => 11.5 ];
		$yaxis = [ 'axisLabel' => $unit ];
		$chart_data = [];
		$tick = 0;

		$from_year = date('Y') - 1;
		$from_month = date('m') + 0;
		$period_data = App::sql()->query("SELECT * FROM meter_period WHERE meter_id = '$meter_id' AND (year > $from_year OR (year = $from_year AND month >= $from_month)) ORDER BY year, month;");

		// var_dump($period_data); exit;

		if($period_data) {
			$content .= '<div class="chart" id="reading-chart" style="height: 250px !important;"></div>';
			foreach($period_data as $d) {
				$sum = 0;
				if(is_numeric($d->usage_1)) $sum += $d->usage_1;
				if(is_numeric($d->usage_2)) $sum += $d->usage_2;
				if(is_numeric($d->usage_3)) $sum += $d->usage_3;

				$chart_data[] = [
					'color' => $d->complete ? $color : '#e84f32',
					'data' => [[ $tick, $sum ]]
				];

				$xaxis['ticks'][] = [ $tick, date('M', mktime(0, 0, 0, $d->month, 10)) ];
				$tick++;
			}

			$content .= '<input type="hidden" id="reading-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
			$content .= '<input type="hidden" id="reading-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
			$content .= '<input type="hidden" id="reading-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
		} else {
			$content .= '<p>No periods found.</p>';
		}

		$show_partial_toggle = true;
		if($partial) {
			$amr = App::sql()->query(
				"SELECT DISTINCT YEAR(reading_day) AS year, MONTH(reading_day) AS month
				FROM automated_meter_reading_history
				WHERE meter_id = '$meter->id'
				ORDER BY year DESC, month DESC;
			", MySQL::QUERY_ASSOC, false);
		} else {
			$first_dom = date('Y-m-01');
			$amr = App::sql()->query(
				"SELECT DISTINCT YEAR(reading_day) AS year, MONTH(reading_day) AS month
				FROM automated_meter_reading_history
				WHERE meter_id = '$meter->id' AND reading_day < '$first_dom'
				ORDER BY year DESC, month DESC;
			", MySQL::QUERY_ASSOC, false);

			if(!$amr) {
				// Try to fall back to partial month, hide toggle
				$show_partial_toggle = false;
				$amr = App::sql()->query(
					"SELECT DISTINCT YEAR(reading_day) AS year, MONTH(reading_day) AS month
					FROM automated_meter_reading_history
					WHERE meter_id = '$meter->id'
					ORDER BY year DESC, month DESC;
				", MySQL::QUERY_ASSOC, false);
			}
		}

		if($amr) {
			$row = $amr[0];
			$year = $row['year'];
			$monthNum = $row['month'];
			$month = $monthNum < 10 ? ("0$monthNum") : $monthNum;
			$monthName = date('F', mktime(0, 0, 0, $month, 10));

			$date_from = "$year-$month-01";
			$date_to = strtotime('+1 month', strtotime($date_from));
			$date_to = date('Y-m-d', strtotime('-1 day', $date_to));

			$field = 'total_imported_total';
			if($meter_type === 'G') $field = 'gas_imported_m3_total';

			$daily_avg = App::sql()->query(
				"SELECT
					YEAR(reading_day) AS year,
					MONTH(reading_day) AS month,
					AVG($field) AS avg_usage
				FROM automated_meter_reading_history
				WHERE meter_id = '$meter->id' AND is_working_day = 1
				GROUP BY YEAR(reading_day), MONTH(reading_day)
				HAVING month = '$monthNum'
				ORDER BY year
				LIMIT 4;
			", MySQL::QUERY_ASSOC) ?: [];

			$result = App::sql()->query("SELECT DAY(reading_day) AS day, SUM($field) AS total_units FROM automated_meter_reading_history WHERE meter_id = '$meter->id' AND reading_day BETWEEN '$date_from' AND '$date_to' GROUP BY reading_day ORDER BY reading_day;", MySQL::QUERY_ASSOC, false);
			
			if($result) {
				$content .= '<div>';
				if($is_generated) {
					$content .= "<h2 style=\"display: inline-block;\">Daily generated in $monthName $year</h2>";
				} else {
					$content .= "<h2 style=\"display: inline-block;\">Daily usage in $monthName $year</h2>";
				}
				if($show_partial_toggle) $content .= '<a href="#" class="btn btn-default toggle-partial pull-right" style="margin-top:20px;" data-meter-id="'.$meter_id.'"><i class="txt-color-blue eticon eticon-top-consumer"></i> '.($partial ? 'Show last full month' : 'Show partial month').'</a>';
				$content .= '</div>';
				$content .= '<div class="chart" onclick=justShow("'.$meter_id.'") id="amr-chart" style="height: 250px !important;"></div>';

				$chart_data = [];

				$cnt = count($daily_avg);

				if($cnt) {
					$content .= '<div class="row" style="margin-top: 10px;">';

					if($cnt > 1) {
						$row = $daily_avg[$cnt - 2];

						$chart_data[] = [
							'color' => "${color}80",
							'lines' => [ 'show' => true, 'lineWidth' => 2 ],
							'data' => [ [-1, $row['avg_usage']], [40, $row['avg_usage']] ]
						];

						$content.= '<div class="col col-sm-'.($cnt > 1 ? '6' : '12').' text-center"><span style="display: inline-block; width: 30px; height: 3px; position: relative; top: -4px; margin-right: 5px; background: '.$color.'80;"></span> Average working day '.$monthName.' '.$row['year'].'</div>';
					}

					if($cnt > 0) {
						$row = $daily_avg[$cnt - 1];

						$chart_data[] = [
							'color' => "${color}",
							'lines' => [ 'show' => true, 'lineWidth' => 2 ],
							'data' => [ [-1, $row['avg_usage']], [40, $row['avg_usage']] ]
						];

						$content.= '<div class="col col-sm-'.($cnt > 1 ? '6' : '12').' text-center"><span style="display: inline-block; width: 30px; height: 3px; position: relative; top: -4px; margin-right: 5px; background: '.$color.';"></span> Average working day '.$monthName.' '.$row['year'].'</div>';
					}

					$content .= '</div>';
				}

				$xaxis = [ 'axisLabel' => "Days in $monthName", 'ticks' => [], 'min' => -0.5, 'max' => -0.5 + count($result) ];
				$yaxis = [ 'axisLabel' => $unit ];
				$tick = 0;
				foreach($result as $row) {
					$day_title = $row['day'] < 10 ? '0'.$row['day'] : $row['day'];
					$total_units = $row['total_units'] ?: 0;

					$chart_data[] = [
						'color' => $color,
						'data' => [[ $tick, $total_units ]]
					];

					$xaxis['ticks'][] = [ $tick, $day_title ];
					$tick++;
				}

				$content .= '<input type="hidden" id="amr-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="amr-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="amr-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
			}

			if($daily_avg) {
				$icon_class = '';
				switch($meter_type) {
					case 'E':  $icon_class = 'eticon eticon-bolt eticon-bolt-color';        $color = '#bede18'; break;	
					case 'G':  $icon_class = 'eticon eticon-flame eticon-flame-color';         $color = '#adb2b7'; break;	
					case 'W':  $icon_class = 'eticon eticon-droplet eticon-droplet-color ';  $color = '#0097ce'; break;	
					case 'H':  $icon_class = 'eticon eticon-heat eticon-heat-color';           $color = '#F08080'; break;	
					case 'EG': $icon_class = 'eticon eticon-leaf eticon-leaf-color';         $color = '#deeb74'; break;
				}

				$content .= "<h2>Average $monthName working day</h2>";
				$content .= '<div class="row">';

				$colWidth = 12 / count($daily_avg);

				$prev = null;

				foreach($daily_avg as $row) {
					$current = $row['avg_usage'];

					$change = '';
					if($prev !== null && $prev && $current) {
						$change = (($current - $prev) / $prev) * 100;

						if($is_generated) {
							if($change < 0) {
								$change = '<span class="text-danger">('.App::format_number($change, 1, 1).'%)</span>';
							} else if($change > 0) {
								$change = '<span class="text-success">(+'.App::format_number($change, 1, 1).'%)</span>';
							} else {
								$change = '<span class="text-muted">(+'.App::format_number($change, 1, 1).'%)</span>';
							}
						} else {
							if($change < 0) {
								$change = '<span class="text-success">('.App::format_number($change, 1, 1).'%)</span>';
							} else if($change > 0) {
								$change = '<span class="text-danger">(+'.App::format_number($change, 1, 1).'%)</span>';
							} else {
								$change = '<span class="text-muted">(+'.App::format_number($change, 1, 1).'%)</span>';
							}
						}
					}

					$prev = $current;

					$content .= "<div class=\"col col-sm-$colWidth\" style=\"text-align: center;\">";
						$content .= '<p class="font-md"><i class="eticon-3x '.$icon_class.'"></i></p>';
						$content .= '<div class="font-lg"><strong>'.App::format_number_sep($current, 0, 2).' '.$unit.'</strong></div>';
						$content .= "<strong>$monthName $row[year] $change</strong>";
					$content .= '</div>';
				}

				$content .= '</div>';
			}
		}

		$replacement_reading = App::sql()->query(
			"SELECT * FROM meter WHERE id ='$meter->id'", 
			MySQL::QUERY_ASSOC, false);

		$replacement_date = date("d/m/Y", strtotime($replacement_reading[0]['init_date']) );
		$replacement_time = date('H:i', strtotime($replacement_reading[0]['init_time']));
		// print_r($replacement_reading[0]['replaced_meter_reading']); exit;
		if (!empty($replacement_reading[0]['replaced_meter_reading'])){
			$content .= '<div><h2 style="display:inline-block;">Replacement meter readings</h2>';
			$content .= '<div>
			<table class="table table-striped table-hover table-padding-8">
				<tr>
				<th style="text-align: center;">Date</th>
				<th style="text-align: center;">Time (24hr)</th>
				<th style="text-align: center;">Reading</th>
				</tr>
				<tr>
					<td style="text-align: center;">'.$replacement_date.'</td>
					<td style="text-align: center;">'.$replacement_time.'</td>
					<td style="text-align: center;">'.$replacement_reading[0]['replaced_meter_reading'].'</td>
				</tr>
			</table>';
		}
		$content .= '<div><h2 style="display:inline-block;">Latest meter readings</h2>';
		/*if((!$meter->is_automatic() || Permission::get_eticom()->check(Permission::ADMIN)) && Permission::get_building($this->id)->check(Permission::METERS_ADD_READING))*/ $content .= '<a href="#" class="btn btn-default add-meter-reading pull-right" style="margin-top:20px;" data-meter-id="'.$meter_id.'"><i class="txt-color-green eticon eticon-plus"></i> Add meter reading</a>';
		if($this->info->is_demo) $content .= '<a href="#" class="btn btn-default generate-demo-data pull-right" style="margin-top:20px; margin-right:10px;" data-meter-id="'.$meter_id.'"><i class="txt-color-green eticon eticon-plus"></i> Generate demo data</a>';
		$content .= '</div>';
		if(count($data) > 0) {
			$meters_table = $ui->create_datatable($data, [
				'hover'     => true,
				'bordered'  => false,
				'in_widget' => false
			]);
			$meters_table->class = 'table-padding-8';
			$meters_table->id = "meter-readings-table";
			$meters_table
				->col('reading_date', [
					'title' => 'Reading date',
					'attr'  => [ 'style' => 'text-align: center;' ]
				])
				->col('reading_1', [
					'title' => "Day ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('reading_2', [
					'title' => "Night ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('reading_3', [
					'title' => "Evening ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('reading_total', [
					'title' => "Reading Total",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_since', [
					'title' => "$cap_used_or_gen since",
					'attr'  => [ 'style' => 'text-align: center;' ]
				])
				->col('imported_rate_1', [
					'title' => "$cap_used_or_gen Day ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_rate_2', [
					'title' => "$cap_used_or_gen Night ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_rate_3', [
					'title' => "$cap_used_or_gen Evening ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_total', [
					'title' => "Total $cap_used_or_gen ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('total_cost', [
					'title' => "Total Cost",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('initial_reading', ' ');

			$meters_table
				->cell('reading_date', function($row, $value) {
					return '<div class="text-center fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_1', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_2', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_3', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_total', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_since', function($row, $value) {
					if($row->initial_reading) {
						return '<div class="text-center"><label class="label label-danger">Initial reading</label></div>';
					} else {
						return '<div class="text-center fixed-numbers">'.$value.'</div>';
					}
				})
				->cell('imported_rate_1', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_rate_2', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_rate_3', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_total', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('total_cost', function($row, $value) {
					if($row->initial_reading) {
						return '<div class="text-right fixed-numbers">&ndash;</div>';
					} else {
						return '<div class="text-right fixed-numbers">&pound;'.App::format_number($value, 2, 2).'</div>';
					}
				});

			$hidden = ['id', 'initial_reading'];
			if($max_readings < 1) $hidden[] = 'reading_1';
			if($max_readings < 2) $hidden[] = 'reading_2';
			if($max_readings < 3) $hidden[] = 'reading_3';
			if($max_usage < 2) $hidden[] = 'imported_rate_2';
			if($max_usage < 3) $hidden[] = 'imported_rate_3';
			if(!$show_cost) $hidden[] = 'total_cost';
			$meters_table->hidden = $hidden;
			$content .= $meters_table->print_html(true);
		} else {
			$content .= '<p>No meter readings registered.</p>';
		}

		return $content;
	}

	public function get_tenant_meter_readings_html($meter_id, $partial = false) {
		$meter = new Meter($meter_id);

		$show_cost = Permission::get_building($this->id)->check(Permission::TENANT_ENABLED);
		if($meter->info->meter_type === 'H') $show_cost = false; // Heat meter cost not supported

		$is_generated = $meter->info->meter_direction === 'generation';
		$meter_type = $meter->info->meter_type;
		if($is_generated) $meter_type .= 'G';
		$color = '#4F81A0';
		switch($meter_type) {
			case 'E':  $icon_class = 'eticon eticon-bolt eticon-bolt-color';        $color = ' #bede18'; break;	
			case 'G':  $icon_class = 'eticon eticon-flame txt-color-blue';         $color = '#adb2b7'; break;	
			case 'W':  $icon_class = 'eticon eticon-droplet txt-color-blueWater';  $color = '#0097ce'; break;	
			case 'H':  $icon_class = 'eticon eticon-heat txt-color-red';           $color = '#F08080'; break;	
			case 'EG': $icon_class = 'eticon eticon-leaf txt-color-green';         $color = '#deeb74'; break;
		}

		$data = [];
		$max_readings = 0;
		$max_usage = $meter->get_number_of_readings();

		// Select all meter readings
		$readings = $meter->get_all_readings() ?: [];
		// var_dump($readings); exit;
		foreach($readings as $r) {
			$md = [
				'reading_date' => $r->reading_date != null ? App::format_datetime('d/m/Y', $r->reading_date, 'Y-m-d') : '&ndash;',
				'reading_1' => $r->reading_1 != null ? $r->reading_1 : '&ndash;',
				'reading_2' => $r->reading_2 != null ? $r->reading_2 : '&ndash;',
				'reading_3' => $r->reading_3 != null ? $r->reading_3 : '&ndash;',
				'reading_total' => $r->reading_total != null ? $r->reading_total : ((($r->reading_1?:0) + ($r->reading_2?:0) + ($r->reading_3?:0)) ?: '&ndash;'),
				'imported_since' => $r->imported_since != null ? App::format_datetime('d/m/Y', $r->imported_since, 'Y-m-d') : '&ndash;',
				'imported_rate_1' => $r->imported_rate_1 != null ? $r->imported_rate_1 : '&ndash;',
				'imported_rate_2' => $r->imported_rate_2 != null ? $r->imported_rate_2 : '&ndash;',
				'imported_rate_3' => $r->imported_rate_3 != null ? $r->imported_rate_3 : '&ndash;',
				'imported_total' => $r->imported_total != null ? $r->imported_total : ((($r->imported_rate_1?:0) + ($r->imported_rate_2?:0) + ($r->imported_rate_3?:0)) ?: '&ndash;'),
				'total_cost' => $r->cost_total_total ?: '&ndash;',
				'initial_reading' => $r->initial_reading
			];

			if($max_readings < 3 && $md['reading_3'] != '&ndash;') $max_readings = 3;
			if($max_readings < 2 && $md['reading_2'] != '&ndash;') $max_readings = 2;
			if($max_readings < 1 && $md['reading_1'] != '&ndash;') $max_readings = 1;

			$data[] = (object)$md;
		}

		$unit = $meter->get_reading_unit(true);

		$ui = new SmartUI();
		$content = '';

		$used_or_gen = $is_generated ? 'generated' : 'used';
		$cap_used_or_gen = $is_generated ? 'Generated' : 'Used';
		$content .= "<h2>Energy $used_or_gen in past 12 months</h2>";
		$xaxis = [ 'axisLabel' => 'Period', 'ticks' => [], 'min' => -0.5, 'max' => 11.5 ];
		$yaxis = [ 'axisLabel' => $unit ];
		$chart_data = [];
		$tick = 0;

		$from_year = date('Y') - 1;
		$from_month = date('m') + 0;
		$period_data = App::sql()->query("SELECT * FROM meter_period WHERE meter_id = '$meter_id' AND (year > $from_year OR (year = $from_year AND month >= $from_month)) ORDER BY year, month;");

		// var_dump($period_data); exit;

		if($period_data) {
			$content .= '<div class="chart" id="reading-chart" style="height: 250px !important;"></div>';
			foreach($period_data as $d) {
				$sum = 0;
				if(is_numeric($d->usage_1)) $sum += $d->usage_1;
				if(is_numeric($d->usage_2)) $sum += $d->usage_2;
				if(is_numeric($d->usage_3)) $sum += $d->usage_3;

				$chart_data[] = [
					'color' => $d->complete ? $color : '#e84f32',
					'data' => [[ $tick, $sum ]]
				];

				$xaxis['ticks'][] = [ $tick, date('M', mktime(0, 0, 0, $d->month, 10)) ];
				$tick++;
			}

			$content .= '<input type="hidden" id="reading-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
			$content .= '<input type="hidden" id="reading-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
			$content .= '<input type="hidden" id="reading-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
		} else {
			$content .= '<p>No periods found.</p>';
		}

		$show_partial_toggle = true;
		if($partial) {
			$amr = App::sql()->query(
				"SELECT DISTINCT YEAR(reading_day) AS year, MONTH(reading_day) AS month
				FROM automated_meter_reading_history
				WHERE meter_id = '$meter->id'
				ORDER BY year DESC, month DESC;
			", MySQL::QUERY_ASSOC, false);
		} else {
			$first_dom = date('Y-m-01');
			$amr = App::sql()->query(
				"SELECT DISTINCT YEAR(reading_day) AS year, MONTH(reading_day) AS month
				FROM automated_meter_reading_history
				WHERE meter_id = '$meter->id' AND reading_day < '$first_dom'
				ORDER BY year DESC, month DESC;
			", MySQL::QUERY_ASSOC, false);

			if(!$amr) {
				// Try to fall back to partial month, hide toggle
				$show_partial_toggle = false;
				$amr = App::sql()->query(
					"SELECT DISTINCT YEAR(reading_day) AS year, MONTH(reading_day) AS month
					FROM automated_meter_reading_history
					WHERE meter_id = '$meter->id'
					ORDER BY year DESC, month DESC;
				", MySQL::QUERY_ASSOC, false);
			}
		}

		if($amr) {
			$row = $amr[0];
			$year = $row['year'];
			$monthNum = $row['month'];
			$month = $monthNum < 10 ? ("0$monthNum") : $monthNum;
			$monthName = date('F', mktime(0, 0, 0, $month, 10));

			$date_from = "$year-$month-01";
			$date_to = strtotime('+1 month', strtotime($date_from));
			$date_to = date('Y-m-d', strtotime('-1 day', $date_to));

			$field = 'total_imported_total';
			if($meter_type === 'G') $field = 'gas_imported_m3_total';

			$daily_avg = App::sql()->query(
				"SELECT
					YEAR(reading_day) AS year,
					MONTH(reading_day) AS month,
					AVG($field) AS avg_usage
				FROM automated_meter_reading_history
				WHERE meter_id = '$meter->id' AND is_working_day = 1
				GROUP BY YEAR(reading_day), MONTH(reading_day)
				HAVING month = '$monthNum'
				ORDER BY year
				LIMIT 4;
			", MySQL::QUERY_ASSOC) ?: [];

			$result = App::sql()->query("SELECT DAY(reading_day) AS day, SUM($field) AS total_units FROM automated_meter_reading_history WHERE meter_id = '$meter->id' AND reading_day BETWEEN '$date_from' AND '$date_to' GROUP BY reading_day ORDER BY reading_day;", MySQL::QUERY_ASSOC, false);
			
			if($result) {
				$content .= '<div>';
				if($is_generated) {
					$content .= "<h2 style=\"display: inline-block;\">Daily generated in $monthName $year</h2>";
				} else {
					$content .= "<h2 style=\"display: inline-block;\">Daily usage in $monthName $year</h2>";
				}
				if($show_partial_toggle) $content .= '<a href="#" class="btn btn-default toggle-partial pull-right" style="margin-top:20px;" data-meter-id="'.$meter_id.'"><i class="txt-color-blue eticon eticon-top-consumer"></i> '.($partial ? 'Show last full month' : 'Show partial month').'</a>';
				$content .= '</div>';
				$content .= '<div class="chart" onclick=justShow("'.$meter_id.'") id="amr-chart" style="height: 250px !important;"></div>';

				$chart_data = [];

				$cnt = count($daily_avg);

				if($cnt) {
					$content .= '<div class="row" style="margin-top: 10px;">';

					if($cnt > 1) {
						$row = $daily_avg[$cnt - 2];

						$chart_data[] = [
							'color' => "${color}80",
							'lines' => [ 'show' => true, 'lineWidth' => 2 ],
							'data' => [ [-1, $row['avg_usage']], [40, $row['avg_usage']] ]
						];

						$content.= '<div class="col col-sm-'.($cnt > 1 ? '6' : '12').' text-center"><span style="display: inline-block; width: 30px; height: 3px; position: relative; top: -4px; margin-right: 5px; background: '.$color.'80;"></span> Average working day '.$monthName.' '.$row['year'].'</div>';
					}

					if($cnt > 0) {
						$row = $daily_avg[$cnt - 1];

						$chart_data[] = [
							'color' => "${color}",
							'lines' => [ 'show' => true, 'lineWidth' => 2 ],
							'data' => [ [-1, $row['avg_usage']], [40, $row['avg_usage']] ]
						];

						$content.= '<div class="col col-sm-'.($cnt > 1 ? '6' : '12').' text-center"><span style="display: inline-block; width: 30px; height: 3px; position: relative; top: -4px; margin-right: 5px; background: '.$color.';"></span> Average working day '.$monthName.' '.$row['year'].'</div>';
					}

					$content .= '</div>';
				}

				$xaxis = [ 'axisLabel' => "Days in $monthName", 'ticks' => [], 'min' => -0.5, 'max' => -0.5 + count($result) ];
				$yaxis = [ 'axisLabel' => $unit ];
				$tick = 0;
				foreach($result as $row) {
					$day_title = $row['day'] < 10 ? '0'.$row['day'] : $row['day'];
					$total_units = $row['total_units'] ?: 0;

					$chart_data[] = [
						'color' => $color,
						'data' => [[ $tick, $total_units ]]
					];

					$xaxis['ticks'][] = [ $tick, $day_title ];
					$tick++;
				}

				$content .= '<input type="hidden" id="amr-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="amr-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="amr-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
			}

			if($daily_avg) {
				$icon_class = '';
				switch($meter_type) {
					case 'E':  $icon_class = 'eticon eticon-bolt eticon-bolt-color';        $color = '#bede18'; break;	
					case 'G':  $icon_class = 'eticon eticon-flame txt-color-blue';         $color = '#adb2b7'; break;	
					case 'W':  $icon_class = 'eticon eticon-droplet txt-color-blueWater';  $color = '#0097ce'; break;	
					case 'H':  $icon_class = 'eticon eticon-heat txt-color-red';           $color = '#F08080'; break;	
					case 'EG': $icon_class = 'eticon eticon-leaf txt-color-green';         $color = '#deeb74'; break;
				}

				$content .= "<h2>Average $monthName working day</h2>";
				$content .= '<div class="row">';

				$colWidth = 12 / count($daily_avg);

				$prev = null;

				foreach($daily_avg as $row) {
					$current = $row['avg_usage'];

					$change = '';
					if($prev !== null && $prev && $current) {
						$change = (($current - $prev) / $prev) * 100;

						if($is_generated) {
							if($change < 0) {
								$change = '<span class="text-danger">('.App::format_number($change, 1, 1).'%)</span>';
							} else if($change > 0) {
								$change = '<span class="text-success">(+'.App::format_number($change, 1, 1).'%)</span>';
							} else {
								$change = '<span class="text-muted">(+'.App::format_number($change, 1, 1).'%)</span>';
							}
						} else {
							if($change < 0) {
								$change = '<span class="text-success">('.App::format_number($change, 1, 1).'%)</span>';
							} else if($change > 0) {
								$change = '<span class="text-danger">(+'.App::format_number($change, 1, 1).'%)</span>';
							} else {
								$change = '<span class="text-muted">(+'.App::format_number($change, 1, 1).'%)</span>';
							}
						}
					}

					$prev = $current;

					$content .= "<div class=\"col col-sm-$colWidth\" style=\"text-align: center;\">";
						$content .= '<p class="font-md"><i class="eticon-3x '.$icon_class.'"></i></p>';
						$content .= '<div class="font-lg"><strong>'.App::format_number_sep($current, 0, 2).' '.$unit.'</strong></div>';
						$content .= "<strong>$monthName $row[year] $change</strong>";
					$content .= '</div>';
				}

				$content .= '</div>';
			}
		}

		$content .= '<div><h2 style="display:inline-block;">Latest meter readingss</h2>';
		/*if((!$meter->is_automatic() || Permission::get_eticom()->check(Permission::ADMIN)) && Permission::get_building($this->id)->check(Permission::METERS_ADD_READING))*/ $content .= '<a href="#" class="btn btn-default add-meter-reading pull-right" style="margin-top:20px;" data-meter-id="'.$meter_id.'"><i class="txt-color-green eticon eticon-plus"></i> Add meter reading</a>';
		if($this->info->is_demo) $content .= '<a href="#" class="btn btn-default generate-demo-data pull-right" style="margin-top:20px; margin-right:10px;" data-meter-id="'.$meter_id.'"><i class="txt-color-green eticon eticon-plus"></i> Generate demo data</a>';
		$content .= '</div>';
		if(count($data) > 0) {
			$meters_table = $ui->create_datatable($data, [
				'hover'     => true,
				'bordered'  => false,
				'in_widget' => false
			]);
			$meters_table->class = 'table-padding-8';
			$meters_table->id = "meter-readings-table";
			$meters_table
				->col('reading_date', [
					'title' => 'Reading date',
					'attr'  => [ 'style' => 'text-align: center;' ]
				])
				->col('reading_1', [
					'title' => "Day ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('reading_2', [
					'title' => "Night ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('reading_3', [
					'title' => "Evening ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('reading_total', [
					'title' => "Reading Total",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_since', [
					'title' => "$cap_used_or_gen since",
					'attr'  => [ 'style' => 'text-align: center;' ]
				])
				->col('imported_rate_1', [
					'title' => "$cap_used_or_gen Day ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_rate_2', [
					'title' => "$cap_used_or_gen Night ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_rate_3', [
					'title' => "$cap_used_or_gen Evening ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('imported_total', [
					'title' => "Total $cap_used_or_gen ($unit)",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('total_cost', [
					'title' => "Total Cost",
					'attr'  => [ 'style' => 'text-align: right;' ]
				])
				->col('initial_reading', ' ');

			$meters_table
				->cell('reading_date', function($row, $value) {
					return '<div class="text-center fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_1', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_2', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_3', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('reading_total', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_since', function($row, $value) {
					if($row->initial_reading) {
						return '<div class="text-center"><label class="label label-danger">Initial reading</label></div>';
					} else {
						return '<div class="text-center fixed-numbers">'.$value.'</div>';
					}
				})
				->cell('imported_rate_1', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_rate_2', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_rate_3', function($row, $value) {
					return '<div class="text-right fixed-numbers">'.$value.'</div>';
				})
				->cell('imported_total', function($row, $value) {
					return '<div class="text-right fixed-numbers" id="cap" onclick="getValue('.$value.')">'.$value.'</div>';
				})
				->cell('total_cost', function($row, $value) {
					if($row->initial_reading) {
						return '<div class="text-right fixed-numbers">&ndash;</div>';
					} else {
						return '<div class="text-right fixed-numbers">&pound;'.App::format_number($value, 2, 2).'</div>';
					}
				});

			$hidden = ['id', 'initial_reading'];
			if($max_readings < 1) $hidden[] = 'reading_1';
			if($max_readings < 2) $hidden[] = 'reading_2';
			if($max_readings < 3) $hidden[] = 'reading_3';
			if($max_usage < 2) $hidden[] = 'imported_rate_2';
			if($max_usage < 3) $hidden[] = 'imported_rate_3';
			if(!$show_cost) $hidden[] = 'total_cost';
			$meters_table->hidden = $hidden;
			$content .= $meters_table->print_html(true);
		} else {
			$content .= '<p>No meter readings registered.</p>';
		}

		return $content;
	}

	public function get_supply_meters($meter_type, $generation = false) {
		$gen_condition = $generation ? "m.meter_direction = 'generation'" : "m.meter_direction <> 'generation'";
		$list = App::sql()->query(
			"SELECT m.id
			FROM meter AS m
			JOIN area AS a ON a.id = m.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$this->id' AND m.meter_type = '$meter_type' AND m.is_supply_meter AND $gen_condition;
		", MySQL::QUERY_ASSOC, false);

		return array_map(function($item) { return new Meter($item['id']); }, $list ?: []);
	}

	public function get_root_meters($meter_type, $generation = false) {
		$gen_condition = $generation ? "m.meter_direction = 'generation'" : "m.meter_direction <> 'generation'";
		$list = App::sql()->query(
			"SELECT m.id
			FROM meter AS m
			JOIN area AS a ON a.id = m.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$this->id' AND m.meter_type = '$meter_type' AND m.parent_id IS NULL AND $gen_condition;
		", MySQL::QUERY_ASSOC, false);

		return array_map(function($item) { return new Meter($item['id']); }, $list ?: []);
	}

	public function get_main_monitored_meters($meter_type = null, $generation = false) {
		if($meter_type === null) {
			// Return a combined list for all meter types
			$type_list = ['E','G','W','H'];
			$result = [];

			foreach($type_list as $type) {
				$result = array_merge($result, $this->get_main_monitored_meters($type));
				if($type === 'E') $result = array_merge($result, $this->get_main_monitored_meters($type, true));
			}

			return $result;
		}

		if($meter_type === 'E') {
			// Check if we have an ABB meter monitoring the main incomer
			$gen_condition = $generation ? "m.meter_direction = 'generation'" : "m.meter_direction <> 'generation'";
			$list = App::sql()->query(
				"SELECT
					DISTINCT m.id
				FROM dist_board AS db
				JOIN area AS a ON a.id = db.area_id
				JOIN floor AS f ON f.id = a.floor_id
				JOIN breaker AS br ON br.db_id = db.id AND br.way = 1
				JOIN ct ON ct.breaker_id = br.id
				JOIN abb_meter AS abb ON abb.id = ct.abb_meter_id AND abb.meter_id IS NOT NULL AND abb.meter_id <> 0
				JOIN meter AS m ON m.id = abb.meter_id AND m.meter_type = '$meter_type' AND $gen_condition
				WHERE f.building_id = '$this->id' AND db.is_virtual = 1 AND db.ways = 1;
			", MySQL::QUERY_ASSOC, false);

			$meters = array_map(function($item) { return new Meter($item['id']); }, $list ?: []);
			if(count($meters)) return $meters;
		}

		// Return supplier meters
		$meters = $this->get_supply_meters($meter_type, $generation);
		if(count($meters)) return $meters;

		// If still not found, return the meters at the root
		$meters = $this->get_root_meters($meter_type, $generation);
		return $meters;
	}

	public function get_mmm_building_summary_html() {

		$content = '';

		// Show energy used in last period

		$has_electric  = !!$this->get_meters(['floor.building_id' => "='$this->id'", 'meter_type' => "= 'E'", 'meter_direction' => "<> 'generation'" ]);
		$has_gas       = !!$this->get_meters(['floor.building_id' => "='$this->id'", 'meter_type' => "= 'G'", 'meter_direction' => "<> 'generation'" ]);
		$has_water     = !!$this->get_meters(['floor.building_id' => "='$this->id'", 'meter_type' => "= 'W'", 'meter_direction' => "<> 'generation'" ]);
		$has_heat      = !!$this->get_meters(['floor.building_id' => "='$this->id'", 'meter_type' => "= 'H'", 'meter_direction' => "<> 'generation'" ]);
		$has_generated = !!$this->get_meters(['floor.building_id' => "='$this->id'", 'meter_direction' => "= 'generation'" ]);

		$meter_types = [];
		if($has_electric)  $meter_types[] = 'E';
		if($has_gas)       $meter_types[] = 'G';
		if($has_water)     $meter_types[] = 'W';
		if($has_heat)      $meter_types[] = 'H';
		if($has_generated) $meter_types[] = 'EG';

		$col = count($meter_types);

		$charts = [];

		if($col) {
			if($has_generated) {
				$content .= '<h2>Energy used and generated</h2>';
			} else {
				$content .= '<h2>Energy used</h2>';
			}

			$col = $col == 5 ? 2 : 12 / $col;
			$content .= '<br><div class="row">';

			foreach($meter_types as $meter_type) {
				$icon_class = '';
				switch($meter_type) {
					case 'E':  $icon_class = 'eticon eticon-bolt eticon-bolt-color ';      $color = ' #bede18'; break;	
					case 'G':  $icon_class = 'eticon eticon-flame eticon-flame-color ';     $color = '#adb2b7'; break;	
					case 'W':  $icon_class = 'eticon eticon-droplet eticon-droplet-color '; $color = '#0097ce'; break;	
					case 'H':  $icon_class = 'eticon eticon-heat eticon-heat-color ';      $color = '#F08080'; break;	
					case 'EG': $icon_class = 'eticon eticon-leaf eticon-leaf-color';      $color = '#deeb74'; break;
				}

				$unit = '';

				$content .= '<div class="text-center col col-sm-'.$col.'"><p class="font-md"><i class="eticon-3x '.$icon_class.'"></i></p>';

				$is_generated = $meter_type === 'EG';
				$main_meters = $this->get_main_monitored_meters($meter_type[0], $is_generated);
				$main_meters = array_map(function($m) use (&$unit) {
					$unit = $m->get_reading_unit(true);
					return $m->id;
				}, $main_meters);
				$main_meters = implode(',', $main_meters);

				$used = $main_meters ? App::sql()->query(
					"SELECT
						SUM(COALESCE(mp.usage_1, 0) + COALESCE(mp.usage_2, 0) + COALESCE(mp.usage_3, 0)) AS used,
						mp.year, mp.month
					FROM meter_period AS mp
					JOIN meter AS m ON m.id = mp.meter_id
					WHERE mp.complete = 1 AND m.id IN ($main_meters)
					GROUP BY mp.year, mp.month
					ORDER BY mp.year DESC, mp.month DESC;
				") : null;

				if($used) {
					$content .= '<div class="font-lg"><strong>'.App::format_number_sep($used[0]->used, 0, 2).' '.$unit.'</strong></div>';

					$latest_year = $used[0]->year;
					$latest_month = $used[0]->month;
					$latest_month_name = date('F', mktime(0, 0, 0, $latest_month, 10));

					$sub_used = App::sql()->query_row(
						"SELECT SUM(COALESCE(mp.usage_1, 0) + COALESCE(mp.usage_2, 0) + COALESCE(mp.usage_3, 0)) AS used
						FROM meter_period AS mp
						JOIN meter AS m ON m.id = mp.meter_id
						WHERE mp.year = '$latest_year' AND mp.month = '$latest_month' AND m.parent_id IN ($main_meters);
					");

					$sub_used = $sub_used ? $sub_used->used : 0;
					if($sub_used) {
						$content .= '<div class="font-sm"><strong>Sub-meters: '.App::format_number_sep($sub_used, 0, 2).' '.$unit.'</strong></div>';
					}

					$content .= '<div class="font-sm">'.$latest_month_name.' '.$latest_year.'</div>';

					// Create chart for this meter type
					$xaxis = [ 'axisLabel' => '', 'ticks' => [], 'min' => -0.5, 'max' => 11.5 ];
					$yaxis = [ 'axisLabel' => $unit ];
					$chart_data = [];
					$tick = 0;

					$from = 11;
					if(count($used) - 1 < $from) $from = count($used) - 1;
					for($i = $from; $i >= 0; $i--) {
						$d = $used[$i];

						$chart_data[] = [
							'color' => $color,
							'data' => [[ $tick, $d->used ]]
						];

						$month_name = date('M', mktime(0, 0, 0, $d->month, 10));
						$xaxis['ticks'][] = [ $tick, $month_name ];
						$tick++;
					}

					$charts[] = [
						'meter_type' => $meter_type,
						'xaxis'      => $xaxis,
						'yaxis'      => $yaxis,
						'data'       => $chart_data
					];
				} else {
					$content .= 'No data';
				}
				$content .= '</div>';
			}

			$content .= '</div><br>';
		}

		if(count($charts) > 0) {
			$cnt = count($charts);
			for($i = 0; $i < $cnt; $i++) {
				$c = $charts[$i];
				if(($cnt % 2 == 0 && $i % 2 == 0) || ($cnt % 2 == 1 && ($i == 0 || $i % 2 == 1))) $content .= '<div class="row">';

				$col = ($cnt % 2 == 1 && $i == 0) ? 12 : 6;

				$content .= '
					<div class="col col-sm-'.$col.'">
						<h3 class="text-center txt-color-darken padding-10" style="background:#cecece">'.Meter::type_to_description($c['meter_type']).'</h3>
						<div class="chart" id="meter-type-chart-'.$c['meter_type'].'" style="height: 200px !important;"></div>
						<input type="hidden" id="meter-type-chart-'.$c['meter_type'].'-data" value="'.App::clean_str(json_encode($c['data'])).'">
						<input type="hidden" id="meter-type-chart-'.$c['meter_type'].'-xaxis" value="'.App::clean_str(json_encode($c['xaxis'])).'">
						<input type="hidden" id="meter-type-chart-'.$c['meter_type'].'-yaxis" value="'.App::clean_str(json_encode($c['yaxis'])).'">
					</div>
				';

				if(($cnt % 2 == 0 && $i % 2 == 1) || ($cnt % 2 == 1 && ($i == 0 || $i % 2 == 0)) || $i == $cnt - 1) $content .= '</div>';
			}
		}

		// Show tariffs for the building's main meters

		$data = [];

		$add_meter = function($m) use (&$data) {
			$md = [
				'id' => $m->id,
				'description' => $m->info->description,
				'meter_type' => $m->info->meter_type,
				'sub_meter' => $m->info->parent_id ? 1 : 0,
				'supplier_name' => '&ndash;',
				'tariff_name' => '&ndash;'
			];

			$t = $m->get_tariff_info();
			if($t) {
				$md['supplier_name'] = $t->supplier_name != null ? $t->supplier_name : '&ndash;';
				$md['tariff_name'] = $t->description != null ? $t->description : '&ndash;';
			}

			$data[] = (object)$md;
		};

		$unit = '';

		$main_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$this->id' AND m.parent_id IS NULL AND m.meter_direction <> 'generation' ORDER BY m.meter_type, m.description;") ?: [];
		foreach($main_meters as $main_meter) {
			// Add main meter to the data
			$m = new Meter($main_meter->id);
			if($m->validate()) {
				$unit = $m->get_reading_unit(true);
				$add_meter($m);
			}
		}

		$ui = new SmartUI();

		$meters_table = $ui->create_datatable($data, [
			'hover'     => true,
			'bordered'  => false,
			'in_widget' => false
		]);

		$meters_table->class = 'table-padding-8';
		$meters_table->id = "building-summary-table";
		$meters_table
			->col('description', 'Meter')
			->col('supplier_name', 'Supplier')
			->col('tariff_name', 'Tariff');

		$meters_table
			->cell('description', function($row, $value) {
				$desc = '';
				if($row->sub_meter) $desc = '&nbsp;<i class="eticon eticon-arrow-up eticon-arrow-up-color"></i>&nbsp;&nbsp;&nbsp;';	
				$icon_class = '';	
				switch($row->meter_type) {	
					case 'E':	
						$icon_class = 'eticon eticon-bolt eticon-bolt-color ';	
						break;	
					case 'G':	
						$icon_class = 'eticon eticon-flame eticon-flame-color';	
						break;	
					case 'W':	
						$icon_class = 'eticon eticon-droplet eticon-droplet-color ';	
						break;	
					case 'H':	
						$icon_class = 'eticon eticon-heat eticon-heat-color ';
						break;
				}
				$desc .= '<i class="'.$icon_class.'"></i>&nbsp;&nbsp;&nbsp;'.$value;
				return $desc;
			});

		$content .= '<h2>Tariffs</h2>';
		$hidden = ['id', 'meter_type', 'sub_meter' ];
		$meters_table->hidden = $hidden;
		$content .= $meters_table->print_html(true);

		return $content;
	}

	public function html_building_manager_agent_list() {
		$client_id = $this->info->client_id;
		$agent_list = App::sql()->query(
			"SELECT
				a.id,
				MIN(a.name) AS name,
				MIN(a.email_address) AS email_address,
				MIN(b.description) AS building_name,
				COUNT(b.id) AS building_cnt,
				MAX(IF(ab.building_id = '$this->id', 1, 0)) AS this_building
			FROM agent AS a
			LEFT JOIN agent_building AS ab ON ab.agent_id = a.id
			LEFT JOIN building AS b ON b.id = ab.building_id
			WHERE a.client_id = '$client_id'
			GROUP BY a.id
			ORDER BY this_building DESC, name
		") ?: [];

		$result = '
			<li class="list-item">
				<a href="#" class="agent" data-view="building_agents?building_id='.$this->id.'" data-title="Assigned Agents">
					<i class="eticon eticon-arrow-right green pull-right"></i>
					<div class="name">Assigned Agents</div>
					<div class="desc">&nbsp;</div>
				</a>
			</li>
		';

		foreach($agent_list as $agent) {
			$id            = $agent->id;
			$name          = $agent->name;
			$email_address = $agent->email_address;
			$this_building = $agent->this_building;
			$building_name = $this_building ? $this->info->description : $agent->building_name;
			$building_cnt  = $agent->building_cnt;

			if ($building_name) {
				$desc = $building_name;
				if ($building_cnt > 1) $desc .= ', +'.($building_cnt - 1);
			} else {
				$desc = 'Unassigned';
			}

			$icon = 'eticon-agent grey';
			$icon_title = '';
			if($this_building) {
				$icon = 'eticon-building-2 green';
				$icon_title = 'Assigned to this building';
			}

			$result .= '
				<li class="list-item">
					<a href="#" class="agent agent-'.($building_name ? '' : 'un').'assigned" data-view="building_agents_agent?agent_id='.$id.'" data-title="'.$name.'" data-id="'.$id.'">
						<i class="eticon '.$icon.' pull-right" title="'.$icon_title.'"></i>
						<div class="name">'.$name.'</div>
						<div class="desc">'.$desc.'</div>
					</a>
				</li>
			';
		}

		return $result;
	}

	public function get_area_ids_with_permission($permission) {
		$areas = Permission::list_areas([ 'with' => $permission, 'filter_level' => PermissionLevel::BUILDING, 'id' => $this->id ]);
		if($areas) $areas = array_map(function($a) { return $a->area_id; }, $areas);
		return $areas;
	}

	public static function list_with_permission($permission, $include_tenants = false) {
		$list = Permission::list_buildings([ 'with' => $permission]) ?: [];
		if($include_tenants) {
			$building_ids = [];
			foreach($list as $b) {
				$building_ids[] = $b->building_id;
			}

			$tenant_buildings = Permission::list_areas([ 'with' => $permission ], count($building_ids) > 0 ? 'building_id NOT IN ('.implode(',', $building_ids).')' : '') ?: [];
			foreach($tenant_buildings as $b) {
				if(!in_array($b->building_id, $building_ids)) {
					$record = App::sql()->query_row("SELECT * FROM building WHERE id = '$b->building_id';");;
					if($record) {
						$list[] = $record;
						$building_ids[] = $b->building_id;
					}
				}
			}
		}

		return $list;
	}

	public function get_vo() {
		$vo = App::sql()->query_row(
			"SELECT vo.id
			FROM vo_unit AS vo
			JOIN area AS a ON a.id = vo.area_id
			JOIN floor AS f ON f.id = a.floor_id
			WHERE f.building_id = '$this->id'
			LIMIT 1;
		");

		if(!$vo) return null;

		$vo = new VO($vo->id);

		return $vo->is_valid() ? $vo : null;
	}

}

