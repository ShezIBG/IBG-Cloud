<?php
	require_once '../init.ajax.php';

	list($tariff_id, $client_id) = App::get(['tariff_id', 'client_id'], 0, true);
?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-w"><strong><?= $tariff_id ? 'Edit Water Tariff' : 'Add a Water tariff'; ?></strong></h4>
</div>
<div class="modal-body no-padding">

<?php
	$tariff = $tariff_id ? App::sql()->query_row("SELECT * FROM tariff_water WHERE id = '$tariff_id';") : [];

	$fields = [
		'description' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Tariff Name',
				'value' => $tariff ? $tariff->description : ''
			]
		],
		'blank' => [
			'type' => 'blank',
			'col' => 6
		],
		'water_supplier_id' => [
			'type' => 'select2',
			'col' => 6,
			'properties' => [
				'id' => 'water_supplier_id',
				'data' => App::sql()->query("SELECT id, description FROM energy_supplier WHERE energy_type_id IN ('water') ORDER BY description"),
				'value' => 'id',
				'display' => 'description',
				'selected' => $tariff ? $tariff->water_supplier_id : '',
				'label' => 'Water supplier'
			]
		],
		'waste_supplier_id' => [
			'type' => 'select2',
			'col' => 6,
			'properties' => [
				'id' => 'waste_supplier_id',
				'data' => App::sql()->query("SELECT id, description FROM energy_supplier WHERE energy_type_id IN ('water') ORDER BY description"),
				'value' => 'id',
				'display' => 'description',
				'selected' => $tariff ? $tariff->waste_supplier_id : '',
				'label' => 'Waste supplier'
			]
		],
		'water_standing_charge_pence_per_day' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Daily standing charge (water)',
				'value' => App::format_number($tariff->water_standing_charge_pence_per_day ?: 0)
			]
		],
		'waste_standing_charge' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Daily standing charge (waste)',
				'value' => App::format_number($tariff->waste_standing_charge ?: 0)
			]
		],
		'water_volumetric_charge_per_m3' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Cost per m<sup>3</sup> (water)',
				'value' => App::format_number($tariff->water_volumetric_charge_per_m3 ?: 0)
			]
		],
		'waste_volumetric_charge_per_m3' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Cost per m<sup>3</sup> (waste)',
				'value' => App::format_number($tariff->waste_volumetric_charge_per_m3 ?: 0)
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

		$('#water_supplier_id,#waste_supplier_id').initSelect2();

		$form.validate({
			rules: {
				description: { required: true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.($tariff_id ? '/ajax/post/update_water_tariff' : '/ajax/post/add_water_tariff'); ?>', $form.serialize(), function(data) {
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
					$.post('<?= APP_URL ;?>/ajax/post/delete_water_tariff', $form.serialize(), function(data) {
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
