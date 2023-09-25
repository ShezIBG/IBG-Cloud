<!-- /* JEANE CHANGE COLOUR CHART AND STEP */  -->

<?php
	$building = $user->get_default_building(Permission::RENEWABLES_ENABLED);
	if(!$building) return;

	$vo = $building->get_vo();
	$tz = Eticom::find_timezone_id($building->info->timezone);

	$set_tab = App::get('tab', '');
	$tab = 'temperature';
	if(isset($_SESSION['renewables-charts-widget'])) $tab = $_SESSION['renewables-charts-widget'];
	if($set_tab) $tab = $set_tab;
	if(!$vo && in_array($tab, ['voltage', 'live-voltage', 'pf', 'live-pf'])) $tab = 'temperature';

	$_SESSION['renewables-charts-widget'] = $tab;

	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = 'renewables-charts-widget';
	$ui_widget->color = 'orange';
	$ui_widget->header('title', '<p class="myWidget-title">Charts</p><i class="myWidget-colorIcon eticon eticon-top-consumer eticon-shadow"></i>');

	$content = '';

	$content .= '
		<div class="widget-row no-flex">
			<ul class="nav nav-tabs">
	';

	$content .= '<li class="'.($tab === 'temperature' ? 'active' : '').'"><a href="#" class="chart-widget-tab" data-tab="temperature" style="color: #555 !important;" data-toggle="tab"><i class="eticon eticon-thermometer"></i> Temperature</a></li>';
	$content .= '<li class="'.($tab === 'cloud' ? 'active' : '').'"><a href="#" class="chart-widget-tab" data-tab="cloud" style="color: #555 !important;" data-toggle="tab"><i class="eticon eticon-cloud"></i> Cloud Cover</a></li>';
	if($vo) {
		$content .= '<li class="'.($tab === 'voltage' ? 'active' : '').'"><a href="#" class="chart-widget-tab" data-tab="voltage" style="color: #555 !important;" data-toggle="tab"><i class="eticon eticon-bolt"></i> Voltage</a></li>';
		$content .= '<li class="'.($tab === 'pf' ? 'active' : '').'"><a href="#" class="chart-widget-tab" data-tab="pf" style="color: #555 !important;" data-toggle="tab"><i class="eticon eticon-bolt"></i> Power Factor</a></li>';
		$content .= '<li class="pull-right '.($tab === 'live-pf' ? 'active' : '').'"><a href="#" class="chart-widget-tab" data-tab="live-pf" style="color: #555 !important;" data-toggle="tab"><i class="eticon eticon-bolt"></i> Live Power Factor</a></li>';
		$content .= '<li class="pull-right '.($tab === 'live-voltage' ? 'active' : '').'"><a href="#" class="chart-widget-tab" data-tab="live-voltage" style="color: #555 !important;" data-toggle="tab"><i class="eticon eticon-bolt"></i> Live Voltage</a></li>';
	}

	$content .= '
			</ul>
		</div>
	';

	$content .= '<div class="widget-row display-flex">';

	if ($building) {
		$time_period = $dashboard->get_time_period($building->id);
		$weather = new WeatherService($building->id);

		$today = date('Y-m-d');
		$weather_today = $weather->get_hourly_weather_plot($today);
		$current_temp = 0;
		$icon = '';
		$summary = '';
		if($weather_today) {
			$details = $weather_today[$today][date('G')];
			if($details) {
				$current_temp = floor($details->temperature);
				$summary = $details->summary;
				if(in_array($details->icon, ['clear-day', 'clear-night', 'partly-cloudy-day', 'partly-cloudy-night', 'cloudy', 'rain', 'sleet', 'snow', 'wind', 'fog'])) {
					$icon = '<canvas id="climate-weather-icon" width="80" height="80" data-icon="'.$details->icon.'"></canvas>';
				}
			}
		}

		$weather_data = $weather->get_hourly_weather_plot($time_period->date_from, $time_period->date_to);

		if($weather_data) {
			if($tab === 'temperature') {

				$content .= '
					<table style="width: 100%; height: 100%;">
						<tr>
							<td style="width: 15em;" class="text-center">
								Current temperature<br>
								<strong style="font-size: 3em;">'.$current_temp.'<sup><span style="font-size: 0.4em">&deg;C</span></sup></strong><br>
								'.$icon.'<br>
								'.$summary.'
							</td>
							<td style="height:100%;">
								<div id="climate-chart" class="chart"></div>
							</td>
						</tr>
					</table>';

				$chart_data = [];

				$min_temp = null;
				$max_temp = null;
				$min_gas = null;
				$max_gas = null;

				$weather_plot = [];
				foreach($weather_data as $day => $day_data) {
					if($day_data) {
						foreach($day_data as $hour => $data) {
							if($data) {
								$timestamp = strtotime($day.' '.str_pad($hour, 2, '0', STR_PAD_LEFT).':00:00 UTC') * 1000;
								$weather_plot[] = [$timestamp, $data->temperature];

								if($min_temp === null || $max_temp === null) {
									$min_temp = $max_temp = $data->temperature;
								} else {
									$min_temp = min($min_temp, $data->temperature);
									$max_temp = max($max_temp, $data->temperature);
								}
							}
						}
					}
				}

				$unit_html = '';

				$generation_meters = $building->get_main_monitored_meters('E', true);
				$usage_array = [];
				foreach($generation_meters as $meter) {
					if($meter->validate($building->id)) {
						$usage_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to);
						$unit_html = $meter->get_reading_unit(true);
					}
				}
				$usage = Meter::get_total_hourly_usage($usage_array);

				$usage_plot = [];
				foreach($usage as $day => $data) {
					for($h = 0; $h < 24; $h++) {
						$hour = $h < 10 ? "0$h" : "$h";

						$sum = $data['hours'][$h]['used'];
						$timestamp = strtotime("$day $hour:00:00 UTC") * 1000;
						$usage_plot[] = [$timestamp, $sum];

						if($min_gas === null || $max_gas === null) {
							$min_gas = $max_gas = $sum ?: 0;
						} else {
							$min_gas = min($min_gas, $sum ?: 0);
							$max_gas = max($max_gas, $sum ?: 0);
						}
					}
				}

				$min_temp = floor(($min_temp + 0.01) / 10) * 10;
				$max_temp = ceil(($max_temp + 0.01) / 10) * 10;

				// Skew min/max values to make graph nicer
				$min_temp = $min_temp - ($max_temp - $min_temp);
				$max_gas *= 2;

				$xaxis = [ 'mode' => 'time', 'tickLength' => null, 'font' => [ 'color' => '#666' ] ];
				$yaxis = [
					[ 'axisLabel' => '<span style="color:#829c02">Outside Temperature (&deg;C)</span>', 'position' => 'left', 'min' => $min_temp, 'max' => $max_temp, 'font' => [ 'color' => '#829c02' ] ],
					[ 'axisLabel' => '<span style="color:#0097ce">Hourly Generated Power ('.$unit_html.')</span>', 'position' => 'right', 'alignTicksWithAxis' => 1, 'min' => $min_gas, 'max' => $max_gas, 'font' => [ 'color' => '#0097ce' ] ]
				];

				$chart_data[] = [
					'color' => '#829c02',
					'data' => $weather_plot,
					'yaxis' => 1
				];

				$chart_data[] = [
					'color' => '#0097ce',
					'data' => $usage_plot,
					'yaxis' => 2
				];

				$content .= '<input type="hidden" id="climate-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="climate-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="climate-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';

			} else if($tab === 'cloud') {

				$content .= '
					<table style="width: 100%; height: 100%;">
						<tr>
							<td style="width: 15em;" class="text-center">
								Current temperature<br>
								<strong style="font-size: 3em;">'.$current_temp.'<sup><span style="font-size: 0.4em">&deg;C</span></sup></strong><br>
								'.$icon.'<br>
								'.$summary.'
							</td>
							<td style="height:100%;">
								<div id="climate-chart" class="chart"></div>
							</td>
						</tr>
					</table>';

				$chart_data = [];

				$min_temp = null;
				$max_temp = null;
				$min_gas = null;
				$max_gas = null;

				$weather_plot = [];
				foreach($weather_data as $day => $day_data) {
					if($day_data) {
						foreach($day_data as $hour => $data) {
							if($data) {
								$cover = $data->cloudCover * 100;

								$timestamp = strtotime($day.' '.str_pad($hour, 2, '0', STR_PAD_LEFT).':00:00 UTC') * 1000;
								$weather_plot[] = [$timestamp, $cover];

								if($min_temp === null || $max_temp === null) {
									$min_temp = $max_temp = $cover;
								} else {
									$min_temp = min($min_temp, $cover);
									$max_temp = max($max_temp, $cover);
								}
							}
						}
					}
				}

				$unit_html = '';

				$generation_meters = $building->get_main_monitored_meters('E', true);
				$usage_array = [];
				foreach($generation_meters as $meter) {
					if($meter->validate($building->id)) {
						$usage_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to);
						$unit_html = $meter->get_reading_unit(true);
					}
				}
				$usage = Meter::get_total_hourly_usage($usage_array);

				$usage_plot = [];
				foreach($usage as $day => $data) {
					for($h = 0; $h < 24; $h++) {
						$hour = $h < 10 ? "0$h" : "$h";

						$sum = $data['hours'][$h]['used'];
						$timestamp = strtotime("$day $hour:00:00 UTC") * 1000;
						$usage_plot[] = [$timestamp, $sum];

						if($min_gas === null || $max_gas === null) {
							$min_gas = $max_gas = $sum ?: 0;
						} else {
							$min_gas = min($min_gas, $sum ?: 0);
							$max_gas = max($max_gas, $sum ?: 0);
						}
					}
				}

				$min_temp = floor(($min_temp + 0.01) / 10) * 10;
				$max_temp = ceil(($max_temp + 0.01) / 10) * 10;

				// Skew min/max values to make graph nicer
				$min_temp = $min_temp - ($max_temp - $min_temp);
				$max_gas *= 2;

				$xaxis = [ 'mode' => 'time', 'tickLength' => null, 'font' => [ 'color' => '#666' ] ];
				$yaxis = [
					[ 'axisLabel' => '<span style="color:#0ea8a1">Cloud Cover (%)</span>', 'position' => 'left', 'min' => $min_temp, 'max' => $max_temp, 'font' => [ 'color' => '#0ea8a1' ] ],
					[ 'axisLabel' => '<span style="color:#0097ce">Hourly Generated Power ('.$unit_html.')</span>', 'position' => 'right', 'alignTicksWithAxis' => 1, 'min' => $min_gas, 'max' => $max_gas, 'font' => [ 'color' => '#0097ce' ] ]
				];

				$chart_data[] = [
					'color' => '#0ea8a1',
					'data' => $weather_plot,
					'yaxis' => 1
				];

				$chart_data[] = [
					'color' => '#0097ce',
					'data' => $usage_plot,
					'yaxis' => 2
				];

				$content .= '<input type="hidden" id="climate-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="climate-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="climate-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';

			} else if($tab === 'voltage' || $tab === 'live-voltage') {

				$date_from = $time_period->date_from;
				$date_to = $time_period->date_to;

				if($tab === 'live-voltage') {
					$date_from = date('Y-m-d H:i:s', strtotime('-6 hours'));
					$date_to = date('Y-m-d H:i:s');
				}

				$input = $vo->get_input_voltage_history($date_from, $date_to);
				$output = $vo->get_output_voltage_history($date_from, $date_to);

				$content .= '
					<table style="width: 100%; height: 100%;">
						<tr>
							<td style="height:100%;">
								<div id="climate-chart" class="chart"></div>
							</td>
						</tr>
					</table>';

				$chart_data = [];

				$min_input = null;
				$max_input = null;
				$min_output = null;
				$max_output = null;

				$input_plot = [];
				foreach($input as $item) {
					if($tab === 'live-voltage') {
						$timestamp = strtotime(App::timezone($item['datetime'], 'UTC', $tz)) * 1000;
					} else {
						// TODO: This is cheating. We're selecting data in UTC and avoid conversion to correct timezone to show full days.
						// But obviously, everything in the graph is shifted by an hour during the summer...
						$timestamp = strtotime($item['datetime']) * 1000;
					}
					$input_plot[] = [$timestamp, $item['avg']];

					if($min_input === null || $max_input === null) {
						$min_input = $max_input = $item['avg'];
					} else {
						$min_input = min($min_input, $item['avg']);
						$max_input = max($max_input, $item['avg']);
					}
				}

				$output_plot = [];
				foreach($output as $item) {
					if($tab === 'live-voltage') {
						$timestamp = strtotime(App::timezone($item['datetime'], 'UTC', $tz)) * 1000;
					} else {
						// TODO: This is cheating. We're selecting data in UTC and avoid conversion to correct timezone to show full days.
						// But obviously, everything in the graph is shifted by an hour during the summer...
						$timestamp = strtotime($item['datetime']) * 1000;
					}
					$output_plot[] = [$timestamp, $item['avg']];

					if($min_output === null || $max_output === null) {
						$min_output = $max_output = $item['avg'];
					} else {
						$min_output = min($min_output, $item['avg']);
						$max_output = max($max_output, $item['avg']);
					}
				}

				$min_value = min($min_input ?: $min_output, $min_output ?: $min_input) ?: 0;
				$max_value = max($max_input ?: $max_output, $max_output ?: $max_input) ?: 0;

				$min_value = floor(($min_value + 0.01) / 10) * 10;
				$max_value = ceil(($max_value + 0.01) / 10) * 10;

				$xaxis = [ 'mode' => 'time', 'tickLength' => null, 'font' => [ 'color' => '#666' ] ];
				$yaxis = [
					[ 'axisLabel' => 'Input/Output Voltage (V)', 'position' => 'left', 'min' => $min_value, 'max' => $max_value ],
				];

				$chart_data[] = [
					'color' => '#6cbf65',
					'data' => $input_plot,
					'yaxis' => 1
				];

				$chart_data[] = [
					'color' => '#0097ce',
					'data' => $output_plot,
					'yaxis' => 1
				];

				$content .= '<input type="hidden" id="climate-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="climate-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="climate-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';

			} else if($tab === 'pf' || $tab === 'live-pf') {

				$date_from = $time_period->date_from;
				$date_to = $time_period->date_to;

				if($tab === 'live-pf') {
					$date_from = date('Y-m-d H:i:s', strtotime('-6 hours'));
					$date_to = date('Y-m-d H:i:s');
				}

				$pf = $vo->get_power_factor_history($date_from, $date_to);

				$content .= '
					<table style="width: 100%; height: 100%;">
						<tr>
							<td style="height:100%;">
								<div id="climate-chart" class="chart"></div>
							</td>
							<td style="width: 100px; padding-left: 20px;">
								<span style="background: #0ea8a1; display: inline-block; width: 20px; height: 3px; position: relative; top: -3px;"></span>&nbsp;&nbsp;&nbsp;L1<br>
								<span style="background: #000000; display: inline-block; width: 20px; height: 3px; position: relative; top: -3px;"></span>&nbsp;&nbsp;&nbsp;L2<br>
								<span style="background: #9399a3; display: inline-block; width: 20px; height: 3px; position: relative; top: -3px;"></span>&nbsp;&nbsp;&nbsp;L3<br>
								<span style="background: #0097ce; display: inline-block; width: 20px; height: 3px; position: relative; top: -3px;"></span>&nbsp;&nbsp;&nbsp;Total
							</td>
						</tr>
					</table>';

				$chart_data = [];

				$min_value = 0;
				$max_value = 1;

				$l1_plot = [];
				$l2_plot = [];
				$l3_plot = [];
				$total_plot = [];
				foreach($pf as $item) {
					if($tab === 'live-pf') {
						$timestamp = strtotime(App::timezone($item['datetime'], 'UTC', $tz)) * 1000;
					} else {
						// TODO: This is cheating. We're selecting data in UTC and avoid conversion to correct timezone to show full days.
						// But obviously, everything in the graph is shifted by an hour during the summer...
						$timestamp = strtotime($item['datetime']) * 1000;
					}
					$l1_plot[] = [$timestamp, $item['l1']];
					$l2_plot[] = [$timestamp, $item['l2']];
					$l3_plot[] = [$timestamp, $item['l3']];
					$total_plot[] = [$timestamp, $item['total']];
				}

				$xaxis = [ 'mode' => 'time', 'tickLength' => null, 'font' => [ 'color' => '#666' ] ];
				$yaxis = [
					[ 'axisLabel' => 'Power Factor', 'position' => 'left', 'min' => $min_value, 'max' => $max_value ],
				];

				$chart_data[] = [
					'color' => '#0ea8a1',
					'data' => $l1_plot,
					'label' => 'L1'
				];

				$chart_data[] = [
					'color' => '#000000',
					'data' => $l2_plot,
					'label' => 'L2'
				];

				$chart_data[] = [
					'color' => '#9399a3',
					'data' => $l3_plot,
					'label' => 'L3'
				];

				$chart_data[] = [
					'color' => '#0097ce',
					'data' => $total_plot,
					'label' => 'Total'
				];

				$content .= '<input type="hidden" id="climate-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
				$content .= '<input type="hidden" id="climate-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
				$content .= '<input type="hidden" id="climate-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';

			}
		} else {
			$content .= '<p>No data.</p>';
		}
	}

	$content .= '</div>';

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = '<a class="font-xs pull-right" href="https://darksky.net/poweredby/" target="_blank">Powered by Dark Sky</a>';

	$ui_widget->print_html();
