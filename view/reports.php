<?php
	require_once 'init.view.php';

	$module = Module::get_module(Module::REPORTS);
	if(!$module->init()) return;

	$widget = $ui->create_widget();
	$widget->body('class');
	$widget->header('title', '<p class="myWidget-title">Reports</p>');
	$widget->header('icon', 'myWidget-colorIcon eticon-clipboard eticon-shadow');
	$widget->class = 'grid-stack-item-content page-widget jarviswidget-color-purple';
	//JEANE CHANGE here and at button
	$wizard_content = '
		<div class="report-wizard wizard" id="fuelux-wizard" data-target="#step-container">
			<ul class="wizard-steps steps">
				<li data-target="#step1" class="active">
					<span class="step myStepSpace description">&nbsp;Pre-generated Reports</span>
				</li>
			</ul>
		</div>
		<span class="myWidget-title-inside">Select a report to view</span>
		<div class="step-content" id="step-container">
			<form id="report-wizard" class="form-horizontal report-wizard">
				<div class="step-pane active" id="step1">
						<fieldset>
							<div class="form-group">
								<label class="col-md-3 control-label">Choose your building<span class="txt-color-red">*</span></label>
								<div class="col-md-9">
									<select name="report_building" id="report_building_select" class="form-control" required>
	';

	$query = Permission::list_buildings([ 'with' => Permission::REPORTS_ENABLED ]) ?: [];
	if(count($query) == 0) $wizard_content .= '<option value="" selected>Please select a building</option>';
	foreach($query as $item) {
		$wizard_content .= "<option value='".($item->id ? $item->id : '')."'> {$item->description} </option>";
	}

	$wizard_content .= '
									</select>
									<i></i>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-3 control-label">Select report type<span class="txt-color-red">*</span></label>
								<div class="col-md-9">
									<select name="report_type" id="report_type_select" class="form-control" required disabled>
										<option value="" selected>Please select a report type</option>
	';

	foreach(Eticom::get_dropdowns('report_type') as $item) {
		$wizard_content .= "<option value='".($item->value ? $item->value : '')."'> {$item->description} </option>";
	}

	$wizard_content .= '
									</select>
									<i></i>
								</div>
							</div>

							<div class="form-group ">
								<label class="col-md-3 control-label">Filter by date</label>
								<div class="col-md-3 year-filter">
									<select name="filter_year" id="filter_year" class="form-control" required disabled>
										<option value="" selected>Filter By Year</option>
									</select>
									<i></i>
								</div>
								<div class="col-md-3 month-filter">
									<select name="filter_month" id="filter_month" class="form-control" required disabled>
										<option value="" selected>Filter By Month</option>
									</select>
									<i></i>
								</div>
								<div class="col-md-3 day-filter">
									<select name="filter_day" id="filter_day" class="form-control" required disabled>
										<option value="" selected>Filter By Day</option>
									</select>
									<i></i>
								</div>
								<div class="col-md-3 week-filter hidden">
									<select name="filter_week" id="filter_week" class="form-control" required disabled>
										<option value="" selected>Filter By Week</option>
									</select>
									<i></i>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label">Choose report<span class="txt-color-red">*</span></label>
								<div class="col-md-9">
									<select name="choose_report" id="choose_report_select" class="form-control" required disabled>
										<option value="" selected>Please select a report to view</option>
									</select>
									<i></i>
								</div>
							</div>

							<div class="form-group hidden-md hidden-sm hidden-xs ">
								<label class="col-md-3 control-label" for="switch-toggle-8">View in browser?</label>
								<div class="col-md-9">
									<select name="view_inline" id="view_inline" class="form-control" required>
										<option value="0" selected>No</option>
										<option value="1">Yes</option>
									</select>
									<i></i>
								</div>
							</div>

						</fieldset>
				</div>
			</form>
			<!-- JEANE CHANGE -->
			<div class="wizard-actions">
				<button class="myBtn1 btn-prev">
					<i class="icon-arrow-left"></i>
					Back
				</button>
				<button id="view_report" class="myBtn1" data-last="Confirm " disabled><!-- btn-next -->
					View Report
					<i class="icon-arrow-right icon-on-right"></i>
				</button>
			</div>
		</div>
	';

	$widget->body('content', $wizard_content);
?>

<div class="row">
	<div class="col-md-1 col-lg-2 hidden-sm">&nbsp;</div>
	<div class="col-md-10 col-lg-8 col-sm-12">
		<?php $widget->print_html(); ?>
	</div>
	<div class="col-md-1 col-lg-2 hidden-sm">&nbsp;</div>
