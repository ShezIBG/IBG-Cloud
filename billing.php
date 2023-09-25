<?php
	require_once 'inc/init.user.php';

	if(!Module::is_enabled(Module::BILLING)) {
		$user->launch_home_page();
		exit;
	}

	$main_page = App::MAIN_PAGE_BILLING;
	require_once 'inc/config.ui.php';

	$page_body_prop = [ "class" => "fixed-header fixed-navigation fixed-ribbon minified" ];
	include 'inc/header.php';

	$url = APP_URL.'/v3/billing';
?>

<div style="position:fixed; top:50px; left:0; width:100%; height:100%; margin:0; padding:0;">
	<div style="position:absolute; top:0; bottom:50px; left:0; right:0;">
		<iframe src="<?= $url ?>" style="border:none; width:100%; height:100%;">
		</iframe>
	</div>
</div>

<?php
	include 'inc/scripts.php';

	// Have to call it after jQuery is included
	$module = Module::get_module(Module::BILLING);
	if(!$module->init()) return;
?>
