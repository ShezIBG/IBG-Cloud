<?php
	require_once 'inc/init.user.php';

	if(!Module::is_enabled(Module::SETTINGS)) {
		$user->launch_home_page();
		exit;
	}

	$main_page = App::MAIN_PAGE_ADMIN;
	require_once 'inc/config.ui.php';

	$page_body_prop = [ "class" => "fixed-header fixed-navigation fixed-ribbon minified" ];
	include 'inc/header.php';

	$settings_url = APP_URL.'/v3/settings';
	list($client_id, $tab, $path) = App::get(['client', 'tab', 'path'], '', true);

	if($client_id) {
		$settings_url .= '/client/'.$client_id;
		if($tab) $settings_url .= '/'.$tab;
	} else if($path) {
		$settings_url .= '/'.$path;
	}
?>

<div style="position:fixed; top:50px; left:0; width:100%; height:100%; margin:0; padding:0;">
	<div style="position:absolute; top:0; bottom:50px; left:0; right:0;">
		<iframe src="<?= $settings_url ?>" style="border:none; width:100%; height:100%;">
		</iframe>
	</div>
</div>

<?php
	include 'inc/scripts.php';
?>
