<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-red">Edit miscellaneous bill</h4>
</div>
<div class="modal-body no-padding">

<?php
	list($building_id, $lease_id) = App::get(['building_id', 'lease_id'], '', true);

	if (!$building_id) {
		$ui->print_danger('Building not found.');
	} else {
		$building = new Building($building_id);
		$lease = new Lease($lease_id);
		$today = strtotime('today');

		if(!$lease->is_current()) {
			$ui->print_danger('You can only edit bills on current leases.');
		} else if ($lease->info && $building->validate()) {
			$fields = [
				'description_label' => [
					'type' => 'label',
					'col' => 5,
					'properties' => 'Item description'
				],
				'amount_label' => [
					'type' => 'label',
					'col' => 7,
					'properties' => 'Amount (&pound;)'
				]
			];

			for($i = 1; $i <= 10; $i++) {
				$fields = array_merge($fields, [
					"misc_{$i}_desc" => [
						'type' => 'input',
						'col'  => 5,
						'properties' => [
							'id'    => "misc_{$i}_desc",
							'class' => 'popup-misc-desc',
							'value' => $lease->info->{"misc_{$i}_desc"} ?: ''
						]
					],
					"misc_{$i}_value" => [
						'type' => 'input',
						'col'  => 4,
						'properties' => [
							'id'    => "misc_{$i}_value",
							'value' => isset($lease->info->{"misc_{$i}_value"}) ? number_format($lease->info->{"misc_{$i}_value"} ?: 0, 2, '.', '') : ''
						]
					],
					"misc_{$i}_recurring" => [
						'type' => 'checkbox',
						'col'  => 3,
						'properties' => [
							'items' => [[
								'id'      => "misc_{$i}_recurring",
								'value'   => 1,
								'label'   => 'Recurring?',
								'checked' => !!$lease->info->{"misc_{$i}_recurring"}
							]]
						]
					]
				]);
			}

			$fields = array_merge($fields, [
				'add_line' => [
					'type'       => 'blank',
					'col'        => 12,
					'properties' => '<a href="#" id="add_misc_line"><i class="eticon eticon-plus"></i> Add item</a>'
				],
				'separator' => [
					'type' => 'blank',
					'properties' => '<hr>'
				],
				'enable_billing' => [
					'type' => 'checkbox',
					'col' => 12,
					'properties' => [
						'note' => 'Bills will be automatically generated on the selected day in each month/quarter.',
						'items' => [[
							'id' => 'popup_enable_billing',
							'value' => 1,
							'label' => 'Enable automated billing',
							'checked' => !(!$lease->info->bill_generate_date_misc || strtotime($lease->info->bill_generate_date_misc) < $today)
						]]
					]
				],
				'bill_generate_date_misc' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id' => 'popup_bill_generate_date_misc',
						'placeholder' => 'dd/mm/yyyy',
						'value' => $lease->info->bill_generate_date_misc && strtotime($lease->info->bill_generate_date_misc) >= $today ? App::format_datetime('d/m/Y', $lease->info->bill_generate_date_misc, 'Y-m-d') : '',
						'label' => 'Next billing date'
					]
				],
				'days_to_pay_misc_bill' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id'    => 'popup_days_to_pay_misc_bill',
						'value' => $lease->info->days_to_pay_misc_bill ?: '14',
						'label' => 'Days to pay'
					]
				],
				'bill_generate_frequency_misc' => [
					'type' => 'select2',
					'col' => 6,
					'properties' => [
						'id' => 'popup_bill_generate_frequency_misc',
						'data' => Lease::list_bill_frequencies(),
						'value' => 'value',
						'display' => 'description',
						'label' => 'Bill frequency',
						'selected' => $lease->info->bill_generate_frequency_misc
					]
				],
				'print_zero_misc_bill' => [
					'type' => 'checkbox',
					'col' => 6,
					'properties' => [
						'note' => 'Enabling this option will generate a miscellaneous bill even if the total is zero.',
						'items' => [[
							'id' => 'popup_print_zero_misc_bill',
							'value' => 1,
							'label' => 'Print zero bill?',
							'checked' => !!$lease->info->print_zero_misc_bill
						]]
					]
				],
				'bill_from_date_misc' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id' => 'popup_bill_from_date_misc',
						'placeholder' => 'dd/mm/yyyy',
						'value' => $lease->info->bill_from_date_misc && $lease->info->bill_from_date_misc != '0000-00-00' ? App::format_datetime('d/m/Y', $lease->info->bill_from_date_misc, 'Y-m-d') : '',
						'label' => 'Bill from date'
					]
				],
				'bill_to_date_misc' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id' => 'popup_bill_to_date_misc',
						'placeholder' => 'dd/mm/yyyy',
						'value' => $lease->info->bill_to_date_misc && $lease->info->bill_to_date_misc != '0000-00-00' ? App::format_datetime('d/m/Y', $lease->info->bill_to_date_misc, 'Y-m-d') : '',
						'label' => 'Bill to date'
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
			$form->id = 'lease-bill-misc-form';

			$form->footer(function() use ($ui) {
				return implode(' ', [
					'<span class="txt-color-orange" style="display:inline-block; margin-top: 13px;">All charges exclude VAT.</span>',
					$ui->create_button('Update', 'success')->attr([ 'type' => 'submit' ])->print_html(true),
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
		var $form = $("#lease-bill-misc-form");

		$('#popup_bill_generate_frequency_misc').initSelect2();

		$('#popup_bill_generate_date_misc').datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			minDate: new Date(),
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		$('#popup_bill_from_date_misc,#popup_bill_to_date_misc').datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		$('#popup_enable_billing')
			.on('change', function() {
				$('#popup_bill_generate_date_misc').closest('div.row').toggle($(this).is(':checked'));
				$('#popup_bill_generate_frequency_misc').closest('div.row').toggle($(this).is(':checked'));
				$('#popup_bill_from_date_misc').closest('div.row').toggle($(this).is(':checked'));
			})
			.trigger('change');

		// Hide empty misc fields
		for(var i = 2; i <= 10; i++) {
			var $misc_field = $('#misc_' + i + '_desc');
			if(!$misc_field.val()) $misc_field.closest('div.row').hide();
		}

		var refreshAddButtonState = function() {
			$('#add_misc_line').closest('section').toggle(!$('#misc_10_desc').is(':visible'));
		};

		refreshAddButtonState();

		$('#add_misc_line').on('click', function(e) {
			e.preventDefault();
			$('.popup-misc-desc').closest('div.row').filter(':hidden:first').show();
			refreshAddButtonState();
		});

		$form.validate({
			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/lease_bill_misc'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
