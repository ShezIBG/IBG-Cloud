<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = 'sub-meter-list-widget';
	$ui_widget->color = 'teal';

	if ($building = $user->get_default_building(Permission::GAS_ENABLED)) {
		$time_period = $dashboard->get_time_period($building->id);

		$sub_meters = App::sql()->query("SELECT m.id, a.description AS area FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id' AND m.meter_type = 'G' AND m.parent_id IS NOT NULL ORDER BY m.description;");

		if($time_period->value == Dashboard::TIME_PERIOD_YESTERDAY || $time_period->value == Dashboard::TIME_PERIOD_DAY) {
			// A single day is selected
			$offset = $time_period->value == Dashboard::TIME_PERIOD_YESTERDAY ? 1 : $time_period->day;
			$dt = strtotime("-{$offset} day");
			$y = date('Y', $dt);
			$m = date('m', $dt);
			$d = date('d', $dt);
			$wk = date('W', $dt);

		} else if($time_period->value == Dashboard::TIME_PERIOD_LAST_WEEK) {
			// A week is selected
			$sdt = strtotime('last week');      // Start of the week
			$edt = strtotime("+6 days", $sdt);  // End of the week
			$wk = date('W', $sdt);
			$sy = date('Y', $sdt);
			$sm = date('m', $sdt);
			$ey = date('Y', $edt);
			$em = date('m', $edt);

		} else if($time_period->value == Dashboard::TIME_PERIOD_LAST_MONTH) {
			// A month is selected
			$dt = strtotime('first day of last month');
			$y = date('Y', $dt);
			$m = date('m', $dt);

		}

		if($sub_meters) {
			$table_data = [];

			$current_date_from = $time_period->date_from;
			$current_date_to = $time_period->date_to;

			foreach($sub_meters as $m) {
				$meter = new Meter($m->id);
				$area_description = $m->area;
				$usage = $meter->get_hourly_usage($current_date_from, $current_date_to);
				$usage_total = 0;
				foreach($usage as $u) {
					$usage_total += $u['used'];
				}
				$reading = $meter->get_latest_reading();
				$reading_total = $reading ? ($reading->reading_total ? $reading->reading_total : $reading->reading_1 + $reading->reading_2 + $reading->reading_3) : 0;

				$tenant_name = $meter->get_tenant_name();

				$table_data[] = [
					'id' => $meter->id,
					'Description' => $meter->info->description.' <br><span style="font-size: 80%; color: #999;">'.($tenant_name ? $tenant_name : $area_description).'</span>',
					'Latest Reading' => $reading_total,
					'Usage' => $usage_total.' kWh'
				];
			}

			$ui_table = $ui->create_datatable($table_data, [
				'static' => true,
				'in_widget' => false,
				'bordered' => false,
				'striped' => false,
				'default_col' => false,
				'columns' => true,
				'hover' => false
			]);

			$ui_table->hidden = ['id'];
			$ui_table->class = 'font-md';

			$ui_table
				->cell('Usage', [ 'class' => 'text-right fixed-numbers' ])
				->cell('Latest Reading', [ 'class' => 'text-right fixed-numbers' ])
				->col('Usage', [ 'class' => 'text-right' ])
				->col('Latest Reading', [ 'class' => 'text-right' ]);

			$content = '
				<div class="widget-row">
					'.$ui_table->print_html(true).'
				</div>';
		} else {
			$content = '<p class="myText-noData">No sub-meters found.</p>';
		}

		$ui_widget->header('title', '<p class="myWidget-title">Sub-meters</p><i class="myWidget-colorIcon eticon eticon-droplet eticon-droplet-color eticon-shadow" style="margin-top: 15px !important;"></i>');
		$ui_widget->footer = ' ';

	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->print_html();
?>
 
