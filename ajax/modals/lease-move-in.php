<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-red">Move in</h4>
</div>
<div class="modal-body no-padding">

<?php
	list($building_id, $lease_id) = App::get(['building_id', 'lease_id'], '', true);

	if (!$building_id) {
		$ui->print_danger('Building not found.');
	} else {
		$building = new Building($building_id);
		$lease = new Lease($lease_id);

		if(!$lease->is_future()) {
			$ui->print_danger('This is not a future lease.');
		} else if ($lease->info && $building->validate()) {
			$fields = [
				'lease_start_date' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id' => 'popup_lease_start_date',
						'placeholder' => 'dd/mm/yyyy',
						'label' => 'Start date',
						'value' => $lease->info->lease_start_date ? App::format_datetime('d/m/Y', $lease->info->lease_start_date, 'Y-m-d') : ''
					]
				],
				'term' => [
					'type' => 'input',
					'col' => 3,
					'properties' => [
						'label' => 'Contract term',
						'value' => $lease->info->term
					]
				],
				'term_units' => [
					'type' => 'select2',
					'col' => 3,
					'properties' => [
						'id' => 'popup_term_units',
						'data' => Lease::list_term_units(),
						'value' => 'value',
						'display' => 'description',
						'label' => '&nbsp;',
						'selected' => $lease->info->term_units
					]
				],
				'lease_renewal_alert_date' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id' => 'popup_lease_renewal_alert_date',
						'placeholder' => 'dd/mm/yyyy',
						'value' => $lease->info->lease_renewal_alert_date ? App::format_datetime('d/m/Y', $lease->info->lease_renewal_alert_date, 'Y-m-d') : '',
						'label' => 'Renewal due'
					]
				],
				'account_ref' => [
					'type'       => 'input',
					'col'        => 6,
					'properties' => [
						'label' => 'Account reference',
						'value' => $lease->info->account_ref
					],
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
			$form->id = 'lease-move-in-form';

			$form->footer(function() use ($ui) {
				return implode(' ', [
					$ui->create_button('Move In', 'success')->attr([ 'type' => 'submit' ])->print_html(true),
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
		var $form = $("#lease-move-in-form");

		$('#popup_term_units').initSelect2();

		$("#popup_lease_start_date").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			showButtonPanel: true,
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		$('#popup_lease_renewal_alert_date').datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			showButtonPanel: true,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		$form.validate({
			rules: {
				lease_start_date: { required : true },
				term: { required : true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/lease_move_in'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
