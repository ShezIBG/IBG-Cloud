<!-- /* JEANE CHANGE COLOUR CHART AND STEP */ -->


<?php
	$items = [
		'item1' => [
			'img' => '<div class="item-image" style="background-image: url('.ASSETS_URL.'/img/banners/manager-foreground.png), url('.ASSETS_URL.'/img/banners/manager-background.png); background-size: contain, cover; background-position: bottom center, center; background-repeat: no-repeat, no-repeat;"></div>',
			'caption' => '<div class="extra-content large"><i class="eticon eticon-shadow eticon-lg eticon-mobile-grid"></i> <i class="eticon eticon-shadow eticon-lg eticon-building-cogs"></i></div>
			<div class="caption-container" style="background: #2E3C47;">
				<div class="caption-content">
					<h1>Building Manager<br>Smartphone App</h1>
					<p>Keep track of your your buildings on the go with our incredible new app for Commercial Landlords</p>
				</div>
			</div>'
		],
		'item2' => [
			'img' => '<div class="item-image" style="background-image: url('.ASSETS_URL.'/img/banners/tariff-foreground.png), url('.ASSETS_URL.'/img/banners/tariff-background.png); background-size: contain, cover; background-position: bottom center, center; background-repeat: no-repeat, no-repeat;"></div>',
			'caption' => '<div class="extra-content large"><i class="eticon eticon-shadow eticon-lg eticon-meter"></i></div>
			<div class="caption-container" style="background: #829c02;">
				<div class="caption-content">
					<h1>Switch your<br>Energy Tariff</h1>
					<p>We’ll tell you when your energy contract is up and find you a cheaper tariff, hassle free!</p>
				</div>
			</div>'
		],
		'item3' => [
			'img' => '<div class="item-image" style="background-image: url('.ASSETS_URL.'/img/stock/emergency-lights.jpg)"></div>',
			'caption' => '<div class="extra-content large"><i class="eticon eticon-shadow eticon-lg eticon-exit"></i> <i class="eticon eticon-shadow eticon-lg eticon-bulb-alt"></i></div>
			<div class="caption-container bg-color-greenBright">
				<div class="caption-content">
					<h1>Emergency Lighting<br>Testing Wizard</h1>
					<p>Complete your tests in less than half the time with our brand new Emergency Lighting software</p>
				</div>
			</div>'
		]
	];

	// caption-container can have a span.badge to show text label in top-right

	if(count($items) == 0) {
		echo '<div class="well dashboard-widget no-margin" id="'.$widget_info->ui_id.'" style="padding:20px 0;background:#9BD4DF;"><div style="';
		echo "margin:0 33%;padding:0;width:33%;height:100%;background:url('".ASSETS_URL."/img/eticom_large.png') center no-repeat; background-size:contain;";
		echo '"></div></div>';
		return;
	}
?>

<?php if(BRANDING === 'elanet') { ?>
	<div class="well dashboard-widget no-margin" id="<?= $widget_info->ui_id; ?>" style="padding:32px;background:#fff">
		<div style="margin:auto;max-width:600px;width:100%;height:100%;background:#fff url('branding/elanet/elanet_logo_blue.png');background-size:contain;background-repeat:no-repeat;background-position:center center;"></div>
	</div>
<?php } else { ?>
	<div class="well dashboard-widget no-margin carousel-widget" id="<?= $widget_info->ui_id; ?>" style="padding:0;background:#fff;">
		<?php
			$ui->create_carousel($items)->print_html();
		?>
	</div>
<?php } ?>

<script>
	$('.carousel').carousel({
		'interval': 5000
	});
</script>
