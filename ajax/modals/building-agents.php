<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-purple">Assigned agents</h4>
</div>

<?php

$building_id = App::get('building_id', 0, true);
$building = new Building($building_id);

if ($building->validate()) {
	$client_id = $building->info->client_id;
	$agents = App::sql()->query("SELECT id, name FROM agent WHERE client_id = '$client_id' ORDER BY name;");
	$building_agents = array_map(function($building) {
		return $building->agent_id;
	}, App::sql()->query("SELECT agent_id FROM agent_building WHERE building_id = '$building_id';") ?: []);

	if($agents) {
?>
	<form id="widget-building-agents-form">
		<input type="hidden" name="building_id" value="<?= $building_id; ?>">
		<div class="modal-body no-padding">
		<?php
			$agents_data = array_map(function($agent) {
				return (object) [
					'id' => $agent->id,
					'name' => $agent->name
				];
			}, $agents);

			$ui_table = $ui->create_datatable($agents_data, [
				'in_widget' => false,
				'hover' => false,
				'checkboxes' => [
					'name' => 'agents',
					'value' => '{{id}}'
				],
				'bordered' => false
			]);
			$ui_table->id = 'widget-building-agents';
			$ui_table->hidden(['id']);
			$ui_table->col('name', [
				'attr' => [ 'colspan' => 2 ],
				'title' => 'Select agents to assign to '.$building->info->description
			]);

			$ui_table->each('row', function($row) use ($building_agents) {
				$checked = in_array($row->id, $building_agents);
				return [ 'checkbox' => [ 'checked' => $checked ] ];
			});

			$ui_table->js('properties', [
				'displayLength' => 25,
				'lengthMenu' => '[[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]'
			]);

			$ui_table->print_html();
		?>
		</div>
		<div class="modal-footer">
		<?php
				$ui->create_button('Update', 'primary')->print_html();
				$ui->create_button('Cancel', 'default')->attr('data-dismiss', 'modal')->print_html();
		?>
		</div>
	</form>
<?php
	} else {
		$ui->print_warning('No agents found.');
	}
} else {
	$ui->print_warning('Access denied.');
}

?>

<script>
	var runFunction = function() {
		<?php if (isset($ui_table)) { $ui_table->print_js(false, false); ?>
			var $form = $("#widget-building-agents-form"),
				$agentsCheckboxes =  $('input.checkbox', <?= $ui_table->js('oTable'); ?>.fnGetNodes());

			$form.on('submit', function(e) {
				e.preventDefault();
				$.post('<?= APP_URL ;?>/ajax/post/assign_building_agents', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				}, 'json');
			});

			var $checkAll = $("#widget-building-agents th input:checkbox");
			$checkAll.on("change" , function() {
				var value = this.checked;
				$agentsCheckboxes.each(function() {
					this.checked = value;
				});
			});

			$agentsCheckboxes.on('change', function() {
				if (!this.checked) $checkAll.get(0).checked = false;
			});
		<?php } ?>
	};

	loadScript('<?=ASSETS_URL ?>/js/plugin/select2/select2.js', function() {
		loadScript("<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js", function() {
			loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/jquery.dataTables.min.js", function() {
				loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.colVis.min.js", function() {
					loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.tableTools.min.js", function() {
						loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.bootstrap.min.js", function() {
							loadScript("<?= ASSETS_URL ?>/js/plugin/datatable-responsive/datatables.responsive.min.js", runFunction)
						});
					});
				});
			});
		});
	})
</script>
