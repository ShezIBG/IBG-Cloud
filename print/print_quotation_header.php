<!doctype html>
<html>

<?php if(!$print_auth) return; ?>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/eticom-icons.css">
	<link rel="stylesheet" href="css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" href="css/styles.css">

	<link rel="stylesheet" href="print_quotation.css">
</head>
<body>

<?php

	function compile_address($array, $delimiter = ', ') {
		$addr = [];
		foreach($array as $s) {
			if($s) $addr[] = $s;
		}
		return implode($delimiter, $addr);
	}

	MySQL::$clean = false;

	$project_id = App::get('project_id');
	$hide_labour = App::get('hide_labour', 0);
	$project = App::select('project', $project_id) ?: [];

	$si_id = $project['system_integrator_id'];
	$si = App::select('system_integrator', $si_id);

	if($si['logo_on_light_id']) {
		$uc = new UserContent($si['logo_on_light_id']);
		$logo_on_light = $uc->get_url();
	}
	if($si['logo_on_dark_id']) {
		$uc = new UserContent($si['logo_on_dark_id']);
		$logo_on_dark = $uc->get_url();
	}
	if($logo_on_light && !$logo_on_dark) $logo_on_dark = $logo_on_light;
	if($logo_on_dark && !$logo_on_light) $logo_on_light = $logo_on_dark;

	$customer_id = $project['customer_id'];
	$customer = App::select('sales_customer', $customer_id) ?: [];

	$si_name = htmlentities($si['company_name']);

	$si_address = htmlentities(compile_address([
		$si['address_line_1'],
		$si['address_line_2'],
		$si['address_line_3'],
		$si['posttown'],
		$si['postcode']
	])) ?: '';

	$customer_name = htmlentities($customer['name']);

	$customer_address = htmlentities(compile_address([
		$customer['address_line_1'],
		$customer['address_line_2'],
		$customer['address_line_3'],
		$customer['posttown'],
		$customer['postcode']
	])) ?: '';

?>

	<header>
		<table style="width: 100%;">
			<tbody>
				<tr>
					<td style="width: 50%; vertical-align: top; padding-right: 40px; padding-left: 0;">
						<h3 style="margin-bottom: 0;">
							Quotation #<?=$project['project_no'] ?>
							<?php if($hide_labour) { ?>
								<span class="subtitle">without labour</span>
							<?php } ?>
						</h3>

						<div><b><?= htmlentities($project['description']) ?></b></div>

						<div style="margin-top: 20px;"><b><?= $customer_name ?></b></div>
						<div><?= $customer_address ?></div>

						<?php
							// if ($customer['address_line_1']) echo '<div>'.htmlentities($customer['address_line_1']).'</div>';
							// if ($customer['address_line_2']) echo '<div>'.htmlentities($customer['address_line_2']).'</div>';
							// if ($customer['address_line_3']) echo '<div>'.htmlentities($customer['address_line_3']).'</div>';
							// if ($customer['posttown']) echo '<div>'.htmlentities($customer['posttown']).'</div>';
							// if ($customer['postcode']) echo '<div>'.htmlentities($customer['postcode']).'</div>';
						?>
					</td>
					<td style="width: 50%; vertical-align: top;">
						<h3 style="margin-bottom: 0;"><?=$si_name ?></h3>
						<?php
							if ($si['address_line_1']) echo '<div>'.htmlentities($si['address_line_1']).'</div>';
							if ($si['address_line_2']) echo '<div>'.htmlentities($si['address_line_2']).'</div>';
							if ($si['address_line_3']) echo '<div>'.htmlentities($si['address_line_3']).'</div>';
							if ($si['posttown']) echo '<div>'.htmlentities($si['posttown']).'</div>';
							if ($si['postcode']) echo '<div>'.htmlentities($si['postcode']).'</div>';
						?>
					</td>
					<td style="vertical-align: top; text-align: right; padding-right: 0;">
						<span class="logo-large" style="background-image: url('<?= $logo_on_light ?>');"></span>
					</td>
				</tr>
			</tbody>
		</table>
	</header>

</body>
</html>
