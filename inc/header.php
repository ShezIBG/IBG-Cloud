<?php
// Connects to overdue class in lib to return paymentoverdue (t/f)
$paymentOverdue = new Overdue();
$response = $paymentOverdue->get_billing_account();
if ($response) {
    $responseData = json_decode($response);
}
?>
<!DOCTYPE html>

<html>
<head>
	<meta https-equiv="Cache-control" content="public">
	<meta charset="utf-8">
	<title><?= $page_title != '' ? $page_title.' - ' : ''; ?><?=(BRANDING === 'elanet' ? 'Elanet Cloud' : 'Eticom Cloud')?></title>
	<title>IBG Cloud</title>
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
	
	<!-- MY BOOTSTRAP -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

	<script
		src="https://code.jquery.com/jquery-3.6.3.min.js"
		integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU="
		crossorigin="anonymous"></script>
  	<!-- <script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js'></script> -->
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
    <script src="node_modules\chartjs-plugin-zoom\dist\chartjs-plugin-zoom.min.js"></script>
	<!-- <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script> -->
	
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/bootstrap.css?rev=4">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/smartadmin-production-plugins.css?rev=5">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/smartadmin-production.css?rev=47">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/smartadmin-skins.css?rev=4">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/smartadmin-rtl.min.css">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/custom.css">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/mystyle.css">
	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/newstyle.css">

	<link rel="stylesheet" type="text/css" media="screen" href="<?= ASSETS_URL ?>/css/mobilemedia.css">
	
	<?php
	// setcookie('cookie-name', '1', 0, '/; samesite=None');
	?>
	
	
	<?php
		if($page_css) {
			foreach ($page_css as $css) {
				echo '<link rel="stylesheet" type="text/css" media="screen" href="'.ASSETS_URL.'/css/'.$css.'">';
			}
		}
	?>
	<?php
		if(CUSTOM_CSS) {
			echo '<link rel="stylesheet" type="text/css" media="screen" href="'.CUSTOM_CSS.'">';
		}
	?>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,300,400,700">
	<link rel="apple-touch-icon" sizes="57x57" href="<?= APP_URL ;?>/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="<?= APP_URL ;?>/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="<?= APP_URL ;?>/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="<?= APP_URL ;?>/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="<?= APP_URL ;?>/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?= APP_URL ;?>/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="<?= APP_URL ;?>/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="<?= APP_URL ;?>/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="<?= APP_URL ;?>/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="<?= APP_URL ;?>/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="<?= APP_URL ;?>/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="<?= APP_URL ;?>/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="<?= APP_URL ;?>/favicon-16x16.png">
	<link rel="manifest" href="<?= APP_URL ;?>/manifest.json">
	<meta name="msapplication-TileColor" content="#39a57a">
	<meta name="msapplication-TileImage" content="<?= APP_URL ;?>/ms-icon-144x144.png">
	<meta name="theme-color" content="#39a57a">

	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
<!-- MY BOOTSTRAP -->
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script> -->
	<script>
		function minMenu(){
			const varhide = document.getElementsByClassName("myHide");
			for(i=0; i < varhide.length; i++){
				if(varhide[i].style.display === "block"){
					varhide[i].style.display = "none";
					}else{
						varhide[i].style.display = "block";
				}
			}
			const varshow = document.getElementsByClassName("myShow");
			for(i=0; i < varshow.length; i++){
				if(varshow[i].style.display === "none"){
					varshow[i].style.display = "block";
					}else{
						varshow[i].style.display = "none";
				}
			}
		}

	</script>
</head>
<body <?php
	echo implode(' ', array_map(function($prop, $value) {
		return $prop.'="'.$value.'"';
	}, array_keys($page_body_prop), $page_body_prop));
?>>
<!-- Overdue response value -->
<script>
	var isInDebt = <?php echo json_encode($responseData); ?>;
