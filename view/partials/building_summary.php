<?php
	require_once '../init.view.php';

	// Get building details

	$building_id = App::get('building_id', null, true);
	if (!$building_id) {
		$ui->print_alert('Building not found.', 'warning');
		return;
	}

	$building = new Building($building_id);

	$status_current = "'".Lease::STATUS_CURRENT_ACTIVE."', '".Lease::STATUS_CURRENT_EXPIRING."', '".Lease::STATUS_CURRENT_ENDING."'";
	$status_ending = Lease::STATUS_CURRENT_ENDING;
	$status_expiring = Lease::STATUS_CURRENT_EXPIRING;

	$data = App::sql()->query_row(
		"SELECT
			COUNT(*) AS total_units,
			COUNT(ta.tenant_id) AS occupied_units,
			COUNT(DISTINCT ta.tenant_id) AS total_tenants,
			SUM(tl.rental_cost_pounds_ex_vat_per_year + tl.service_charge_pounds_ex_vat_per_year) / 12 AS monthly_income,
			SUM(IF(tl.status = '$status_ending', 1, 0)) AS lease_ending,
			SUM(IF(tl.status = '$status_expiring', 1, 0)) AS lease_expiring
		FROM
			building AS b
		JOIN floor AS f ON f.building_id = b.id
		JOIN area AS a ON a.floor_id = f.id
		JOIN tenanted_area AS ta ON ta.area_id = a.id AND (a.is_tenanted = 1 OR a.is_owner_occupied = 1)
		LEFT JOIN tenant_lease AS tl ON tl.area_id = a.id AND tl.status IN ($status_current)
		WHERE b.id = '$building_id'
	");

	$total_units = $data->total_units ?: 0;
	$occupied_units = $data->occupied_units ?: 0;
	$monthly_income = $data->monthly_income ?: 0;
	$total_tenants = $data->total_tenants ?: 0;
	$lease_ending = $data->lease_ending ?: 0;
	$lease_expiring = $data->lease_expiring ?: 0;

	$data = App::sql()->query_row(
		"SELECT
			COUNT(*) as total_agents
		FROM agent_building
		WHERE building_id = '$building_id'
	");

	$total_agents = $data->total_agents ?: 0;

	$default_image_url = ASSETS_URL.'/img/building-add-image.png';
	$image_url = $default_image_url;
	if ($building->info->image_id) {
		$image = new UserContent($building->info->image_id);
		$image_url = $image->get_url();
	}
?>

<div id="building-summary" class="container-fluid content txt-color-darken">
	<div class="row">
		<div class="col col-sm-4 extra-padding">
			<div style="width:100%;height:0;padding-bottom:100%;background:url('<?= $image_url; ?>');background-size:cover;background-position:center;border-radius:50%;"></div>
			<h1><?= $building->info->description; ?></h1>
			<p class="address">
				<?php
					if($building->info->address) echo $building->info->address.'<br>';
					if($building->info->posttown) echo $building->info->posttown.'<br>';
					if($building->info->postcode) echo $building->info->postcode.'<br>';
				?>
			</p>
		</div>
		<div class="col col-sm-8 divided-divs">
			<div class="row units">
				<div class="col col-sm-3 text-center">
					<span class="eticon-stack text-center">
						<i class="eticon eticon-circle eticon-stack-2x txt-color-greyDark"></i>
						<i class="eticon eticon-area eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
					</span>
					<div class="title">Units</div>
					<div class="value"><?= $total_units; ?></div>
				</div>
				<div class="col col-sm-3 text-center">
					<span class="eticon-stack text-center">
						<i class="eticon eticon-circle eticon-stack-2x txt-color-greenLight"></i>
						<i class="eticon eticon-area eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
					</span>
					<div class="title">Occupied</div>
					<div class="value"><?= $occupied_units; ?></div>
				</div>
				<div class="col col-sm-3 text-center">
					<span class="eticon-stack text-center">
						<i class="eticon eticon-circle eticon-stack-2x txt-color-greyLight"></i>
						<i class="eticon eticon-area eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
					</span>
					<div class="title">Vacant</div>
					<div class="value"><?= $total_units - $occupied_units; ?></div>
				</div>
				<div class="col col-sm-3 text-center">
					<span class="eticon-stack text-center">
						<i class="eticon eticon-circle eticon-stack-2x txt-color-red"></i>
						<i class="eticon eticon-tenants eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
					</span>
					<div class="title">Tenants</div>
					<div class="value"><?= $total_tenants; ?></div>
				</div>
			</div>

			<div class="row details">
				<div class="col col-sm-6">
					<table>
						<tr>
							<td class="icon">
								<span class="eticon-stack text-center font-lg">
									<i class="eticon eticon-circle eticon-stack-2x txt-color-greyDark"></i>
									<i class="eticon eticon-pound-plus eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
								</span>
							</td>
							<td class="value">
								Building monthly income<br>
								<span class="font-lg">&pound;<?= number_format($monthly_income, 2); ?></span>
							</td>
						</tr>
					</table>
				</div>
				<div class="col col-sm-6">
					<table>
						<tr>
							<td class="icon">
								<span class="eticon-stack text-center font-lg">
									<i class="eticon eticon-circle eticon-stack-2x txt-color-purple"></i>
									<i class="eticon eticon-agent eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
								</span>
							</td>
							<td class="value">
								Agents assigned<br>
								<span class="font-lg"><?= $total_agents; ?></span>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="row details">
				<div class="col col-sm-6">
					<table>
						<tr>
							<td class="icon">
								<span class="eticon-stack text-center font-lg">
									<i class="eticon eticon-circle eticon-stack-2x txt-color-red"></i>
									<i class="eticon eticon-alert eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
								</span>
							</td>
							<td class="value">
								Lease expiring<br>
								<span class="font-lg"><?= $lease_ending; ?></span>
							</td>
						</tr>
					</table>
				</div>
				<div class="col col-sm-6">
					<table>
						<tr>
							<td class="icon">
								<span class="eticon-stack text-center font-lg">
									<i class="eticon eticon-circle eticon-stack-2x txt-color-blueDark"></i>
									<i class="eticon eticon-calendar eticon-stack-1x eticon-inverse eticon-shadow txt-color-white"></i>
								</span>
							</td>
							<td class="value">
								Lease ending<br>
								<span class="font-lg"><?= $lease_ending; ?></span>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
echo
	'<li><iframe src="https://www.google.com/maps/embed/v1/place?key=AIzaSyAbik3n1kA7O9z-s9BaufYDzEtajR77S9k 
						&q='.$building->info->posttown.','.$building->info->postcode.'"width="850" height="350" style="margin-left:590px; margin-top:-105px;" referrerpolicy="no-referrer-when-downgrade"
						allowfullscreen">
	</iframe>
	</li>'  
?>