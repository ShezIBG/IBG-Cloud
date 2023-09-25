<?php
	$codes = [
		400 => [
			'title'   => 'Not found',
			'message' => 'The page you requested <b class="text-danger">could not be found</b>, either contact your webmaster or try again. Use your browsers <b>Back</b> button to navigate to the page you have prevously come from',
			'icon'    => 'fa-warning text-warning'
		],
		403 => [
			'title'   => 'Forbidden',
			'message' => 'The page you requested is <b class="text-danger">forbidden</b>, either contact your webmaster or try again. Use your browsers <b>Back</b> button to navigate to the page you have prevously come from',
			'icon'    => 'fa-lock txt-color-orange'
		],
		404 => [
			'title'   => 'Not found',
			'message' => 'The page you requested <b class="text-danger">could not be found</b>, either contact your webmaster or try again. Use your browsers <b>Back</b> button to navigate to the page you have prevously come from',
			'icon'    => 'fa-warning text-warning'
		],
		500 => [
			'title'   => 'Internal error',
			'message' => 'The server encountered an <b class="text-danger">internal server error</b>, either contact your webmaster or try again. Use your browsers <b>Back</b> button to navigate to the page you have prevously come from',
			'icon'    => 'fa-x text-danger'
		]
	];

	$code = isset($_GET['code']) && isset($codes[$_GET['code']]) ? $_GET['code'] : 500;
	$code_info = $codes[$code];
?>

<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="text-center error-box margin-top-10">
			<h2 class="font-xl"><strong><i class="fa fa-fw <?= $code_info['icon']; ?> fa-lg"></i> <?= $code.' '.$code_info['title'] ?> </strong></h2>
			<br>
			<p>
				<?= $code_info['message']; ?>
			</p>
		</div>
	</div>
</div>
