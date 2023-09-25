<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-red">Lease invoice address</h4>
</div>
<div class="modal-body no-padding">

<?php
	list($building_id, $lease_id) = App::get(['building_id', 'lease_id'], '', true);

	if (!$building_id) {
		$ui->print_danger('Building not found.');
	} else {
		$building = new Building($building_id);
		$lease = new Lease($lease_id);

		if ($lease->info && $building->validate()) {
			$fields = [
				'invoice_address_1' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Line 1',
						'value' => $lease->info->invoice_address_1
					]
				],
				'invoice_address_2' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Line 2',
						'value' => $lease->info->invoice_address_2
					]
				],
				'invoice_address_3' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Line 3',
						'value' => $lease->info->invoice_address_3
					]
				],
				'invoice_posttown' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Town',
						'value' => $lease->info->invoice_posttown
					]
				],
				'postcode' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Postcode',
						'value' => $lease->info->postcode
					]
				],
				'building_id' => [
					'type' => 'hidden',
					'properties' => [ 'value' => $building->id ]
				],
				'lease_id' => [
					'type' => 'hidden',
					'properties' => [ 'value' => $lease_id ]
				]
			];

			$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
			$form->id = 'lease-invoice-address-form';

			$form->footer(function() use ($ui) {
				return implode(' ', [
					$ui->create_button('Save', 'success')->attr([ 'type' => 'submit' ])->print_html(true),
					$ui->create_button('Cancel', 'default')->attr([ 'data-dismiss' => 'modal' ])->print_html(true)
				]);
			});

			$form->print_html();
		} else {
			$ui->print_danger('Lease not found.');
		}
	}
?>

</div>

<script>
	var runFunction = function() {
		var $form = $("#lease-invoice-address-form");

		$form.validate({
			rules: {
				postcode: { required : true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/update_lease_invoice_address'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
