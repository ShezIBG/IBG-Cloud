<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = 'sub-meter-detail-widget';
	$ui_widget->color = 'teal';

	if ($building = $user->get_default_building(Permission::WATER_ENABLED)) {
		$time_period = $dashboard->get_time_period($building->id);

		$first_meter = App::sql()->query_row("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building->id' AND m.meter_type = 'W' AND m.parent_id IS NOT NULL ORDER BY m.description LIMIT 1;");
		$default_meter_id = $first_meter ? $first_meter->id : null;
		$meter_id = App::get('meter_id', $default_meter_id);

		$meter = new Meter($meter_id);
		if($meter->validate($building->id)) {
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

			$content = '
				<div style="padding: 20px;">
					<table class="table font-md">
			';

			$content .= '<tr>   <td><b>Meter Description</b></td>   <td class="text-right">'.$meter->info->description.'</td>   </tr>';
			if($meter->info->serial_number) $content .= '<tr>   <td><b>Meter Serial Number</b></td>   <td class="text-right">'.$meter->info->serial_number.'</td>   </tr>';
			if($meter->info->mpan) $content .= '<tr>   <td><b>MPRN</b></td>   <td class="text-right">'.$meter->info->mpan.'</td>   </tr>';

			$tariff = $meter->get_tariff_info();
			if($tariff) {
				if($tariff->supplier_name) $content .= '<tr>   <td><b>Supplier</b></td>   <td class="text-right">'.$tariff->supplier_name.'</td>   </tr>';
				if($tariff->description) $content .= '<tr>   <td><b>Tariff</b></td>   <td class="text-right">'.$tariff->description.'</td>   </tr>';
			}

			$content .= '
					</table>
				</div>
			';
			$content .= '<div style="margin-top: -20px; border-top: 1px solid #e5e5e5;"></div>';

			$content .= '
				<div style="padding: 20px;">
					<table class="table font-md">
			';

			$reading = $meter->get_latest_reading();
			if($reading) {
				$content .= '<tr>   <td><b>Last read</b></td>   <td class="text-right">'.App::format_datetime('d F Y', $reading->reading_date, 'Y-m-d').'</td>   </tr>';
				if($reading->reading_1) $content .= '<tr>   <td><b>Reading (Day)</b></td>   <td class="text-right">'.$reading->reading_1.'</td>   </tr>';
				if($reading->reading_2) $content .= '<tr>   <td><b>Reading (Night)</b></td>   <td class="text-right">'.$reading->reading_2.'</td>   </tr>';
				if($reading->reading_3) $content .= '<tr>   <td><b>Reading (Evening/Weekend)</b></td>   <td class="text-right">'.$reading->reading_2.'</td>   </tr>';
				if($reading->reading_total) $content .= '<tr>   <td><b>Reading Total</b></td>   <td class="text-right">'.$reading->reading_total.'</td>   </tr>';
			} else {
				$content .= '<tr>   <td><b>Last read</b></td>   <td class="text-right">never</td>   </tr>';
			}

			$content .= '
					</table>
				</div>
			';

		} else {
			$content = '';
			//No sub-meter selected.
		}
	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->header('title', '<p class="myWidget-title">Sub-meter Details</p><i class="myWidget-colorIcon eticon eticon-droplet eticon-droplet-color eticon-shadow" style="margin-top: 15px !important;"></i>');
	$ui_widget->footer = ' ';
	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->print_html();
?>
