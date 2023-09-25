<?php
	require_once 'init.view.php';
	$db_type = Dashboard::DASHBOARD_TYPE_HOME;

	$db_partial_path = APP_PATH.'/view/partials/dashboard_'.$db_type.'.php';
	if (isset($db_type) && file_exists($db_partial_path)) include_once($db_partial_path);
?>

<script>
	$(function() {
		registerRefreshListener(function() {
			$('.header-nav .dashboard-home').removeClass('active');
		}, true);

		$('.header-nav .dashboard-home').addClass('active');
	});
</script>
