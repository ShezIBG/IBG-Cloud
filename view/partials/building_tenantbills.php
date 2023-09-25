<?php
	require_once '../init.view.php';

	// Get building details
	$building_id = App::get('building_id');
	if(!$building_id) {
		$ui->print_alert('Building not found.', 'warning');
		exit;
	}

	$building = new Building($building_id);
	$client_id = $building->info->client_id;

	$q = '';
	foreach(TenantBill::$tables as $type => $table) {
		if($q) $q .= ' UNION ALL ';

		$q .= "(SELECT
			'$type' as bill_type,
			tenant_company,
			tenant_name,
			area_description,
			bill_date,
			bill_total,
			status,
			id
		FROM $table WHERE building_id = '$building_id')";
	}

	$q .= ' ORDER BY bill_date DESC, tenant_company, area_description';

	$bills = App::sql()->query($q) ?: [];
	$bills_table = $ui->create_datatable($bills, [
		'hover'     => true,
		'bordered'  => false,
		'in_widget' => false
	]);
	$bills_table->id = 'tenant-bills-table';
	$bills_table
		->col('bill_type', 'Bill Type')
		->col('tenant_company', 'Company')
		->col('tenant_name', 'Contact')
		->col('area_description', 'Unit')
		->col('bill_date', 'Bill Date')
		->col('bill_total', [
			'title' => 'Total',
			'attr'  => [ 'style' => 'text-align: right;' ]
		])
		->col('status', 'Status')
		->col('id', ' ');

	$bills_table
		->cell('bill_type', function($row, $value) {
			return TenantBill::get_type_description($value);
		})
		->cell('bill_total', function($row, $value) {
			return '<div style="text-align:right;font-family:\'Open Sans\';">&pound;'.number_format($value, 2).'</div>';
		})
		->cell('bill_date', function($row, $value) {
			return App::format_datetime('d F Y', $value, 'Y-m-d');
		})
		->cell('status', function($row, $value) {
			$badge_text = '';
			$badge_class = '';
			$badge_hint = '';

			switch($value) {
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
			return '<a href="#" class="bill" data-id="'.$row->id.'" data-type="'.$row->bill_type.'"><span class="label '.$badge_class.'" title="'.$badge_hint.'">'.$badge_text.'</span></a>';
		})
		->cell('id', function($row, $value) {
			return '<a href="'.TenantBill::get_public_url($row->bill_type, $value).'" target="_blank"><i class="eticon eticon-file"></i></a>';
		});

	$bills_table->js('properties', [
		'displayLength' => -1,
		'scrollY' => 200,
		'lengthChange' => 0,
		'paging' => 0,
		'lengthMenu' => '[]'
	]);

	$bills_table->class = 'resizeme';

	// Create filtering tab bar
	echo '<div class="table-view">';
	echo '<ul class="nav nav-tabs bills">';
	foreach(array_reverse(TenantBill::$tables) as $type => $table) {
		$desc = TenantBill::get_type_description($type);
		echo '<li class="pull-right"><a href="#tab-'.$type.'" data-filter="'.$desc.'" data-toggle="tab">'.$desc.'</a></li>';
	}
	echo '<li class="active pull-right"><a href="#tab-all" data-filter="" data-toggle="tab">All</a></li>';
	echo '</ul>';

	$bills_table->print_html();

	echo '</div>';
?>

<script>
	var runFunction = function() {
		<?php $bills_table->print_js(false, false); ?>

		$('#tenant-bills-table').data('padding-bottom', 20).on('click', 'a.bill', function(e) {
			e.preventDefault();
			var $this = $(this),
				id = $this.data('id'),
				type = $this.data('type');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/tenant-bill.php?bill_id='+id+'&type='+type+'&building_id=<?= $building_id; ?>');
		});

		resizeScrolltables();

		$('#view ul.nav a').on('click', function(e) {
			e.preventDefault();
			var filter = $(this).data('filter') || '';
			var dt = $('#tenant-bills-table').data('dt');
			dt.api().column(0).search(filter).draw();
		});
	};

	loadScript('<?=ASSETS_URL ?>/js/plugin/select2/select2.js', function() {
		loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", function() {
			loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/jquery.dataTables.min.js", function() {
				loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.colVis.min.js", function() {
					loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.tableTools.min.js", function() {
						loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.bootstrap.min.js", function() {
							loadScript("<?= ASSETS_URL ?>/js/plugin/datatable-responsive/datatables.responsive.min.js", runFunction);
						});
					});
				});
			});
		});
	});
</script>