</script>
	<header id="header">
		<a href="<?= APP_URL ?>" style="float: left; margin: 4px 0 0 10px;">
			<?php if(BRANDING === 'eticom') { ?>
				<img class="p-1" src="<?= APP_URL ?>/v3/assets/img/logo/eticom-logo.svg" style="height: 35px;">
			<?php } else if(BRANDING === 'elanet') { ?>
				<img src="branding/elanet/elanet_logo_white.png" style="margin-top: 6px; height: 32px;">
			<?php } ?>
		</a>
		<!-- jglassell header home page link-->
		<div class="myHeader-home-wrapper">
			<a class="myBtn-home" href="<?= APP_URL ?>/dashboard#view/home" style="">
				<!-- <div class="myLine-header-vert pull-left"></div> -->
				<i class="eticon eticon-lg eticon-fw eticon-home"></i>
			</a>
		</div>

		<div class="header-nav pull-right">
			<ul class="list-inline">
				<?php if(Module::is_enabled(Module::SETTINGS)) { ?>
					<li class="<?= $main_page == App::MAIN_PAGE_SETTINGS || $main_page == App::MAIN_PAGE_ADMIN ? 'active' : ''; ?>"><a href="<?= APP_URL ;?>/admin"><i class="eticon eticon-2x eticon-gear eticon-gear2" ></i></a></li>
				<?php } ?>

				<li class="hidden-sm hidden-md hidden-lg hidden-xl">
					<a class="myLink1" href="<?= APP_URL ?>/auth?logout=1" title="Sign Out" data-action="userLogout" data-logout-msg="You can improve your security further after logging out by closing this opened browser"><i class="eticon eticon-2x eticon-power"></i></a>
				</li>
				<!-- JEANE CHANGE -->
				<li class="hidden-mobile hidden-xs btn-header-profile">
					<div class="name">Welcome, <?= $user->info->name; ?></div>
					<a class="myLink1 mySignOut" href="<?= APP_URL ?>/auth?logout=1" title="Sign Out" data-action="userLogout" data-logout-msg="You can improve your security further after logging out by closing this opened browser" class="logout"><em>Sign out</em></a>
				</li>
			</ul>
		</div>
		<div id="module-nav" class="pull-right">
			<?php
				require_once dirname($app_path).'/lib/class.dashboard.php';
				//Module::print_icon_bar();
				//Shez CHanged here top bar items
			?>
		</div>		
		<?php
			echo '
				<div class="menu-wrap" id="menu-nav">
					<input type="checkbox" class="toggler">
					<div class="hamburger"><div></div></div>
						<div class="menu">
							<div class="scroll_wrap_4">
								<div>
									<ul>
										<li onclick="reloadThePagehome()"><a href="'.APP_URL.'/dashboard#view/dashboard/home">Home</a></li>
										<li onclick="reloadThePageelectricity()"><a href ="'.APP_URL.'/dashboard#view/dashboard/main">Electricity</a></li>
										<li onclick="reloadThePagegas()"><a href="'.APP_URL.'/dashboard#view/dashboard/gas">Gas</a></li>
										<li onclick="reloadThePagewater()"><a href ="'.APP_URL.'/dashboard#view/dashboard/water">Water</a></li>
										<li onclick="reloadThePagerenewables()"><a href ="'.APP_URL.'/dashboard#view/dashboard/renewables">Renewables</a></li>
										<li onclick="reloadThePagemeters()"><a href ="'.APP_URL.'/dashboard#view/dashboard/meters">Meters</a></li>
										<li onclick="reloadThePageemergency()"><a href ="'.APP_URL.'/dashboard#view/dashboard/emergency">Emergency</a></li>
										<li onclick="reloadThepagebuilding"><a href ="'.APP_URL.'/building">Building</a></li>
										<li onclick="reloadThepagereports"><a href ="'.APP_URL.'/dashboard#view/reports">Reports</a></li>
										<li onclick="reloadThepagesales"><a href ="'.APP_URL.'/sales">Sales</a></li>
										<li onclick="reloadThepageisp"><a href ="'.APP_URL.'/isp">Isp</a></li>
										<li onclick="reloadThepageclimate"><a href ="'.APP_URL.'/dashboard#view/dashboard/climate">Climate</a></li>
										<li onclick="reloadThepagestock"><a href ="'.APP_URL.'/stock">Stock</a></li>
										<li onclick="reloadThepagelighting"><a href ="'.APP_URL.'/lighting">Lighting</a></li>
										<li onclick="reloadThepagebilling"><a href ="'.APP_URL.'/billing">Billing</a></li>
										<li onclick="reloadThepagecontrol"><a href ="'.APP_URL.'/control">Control</a></li>
										<li onclick="reloadThePageaccess()"><a href ="'.APP_URL.'/dashboard#view/dashboard/access">Access</a></li>
										<li onclick="reloadThePagemultisense()"><a href ="'.APP_URL.'//dashboard#view/dashboard/multisense">Multisense</a></li>
										<li onclick="reloadThepageSurveillance"><a href ="'.APP_URL.'/dashboard#view/dashboard/Surveillance">Surveillance</a></li>
										<li onclick="reloadThePagesecurity()"><a href ="'.APP_URL.'/dashboard#view/dashboard/security">Security</a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			';
		?>
	</header>
