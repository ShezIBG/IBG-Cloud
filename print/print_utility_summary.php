<!-- /* JEANE MYPDF */  -->


<!doctype html>
<html>

<?php

if(!$print_auth) return;

$sql = App::sql();
list($building_id, $period) = App::get(['building_id', 'period'], '', true);

if(!$building_id || !$period) return;

list($year, $month) = explode('-', $period);
$monthName = date('F', mktime(0, 0, 0, $month, 10));

$building = App::select('building', $building_id);
$client_id = $building['client_id'];
$client = App::select('client', $client_id);

$building_address = [];
if($building['address']) $building_address[] = $building['address'];
if($building['posttown']) $building_address[] = $building['posttown'];
if($building['postcode']) $building_address[] = $building['postcode'];

$client_logo = '';
if($client['image_id']) {
	$uc = new UserContent($client['image_id']);
	$url = $uc->get_url();
	$client_logo = "<img src=\"$url\" style=\"max-width: 100%; max-height: 120px;\">";
}

$date_from = "$year-$month-01";
$date_to = strtotime('+1 month', strtotime($date_from));
$date_to = date('Y-m-d', strtotime('-1 day', $date_to));

$period_from = date('d/m/Y', strtotime($date_from));
$period_to = date('d/m/Y', strtotime($date_to));

$b = new Building($building_id);

$has_e0 = false;
$has_g0 = false;
$has_w0 = false;
$has_e1 = false;
$has_ex = false;

// Electricity imported

$meters = $b->get_main_monitored_meters('E');
$mids = implode(', ', array_map(function($m) { return $m->id; }, $meters ?: []));
if($mids) {
	$has_e0 = true;
	$result = $sql->query_row("SELECT SUM(total_imported_total) AS total_units, SUM(total_cost_total) AS total_cost, SUM(total_exported_total) AS total_exported FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to';", MySQL::QUERY_ASSOC, false);
	$e0_total_units = App::format_number($result['total_units'] ?: 0, 2, 2);
	$e0_total_cost = App::format_number($result['total_cost'] ?: 0, 2, 2);

	$has_ex = true;
	$ex_total_units = App::format_number($result['total_exported'] ?: 0, 2, 2);

	$result = $sql->query("SELECT DAY(reading_day) AS day, SUM(total_imported_total) AS total_units FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to' GROUP BY reading_day ORDER BY reading_day;", MySQL::QUERY_ASSOC, false) ?: [];
	$e0_day_title = [];
	$e0_day_total = [];
	foreach($result as $row) {
		$e0_day_title[] = $row['day'] < 10 ? '0'.$row['day'] : $row['day'];
		$e0_day_total[] = $row['total_units'] ?: 0;
	}
}

// Gas used

$meters = $b->get_main_monitored_meters('G');
$mids = implode(', ', array_map(function($m) { return $m->id; }, $meters ?: []));
if($mids) {
	$has_g0 = true;
	$result = $sql->query_row("SELECT SUM(total_imported_total) AS total_units, SUM(total_cost_total) AS total_cost, SUM(gas_imported_m3_total) AS total_m3 FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to';", MySQL::QUERY_ASSOC, false);
	$g0_total_units = App::format_number($result['total_units'] ?: 0, 2, 2);
	$g0_total_cost = App::format_number($result['total_cost'] ?: 0, 2, 2);
	$g0_total_m3 = App::format_number($result['total_m3'] ?: 0, 2, 2);

	$result = $sql->query("SELECT DAY(reading_day) AS day, SUM(total_imported_total) AS total_units FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to' GROUP BY reading_day ORDER BY reading_day;", MySQL::QUERY_ASSOC, false) ?: [];
	$g0_day_title = [];
	$g0_day_total = [];
	foreach($result as $row) {
		$g0_day_title[] = $row['day'] < 10 ? '0'.$row['day'] : $row['day'];
		$g0_day_total[] = $row['total_units'] ?: 0;
	}
}

// Water used

$meters = $b->get_main_monitored_meters('W');
$mids = implode(', ', array_map(function($m) { return $m->id; }, $meters ?: []));
if($mids) {
	$has_w0 = true;
	$result = $sql->query_row("SELECT SUM(total_imported_total) AS total_units, SUM(total_cost_total) AS total_cost FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to';", MySQL::QUERY_ASSOC, false);
	$w0_total_units = App::format_number($result['total_units'] ?: 0, 2, 2);
	$w0_total_cost = App::format_number($result['total_cost'] ?: 0, 2, 2);

	$result = $sql->query("SELECT DAY(reading_day) AS day, SUM(total_imported_total) AS total_units FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to' GROUP BY reading_day ORDER BY reading_day;", MySQL::QUERY_ASSOC, false) ?: [];
	$w0_day_title = [];
	$w0_day_total = [];
	foreach($result as $row) {
		$w0_day_title[] = $row['day'] < 10 ? '0'.$row['day'] : $row['day'];
		$w0_day_total[] = $row['total_units'] ?: 0;
	}
}

