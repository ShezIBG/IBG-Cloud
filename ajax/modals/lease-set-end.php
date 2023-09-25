<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-red">Set moving out date</h4>
</div>
<div class="modal-body no-padding">

<?php
	list($building_id, $lease_id) = App::get(['building_id', 'lease_id'], '', true);

	if (!$building_id) {
		$ui->print_danger('Building not found.');
	} else {
		$building = new Building($building_id);
		$lease = new Lease($lease_id);

		if(!$lease->is_current()) {
			$ui->print_danger('This is not a current lease.');
		} else if ($lease->info && $building->validate()) {
			$fields = [
				'lease_end_date' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id' => 'popup_lease_end_date',
						'placeholder' => 'dd/mm/yyyy',
						'label' => 'End date',
						'value' => $lease->info->lease_end_date ? App::format_datetime('d/m/Y', $lease->info->lease_end_date, 'Y-m-d') : ''
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
			$form->id = 'lease-set-end-form';

			$form->footer(function() use ($ui) {
				return implode(' ', [
					$ui->create_button('Set', 'danger')->attr([ 'type' => 'submit' ])->print_html(true),
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
		var $form = $("#lease-set-end-form");

		$("#popup_lease_end_date").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			showButtonPanel: true,
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		$form.validate({
			rules: {
				lease_end_date: { required : true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/lease_set_end'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
