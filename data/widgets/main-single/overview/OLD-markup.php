<!-- JEANE CHANGE 
4E81A3 > 2E3C47
ED7339 > 829c02
-->

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

	if ($building = $user->get_default_building(Permission::ELECTRICITY_ENABLED)) {
		$vpd = $dashboard->get_valid_period_days($building->id);
		$time_period = $dashboard->get_time_period($building->id, $vpd);

		foreach($vpd as $d) {
			$dates[date('d/m/Y', strtotime("-{$d} day"))] = Dashboard::TIME_PERIOD_DAY.$d;
		}

		// Add yesterday to the list of selectable dates
		$dates[date('d/m/Y', strtotime('yesterday'))] = Dashboard::TIME_PERIOD_YESTERDAY;

		$flot_data = "";

		$data = App::sql()->query_row(
			"SELECT SUM({$time_period->yec_kwh_used}) AS kwh_used, SUM({$time_period->yec_cost}) AS cost
			FROM `{$time_period->yec_table}`
			WHERE category_id = ".Eticom::CATEGORY_BILLING." AND building_id = $building->id"
		);

		$kwh = $data ? $data->kwh_used : 0;
		$cost = $data ? number_format($data->cost, 2) : 0;
		$xaxis = [];

		if($time_period->step_filter) {
			$q = "SELECT * FROM `{$time_period->step_table}` WHERE {$time_period->step_filter} AND building_id = $building->id";
		} else {
			$q = "SELECT * FROM `{$time_period->step_table}` WHERE building_id = $building->id";
		}

		$kwh_data = App::sql()->query_row($q);

		$billing_category = Eticom::CATEGORY_BILLING;
		$top_consumer = App::sql()->query_row(
			"SELECT
				MIN(IF(c.description IS NOT NULL, c.description, yec.cat_desc)) AS cat_desc,
				SUM(yec.{$time_period->yec_cost}) AS total_cost,
				yec.category_id
			FROM `{$time_period->yec_table}` AS yec
			LEFT JOIN category AS c ON c.id = yec.category_id
			WHERE
				yec.category_id <> '$billing_category'
				AND yec.{$time_period->yec_kwh_used} > 0
				AND yec.building_id = '$building->id'
				AND yec.category_id NOT IN (SELECT category_id FROM building_category_settings WHERE building_id = '$building->id' AND hide_from_electricity_widget = 1)
			GROUP BY category_id
			ORDER BY total_cost DESC
			LIMIT 1
		");

		$get_bar_color = function($is_open) {
			return $is_open ? '#313f47' : '#BDBDBD';
			// return $is_open ? '#F9C12B' : '#2E3C47';
		};

		if ($time_period->value == Dashboard::TIME_PERIOD_YESTERDAY || $time_period->value == Dashboard::TIME_PERIOD_DAY) {
			if ($kwh_data) {
				$flot_data = array_map(function($hour) use ($kwh_data, $get_bar_color) {
					return [
						'color' => $get_bar_color($kwh_data->{'open_hour_'.$hour} != 0),
						'data' => [[$hour - 1, $kwh_data->{'kwh_used_hour_'.$hour}]]
					];
				}, range(1, 24));
			} else {
				$flot_data = [];
			}

			$xaxis = [
				'axisLabel' => 'Hour of day',
				'tickSize' => 2
			];
		} else if ($time_period->value == Dashboard::TIME_PERIOD_LAST_WEEK) {
			$d1 = strtotime('last week');

			if ($kwh_data) {
				$flot_data = array_map(function($day) use ($kwh_data, $get_bar_color, $d1, $dates) {
					$tp = '';
					$dt = date('d/m/Y', strtotime("+{$day} day", $d1));
					if(isset($dates[$dt])) $tp = $dates[$dt];

					$day_str = strtolower(jddayofweek($day, 2));
					return [
						'color' => $get_bar_color($kwh_data->{'open_'.$day_str} != 0),
						'data' => [[$day, $kwh_data->{'kwh_used_'.$day_str}, $tp]]
					];
				}, range(0, 6));
			}

			$xaxis = [
				'axisLabel' => 'Days of Week',
				'ticks' => array_map(function($day) use ($d1) {
					return [$day, date('D j\<\s\u\p\>S\<\/\s\u\p\>', strtotime("+{$day} day", $d1))];
				}, range(0, 6))
			];
		} else if ($time_period->value == Dashboard::TIME_PERIOD_LAST_MONTH) {
			$month = date('n') - 1 ?: 12;
			$leap = date('L');
			$days_of_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
			if ($leap) $days_of_month[1] = 29;

			$d1 = strtotime('first day of last month');

			if ($kwh_data) {
				$flot_data = array_map(function($day) use ($kwh_data, $get_bar_color, $d1, $dates) {
					$tp = '';
					$add = $day - 1;
					$dt = date('d/m/Y', strtotime("+{$add} day", $d1));
					if(isset($dates[$dt])) $tp = $dates[$dt];

					return [
						'color' => $get_bar_color($kwh_data->{'open_day_'.$day} != 0),
						'data' => [[$day, $kwh_data->{'kwh_used_day_'.$day}, $tp]]
					];
				}, range(1, $days_of_month[$month - 1]));
			}

			$xaxis = [
				'axisLabel' => 'Days',
				'tickSize' => 2
			];
		}

		$yaxis = [ 'axisLabel' => 'kWh' ];
		// JEANE CHANGE
		$content = '
		<input type="hidden" id="kwh-usage-data" value="'.App::clean_str(json_encode($flot_data)).'">
		<input type="hidden" id="kwh-usage-xaxis" value="'.App::clean_str(json_encode($xaxis)).'">
		<input type="hidden" id="kwh-usage-yaxis" value="'.App::clean_str(json_encode($yaxis)).'">

		<div class="myDropdown-wrapper2 widget-row display-flex no-flex overflow-hidden overview-building">
			<div>
				<p class="myDescription no-margin padding-top-5">Choose building</p>
			</div>
			<div class="myControl-dropdown-container">
				<select class="select2 centered" id="default-building" style="width:100%;">';

		$list = Permission::list_buildings([ 'with' => Permission::ELECTRICITY_ENABLED ]);
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

		$content .= '</select>
			</div>
		</div>

		<div class="widget-row display-flex no-flex overview">
			<div class="overview-detail">
				<span class="eticon-stack text-center">
					<i class="eticon eticon-circle eticon-stack-2x"></i>
					<i class="eticon eticon-bolt eticon-inverse eticon-stack-1x eticon-shadow"></i>
				</span>
				<p class="padding-top-10 no-margin myNote">Total power</p>
				<p class="no-margin font-md">'.$kwh.' kWh</p>
			</div>

			<div class="overview-detail">
				<span class="eticon-stack text-center">
					<i class="eticon eticon-circle eticon-stack-2x"></i>
					<i class="eticon eticon-pound-sign eticon-stack-1x eticon-inverse eticon-shadow"></i>
				</span>
				<p class="padding-top-10 no-margin myNote">Total cost</p>
				<p class="no-margin font-md">&pound;'.$cost.'</p>
			</div>

			<div class="overview-detail">
				<span class="eticon-stack text-center">
					<i class="eticon eticon-circle eticon-stack-2x"></i>
					<i class="eticon eticon-top-consumer eticon-stack-1x eticon-inverse eticon-shadow"></i>
				</span>
				<p class="padding-top-10 no-margin myNote">Top consumer</p>
				<p class="no-margin txt-color-'.($top_consumer ? 'red' : 'green').' font-md">'. ($top_consumer ? $top_consumer->cat_desc : 'No data to show') .'</p>
			</div>
		</div>
		<div class="dashboard-widget-content-separator"><h5>Your Electrical kWh Usage</h5></div>
		<div class="chart" id="kwh-usage-chart"></div>';

	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = ' ';
	$ui_widget->print_html();
?>

<script>
	var xaxis = $.parseJSON($('#kwh-usage-xaxis').val()),
		yaxis = $.parseJSON($('#kwh-usage-yaxis').val()),
		flotData = $.parseJSON($('#kwh-usage-data').val());

	initFlot('#kwh-usage-chart', flotData, {
		xaxis: xaxis,
		yaxis: yaxis,
		series: {
			bars: { show: true }
		},
		grid: {
			hoverable: true,
			clickable: true
		}
	});

	$("#kwh-usage-chart")
		.bind("plotclick", function (event, pos, item) {
			if(item && item.series.data[0][2]) {
				setTimePeriod(item.series.data[0][2]);
			}
		})
		.bind("plothover", function (event, pos, item) {
			if(item && item.series.data[0][2]) {
				$('#kwh-usage-chart').css('cursor', 'pointer');
			} else {
				$('#kwh-usage-chart').css('cursor', 'default');
			}
		});

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
