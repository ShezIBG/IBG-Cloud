<?php
	require_once '../init.view.php';

	$agent_id = App::get('agent_id', 0, true);

	$agent = null;
	if($agent_id) $agent = App::sql()->query_row("SELECT * FROM agent WHERE id = '$agent_id';");

?>

<?php
	if (!$agent) {
		$ui->print_alert('Agent not found.', 'warning');
		exit;
	}

	// Get list of assigned buildings
	$agent_buildings = App::sql()->query("SELECT b.id, b.description FROM agent_building AS ab JOIN building AS b ON b.id = ab.building_id WHERE ab.agent_id = '$agent_id' ORDER BY b.description;");
	$buildings_desc = '';
	foreach ($agent_buildings as $ab) {
		$buildings_desc .= "$ab->description<br>";
	}
?>

<div class="content agent txt-color-darken">

<?php
	$fields = [
		'name' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'label' => 'Agent Name',
				'value' => $agent->name,
				'attr'  => ['autofocus']
			]
		],
		'email_address' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'label' => 'Email address',
				'value' => $agent->email_address
			]
		],
		'office_address' => [
			'type' => 'input',
			'col'  => 9,
			'properties' => [
				'label' => 'Office address',
				'value' => $agent->office_address
			]
		],
		'postcode' => [
			'type' => 'input',
			'col'  => 3,
			'properties' => [
				'label' => 'Post code',
				'value' => $agent->postcode
			]
		],
		'telephone_number' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'label' => 'Telephone',
				'value' => $agent->telephone_number
			]
		],
		'mobile_number' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'label' => 'Mobile phone',
				'value' => $agent->mobile_number
			]
		],
		'agent_id' => [
			'type'       => 'hidden',
			'properties' => [ 'value' => $agent_id ]
		],
		'buildings' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => '<label class="label">Assigned buildings</label><p>'.($buildings_desc ?: 'None').'</p>'
		],
		'save' => [
			'type'       => 'blank',
			'col'        => 12,
			'properties' => [ 'content' => '<a href="#" id="change-buildings" class="btn primary">'.($buildings_desc ? 'Change' : 'Assign').' buildings</a><button id="save-agent" class="primary pull-right">Save</button>' ]
		]
	];

	$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
	$form->id = 'agent-details-form';

	$form->print_html();
?>

</div>

<script>
	$(function() {
		selectMenuItem($('#menu-agents'));

		var runFunction = function() {
			var $form = $("#agent-details-form");

			$form.validate({
				rules: {
					name: { required: true }
				},

				errorPlacement: function(error, element) {
					error.insertAfter(element.parent());
				},

				submitHandler: function() {
					$.post('<?= APP_URL.($agent_id ? '/ajax/post/update_agent' : '/ajax/post/add_agent'); ?>', $form.serialize(), function(data) { //where to send the data
						$.ajaxResult(data, function() {
							$.messagebox('Agent updated.', {
								title: '<strong><span class="txt-color-green">Success</span></strong>',
								iconClass: 'fa fa-check txt-color-green',
								buttons: '[OK]'
							}, refreshTenantView);
						});
					});
				}
			});

			$('#save-agent').on('click', function(e) {
				e.preventDefault();
				$form.submit();
			});

			$('#change-buildings').on('click', function(e) {
				e.preventDefault();
				$.ajaxModal('<?= APP_URL ?>/ajax/modals/agent-buildings.php?agent_id=<?= $agent_id; ?>');
			});
		}

		loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", runFunction);
	});
</script>
