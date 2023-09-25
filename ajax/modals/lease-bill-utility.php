<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-red">Edit utility bill</h4>
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
				'electric_ex_vat_cost_in_pence_per_kwh' => [
					'type' => 'input',
					'col' => 4,
					'properties' => [
						'label' => 'Electricity charge<br>(pence/kWh)',
						'placeholder' => 'Electricity charge',
						'value' => number_format($lease->info->electric_ex_vat_cost_in_pence_per_kwh ?: 0, 2, '.', '')
					]
				],
				'gas_ex_vat_cost_in_pence_per_kwh' => [
					'type' => 'input',
					'col' => 4,
					'properties' => [
						'label' => 'Gas charge<br>(pence/kWh)',
						'placeholder' => 'Gas charge',
						'value' => number_format($lease->info->gas_ex_vat_cost_in_pence_per_kwh ?: 0, 2, '.', '')
					]
				],
				'water_ex_vat_cost_in_pence_per_m3' => [
					'type' => 'input',
					'col' => 4,
					'properties' => [
						'label' => 'Water charge<br>(pence/m<sup>3</sup>)',
						'placeholder' => 'Water charge',
						'value' => number_format($lease->info->water_ex_vat_cost_in_pence_per_m3 ?: 0, 2, '.', '')
					]
				],
				'display_electric_usage_on_bill' => [
					'type' => 'checkbox',
					'col' => 4,
					'properties' => [
						'items' => [[
							'value' => 1,
							'label' => 'Display usage on bill?',
							'checked' => !!$lease->info->display_electric_usage_on_bill
						]]
					]
				],
				'display_gas_usage_on_bill' => [
					'type' => 'checkbox',
					'col' => 4,
					'properties' => [
						'items' => [[
							'value' => 1,
							'label' => 'Display usage on bill?',
							'checked' => !!$lease->info->display_gas_usage_on_bill
						]]
					]
				],
				'display_water_usage_on_bill' => [
					'type' => 'checkbox',
					'col' => 4,
					'properties' => [
						'items' => [[
							'value' => 1,
							'label' => 'Display usage on bill?',
							'checked' => !!$lease->info->display_water_usage_on_bill
						]]
					]
				],
				'utility_vat_rate' => [
					'type' => 'select2',
					'col' => 4,
					'properties' => [
						'id' => 'popup_utility_vat_rate',
						'data' => Eticom::get_vat_rates($lease->info->utility_vat_rate),
						'value' => 'value',
						'display' => 'description',
						'selected' => number_format($lease->info->utility_vat_rate ?: 0, 2, '.', ''),
						'label' => 'Utility VAT rate'
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
						'note' => 'Bills will be automatically generated on the first day of each month. Billing period is always the month before.',
						'items' => [[
							'id' => 'popup_enable_billing',
							'value' => 1,
							'label' => 'Enable automated billing',
							'checked' => !(!$lease->info->bill_generate_date_utility || strtotime($lease->info->bill_generate_date_utility) < $today)
						]]
					]
				],
				'bill_generate_date_utility' => [
					'type' => 'select2',
					'col' => 6,
					'properties' => [
						'id' => 'popup_bill_generate_date_utility',
						'data' => TenantBill::get_bill_generate_date(TenantBill::TYPE_UTILITY),
						'value' => 'value',
						'display' => 'description',
						'label' => 'Next billing date',
						'selected' => $lease->info->bill_generate_date_utility
					]
				],
				'days_to_pay_utility_bill' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'id'    => 'popup_days_to_pay_utility_bill',
						'value' => $lease->info->days_to_pay_utility_bill ?: '14',
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
			$form->id = 'lease-bill-utility-form';

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
		var $form = $("#lease-bill-utility-form");

		$('#popup_bill_generate_date_utility,#popup_utility_vat_rate').initSelect2();

		$('#popup_enable_billing')
			.on('change', function() {
				$('#popup_bill_generate_date_utility').closest('div.row').toggle($(this).is(':checked'));
			})
			.trigger('change');

		$form.validate({
			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/lease_bill_utility'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
