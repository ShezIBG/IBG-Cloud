<?php
	require_once '../init.ajax.php';

	list($tariff_id, $client_id) = App::get(['tariff_id', 'client_id'], 0, true);
?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-e"><strong><?= $tariff_id ? 'Edit Electricity Tariff' : 'Add an Electricity tariff'; ?></strong></h4>
</div>
<div class="modal-body no-padding">

<?php
	$daily_standing_charge = 0;
	$unit_rate_day = 0;
	$unit_rate_night = 0;

	if($tariff_id) {
		$tariff = App::sql()->query_row("SELECT * FROM tariff_electricity WHERE id = '$tariff_id';");

		if($tariff->standard_tariff_non_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->standard_tariff_non_dd_daily_standing_charge;
		if($tariff->standard_tariff_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->standard_tariff_dd_daily_standing_charge;
		if($tariff->economy7_tariff_non_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->economy7_tariff_non_dd_daily_standing_charge;
		if($tariff->economy7_tariff_dd_daily_standing_charge > $daily_standing_charge) $daily_standing_charge = $tariff->economy7_tariff_dd_daily_standing_charge;

		if($tariff->standard_tariff_non_dd_unit_rate > $unit_rate_day) $unit_rate_day = $tariff->standard_tariff_non_dd_unit_rate;
		if($tariff->standard_tariff_dd_unit_rate > $unit_rate_day) $unit_rate_day = $tariff->standard_tariff_dd_unit_rate;
		if($tariff->economy7_tariff_non_dd_unit_rate_day > $unit_rate_day) $unit_rate_day = $tariff->economy7_tariff_non_dd_unit_rate_day;
		if($tariff->economy7_tariff_dd_unit_rate_day > $unit_rate_day) $unit_rate_day = $tariff->economy7_tariff_dd_unit_rate_day;

		if($tariff->economy7_tariff_non_dd_unit_rate_night > $unit_rate_night) $unit_rate_night = $tariff->economy7_tariff_non_dd_unit_rate_night;
		if($tariff->economy7_tariff_dd_unit_rate_night > $unit_rate_night) $unit_rate_night = $tariff->economy7_tariff_dd_unit_rate_night;
	} else {
		$tariff = [];
	}

	$fields = [
		'description' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Tariff Name',
				'value' => $tariff ? $tariff->description : ''
			]
		],
		'supplier_id' => [
			'type' => 'select2',
			'col' => 6,
			'properties' => [
				'id' => 'supplier_id',
				'data' => App::sql()->query("SELECT id, description FROM energy_supplier WHERE energy_type_id IN ('electric', 'electric & gas') ORDER BY description"),
				'value' => 'id',
				'display' => 'description',
				'selected' => $tariff ? $tariff->supplier_id : '',
				'label' => 'Supplier'
			]
		],
		'business_contract_notice_period_days' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Notice Period (days)',
				'value' => $tariff ? $tariff->business_contract_notice_period_days : ''
			]
		],
		'daily_standing_charge' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Daily standing charge',
				'value' => App::format_number($daily_standing_charge)
			]
		],
		'unit_rate_day' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Cost / kWh (day)',
				'value' => App::format_number($unit_rate_day)
			]
		],
		'unit_rate_night' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Cost / kWh (night)',
				'value' => App::format_number($unit_rate_night)
			]
		],
		'reactive_power_rate_pounds_per_kva' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Reactive power rate (&pound;/KVA)',
				'value' => App::format_number($tariff ? $tariff->reactive_power_rate_pounds_per_kva : 0)
			]
		],
		'CCL_pence_per_unit' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Climate change rate',
				'value' => App::format_number($tariff ? $tariff->CCL_pence_per_unit : 0)
			]
		],
		'CCL_cost_pounds' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Climate change cost',
				'value' => '0.00'
			]
		],
		'settlement_charges_pounds_per_year' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Settlement/Agent charges (&pound;/year)',
				'value' => App::format_number($tariff ? $tariff->settlement_charges_pounds_per_year : 0)
			]
		],
		'excess_capacity_rate_pounds_per_kva' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Excess capacity rate',
				'value' => App::format_number($tariff ? $tariff->excess_capacity_rate_pounds_per_kva : 0)
			]
		],
		'client_id' => [
			'type' => 'hidden',
			'properties' => [ 'value' => $client_id ]
		]
	];

	if($tariff_id) {
		$fields['tariff_id'] = [
			'type' => 'hidden',
			'properties' => [ 'value' => $tariff_id ]
		];
	}

	$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
	$form->id = 'tariff-details-form';

	$form->footer(function() use ($ui, $tariff_id) {
		$buttons = [];
		if($tariff_id) {
			$buttons[] = $ui->create_button('Save', 'success')->attr([ 'type' => 'submit' ])->print_html(true);
			$buttons[] = $ui->create_button('Delete', 'danger')->attr([ 'id' => 'delete-tariff' ])->print_html(true);
		} else {
			$buttons[] = $ui->create_button('Add', 'success')->attr([ 'type' => 'submit' ])->print_html(true);
		}
		$buttons[] = $ui->create_button('Cancel', 'default')->attr([ 'data-dismiss' => 'modal' ])->print_html(true);

		return implode(' ', $buttons);
	});

	$form->print_html();
?>

</div>
<script>
	var runFunction = function() {
		var $form = $("#tariff-details-form");

		$('#supplier_id').initSelect2();

		$form.validate({
			rules: {
				description: { required: true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.($tariff_id ? '/ajax/post/update_electricity_tariff' : '/ajax/post/add_electricity_tariff'); ?>', $form.serialize(), function(data) {
					$.modalResult(data, function() {
						refreshTariffs(data.data);
					});
				});
			}
		});

		$('#delete-tariff').on('click', function(e) {
			e.preventDefault();
			$.messagebox('Are you sure you want to delete this record?', function(btn) {
				if (btn == "Yes") {
					$.post('<?= APP_URL ;?>/ajax/post/delete_electricity_tariff', $form.serialize(), function(data) {
						$.modalResult(data, function() {
							refreshTariffs('');
						});
					});
				}
			});
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
