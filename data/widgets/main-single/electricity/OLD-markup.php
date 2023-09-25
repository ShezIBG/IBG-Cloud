
<?php
	$table_js = '';
	$table_id = '';

	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'yellow';

	if ($building = $user->get_default_building(Permission::ELECTRICITY_ENABLED)) {
		$time_period = $dashboard->get_time_period($building->id);
		$sum_data = App::sql()->query_row("SELECT SUM({$time_period->yec_kwh_used}) AS kwh_used, SUM({$time_period->yec_cost}) AS cost FROM `{$time_period->yec_table}` WHERE category_id = ".Eticom::CATEGORY_BILLING." AND building_id = $building->id");
		$total_kwh = $sum_data ? $sum_data->kwh_used : 0;
		$total_cost = $sum_data ? $sum_data->cost : 0;

		$data = [];

		$billing_category = Eticom::CATEGORY_BILLING;
		$electricity_data = App::sql()->query(
			"SELECT
				MIN(IF(c.description IS NOT NULL, c.description, yec.cat_desc)) AS cat_desc,
				SUM(yec.{$time_period->yec_kwh_used}) AS kwh_used,
				SUM(yec.{$time_period->yec_cost}) AS cost,
				yec.category_id
			FROM `{$time_period->yec_table}` AS yec
			LEFT JOIN category AS c ON yec.category_id = c.id
			WHERE
				yec.category_id <> '$billing_category'
				AND yec.building_id = '$building->id'
				AND yec.category_id NOT IN (SELECT category_id FROM building_category_settings WHERE building_id = '$building->id' AND hide_from_electricity_widget = 1)
			GROUP BY category_id
			ORDER BY kwh_used DESC
		");

		if ($electricity_data) {
			foreach ($electricity_data as $row) {
				$data_row = new stdClass;
				$data_row->{'Category name'} = '<a href="#" class="row-detail" style="color:inherit">'.$row->cat_desc.'</a>';
				$data_row->kWh = number_format($row->kwh_used, 2);
				$data_row->Cost = '&pound;'.number_format($row->cost, 2);

				$detail_html = '';
				$detail_flot_data = '';

				$circuit_data = App::sql()->query(
					"SELECT
						MIN(IF(ct.long_description IS NOT NULL, ct.long_description, cce.ct_long_description)) AS description,
						SUM(cce.{$time_period->cce_kwh_used}) AS kwh,
						SUM(cce.{$time_period->cce_cost}) AS cost
					FROM ct_category_eod AS cce
					LEFT JOIN ct ON ct.id = cce.ct_id
					WHERE cce.building_id = $building->id AND cce.category_id = $row->category_id
					GROUP BY ct_id
					ORDER BY kwh DESC
				");

				$perc_total = $row->kwh_used ?: 0;

				if ($circuit_data) {
					$flot_data = [];
					$other_kwh = 0;
					$other_perc = 0;
					foreach ($circuit_data as $circuit_data_row) {
						$perc_current = $circuit_data_row->kwh ?: 0;
						$percent = $perc_total > 0 ? round($perc_current / $perc_total * 100) : 0;

						$detail_html .= '
							<tr class="details">
								<td>&nbsp;</td>
								<td>'.$circuit_data_row->description.'</td>
								<td text-right fixed-numbers">'.number_format($circuit_data_row->kwh, 2).'</td>
								<td text-right fixed-numbers" style="padding-right: 15px;">&pound;'.number_format($circuit_data_row->cost, 2).'</td>
							</tr>';

						if($percent >= 2) {
							$flot_data[] = [
								'label' => '<table><tr><td class="fixed-numbers" style="width:2.5em;color:#999;"><label style="margin:0 0 0 -5px;">'.$percent.'%</label></td><td>'.$circuit_data_row->description.'</td></tr></table>',
								'data' => $circuit_data_row->kwh
							];
						} else {
							$other_perc += $perc_total > 0 ? $perc_current / $perc_total * 100 : 0;
							$other_kwh += $circuit_data_row->kwh;
						}
					}

					if($other_perc > 0 && $other_kwh > 0) {
						$percent = round($other_perc);
						$flot_data[] = [
							'label' => '<table><tr><td class="fixed-numbers" style="width:2.5em;color:#999;"><label style="margin:0 0 0 -5px;">'.$percent.'%</label></td><td>Other</td></tr></table>',
							'data' => $other_kwh,
							'shadowSize' => 10
						];
					}

					$detail_html = App::clean_str($detail_html, false);
					$detail_flot_data = '<input type="hidden" class="js-flot-data details" value="'.App::clean_str(json_encode($flot_data)).'">';
				}

				$data_row->Detail = $detail_html;
				$data_row->DetailData = $detail_flot_data;

				$data[] = $data_row;
			}
		}

		$ui_table = $ui->create_datatable($data, [
			'static' => true,
			'in_widget' => false,
			'bordered' => false,
			'striped' => false,
			'default_col' => false,
			'columns' => true,
			'hover' => false,
			'row_details' => '{{Detail}}',
			'row_details_opened' => function($table_id) {
				return '
					nTr.after($("<div></div>").html(value).text());
					var tds = nTr.find(\'td\'),
						flotJSON = tds.eq(5).find(\'input.js-flot-data\').val(),
						flotData = flotJSON ? $.parseJSON(flotJSON) : [];

					// Close other opened details
					var otherTr = $(\'#'.$table_id.'\').find(\'tbody tr:not(.details)\').not(nTr);
					otherTr.each(function() {
						var $this = $(this),
							isOpen = $this.nextUntil(":not(.details)", ".details").length > 0;
						if (isOpen) $this.find(\'a i[data-toggle="row-detail"]\').eq(0).trigger(\'click\');
					});

					$(\'#your-electricity-selected-category\').text(tds.eq(1).text());
					if(flotJSON) {
						initFlot(\'#your-electricity-chart\', flotData, {
							series: {
								pie: {
									show: true,
									radius: 0.7,
									stroke: { width: 0 }
								}
							},
							legend: {
								// show: true,
								noColumns: 1,
								container: $(\'#your-electricity-legend\'),
								backgroundOpacity: 1
							}
						});
					}
					';
			},
			'row_detail_icons' => [ 'opened' => 'caret-down eticon-lg', 'closed' => 'caret-right eticon-lg' ]
		]);

		$ui_table->hidden = ['Detail', 'DetailData'];

		$ui_table
			->cell('Cost', [
				'class' => 'text-right fixed-numbers',
				'attr' => [ 'style' => 'padding-right: 15px' ]
			])
			->col('Cost', [
				'class' => 'text-right',
				'attr' => [ 'style' => 'padding-right: 15px' ]
			])
			->cell('kWh', [ 'class' => 'text-right fixed-numbers' ])
			->col('kWh', [ 'class' => 'text-right' ]);

		$table_js = $ui_table->print_js(true, false);
		$table_id = $ui_table->id;
		// JEANE CHANGE
		$content = '
			<p class="myWidget-title-inside">Top Consuming Categories</p>
			<div class="widget-row">
				'.$ui_table->print_html(true).'
			</div>
			<hr class="myLine2"/><div class="dashboard-widget-content-separator"><p class="myWidget-title-inside2">How you\'ve used your power</p></div>
			<div class="widget-row widget-row-chart display-flex no-flex">
				<div class="chart" id="your-electricity-chart"></div>
				<div>
					<p><h5 class="font-md" id="your-electricity-selected-category">-</h5></p>
					<div id="your-electricity-legend" class="chart-legend" style="font-size:larger"></div>
				</div>
			</div>';
//JEANE CHANGE
		$ui_widget->header('title', '
		 <p class="myWidget-title">Your Electricity</p><i class="myWidget-colorIcon eticon eticon-bolt eticon-shadow"></i>
			<div class="clearfix"></div>
			<div class="widget-row display-flex no-flex">
				<div>
					<div class="myWidget-title-total">Power Used</div>
					<div class="myWidget-title-value padding-top-5">'.$total_kwh.' kWh</div>
				</div>
				<div>
					<div class="myWidget-title-total">Cost</div>
					<div class="myWidget-title-value padding-top-5">&pound;'.number_format($total_cost, 2).'</div>
				</div>
			</div> <hr class="myLine1" />');

	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';

	$ui_widget->footer = ' ';

	$ui_widget->print_html();
?>

<script>
	<?= $table_js; ?>
	$('#<?= $table_id; ?>').on('click', 'a.row-detail', function(e) {
		e.preventDefault();
		$(this).closest('tr').find('a i[data-toggle="row-detail"]').click();
	});

	$('#<?= $table_id; ?> a i[data-toggle="row-detail"]').eq(0).trigger('click');
</script>
