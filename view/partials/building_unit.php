<?php
	require_once '../init.view.php';

	// Get area and tenant details

	$tenanted_id = App::get('tenanted_id', '', true);
	$active_tab = App::get('tab', '');

	if(!$tenanted_id) {
		$ui->print_alert('No tenanted area set.', 'warning');
		return;
	}

	$tenanted_area = App::sql()->query_row("SELECT ta.*, b.id AS building_id, f.description AS floor_description FROM tenanted_area AS ta INNER JOIN area AS a ON a.id = ta.area_id AND (a.is_tenanted = 1 OR a.is_owner_occupied = 1) INNER JOIN floor AS f ON f.id = a.floor_id INNER JOIN building AS b ON b.id = f.building_id WHERE ta.id = $tenanted_id");
	if(!$tenanted_area) {
		$ui->print_alert('Invalid tenanted area.', 'warning');
		return;
	}

	$building_id = $tenanted_area->building_id;
	if(!$building_id) {
		$ui->print_alert('Building not found.', 'warning');
		return;
	}

	$building = new Building($building_id);

	$area_id = $tenanted_area->area_id;
	$area = $building->get_areas(
		[ 'area.id' => "='$area_id'" ],
		[ 'area.*', 'tenanted_area.id as tenanted_id', 'tenant_type' ],
		[ 'INNER JOIN tenanted_area ON tenanted_area.area_id = area.id AND (area.is_tenanted = 1 OR area.is_owner_occupied = 1)' ],
		'area.id'
	)[0];

	if (!$area) {
		$ui->print_danger('Tenanted area does not exists!');
		return;
	}

	$previous = Lease::get_previous_lease($tenanted_id);
	$current = Lease::get_current_lease($tenanted_id);
	$future = Lease::get_future_lease($tenanted_id);

	$show_previous_tab = !!$previous;
	$show_current_tab = !!$current;
	$show_future_tab = !!$future;
	$show_create_tab = !$future;

	// Clear active tab is it points to a non-existing one
	switch($active_tab) {
		case 'previous': if(!$show_previous_tab) $active_tab = ''; break;
		case 'current':  if(!$show_current_tab) $active_tab = '';  break;
		case 'future':   if(!$show_future_tab) $active_tab = '';   break;
		case 'create':   $active_tab = '';                         break;
		case 'unit':     break;
		default:         $active_tab = '';
	}

	if(!$active_tab) $active_tab = $show_current_tab ? 'current' : ($show_future_tab ? 'future' : 'create');

	function show_lease_details($lease, $tenanted_area, $ui, $has_current) {
		global $area;

		if(!$lease) return;

		$tenant = $lease->get_tenant();
		if(!$tenant) return;

		$lines = [];
		$add_line = function($s) use (&$lines) {
			if($s) $lines[] = $s;
		};
		$dump_lines = function() use (&$lines) {
			echo implode('<br>', $lines);
			$lines = [];
		};

		echo '<div class="row">';
		echo '<div class="col col-sm-12">';
		if ($lease->is_expiring()) {
			$ui->print_alert('Lease is expiring on <b>'.App::format_datetime('d/m/Y', $lease->info->lease_end_date, 'Y-m-d').'</b>.', 'warning', [ 'closebutton' => false ]);
		} else if ($lease->is_ending()) {
			$ui->print_alert('Lease will end on <b>'.App::format_datetime('d/m/Y', $lease->info->lease_end_date, 'Y-m-d').'</b>.', 'warning', [ 'closebutton' => false ]);
		} else {
			echo '&nbsp;';
		}
		echo '</div>';
		echo '</div>';

		echo '<div class="row">';
		echo '<div class="col col-sm-4" style="word-wrap: break-word;">';
			echo $area->is_owner_occupied ? '<h1>Owner</h1>' : '<h1>Tenant</h1>';
			if (!$lease->is_previous()) echo '<p><a href="#" class="edit-tenant txt-color-red" data-id="'.$tenant->id.'"><i class="eticon eticon-pencil"></i> Edit '.($area->is_owner_occupied ? 'owner' : 'tenant').'</a></p>';
			echo '<p>';
			if ($tenant->company) $add_line('<strong>'.$tenant->company.'</strong>');
			$add_line($tenant->name);
			$dump_lines();
			echo '</p>';

			echo '<p>';
			if ($tenant->email_address) $add_line('<a href="mailto:'.$tenant->email_address.'">'.$tenant->email_address.'</a>');
			$add_line($tenant->telephone_number);
			$add_line($tenant->mobile_number);
			$dump_lines();
			echo '</p>';

			if ($tenant->home_address) echo '<p><strong>Home address</strong><br>'.$tenant->home_address.'</p>';
			if ($tenant->customer_reference_number) echo '<p><strong>Customer reference</strong><br>'.$tenant->customer_reference_number.'</p>';
		echo '</div>';

		echo '<div class="col col-sm-4" style="border-left: 1px solid #ddd;">';
			echo $area->is_owner_occupied ? '<h1>Contract</h1>' : '<h1>Lease</h1>';
			if ($lease->is_future() || $lease->is_current()) {
				echo '<p><a href="#" class="edit-lease txt-color-red" data-id="'.$lease->id.'"><i class="eticon eticon-pencil"></i> Edit '.($area->is_owner_occupied ? 'contract' : 'lease').'</a></p>';
			}
			echo $area->is_owner_occupied ? '<p><strong>Date</strong>' : '<p><strong>Start and end date</strong>';
			echo '<br>'.date('d F Y', strtotime($lease->info->lease_start_date));
			if (!$area->is_owner_occupied) echo '<br>'.date('d F Y', strtotime($lease->info->lease_end_date));
			echo '</p>';
			if (!$area->is_owner_occupied && $lease->info->term) echo '<p><strong>Contract term</strong><br>'.$lease->info->term.' '.$lease->get_term_units_description().'</p>';
			if ($lease->info->payment_type) echo '<p><strong>Bills paid by</strong><br>'.$lease->get_payment_type_description().'</p>';
			if ($lease->info->account_ref) echo '<p><strong>Account reference</strong><br>'.$lease->info->account_ref.'</p>';
			if (!$area->is_owner_occupied && $lease->info->lease_renewal_alert_date) echo '<p><strong>Renewal due</strong><br>'.App::format_datetime('d/m/Y', $lease->info->lease_renewal_alert_date, 'Y-m-d').'</p>';
		echo '</div>';

		echo '<div class="col col-sm-4" style="border-left: 1px solid #ddd;">';
			echo '<h1>Invoice address</h1>';
			if (!$lease->is_previous()) echo '<p><a href="#" class="edit-address txt-color-red" data-id="'.$lease->id.'"><i class="eticon eticon-pencil"></i> Edit address</a></p>';
			echo '<p>';
			$add_line($lease->info->invoice_address_1);
			$add_line($lease->info->invoice_address_2);
			$add_line($lease->info->invoice_address_3);
			$add_line($lease->info->invoice_posttown);
			$add_line($lease->info->postcode);
			$dump_lines();
			echo '</p>';

		echo '</div>';
		echo '</div>';

		if ($lease->is_future()) {
			echo '<div class="row"><div class="col col-sm-12"><br>';
			echo '<button class="cancel-lease" data-id="'.$lease->id.'">Cancel '.($area->is_owner_occupied ? 'contract' : 'lease').'</button>&nbsp;&nbsp;&nbsp;&nbsp;';
			if (!$has_current) echo '<button class="move-in primary" data-id="'.$lease->id.'">Move in</button>&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '</div></div>';
		} else if ($lease->is_current()) {
			echo '<div class="row"><div class="col col-sm-12"><br>';
			echo '<button class="change-end-date'.($lease->is_expiring() ? ' primary' : '').'" data-id="'.$lease->id.'">Change end date</button>&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<button class="set-lease-end" data-id="'.$lease->id.'">Set moving out date</button>&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<button class="move-out'.($lease->is_ending() ? ' primary' : '').'" data-id="'.$lease->id.'">Move out</button>&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '</div></div>';
		}

		$bills = [];
		$today = strtotime('today');

		//
		// Rent and service charges
		//

		if (!$area->is_owner_occupied) {
			$bill_inactive = !$lease->info->bill_generate_date_rent || strtotime($lease->info->bill_generate_date_rent) < $today;

			$items = '';
			$items .= '<table style="width:100%;">';
			$items .= '<tr><td><strong>Rent and Service charges</strong></td><td class="text-right">'.($bill_inactive ? '<i class="txt-color-orange">Inactive</i>' : '<i class="txt-color-green">Active</i>').'</td></tr>';
			if ($lease->info->rental_cost_pounds_ex_vat_per_year) $items .= '<tr><td>Rental charge (pa)</td><td class="text-right fixed-numbers">&pound;'.number_format($lease->info->rental_cost_pounds_ex_vat_per_year,2).'</td></tr>';
			if ($lease->info->service_charge_pounds_ex_vat_per_year) $items .= '<tr><td>Service charge (pa)</td><td class="text-right fixed-numbers">&pound;'.number_format($lease->info->service_charge_pounds_ex_vat_per_year,2).'</td></tr>';
			$items .= '</table>';

			$dates = '';
			$dates .= '<table style="width:100%;">';
			if ($lease->info->prev_bill_generate_date_rent) $dates .= '<tr><td>Last sent:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->prev_bill_generate_date_rent)).'</td></tr>';
			if (!$bill_inactive) {
				$dates .= '<tr><td>Next bill:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->bill_generate_date_rent)).'</td></tr>';
				if ($lease->info->days_to_pay_rent_bill) $dates .= '<tr><td>Days to pay:</td><td class="text-right fixed-numbers">'.$lease->info->days_to_pay_rent_bill.'</td></tr>';
				if ($freq = $lease->get_bill_frequency_description(TenantBill::TYPE_RENT)) $dates .= '<tr><td>Paid:</td><td class="text-right fixed-numbers">'.$freq.'</td></tr>';
			}
			$dates .= '</table>';

			$bills[] = [
				'Items' => $items,
				'Dates' => $dates,
				'Action' => '<a href="#" class="edit-bill-rent" data-id="'.$lease->id.'"><i class="eticon eticon-pencil"></i> Edit</a>'
			];
		}

		//
		// Utility bills
		//

		if ($tenanted_area->tenant_type == 'serviced') {
			$bill_inactive = !$lease->info->bill_generate_date_utility || strtotime($lease->info->bill_generate_date_utility) < $today;

			$items = '';
			$items .= '<table style="width:100%;">';
			$items .= '<tr><td><strong>Utilities</strong></td><td class="text-right">'.($bill_inactive ? '<i class="txt-color-orange">Inactive</i>' : '<i class="txt-color-green">Active</i>').'</td></tr>';
			if ($lease->info->electric_ex_vat_cost_in_pence_per_kwh) $items .= '<tr><td>Electricity charge (pence/kWh)</td><td class="text-right fixed-numbers">'.number_format($lease->info->electric_ex_vat_cost_in_pence_per_kwh,2).'</td></tr>';
			if ($lease->info->gas_ex_vat_cost_in_pence_per_kwh) $items .= '<tr><td>Gas charge (pence/kWh)</td><td class="text-right fixed-numbers">'.number_format($lease->info->gas_ex_vat_cost_in_pence_per_kwh,2).'</td></tr>';
			if ($lease->info->water_ex_vat_cost_in_pence_per_m3) $items .= '<tr><td>Water charge (pence/m<sup>3</sup>)</td><td class="text-right fixed-numbers">'.number_format($lease->info->water_ex_vat_cost_in_pence_per_m3,2).'</td></tr>';
			$items .= '<tr><td>Utility VAT rate</td><td class="text-right fixed-numbers">'.App::format_number($lease->info->utility_vat_rate,0,2).'%</td></tr>';

			$items .= '</table>';

			$dates = '';
			$dates .= '<table style="width:100%;">';
			if ($lease->info->prev_bill_generate_date_utility) $dates .= '<tr><td>Last sent:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->prev_bill_generate_date_utility)).'</td></tr>';
			if (!$bill_inactive) {
				$dates .= '<tr><td>Next bill:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->bill_generate_date_utility)).'</td></tr>';
				if ($lease->info->days_to_pay_utility_bill) $dates .= '<tr><td>Days to pay:</td><td class="text-right fixed-numbers">'.$lease->info->days_to_pay_utility_bill.'</td></tr>';
				if ($freq = $lease->get_bill_frequency_description(TenantBill::TYPE_UTILITY)) $dates .= '<tr><td>Paid:</td><td class="text-right fixed-numbers">'.$freq.'</td></tr>';
			}
			$dates .= '</table>';

			$bills[] = [
				'Items' => $items,
				'Dates' => $dates,
				'Action' => '<a href="#" class="edit-bill-utility" data-id="'.$lease->id.'"><i class="eticon eticon-pencil"></i> Edit</a>'
			];
		}

		//
		// Estate costs
		//

		if ($area->is_owner_occupied) {
			$bill_inactive = !$lease->info->bill_generate_date_estate_cost || strtotime($lease->info->bill_generate_date_estate_cost) < $today;

			$items = '';
			$items .= '<table style="width:100%;">';
			$items .= '<tr><td><strong>Estate costs</strong></td><td class="text-right">'.($bill_inactive ? '<i class="txt-color-orange">Inactive</i>' : '<i class="txt-color-green">Active</i>').'</td></tr>';
			if ($lease->info->estate_cost_pounds_ex_vat_per_year) $items .= '<tr><td>Estate costs (pa)</td><td class="text-right fixed-numbers">'.number_format($lease->info->estate_cost_pounds_ex_vat_per_year,2).'</td></tr>';
			$items .= '</table>';

			$dates = '';
			$dates .= '<table style="width:100%;">';
			if ($lease->info->prev_bill_generate_date_estate_cost) $dates .= '<tr><td>Last sent:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->prev_bill_generate_date_estate_cost)).'</td></tr>';
			if (!$bill_inactive) {
				$dates .= '<tr><td>Next bill:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->bill_generate_date_estate_cost)).'</td></tr>';
				if ($lease->info->days_to_pay_estate_cost_bill) $dates .= '<tr><td>Days to pay:</td><td class="text-right fixed-numbers">'.$lease->info->days_to_pay_estate_cost_bill.'</td></tr>';
				if ($freq = $lease->get_bill_frequency_description(TenantBill::TYPE_ESTATE)) $dates .= '<tr><td>Paid:</td><td class="text-right fixed-numbers">'.$freq.'</td></tr>';
			}
			$dates .= '</table>';

			$bills[] = [
				'Items' => $items,
				'Dates' => $dates,
				'Action' => '<a href="#" class="edit-bill-estate" data-id="'.$lease->id.'"><i class="eticon eticon-pencil"></i> Edit</a>'
			];
		}

		//
		// Miscellaneous
		//

		$bill_inactive = !$lease->info->bill_generate_date_misc || strtotime($lease->info->bill_generate_date_misc) < $today;

		$items = '';
		$items .= '<table style="width:100%;">';
		$items .= '<tr><td><strong>Miscellaneous</strong></td><td class="text-right">'.($bill_inactive ? '<i class="txt-color-orange">Inactive</i>' : '<i class="txt-color-green">Active</i>').'</td></tr>';
		for ($i = 1; $i <= 10; $i++) {
			if($lease->info->{"misc_{$i}_value"}) {
				$items .= '<tr><td>'.($lease->info->{"misc_{$i}_desc"} ?: '').($lease->info->{"misc_{$i}_recurring"} ? ' <i>(recurring)</i>' : '').'</td><td class="text-right fixed-numbers">'.number_format($lease->info->{"misc_{$i}_value"},2).'</td></tr>';
			}
		}
		$items .= '</table>';

		$dates = '';
		$dates .= '<table style="width:100%;">';
		if ($lease->info->prev_bill_generate_date_misc) $dates .= '<tr><td>Last sent:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->prev_bill_generate_date_misc)).'</td></tr>';
		if (!$bill_inactive) {
			$dates .= '<tr><td>Next bill:</td><td class="text-right fixed-numbers">'.date('d/m/Y', strtotime($lease->info->bill_generate_date_misc)).'</td></tr>';
			if ($lease->info->days_to_pay_misc_bill) $dates .= '<tr><td>Days to pay:</td><td class="text-right fixed-numbers">'.$lease->info->days_to_pay_misc_bill.'</td></tr>';
			if ($freq = $lease->get_bill_frequency_description(TenantBill::TYPE_MISC)) $dates .= '<tr><td>Paid:</td><td class="text-right fixed-numbers">'.$freq.'</td></tr>';
		}
		$dates .= '</table>';

		$bills[] = [
			'Items' => $items,
			'Dates' => $dates,
			'Action' => '<a href="#" class="edit-bill-misc" data-id="'.$lease->id.'"><i class="eticon eticon-pencil"></i> Edit</a>'
		];

		// Show billing table

		$bills_table = $ui->create_datatable($bills, [
			'hover'     => true,
			'bordered'  => true,
			'in_widget' => false
		]);
		$bills_table->class = 'dark-header';
		$bills_table
			->col('Action', [ 'title' => '' ])
			->cell('Action', function($row, $value) {
				return '<div class="text-center">'.$value.'</div>';
			});

		if($lease->is_previous()) {
			$bills_table->hidden = ['Action'];
		}

		echo '<h1 style="margin-top:1em;">Billing</h1>';
		echo $bills_table->print_html(true);
	}
