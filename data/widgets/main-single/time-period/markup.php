
<?php
	$building = null;
	switch($dashboard->type) {
		case Dashboard::DASHBOARD_TYPE_MAIN:
			$building = $user->get_default_building(Permission::ELECTRICITY_ENABLED);
			break;

		case Dashboard::DASHBOARD_TYPE_GAS:
			$building = $user->get_default_building(Permission::GAS_ENABLED);
			break;

		case Dashboard::DASHBOARD_TYPE_WATER:
			$building = $user->get_default_building(Permission::WATER_ENABLED);
			break;

		case Dashboard::DASHBOARD_TYPE_RENEWABLES:
			$building = $user->get_default_building(Permission::RENEWABLES_ENABLED);
			break;
	}

	$vpd = $dashboard->get_valid_period_days($building->id);
	$time_period = $dashboard->get_time_period($building->id, $vpd);

	$dates = [];
	foreach($vpd as $d) {
		$dates[date('d/m/Y', strtotime("-{$d} day"))] = Dashboard::TIME_PERIOD_DAY.$d;
	}

	// Add yesterday to the list of selectable dates
	$dates[date('d/m/Y', strtotime('yesterday'))] = Dashboard::TIME_PERIOD_YESTERDAY;

	$selected_date = '';
	if(isset($time_period->day) && $time_period->day > 0) {
		$selected_date = date('d/m/Y', strtotime("-{$time_period->day} day"));
	} else if($time_period->value == Dashboard::TIME_PERIOD_YESTERDAY) {
		$selected_date = date('d/m/Y', strtotime('yesterday'));
	}
#MY Shez Change bg color for Dashboard

	switch($dashboard->type) {
		case Dashboard::DASHBOARD_TYPE_GAS:
			$widget_class = 'myForm-dropdown1';
			$button_class = 'btn-success';
			break;
		case Dashboard::DASHBOARD_TYPE_WATER:
			$widget_class = 'myForm-dropdown1';
			$button_class = 'btn-success';
			break;
		case Dashboard::DASHBOARD_TYPE_MAIN:
		default:
			$widget_class = 'myForm-dropdown1';
			$button_class = 'btn-success';
			break;
	}

?>

<div class="mySearch-container well dashboard-widget no-margin time-period-widget <?= $widget_class ?>" id="<?= $widget_info->ui_id; ?>">
	<div class="widget-row display-flex">
		<div>
			<div class="centered">
				<div class="dropdown pull-right" style="margin-right:10px;">
					<button class="btn dropdown-toggle myForm-dropdown1" type="button" id="time-period-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<i class="eticon eticon-calendar"></i> Select Date
					</button>
					<ul id="time-period-menu" class="dropdown-menu" aria-labelledby="time-period-dropdown">
						<?php if($dashboard->type == Dashboard::DASHBOARD_TYPE_MAIN) { ?>
							<li class="<?= $time_period->value == Dashboard::TIME_PERIOD_YESTERDAY ? 'active' : '' ?>"><a href="#" data-tp="<?= Dashboard::TIME_PERIOD_YESTERDAY ?>">Yesterday</a></li>
							<li class="<?= $time_period->value == Dashboard::TIME_PERIOD_LAST_WEEK ? 'active' : '' ?>"><a href="#" data-tp="<?= Dashboard::TIME_PERIOD_LAST_WEEK ?>">Last Week</a></li>
							<li class="<?= $time_period->value == Dashboard::TIME_PERIOD_LAST_MONTH ? 'active' : '' ?>"><a href="#" data-tp="<?= Dashboard::TIME_PERIOD_LAST_MONTH ?>">Last Month</a></li>
						<?php } else { ?>
							<li class="<?= $time_period->value == Dashboard::TIME_PERIOD_YESTERDAY ? 'active' : '' ?>"><a href="#" data-tp="<?= Dashboard::TIME_PERIOD_YESTERDAY ?>">Yesterday</a></li>
							<li class="<?= $time_period->value == Dashboard::TIME_PERIOD_LAST_WEEK ? 'active' : '' ?>"><a href="#" data-tp="<?= Dashboard::TIME_PERIOD_LAST_WEEK ?>">Last 7 Days</a></li>
							<li class="<?= $time_period->value == Dashboard::TIME_PERIOD_LAST_MONTH ? 'active' : '' ?>"><a href="#" data-tp="<?= Dashboard::TIME_PERIOD_LAST_MONTH ?>">Last 30 Days</a></li>
						<?php } ?>
						<li role="separator" class="divider"></li>
						<li><div id="time-period-date"></div></li>
					</ul>
				</div>
				<span class="description" style=""><?= $time_period->title ?></span><br>
				<span class="myWidget-colorHeader"><?= $time_period->subtitle ?></span>
			</div>
		</div>
	</div>
</div>

<script>
	var timePeriodDates = <?= json_encode($dates) ?>;
	var defaultDate = '<?= $selected_date ?>';

	function setTimePeriod(tp) {
		var $this = $(this);
		$.post('<?= APP_URL ?>/ajax/post/update_dashboard_main', {
			dashboard_id: $selectedDashboard.val(),
			time_period: tp
		}, function(data) {
			$.ajaxResult(data, checkURL);
		});
	}

	$('#time-period-date')
		.on('click', function(e) {
			e.stopPropagation();
		})
		.datepicker({
			numberOfMonths: 1,
			dateFormat: 'dd/mm/yy',
			prevText: '<i class="fa fa-chevron-left"></i>',
			nextText: '<i class="fa fa-chevron-right"></i>',
			maxDate: '<?= date("d/m/Y", strtotime("yesterday")) ?>',
			minDate: '<?= date("d/m/Y", strtotime("-60 days")) ?>',
			defaultDate: defaultDate || null,
			beforeShowDay: function(date) {
				var tp = timePeriodDates[$.datepicker.formatDate('dd/mm/yy', date)];
				return [!!tp, ''];
			},
			onSelect: function (dateText) {
				var tp = timePeriodDates[dateText];
				if(tp) setTimePeriod(tp);
			}
		});

	if(!defaultDate) {
		$('#time-period-date')
			.datepicker('setDate', null)
			.find('.ui-state-active,.ui-state-hover').removeClass('ui-state-active ui-state-hover');
	}

	$('#time-period-dropdown').closest('.grid-stack-item-content').attr('style', 'overflow: visible; z-index: 100 !important;');

	$('#time-period-menu > li > a').on('click', function(e) {
		e.preventDefault();
		setTimePeriod($(this).data('tp'));
	});

</script>