// Electricity generated

$meters = $b->get_main_monitored_meters('E', true);
$mids = implode(', ', array_map(function($m) { return $m->id; }, $meters ?: []));
if($mids) {
	$has_e1 = true;
	$result = $sql->query_row("SELECT SUM(total_imported_total) AS total_units, SUM(total_cost_total) AS total_cost FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to';", MySQL::QUERY_ASSOC, false);
	$e1_total_units = App::format_number($result['total_units'] ?: 0, 2, 2);
	$e1_total_cost = App::format_number($result['total_cost'] ?: 0, 2, 2);

	$result = $sql->query("SELECT DAY(reading_day) AS day, SUM(total_imported_total) AS total_units FROM automated_meter_reading_history WHERE meter_id IN ($mids) AND reading_day BETWEEN '$date_from' AND '$date_to' GROUP BY reading_day ORDER BY reading_day;", MySQL::QUERY_ASSOC, false) ?: [];
	$e1_day_title = [];
	$e1_day_total = [];
	foreach($result as $row) {
		$e1_day_title[] = $row['day'] < 10 ? '0'.$row['day'] : $row['day'];
		$e1_day_total[] = $row['total_units'] ?: 0;
	}
}

// Weather chart

$has_we = false;
$result = $sql->query("SELECT DISTINCT DAY(date) AS day, temperatureMin, temperatureMax FROM weather WHERE building_id = '$building_id' AND date BETWEEN '$date_from' AND '$date_to' ORDER BY date;", MySQL::QUERY_ASSOC, false);
if($result) {
	$has_we = true;
	$we_day_title = [];
	$we_day_min = [];
	$we_day_max = [];
	foreach($result as $row) {
		$we_day_title[] = $row['day'] < 10 ? '0'.$row['day'] : $row['day'];
		$we_day_min[] = $row['temperatureMin'] ?: 0;
		$we_day_max[] = $row['temperatureMax'] ?: 0;
	}
}

// Pie chart

$pie_slices = [];
$pie_colors = [];
$total = ($has_e0 ? $e0_total_cost : 0) + ($has_g0 ? $g0_total_cost : 0) + ($has_w0 ? $w0_total_cost : 0);
if($total > 0) {
	if($has_e0) {
		$pie_slices[] = App::format_number(($e0_total_cost / $total) * 100, 2, 2);
		$pie_colors[] = 'F7BC3B';
	}
	if($has_g0) {
		$pie_slices[] = App::format_number(($g0_total_cost / $total) * 100, 2, 2);
		$pie_colors[] = '7EAFC6';
	}
	if($has_w0) {
		$pie_slices[] = App::format_number(($w0_total_cost / $total) * 100, 2, 2);
		$pie_colors[] = 'AADBE7';
	}
}

?>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/eticom-icons.css">
	<link rel="stylesheet" href="css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" href="css/styles.css">

	<link rel="stylesheet" href="print_utility_summary.css">

	<title>EticomCloud monthly utility summary</title>
