<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'greyDark';
	$ui_widget->header('title', '<p class="myWidget-title">Multi Meter Manager</p><i class="eticon eticon-meter eticon-meter-color eticon-shadow"></i>');

	$building = $user->get_default_building(Permission::METERS_ENABLED, '', true);
	if($building) {
		$area_access = $building->get_area_ids_with_permission(Permission::METERS_ENABLED);
		if($area_access) $area_access = '('.implode(',', $area_access).')';
		$building_access = Permission::get_building($building->id)->check(Permission::METERS_ENABLED);
	}
	// JEANE CHANGE
	if ($building && $area_access) {
		$content = '
			<div class="myDropdown-wrapper2 widget-row display-flex no-flex overflow-hidden overview-building">
				<div>
					<p class="description no-margin"><strong>Choose building</strong></p>
				</div>
				<div class="select2-color-greyDark">
					<select class="select2 centered" id="default-building" style="width:100%;">
		';

		$list = Building::list_with_permission(Permission::METERS_ENABLED, true) ?: [];
		//print_r($list); exit;
		$User_id = $user->info->id;
		// print_r($User_id);exit;
		if($User_id == 251){
			if($list) {
				foreach ($list as $b) {
					$content .= '<option '.($building->id == $b->id ? 'selected' : '').' value="'.$b->id.'">Demo Building</option>';
				}
			}
		}
		elseif($list) {
			foreach ($list as $b) {
				
				$content .= '<option '.($building->id == $b->id ? 'selected' : '').' value="'.$b->id.'">'.$b->description.'</option>';
			
			}
		}
		//print_r($content); exit;
		$content .= '
					</select>
				</div>
			</div>
		';

		// Show meter tree

		$meter_filter = ['floor.building_id' => "='$building->id'", 'COALESCE(virtual_area_id, area_id)' => "IN $area_access" ];

		$has_meters    = !!$building->get_meters($meter_filter);
		$has_electric  = !!$building->get_meters(array_merge($meter_filter, [ 'meter_type' => "= 'E'", 'meter_direction' => "<> 'generation'" ]));
		$has_gas       = !!$building->get_meters(array_merge($meter_filter, [ 'meter_type' => "= 'G'", 'meter_direction' => "<> 'generation'" ]));
		$has_water     = !!$building->get_meters(array_merge($meter_filter, [ 'meter_type' => "= 'W'", 'meter_direction' => "<> 'generation'" ]));
		$has_heat      = !!$building->get_meters(array_merge($meter_filter, [ 'meter_type' => "= 'H'", 'meter_direction' => "<> 'generation'" ]));
		$has_generated = !!$building->get_meters(array_merge($meter_filter, [ 'meter_direction' => "= 'generation'" ]));
		
		$content .= '<input type="text" id="mySearchBarInput" onkeyup="mySearchBar2()" placeholder="Search for meter..."><br>';
		$content .= '<ul class="nav nav-tabs widget-row no-flex" style="padding-top: 1px; padding-bottom: 1px;">';
			$content .= '<li class="active"><a href="#tab-all" data-toggle="tab">All</a></li>';
			if($has_electric) $content .= '<li><a href="#tab-electric" data-toggle="tab"><i class="eticon eticon-bolt eticon-bolt-color" title="Electric meters"></i></a></li>';
			if($has_gas) $content .= '<li><a href="#tab-gas" data-toggle="tab"><i class="eticon eticon-flame eticon-flame-color" title="Gas meters"></i></a></li>';
			if($has_water) $content .= '<li><a href="#tab-water" data-toggle="tab"><i class="eticon eticon-droplet eticon-droplet-color" title="Water meters"></i></a></li>';
			if($has_heat) $content .= '<li><a href="#tab-heat" data-toggle="tab"><i class="eticon eticon-heat" title="Heat meters"></i></a></li>';
			if($has_generated) $content .= '<li><a href="#tab-generated" data-toggle="tab"><i class="eticon eticon-leaf eticon-leaf-color" title="Generation meters"></i></a></li>';
			if($has_meters && $building_access) {
				$content .= '<li><a href="#tab-structure" data-toggle="tab"><i class="eticon eticon-area eticon-area-color" title="Show building structure"></i></a></li>';
				$content .= '<li><a href="#tab-connections" data-toggle="tab"><i class="eticon eticon-meter eticon-meter-color" title="Show meter connections"></i></a></li>';
			}
		$content .= '</ul>';

		$content .= '<div id="meter-list" class="tab-content widget-row">';
		//print_r($building); exit;
		

		//Sql Params - File path
		$sql = App::sql();
		$this_directory = $_SERVER['DOCUMENT_ROOT'];
				
		// ------------------Get User role Tenant fix ---------------------
		
		
		$user_role_id_result = App::sql()->query("SELECT user_role_id FROM user_role_assignment WHERE user_id = '$User_id';", MySQL::QUERY_ASSOC, false);
		$user_role_id = $user_role_id_result[0]['user_role_id'];

		$user_role_result = App::sql()->query("SELECT description FROM user_role WHERE id = '$user_role_id';", MySQL::QUERY_ASSOC, false);
		// print_r($user_role[0]['description']);exit;

		$user_role = $user_role_result[0]['description'];
	
		//Comparison Params
		$curr_building = $building->info->id;
		$curr_config_id = file_get_contents($this_directory . "eticom/user-content/Config_history/id.txt");
		
		//Check if configurator has been updated
		$config_check = $sql->query_row(
			"SELECT id, building_id FROM configurator_history ORDER BY id DESC LIMIT 1;
		", MySQL::QUERY_ASSOC);
		//print_r($config_check['id']);exit;
		if($config_check['id'] > $curr_config_id && $config_check['building_id'] == $curr_building){
			
			$fp = fopen($this_directory . "eticom/user-content/Config_history/id.txt", "w");
			fwrite($fp, print_r($config_check['id'], true));
			fclose($fp);

			$update_buildings = true;
			$cached_page = false;

			
		}else{
			// print_r($user->info->id);exit;
			$update_buildings = false; //left side
			$cached_page = true;
			
		}

		// Added to ignore cache for tenant meter (typically one meter)
		if ($user_role == "Tenant" || $user_role == "tenant" || $user_role == "tenant MM" || $user_role == "Tenant MM"){
			$update_buildings = false; //left side
			$cached_page = false;


		}
		
		//SHEZ GENERATE LATEST  CACHE DATA 
		$User_id = USER::Current_user();
		if($User_id == 251){
			if($update_buildings == true)
			{
				//ALL TAB
				$outputall = $building->get_mmm_meter_list_html('', true, true);
				$this_directory = $_SERVER['DOCUMENT_ROOT'];
				if (!file_exists($this_directory . "eticom/user-content-demo/".$building->info->description."")) {
					mkdir($this_directory . "eticom/user-content-demo/".$building->info->description."", 0777, true);
				}
				$fp = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_TAB.txt", "w");
				
				
				fwrite($fp, print_r($outputall, true));
				fclose($fp);
	
				//ELEC TAB
				if($has_electric){
					$outputelec = $building->get_mmm_meter_list_html('E', true, true);
					$fp2 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_ELEC.txt", "w");
					
					
					fwrite($fp2, print_r($outputelec, true));
					fclose($fp2);
				}
				//GAS TAB
				if($has_gas){
					$outputgas = $building->get_mmm_meter_list_html('G', true, true);
					$fp3 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_GAS.txt", "w");
					
					
					fwrite($fp3, print_r($outputgas, true));
					fclose($fp3);
				}
	
				//WATER TAB
				if($has_water){
					$outputwater = $building->get_mmm_meter_list_html('W', true, true);
					$fp4 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_WATER.txt", "w");
					
					
					fwrite($fp4, print_r($outputwater, true));
					fclose($fp4);
				}
	
				if($has_heat){
					$outputheat = $building->get_mmm_meter_list_html('H', true, true);
					$fp5 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_HEAT.txt", "w");
					
					
					fwrite($fp5, print_r($outputheat, true));
					fclose($fp5);
				}
	
				if($has_generated){
	
					$outputgenerated = $building->get_mmm_meter_list_html('E', true, true, true);
					$fp6 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_GENERATED.txt", "w");
					
					
					fwrite($fp6, print_r($outputgenerated, true));
					fclose($fp6);
	
				}
				if($has_meters && $building_access){
					
					$output_meters_access = $building->get_mmm_meter_list_html('', true, false);
					$fp7 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_METER_ACCESS.txt", "w");
					fwrite($fp7, print_r($output_meters_access, true));
					//connections
					$output_meter_connections = $building->get_mmm_meter_list_html('', false, false);
					$fp8 = fopen($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_METER_connections.txt", "w");
					fwrite($fp8, print_r($output_meter_connections, true));
	
					fclose($fp7);
					fclose($fp8);
	
	
				}
	
			}	

		}else{
			if($update_buildings == true)
			{
				//ALL TAB
				$outputall = $building->get_mmm_meter_list_html('', true, true);
				$this_directory = $_SERVER['DOCUMENT_ROOT'];
				if (!file_exists($this_directory . "eticom/user-content/".$building->info->description."")) {
					mkdir($this_directory . "eticom/user-content/".$building->info->description."", 0777, true);
				}
				$fp = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_TAB.txt", "w");
				
				
				fwrite($fp, print_r($outputall, true));
				fclose($fp);

				//ELEC TAB
				if($has_electric){
					$outputelec = $building->get_mmm_meter_list_html('E', true, true);
					$fp2 = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_ELEC.txt", "w");
					
					
					fwrite($fp2, print_r($outputelec, true));
					fclose($fp2);
				}
				//GAS TAB
				if($has_gas){
					$outputgas = $building->get_mmm_meter_list_html('G', true, true);
					$fp3 = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_GAS.txt", "w");
					
					
					fwrite($fp3, print_r($outputgas, true));
					fclose($fp3);
				}

				//WATER TAB
				if($has_water){
					$outputwater = $building->get_mmm_meter_list_html('W', true, true);
					$fp4 = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_WATER.txt", "w");
					
					
					fwrite($fp4, print_r($outputwater, true));
					fclose($fp4);
				}

				if($has_heat){
					$outputheat = $building->get_mmm_meter_list_html('H', true, true);
					$fp5 = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_HEAT.txt", "w");
					
					
					fwrite($fp5, print_r($outputheat, true));
					fclose($fp5);
				}

				if($has_generated){

					$outputgenerated = $building->get_mmm_meter_list_html('E', true, true, true);
					$fp6 = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_GENERATED.txt", "w");
					
					
					fwrite($fp6, print_r($outputgenerated, true));
					fclose($fp6);

				}
				if($has_meters && $building_access){
					
					$output_meters_access = $building->get_mmm_meter_list_html('', true, false);
					$fp7 = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_METER_ACCESS.txt", "w");
					fwrite($fp7, print_r($output_meters_access, true));
					//connections
					$output_meter_connections = $building->get_mmm_meter_list_html('', false, false);
					$fp8 = fopen($this_directory . "eticom/user-content/".$building->info->description."/ALL_METER_connections.txt", "w");
					fwrite($fp8, print_r($output_meter_connections, true));

					fclose($fp7);
					fclose($fp8);


				}

			}	

		}
		
		
		// AINTREE PAGE CACHE REQUEST(SHEZ)
	
		if($User_id == 251){
				if ($cached_page == true) 
			{
				$this_directory = $_SERVER['DOCUMENT_ROOT'];

				//ALL TAB
				$content .= '<div class="tab-pane active" id="tab-all">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_TAB.txt").'	
				</div>';
				
				
				//ELEC TAB
				if($has_electric){
					$content .= '<div class="tab-pane" id="tab-electric">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_ELEC.txt").'
					</div>';
				}
				
				//Water
				if($has_water){
					$content .= '<div class="tab-pane" id="tab-water">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_WATER.txt").'	 		
					</div>';
				}
				
				//Heat
				if($has_heat){
					$content .= '<div class="tab-pane" id="tab-heat">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_HEAT.txt").'	 		
					</div>';
				}

				//GAS
				if($has_gas){
					$content .= '<div class="tab-pane" id="tab-gas">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_GAS.txt").'	 		
					</div>';
				}

				//GENERATED
				if($has_generated){
					$content .= '<div class="tab-pane" id="tab-generated">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_GENERATED.txt").'	 		
					</div>';
				}

				//ACCESS Connections
				if($has_meters && $building_access){
					//ACCESS
					$content .= '<div class="tab-pane" id="tab-structure">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_METER_ACCESS.txt").' 		
					</div>';
					//Connections
					$content .= '<div class="tab-pane" id="tab-connections">'.file_get_contents($this_directory . "eticom/user-content-demo/".$building->info->description."/ALL_METER_connections.txt").'		
					</div>';
				}
			}
			else{
			$content .= '
				<div class="tab-pane active" id="tab-all">
					'.$building->get_mmm_meter_list_html('', true, true).'
				</div>
			';

			if($has_electric) {
				$content .= '
					<div class="tab-pane" id="tab-electric">
						'.$building->get_mmm_meter_list_html('E', true, true).'
					</div>
				';
			}
			if($has_gas) {
				$content .= '
					<div class="tab-pane" id="tab-gas">
						'.$building->get_mmm_meter_list_html('G', true, true).'
					</div>
				';
			}
			if($has_water) {
				$content .= '
					<div class="tab-pane" id="tab-water">
						'.$building->get_mmm_meter_list_html('W', true, true).'
					</div>
				';
			}
			if($has_heat) {
				$content .= '
					<div class="tab-pane" id="tab-heat">
						'.$building->get_mmm_meter_list_html('H', true, true).'
					</div>
				';
			}
			if($has_generated) {
				$content .= '
					<div class="tab-pane" id="tab-generated">
						'.$building->get_mmm_meter_list_html('E', true, true, true).'
					</div>
				';
			}
			if($has_meters && $building_access) {
				$content .= '
					<div class="tab-pane" id="tab-structure">
						'.$building->get_mmm_meter_list_html('', true, false).'
					</div>
				';
				$content .= '
					<div class="tab-pane" id="tab-connections">
						'.$building->get_mmm_meter_list_html('', false, false).'
					</div>
				';
			}}


		}else
		{ if($cached_page == true)
			{

				$this_directory = $_SERVER['DOCUMENT_ROOT'];

				//ALL TAB
				$content .= '<div class="tab-pane active" id="tab-all">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_TAB.txt").'	
				</div>';
				
				
				//ELEC TAB
				if($has_electric){
					$content .= '<div class="tab-pane" id="tab-electric">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_ELEC.txt").'
					</div>';
				}
				
				//Water
				if($has_water){
					$content .= '<div class="tab-pane" id="tab-water">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_WATER.txt").'	 		
					</div>';
				}
				
				//Heat
				if($has_heat){
					$content .= '<div class="tab-pane" id="tab-heat">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_HEAT.txt").'	 		
					</div>';
				}

				//GAS
				if($has_gas){
					$content .= '<div class="tab-pane" id="tab-gas">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_GAS.txt").'	 		
					</div>';
				}

				//GENERATED
				if($has_generated){
					$content .= '<div class="tab-pane" id="tab-generated">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_GENERATED.txt").'	 		
					</div>';
				}

				//ACCESS Connections
				if($has_meters && $building_access){
					//ACCESS
					$content .= '<div class="tab-pane" id="tab-structure">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_METER_ACCESS.txt").' 		
					</div>';
					//Connections
					$content .= '<div class="tab-pane" id="tab-connections">'.file_get_contents($this_directory . "eticom/user-content/".$building->info->description."/ALL_METER_connections.txt").'		
					</div>';
				}
			}
			else
				{
					$content .= '
						<div class="tab-pane active" id="tab-all">
							'.$building->get_mmm_meter_list_html('', true, true).'
						</div>
					';
			
					if($has_electric) {
						$content .= '
							<div class="tab-pane" id="tab-electric">
								'.$building->get_mmm_meter_list_html('E', true, true).'
							</div>
						';
					}
					if($has_gas) {
						$content .= '
							<div class="tab-pane" id="tab-gas">
								'.$building->get_mmm_meter_list_html('G', true, true).'
							</div>
						';
					}
					if($has_water) {
						$content .= '
							<div class="tab-pane" id="tab-water">
								'.$building->get_mmm_meter_list_html('W', true, true).'
							</div>
						';
					}
					if($has_heat) {
						$content .= '
							<div class="tab-pane" id="tab-heat">
								'.$building->get_mmm_meter_list_html('H', true, true).'
							</div>
						';
					}
					if($has_generated) {
						$content .= '
							<div class="tab-pane" id="tab-generated">
								'.$building->get_mmm_meter_list_html('E', true, true, true).'
							</div>
						';
					}
					if($has_meters && $building_access) {
						$content .= '
							<div class="tab-pane" id="tab-structure">
								'.$building->get_mmm_meter_list_html('', true, false).'
							</div>
						';
						$content .= '
							<div class="tab-pane" id="tab-connections">
								'.$building->get_mmm_meter_list_html('', false, false).'
							</div>
						';
					}
				}
		}
		

		$content .= '</div>';
	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = ' ';

	$ui_widget->print_html();
?>

<script>
	$('#default-building').initSelect2().on('change', function(e) {
		var $this = $(this);
		$.post('<?= APP_URL ?>/ajax/post/set_default_building', {
			building_id: $this.val()
		}, function(data) {
			$.ajaxResult(data, checkURL);
		});
	});

	function selectMeter(el) {
		$('#meter-list li.active').removeClass('active');
		$(el).closest('li').addClass('active');
		loadWidget($('#meter-detail-widget').closest('.grid-stack-item'), {
			building_id: $(el).data('building-id'),
			meter_id: $(el).data('meter-id')
		});
	};

	//SHEZ SEARCH BAR SCRIPT ADDED 07/01/23
	function mySearchBar2() {
		// Declare variables
		var input, filter, ul, li, a, i, txtValue, tabpane;
		input = document.getElementById('mySearchBarInput');
		filter = input.value.toUpperCase();
		ul = document.getElementById("tab-all");
		li = ul.getElementsByTagName('li');
		// Loop through all list items, and hide those who don't match the search query
		for (i = 0; i < li.length; i++) {
			a = li[i].getElementsByTagName("a")[0];
			txtValue = a.textContent || a.innerText;			
			if (txtValue.toUpperCase().indexOf(filter) > -1) {
			li[i].style.display = "";
			} else {
				if(li[i].className == "item-type-floor" || li[i].className == "item-type-area"){
					li[i].style.display = "";
				
					var inside_ul = li[i].getElementsByTagName('ul');
					//console.log(inside_ul.length)
					// for (let item of inside_ul) {
					// 	console.log(item.outerHTML);
					// }
				}else{
					li[i].style.display = "none";
				}		
			}
		}
		
	};






</script>
<style>

@media (max-width: 360px){
	#left-panel{
		display:none;

	}
	#main{
		margin-left:auto;
		

	}

}

@media (max-width: 820px){
	#left-panel{
		display:none;


	}
	#main{
		margin-left:auto;
		

	}
}


</style>
