<?php
	require_once '../init.ajax.php';

	list($building_id, $lease_id) = App::get(['building_id', 'lease_id'], '', true);

	$lease = new Lease($lease_id);
	$area = $lease->get_area_info();
	$owner_occupied = $area && $area->is_owner_occupied;
?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-red">Edit <?= $owner_occupied ? 'contract' : 'lease' ?></h4>
</div>
<div class="modal-body no-padding">

<?php
	if (!$building_id || !$lease) {
		$ui->print_danger('Building not found.');
	} else {
		$building = new Building($building_id);

		if ($lease->info && $building->validate()) {
			$fields = [];

			if($lease->is_future()) {
				$fields = array_merge($fields, [
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
					]
				]);
			}

			$fields = array_merge($fields, [
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
				'payment_type' => [
					'type' => 'select2',
					'col' => 6,
					'properties' => [
						'id' => 'popup_payment_type',
						'data' => Lease::list_payment_types(),
						'value' => 'value',
						'display' => 'description',
						'label' => 'Bills paid by',
						'selected' => $lease->info->payment_type
					]
				],
				'account_ref' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Account reference',
						'value' => $lease->info->account_ref
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
			]);

			$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
			$form->id = 'lease-edit-form';

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
		var $form = $("#lease-edit-form");

		$('#popup_payment_type,#popup_term_units').initSelect2();

		$("#popup_lease_start_date").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		$('#popup_lease_renewal_alert_date').datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
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
				$.post('<?= APP_URL.'/ajax/post/update_lease'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
