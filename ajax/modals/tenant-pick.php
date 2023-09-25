<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 id="pick-modal-title" class="modal-title txt-tenant">Select Tenant</h4>
</div>

<?php
	$building_id = App::get('building_id', null, true);
	$mode = App::get('mode', '', true);

	if(!$building_id) {
		$ui->print_danger('Building and/or tenanted area not set.');
		return;
	}

	$building = new Building(App::get('building_id'));
	if (!$building->validate()) {
		$ui->print_danger('Building does not exist.');
	}
?>

<div id="pick-select-screen" class="modal-body no-padding">

<?php
	$tenants = $building->get_tenants(false, ['id', 'company', 'name', 'email_address']) ?: [];
	$table = $ui->create_datatable($tenants, [
		'hover' => false,
		'bordered' => false,
		'in_widget' => false
	]);
	$table->hidden = ['id'];
	$table
		->col('company', 'Company')
		->col('name', 'Contact Name')
		->col('email_address', 'Email');

	$table->cell('company', '<a href="#" class="select-tenant" data-id="{{id}}">{{company}}</a>');
	$table->cell('email_address', '<a href="mailto:{{email_address}}">{{email_address}}</a>');
	$table->id = 'list-tenants-table';
	$table->js('properties', [
		'displayLength' => 25,
		'lengthMenu' => '[[25, 50, 100, -1], [25, 50, 100, "All"]]'
	]);
	$table->print_html(false);
?>

</div>

<div id="pick-create-screen" class="modal-body no-padding">

<?php
	$fields = [
		'company' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [ 'label' => 'Company' ]
		],
		'name' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [ 'label' => 'Contact name' ]
		],
		'email_address' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [ 'label' => 'Email address' ]
		],
		'customer_reference_number' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [ 'label' => 'Customer reference number' ]
		],
		'home_address' => [
			'type' => 'input',
			'col' => 12,
			'properties' => [ 'label' => 'Tenant home address' ]
		],
		'telephone_number' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [ 'label' => 'Telephone' ]
		],
		'mobile_number' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [ 'label' => 'Mobile phone' ]
		],
		'reset_password' => [
			'type' => 'checkbox',
			'col' => 12,
			'properties' => [
				'label' => 'Tenancy app',
				'note' => 'Tenant will receive an email with their auto-generated password.',
				'items' => [[
					'value' => 1,
					'label' => 'Create tenant sign in?'
				]]
			]
		],
		'building_id' => [
			'type' => 'hidden',
			'properties' => [ 'value' => $building->id ]
		],
		'client_id' => [
			'type' => 'hidden',
			'properties' => [ 'value' => $building->info->client_id ]
		]
	];

	$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
	$form->id = 'pick-create-tenant-form';

	$form->footer(function() use ($ui) {
		return implode(' ', [
			$ui->create_button('Create', 'danger')->attr([ 'type' => 'submit' ])->print_html(true),
			$ui->create_button('Cancel', 'default')->attr([ 'data-dismiss' => 'modal' ])->print_html(true)
		]);
	});

	$form->print_html();
?>

</div>

<script>
	var pagefunction = function() {
		<?php $table->print_js(false, false); ?>

		$('#pick-create-screen').hide();

		$('#list-tenants-table_filter > label').append('<button id="pick-new-tenant" class="btn btn-danger" style="margin-left: 20px;">Add new tenant</button>');

		$('#pick-new-tenant').on('click', function(e) {
			e.preventDefault();
			$('#pick-modal-title').text('Create Tenant');
			$('#pick-select-screen').hide();
			$('#pick-create-screen').show();
		});

		$('#list-tenants-table').on('click', 'a.select-tenant', function(e) {
			e.preventDefault();
			$('#modal-ajax').modal('hide');
			tenantSelected($(this).data('id'));
		});

		var $form = $("#pick-create-tenant-form");

		$form.validate({
			rules: {
				company: { required: true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/add_tenant'; ?>', $form.serialize(), function(data) {
					if(data.data) {
						$('#modal-ajax').modal('hide');
						tenantSelected(data.data);
					}
				});
			}
		});

		var mode = '<?= $mode ?>';
		if(mode === 'select') {
			// Pure select mode, hide add button
			$('#pick-new-tenant').hide();
		} else if(mode === 'new') {
			// Pure creation mode
			$('#pick-new-tenant').click();
		}
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", function() {
		loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/jquery.dataTables.min.js", function() {
			loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.colVis.min.js", function() {
				loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.tableTools.min.js", function() {
					loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.bootstrap.min.js", function() {
						loadScript("<?= ASSETS_URL ?>/js/plugin/datatable-responsive/datatables.responsive.min.js", pagefunction);
					});
				});
			});
		});
	});
</script>
