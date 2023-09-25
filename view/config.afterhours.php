<?php
	require_once 'init.view.php';

	$building_id = App::get('bldg', '', true);

	if ($building_id) {
		$building = new Building($building_id);
	} else {
		$building = $user->get_default_building(Permission::ADMIN);
	}

	if (!$building) {
		$ui->print_danger('Building not found.');
		return;
	}

	if (!Permission::get_building($building->id)->check(Permission::ADMIN)) {
		Eticom::print_status(403);
		exit;
	}

	$widget = $ui->create_widget();
	$widget->body('class');
	$widget->header('title', '<h2>After Hours Setup Wizard</h2>');
	$widget->header('icon', 'eticon-clock eticon-shadow');
	$widget->class = 'page-widget jarviswidget-color-blueDark';

	$buildings = Permission::list_buildings([ 'with' => Permission::ADMIN ]) ?: [];
	$cnt = count($buildings);

	if ($cnt == 1) {
		$building_select = '<input type="hidden" name="bldg_id" value="'.$building->id.'">';
	} else {
		if ($cnt) {
			foreach ($buildings as $building_info) {
				$selected = $building_info->id == $building->id;
				$building_html_list[] = '<option value="'.$building_info->id.'" '.($selected ? 'selected' : '').'> '. $building_info->description .'</option>';
			}
		}

		$building_select = '
			<div class="form-group">
				<label class="col-md-2 control-label font-md">Select a Building</label>
				<div class="col-md-10">
					<select name="bldg_id" id="building_select" class="form-control" required>
						<option value="" selected>Please Select a Building</option>
						'.implode('', $building_html_list).'
					</select>
					<i class="bg-color-blueDark"></i>
				</div>
			</div>';
	}

	$days = Eticom::get_days();
	$working_hours = [];
	if ($working_hours_data = $building->get_working_hours()) {
		foreach ($working_hours_data as $working_hour) {
			$working_hours[$working_hour->day] = [
				'start'          => $working_hour->open_time,
				'end'            => $working_hour->close_time,
				'closed_all_day' => $working_hour->closed_all_day
			];
		}
	}

	$holidays = $building->get_holidays() ?: [];

	$table_data = [];
	foreach ($days as $day) {
		$table_data[] = (object)[
			'day'            => $day,
			'start'          => isset($working_hours[$day]) ? App::format_datetime('H:i', $working_hours[$day]['start']) : '00:00',
			'end'            => isset($working_hours[$day]) ? App::format_datetime('H:i', $working_hours[$day]['end']) : '00:00',
			'closed_all_day' => isset($working_hours[$day]) && $working_hours[$day]['closed_all_day'] ?  1 :0
		];
	}

	$ui_table = $ui->create_datatable($table_data, [
		'in_widget' => false,
		'bordered'  => false,
		'striped'   => false,
		'hover'     => false,
		'forum'     => true
	]);

	$ui_table->class = 'no-border transparent';
	$ui_table->col('day', '');
	$ui_table->col('start', [
		'title' => 'Start',
		'class' => 'text-center'
	]);
	$ui_table->col('end', [
		'title' => 'End',
		'class' => 'text-center'
	]);
	$ui_table->col('closed_all_day', 'Closed all day?');

	$ui_table->cell('day', [
		'content' => function($row) {
			return '<p class="txt-color-blueDark"><strong>'.$row->day.'</strong></p>';
		},
		'class' => 'pull-right'
	]);
	$ui_table->cell('start', [
		'content' => function($row, $value) {
			return '<input name="working_hours_open_time['.$row->day.']" data-day="'.$row->day.'" class="form-control clockpicker text-center js-working-hour" type="text" value="'.$value.'" data-autoclose="true" readonly '.($row->closed_all_day ? 'disabled' : '').'>';
		},
		'attr' => [ 'style' => 'width: 200px;' ]
	]);
	$ui_table->cell('end', [
		'content' => function($row, $value) {
			return '<input name="working_hours_close_time['.$row->day.']" data-day="'.$row->day.'" class="form-control clockpicker text-center js-working-hour" type="text" value="'.$value.'" data-autoclose="true" readonly '.($row->closed_all_day ? 'disabled' : '').'>';
		},
		'attr' => [ 'style' => 'width: 200px;' ]
	]);
	$ui_table->cell('closed_all_day', [
		'content' => function($row, $value) {
			return '
				<input name="working_hours_closed_all_day['.$row->day.']" data-day="'.$row->day.'" id="closed-all-day-'.strtolower($row->day).'" class="switch-toggle switch-toggle-round-flat js-working-hours-closed-all-day" type="checkbox" '.($value ? 'checked' : '').' value="1">
				<label for="closed-all-day-'.strtolower($row->day).'"></label>';
		},
		'class' => 'padding-left-10'
	]);

	$step1_config = $ui_table->print_html(true);

	$wizard_content = '
		<div class="wizard wizard-color-blueDark" id="fuelux-wizard" data-target="#step-container">
			<ul class="wizard-steps steps">
				<li data-target="#step1" class="active">
					<span class="step"><b>1</b> <span>Working Hours Setup</span></span>
					<span class="title">Step 1: Set your working hours</span>
				</li>
				<li data-target="#step2">
					<span class="step"><b>2</b> <span>Holidays Setup</span></span>
					<span class="title">Step 2: Set your holidays</span>
				</li>
			</ul>
		</div>
		<div class="step-content" id="step-container">
			<form id="hours-wizard" class="form-horizontal">
				<div class="step-pane active" id="step1">
					'.$building_select.'
					'.$step1_config.'
				</div>

				<div class="step-pane" id="step2">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label col-xs-2 col-md-4 txt-color-blueDark" for="prepend">Description</label>
								<div class="col-xs-10 col-md-8">
									<div class="input-group input-group-md">
										<span class="input-group-addon bg-color-blueDark"><i class="eticon eticon-pencil"></i></span>
										<input type="text" placeholder="Description" class="form-control" id="holiday-description" required>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-xs-2 col-md-4 txt-color-blueDark" for="prepend">Select Date</label>
								<div class="col-xs-10 col-md-8">
									<div class="input-group input-group-md">
										<span class="input-group-addon bg-color-blueDark"><i class="eticon eticon-calendar"></i></span>
										<input type="text" placeholder="dd/mm/yy" class="form-control" id="holiday-date" required>
									</div>
								</div>
							</div>
							<div class="form-group">
								<div class="col-xs-10 col-md-8 col-xs-offset-2 col-md-offset-4">
									<label class="control-label txt-color-blueDark" for="prepend">Closed all day?</label>
									<div class="pull-right">
										<input id="holiday-closed-all-day" class="switch-toggle switch-toggle-round-flat" type="checkbox" value="1" checked>
										<label for="holiday-closed-all-day"></label>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-xs-10 col-xs-offset-2 col-md-8 col-md-offset-4">
									<div class="row">
										<label class="control-label col-xs-6 txt-color-blueDark text-center padding-10" for="prepend">Start</label>
										<label class="control-label col-xs-6 txt-color-blueDark text-center padding-10" for="prepend">End</label>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-xs-2 col-md-4 txt-color-blueDark" for="prepend">Open&nbsp;hours</label>
								<div class="col-xs-5 col-md-4">
									<input type="text" class="form-control clockpicker text-center" readonly disabled placeholder="00:00" id="holiday-time-start" data-autoclose="true">
								</div>
								<div class="col-xs-5 col-md-4">
									<input type="text" class="form-control clockpicker text-center" readonly disabled placeholder="00:00" id="holiday-time-end" data-autoclose="true">
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<button class="btn btn-primary pull-right" data-exec="updateHolidayItem" id="holiday-update-item">Add</button>
								</div>
							</div>
							<br>
						</div>
						<div class="col-md-5 col-md-offset-1">
							<div>
								<label for="sel1" class="txt-color-blueDark">List of holidays</label>
								<label class="pull-right text-muted">Tap date to amend/delete</label>
								<div class="list-group list-group-box" id="holiday-list">
									'.implode('', array_map(function($holiday) {
										$description = $holiday->description;
										$date = App::format_datetime('d/m/Y', $holiday->date);
										$open_time = App::format_datetime('H:i', $holiday->open_time);
										$close_time = App::format_datetime('H:i', $holiday->close_time);
										$value = json_encode([
											'description' => $description,
											'date' => $date,
											'open_time' => $open_time,
											'close_time' => $close_time,
											'closed_all_day' => $holiday->closed_all_day
										]);
										return '<a href="#" data-value="'.App::clean_str($value).'" class="list-group-item js-holiday-item">'.$description .' '. $date.($holiday->closed_all_day ? '<span class="pull-right label label-warning">Closed all day</span>' : '<span class="pull-right label label-success">'.$open_time.' - '.$close_time.'</span>').'</a>';
									}, $holidays)).'
								</div>
							</div>
							<button class="btn btn-success" data-exec="amendHolidayItem">Amend</button>
							<button class="btn btn-danger" data-exec="deleteHolidayItem">Delete</button>
						</div>
					</div>
				</div>
			</form>

			<div class="wizard-actions">
				<button class="btn btn-lg btn-prev bg-color-blue txt-color-white">
					<i class="icon-arrow-left"></i>
					Back
				</button>
				<button class="btn btn-lg bg-color-blueDark btn-next txt-color-white" data-last="Confirm ">
					Next
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

	var $holidayEditingItem;

	var deleteHolidayItem = function() {
		$('#holiday-list').find('.js-holiday-item.active').eq(0).remove();
	};

	var amendHolidayItem = function() {
		var $dates = $('#holiday-list'),
			$date = $('#holiday-date'),
			$description = $('#holiday-description'),
			$timeStart = $('#holiday-time-start'),
			$timeEnd = $('#holiday-time-end'),
			$closedAllDay = $('#holiday-closed-all-day');

		$holidayEditingItem = $dates.find('.js-holiday-item.active').eq(0);

		if ($holidayEditingItem.length > 0) {
			var data = $holidayEditingItem.data('value');
			if (data) {
				$date.val(data.date);
				$description.val(data.description);
				$timeStart.val(data.open_time);
				$timeEnd.val(data.close_time);
				$closedAllDay.prop('checked', data.closed_all_day == 1);
				$('#holiday-time-start').prop('disabled', data.closed_all_day == 1);
				$('#holiday-time-end').prop('disabled', data.closed_all_day == 1);

				$('#holiday-update-item').text('Update');
			} else $holidayEditingItem = undefined;
		} else $holidayEditingItem = undefined;
	};

	var updateHolidayItem = function() {
		var $date = $('#holiday-date'),
			$description = $('#holiday-description'),
			$timeStart = $('#holiday-time-start'),
			$timeEnd = $('#holiday-time-end'),
			$closedAllDay = $('#holiday-closed-all-day'),
			isClosedAllDay = $closedAllDay.is(':checked');
			data = {
				description: $description.val(),
				date: $date.val(),
				open_time: $timeStart.val() || '00:00',
				close_time: $timeEnd.val() || '00:00',
				closed_all_day: isClosedAllDay ? 1 : 0
			};

		if (!$date.val()) {
			$.messagebox('You must enter your holiday date.', {
				buttons: '[OK]',
				title: 'Date empty'
			});
			return;
		}

		var $dates = $('#holiday-list'),
			text = $description.val() + ' - ' + $date.val() + (isClosedAllDay ? '<span class="pull-right label label-warning">Closed all day</span>' : '<span class="pull-right label label-success">'+data.open_time+' - '+data.close_time+'</span>');

		if (typeof $holidayEditingItem != 'undefined') {
			$holidayEditingItem.data('value', data).html(text);
			$holidayEditingItem = undefined;
		} else {
			var $filter = $dates.find('.js-holiday-item').filter(function(i, e) {
				var value = $(this).data('value');
				return value.date == $date.val();
			});

			if ($filter.length > 0) {
				$.messagebox('Date is already in the list. Please choose another.', {
					buttons: '[OK]',
					title: 'Date exists'
				});
			} else {
				$dates.append($('<a></a>', {
					href: '#',
					class: 'list-group-item js-holiday-item'
				}).data('value', data).html(text));
			}
		}

		$date.val('');
		$timeStart.val('');
		$timeEnd.val('');
		$description.val('');
		$('#holiday-closed-all-day').prop('checked', true);
		$('#holiday-update-item').text('Add');
		$('#holiday-time-start').prop('disabled', true);
		$('#holiday-time-end').prop('disabled', true);
	};

	var pagefunction = function() {
		var $form = $('#hours-wizard');
		var wizard = $('.wizard').initWizard();

		$form.validate({
			ignore: '.hidden, :disabled, :hidden, .not_required, #sms_field, #email_field'
		});

		$(document).on('click', '.js-holiday-item', function(e) {
			e.preventDefault();
			$('.js-holiday-item.active').removeClass('active');
			$(this).addClass('active');
		});

		$('.js-working-hours-closed-all-day').on('change', function() {
			var day = $(this).data('day');
			$('.js-working-hour[data-day="'+day+'"]').prop('disabled', this.checked);
		})

		$('#holiday-closed-all-day').on('change', function() {
			$('#holiday-time-start').prop('disabled', this.checked);
			$('#holiday-time-end').prop('disabled', this.checked);
		})

		wizard.on('finished', function(e, data) {
			var holidays = {
				holidays_date: [],
				holidays_description: [],
				holidays_open_time: [],
				holidays_close_time: [],
				holidays_closed_all_day: []
			};

			$('#holiday-list').find('.js-holiday-item').each(function() {
				var $option = $(this),
					data = $option.data('value');

				if (data) {
					for (i in data)
						holidays['holidays_' + i].push(data[i]);
				}
			});

			$.post('<?= APP_URL ?>/ajax/post/update_afterhours', $form.serialize() + '&' + $.param(holidays), function(result) {
				$.ajaxResult(result, function() {
					window.location.href = '<?= APP_URL."/dashboard#view/dashboard"; ?>';
				});
			});
		})
		.on('change', function(e, data) {
			if(data.direction === 'next' && !$form.valid()) { e.preventDefault(); }
		});

		$('.clockpicker').clockpicker({
			placement: 'top',
			donetext: 'Done'
		});

		$('#building_select').on('change', function() {
			window.location.href = '<?= APP_URL."/settings#view/config/afterhours/"; ?>'+$(this).val();
		});

		$("#holiday-date").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: '2016:2046',
			numberOfMonths: 2,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>',
			onClose: function (selectedDate) {
				$('#holiday-date').next('em.invalid').remove();
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/fuelux/wizard/wizard.min.js", function() {
		loadScript("<?= ASSETS_URL ?>/js/plugin/clockpicker/clockpicker.min.js", function() {
			loadScript("<?= ASSETS_URL ?>/js/plugin/ion-slider/ion.rangeSlider.min.js", pagefunction);
		});
	});

	// Apply sky background to body and remove on leaving the view
	$('body,html').addClass('eticom-faded-background');

	registerRefreshListener(function() {
		$('body,html').removeClass('eticom-faded-background');
	}, true);
</script>
