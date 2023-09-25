<?php
	require_once '../init.ajax.php';

	list($tariff_id, $client_id) = App::get(['tariff_id', 'client_id'], 0, true);
?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-g"><strong><?= $tariff_id ? 'Edit Gas Tariff' : 'Add a Gas tariff'; ?></strong></h4>
</div>
<div class="modal-body no-padding">

<?php
	$standing_charge = 0;
	$cost_per_kwh = 0;

	if($tariff_id) {
		$tariff = App::sql()->query_row("SELECT * FROM tariff_gas WHERE id = '$tariff_id';");

		if($tariff->standing_charge_non_dd > $standing_charge) $standing_charge = $tariff->standing_charge_non_dd;
		if($tariff->standing_charge_dd > $standing_charge) $standing_charge = $tariff->standing_charge_dd;

		if($tariff->cost_per_kwh_non_dd > $cost_per_kwh) $cost_per_kwh = $tariff->cost_per_kwh_non_dd;
		if($tariff->cost_per_kwh_dd > $cost_per_kwh) $cost_per_kwh = $tariff->cost_per_kwh_dd;
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
				'data' => App::sql()->query("SELECT id, description FROM energy_supplier WHERE energy_type_id IN ('gas', 'electric & gas') ORDER BY description"),
				'value' => 'id',
				'display' => 'description',
				'selected' => $tariff ? $tariff->supplier_id : '',
				'label' => 'Supplier'
			]
		],
		'standing_charge' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Standing charge',
				'value' => App::format_number($standing_charge)
			]
		],
		'cost_per_kwh' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Cost per kWh',
				'value' => App::format_number($cost_per_kwh)
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
				$.post('<?= APP_URL.($tariff_id ? '/ajax/post/update_gas_tariff' : '/ajax/post/add_gas_tariff'); ?>', $form.serialize(), function(data) {
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
					$.post('<?= APP_URL ;?>/ajax/post/delete_gas_tariff', $form.serialize(), function(data) {
						$.modalResult(data, function() {
							refreshTariffs('');
						});
					});
				}
			});
		});
	}

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
