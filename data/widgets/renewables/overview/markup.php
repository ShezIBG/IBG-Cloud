<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'blueLight';
	$ui_widget->header('title', '<p class="myWidget-title">Your Building</p>');

	$dates = [];

	if ($building = $user->get_default_building(Permission::RENEWABLES_ENABLED)) {
		$vpd = $dashboard->get_valid_period_days($building->id);
		$time_period = $dashboard->get_time_period($building->id, $vpd);

		foreach($vpd as $d) {
			$dates[date('d/m/Y', strtotime("-{$d} day"))] = Dashboard::TIME_PERIOD_DAY.$d;
		}

		// Add yesterday to the list of selectable dates
		$dates[date('d/m/Y', strtotime('yesterday'))] = Dashboard::TIME_PERIOD_YESTERDAY;

		$content = '
		<div class="widget-row display-flex no-flex overflow-hidden overview-building">
			<div>
				<p class="description no-margin padding-top-5">Choose building</p>
			</div>
			<div class="myControl-dropdown-container">
				<select class="select2 centered" id="default-building" style="width:100%;">';

		$list = Permission::list_buildings([ 'with' => Permission::RENEWABLES_ENABLED ]);
		if($list) {
			foreach($list as $b) {
				$content .= '<option '.($building->id == $b->id ? 'selected' : '').' value="'.$b->id.'">'.$b->description.'</option>';
			}
		}

		$time_period = $dashboard->get_time_period($building->id);

		$current_date_from = $time_period->date_from;
		$current_date_to = $time_period->date_to;

		if($time_period->period_length == 1) {
			// If period is a single day, compare usage to the same day a week before
			$previous_date_from = date('Y-m-d', strtotime("-7 day", strtotime($current_date_from)));
			$previous_date_to = date('Y-m-d', strtotime("-7 day", strtotime($current_date_to)));
		} else {
			// For longer periods, compare to previous period with the same length
			$previous_date_from = date('Y-m-d', strtotime("-{$time_period->period_length} day", strtotime($current_date_from)));
			$previous_date_to = date('Y-m-d', strtotime("-{$time_period->period_length} day", strtotime($current_date_to)));
		}

		// Import meters
		$import_meters = $building->get_main_monitored_meters('E', false);

		$current_import_array = [];
		$previous_import_array = [];

		$unit_import_html = '';
		foreach($import_meters as $meter) {
			if($meter->validate($building->id)) {
				$unit_import_html = $meter->get_reading_unit(true);
				$current_import_array[] = $meter->get_hourly_usage($current_date_from, $current_date_to);
				$previous_import_array[] = $meter->get_hourly_usage($previous_date_from, $previous_date_to);
			}
		}

		$current_import = Meter::get_total_hourly_usage($current_import_array);
		$previous_import = Meter::get_total_hourly_usage($previous_import_array);

		$current_import_total = 0;
		foreach($current_import as $d) { $current_import_total += $d['used']; }

		$previous_import_total = 0;
		foreach($previous_import as $d) { $previous_import_total += $d['used']; }

		$change_import = $previous_import_total != 0 ? (($current_import_total / $previous_import_total) * 100 - 100) : ($current_import_total == 0 ? 0 : 100);

		// Calculate export
		$current_export_array = [];
		$previous_export_array = [];

		$unit_export_html = '';
		foreach($import_meters as $meter) {
			if($meter->validate($building->id)) {
				$unit_export_html = $meter->get_reading_unit(true);
				$current_export_array[] = $meter->get_hourly_usage($current_date_from, $current_date_to, true);
				$previous_export_array[] = $meter->get_hourly_usage($previous_date_from, $previous_date_to, true);
			}
		}

		$current_export = Meter::get_total_hourly_usage($current_export_array);
		$previous_export = Meter::get_total_hourly_usage($previous_export_array);

		$current_export_total = 0;
		foreach($current_export as $d) { $current_export_total += $d['used']; }

		$previous_export_total = 0;
		foreach($previous_export as $d) { $previous_export_total += $d['used']; }

		$change_export = $previous_export_total != 0 ? (($current_export_total / $previous_export_total) * 100 - 100) : ($current_export_total == 0 ? 0 : 100);

		// Generation meters
		$generation_meters = $building->get_main_monitored_meters('E', true);

		$current_generated_array = [];
		$previous_generated_array = [];

		$unit_generated_html = '';
		foreach($generation_meters as $meter) {
			if($meter->validate($building->id)) {
				$unit_generated_html = $meter->get_reading_unit(true);
				$current_generated_array[] = $meter->get_hourly_usage($current_date_from, $current_date_to);
				$previous_generated_array[] = $meter->get_hourly_usage($previous_date_from, $previous_date_to);
			}
		}

		$current_generated = Meter::get_total_hourly_usage($current_generated_array);
		$previous_generated = Meter::get_total_hourly_usage($previous_generated_array);

		$current_generated_total = 0;
		foreach($current_generated as $d) { $current_generated_total += $d['used']; }

		$previous_generated_total = 0;
		foreach($previous_generated as $d) { $previous_generated_total += $d['used']; }

		$change_generated = $previous_generated_total != 0 ? (($current_generated_total / $previous_generated_total) * 100 - 100) : ($current_generated_total == 0 ? 0 : 100);

		// Calculate consumption
		$current_consumed_total = $current_import_total + $current_generated_total - $current_export_total;
		$previous_consumed_total = $previous_import_total + $previous_generated_total - $previous_export_total;
		$change_consumed = $previous_consumed_total != 0 ? (($current_consumed_total / $previous_consumed_total) * 100 - 100) : ($current_consumed_total == 0 ? 0 : 100);

		$row_spacing = '1em';

		$content .= '</select>
			</div>
		</div>

		<div class="widget-row display-flex">
			<table class="centered txt-color-blueDark" style="font-size: 1.5em; font-size: 1.5em; background:transparent;">
				<tr>
					<th style="padding-left: 1em;"></th>
					<th class="text-right" style="padding-right: 1em;">kWh</th>
					<th style="padding-right: 1em;">Change</th>
				</tr>
				<tr style="border-top: '.$row_spacing.' solid white;">
					<td style="padding-left: 1em;">
						<span class="eticon-stack text-center">
							<i class="eticon eticon-circle eticon-stack-2x myBrand-Color-B"></i>
							<i class="eticon eticon-bolt eticon-bolt-color eticon-stack-1x eticon-inverse eticon-shadow"></i>
						</span>
						&nbsp;Consumed
					</td>
					<td class="text-right" style="padding-right: 1em;">
						<strong>'.App::format_number($current_consumed_total, 0, 2).'</strong>
					</td>
					<td style="padding-right: 1em;">
						<i class="eticon eticon-arrow-'.($change_consumed < 0 ? 'down' : 'up').'" style="font-size: 0.75em;"></i> '.App::format_number(abs($change_consumed), 0, 0).'%
					</td>
				</tr>
				<tr style="border-top: '.$row_spacing.' solid white;">
					<td style="padding-left: 1em;">
						<span class="eticon-stack text-center">
							<i class="eticon eticon-circle eticon-stack-2x myBrand-Color-B"></i>
							<i class="eticon eticon-bolt eticon-bolt-color eticon-stack-1x eticon-shadow eticon-inverse"></i>
						</span>
						&nbsp;Imported
					</td>
					<td class="text-right" style="padding-right: 1em;">
						<strong>'.App::format_number($current_import_total, 0, 2).'</strong>
					</td>
					<td style="padding-right: 1em;">
						<i class="eticon eticon-arrow-'.($change_import < 0 ? 'down' : 'up').'" style="font-size: 0.75em;"></i> '.App::format_number(abs($change_import), 0, 0).'%
					</td>
				</tr>
				<tr style="border-top: '.$row_spacing.' solid white;">
					<td style="padding-left: 1em;">
						<span class="eticon-stack text-center">
							<i class="eticon eticon-circle eticon-stack-2x txt-color-green"></i>
							<i class="eticon eticon-leaf eticon-leaf-color eticon-stack-1x eticon-inverse eticon-shadow"></i>
						</span>
						&nbsp;Generated
					</td>
					<td class="text-right" style="padding-right: 1em;">
						<strong>'.App::format_number($current_generated_total, 0, 2).'</strong>
					</td>
					<td style="padding-right: 1em;">
						<i class="eticon eticon-arrow-'.($change_generated < 0 ? 'down' : 'up').'" style="font-size: 0.75em;"></i> '.App::format_number(abs($change_generated), 0, 0).'%
					</td>
				</tr>
				<tr style="border-top: '.$row_spacing.' solid white;">
					<td style="padding-left: 1em;">
						<span class="eticon-stack text-center">
							<i class="eticon eticon-circle eticon-stack-2x myBrand-Color-B"></i>
							<i class="eticon eticon-bolt eticon-bolt-color eticon-stack-1x eticon-shadow eticon-inverse"></i>
						</span>
						&nbsp;Exported
					</td>
					<td class="text-right" style="padding-right: 1em;">
						<strong>'.App::format_number($current_export_total, 0, 2).'</strong>
					</td>
					<td style="padding-right: 1em;">
						<i class="eticon eticon-arrow-'.($change_export < 0 ? 'down' : 'up').'" style="font-size: 0.75em;"></i> '.App::format_number(abs($change_export), 0, 0).'%
					</td>
				</tr>
			</table>
		</div>';

		$footers = [];

		// Select the main electricity meter for the building
		$meter = null;
		$tariff = null;
		$meter_result = App::sql()->query_row("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id' AND m.meter_type = 'E' AND m.parent_id IS NULL LIMIT 1;");
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

			$footer_content = '
				<div style="margin: 8px;">
					<table style="width: 100%;">
						<tr>
							<td>
								<p class="font-md"><strong>'.$meter->info->description.'</strong></p>
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
								<p>
									Date&nbsp;Read<br>
									'.($reading_date).'
								</p>
								<p>
									Meter&nbsp;Point&nbsp;Ref<br>
									'.($meter->info->mpan ?: '&ndash;').'
								</p>
								<p style="margin-bottom: 0;">
									Serial&nbsp;Number<br>
									'.($meter->info->serial_number ?: '&ndash;').'
								</p>
							</td>
						</tr>
					</table>
				</div>
			';

			// $footers[] = [
			// 	'content' => $footer_content,
			// 	'class'   => 'bg-color-green'
			// ];

			$tariff = $meter->get_tariff_info();
		}
//MY Shez Color Change
		if($tariff && isset($tariff->supplier_name)) {
			$footers[] = [
				'content' => '<strong class="myWidget-colorFooter">My Supplier</strong> <span class="myWidget-colorFooter pull-right"><strong>'.($tariff->supplier_name ?: '').'</strong></span>',
				'class'   => 'bg-color-purple'
			];
		} else {
			// Add just a thin line to the bottom of the widget if no tariff is set
			$footers[] = [
				'content' => ' ',
				'class'   => 'bg-color-purple'
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

