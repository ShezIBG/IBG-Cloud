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
	MySQL::$clean = false;

	$project_id = App::get('project_id');
	$project = App::select('project', $project_id) ?: [];

	$si_id = $project['system_integrator_id'];
	$si = App::select('system_integrator', $si_id);

	$footer_text = htmlentities($si['proposal_footer'] ?: '');
	$footer_text = str_replace(' ', '&nbsp;', $footer_text);
?>

	<footer>
		<?= $footer_text ?>&nbsp;
		<span class="pageno">Page <?= $_GET['page'] ?> of <?= $_GET['topage'] ?></span>
	</footer>

</body>
</html>
