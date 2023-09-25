<?php
	$meter_id = App::get('meter_id');

	if(!isset($_GET['partial'])) {
		$partial = 1;
	} else {
		$partial = App::get('partial', 0);
		$partial = $partial ? 1 : 0;
	}

	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = 'meter-detail-widget';
	$ui_widget->color = 'greyDark';

	if(!$user) return;

	$building = $user->get_default_building(Permission::METERS_ENABLED, '', true);
	if(!$building) return;

	$area_access = $building->get_area_ids_with_permission(Permission::METERS_ENABLED);
	if($area_access) $area_access = '('.implode(',', $area_access).')';
	$building_access = Permission::get_building($building->id)->check(Permission::METERS_ENABLED);

	$meter_list = $building->get_meters(['floor.building_id' => "='$building->id'", 'COALESCE(virtual_area_id, area_id)' => "IN $area_access" ]);
	$has_meters = !!$meter_list;

	// Auto-select first meter if user has no building-wide access
	if(!$building_access && !$meter_id && $meter_list) {
		$meter_id = $meter_list[0]->id;
	}

	$content = '';
	$meter = null;
	if($meter_id) {
		// Validate meter, see if user has access
		$meter = new Meter($meter_id);
		if(!$meter->validate()) $meter = null;
		if($meter) {
			$assigned_area = $meter->info->virtual_area_id ?: $meter->info->area_id;
			if(!$assigned_area) $meter = null;
			if(!Permission::get_area($assigned_area)->check(Permission::METERS_ENABLED)) $meter = null;
		}
	}

	if($meter) {
		// Show selected meter info
		$icon = '';
		switch($meter->info->meter_type) {
			case 'E': $icon = '<i class="eticon eticon-bolt eticon-bolt-color" style="margin-left:1vw"></i>'; break;	
			case 'G': $icon = '<i class="eticon eticon-flame eticon-flame-color" style="margin-left:1vw"></i>'; break;	
			case 'W': $icon = '<i class="eticon eticon-droplet eticon-droplet-color" style="margin-left:1vw"></i>'; break;	
			case 'H': $icon = '<i class="eticon eticon-heat-color" style="margin-left:1vw"></i>'; break;
		}
		if($meter->info->meter_direction == 'generation') $icon = '<i class="eticon eticon-leaf-txt-color-green" style="margin-left:1vw"></i>';

		$title = '<p class="myWidget-title">'.$meter->info->description.'</strong></h2>'.$icon;
		$ui_widget->header('title', $title);

		$has_submeters = !!$building->get_meters(['floor.building_id' => "='$building->id'", 'meter.parent_id' => "= '$meter->id'" ]);

		$content .= '<ul id="meter-detail-tabs" class="nav nav-tabs widget-row no-flex" style="padding-top: 1px; padding-bottom: 1px;">';
			$content .= '<li class="active" data-meter-id="'.$meter->id.'"><a href="#tab-meter-summary" data-toggle="tab">Meter Readings</a></li>';
			if($has_submeters) {
				if($meter->info->meter_direction == 'generation') {
					$content .= '<li data-meter-type="E" data-meter-id="'.$meter->id.'"><a href="#tab-submeters" data-toggle="tab"><i class="eticon eticon-leaf txt-color-green" title="Generated"></i> Sub-meters</a></li>';
				} else {
					switch($meter->info->meter_type) {
						case 'E':
							$content .= '<li data-meter-type="E" data-meter-id="'.$meter->id.'"><a href="#tab-submeters" data-toggle="tab"><i class="eticon eticon-bolt eticon-bolt-color" title="Electric"></i> Sub-meters</a></li>';
							break;
						case 'G':
							$content .= '<li data-meter-type="G" data-meter-id="'.$meter->id.'"><a href="#tab-submeters" data-toggle="tab"><i class="eticon eticon-flame" title="Gas"></i> Sub-meters</a></li>';
							break;
						case 'W':
							$content .= '<li data-meter-type="W" data-meter-id="'.$meter->id.'"><a href="#tab-submeters" data-toggle="tab"><i class="eticon eticon-droplet" title="Water"></i> Sub-meters</a></li>';
							break;
						case 'H':
							$content .= '<li data-meter-type="H" data-meter-id="'.$meter->id.'"><a href="#tab-submeters" data-toggle="tab"><i class="eticon eticon-heat" title="Heat"></i> Sub-meters</a></li>';
							break;
					}
				}
			}
			$content .= '<li class="pull-right"><a href="#tab-reports" class="myText-bolder myLink-colorC" data-toggle="tab"><i class="eticon eticon-clipboard"></i> Reports</a></li>';
		$content .= '</ul>';

		$content .= '<div id="detail-tab-container" class="tab-content widget-row">';
		$content .= '
			<div class="tab-pane active" id="tab-meter-summary">
				'.$building->get_mmm_meter_readings_html($meter->id, $partial).'
			</div>
		';
		if($has_submeters) {
			$content .= '
				<div class="tab-pane" id="tab-submeters">
					'.$building->get_mmm_building_meters_html($meter->info->meter_type, $meter->id).'
				</div>
			';
		}

		$reports = [
			[
				'title' => 'Meter periods report',
				'desc'  => 'A summary of all periods for the meter.',
				'btn'   => ['<a class="btn btn-default myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=meter-periods&building_id='.$building->id.'&meter_id='.$meter->id.'" target="_blank"><i class="eticon eticon-clipboard"></i> Run Report</a>']
			],
			[
				'title' => 'Meter readings report',
				'desc'  => 'Lists all meter readings for the meter.',
				'btn'   => ['<a class="btn btn-default myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=meter-readings&building_id='.$building->id.'&meter_id='.$meter->id.'" target="_blank"><i class="eticon eticon-clipboard"></i> Run Report</a>']
			]
		];

		if($meter->has_submeters()) {
			$reports[] = [
				'title' => 'Sub-meter readings report',
				'desc'  => 'Lists all sub-meter readings.',
				'btn'   => ['<a class="btn btn-default myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=meter-submeter-readings&building_id='.$building->id.'&meter_id='.$meter->id.'" target="_blank"><i class="eticon eticon-clipboard"></i> Run Report</a>']
			];

			$reports[0]['desc'] .= ' Also includes sub-meter comparison.';
		}

		$content .= '
			<div class="tab-pane" id="tab-reports">
				<br>
				<table class="table table-striped table-hover table-padding-8">
					<thead>
						<th>Report type</th>
						<th></th>
					</thead>
					<tbody>
		';

		foreach($reports as $r) {
			$content .= '
				<tr>
					<td style="width:99%"><p class="font-lg">'.$r['title'].'</p><p>'.$r['desc'].'</p></td>
					<td style="width:1%">'.implode('', $r['btn']).'</td>
				</tr>
			';
		}

		$content .= '
					</tbody>
				</table>
		';

		if($building_access) $content .= '<p class="myText-bolder myBrand-colorD"><strong>To run reports on the whole building, please select the building item at the top of the list on the left.</strong></p>';

		$content .= '
			</div>
		</div>
		';

	} else if(!$has_meters) {
		$User_id = $user->info->id;
		
		if($User_id == 251){

			$ui_widget->header('title', '<p class="myWidget-title">Demo Building meter summary</strong></h2>');
		}
		else{$ui_widget->header('title', '<p class="myWidget-title">'.$building->info->description.' meter summary</strong></h2>');
		}// Building has no meters
		

		$content = '<p>No meters found in this building.</p>';

	} else if($building_access) {
		// Building summary

		$User_id = $user->info->id;
		
		if($User_id == 251){
		$ui_widget->header('title', '<p class="myWidget-title">Demo Building meter summary</strong></h2>');
		}else{
			$ui_widget->header('title', '<p class="myWidget-title">'.$building->info->description.' meter summary</strong></h2>');
		}

		$has_electric  = !!$building->get_meters(['floor.building_id' => "='$building->id'", 'meter_type' => "= 'E'", 'meter_direction' => "<> 'generation'" ]);
		$has_gas       = !!$building->get_meters(['floor.building_id' => "='$building->id'", 'meter_type' => "= 'G'", 'meter_direction' => "<> 'generation'" ]);
		$has_water     = !!$building->get_meters(['floor.building_id' => "='$building->id'", 'meter_type' => "= 'W'", 'meter_direction' => "<> 'generation'" ]);
		$has_heat      = !!$building->get_meters(['floor.building_id' => "='$building->id'", 'meter_type' => "= 'H'", 'meter_direction' => "<> 'generation'" ]);
		$has_generated = !!$building->get_meters(['floor.building_id' => "='$building->id'", 'meter_direction' => "= 'generation'" ]);

		$content .= '<ul id="meter-detail-tabs" class="nav nav-tabs widget-row no-flex" style="padding-top: 1px; padding-bottom: 1px;">';
			$content .= '<li class="active"><a href="#tab-building-summary" data-toggle="tab">Building Summary</a></li>';
			if($has_electric)  $content .= '<li data-meter-type="E" data-meter-id="0"><a href="#tab-building-E" data-toggle="tab"><i class="eticon eticon-bolt eticon-bolt-color" title="Electric"></i> '.Meter::type_to_description('E').'</a></li>';
			if($has_gas)       $content .= '<li data-meter-type="G" data-meter-id="0"><a href="#tab-building-G" data-toggle="tab"><i class="eticon eticon-flame eticon-flame-color" title="Gas"></i> '.Meter::type_to_description('G').'</a></li>';
			if($has_water)     $content .= '<li data-meter-type="W" data-meter-id="0"><a href="#tab-building-W" data-toggle="tab"><i class="eticon eticon-droplet eticon-droplet-color" title="Water"></i> '.Meter::type_to_description('W').'</a></li>';
			if($has_heat)      $content .= '<li data-meter-type="H" data-meter-id="0"><a href="#tab-building-H" data-toggle="tab"><i class="eticon eticon-heat-color" title="Heat"></i> '.Meter::type_to_description('H').'</a></li>';
			if($has_generated) $content .= '<li data-meter-type="EG" data-meter-id="0"><a href="#tab-building-EG" data-toggle="tab"><i class="eticon eticon-leaf eticon-leaf-color" title="Generated"></i> Generated</a></li>';
			$content .= '<li class="pull-right"><a href="#tab-reports" class="myLink-colorC" data-toggle="tab"><i class="eticon eticon-clipboard eticon-clipboard-color"></i> Reports</a></li>';
		$content .= '</ul>';

		$content .= '<div id="detail-tab-container" class="tab-content widget-row">';
		//$building_mm_list = []

		//Sql Params - File path
		$sql = App::sql();
		$this_directory = $_SERVER['DOCUMENT_ROOT'];

		//Comparison Params
		$curr_building = $building->info->id;
		$curr_config_id = file_get_contents($this_directory . "eticom/user-content/Config_history/meter_detail_id.txt");

		//Check if configurator has been updated
		$config_check = $sql->query_row(
			"SELECT id, building_id FROM configurator_history ORDER BY id DESC LIMIT 1;
		", MySQL::QUERY_ASSOC);
		//print_r($config_check['id']);exit;
		if($config_check['id'] > $curr_config_id && $config_check['building_id'] == $curr_building){
			
			$fp = fopen($this_directory . "eticom/user-content/Config_history/meter_detail_id.txt", "w");
			fwrite($fp, print_r($config_check['id'], true));
			fclose($fp);

			$building_trigger = true;
			$cached_page_load = false;

			
		}else{
			$building_trigger = false; //right section
			$cached_page_load = true;
			
		}


		//SHEZ METER Details (AINTREE)
		// $building_trigger = false;
		$User_id = USER::Current_user();
		
		if($User_id == 251){
			if($building_trigger == true)
			{
				//ALL TAB
				$output_summary_all = $building->get_mmm_building_summary_html();
				$this_directory = $_SERVER['DOCUMENT_ROOT'];
				if (!file_exists($this_directory . "eticom/user-content-demo/".$building->info->description."")) {
					mkdir($this_directory . "eticom/user-content-demo/".$building->info->description."", 0777, true);
				}
				$fp = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_ALL.txt", "w");


				fwrite($fp, print_r($output_summary_all, true));
				fclose($fp);

				//ELEC TAB
				if($has_electric)
				{
					$output_summary_elec = $building->get_mmm_building_meters_html('E');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp2 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_ELEC.txt", "w");


					fwrite($fp2, print_r($output_summary_elec, true));
					fclose($fp2);
				}

				//GAS TAB
				if($has_gas)
				{
					$output_summary_gas = $building->get_mmm_building_meters_html('G');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp3 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_GAS.txt", "w");


					fwrite($fp3, print_r($output_summary_gas, true));
					fclose($fp3);
				}

				//WATER TAB
				if($has_water)
				{
					$output_summary_water = $building->get_mmm_building_summary_html('W');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp4 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_WATER.txt", "w");


					fwrite($fp4, print_r($output_summary_water, true));
					fclose($fp4);
				}


				//HEAT TAB
				if($has_heat)
				{
					$output_summary_heat = $building->get_mmm_building_summary_html('H');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp5 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_HEAT.txt", "w");


					fwrite($fp5, print_r($output_summary_heat, true));
					fclose($fp5);
				}

				// GENERATED TAB
				if($has_generated)
				{
					$output_summary_generated = $building->get_mmm_building_summary_html('EG');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp6 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_GENERATED.txt", "w");


					fwrite($fp6, print_r($output_summary_generated, true));
					fclose($fp6);
				}

			}
		}else{
			if($building_trigger == true)
			{
				//ALL TAB
				$output_summary_all = $building->get_mmm_building_summary_html();
				$this_directory = $_SERVER['DOCUMENT_ROOT'];
				if (!file_exists($this_directory . "eticom/user-content/".$building->info->description."")) {
					mkdir($this_directory . "eticom/user-content/".$building->info->description."", 0777, true);
				}
				$fp = fopen($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_ALL.txt", "w");


				fwrite($fp, print_r($output_summary_all, true));
				fclose($fp);

				//ELEC TAB
				if($has_electric)
				{
					$output_summary_elec = $building->get_mmm_building_meters_html('E');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp2 = fopen($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_ELEC.txt", "w");


					fwrite($fp2, print_r($output_summary_elec, true));
					fclose($fp2);
				}

				//GAS TAB
				if($has_gas)
				{
					$output_summary_gas = $building->get_mmm_building_meters_html('G');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp3 = fopen($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_GAS.txt", "w");


					fwrite($fp3, print_r($output_summary_gas, true));
					fclose($fp3);
				}

				//WATER TAB
				if($has_water)
				{
					$output_summary_water = $building->get_mmm_building_summary_html('W');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp4 = fopen($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_WATER.txt", "w");


					fwrite($fp4, print_r($output_summary_water, true));
					fclose($fp4);
				}


				//HEAT TAB
				if($has_heat)
				{
					$output_summary_heat = $building->get_mmm_building_summary_html('H');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp5 = fopen($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_HEAT.txt", "w");


					fwrite($fp5, print_r($output_summary_heat, true));
					fclose($fp5);
				}

				// GENERATED TAB
				if($has_generated)
				{
					$output_summary_generated = $building->get_mmm_building_summary_html('EG');
					$this_directory = $_SERVER['DOCUMENT_ROOT'];
					$fp6 = fopen($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_GENERATED.txt", "w");


					fwrite($fp6, print_r($output_summary_generated, true));
					fclose($fp6);
				}

			}

		}

		// $cached_page_load = true;
		//DEMO PAGE CACHE
		if($User_id == 251){
			if ($cached_page_load == true)
			{
				$this_directory = $_SERVER['DOCUMENT_ROOT'];

				//ALL TAB
				$content .= '<div class="tab-pane active" id="tab-building-summary">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_ALL.txt").'	
				</div>';


				if($has_electric){
				//ELEC TAB
				$content .= '<div class="tab-pane" id="tab-building-E">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_ELEC.txt").'	
				</div>';
				}

				if($has_water){
				//WATER TAB
				$content .= '<div class="tab-pane" id="tab-building-W">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_WATER.txt").'	
				</div>';
				}

				if($has_gas){
				//GAS TAB
				$content .= '<div class="tab-pane" id="tab-building-G">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_GAS.txt").'	
					</div>';
				}

				if($has_heat){
				//HEAT TAB
				$content .= '<div class="tab-pane" id="tab-building-H">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_HEAT.txt").'	
					</div>';
				}

				if($has_generated){
				//GENERATED TAB
				$content .= '<div class="tab-pane" id="tab-building-EG">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/SUMMARY_GENERATED.txt").'	
					</div>';
				}

			}else
			{
				$content .= '
					<div class="tab-pane active" id="tab-building-summary">
							'.$building->get_mmm_building_summary_html().'
					</div>
				';
				if($has_electric) {
					$content .= '
						<div class="tab-pane" id="tab-building-E">
							'.$building->get_mmm_building_meters_html('E').'
						</div>
					';
				}
				if($has_gas) {
					$content .= '
						<div class="tab-pane" id="tab-building-G">
							'.$building->get_mmm_building_meters_html('G').'
						</div>
					';
				}
				if($has_water) {
					$content .= '
						<div class="tab-pane" id="tab-building-W">
							'.$building->get_mmm_building_meters_html('W').'
						</div>
					';
				}
				if($has_heat) {
					$content .= '
						<div class="tab-pane" id="tab-building-H">
							'.$building->get_mmm_building_meters_html('H').'
						</div>
					';
				}
				if($has_generated) {
					$content .= '
						<div class="tab-pane" id="tab-building-EG">
							'.$building->get_mmm_building_meters_html('EG').'
						</div>
					';
				}
			}
		}else{
			if ($cached_page_load == true)
			{
				$this_directory = $_SERVER['DOCUMENT_ROOT'];

				//ALL TAB
				$content .= '<div class="tab-pane active" id="tab-building-summary">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_ALL.txt").'	
				</div>';


				if($has_electric){
				//ELEC TAB
				$content .= '<div class="tab-pane" id="tab-building-E">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_ELEC.txt").'	
				</div>';
				}

				if($has_water){
				//WATER TAB
				$content .= '<div class="tab-pane" id="tab-building-W">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_WATER.txt").'	
				</div>';
				}

				if($has_gas){
				//GAS TAB
				$content .= '<div class="tab-pane" id="tab-building-G">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_GAS.txt").'	
					</div>';
				}

				if($has_heat){
				//HEAT TAB
				$content .= '<div class="tab-pane" id="tab-building-H">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_HEAT.txt").'	
					</div>';
				}

				if($has_generated){
				//GENERATED TAB
				$content .= '<div class="tab-pane" id="tab-building-EG">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/SUMMARY_GENERATED.txt").'	
					</div>';
				}

			}
			else
				{
					$content .= '
						<div class="tab-pane active" id="tab-building-summary">
								'.$building->get_mmm_building_summary_html().'
						</div>
					';
					if($has_electric) {
						$content .= '
							<div class="tab-pane" id="tab-building-E">
								'.$building->get_mmm_building_meters_html('E').'
							</div>
						';
					}
					if($has_gas) {
						$content .= '
							<div class="tab-pane" id="tab-building-G">
								'.$building->get_mmm_building_meters_html('G').'
							</div>
						';
					}
					if($has_water) {
						$content .= '
							<div class="tab-pane" id="tab-building-W">
								'.$building->get_mmm_building_meters_html('W').'
							</div>
						';
					}
					if($has_heat) {
						$content .= '
							<div class="tab-pane" id="tab-building-H">
								'.$building->get_mmm_building_meters_html('H').'
							</div>
						';
					}
					if($has_generated) {
						$content .= '
							<div class="tab-pane" id="tab-building-EG">
								'.$building->get_mmm_building_meters_html('EG').'
							</div>
						';
					}
				}


		}


		//Original CACHE
		if ($building->id != 141) {
			$reports = [
				[
					'title' => 'Building periods report',
					'desc'  => 'A summary of all utilities of the building per period.',
					'btn'   => ['<a class="btn btn-default myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-periods&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-clipboard"></i> Run Report</a>']
				],
				[
					'title' => 'Building meter readings report',
					'desc'  => 'Lists all meter readings for the building.',
					'btn'   => ['<a class="btn btn-default myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-readings&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-clipboard"></i> Run Report</a>']
				],
				[
					'title' => 'Building tariff report',
					'desc'  => 'Lists all meters and their current tariffs.',
					'btn'   => ['<a class="btn btn-default myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-tariffs&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-clipboard"></i> Run Report</a>']
				]			
			];


		}
		else{
			$reports =[];

		}

		
		// hack for Aintree && capitol life (brooke court)
		if ($building->id == 87) {
			array_push($reports,
						[
					'title' => 'Building electricity report',
					'desc'  => 'Generate building electric report.',
					'btn' => ['<form action="'.APP_URL.'/ajax/elect_report.php" method="POST" style="display:inline">
					<div class="combine-el">
						<label>Select Month:</label>
						<input name="building_id" value='.$building->id.' type="hidden">
						<select name="electReport[]" id="electReport">
							<option value="JAN">January</option>
							<option value="FEB">February</option>
							<option value="MAR">March</option>
							<option value="APR">April</option>
							<option value="MAY">May</option>
							<option value="JUN">June</option>
							<option value="JUL">July</option>
							<option value="AUG">August</option>
							<option value="SEP">September</option>
							<option value="OCT">October</option>
							<option value="NOV">November</option>
							<option value="DEC">December</option>
						</select>
						<div class="icon-merge">
						<input class="new-font-button btn btn-default myText-bolder myLink-colorC" type="submit" value="Run Report">
						<i class="eticon eticon-clipboard"></i>
						</div>
					</div>
						</form>

					'],
					],
					[
					'title' => 'Building water report',
					'desc'  => 'Generate building water report.',
					'btn' => ['<form action="'.APP_URL.'/ajax/water_report.php" method="POST" style="display:inline">
					<div class="combine-el">
						<label>Select Month:</label>
						<input name="building_id" value='.$building->id.' type="hidden">
						<select name="waterReport[]" id="waterReport">
							<option value="JAN">January</option>
							<option value="FEB">February</option>
							<option value="MAR">March</option>
							<option value="APR">April</option>
							<option value="MAY">May</option>
							<option value="JUN">June</option>
							<option value="JUL">July</option>
							<option value="AUG">August</option>
							<option value="SEP">September</option>
							<option value="OCT">October</option>
							<option value="NOV">November</option>
							<option value="DEC">December</option>
						</select>
						<div class="icon-merge">
						<input class="new-font-button btn btn-default myText-bolder myLink-colorC" type="submit" value="Run Report">
						<i class="eticon eticon-clipboard"></i>
						</div>
					</div>
						</form>

					'],
					]
			);

		}

		if ($building->id == 140 || $building->id == 141) {
			array_push($reports,
						[
					'title' => 'Building electricity report',
					'desc'  => 'Generate building monthly electric consumption report.',
					'btn' => ['<form action="'.APP_URL.'/ajax/elect_report.php" method="POST" style="display:inline">
					<div class="combine-el">
						<label>Select Month:</label>
						<input name="building_id" value='.$building->id.' type="hidden">
						<select name="electReport[]" id="electReport">
							<option value="JAN">January</option>
							<option value="FEB">February</option>
							<option value="MAR">March</option>
							<option value="APR">April</option>
							<option value="MAY">May</option>
							<option value="JUN">June</option>
							<option value="JUL">July</option>
							<option value="AUG">August</option>
							<option value="SEP">September</option>
							<option value="OCT">October</option>
							<option value="NOV">November</option>
							<option value="DEC">December</option>
						</select>
						<div class="icon-merge">
						<input class="new-font-button btn btn-default myText-bolder myLink-colorC" type="submit" value="Run Report">
						<i class="eticon eticon-clipboard"></i>
						</div>
					</div>
						</form>

					'],
					]
			);

		}

		$content .= '
			<div class="tab-pane" id="tab-reports">
				<br>
				<table class="table table-striped table-hover table-padding-8">
					<thead>
						<th>Report type</th>
						<th></th>
					</thead>
					<tbody>
		';

		foreach($reports as $r) {
			$content .= '
				<tr>
					<td style="width:99%"><p class="font-lg">'.$r['title'].'</p><p style="font-size: 10px !important;">'.$r['desc'].'</p></td>
					<td style="width:1%">'.implode('', $r['btn']).'</td>
				</tr>
			';
		}

		$content .= '
					</tbody>
				</table>
				<p style="font-size: 10px !important;">To run reports on a single meter, please select one from the list on the left.</p>
		';

		if ($building->id == 126){
			$analysis_tool = [
				['title' => 'Building Total Readings',
				'desc'  => 'Total consumption for each meter',
				'btn'   => ['<form action="'.APP_URL.'/ajax/dalestreet_readings.php" method="post">
						<div class="combine-el-month">
						<label class="cost_month">Month:</label>
						<select name="month[]" id="cost_month">
						<option value="01">January</option>
						<option value="02">Feburary</option>
						<option value="03">March</option>
						<option value="04">April</option>
						<option value="05">May</option>
						<option value="06">June</option>
						<option value="07">July</option>
						<option value="08">August</option>
						<option value="09">September</option>
						<option value="10">October</option>
						<option value="11">November</option>
						<option value="12">December</option>
						</select>
						<label class="cost_year">Year:</label>
						<select name="month[]" id="cost_month">
						<option value="2023">2023</option>
						<option value="2022">2022</option>
						</select>
						<div class="icon-merge-month">
						<input class="new-month-button btn btn-default myText-bolder myLink-colorC" type="submit" value="Run Report">
						<i class="eticon eticon-clipboard"></i>
						</div>
					</div>
					
					
				</form>']
				],
				[
					'title' => 'Comparison Tool',
					'desc'  => 'Compare and plot multiple meters on to a graph',
					'btn'   => ['<a class="btn btn-default add-half-hour-data myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_half_hour_data"><i class="eticon eticon-clipboard"></i> Run Report</a>']
				]		
			];		


		}else{
			$analysis_tool = [
				[
					'title' => 'Comparison Tool',
					'desc'  => 'Compare and plot multiple meters on to a graph',
					'btn'   => ['<a class="btn btn-default add-half-hour-data myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_half_hour_data"><i class="eticon eticon-clipboard"></i> Run Report</a>']
				],		
			];
		}
		
			$content .= '
			<div class="tab-pane" id="tab-analysis">
			<br>
			<table class="table table-striped table-hover table-padding-8">
				<thead>
					<th>Analysis tools</th>
					<th></th>
					<th></th>
					<th></th>
				</thead>
				<tbody>
		';

		foreach($analysis_tool as $tt) {
			$content .= '
				<tr>
					<td style="width:99%"><p class="font-lg">'.$tt['title'].'</p><p style="font-size: 10px !important;">'.$tt['desc'].'</p></td>
					<td>'.implode('',$tt['month']).'</td>
					<td>'.implode('',$tt['year']).'</td>
					<td style="width:1%">'.implode('', $tt['btn']).'</td>
				</tr>
			';
		}

		$monitored_meters = array_map(function($m) { return $m->id; }, $building->get_main_monitored_meters());

		if(count($monitored_meters) > 0) {
			$meter_ids = implode(', ', $monitored_meters);

			// Get all year/month combinations from AMR table (excluding this month)
			$first_dom = date('Y-m-01');
			$amr = App::sql()->query(
				"SELECT DISTINCT YEAR(reading_day) AS year, MONTH(reading_day) AS month
				FROM automated_meter_reading_history
				WHERE meter_id IN ($meter_ids) AND reading_day < '$first_dom'
				ORDER BY year DESC, month DESC;
			", MySQL::QUERY_ASSOC, false);

			if($amr) {
					$content .= '
						<table class="table table-striped table-hover table-padding-8">
						<thead>
							<th>Monthly utility summaries</th>
							<th></th>
						</thead>
								<tbody>
				';

				foreach($amr as $row) {
					$year = $row['year'];
					$month = $row['month'];
					$monthName = date('F', mktime(0, 0, 0, $month, 10));

					if($month < 10) $month = "0$month";
					$param = "$year-$month";

					$content .= '
							<tr>
								<td style="width:99%"><p class="font-lg">'.$monthName.' '.$year.'</p></td>
								<td style="width:1%">
									<a class="btn btn-default myText-bolder myBrand-colorD" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-utility-summary&param='.$param.'&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-clipboard"></i> Run Report</a>
								</td>
							</tr>
					';
				}

				$content .= '
						</tbody>
					</table>
				';
			}
		}

		$content .= '
			</div>
		';

		$content .= '</div>';
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = ' ';

	$ui_widget->print_html();




?>





<script>

	
	$('#detail-tab-container').on('click', 'a.add-meter-reading', function(e) {
		e.preventDefault();
		var meterId = $(this).data('meter-id');
		$.ajaxModal('<?= APP_URL ?>/ajax/modals/add-meter-reading.php?building_id=<?= $building->id; ?>&meter_id=' + meterId);
	});

	$('#detail-tab-container').on('click', 'a.generate-demo-data', function(e) {
		e.preventDefault();
		var meterId = $(this).data('meter-id');
		$.post('<?= APP_URL.'/ajax/post/generate_demo_readings'; ?>', { meter_id: meterId }, function(data) {
			alert('done.');
			refreshMeterList();
		});
	});

		$('#detail-tab-container').on('click', 'a.add-half-hour-data', function(e){
		e.preventDefault();
		$.ajaxModal('<?= APP_URL ?>/ajax/modals/add-half-hour-data.php');
	});


	$('#detail-tab-container').on('click', 'a.toggle-partial', function(e) {
		e.preventDefault();
		loadWidget($('#meter-detail-widget').closest('.grid-stack-item'), {
			building_id: '<?= $building->id ?>',
			meter_id: $(this).data('meter-id'),
			partial: <?= $partial ? 0 : 1 ?>
		});
	});

	function refreshMeterList() {
		// Refresh currently active meter tab

		var meterType = $('#meter-detail-tabs > li.active').data('meter-type');
		var meterId = $('#meter-detail-tabs > li.active').data('meter-id');
		var partial = <?= $partial ?>

		// Refresh building meters tab
		if(meterType) {
			if($('#tab-building-' + meterType).length) $('#tab-building-' + meterType).load('<?= APP_URL ?>/ajax/get/get_mmm_building_meters_html?building_id=<?= $building->id ?>&meter_type=' + meterType + '&meter_id=' + meterId, function() { initChart('meter-type-chart-' + meterType); });
		}

		// Refresh submeters tab
		if(meterType && meterId) {
			if($('#tab-submeters').length) $('#tab-submeters').load('<?= APP_URL ?>/ajax/get/get_mmm_building_meters_html?building_id=<?= $building->id ?>&meter_type=' + meterType + '&meter_id=' + meterId);
		}

		// Refresh building summary
		if($('#tab-building-summary').length) $('#tab-building-summary').load('<?= APP_URL ?>/ajax/get/get_mmm_building_summary_html?building_id=<?= $building->id ?>', function() { initChart('reading-chart'); });

		// Refresh meter summary
		if(meterId) {
			if($('#tab-meter-summary').length) $('#tab-meter-summary').load('<?= APP_URL ?>/ajax/get/get_mmm_meter_readings_html?building_id=<?= $building->id ?>&meter_id=' + meterId + '&partial=' + partial, function() { initChart('reading-chart'); });
		}
	}


	function initChart(chartName) {
		var xaxis = $('#' + chartName + '-xaxis').val(),
			yaxis = $('#' + chartName + '-yaxis').val(),
			flotData = $('#' + chartName + '-data').val();

		if(xaxis && yaxis && flotData) {
			initFlot('#' + chartName, $.parseJSON(flotData), {
				xaxis: $.parseJSON(xaxis),
				yaxis: $.parseJSON(yaxis),
				series: {
					bars: { show: true }
				}
			});
		}
	}

	initChart('amr-chart');
	initChart('reading-chart');
	initChart('meter-type-chart-E');
	initChart('meter-type-chart-W');
	initChart('meter-type-chart-G');
	initChart('meter-type-chart-H');
	initChart('meter-type-chart-EG');
</script>
