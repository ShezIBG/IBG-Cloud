<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'blueLight';
	// JEANE CHANGE
	$ui_widget->header('title', '<p class="myWidget-title">Your Building</p>');

	$dates = [];

	if ($building = $user->get_default_building(Permission::WATER_ENABLED)) {
		$vpd = $dashboard->get_valid_period_days($building->id);
		$time_period = $dashboard->get_time_period($building->id, $vpd);

		foreach($vpd as $d) {
			$dates[date('d/m/Y', strtotime("-{$d} day"))] = Dashboard::TIME_PERIOD_DAY.$d;
		}

		// Add yesterday to the list of selectable dates
		$dates[date('d/m/Y', strtotime('yesterday'))] = Dashboard::TIME_PERIOD_YESTERDAY;
		// JEANE CHANGE
		$content = '
		<div class="myDropdown-wrapper2 widget-row display-flex no-flex overflow-hidden overview-building">
			<div>
				<p class="description no-margin padding-top-5">Choose building</p>
			</div>
			<div class="myControl-dropdown-container">
				<select class="select2 centered" id="default-building" style="width:100%;">';

		$list = Permission::list_buildings([ 'with' => Permission::WATER_ENABLED ]) ?: [];
		
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

		$time_period = $dashboard->get_time_period($building->id);

		$water_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id' AND m.meter_type = 'W' AND m.parent_id IS NULL;") ?: [];

		$current_usage_array = [];
		$current_date_from = $time_period->date_from;
		$current_date_to = $time_period->date_to;

		$previous_usage_array = [];
		if($time_period->period_length == 1) {
			// If period is a single day, compare usage to the same day a week before
			$previous_date_from = date('Y-m-d', strtotime("-7 day", strtotime($current_date_from)));
			$previous_date_to = date('Y-m-d', strtotime("-7 day", strtotime($current_date_to)));
		} else {
			// For longer periods, compare to previous period with the same length
			$previous_date_from = date('Y-m-d', strtotime("-{$time_period->period_length} day", strtotime($current_date_from)));
			$previous_date_to = date('Y-m-d', strtotime("-{$time_period->period_length} day", strtotime($current_date_to)));
		}

		$unit_html = '';
		foreach($water_meters as $m) {
			$meter = new Meter($m->id);
			if($meter->validate($building->id)) {
				$unit_html = $meter->get_reading_unit(true);
				$current_usage_array[] = $meter->get_hourly_usage($current_date_from, $current_date_to);
				$previous_usage_array[] = $meter->get_hourly_usage($previous_date_from, $previous_date_to);
			}
		}

		$current_usage = Meter::get_total_hourly_usage($current_usage_array);
		$previous_usage = Meter::get_total_hourly_usage($previous_usage_array);

		$current_total = 0;
		foreach($current_usage as $d) { $current_total += $d['used']; }

		$previous_total = 0;
		foreach($previous_usage as $d) { $previous_total += $d['used']; }

		$change = $previous_total != 0 ? (($current_total / $previous_total) * 100 - 100) : ($current_total == 0 ? 0 : 100);

		$content .= '</select>
			</div>
		</div>

		<div class="widget-row display-flex">
			<table class="centered">
				<tr>
					<td style="width: 33%; padding: 0 2em;">
						<svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 111.9 182.73" style="max-height: 80%"><defs><style>.cls-1{isolation:isolate;}.cls-2{mix-blend-mode:multiply;}.cls-3{fill:#aadbe7;}</style></defs><title>waterdrop</title><g class="cls-1"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><g class="cls-2"><path class="cls-3" d="M111.9,129.41c0-10-7.46-34.17-24.24-62S55.95,0,55.95,0,41,39.65,24.24,67.44,0,119.39,0,129.41s-.93,53.32,54.55,53.32h2.8C112.83,182.73,111.9,139.44,111.9,129.41ZM29.52,117.19C18.09,101.79,53.93,55.6,53.93,55.6l9.85,30.79C74.41,124.12,40.94,132.59,29.52,117.19Z"/></g></g></g></g></svg>
					</td>
					<td class="text-center" style="padding: 0 1em;">
						<div style="display:inline-block; text-align: left;">
							<p class="font-lg myBrand-colorB" style="margin-bottom:16px;"><strong>Total Water Used</strong></p>
							<p class="font-lg myBrand-colorB">
								<span class="eticon-stack text-center">
									<i class="eticon eticon-circle eticon-stack-2x txt-color-blueWater"></i>
									<i class="eticon eticon-droplet eticon-stack-1x eticon-inverse eticon-shadow"></i>
								</span>&nbsp;&nbsp;&nbsp;<strong>'.App::format_number($current_total, 0, 2).' '.$unit_html.'</strong>
							</p>
							<p class="txt-color-blue font-lg">
								<span class="eticon-stack text-center">
									<i class="eticon eticon-arrow-'.($change < 0 ? 'down' : 'up').' eticon-stack-1x" style="font-size: 1.5em"></i>
								</span>&nbsp;&nbsp;&nbsp;<strong>'.App::format_number(abs($change), 0, 0).'%</strong>
							</p>
						</div>
					</td>
				</tr>
			</table>
		</div>';

		$footers = [];
//JEANE CHANGE
		// Select the main water meter for the building
		$meter = null;
		$tariff = null;
		$meter_result = App::sql()->query_row("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id' AND m.meter_type = 'W' AND m.parent_id IS NULL LIMIT 1;");
		if($meter_result) {
			$meter = new Meter($meter_result->id);
			if(!$meter->validate($building->id)) $meter = null;
		}

		if($meter) {
			$reading = $meter->get_latest_reading();
			$reading_date = '&ndash;';
			if($reading) {
				if($reading->reading_date) $reading_date = App::format_datetime('d/m/Y', $reading->reading_date, 'Y-m-d');
				$reading = $reading->reading_1 ? floor($reading->reading_1) : 0;
			}
			$reading = $reading ?: 0;

			$reading = str_pad($reading, 6, '0', STR_PAD_LEFT);
//JEANE CHANGE
			$footer_content = '
				<div style="margin: 8px;">
					<table style="width: 100%;">
						<tr>
							<td>
								<p class="mySupplierFooter-title">'.$meter->info->description.'</p>
								<br>
								<table class="meter-reading font-lg">
									<tr>
										<td>'.
											implode('</td><td>', str_split($reading))
										.'</td>
									</tr>
								</table>
							</td>
							<td class="font-xxs" style="padding-left: 1em; font-weight: bold;">
								<p class="mySupplierFooter-cat">Date&nbsp;Read</p>
								<p class="mySupplierFooter-text">'.($reading_date).'</p>

								<p class="mySupplierFooter-cat">Meter&nbsp;Point&nbsp;Ref</p>
								<p class="mySupplierFooter-text">'.($meter->info->mpan ?: '&ndash;').'</p>
								
								<p class="mySupplierFooter-cat">Serial&nbsp;Number</p>
								<p class="mySupplierFooter-text" style="margin-bottom: 0;">'.($meter->info->serial_number ?: '&ndash;').'</p>

							</td>
						</tr>
					</table>
				</div>
			';
//JEANE CHANGE
			$footers[] = [
				'content' => $footer_content,
				'class'   => 'mySupplierFooter-wrapper'
			];

			$tariff = $meter->get_tariff_info();
		}
//JEANE CHANGE
		if($tariff && isset($tariff->supplier_name)) {
			$footers[] = [
				'content' => '<strong class="myWidget-colorFooter">My Supplier</strong> <span class="myWidget-colorFooter pull-right"><strong>'.($tariff->supplier_name ?: '').'</strong></span>',
				'class'   => 'mySupplierFooter-wrapper'
			];
		} else {
			// Add just a thin line to the bottom of the widget if no tariff is set
			$footers[] = [
				'content' => ' ',
				'class'   => 'mySupplierFooter-wrapper'
			];
		}

		$ui_widget->footers = $footers;
	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
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