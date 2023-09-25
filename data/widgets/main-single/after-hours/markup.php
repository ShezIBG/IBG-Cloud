
<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'blueDark';

	if ($building = $user->get_default_building(Permission::ELECTRICITY_ENABLED)) {
		$time_period = $dashboard->get_time_period($building->id);

		$total_kwh = 0;
		$total_cost = 0;
		$kwh_percent = 0;
		$cost_percent = 0;

		$sum_data = App::sql()->query_row("
			SELECT
				SUM({$time_period->ahc_kwh_used_out}) AS koh,
				SUM({$time_period->ahc_kwh_used_in}) AS kih,
				SUM({$time_period->ahc_cost_out}) AS coh,
				SUM({$time_period->ahc_cost_in}) AS cih
			FROM `{$time_period->ahc_table}`
			WHERE category_id = 11 AND building_id = $building->id
		");

		if ($sum_data) {
			$total_kwh = $sum_data->koh;
			$total_kwh_i = $sum_data->kih;
			$total_cost = $sum_data->coh;
			$total_cost_i = $sum_data->cih;

			$d = $total_kwh + $total_kwh_i;
			$kwh_percent = $d != 0 ? ($total_kwh / $d) * 100 : 0;

			$d = $total_cost + $total_cost_i;
			$cost_percent = $d != 0 ? ($total_cost / $d) * 100 : 0;
		}

		$after_hours_data = App::sql()->query(
			"SELECT
				DISTINCT ct_id,
				MIN(IF(ct.long_description IS NOT NULL, ct.long_description, cce.ct_long_description)) AS 'Circuit name',
				SUM(cce.{$time_period->cce_kwh_used_out}) AS kWh,
				SUM(cce.{$time_period->cce_cost_out}) AS Cost
			FROM ct_category_eod AS cce
			LEFT JOIN ct ON ct.id = cce.ct_id
			WHERE cce.{$time_period->cce_kwh_used_out} > 0 AND cce.building_id = $building->id
			GROUP BY cce.ct_id, cce.category_id
			ORDER BY kWh DESC") ?: [];

		$ui_table = $ui->create_datatable($after_hours_data, [
			'static' => true,
			'in_widget' => false,
			'bordered' => false,
			'striped' => false,
			'default_col' => false,
			'columns' => true,
			'hover' => false
		]);

		$ui_table->hidden = ['ct_id'];

		$ui_table
			->cell('Cost', [
				'content' => function($row, $value) {
					return '&pound;'.number_format($value, 2);
				},
				'class' => 'text-right fixed-numbers',
				'attr' => [ 'style' => 'padding-right: 15px' ]
			])
			->cell('kWh', [
				'class' => 'text-right fixed-numbers',
				'content' => function($row, $value) {
					return number_format($value, 2);
				}
			])
			->col('Cost', [
				'class' => 'text-right',
				'attr' => [ 'style' => 'padding-right: 15px' ]
			])
			->col('kWh', [
				'class' => 'text-right'
			]);
// JEANE CHANGE
		$content = '
			<p class="myWidget-title-inside">Top consuming Circuits</p>
			<div class="widget-row">
				'.$ui_table->print_html(true).'
			</div>';
//JEANE CHANGE
		$ui_widget->header('title', '
			<p class="myWidget-title">After Hours</p><i class="myWidget-colorIcon eticon eticon-moon eticon-shadow"></i>
			<div class="clearfix"></div>
			<div class="widget-row display-flex">
				<div>
					<div class="myWidget-title-total-perc" style="position: absolute; bottom: 0; right: 0;">'.number_format($kwh_percent, 2).'%</div>
					<div class="myWidget-title-total">Power Used</div>
					<div class="myWidget-title-value padding-top-5">'.number_format($total_kwh, 2).' kWh</div>
				</div>
				<div class="space"></div>
				<div>
					<div class="myWidget-title-total-perc" style="position: absolute; bottom: 0; right: 0;">'.number_format($cost_percent, 2).'%</div>
					<div class="myWidget-title-total">Cost</div>
					<div class="myWidget-title-value padding-top-5">&pound;'.number_format($total_cost, 2).'</div>
				</div>
			</div><hr class="myLine1"/>');
		//JEANE CHANGE
		if (Module::is_enabled(Module::SETTINGS) && Permission::get_building($building->id)->check(Permission::ADMIN)) {
			// $ui_widget->footer = '<a class="myLink2" href="'.APP_URL.'/settings#view/config/afterhours/'.$building->id.'">Change your working times</a> <a href="'.APP_URL.'/settings#view/config/afterhours/'.$building->id.'" class="pull-right"><i class="eticon eticon-arrow-right"></i></a>';
			$ui_widget->footer = '<a class="myLink2" style="padding-left: 8px; padding-right: 8px;" href="'.APP_URL.'/settings#view/config/afterhours/'.$building->id.'">
			Change your working times <i class="eticon eticon-arrow-right pull-right"></i>
			</a>';
		} else {
			$ui_widget->footer = ' ';
		}

	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->print_html();
?>
