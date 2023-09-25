<?php
	require_once '../init.view.php';

	list($building_id, $tenant_id) = App::get(['building_id', 'tenant_id'], '', true);
?>

<div class="content tenant txt-color-darken">

<?php
	if (!$building_id) {
		$ui->print_danger('Building not found.');
	} else {
		$building = new Building($building_id);
		if ($building->validate()) {
			$tenant = $tenant_id ? $building->get_tenant_info($tenant_id) : null;

			$fields = [
				'company' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Company',
						'value' => $tenant ? $tenant->company : '',
						'attr' => ['autofocus']
					]
				],
				'name' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Contact name',
						'value' => $tenant ? $tenant->name : ''
					]
				],
				'email_address' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Email address',
						'value' => $tenant ? $tenant->email_address : ''
					]
				],
				'customer_reference_number' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Customer reference number',
						'value' => $tenant ? $tenant->customer_reference_number : ''
					]
				],
				'home_address' => [
					'type' => 'input',
					'col' => 12,
					'properties' => [
						'label' => 'Tenant home address',
						'value' => $tenant ? $tenant->home_address : ''
					]
				],
				'telephone_number' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Telephone',
						'value' => $tenant ? $tenant->telephone_number : ''
					]
				],
				'mobile_number' => [
					'type' => 'input',
					'col' => 6,
					'properties' => [
						'label' => 'Mobile phone',
						'value' => $tenant ? $tenant->mobile_number : ''
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

			if ($tenant && $tenant->password) {
				$fields['reset_password'] = [
					'type' => 'checkbox',
					'col' => 12,
					'properties' => [
						'label' => 'Tenancy app',
						'note' => 'Tenant will receive an email with their new auto-generated password.',
						'items' => [[
							'value' => 1,
							'label' => 'Reset tenant\'s password?'
						]]
					]
				];
			} else {
				$fields['reset_password'] = [
					'type' => 'checkbox',
					'col' => 12,
					'properties' => [
						'label' => 'Tenancy app',
						'note' => 'Tenant will receive an email with their auto-generated password.',
						'items' => [[
							'value' => 1,
							'label' => 'Create tenant login?'
						]]
					]
				];
			}

			if($tenant_id) {
				$fields['tenant_id'] = [
					'type' => 'hidden',
					'properties' => [ 'value' => $tenant_id ]
				];
			}

			$fields['buttons'] = [
				'type'       => 'blank',
				'col'        => 12,
				'properties' => [ 'content' => '<button id="save-tenant" class="primary pull-right">'.($tenant_id ? 'Save' : 'Create').'</button>' ]
			];

			$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
			$form->id = 'bm-tenant-form';

			$form->print_html();
		} else {
			$ui->print_danger('Building does not exist.');
		}
	}
?>

</div>

<script>
	var runFunction = function() {
		var $form = $("#bm-tenant-form");

		$form.validate({
			rules: {
				company: { required: true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.($tenant_id ? '/ajax/post/update_tenant' : '/ajax/post/add_tenant'); ?>', $form.serialize(), function(data) {
					$.ajaxResult(data, function() {
						$.messagebox('Tenant updated.', {
							title: '<strong><span class="txt-color-green">Success</span></strong>',
							iconClass: 'fa fa-check txt-color-green',
							buttons: '[OK]'
						}, refreshTenantView);
					});
				});
			}
		});

		$('#save-tenant').on('click', function(e) {
			e.preventDefault();
			$form.submit();
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
