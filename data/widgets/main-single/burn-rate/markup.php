
<?php
	$building = $user->get_default_building(Permission::ELECTRICITY_ENABLED);
if($building->id == 138)
{
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
		
	$ui_widget->id = $widget_info->ui_id;
	
	$ui_widget->color = 'blueDark';
	
	
	$ui_widget->header('title', '<p class="myWidget-title">Request Live Data</p>');
	
	$content ="
	<script src='https://cdn.jsdelivr.net/npm/gaugeJS/dist/gauge.min.js'></script>
	
	
	<script>
	var selectedOption = document.getElementById('location')
	
	var liveButton = document.getElementById('liveData-btn')
	
	selectedOption.addEventListener('change', function(){
		var selectedOptionValue = selectedOption.value
		if(selectedOptionValue !== ''){
			liveButton.disabled = false
		}else{
			liveButton.disabled = true
		}
	})
	
	
	document.getElementById('liveData-btn').addEventListener('click', createGauges);
	var gaugeLoader = document.getElementById('gauge-loader')
	
	function showLoader() {
		var loader = document.getElementById('loader');
		loader.style.display = 'block';
	}
	  
	function hideLoader() {
		var loader = document.getElementById('loader');
		loader.style.display = 'none';
	}
	
	function createGauges(){
		showLoader()
	
		//initialising gauge variables
		var opts = {
			angle: -0.25,
			lineWidth: 0.2,
			radiusScale: 1,
			pointer: {
				length: 0.6,
				strokeWidth: 0.035,
				color: '#000000'
			},
			limitMax: true,
			limitMin: true,
			colorStart: '#6FADCF',
			colorStop: '#8FC0DA',
			strokeColor: '#E0E0E0',
			generateGradient: true,
			highDpiSupport: true
		};
	
		//initialising guages
		var gauge = new Gauge(document.getElementById('gaugeCanvas')).setOptions(opts);
		
		// Set gauge range
		gauge.minValue = 0;
		gauge.maxValue = 500;
		
		// Set initial value
		gauge.set(250);
	
		// Create and configure the second gauge
		var gauge2 = new Gauge(document.getElementById('gaugeCanvas2')).setOptions(opts);
		gauge2.minValue = 0;
		gauge2.maxValue = 20;
		gauge2.set(10);
	
		// Create and configure the second gauge
		var gauge3 = new Gauge(document.getElementById('gaugeCanvas3')).setOptions(opts);
		gauge3.minValue = 0;
		gauge3.maxValue = 500;
		gauge3.set(250);
	
		var selectedOptionValue = selectedOption.value;
		var intValue = parseFloat(selectedOptionValue);
	
		getLiveData(gauge, gauge2, gauge3, intValue)
	}
	
	function getLiveData(gauge, gauge2, gauge3, intValue){
		gaugeLoader.style.display = 'none';
	
		var loopCount = 0;
		var maxLoops = 12;
	
		var dataArray = [];
		var device_type = 2;
	
		function executeScript() {
			var PhaseFreqArray = [];
			var CTCurrentArray = [];
			var CTPowerArray = [];
			$.ajax({
				url: 'https://ibg-uk.cloud/eticom/data/widgets/main-single/burn-rate/ssh_script.php',
				type: 'POST',
				data: { arg1: 'whitemoss', arg2: '6214' },
				dataType: 'html',
	
				success: function(output) {
					var data = output.split(',');
					
					// Other Device Types - add later
					if(device_type == 1){
						console.log('Device type not configured');
					}
	
					// Handle PM12 Device Type
					else if (device_type == 2){
						// Seperate Volt and Freq
						for (var i = 0; i < 2; i++) {
							PhaseFreqArray.push(data[i]);
						}
	
						// Seperate Current CT1-CT12
						for (var c = 2; c < 11; c++) {
							CTCurrentArray.push(data[c]);
						}
	
						// Seperate Power CT1-CT12
						for (var p = 11; p < 20; p++) {
							CTPowerArray.push(data[p]);
						}
	
						hideLoader()
						gaugeLoader.style.display = 'flex';
	
						updatePhase(parseFloat(PhaseFreqArray[0]), gauge);
						updateCurrent(parseFloat(CTCurrentArray[intValue]), gauge2);
						updatePower(parseFloat(CTPowerArray[intValue]), gauge3);
					}
	
					// Other Device Types - add later
					else if(device_type == 3){
						console.log('Device type not configured');
					}
					// Other Device Types - add later
					else if(device_type == 4){
						console.log('Device type not configured');
					}
					// Other Device Types - add later
					else if(device_type == 5){
						console.log('Device type not configured');
					}
	
					loopCount++;
					
					if (loopCount < maxLoops) {
						executeScript(loopCount);
						document.getElementById('location').setAttribute('disabled', 'disabled');
					}
	
					if (loopCount == (maxLoops - 1)){
						document.getElementById('location').removeAttribute('disabled');
					}
				},
				error: function() {
					$('#output').append('<p>Error executing script.</p>');
				}
			});
		}
		executeScript();
	}
	</script>
	
	<script>
	// Update gauge value
	function updatePhase(value, gauge) {
		gauge.set(value);
		document.getElementById('gaugeValue').innerText = value+ ' Volts';
	}
	
	function updateCurrent(value, gauge) {
		gauge.set(value);
		document.getElementById('gaugeValue2').innerText = value + ' Amps';
	}
	
	function updatePower(value, gauge) {
		gauge.set(value);
		document.getElementById('gaugeValue3').innerText = value+ ' Watts';
	
	}
	</script>
	
	<style>
	.gauge-container {
		display: flex;
	}
	.gauge-wrapper {
		margin-left: -84px;
		text-align: center;
	}
	.gauge-value {
		margin-top: 10px;
		font-weight: bold;
	}
	
	/* Safari */
	@-webkit-keyframes spin {
	  0% { -webkit-transform: rotate(0deg); }
	  100% { -webkit-transform: rotate(360deg); }
	}
	
	@keyframes spin {
	  0% { transform: rotate(0deg); }
	  100% { transform: rotate(360deg); }
	}
	
	
	.box{
		width: 240px;
		height: 150px;
		position: absolute;
		top: calc(50% - 25px);
		top: -webkit-calc(50% - 25px);
		left: calc(50% - 120px);
		left: -webkit-calc(50% - 120px);
	  }
	  
	   
	  
	  .status_text{
		font-family: 'Poppins', sans-serif !important;
		color: #465262;
		font-weight: 300;
		font-size: 45px;
		position: absolute;
		top: calc(50% - 105px);
		top: -webkit-calc(50% - 105px);
		left: calc(50% - 160px);
		left: -webkit-calc(50% - 160px);
		oapcity: 1;
		-webkit-animation: fade-in-out 2.5s infinite; 
		-moz-animation: fade-in-out 2.5s infinite; 
		-o-animation: fade-in-out 2.5s infinite; 
		animation: fade-in-out 2.5s infinite; 
	  }
	  
	   
	  
	  .comp{
		position: absolute;
		top: 0px;
		width: 80px;
		height: 55px;
		border: 3px solid #465262;
		border-radius: 5px;
	  }
	  
	   
	  
	  .comp:after{
		content: '';
		position: absolute;
		z-index: 5;
		top: 19px;
		left: 5px;
		width: 65px;
		height: 10px;
		border-radius: 360px;
		border: 3px solid #465262;
	  }
	  
	   
	  
	  .loader{
		position: absolute;
		z-index: 5;
		top: 23px;
		left: 10px;
		width: 8px;
		height: 8px;
		border-radius: 360px;
		background: linear-gradient(90deg, rgba(190,222,24,1) 0%,
		rgba(0,151,206,1) 81%, rgba(0,151,206,1) 100%);
		-webkit-animation: loader 5s infinite linear 0.5s;
		-moz-animation: loader 5s infinite linear 0.5s;
		-o-animation: loader 5s infinite linear 0.5s;
		animation: loader 5s infinite linear 0.5s;
	  }
	  
	  .con{
		position: absolute;
		top: 28px;
		left: 85px;
		width: 100px;
		height: 3px;
		background: #465262;
	  }
	  
	   
	  
	  .byte{
		position: absolute;
		top: 25px;
		left: 80px;
		height: 9px;
		width: 9px;
		background: linear-gradient(90deg, rgba(190,222,24,1) 0%,
		  rgba(0,151,206,1) 81%, rgba(0,151,206,1) 100%);;
		border-radius: 360px;
		z-index: 6;
		opacity: 0;
		-webkit-animation: byte_animate 5s infinite linear 0.5s;
		-moz-animation: byte_animate 5s infinite linear 0.5s;
		-o-animation: byte_animate 5s infinite linear 0.5s;
		animation: byte_animate 5s infinite linear 0.5s;
	  }
	  
	   
	  
	  .server{
		position: absolute;
		top: 22px;
		left: 185px;
		width: 35px;
		height: 35px;
		z-index: 1;
		border: 4px solid #465262;
		background: linear-gradient(90deg, rgba(190,222,24,1) 0%,
		  rgba(0,151,206,1) 81%, rgba(0,151,206,1) 100%);;
		border-radius: 360px;
		-webkit-transform: rotateX(58deg);
		-moz-transform: rotateX(58deg);
		-o-transform: rotateX(58deg);
		transform: rotateX(58deg);
	  }
	  
	   
	  
	  .server:before{
		content: '';
		position: absolute;
		top: -47px;
		left: -3px;
		width: 35px;
		height: 35px;
		z-index: 20;
		border: 3px solid #465262;
		background: linear-gradient(90deg, rgba(190,222,24,1) 0%,
		  rgba(0,151,206,1) 81%, rgba(0,151,206,1) 100%);;
		border-radius: 360px;
	  }
	  
	   
	  
	  .server:after{
		position: absolute;
		top: -26px;
		left: -3px;
		border-left: 3px solid #465262;
		border-right: 3px solid #465262;
		width: 35px;
		height: 40px;
		z-index: 17;
		background: linear-gradient(90deg, rgba(190,222,24,1) 0%,
		  rgba(0,151,206,1) 81%, rgba(0,151,206,1) 100%);;
		content: '';
	  }
	  
	   
	  
	  /*Byte Animation*/
	  @-webkit-keyframes byte_animate{
		0%{
		  opacity: 0;
		  left: 80px;
		}
		4%{
		  opacity: 1;
		}
		46%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		  left: 185px;
		}
		54%{
		  opacity: 1;
		}
		96%{
		  opacity: 1;
		}
		100%{
		  opacity: 0;
		  left: 80px;
		}
	  }
	  
	   
	  
	  @-moz-keyframes byte_animate{
		0%{
		  opacity: 0;
		  left: 80px;
		}
		4%{
		  opacity: 1;
		}
		46%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		  left: 185px;
		}
		54%{
		  opacity: 1;
		}
		96%{
		  opacity: 1;
		}
		100%{
		  opacity: 0;
		  left: 80px;
		}
	  }
	  
	   
	  
	  @-o-keyframes byte_animate{
		0%{
		  opacity: 0;
		  left: 80px;
		}
		4%{
		  opacity: 1;
		}
		46%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		  left: 185px;
		}
		54%{
		  opacity: 1;
		}
		96%{
		  opacity: 1;
		}
		100%{
		  opacity: 0;
		  left: 80px;
		}
	  }
	  
	   
	  
	  @keyframes byte_animate{
		0%{
		  opacity: 0;
		  left: 80px;
		}
		4%{
		  opacity: 1;
		}
		46%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		  left: 185px;
		}
		54%{
		  opacity: 1;
		}
		96%{
		  opacity: 1;
		}
		100%{
		  opacity: 0;
		  left: 80px;
		}
	  }
	  
	   
	  
	  /*LOADER*/
	  @-webkit-keyframes loader{
		0%{
		  width: 8px;
		}
		100%{
		  width: 63px;
		}
	  }
	  
	   
	  
	  @-moz-keyframes loader{
		0%{
		  width: 8px;
		}
		100%{
		  width: 63px;
		}
	  }
	  
	   
	  
	@-o-keyframes loader{
		0%{
		  width: 8px;
		}
		100%{
		  width: 63px;
		}
	}
	  
	@keyframes loader{
		0%{
		  width: 8px;
		}
		100%{
		  width: 63px;
		}
	}
	  
	   
	  
	  
	/*FADE IN-OUT*/
	@-webkit-keyframes fade-in-out{
		0%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		}
		100%{
		  oapcity: 1;
		}
	}
	
	@-moz-keyframes fade-in-out{
		0%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		}
		100%{
		  oapcity: 1;
		}
	}
	
	@-o-keyframes fade-in-out{
		0%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		}
		100%{
		  oapcity: 1;
		}
	}
	
	@keyframes fade-in-out{
		0%{
		  opacity: 1;
		}
		50%{
		  opacity: 0;
		}
		100%{
		  oapcity: 1;
		}
	}
	</style>
	";
	
	$content .="
	<body>
	<div>
		<button id='liveData-btn' class='btn btn-default' disabled>Connect to your device</button>
		<br>
		<select id='location'>
			<option value=''>Categories</option>
			<option value='0'>Main incomer</option>
			<option value='1'>Downstairs Sockets</option>
			<option value='2'>Car Charger</option>
			<option value='3'>Up lights</option>
			<option value='4'>Appliances</option>
			<option value='5'>Down lights</option>
			<option value='6'>Upstairs Socket</option>
			<option value='7'>Cooker</option>
			<option value='8'>Smoke Alarm/Loft</option>
		</select>
	</div>
	<div id='loader' style='display:none;'>
		<div class='status_text'>
			CONNECTING
		</div>
		<div class='box'>
			<div class='comp'></div>
			<div class='loader'></div>
			<div class='con'></div>
			<div class='byte'></div>
			<div class='server'></div>
		</div>
	</div>
	<div id='gauge-loader' class='gauge-container'>
		
		<div class='gauge-wrapper'>
			<canvas id='gaugeCanvas'></canvas>
			<div id='gaugeValue'></div>
		</div>
	
		<div class='gauge-wrapper'>
			<canvas id='gaugeCanvas2'></canvas>
			<div id='gaugeValue2'></div>
		</div>
	
		<div class='gauge-wrapper'>
			<canvas id='gaugeCanvas3'></canvas>
			<div id='gaugeValue3'></div>
		</div>
	</div>
	</body>";
	
	
	$ui_widget->body('content', $content);
	$ui_widget->class='dashboard-widget dashboard-widget-fixed';
	$ui_widget->print_html();
}
else{
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'teal';

	if ($building = $user->get_default_building(Permission::ELECTRICITY_ENABLED)) {
		$time_period = $dashboard->get_time_period($building->id);

		$categories = App::sql()->query(
			"SELECT DISTINCT
				parent_category.id,
				parent_category.description
			FROM category AS sub_category
			INNER JOIN category AS parent_category
				ON (sub_category.parent_category_id = parent_category.id)
			WHERE sub_category.client_id = '".$building->info->client_id."'
				AND sub_category.id IN (SELECT category_id FROM `{$time_period->ahc_table}` WHERE {$time_period->ahc_cost_in} + {$time_period->ahc_cost_out} > 0
				AND building_id = $building->id)
		");

		$category_id = App::get('category_id', 0);
		if ($categories && !$category_id) $category_id = $categories[0]->id;

		$categories = array_merge([(object)[
			'id' => 0,
			'description' => '(Select)'
		]], is_array($categories) ? $categories : []);

		$burn_rate_data = App::sql()->query(
			"SELECT
				MIN(c.description) AS description,
				SUM(yec.{$time_period->yec_kwh_used}) AS kwh_used,
				SUM(yec.{$time_period->yec_cost}) AS cost
			FROM
				`{$time_period->yec_table}` AS yec
			INNER JOIN category AS c ON c.id = yec.category_id
			WHERE yec.building_id = '$building->id' AND c.parent_category_id = $category_id
			GROUP BY category_id
			HAVING cost > 0
			ORDER BY cost DESC
		") ?: [];

		$ui_table = $ui->create_datatable($burn_rate_data, [
			'static' => true,
			'in_widget' => false,
			'bordered' => false,
			'striped' => false,
			'default_col' => false,
			'columns' => true,
			'hover' => false
		]);

		$ui_table
			->cell('cost', [
				'content' => function($row, $value) {
					return '&pound;'.number_format($value, 2);
				},
				'class' => 'text-right fixed-numbers',
				'attr' => [ 'style' => 'padding-right: 15px' ]
			])
			->cell('kwh_used', [
				'class' => 'text-right fixed-numbers',
				'content' => function($row, $value) {
					return number_format($value, 2);
				}
			])
			->col('description', 'Sub Category')
			->col('kwh_used', [
				'class' => 'text-right',
				'title' => 'kWh'
			])
			->col('cost', [
				'class' => 'text-right',
				'attr' => [ 'style' => 'padding-right: 15px' ],
				'title' => 'Cost'
			]);

		$info_values = App::sql()->query_row(
			"SELECT
				SUM(ahc.{$time_period->ahc_cost_in}) AS cost_during_working_hours,
				SUM(ahc.{$time_period->ahc_cost_out}) AS cost_out_of_hours,
				SUM(ahc.{$time_period->ahc_cost_in} + {$time_period->ahc_cost_out}) AS total_cost
			FROM category AS c
			INNER JOIN `{$time_period->ahc_table}` AS ahc
				ON c.id = ahc.category_id
			WHERE (ahc.building_id = $building->id) AND c.parent_category_id = $category_id
		");
		// JEANE CHANGE
		$content = '
			<h5 class="myWidget-title-inside">How you’ve used your power</h5>
			<div class="widget-row">
				'.$ui_table->print_html(true).'
			</div><hr class="myLine2" />';

		$ui_widget->footers = array_map(function($info) {
			return [
				'content' =>  $info['description'].' <span class="pull-right txt-color-myBrand-color-A">'.$info['value'].'</span>',
				'class' => $info['bg']
			];
		}, [
			[
				'bg' => 'myWidget-colorDescription',
				'description' => '<i class="eticon eticon-fw eticon-sun eticon-shadow"></i> Daytime cost for category',
				'value' => '<span class="fixed-numbers">&pound;'.number_format($info_values->cost_during_working_hours ? : 0, 2).'</span>'
			],
			[
				'bg' => 'myWidget-colorDescription',
				'description' => '<i class="eticon eticon-fw eticon-moon eticon-shadow"></i> After hours cost for category',
				'value' => '<span class="fixed-numbers">&pound;'.number_format($info_values->cost_out_of_hours ? : 0, 2).'</span>'
			],
			[
				'bg' => 'myWidget-colorDescription',
				'description' => 'Total cost for category',
				'value' => '<span class="fixed-numbers myWidget-title-value2">&pound;'.number_format($info_values->total_cost ? : 0, 2).'</span>'
			]
		]);

		$title = $building->info->burn_rate_widget_title ?: 'Your Burn Rate';
		// JEANE CHANGE
		$ui_widget->header('title', '
			<p class="myWidget-title">'.$title.'</p><i class="myWidget-colorIcon eticon eticon-pound-sign eticon-shadow"></i>
			<div class="clearfix"></div>
			
			<div class="myDropdown-wrapper widget-row display-flex">
				<div>
					<p class="myDescription no-margin padding-top-5">Choose your category</p>
				</div>
				<div id="burn-rate-select2-container" class="select2-color-tealDark">
					<select class="select2" id="burn-rate-categories" style="width:100%">
						'.implode(PHP_EOL, array_map(function($category) use ($category_id) {
							return '<option value="'.$category->id.'" '.($category_id == $category->id ? 'selected' : '').'>'.$category->description.'</option>';
						}, $categories ?: [])).'
					</select>
				</div>
			</div>');
	} else {
		$content = $ui->print_warning('No building found');
	}

	$ui_widget->body('content', $content);
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->print_html();
}
?>

<script>
	$('#burn-rate-categories').initSelect2().on('change', function() {
		loadWidget($('[data-widget-id="<?= $widget_info->widget_id; ?>"]'), {
			category_id: $(this).val()
		});
	});
</script>
