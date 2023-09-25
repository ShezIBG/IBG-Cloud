<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'magenta';

	$building = null;
	switch($dashboard->type) {
		case Dashboard::DASHBOARD_TYPE_MAIN:
			$building = $user->get_default_building(Permission::ELECTRICITY_ENABLED);
			break;

		case Dashboard::DASHBOARD_TYPE_GAS:
			$building = $user->get_default_building(Permission::GAS_ENABLED);
			break;

		case Dashboard::DASHBOARD_TYPE_WATER:
			$building = $user->get_default_building(Permission::WATER_ENABLED);
			break;

		case Dashboard::DASHBOARD_TYPE_RENEWABLES:
			$building = $user->get_default_building(Permission::RENEWABLES_ENABLED);
			break;
	}

	if ($building) {
		$time_period = $dashboard->get_time_period($building->id);

		if($dashboard->type == Dashboard::DASHBOARD_TYPE_MAIN) {
			$reports = [];

			$add_reports = function($q) use (&$reports, $building) {
				$list = App::sql()->query("SELECT id, year, month, day, week_number, report_type, directory, tag FROM report_history WHERE building_id = '$building->id' AND $q;") ?: [];
				foreach($list as $r) {
					$reports[] = $r;
				}
			};

			if($time_period->value == Dashboard::TIME_PERIOD_YESTERDAY || $time_period->value == Dashboard::TIME_PERIOD_DAY) {
				// A single day is selected
				$offset = $time_period->value == Dashboard::TIME_PERIOD_YESTERDAY ? 1 : $time_period->day;
				$dt = strtotime("-{$offset} day");
				$y = date('Y', $dt);
				$m = date('m', $dt);
				$d = date('d', $dt);
				$wk = date('W', $dt);

				if(!$building->info->is_demo) {
					$add_reports("report_type = 'end_of_day' AND year = '$y' AND month = '$m' AND day = '$d'");
					$add_reports("report_type = 'weekly_electric_kWh_usage_by_day' AND year = '$y' AND week_number = '$wk'");
					$add_reports("report_type = 'end_of_month' AND year = '$y' AND month = '$m'");
				} else {
					$add_reports("report_type = 'end_of_day' ORDER BY id DESC LIMIT 1");
					$add_reports("report_type = 'weekly_electric_kWh_usage_by_day' ORDER BY id DESC LIMIT 1");
					$add_reports("report_type = 'end_of_month'  ORDER BY id DESC LIMIT 1");

					if($building->info->is_demo) {
						foreach($reports as $r) {
							if($r->year) $r->year = $y;
							if($r->month) $r->month = $m;
							if($r->day) $r->day = $d;
							if($r->week_number) $r->week_number = $wk;
						}
					}
				}

			} else if($time_period->value == Dashboard::TIME_PERIOD_LAST_WEEK) {
				// A week is selected
				$sdt = strtotime('last week');      // Start of the week
				$edt = strtotime("+6 days", $sdt);  // End of the week
				$wk = date('W', $sdt);
				$sy = date('Y', $sdt);
				$sm = date('m', $sdt);
				$ey = date('Y', $edt);
				$em = date('m', $edt);

				if(!$building->info->is_demo) {
					$add_reports("report_type = 'weekly_electric_kWh_usage_by_day' AND year = '$ey' AND week_number = '$wk'");
					$add_reports("report_type = 'end_of_month' AND year = '$sy' AND month = '$sm'");

					// Add another monthly report if week crosses month boundary
					if($sm != $em) $add_reports("report_type = 'end_of_month' AND year = '$ey' AND month = '$em'");
				} else {
					$add_reports("report_type = 'weekly_electric_kWh_usage_by_day' ORDER BY id DESC LIMIT 1");
					$add_reports("report_type = 'end_of_month' ORDER BY id DESC LIMIT 1");

					if($building->info->is_demo) {
						foreach($reports as $r) {
							if($r->year) $r->year = $sy;
							if($r->month) $r->month = $sm;
							if($r->week_number) $r->week_number = $wk;
						}
					}
				}
			} else if($time_period->value == Dashboard::TIME_PERIOD_LAST_MONTH) {
				// A month is selected
				$dt = strtotime('first day of last month');
				$y = date('Y', $dt);
				$m = date('m', $dt);

				if(!$building->info->is_demo) {
					$add_reports("report_type = 'end_of_month' AND year = '$y' AND month = '$m'");
				} else {
					$add_reports("report_type = 'end_of_month' ORDER BY id DESC LIMIT 1");

					if($building->info->is_demo) {
						foreach($reports as $r) {
							if($r->year) $r->year = $y;
							if($r->month) $r->month = $m;
						}
					}
				}

			}

			if($reports) {
				$ui_table = $ui->create_datatable($reports, [
					'static' => true,
					'in_widget' => false,
					'bordered' => false,
					'striped' => false,
					'default_col' => false,
					'columns' => true,
					'hover' => false
				]);

				$ui_table->hidden = ['id', 'month', 'day', 'week_number', 'tag'];

				$ui_table
					->cell('year', [
						'content' => function($row, $value) {
							switch($row->report_type) {
								case 'end_of_day':
									return sprintf('%02d/%02d/%d', $row->day, $row->month, $row->year);
								case 'weekly_electric_kWh_usage_by_day':
									return "$row->year week $row->week_number";
								case 'end_of_month':
									$monthName = date('F', mktime(0, 0, 0, $row->month, 10));
									return "$row->year $monthName";
							}
						},
						'attr' => [ 'style' => 'padding-right: 15px' ]
					])
					->cell('report_type', [
						'content' => function($row, $value) {
							$desc = '';
							$tag = $row->tag;
							switch($row->report_type) {
								case 'end_of_day':                       $desc = 'Daily summary'; break;
								case 'weekly_electric_kWh_usage_by_day': $desc = 'End of week'; break;
								case 'end_of_month':                     $desc = 'End of month'; break;
							}

							if($tag) {
								$desc .= " ($tag)";
							} else if(isset($row->directory) && preg_match("/by_hour/i", $row->directory)) {
								// Fallback to old way
								$desc .= ' (Hourly)';
							}
							return $desc;
						}
					])
					->cell('directory', [
						'class' => 'text-right',
						'attr' => [ 'style' => 'padding-right: 15px' ],
						'content' => function($row, $value) {
							return '<a href="'.APP_URL.'/ajax/get/get_report?id='.$row->id.'&view=1" target="_blank" class="myText-bolder myLink-colorC"><i class="eticon eticon-search"></i> View</a>';
						}
					]);

				$content = $ui_table->print_html(true);
			} else {
				$content = '<p class="myText-noData">No reports found.</p>';
			}
		} else if($dashboard->type == Dashboard::DASHBOARD_TYPE_GAS) {
			$reports = [
				[
					'title' => 'Gas periods report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-periods&meter_type=G&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				],
				[
					'title' => 'Gas meter readings report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-readings&meter_type=G&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				],
				[
					'title' => 'Gas tariff report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-tariffs&meter_type=G&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				]
			];

			$ui_table = $ui->create_datatable($reports, [
				'static' => true,
				'in_widget' => false,
				'bordered' => false,
				'striped' => false,
				'default_col' => false,
				'columns' => true,
				'hover' => false
			]);

			$ui_table
				->cell('action', [
					'class' => 'text-right',
					'attr' => [ 'style' => 'padding-right: 15px' ]
				]);

			$content = $ui_table->print_html(true);
		} else if($dashboard->type == Dashboard::DASHBOARD_TYPE_WATER) {
			$reports = [
				[
					'title' => 'Water periods report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-periods&meter_type=W&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				],
				[
					'title' => 'Water meter readings report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-readings&meter_type=W&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				],
				[
					'title' => 'Water tariff report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-tariffs&meter_type=W&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				]
			];

			$ui_table = $ui->create_datatable($reports, [
				'static' => true,
				'in_widget' => false,
				'bordered' => false,
				'striped' => false,
				'default_col' => false,
				'columns' => true,
				'hover' => false
			]);

			$ui_table
				->cell('action', [
					'class' => 'text-right',
					'attr' => [ 'style' => 'padding-right: 15px' ]
				]);

			$content = $ui_table->print_html(true);

		} else if($dashboard->type == Dashboard::DASHBOARD_TYPE_RENEWABLES) {
			$reports = [
				[
					'title' => 'Renewable periods report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-periods&meter_type=E&meter_direction=generation&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				],
				[
					'title' => 'Renewable meter readings report',
					'action'   => '<a class="myText-bolder myLink-colorC" href="'.APP_URL.'/ajax/get/get_meter_report?type=building-readings&meter_type=E&meter_direction=generation&building_id='.$building->id.'" target="_blank"><i class="eticon eticon-search"></i> View</a>'
				]
			];

			$ui_table = $ui->create_datatable($reports, [
				'static' => true,
				'in_widget' => false,
				'bordered' => false,
				'striped' => false,
				'default_col' => false,
				'columns' => true,
				'hover' => false
			]);

			$ui_table
				->cell('action', [
					'class' => 'text-right',
					'attr' => [ 'style' => 'padding-right: 15px' ]
				]);

			$content = $ui_table->print_html(true);
		}

		$ui_widget->header('title', '<p class="myWidget-title">Reports</p><i class="myWidget-colorIcon eticon eticon-clipboard eticon-clipboard-color eticon-shadow" style="margin-top: 15px !important;"></i>');
		$ui_widget->footer = ' ';

	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed reports-widget';
	$ui_widget->print_html();
?>
