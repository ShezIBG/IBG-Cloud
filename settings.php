<?php
	require_once 'inc/init.user.php';

	if(!Module::is_enabled(Module::SETTINGS)) {
		$user->launch_home_page();
		exit;
	}

	$main_page = App::MAIN_PAGE_SETTINGS;
	require_once 'inc/config.ui.php';

	$page_body_prop = [ "class" => "fixed-header fixed-navigation fixed-ribbon minified" ];
	include 'inc/header.php';
	include 'inc/nav.php';
?>

<div id="main" role="main">
	<?php include 'inc/ribbon.php'; ?>
	<div id="content">
	</div>
</div>

<?php
	include 'inc/scripts.php';
	include 'inc/google-analytics.php';
?>
