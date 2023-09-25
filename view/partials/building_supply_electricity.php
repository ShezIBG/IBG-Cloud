<?php
	require_once '../init.view.php';

	$building_id = App::get('building_id', '', true);
	if(!$building_id) {
		$ui->print_alert('No building set.', 'warning');
		return;
	}

	$building = new Building($building_id);

	// Get meter details
	$e_meter = App::sql()->query_row("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id' AND m.meter_type = 'E' LIMIT 1;");
	$g_meter = App::sql()->query_row("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id' AND m.meter_type = 'G' LIMIT 1;");
	$w_meter = App::sql()->query_row("SELECT m.id FROM meter AS m JOIN area AS a ON a.id = m.area_id JOIN floor AS f ON f.id = a.floor_id WHERE f.building_id = '$building_id' AND m.meter_type = 'W' LIMIT 1;");
?>

<header class="bg-e">
	<span>Landlord's Supply - Electricity</span>
	<ul>
		<?php if($e_meter) { ?><li><a href="#" data-view="building_supply_electricity?building_id=<?= $building_id; ?>" class="bg-e active"><i class="eticon eticon-shadow eticon-bolt"></i></a>
		</li><?php }; if($g_meter) { ?><li><a href="#" data-view="building_supply_gas?building_id=<?= $building_id; ?>"><i class="eticon eticon-shadow eticon-flame"></i></a>
		</li><?php }; if($w_meter) { ?><li><a href="#" data-view="building_supply_water?building_id=<?= $building_id; ?>"><i class="eticon eticon-shadow eticon-droplet"></i></a></li><?php } ?>
	</ul>
</header>

<?php
	// Set the meter used on the current form

	$meter = $e_meter ? new Meter($e_meter->id) : null;

	if (!$meter) {
		$ui->print_alert('No electricity meter found.', 'warning');
		return;
	}
?>

<div class="content electricity">
	<div class="row">

<?php
	$mpan = $meter->info->mpan;
	if (!$mpan) $mpan = '';
	$mpan = str_replace('"', '', $mpan);
	$mpan_chunks = [
		substr($mpan, 0, 2),
		substr($mpan, 2, 3),
		substr($mpan, 5, 3),
		substr($mpan, 8, 2),
		substr($mpan, 10, 8),
		substr($mpan, 18, 3)
	];

	$mpan_content = '
		<label class="label">Supply number</label>
		<input type="hidden" name="mpan" id="mpan">
		<table class="mpan" style="width:100%">
			<tr>
				<td rowspan="2" style="width:20%; color:white; font-size: 2.3em; text-align:center; font-weight:bold;" class="bg-e">S</td>
				<td colspan="2" style="width:26%"><input type="text" id="mpan_1" name="mpan_1" maxlength="2" value="'.$mpan_chunks[0].'" placeholder="00"></td>
				<td colspan="2" style="width:27%"><input type="text" id="mpan_2" name="mpan_2" maxlength="3" value="'.$mpan_chunks[1].'" placeholder="000"></td>
				<td colspan="2" style="width:27%"><input type="text" id="mpan_3" name="mpan_3" maxlength="3" value="'.$mpan_chunks[2].'" placeholder="000"></td>
			</tr>
			<tr>
				<td rowspan="1" style="width:20%"><input type="text" id="mpan_4" name="mpan_4" maxlength="2" value="'.$mpan_chunks[3].'" placeholder="00"></td>
				<td colspan="4" style="width:40%"><input type="text" id="mpan_5" name="mpan_5" maxlength="8" value="'.$mpan_chunks[4].'" placeholder="00000000"></td>
				<td colspan="1" style="width:20%"><input type="text" id="mpan_6" name="mpan_6" maxlength="3" value="'.$mpan_chunks[5].'" placeholder="000"></td>
			</tr>
		</table>
	';

	$fields = [
		'meter_id' => [
			'type'       => 'hidden',
			'properties' => [ 'value' => $meter->id ]
		],
		'serial_number' => [
			'type' => 'input',
			'col'  => 6,
			'properties' => [
				'value' => $meter->info->serial_number,
				'label' => '<br><br>Serial number'
			]
		],
		'mpan' => [
			'type'       => 'blank',
			'col'        => 6,
			'properties' => [ 'content' => $mpan_content ]
		],
		'tariff_id' => [
			'type' => 'select2',
			'col'  => 6,
			'properties' => [
				'id'       => 'tariff_id',
				'data'     => $meter->get_available_tariffs($building->info->client_id),
				'value'    => 'id',
				'display'  => 'description',
				'selected' => $meter->info->tariff_id,
				'label'    => 'Choose Tariff'
			]
		],
		'add_tariff' => [
			'type'       => 'blank',
			'col'        => 3,
			'properties' => [ 'content' => '<label class="label">&nbsp;</label> <a href="#" id="add_tariff" class="btn">Add A Tariff</a>' ]
		],
		'tariff_change_date' => [
			'type' => 'input',
			'col'  => 3,
			'properties' => [
				'id'          => 'tariff_change_date',
				'placeholder' => 'dd/mm/yyyy',
				'label'       => 'Tariff change date'
			]
		],
		'spacer' => [
			'type' => 'blank',
			'col'  => 10
		],
		'update' => [
			'type'       => 'blank',
			'col'        => 2,
			'properties' => [ 'content' => '<label class="label">&nbsp;</label> <button id="update" class="primary" style="width:100%;padding-left:0;padding-right:0;">Update</button>' ]
		]
	];

	$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
	$form->id = 'supply-form';
	$form->print_html();
