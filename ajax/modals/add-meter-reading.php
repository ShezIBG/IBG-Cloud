<?php require_once '../init.ajax.php'; ?>
<?php
	
	if(isset($_POST['selected_date_change']))
	{
		//$meter_date_change = new Meter($_POST['meter_id']);
		//$newDateFormat = date("Y-d-m", strtotime($_POST['selected_date_change']) );
		//print_r($newDateFormat);exit;
		//$prev_day_reading = $meter_date_change->get_date_change_reading($newDateFormat, $_POST['meter_id']);
		//echo $prev_day_reading->reading;
		//exit;
		$meter_date_change = new Meter($_POST['meter_id']);			
		$explode =explode("/",$_POST['selected_date_change']);			
		$array = $explode [2]."-".$explode[1]."-".$explode[0];
		$prev_day_reading = $meter_date_change->get_date_change_reading($array, $_POST['meter_id']);
		echo $prev_day_reading->reading;
		exit;
	}

?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title">Add meter reading</h4>
</div>
<div class="modal-body no-padding">

<?php
	list($building_id, $meter_id) = App::get(['building_id', 'meter_id'], '');

	if (!$building_id) {
		$ui->print_danger('Building not found.');
	} else {
		$building = new Building($building_id);
		$meter = new Meter($meter_id);

		if(!$meter->validate($building_id)) {
			$ui->print_danger('Invalid meter.');
		} else {
			if($meter->is_automatic()) $ui->print_warning('Meter is read automatically, no need to add manual readings.');

			$n = $meter->get_number_of_readings();
			$latest = $meter->get_latest_reading();
			$prev_dt = date('Y-m-d',strtotime("-1 days"));
			$prev_day_reading = $meter->get_date_reading($prev_dt);


			$fields = [
				'last_header' => [
					'type'       => 'blank',
					'col'        => 6,
					'properties' => '<h2>Previous reading</h2>'
				],
				'this_header' => [
					'type'       => 'blank',
					'col'        => 6,
					'properties' => '<h2>This reading</h2>'
				],
				'last_reading_date' => [
					'type' => 'blank',
					'col'  => 6,
					'properties' => [
						'label'   => 'Date',
						'content' => $latest ? App::format_datetime('d/m/Y', $latest->reading_date, 'Y-m-d') : 'Never'
					]
				],
				'reading_date' => [
					'type' => 'input',
					'col'  => 6,
					'properties' => [
						'id'          => 'reading_date',
						'placeholder' => 'dd/mm/yyyy',
						'label'       => 'Date',
						'value'       => App::format_datetime('d/m/Y', MySQL::get_date(), 'Y-m-d')
					]
				],
				'last_reading_1' => [
					'type' => 'blank',
					'col'  => 6,
					'properties' => [
						'label'   => "Day ({$meter->get_reading_unit(true)})",
						'content' => $latest && $latest->reading_1 ? $latest->reading_1 : '&ndash;'
					]
				],
				'reading_1' => [
					'type' => 'input',
					'col'  => 6,
					'properties' => [
						'label' => "Day ({$meter->get_reading_unit(true)})",	
						'value' => $prev_day_reading->reading
					]
				]
			];

			if($n >= 2) {
				$fields = array_merge($fields, [
					'last_reading_2' => [
						'type' => 'blank',
						'col'  => 6,
						'properties' => [
							'label'   => "Night ({$meter->get_reading_unit(true)})",
							'content' => $latest && $latest->reading_2 ? $latest->reading_2 : '&ndash;'
						]
					],
					'reading_2' => [
						'type'       => 'input',
						'col'        => 6,
						'properties' => [ 'label' => "Night ({$meter->get_reading_unit(true)})" ]
					]
				]);
			}

			if($n >= 3) {
				$fields = array_merge($fields, [
					'last_reading_3' => [
						'type' => 'blank',
						'col'  => 6,
						'properties' => [
							'label'   => "Evening/Weekend ({$meter->get_reading_unit(true)})",
							'content' => $latest && $latest->reading_3 ? $latest->reading_3 : '&ndash;'
						]
					],
					'reading_3' => [
						'type'       => 'input',
						'col'        => 6,
						'properties' => [ 'label' => "Evening/Weekend ({$meter->get_reading_unit(true)})" ]
					]
				]);
			}

			$fields = array_merge($fields, [
				'spacer' => [
					'type' => 'blank',
					'col'  => 6
				],
				'initial_reading' => [
					'type' => 'checkbox',
					'col'  => 6,
					'properties' => [
						'items' => [[
							'checked' => !$latest,
							'label'   => 'Initial meter reading?'
						]]
					]
				]
			]);

			$fields = array_merge($fields, [
				'building_id' => [
					'type'       => 'hidden',
					'properties' => [ 'value' => $building->id ]
				],
				'meter_id' => [
					'type'       => 'hidden',
					'properties' => [ 'value' => $meter_id ]
				]
			]);

			$form = $ui->create_smartform($fields, [ 'in_widget' => false ]);
			$form->id = 'add-meter-reading-form';

			$form->footer(function() use ($ui) {
				return implode(' ', [
					$ui->create_button('Add', 'success')->attr([ 'type' => 'submit' ])->print_html(true),
					$ui->create_button('Cancel', 'default')->attr([ 'data-dismiss' => 'modal' ])->print_html(true)
				]);
			});

			$form->print_html();
		}
	}
?>

</div>

<script>
	var runFunction = function() {
		var $form = $('#add-meter-reading-form');

		$('#reading_date').datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>'
		}).on("change", function() {
		    var newDate = this.value
		    $.ajax({
		         url: "ajax/modals/add-meter-reading.php",
		         type: "POST",
		         async: true,
		         data: {
		            selected_date_change: newDate,
		            meter_id:<?php echo $meter_id?> 
		         },
		         success: function(resp) {
		            if (resp) {
		               $('input[name=reading_1]').val(resp);
		            } else {
		               alert("No Data Found");
		            }
		         }
		      });

		  });

		$form.validate({
			rules: {
				reading_1: { required : true }
			},

			errorPlacement: function(error, element) {
				error.insertAfter(element.parent());
			},

			submitHandler: function() {
				$.post('<?= APP_URL.'/ajax/post/add_meter_reading'; ?>', $form.serialize(), function(data) {
					$.modalResult(data, refreshMeterList);
				});
			}
		});
	};

	loadScript('<?= ASSETS_URL ?>/js/plugin/jquery-form/jquery-form.min.js', runFunction);
</script>
