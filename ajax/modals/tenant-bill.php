<?php
	require_once '../init.ajax.php';

	list($type, $bill_id, $building_id) = App::get(['type', 'bill_id', 'building_id'], '', true);

	$type = TenantBill::validate_type($type);
?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title">Tenant Bill - <?= TenantBill::get_type_description($type) ?></h4>
</div>
<div class="modal-body no-padding">

<?php

	$bill = new TenantBill($type, $bill_id);

	if (!$bill->info) {
		$ui->print_danger('Bill not found.');
	} else if (!Permission::get_client($bill->info->client_id)->check(Permission::ADMIN)) {
		$ui->print_danger('Access denied.');
	} else {
		$building = new Building($building_id);

		$badge_text = '';
		$badge_class = '';
		$badge_hint = '';

		switch($bill->info->status) {
			case TenantBill::STATUS_OUTSTANDING:
				$badge_class = 'label-default';
				$badge_text = 'Outstanding';
				$badge_hint = 'Bill has not been paid yet.';
				break;
			case TenantBill::STATUS_SENT:
				$badge_class = 'label-primary';
				$badge_text = 'Pending';
				$badge_hint = 'Payment request has been sent to GoCardless, awaiting response.';
				break;
			case TenantBill::STATUS_PAID:
				$badge_class = 'label-success';
				$badge_text = 'Paid';
				$badge_hint = 'Bill has been paid in full.';
				break;
			case TenantBill::STATUS_FAILED_NO_FUNDS:
				$badge_class = 'label-danger';
				$badge_text = 'Insufficient Funds';
				$badge_hint = 'Payment failed due to insufficient funds in bank account. You can try the charge again.';
				break;
			case TenantBill::STATUS_FAILED_PERMANENT:
				$badge_class = 'label-danger';
				$badge_text = 'Failed';
				$badge_hint = 'Direct Debit mandate has failed. Tenant needs to set it up again.';
				break;
		}

		$status_badge = '<span class="txt-color-white label '.$badge_class.'" style="display: inline-block; padding: 2px 10px;" title="'.$badge_hint.'">'.$badge_text.'</span>';

		if ($building->validate()) {
			$fields = [
				'company' => [
					'type' => 'blank',
					'col' => 6,
					'properties' => [
						'label' => 'Company',
						'content' => $bill->info->tenant_company
					]
				],
				'contact' => [
					'type' => 'blank',
					'col' => 6,
					'properties' => [
						'label' => 'Contact',
						'content' => $bill->info->tenant_name
					]
				],
				'unit' => [
					'type' => 'blank',
					'col' => 6,
					'properties' => [
						'label' => 'Unit',
						'content' => $bill->info->area_description
					]
				],
				'bill_date' => [
					'type' => 'blank',
					'col' => 6,
					'properties' => [
						'label' => 'Bill date',
						'content' => App::format_datetime('d F Y', $bill->info->bill_date, 'Y-m-d')
					]
				],
				'bill_total' => [
					'type' => 'blank',
					'col' => 6,
					'properties' => [
						'label' => 'Bill Total',
						'content' => '&pound;'.number_format($bill->info->bill_total, 2)
					]
				],
				'status' => [
					'type' => 'blank',
					'col' => 6,
					'properties' => [
						'label' => 'Status',
						'content' => $status_badge
					]
				],
				'tenant_bill_type' => [
					'type'       => 'hidden',
					'properties' => [ 'value' => $bill->type ]
				],
				'tenant_bill_id' => [
					'type'       => 'hidden',
					'properties' => [ 'value' => $bill->id ]
				]
			];

			$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
			$form->id = 'tenant-bill-form';

			$form->footer(function() use ($ui, $bill) {
				$buttons = [];

				$buttons[] = $ui->create_button('Cancel', 'default')->attr([ 'data-dismiss' => 'modal' ])->print_html(true);
				if($bill->is_outstanding()) $buttons[] = $ui->create_button('Mark as paid', 'success')->attr([ 'type' => 'submit', 'id' => 'bill-mark-as-paid' ])->print_html(true);

				return implode(' ', $buttons);
			});

			$form->print_html();
		} else {
			$ui->print_danger('Building access denied.');
		}
	}
?>

</div>

<script>
	var runFunction = function() {
		var $form = $('#tenant-bill-form');

		$('#bill-mark-as-paid').on('click', function(e) {
			e.preventDefault();
			$.messagebox('Are you sure you want to manually mark this bill as PAID?', function(btn) {
				if (btn == "Yes") {
					$.post('<?= APP_URL ;?>/ajax/post/tenant_bill_paid', $form.serialize(), function(data) {
						$.modalResult(data, refreshTenantView);
					});
				}
			});
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