?>

	</div>
	<div id="tariff-info" class="row"></div>
</div>

<script>
	$(function() {
		var $view = $('#view');

		$view.find('header ul').on('click', 'li a', function(e) {
			e.preventDefault();
			var $this = $(this);
			if (!$this.is('.active')) {
				LoadTenantView($this.data('view'));
			}
		});

		$("#tariff_change_date").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		});

		$('#tariff_id').initSelect2();

		$('#tariff_id').on('change', function() {
			var originalTariff = '<?= $meter->info->tariff_id ?: ''; ?>';
			var newTariff = $(this).val();

			$('#tariff_change_date').closest('section').find('*').toggle( !!originalTariff && newTariff != originalTariff );
			getTariffDetails();
		});

		window.refreshTariffs = function(sel) {
			var $field = $('#tariff_id');

			// If no selection sent through, remember original value
			if(!sel) sel = $field.val();

			$.getJSON('<?= APP_URL ?>/ajax/get/meter_tariffs?meter_id=<?= $meter->id; ?>', function(result) {
				$field.find('option').remove();
				for(var i = 0; i < result.data.length; i++) {
					var tariff = result.data[i];
					$field.append($('<option value="' + tariff.id + '"></option>').text(tariff.description));
				}

				// Set new or previous value
				if(sel) {
					$field.select2('val', sel);
					$field.trigger('change');
				}
			});
		};

		var getTariffDetails = function() {
			var id = $('#tariff_id').val();

			var showTariffInfo = function(data) {
				if(!data || data == '') {
					$('#tariff-info').html('').addClass('hidden');
				} else {
					$('#tariff-info').html(data).removeClass('hidden');
				}
			}

			if(!id || id == 'NULL') {
				showTariffInfo();
				return;
			}

			$.getJSON('<?= APP_URL ?>/ajax/get/tariff_electricity_info?tariff_id=' + id, function(result) {
				showTariffInfo(result.data);
			});
		};

		// Get tariff details on form show
		getTariffDetails();

		// Hide tariff change date by default
		$('#tariff_change_date').closest('section').find('*').hide();

		// Form validation and posting

		var $form = $("#supply-form");

		var getMpan = function() {
			var mpan = '';
			$('#mpan_1,#mpan_2,#mpan_3,#mpan_4,#mpan_5,#mpan_6').each(function() {
				mpan += '' + $(this).val();
			});
			return mpan;
		};

		var isMpanValid = function() {
			return getMpan().length === 21;
		};

		$('#update').on('click', function(e) {
			e.preventDefault();

			if (!isMpanValid()) {
				$.messagebox('Please enter the correct supply number.', {
					title: '<strong><span class="txt-color-red">Error</span></strong>',
					iconClass: 'fa fa-exclamation-triangle txt-color-red',
					buttons: '[OK]'
				});
				return;
			}

			if($('#tariff_change_date').is(':visible') && !$('#tariff_change_date').val()) {
				$.messagebox('Please enter the date your tariff changes.', {
					title: '<strong><span class="txt-color-red">Error</span></strong>',
					iconClass: 'fa fa-exclamation-triangle txt-color-red',
					buttons: '[OK]'
				});
				return;
			}

			$('#mpan').val(getMpan());
			$.post('<?= APP_URL ;?>/ajax/post/update_landlord_supply', $form.serialize(), function(data) {
				$.ajaxResult(data, function() {
					$.messagebox('Electricity supply updated.', {
						title: '<strong><span class="txt-color-green">Success</span></strong>',
						iconClass: 'fa fa-check txt-color-green',
						buttons: '[OK]'
					}, refreshTenantView);
				});
			});
		});

		// Tariff add/edit handlers
		var addTariff = function(e) {
			if(e) e.preventDefault();
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/crud-tariff-electricity.php?client_id=<?= $building->info->client_id; ?>&mpan=' + getMpan(), { size: 'lg' });
		};

		var editTariff = function(el) {
			var id = $(el).data('id');
			$.ajaxModal('<?= APP_URL ?>/ajax/modals/crud-tariff-electricity.php?client_id=<?= $building->info->client_id; ?>&mpan=' + getMpan() + '&tariff_id=' + id, { size: 'lg' });
		};

		$('#add_tariff').on('click', addTariff);

		$('#tariff-info').on('click', 'button', function(e) {
			e.preventDefault();
			var $this = $(this);
			if($this.data('type') == 'edit') editTariff($this);
		});
	});
</script>
