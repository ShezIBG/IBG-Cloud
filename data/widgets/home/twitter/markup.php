<?php if(BRANDING === 'elanet') { ?>

<div class="well dashboard-widget no-margin" id="<?= $widget_info->ui_id; ?>" style="padding:0;background:#fff">
	<iframe style="margin:0;border:0;width:100%;height:100%;" src="https://elanet.co.uk"></iframe>
</div>

<?php } else { ?>

<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'blueLight';
	$ui_widget->header('title', '<img style="height:50px;margin:10px;border-radius:5px;" src="'.ASSETS_URL.'/img/eticom_twitter.jpg"> <span style="display:inline-block;vertical-align:middle;">Eticom Ltd<br><strong style="font-size:1.5em;">@EticomUK</strong></span> <i class="eticon eticon-logo-twitter eticon-shadow"></i>');

	$content = '<div class="widget-row"><a data-chrome="noheader nofooter transparent" class="twitter-timeline" href="https://twitter.com/EticomUK">Tweets by EticomUK</a></div>';

	$ui_widget->body('content', $content);
	$ui_widget->body('class', 'padding-0');
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = ' ';

	$ui_widget->print_html();
?>

<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>

<?php } ?>
