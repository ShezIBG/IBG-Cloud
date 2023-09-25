<?php
	require_once '../init.view.php';

	// Get building details

	$building_id = App::get('building_id', null, true);
	if(!$building_id) {
		$ui->print_alert('Building not found.', 'warning');
		return;
	}

	$building = new Building($building_id);
	$client_id = $building->info->client_id;
	$agents = App::sql()->query("SELECT a.* FROM agent AS a JOIN agent_building AS ab ON ab.agent_id = a.id WHERE ab.building_id = '$building_id' ORDER BY a.name;");
?>

<div class="container-fluid content agent txt-color-darken">
	<div id="assigned-agent-list" class="row">
		<?php if ($agents) {
				foreach ($agents as $agent) {
		?>
			<div class="col col-md-6">
				<div class="agent-card widget-shadow">
					<a href="#" data-id= "<?= $agent->id; ?>"class="edit-agent pull-right"><i class="eticon eticon-gear"></i></a>
					<?php
						echo '<label class="font-lg">'.$agent->name.'</label><br>';
						if($agent->email_address) echo '<label>Email</label> <a href="mailto:'.$agent->email_address.'">'.$agent->email_address.'</a><br>';
						if($agent->telephone_number) echo '<label>Telephone</label> '.$agent->telephone_number.'<br>';
						if($agent->mobile_number) echo '<label>Mobile Phone</label> '.$agent->mobile_number.'<br>';
						if($agent->office_address) echo '<label>Office address</label><br>'.$agent->office_address.', '.$agent->postcode.'<br>';
					?>
				</div>
			</div>
		<?php
				}
			} else {
				echo '<br>No agents found.';
			}
		?>
	</div>
	<br><button id="assign-agents" class="primary"><?= $agents ? 'Change Agents' : 'Assign Agents'; ?></button> <button id="add-agent" class="primary pull-right">Add an Agent</button>
</div>

<script>
	$(function() {
		selectMenuItem($('#menu-agents'));

		$('#add-agent').on('click', function(e) {
			e.preventDefault();
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/crud-agent.php?client_id=<?= $client_id; ?>&building_id=<?= $building_id; ?>');
		});

		$('#assign-agents').on('click', function(e) {
			e.preventDefault();
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/building-agents.php?building_id=<?= $building_id; ?>');
		});

		$('#assigned-agent-list').on('click', '.edit-agent', function(e) {
			e.preventDefault();
			var agentId = $(this).data('id');
			$('#agent-list a[data-id="' + agentId + '"]').click();
		});
	});
</script>
