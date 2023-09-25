<div class="" data-gs-animate="true">
<?php
	if($user) $current_dashboard = $user->get_dashboard($db_type);
	if (!$current_dashboard) $current_dashboard = Dashboard::get_default($db_type);

	$widgets = $current_dashboard->get_widgets();
	$widgets_data = [];

	if ($widgets) {
		foreach ($widgets as $index => $widget) {
			$dashboard_widget_info = $current_dashboard->get_widget_info($widget, $is_mobile);
			$widgets_data[$index] = $dashboard_widget_info;
		}

		foreach ($widgets_data as $widget_info) {
			$gs_width = $widget_info->grid->width;
			$gs_height = $widget_info->grid->height;

			echo '
				<div class="container-fluid grid-stack-item js-widget-item"
					data-gs-auto-position="true"
					data-gs-no-resize="true"
					data-gs-no-move="true"
					data-gs-max-width="'.$gs_width.'"
					data-gs-min-width="'.$gs_width.'"
					data-gs-min-height="'.$gs_height.'"
					data-gs-max-height="'.$gs_height.'"
					data-gs-width="'.$gs_width.'"
					data-gs-height="'.$gs_height.'"
					data-id="'.$widget_info->id.'"
					data-widget-id="'.$widget_info->widget_id.'">

					<div class="grid-stack-item-content myGrid-container"> <i class="eticon eticon-gear eticon-spin eticon-2x"></i> </div>
				</div>';
		}
	}
?>
</div>
<input type="hidden" value="<?= $current_dashboard->id; ?>" name="selected-dashboard" id="selected-dashboard">
<?php include_once(APP_PATH.'/view/partials/dashboard_scripts.php') ?>
