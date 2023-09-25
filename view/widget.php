<?php

require_once 'init.view.php';
$db_type = App::get('db_type', Dashboard::DASHBOARD_TYPE_MAIN);

$dashboard = new Dashboard(App::get('dashboard_id'));
if (!$dashboard->validate()) {
	$ui->print_danger('Dashboard not found');
	return;
}

$widget = null;

// get widgets by dashboard_widget_id or widget_id
// if it's a single dashboard, return by widget_id (we are not saving widgets to each user dashboard)

$widget_id = App::get('widget_id', '', true);

if($widget_id) {
	if ($widget_data = $dashboard->get_widgets([ 'widget.id' => '= '.$widget_id ])) {
		$widget = $widget_data[0];
	}
}

if ($widget) {
	$widget_info = $dashboard->get_widget_info($widget);
	if (file_exists($widget_info->markup_path)) {
		include_once($widget_info->markup_path);
	} else {
		$ui->print_danger('Markup path could not be found<br>'.$widget_info->markup_path);
	}
} else {
	$ui->print_danger('Widget not found');
}
