<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'green';
	$ui_widget->header('title', '<p class="myWidget-title">Your Electricity Consumption</p><i class="myWidget-colorIcon eticon eticon-meter eticon-meter-color eticon-shadow"></i>');

	$content = '<div class="widget-row display-flex">';

	if ($building = $user->get_default_building(Permission::RENEWABLES_ENABLED)) {
		$vpd = $dashboard->get_valid_period_days($building->id);
		$time_period = $dashboard->get_time_period($building->id, $vpd);

		foreach($vpd as $d) {
			$dates[date('Y-m-d', strtotime("-{$d} day"))] = Dashboard::TIME_PERIOD_DAY.$d;
		}

		// Add yesterday to the list of selectable dates
		$dates[date('Y-m-d', strtotime('yesterday'))] = Dashboard::TIME_PERIOD_YESTERDAY;

		$single_day = $time_period->date_from === $time_period->date_to;
		$import_meters = $building->get_main_monitored_meters('E', false);
		$generation_meters = $building->get_main_monitored_meters('E', true);

		if($single_day) {
			// Hourly chart

			$unit_html = '';
			$usage_import_array = [];
			foreach($import_meters as $meter) {
				if($meter->validate($building->id)) {
					$usage_import_array[] = $meter->get_hourly_usage($time_period->date_from);
					$unit_html = $meter->get_reading_unit(true);
				}
			}
			$usage_export_array = [];
			foreach($import_meters as $meter) {
				if($meter->validate($building->id)) {
					$usage_export_array[] = $meter->get_hourly_usage($time_period->date_from, '', true);
				}
			}
			$usage_generated_array = [];
			foreach($generation_meters as $meter) {
				if($meter->validate($building->id)) {
					$usage_generated_array[] = $meter->get_hourly_usage($time_period->date_from);
				}
			}

			$usage_import = Meter::get_total_hourly_usage($usage_import_array);
			$usage_generated = Meter::get_total_hourly_usage($usage_generated_array);
			$usage_export = Meter::get_total_hourly_usage($usage_export_array);
			$working_hours = $building->get_working_hours_plot($time_period->date_from);

			if(isset($usage_import[$time_period->date_from])) {
				$usage_import = $usage_import[$time_period->date_from]['hours'];
				$usage_generated = $usage_generated[$time_period->date_from]['hours'];
				$usage_export = $usage_export[$time_period->date_from]['hours'];
				$content .= '<div class="chart" id="gas-use-chart"></div>';

				$xaxis = [ 'axisLabel' => 'Hours', 'ticks' => [], 'min' => -0.5, 'max' => count($usage_import) - 0.5 ];
				$yaxis = [ 'axisLabel' => $unit_html ];
				$chart_data = [];
				$tick = 0;
				$tickSize = count($usage_import) > 10 ? 2 : 1;

				foreach($usage_import as $hour => $data) {
					$rgb = $working_hours[$hour] ? '49,63,76' : '189,189,189'; //SHEZ'247,188,59' : '79,129,160';
					$op = $data['estimated'] ? 0.66 : 1;

					$data_imported = $data['used'];
					$data_generated = isset($usage_generated[$hour]) ? $usage_generated[$hour]['used'] : 0;
					$data_exported = isset($usage_export[$hour]) ? $usage_export[$hour]['used'] : 0;

					if($data_exported) {
						$chart_data[] = [
							'color' => "rgba(236,228,20,0)",
							'data' => [[ $tick, -$data_exported ]]
						];
						$chart_data[] = [
							'color' => "rgba(46,168,161,$op)",//"rgba(236,228,20,$op)",
							'data' => [[ $tick, $data_exported ]]
						];
					}
					$chart_data[] = [
						'color' => "rgba(0,151,206,$op)",//"rgba(162,184,58,$op)",
						'data' => [[ $tick, max($data_generated - $data_exported, 0) ]]
					];
					$chart_data[] = [
						'color' => "rgba($rgb,$op)",
						'data' => [[ $tick, $data_imported ]]
					];

					$xaxis['ticks'][] = [ $tick, ($tick % $tickSize == 0 ? ($hour < 10 ? "0$hour" : "$hour") : '') ];
					$tick++;
				}

				$content .= '<input type="hidden" id="gas-use-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="gas-use-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="gas-use-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
			} else {
				$content .= '<p>No data.</p>';
			}
		} else {
			// Daily chart

			$unit_html = '';
			$usage_array = [];
			foreach($import_meters as $meter) {
				if($meter->validate($building->id)) {
					$usage_import_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to);
					$unit_html = $meter->get_reading_unit(true);
				}
			}
			$usage_export_array = [];
			foreach($import_meters as $meter) {
				if($meter->validate($building->id)) {
					$usage_export_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to, true);
				}
			}
			$usage_generated_array = [];
			foreach($generation_meters as $meter) {
				if($meter->validate($building->id)) {
					$usage_generated_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to);
				}
			}

			$usage_import = Meter::get_total_hourly_usage($usage_import_array);
			$usage_generated = Meter::get_total_hourly_usage($usage_generated_array);
			$usage_export = Meter::get_total_hourly_usage($usage_export_array);
			$working_days = $building->get_working_days_plot($time_period->date_from, $time_period->date_to);

			if($usage_import) {
				$content .= '<div class="chart" id="gas-use-chart"></div>';

				$xaxis = [ 'axisLabel' => 'Days', 'ticks' => [], 'min' => -0.5, 'max' => count($usage_import) - 0.5 ];
				$yaxis = [ 'axisLabel' => $unit_html ];
				$chart_data = [];
				$tick = 0;
				$no_of_days = count($usage_import);
				$tickSize = $no_of_days > 7 ? 2 : 1;

				foreach($usage_import as $day => $data) {
					$tp = '';
					if(isset($dates[$day])) $tp = $dates[$day];

					$rgb = $working_days[$day] ? '49,63,76' : '189,189,189'; //SHEZ'247,188,59' : '79,129,160';
					$op = $data['incomplete'] || $data['estimated'] ? 0.66 : 1;

					$data_imported = $data['used'];
					$data_generated = isset($usage_generated[$day]) ? $usage_generated[$day]['used'] : 0;
					$data_exported = isset($usage_export[$day]) ? $usage_export[$day]['used'] : 0;

					// $chart_data[] = [
					// 	'color' => "rgba($rgb,$op)",
					// 	'data' => [[ $tick, $data['used'], $tp ]]
					// ];

					if($data_exported) {
						$chart_data[] = [
							'color' => "rgba(236,228,20,0)",
							'data' => [[ $tick, -$data_exported, $tp ]]
						];
						$chart_data[] = [
							'color' => "rgba(236,228,20,$op)",
							'data' => [[ $tick, $data_exported, $tp ]]
						];
					}
					$chart_data[] = [
						'color' => "rgba(162,184,58,$op)",
						'data' => [[ $tick, max($data_generated - $data_exported, 0), $tp ]]
					];
					$chart_data[] = [
						'color' => "rgba($rgb,$op)",
						'data' => [[ $tick, $data_imported, $tp ]]
					];

					$xaxis['ticks'][] = [ $tick, ($tick % $tickSize == 0 ? date($no_of_days > 7 ? 'd' : 'D j\<\s\u\p\>S\<\/\s\u\p\>', strtotime($day)) : '') ];
					$tick++;
				}

				$content .= '<input type="hidden" id="gas-use-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="gas-use-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="gas-use-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
			} else {
				$content .= '<p>No data.</p>';
			}
		}
	}

	$content .= '</div>';

	$content = "<div style=\"margin: 5px !important; position: absolute; top: 0; left: 0; bottom: 0; right: 0;\">$content</div>";

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = ' ';

	$ui_widget->print_html();
?>

<script>
	function initChart(chartName) {
		var xaxis = $('#' + chartName + '-xaxis').val(),
			yaxis = $('#' + chartName + '-yaxis').val(),
			flotData = $('#' + chartName + '-data').val();

		if(xaxis && yaxis && flotData) {
			initFlot('#' + chartName, $.parseJSON(flotData), {
				xaxis: $.parseJSON(xaxis),
				yaxis: $.parseJSON(yaxis),
				series: {
					stack: 1,
					bars: { show: true }
				},
				grid: {
					hoverable: true,
					clickable: true
				}
			});
		}
	}

	initChart('gas-use-chart');

	$("#gas-use-chart")
		.bind("plotclick", function (event, pos, item) {
			if(item && item.series.data[0][2]) {
				setTimePeriod(item.series.data[0][2]);
			}
		})
		.bind("plothover", function (event, pos, item) {
			if(item && item.series.data[0][2]) {
				$('#gas-use-chart').css('cursor', 'pointer');
			} else {
				$('#gas-use-chart').css('cursor', 'default');
			}
		});
</script>