?>

<script>
	function initLineChart(chartName) {
		var xaxis = $('#' + chartName + '-xaxis').val(),
			yaxis = $('#' + chartName + '-yaxis').val(),
			flotData = $('#' + chartName + '-data').val();

		if(xaxis && yaxis && flotData) {
			xaxis = $.parseJSON(xaxis);
			xaxis.tickFormatter = function (val, axis) {
				var d = new Date(val);
				var month = d.getUTCMonth() + 1;
				var day = d.getUTCDate();
				var hours = d.getUTCHours();
				var minutes = d.getUTCMinutes();

				if(month < 10) month = '0' + month;
				if(day < 10) day = '0' + day;
				if(hours < 10) hours = '0' + hours;
				if(minutes < 10) minutes = '0' + minutes;

				return day + '/' + month + '<br>' + hours + ':' + minutes;
			}

			initFlot('#' + chartName, $.parseJSON(flotData), {
				xaxis: xaxis,
				yaxis: $.parseJSON(yaxis)[0],
				y2axis: $.parseJSON(yaxis)[1],
				lines: { lineWidth: 2 },
				series: {
					lines: { show: true }
				}
			});
		}
	}

	$(function() {
		var $canvas = $('#climate-weather-icon');
		if($canvas.length) {
			var skycons = new Skycons({ "color": "#829c02" }, { 'resizeClear': true });
			skycons.add($canvas[0], $canvas.data('icon'));
			skycons.play();

			registerRefreshListener(function() {
				skycons.remove($canvas[0]);
				skycons = null;
			}, true);
		}

		$('#renewables-charts-widget .chart-widget-tab').click(function(e) {
			e.preventDefault();
			loadWidget($('#renewables-charts-widget').closest('.grid-stack-item'), {
				tab: $(this).data('tab')
			});
		});
	});

	initLineChart('climate-chart');
</script>
<style>

@media (max-width: 360px){
	#left-panel{
		display:none;

	}
	#main{
		margin-left:auto;
		

	}
	
	.text-center{

	display:none;
	}

}

@media (max-width: 820px){
	#left-panel{
		display:none;


	}
	#main{
		margin-left:auto;
		

	}

	.text-center{

	display:none;
	}
}



</style>
