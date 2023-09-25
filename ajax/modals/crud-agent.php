<?php
	require_once '../init.ajax.php';

	list($client_id, $agent_id, $building_id) = App::get(['client_id', 'agent_id', 'building_id'], 0, true);

	$agent_id = 0;
	$agent = null;

	if($agent_id) {
		$agent = App::sql()->query_row("SELECT * FROM agent WHERE id = $agent_id;");
		if($agent) {
			$client_id = $agent->client_id;
		} else {
			$agent_id = 0;
		}
	}

	$building_list = Permission::list_buildings([], "building.client_id = '$client_id' AND building.is_tenanted = 1");
	$building_list = array_merge([[ 'value' => '', 'description' => 'None' ]], $building_list ?: []);
?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-purple"><?= $agent_id ? 'Edit Agent Information' : 'Add Agent'; ?></h4>
</div>
<div class="modal-body no-padding">

<?php
	$fields = [
		'name' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Agent Name',
				'value' => $agent ? $agent->name : '',
				'attr' => ['autofocus']
			]
		],
		'email_address' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Email address',
				'value' => $agent ? $agent->email_address : ''
			]
		],
		'office_address' => [
			'type' => 'input',
			'col' => 9,
			'properties' => [
				'label' => 'Office address',
				'value' => $agent ? $agent->office_address : ''
			]
		],
		'postcode' => [
			'type' => 'input',
			'col' => 3,
			'properties' => [
				'label' => 'Post code',
				'value' => $agent ? $agent->postcode : ''
			]
		],
		'telephone_number' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Telephone',
				'value' => $agent ? $agent->telephone_number : ''
			]
		],
		'mobile_number' => [
			'type' => 'input',
			'col' => 6,
			'properties' => [
				'label' => 'Mobile phone',
				'value' => $agent ? $agent->mobile_number : ''
			]
		],
		'client_id' => [
			'type' => 'hidden',
			'properties' => [ 'value' => $client_id ]
		]
	];

	if($agent_id) {
		$fields['agent_id'] = [
			'type' => 'hidden',
			'properties' => [ 'value' => $agent_id ]
		];

		$fields['blank'] = [
			'type' => 'blank',
			'col' => 6
		];
	} else {
		$fields['building_id'] = [
			'type' => 'select2',
			'col' => 6,
			'properties' => [
				'id' => 'crud-agent-building',
				'data' => $building_list,
				'value' => 'id',
				'display' => 'description',
				'label' => 'Assign to building',
				'selected' => $building_id ?: ''
			]
		];
	}

	$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
	$form->id = 'crud-agent-form';
	$form->footer(function() use ($ui) {
		return implode(' ', [
			$ui->create_button('Save', 'success')->attr([ 'type' => 'submit' ])->print_html(true),
			$ui->create_button('Cancel', 'default')->attr([ 'data-dismiss' => 'modal' ])->print_html(true)
		]);
	});

	$form->print_html();
?>

</div>
<script>
	var runFunction = function() {
		var $form = $("#crud-agent-form");

		$form.validate({
			rules: {
				name: { required: true },
				password: { required: true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL; echo $agent_id ? '/ajax/post/update_agent' : '/ajax/post/add_agent'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				});
			}
		});

		$('#crud-agent-building').initSelect2();
	}

	loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
</script>
