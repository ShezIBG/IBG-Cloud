<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'orange';
	$ui_widget->header('title', '<p class="myWidget-title">Your Climate</p> <i class="myWidget-colorIcon eticon eticon-thermometer eticon-shadow"></i>');

	$content = '<div class="widget-row display-flex">';

	if ($building = $user->get_default_building(Permission::GAS_ENABLED)) {
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
					$icon = '<canvas id="climate-weather-icon" width="100" height="100" data-icon="'.$details->icon.'"></canvas>';
				}
			}
		}

		$weather_data = $weather->get_hourly_weather_plot($time_period->date_from, $time_period->date_to);

		if($weather_data) {
			$content .= '
				<table style="width: 100%; height: 100%;">
					<tr>
						<td style="width: 15em;" class="text-center">
							Current temperature<br>
							<strong style="font-size: 4em;">'.$current_temp.'<sup><span style="font-size: 0.4em">&deg;C</span></sup></strong><br>
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
							if($hour < 24){
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
			}

			$unit_html = 'kWh';

			$gas_meters = App::sql()->query("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id' AND m.meter_type = 'G' AND m.parent_id IS NULL;") ?: [];
			$usage_array = [];
			foreach($gas_meters as $m) {
				$meter = new Meter($m->id);
				if($meter->validate($building->id)) {
					$usage_array[] = $meter->get_hourly_usage($time_period->date_from, $time_period->date_to);
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

			$xaxis = [ 'mode' => 'time', 'tickLength' => null ];
			$yaxis = [
				[ 'axisLabel' => '<span style="color:#EE7339">Outside Temperature (&deg;C)</span>', 'position' => 'left', 'min' => $min_temp, 'max' => $max_temp, 'font' => [ 'color' => '#EE7339' ] ],
				[ 'axisLabel' => '<span style="color:#4F81A0">Hourly Gas Usage ('.$unit_html.')</span>', 'position' => 'right', 'alignTicksWithAxis' => 1, 'min' => $min_gas, 'max' => $max_gas, 'font' => [ 'color' => '#4F81A0' ] ]
			];

			$chart_data[] = [
				'color' => '#EE7339',
				'data' => $weather_plot,
				'yaxis' => 1
			];

			$chart_data[] = [
				'color' => '#4F81A0',
				'data' => $usage_plot,
				'yaxis' => 2
			];

			$content .= '<input type="hidden" id="climate-chart-data" value="'.App::clean_str(json_encode($chart_data)).'">';
			$content .= '<input type="hidden" id="climate-chart-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">';
			$content .= '<input type="hidden" id="climate-chart-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">';
		} else {
			$content .= '<p>No periods found.</p>';
		}
	}

	$content .= '</div>';

	$content = "<div style=\"margin: 5px !important; position: absolute; top: 0; left: 0; bottom: 0; right: 0;\">$content</div>";

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = '<a class="font-xs pull-right" href="https://pirateweather.net/en/latest/API/" target="_blank">Powered by Pirate Weather</a>';

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
			var skycons = new Skycons({ "color": "#F37943" }, { 'resizeClear': true });
			skycons.add($canvas[0], $canvas.data('icon'));
			skycons.play();

			registerRefreshListener(function() {
				skycons.remove($canvas[0]);
				skycons = null;
			}, true);
		}
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

