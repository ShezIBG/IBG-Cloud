<?php require_once '../init.ajax.php'; ?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title txt-color-purple">Assigned buildings</h4>
</div>

<?php
	$agent_id = App::get('agent_id', 0, true);
	$agent = null;
	if ($agent_id) $agent = App::sql()->query_row("SELECT * FROM agent WHERE id = '$agent_id';");

	if ($agent) {
		$buildings = Permission::list_buildings([], "building.client_id = '$agent->client_id' AND building.is_tenanted = 1");
		$agent_buildings = array_map(function($building) {
			return $building->building_id;
		}, App::sql()->query("SELECT building_id FROM agent_building WHERE agent_id = '$agent_id';") ?: []);

		if ($buildings) {
?>
	<form id="widget-agent-buildings-form">
		<input type="hidden" name="agent_id" value="<?= $agent_id; ?>">
		<div class="modal-body no-padding">
		<?php
			$buildings_data = array_map(function($building) {
				return (object) [
					'id' => $building->id,
					'name' => $building->description
				];
			}, $buildings);

			$ui_table = $ui->create_datatable($buildings_data, [
				'in_widget' => false,
				'hover' => false,
				'checkboxes' => [
					'name' => 'buildings',
					'value' => '{{id}}'
				],
				'bordered' => false
			]);
			$ui_table->id = 'widget-agent-buildings';
			$ui_table->hidden(['id']);
			$ui_table->col('name', [
				'attr' => [ 'colspan' => 2 ],
				'title' => 'Select buildings to assign to '.$agent->name
			]);

			$ui_table->each('row', function($row) use ($agent_buildings) {
				$checked = in_array($row->id, $agent_buildings);
				return [ 'checkbox' => [ 'checked' => $checked ] ];
			});

			$ui_table->js('properties', [
				'displayLength' => -1,
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
			$ui->print_warning('No building found for this user');
		}
	} else {
		$ui->print_warning('Agent not found');
	}

?>

<script>
	var runFunction = function() {
		<?php if (isset($ui_table)) { $ui_table->print_js(false, false); ?>
			var $form = $("#widget-agent-buildings-form"),
				$buildingsCheckboxes =  $('input.checkbox', <?= $ui_table->js('oTable'); ?>.fnGetNodes());

			$form.on('submit', function(e) {
				e.preventDefault();
				$.post('<?= APP_URL ;?>/ajax/post/assign_agent_buildings', $form.serialize(), function(data) {
					$.modalResult(data, refreshTenantView);
				}, 'json');
			});

			var $checkAll = $("#widget-agent-buildings th input:checkbox");
			$checkAll.on("change" , function() {
				var value = this.checked;
				$buildingsCheckboxes.each(function() {
					this.checked = value;
				});
			});

			$buildingsCheckboxes.on('change', function() {
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