?>

<div class="content tenant">
	<div class="row">
		<br>
		<ul class="nav nav-tabs units">
			<?php if($show_previous_tab) { ?><li<?= $active_tab == 'previous' ? ' class="active"' : ''; ?>><a href="#tab-previous" data-toggle="tab"><i class="eticon eticon-arrow-left"></i>&nbsp; Previous <?= $area->is_owner_occupied ? 'owner' : 'lease' ?></a></li><?php } ?>
			<?php if($show_current_tab) { ?><li<?= $active_tab == 'current' ? ' class="active"' : ''; ?>><a href="#tab-current" data-toggle="tab"><i class="eticon eticon-file"></i>&nbsp; Current <?= $area->is_owner_occupied ? 'owner' : 'lease' ?> (<?= $current->get_sub_status_description(); ?>)</a></li><?php } ?>
			<?php if($show_future_tab) { ?><li<?= $active_tab == 'future' ? ' class="active"' : ''; ?>><a href="#tab-future" data-toggle="tab"><i class="eticon eticon-arrow-right"></i>&nbsp; Future / Approved <?= $area->is_owner_occupied ? 'owner' : 'lease' ?></a></li><?php } ?>
			<?php if($show_create_tab) { ?><li<?= $active_tab == 'create' ? ' class="active"' : ''; ?>><a href="#tab-create" data-toggle="tab"><i class="eticon eticon-plus"></i>&nbsp; <?= $area->is_owner_occupied ? 'Add new owner' : 'Create new lease' ?></a></li><?php } ?>
			<li<?= $active_tab == 'unit' ? ' class="active"' : ''; ?>><a href="#tab-unit" data-toggle="tab"><i class="eticon eticon-area"></i>&nbsp; Unit details</a></li>
		</ul>
		<div class="tab-content">

			<?php if($show_previous_tab) { ?>
				<div class="tab-pane<?= $active_tab == 'previous' ? ' active' : ''; ?>" id="tab-previous">
					<?php show_lease_details($previous, $tenanted_area, $ui, !!$current); ?>
				</div>
			<?php } ?>

			<?php if($show_current_tab) { ?>
				<div class="tab-pane<?= $active_tab == 'current' ? ' active' : ''; ?>" id="tab-current">
					<?php show_lease_details($current, $tenanted_area, $ui, !!$current); ?>
				</div>
			<?php } ?>

			<?php if($show_future_tab) { ?>
				<div class="tab-pane<?= $active_tab == 'future' ? ' active' : ''; ?>" id="tab-future">
					<?php show_lease_details($future, $tenanted_area, $ui, !!$current); ?>
				</div>
			<?php } ?>

			<?php if($show_create_tab) { ?>
				<div class="tab-pane<?= $active_tab == 'create' ? ' active' : ''; ?>" id="tab-create">

<?php
	$fields = [
		'tenant_heading' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => [ 'content' => '<h1>'.($area->is_owner_occupied ? 'Owner' : 'Tenant').' information</h1><div id="create-lease-tenant-info"></div>' ]
		],
		'tenant_id' => [
			'type'       => 'hidden',
			'value'      => '',
			'properties' => [ 'id' => 'tenant_id' ]
		],
		'select_tenant' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => [ 'content' => '<a href="#" id="pick_tenant" class="btn primary">Select existing '.($area->is_owner_occupied ? 'owner' : 'tenant').'</a> <a href="#" id="new_tenant" class="btn">Add new '.($area->is_owner_occupied ? 'owner' : 'tenant').'</a>' ]
		],
		'lease_heading' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => [ 'content' => '<br><hr><br><h1>'.($area->is_owner_occupied ? 'Contract' : 'Lease').' details</h1>' ]
		],
		'lease_start_date' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'id'          => 'lease_start_date',
				'placeholder' => 'dd/mm/yyyy',
				'value'       => '',
				'label'       => 'Start date'
			]
		],
		'term' => [
			'type'       => 'input',
			'col'        => 3,
			'properties' => [ 'label' => 'Contract term' ]
		],
		'term_units' => [
			'type' => 'select2',
			'col'  => 3,
			'properties' => [
				'id'      => 'term_units',
				'data'    => Lease::list_term_units(),
				'value'   => 'value',
				'display' => 'description',
				'label'   => '&nbsp;'
			]
		],
		'lease_renewal_alert_date' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'id'          => 'lease_renewal_alert_date',
				'placeholder' => 'dd/mm/yyyy',
				'value'       => '',
				'label'       => 'Contract renewal alert date'
			]
		],
		'payment_type' => [
			'type' => 'select2',
			'col'  => 6,
			'properties' => [
				'id'      => 'payment_type',
				'data'    => Lease::list_payment_types(),
				'value'   => 'value',
				'display' => 'description',
				'label'   => 'Bills paid by'
			]
		],
		'account_ref' => [
			'type'       => 'input',
			'col'        => 6,
			'properties' => [ 'label' => 'Account reference' ]
		],

		'invoice_heading' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => '<br><hr><br><h1>Invoice address</h1>'
		],
		'invoice_address_1' => [
			'type'       => 'input',
			'col'        => 6,
			'properties' => [ 'label' => 'Line 1' ]
		],
		'invoice_address_2' => [
			'type'       => 'input',
			'col'        => 6,
			'properties' => [ 'label' => 'Line 2' ]
		],
		'invoice_address_3' => [
			'type'       => 'input',
			'col'        => 6,
			'properties' => [ 'label' => 'Line 3' ]
		],
		'invoice_posttown' => [
			'type'       => 'input',
			'col'        => 6,
			'properties' => [ 'label' => 'Town' ]
		],
		'postcode' => [
			'type'       => 'input',
			'col'        => 6,
			'properties' => [ 'label' => 'Postcode' ]
		],
		'building_id' => [
			'type'       => 'hidden',
			'properties' => [ 'value' => $building_id ]
		],
		'tenanted_id' => [
			'type'       => 'hidden',
			'properties' => [ 'value' => $tenanted_id ]
		],
		'update' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => [ 'content' => '<button id="create-lease" class="primary pull-right" style="margin-top: 30px;" type="submit">Create Lease</button>' ]
		]
	];

	$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
	$form->id = 'create-lease-form';
	$form->print_html();