</head>
<body>

	<div id="print" class="content">

		<div class="row">
			<div class="col-sm-6">
				<h2><?= $building['description'] ?></h2>
				<h3>Monthly utility summary</h3>
				<h4><?= $monthName ?> <?= $year ?></h4>
			</div>
			<div class="col-sm-3">
				<?= $client['name'] ?><br>
				<br>
				<?= implode('<br>', $building_address) ?>
			</div>
			<div class="col-sm-3 text-right">
				<?= $client_logo ?>
			</div>
		</div>
		<br><br><br><br>

		<div class="row">
			<div class="col-sm text-center">
				<img src="https://chart.googleapis.com/chart?cht=p&chd=t:<?= implode(',', $pie_slices) ?>&chco=<?= implode(',', $pie_colors) ?>&chs=500x500" style="width: 60%;">
				<?php
					if($has_we) {
						$day_grid = App::format_number(100 / count($we_day_title), 2, 2);
						$weather = '
							<br><br>
							<img src="https://chart.googleapis.com/chart?cht=lc&chd=t:'.implode(',',$we_day_min).'|'.implode(',',$we_day_max).'&chxl=0:|'.implode('|',$we_day_title).'&chco=AADBE7,F7BC3B&chxt=x,y&chbh=23&chds=a&chs=1000x300&chg='.$day_grid.',20&chls=3|3&chxs=0,,17|1,,17" style="width:100%;">
							<h4>Min/max temperature</h4>
						';

						echo $weather;
					}
				?>
			</div>
			<div class="col-sm">
				<table class="table vam">
					<thead>
						<tr>
							<th class="bg-blue text-white" colspan="4"><span class="large">Monthly utility totals</span><br>Period from <?= $period_from ?> to <?= $period_to ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if($has_e0) { ?>
							<tr>
								<td class="shrink large vlarge text-center text-e"><i class="eticon eticon-bolt"></i></td>
								<td><span class="large">Electricity</span><br>imported</td>
								<td class="text-right"><?= $e0_total_units ?>&nbsp;kWh</td>
								<td class="large text-right">&pound;<?= $e0_total_cost ?></td>
							</tr>
						<?php } ?>
						<?php if($has_g0) { ?>
							<tr>
								<td class="shrink large vlarge text-center text-g"><i class="eticon eticon-flame"></i></td>
								<td><span class="large">Gas</span><br>used</td>
								<td class="text-right">
									<?= $g0_total_m3 ?>&nbsp;m<sup>3</sup><br>
									<?= $g0_total_units ?>&nbsp;kWh
								</td>
								<td class="large text-right">&pound;<?= $g0_total_cost ?></td>
							</tr>
						<?php } ?>
						<?php if($has_w0) { ?>
							<tr>
								<td class="shrink large vlarge text-center text-w"><i class="eticon eticon-droplet"></i></td>
								<td><span class="large">Water</span><br>used</td>
								<td class="text-right"><?= $w0_total_units ?>&nbsp;m<sup>3</sup></td>
								<td class="large text-right">&pound;<?= $w0_total_cost ?></td>
							</tr>
						<?php } ?>
						<?php if($has_e1) { ?>
							<tr class="divider">
								<td class="shrink large vlarge text-center text-s"><i class="eticon eticon-leaf"></i></td>
								<td><span class="large">Electricity</span><br>generated</td>
								<td class="text-right"></td>
								<td class="large text-right"><?= $e1_total_units ?>&nbsp;kWh</td>
							</tr>

							<?php if($has_ex) { ?>
								<tr>
									<td class="shrink large vlarge text-center text-s"><i class="eticon eticon-leaf"></i></td>
									<td><span class="large">Electricity</span><br>exported</td>
									<td class="text-right"></td>
									<td class="large text-right"><?= $ex_total_units ?>&nbsp;kWh</td>
								</tr>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<br><br><br>

<?php
	$content = ['', '', '', ''];
	$i = 0;

	if($has_e0) {
		$content[$i] = '
			<img src="https://chart.googleapis.com/chart?cht=bvg&chd=t:'.implode(',',$e0_day_total).'&chxl=0:|'.implode('|',$e0_day_title).'&chco=F7BC3B&chxt=x,y&chbh=23&chds=a&chs=1000x300&chxs=0,,17|1,,17" style="width:100%;">
			<h4>Electricity imported</h4>
		';
		$i++;
	}

	if($has_g0) {
		$content[$i] = '
			<img src="https://chart.googleapis.com/chart?cht=bvg&chd=t:'.implode(',',$g0_day_total).'&chxl=0:|'.implode('|',$g0_day_title).'&chco=7EAFC6&chxt=x,y&chbh=23&chds=a&chs=1000x300&chxs=0,,17|1,,17" style="width:100%;">
			<h4>Gas used</h4>
		';
		$i++;
	}

	if($has_w0) {
		$content[$i] = '
			<img src="https://chart.googleapis.com/chart?cht=bvg&chd=t:'.implode(',',$w0_day_total).'&chxl=0:|'.implode('|',$w0_day_title).'&chco=AADBE7&chxt=x,y&chbh=23&chds=a&chs=1000x300&chxs=0,,17|1,,17" style="width:100%;">
			<h4>Water used</h4>
		';
		$i++;
	}

	if($has_e1) {
		$content[$i] = '
			<img src="https://chart.googleapis.com/chart?cht=bvg&chd=t:'.implode(',',$e1_day_total).'&chxl=0:|'.implode('|',$e1_day_title).'&chco=A2B83A&chxt=x,y&chbh=23&chds=a&chs=1000x300&chxs=0,,17|1,,17" style="width:100%;">
			<h4>Electricity generated</h4>
		';
		$i++;
	}
?>

		<div class="row">
			<div class="col-sm text-center">
				<?= $content[0] ?>
			</div>
			<div class="col-sm text-center">
				<?= $content[1] ?>
			</div>
		</div>
		<br><br><br><br>
		<div class="row">
			<div class="col-sm text-center">
				<?= $content[2] ?>
			</div>
			<div class="col-sm text-center">
				<?= $content[3] ?>
			</div>
		</div>

		<img src="img/eticom-logo-powered-by.png" style="position: absolute; bottom: 4em; right: 2.5em; width: 150px;">
		<div style="position: absolute; bottom: 2em; border-bottom: 20px solid #2E3C47; left: 2em; right: 2em;"></div>
	</div>

</body>
</html>
