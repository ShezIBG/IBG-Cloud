<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'blueWater';
	$ui_widget->header('title', '<p class="myWidget-title">Your Water Use</p><i class="myWidget-colorIcon eticon eticon-meter-color eticon-meter eticon-shadow"></i>');

	$content = '<div class="widget-row display-flex">';

	if ($building = $user->get_default_building(Permission::WATER_ENABLED)) {
		$vpd = $dashboard->get_valid_period_days($building->id);
		$time_period = $dashboard->get_time_period($building->id, $vpd);

		foreach($vpd as $d) {
			$dates[date('Y-m-d', strtotime("-{$d} day"))] = Dashboard::TIME_PERIOD_DAY.$d;
		}

		// Add yesterday to the list of selectable dates
		$dates[date('Y-m-d', strtotime('yesterday'))] = Dashboard::TIME_PERIOD_YESTERDAY;

		$single_day = $time_period->date_from === $time_period->date_to;
		$water_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id' AND m.meter_type = 'W' AND m.parent_id IS NULL;") ?: [];

		if($single_day) {
			// Hourly chart

			$unit_html = '';
			$usage_array = [];
			foreach($water_meters as $m) {
				$meter = new Meter($m->id);
				if($meter->validate($building->id)) {
					$usage_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to);
					$unit_html = $meter->get_reading_unit(true);
				}
			}

			$usage = Meter::get_total_hourly_usage($usage_array);
			$working_hours = $building->get_working_hours_plot($time_period->date_from);

			if(isset($usage[$time_period->date_from])) {
				$usage = $usage[$time_period->date_from]['hours'];
				$content .= '<div class="chart" id="water-use-chart"></div>';

				$xaxis = [ 'axisLabel' => 'Hours', 'ticks' => [], 'min' => -0.5, 'max' => count($usage) - 0.5 ];
				$yaxis = [ 'axisLabel' => $unit_html ];
				$chart_data = [];
				$tick = 0;
				$tickSize = 1;

				foreach($usage as $hour => $data) {
					$rgb = $working_hours[$hour] ? '49,63,76' : '189,189,189'; //SHEZ'247,188,59' : '79,129,160';
					$op = $data['estimated'] ? 0.66 : 1;

					$chart_data[] = [
						'color' => "rgba($rgb,$op)",
						'data' => [[ $tick, $data['used'] ]]
					];

					$xaxis['ticks'][] = [ $tick, ($tick % $tickSize == 0 ? ($hour < 10 ? "0$hour" : "$hour") : '') ];
					$tick++;
				}

				$content .= '<input type="hidden" id="water-use-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="water-use-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="water-use-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
			} else {
				$content .= '<p>No data.</p>';
			}
		} else {
			// Daily chart

			$unit_html = '';
			$usage_array = [];
			foreach($water_meters as $m) {
				$meter = new Meter($m->id);
				if($meter->validate($building->id)) {
					$usage_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to);
					$unit_html = $meter->get_reading_unit(true);
				}
			}

			$usage = Meter::get_total_hourly_usage($usage_array);
			$working_days = $building->get_working_days_plot($time_period->date_from, $time_period->date_to);

			if($usage) {
				$content .= '<div class="chart" id="water-use-chart"></div>';

				$xaxis = [ 'axisLabel' => 'Days', 'ticks' => [], 'min' => -0.5, 'max' => count($usage) - 0.5 ];
				$yaxis = [ 'axisLabel' => $unit_html ];
				$chart_data = [];
				$tick = 0;
				$no_of_days = count($usage);

				foreach($usage as $day => $data) {
					$tp = '';
					if(isset($dates[$day])) $tp = $dates[$day];

					$rgb = $working_days[$day] ? '49,63,76' : '189,189,189'; //SHEZ'247,188,59' : '79,129,160';
					$op = $data['incomplete'] || $data['estimated'] ? 0.66 : 1;

					$chart_data[] = [
						'color' => "rgba($rgb,$op)",
						'data' => [[ $tick, $data['used'], $tp ]]
					];

					$xaxis['ticks'][] = [ $tick, date($no_of_days > 7 ? 'd' : 'D j\<\s\u\p\>S\<\/\s\u\p\>', strtotime($day)) ];
					$tick++;
				}

				$content .= '<input type="hidden" id="water-use-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="water-use-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="water-use-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
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
					bars: { show: true }
				},
				grid: {
					hoverable: true,
					clickable: true
				},
				tooltip: {
					show: true,
					content: '%y.2'
				}
			});
		}
	}

	initChart('water-use-chart');

	$("#water-use-chart")
		.bind("plotclick", function (event, pos, item) {
			if(item && item.series.data[0][2]) {
				setTimePeriod(item.series.data[0][2]);
			}
		})
		.bind("plothover", function (event, pos, item) {
			if(item && item.series.data[0][2]) {
				$('#water-use-chart').css('cursor', 'pointer');
			} else {
				$('#water-use-chart').css('cursor', 'default');
			}
		});
</script>