?>

				</div>
			<?php } // End of create tab ?>

			<div class="tab-pane<?= $active_tab == 'unit' ? ' active' : ''; ?>" id="tab-unit">
<?php
	$fields = [
		'area_description' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'placeholder' => '',
				'value'       => $area->description,
				'label'       => 'Unit name'
			]
		],
		'floor_description' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'value' => $tenanted_area->floor_description,
				'label' => 'Block',
				'attr'  => ['disabled', 'readonly']
			]
		],
		'tenant_type' => [
			'type' => 'checkbox',
			'col'  => 6,
			'properties' => [
				'label' => 'Utility supply',
				'note'  => 'Serviced units are billed by the landlord for utility charges.',
				'items' => [[
					'value'   => 'serviced',
					'label'   => 'Unit is serviced',
					'checked' => $tenanted_area->tenant_type == 'serviced'
				]]
			]
		],
		'is_owner_occupied' => [
			'type' => 'checkbox',
			'col'  => 6,
			'properties' => [
				'label' => 'Occupier',
				'note'  => 'Privately owned units are purchased by the occupier, they do not pay rent.',
				'items' => [[
					'value'   => 1,
					'label'   => 'Unit is privately owned',
					'checked' => !!$area->is_owner_occupied
				]]
			]
		],
		'size' => [
			'type' => 'input',
			'col'  => 3,
			'properties' => [
				'placeholder' => 'Unit size',
				'value'       => $area->size,
				'label'       => 'Unit size'
			]
		],
		'size_unit' => [
			'type' => 'select2',
			'col'  => 3,
			'properties' => [
				'data'     => Building::get_area_size_units(),
				'value'    => 'value',
				'display'  => 'description',
				'selected' => $area->size_unit,
				'label'    => '&nbsp;',
				'id'       => 'size_unit'
			]
		],
		'lease_heading' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => '<br><hr><br><h1>Unit asking price</h1><p><i class="eticon eticon-alert txt-color-yellow"></i> Please note that changing the fields below will not modify existing lease contracts, but will be used as defaults when creating new ones.</p>'
		],
		'asking_rental_cost_pounds' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'label'       => 'Rental charge (&pound; pa)',
				'placeholder' => 'Rental charge',
				'value'       => number_format($tenanted_area->asking_rental_cost_pounds, 2, '.', ''),
				'id'          => 'rental-cost-pounds'
			]
		],
		'asking_service_charge_pounds' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'placeholder' => 'Service charge cost',
				'value'       => number_format($tenanted_area->asking_service_charge_pounds, 2, '.', ''),
				'label'       => 'Service charge (&pound; pa)'
			]
		],
		'service_charge_info' => [
			'type' => 'input',
			'col'  => 12,
			'properties' => [
				'value' => $tenanted_area->service_charge_info ?: '',
				'label' => 'Service charge info'
			]
		],
		'asking_electric_ex_vat_cost_in_pence_per_kwh' => [
			'type' => 'input',
			'col'  => 4,
			'properties' => [
				'label'       => 'Electricity charge (pence/kWh)',
				'placeholder' => 'Electricity charge',
				'value'       => number_format($tenanted_area->asking_electric_ex_vat_cost_in_pence_per_kwh, 2, '.', '')
			]
		],
		'asking_gas_ex_vat_cost_in_pence_per_kwh' => [
			'type' => 'input',
			'col'  => 4,
			'properties' => [
				'label'       => 'Gas charge (pence/kWh)',
				'placeholder' => 'Gas charge',
				'value'       => number_format($tenanted_area->asking_gas_ex_vat_cost_in_pence_per_kwh, 2, '.', '')
			]
		],
		'asking_water_ex_vat_cost_in_pence_per_m3' => [
			'type' => 'input',
			'col'  => 4,
			'properties' => [
				'label'       => 'Water charge (pence/m<sup>3</sup>)',
				'placeholder' => 'Water charge',
				'value'       => number_format($tenanted_area->asking_water_ex_vat_cost_in_pence_per_m3, 2, '.', '')
			]
		],
		'area_id' => [
			'type'       => 'hidden',
			'properties' => [ 'value' => $area_id ]
		],
		'building_id' => [
			'type'       => 'hidden',
			'properties' => [ 'value' => $building->id ]
		],
		'tenanted_area_id' => [
			'type'       => 'hidden',
			'properties' => [ 'value' => $tenanted_id ]
		],
		'update' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => [ 'content' => '<button id="update" class="primary pull-right" style="margin-top: 30px;">Update</button>' ]
		]
	];

	$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
	$form->id = 'unit-form';
	$form->print_html();