</div>

<script>
	pageSetUp();
	var pagefunction = function() {
		$('#filter_year').val('').attr('disabled', true);
		$('#filter_month').val('').attr('disabled', true);
		$('#filter_day').val('').attr('disabled', true);
		$('#filter_week').val('').attr('disabled', true);
		$('#choose_report_select').val('').attr('disabled', true);
		$('#view_report').attr('disabled', true);

		if($('#report_building_select').val() == 0 || $('#report_building_select').val == "") {
			$('#report_type_select').val('').attr('disabled', true);
		} else {
			$('#report_type_select').removeAttr('disabled').val('');
		}

		$('#report_type_select').on('change', function() {
			$('#choose_report_select').val('').attr('disabled', true);
			$('#filter_month').val('').attr('disabled', true);
			$('#filter_day').val('').attr('disabled', true);
			$('#filter_week').val('').attr('disabled', true);
			$('#view_report').attr('disabled', true);

			if($(this).val() == 0 || $(this).val() == '') {
				$('#filter_year').val('').attr('disabled', true);
				return false;
			}

			if($(this).val() == 'eod') {
				//end of day
				$('.month-filter').removeClass('hidden');
				$('.day-filter').removeClass('hidden');
				$('.week-filter').addClass('hidden');
			} else if($(this).val() == 'eow') {
				//end of week
				$('.month-filter').removeClass('hidden');
				$('.day-filter').addClass('hidden');
				$('.week-filter').removeClass('hidden');
			} else if($(this).val() == 'eom') {
				//end of month
				$('.month-filter').removeClass('hidden');
				$('.day-filter').addClass('hidden');
				$('.week-filter').addClass('hidden');
			} else {
				//end of year
				$('.month-filter').addClass('hidden');
				$('.day-filter').addClass('hidden');
				$('.week-filter').addClass('hidden');
			}

			$.get('<?= APP_URL ?>/ajax/get/report_type_change', { type: 'filter_year', id: $('#report_building_select').val(), report_type: $(this).val() }, function(result) {
				if(result.status == "FAIL") return $.ajaxResult(result);
				$('#filter_year').html(result.data.html).removeAttr('disabled');
			});
		});

		$('#filter_year').on('change', function() {
			$('#choose_report_select').val('').attr('disabled', true);
			$('#filter_day').val('').attr('disabled', true);
			$('#filter_week').val('').attr('disabled', true);
			$('#view_report').attr('disabled', true);

			if($(this).val() == 0 || $(this).val() == '') {
				$('#filter_month').val('').attr('disabled', true);
				return false;
			}

			if($('#report_type_select').val() == 'eoy') {
				$.get('<?= APP_URL ?>/ajax/get/report_type_change', { type: 'choose_report', id: $('#report_building_select').val(), report_type: $('#report_type_select').val(), yr: $(this).val(), filter: 'year' }, function(result) {
					if(result.status == "FAIL") return $.ajaxResult(result);
					$('#choose_report_select').html(result.data.html).removeAttr('disabled');
				});
			} else {
				$.get('<?= APP_URL ?>/ajax/get/report_type_change', { type: 'filter_month', id: $('#report_building_select').val(), report_type: $('#report_type_select').val(), yr: $(this).val() }, function(result) {
					if(result.status == "FAIL") return $.ajaxResult(result);
					$('#filter_month').html(result.data.html).removeAttr('disabled');
				});
			}
		});

		$('#filter_month').on('change', function(ev) {
			var report_type = $('#report_type_select').val(),
				$field = report_type == "eow" ? 'filter_week' : 'filter_day',
				$other = report_type == "eow" ? 'filter_day' : 'filter_week';

			$('#choose_report_select').val('').attr('disabled', true);
			$('#filter_day').val('').attr('disabled', true);
			$('#filter_week').val('').attr('disabled', true);
			$('#view_report').attr('disabled', true);

			if($(this).val() == '' || $(this).val() == 0) {
				$('#' + $field).attr('disabled', true);
				return false;
			}

			if($('#report_type_select').val() == "eom") {
				if($(this).val() == 0 || $(this).val() == '') {
					$('#choose_report_select').attr('disabled', true);
					return false;
				}
				$.get('<?= APP_URL ?>/ajax/get/report_type_change', { type: 'choose_report', id: $('#report_building_select').val(), report_type: $('#report_type_select').val(), yr: $('#filter_year').val(), mo: $(this).val(), filter: 'month' }, function(result) {
					if(result.status == "FAIL") return $.ajaxResult(result);
					$('#choose_report_select').html(result.data.html).removeAttr('disabled');
				});
			} else {
				if($(this).val() == 0 || $(this).val() == '') {
					$('#'+field).attr('disabled', true);
					return false;
				}
				$.get('<?= APP_URL ?>/ajax/get/report_type_change', { type: $field, id: $('#report_building_select').val(), report_type: $('#report_type_select').val(), yr: $('#filter_year').val(), mo: $(this).val() }, function(result) {
					if(result.status == "FAIL") return $.ajaxResult(result);
					$('#'+$field).html(result.data.html).removeAttr('disabled');
					$('#'+$other).attr('disabled', true);
				});
			}
		});

		$('#filter_day').on('change', function() {
			$('#view_report').attr('disabled', true);
			if($(this).val() == '' || $(this).val() == 0) {
				$('#choose_report_select').attr('disabled', true);
				return false;
			}
			$.get('<?= APP_URL ?>/ajax/get/report_type_change', { type: 'choose_report', id: $('#report_building_select').val(), report_type: $('#report_type_select').val(), yr: $('#filter_year').val(), mo: $('#filter_month').val(), dy: $(this).val(), filter: 'day' }, function(result) {
				if(result.status == "FAIL") return $.ajaxResult(result);
				$('#choose_report_select').html(result.data.html).removeAttr('disabled');
			});
		});

		$('#filter_week').on('change', function() {
			$('#view_report').attr('disabled', true);
			if($(this).val() == '' || $(this).val() == 0) {
				$('#choose_report_select').attr('disabled', true);
				return false;
			}
			$.get('<?= APP_URL ?>/ajax/get/report_type_change', { type: 'choose_report', id: $('#report_building_select').val(), report_type: $('#report_type_select').val(), yr: $('#filter_year').val(), mo: $('#filter_month').val(), wk: $(this).val(), filter: 'week' }, function(result) {
				if(result.status == "FAIL") return $.ajaxResult(result);
				$('#choose_report_select').html(result.data.html).removeAttr('disabled');
			});
		});

		$('#choose_report_select').on('change', function() {
			if($(this).val() == 0 || $(this).val() == "") {
				$('#view_report').attr('disabled', true);
				return false;
			}
			$('#view_report').removeAttr('disabled');
		});

		$('#view_report').on('click', function(event) {
			event.preventDefault();
			var $building = $('#report_building_select').val(),
				$report_type = $('#report_type_select').val();

			if($building == '' || $report_type == '' || $building == 'undefined' || $report_type == 'undefined') {
				return false;
			} else {
				if($('#view_inline').val() == '1' || $("body").hasClass('mobile-detected')) {
					window.open('<?= APP_URL ?>/ajax/get/get_report?id='+ $('#choose_report_select').val()+'&view=1');
				} else {
					window.location = '<?= APP_URL ?>/ajax/get/get_report?id='+ $('#choose_report_select').val();
				}
			}
		});

		var $form = $('#report-wizard');
		var wizard = $('.wizard').initWizard();
		$form.validate({ ignore: '.hidden, :disabled, :hidden, .not_required' });

		wizard.on('change', function(e, data) {
			if(data.direction === 'next' && !$form.valid()) e.preventDefault();
		});
	}

	loadScript("<?= ASSETS_URL ?>/js/plugin/fuelux/wizard/wizard.min.js", function() {
		loadScript("<?= ASSETS_URL ?>/js/plugin/bootstrap-timepicker/bootstrap-timepicker.min.js", function() {
			loadScript("<?= ASSETS_URL ?>/js/plugin/ion-slider/ion.rangeSlider.min.js", pagefunction);
		});
	});
	//SHEZ background return to show change
	// Apply sky background to body and remove on leaving the view
	//$('body,html').addClass('eticom-faded-background');

	// registerRefreshListener(function() {
	// 	$('body,html').removeClass('eticom-faded-background');
	// }, true);
</script>
<style>

@media (max-width: 360px){
	#left-panel{
		display:none;

	}
	#main{
		margin-left:auto;
		

	}

}

@media (max-width: 820px){
	#left-panel{
		display:none;


	}
	#main{
		margin-left:auto;
		

	}
}


</style>