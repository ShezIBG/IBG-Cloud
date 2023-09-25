<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-red">Edit rent and service charges</h4>
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
				'rental_cost_pounds_ex_vat_per_year' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Rental charge (&pound; pa)',
						'value' => number_format($lease->info->rental_cost_pounds_ex_vat_per_year ?: 0, 2, '.', '')
					]
				],
				'rent_review_date' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id' => 'popup_rent_review_date',
						'placeholder' => 'dd/mm/yyyy',
						'value' => $lease->info->rent_review_date ? App::format_datetime('d/m/Y', $lease->info->rent_review_date, 'Y-m-d') : '',
						'label' => 'Next rent review date'
					]
				],
				'service_charge_pounds_ex_vat_per_year' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Service charge (&pound; pa)',
						'value' => number_format($lease->info->service_charge_pounds_ex_vat_per_year, 2, '.', '')
					]
				],
				'service_charge_info' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'value' => $lease->info->service_charge_info ?: '',
						'label' => 'Service charge info'
					]
				],
				'separator' => [
					'type' => 'blank',
					'properties' => '<hr>'
				],
				'enable_billing' => [
					'type' => 'checkbox',
					'col' => 12,
					'properties' => [
						'note' => 'Bills will be automatically generated on the first day of each month. Billing period is always the month following.',
						'items' => [[
							'id' => 'popup_enable_billing',
							'value' => 1,
							'label' => 'Enable automated billing',
							'checked' => !(!$lease->info->bill_generate_date_rent || strtotime($lease->info->bill_generate_date_rent) < $today)
						]]
					]
				],
				'bill_generate_date_rent' => [
					'type' => 'select2',
					'col' => 6,
					'properties' => [
						'id' => 'popup_bill_generate_date_rent',
						'data' => TenantBill::get_bill_generate_date(TenantBill::TYPE_RENT),
						'value' => 'value',
						'display' => 'description',
						'label' => 'Next billing date',
						'selected' => $lease->info->bill_generate_date_rent
					]
				],
				'days_to_pay_rent_bill' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id'    => 'popup_days_to_pay_rent_bill',
						'value' => $lease->info->days_to_pay_rent_bill ?: '14',
						'label' => 'Days to pay'
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
			$form->id = 'lease-bill-rent-form';

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
		var $form = $("#lease-bill-rent-form");

		$('#popup_bill_generate_date_rent').initSelect2();

		$('#popup_rent_review_date').datepicker({
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
				$('#popup_bill_generate_date_rent').closest('div.row').toggle($(this).is(':checked'));
			})
			.trigger('change');

		$form.validate({
			rules: {
				rental_cost_pounds_ex_vat_per_year: { required : true },
				service_charge_pounds_ex_vat_per_year: { required : true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/lease_bill_rent'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