?>
			</div>

		</div>

	</div>
</div>

<script>
	$(function() {
		var $view = $('#view');

		$('#payment_type,#term_units').initSelect2();

		$("#lease_start_date").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>',
			onClose: function (selectedDate) {
				$("#lease_end_date").datepicker("option", "minDate", selectedDate);
				$("#lease_renewal_alert_date").datepicker("option", "minDate", selectedDate);
			}
		});

		$("#lease_end_date").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>',
			onClose: function (selectedDate) {
				$("#lease_start_date").datepicker("option", "maxDate", selectedDate);
				$("#lease_renewal_alert_date").datepicker("option", "maxDate", selectedDate);
			}
		});

		$('#rent_review_date,#lease_renewal_alert_date').datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		var $form = $("#create-lease-form");

		$form.validate({
			rules: {
				lease_start_date: { required : true },
				term: { required : true },
				postcode: { required : true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			}
		});

		$('#create-lease').on('click', function(e) {
			e.preventDefault();
			if ($form.valid()) {
				$.post('<?= APP_URL.'/ajax/post/create_lease';?>', $form.serialize(), function(data) {
					$.ajaxResult(data, function() {
						$.messagebox('Lease created.', {
							title: '<strong><span class="txt-color-green">Success</span></strong>',
							iconClass: 'fa fa-check txt-color-green',
							buttons: '[OK]'
						}, refreshTenantView);
					});
				});
			}
		});

		$('#pick_tenant').on('click', function(e) {
			e.preventDefault();
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/tenant-pick.php?building_id=<?= $building_id; ?>&tenanted_id=<?= $tenanted_id; ?>&mode=select', { size: 'lg' });
		});

		$('#new_tenant').on('click', function(e) {
			e.preventDefault();
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/tenant-pick.php?building_id=<?= $building_id; ?>&tenanted_id=<?= $tenanted_id; ?>&mode=new', { size: 'lg' });
		});

		window.tenantSelected = function(tenantId) {
			$('#tenant_id').val(tenantId);
			$.getJSON('<?= APP_URL ?>/ajax/get/tenant_info', {
				building_id: <?= $building->id; ?>,
				tenant_id: tenantId
			}, function(result) {
				if(result.data) {
					$('#create-lease-tenant-info').html('<br>' + result.data);
				}
				$('#pick_tenant').text('Change tenant').removeClass('primary');
			});
		};

		$view.find('.edit-tenant').on('click', function(e) {
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/crud-tenant.php?building_id=<?= $building_id; ?>&tenant_id=' + id);
		});

		$view.find('.edit-address').on('click', function(e) {
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-invoice-address.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.edit-lease').on('click', function(e) {
			// Edit lease
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-edit.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.cancel-lease').on('click', function(e) {
			// Cancel future lease
			e.preventDefault();
			var id = $(this).data('id');
			$.messagebox('Are you sure you want to cancel this contract?', function(btn) {
				if(btn == "Yes") {
					$.post('<?= APP_URL ;?>/ajax/post/delete_lease', {
						lease_id: id,
						building_id: '<?= $building_id; ?>'
					}, function(data) {
						$.ajaxResult(data, refreshTenantView);
					});
				}
			});
		});

		$view.find('.move-in').on('click', function(e) {
			// Make future lease current
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-move-in.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.edit-bill-rent').on('click', function(e) {
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-bill-rent.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.edit-bill-utility').on('click', function(e) {
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-bill-utility.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.edit-bill-estate').on('click', function(e) {
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-bill-estate.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.edit-bill-misc').on('click', function(e) {
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-bill-misc.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.change-end-date').on('click', function(e) {
			// Change lease end date
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-change-end.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.set-lease-end').on('click', function(e) {
			// Set final lease end date
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-set-end.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		$view.find('.move-out').on('click', function(e) {
			// Make current lease previous
			e.preventDefault();
			var id = $(this).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/lease-move-out.php?building_id=<?= $building_id; ?>&lease_id=' + id);
		});

		// Unit details tab

		var $unitForm = $("#unit-form");

		$unitForm.validate({
			rules: {
				description: { required: true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			}
		});

		$('#update').on('click', function(e) {
			e.preventDefault();
			if ($unitForm.valid()) {
				$.post('<?= APP_URL ;?>/ajax/post/update_tenanted_area', $unitForm.serialize(), function(data) {
					$.ajaxResult(data, function() {
						$.messagebox('Unit updated.', {
							title: '<strong><span class="txt-color-green">Success</span></strong>',
							iconClass: 'fa fa-check txt-color-green',
							buttons: '[OK]'
						}, refreshTenantView);
					});
				});
			}
		});

		$('#size_unit').initSelect2();

		// Update active tab in JS variable
		$view.find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			var tabname = $(e.target).attr("href");
			viewData.building_unit.tab = tabname.split('-')[1];
		});
	});
</script>
