<?php
	require_once '../init.view.php';

// Get building details
/*
$building_id = App::get('building_id');
if(!$building_id) {
	$ui->print_alert('Building not found.', 'warning');
	exit;
}

$building = new Building($building_id);
$client_id = $building->info->client_id;

$agents = App::sql()->query('SELECT * FROM agent WHERE client_id = '.$client_id.' ORDER BY name;');

?>
<header class="bg-color-yellow">
	<span>Revenue &amp; Costs</span>
	<ul>
		<li><a href="#" data-view="building_agents?building_id=<?= $building_id; ?>"><i class="eticon eticon-shadow eticon-user"></i></a>
		</li><li><a href="#" data-view="building_revenue?building_id=<?= $building_id; ?>" class="bg-color-yellow active"><i class="eticon eticon-shadow eticon-circle-pound"></i></a></li>
	</ul>
</header>

<div class="content txt-color-darken">
	<div class="row revenue">
		<h2>Monthly Revenue</h2>
		<div class="scrolltable">
			<div class="header">
				<table class="table table-condensed">
					<thead>
						<th>Item</th>
						<th>Net</th>
						<th>VAT</th>
						<th>Total</th>
					</thead>
				</table>
			</div>
			<div class="body">
				<table class="table table-condensed">
					<tr>
						<td>Unit 1 rental charge</td>
						<td>1,000.00</td>
						<td>200.00</td>
						<td>1,200.00</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	<div class="row costs">
		<h2>Monthly Costs</h2>
	</div>
</div>

<script>
	$(function() {
		selectMenuItem($('#menu-revenue'));

		var $view = $('#view');

		$view.find('header ul').on('click', 'li a', function(e) {
			e.preventDefault();
			var $this = $(this);
			if(!$this.is('.active')) {
				LoadTenantView($this.data('view'));
			}
		});

		resizeScrolltables();
	});
</script>
<?php */ ?>
<div style="margin:0;padding:0;width:100%;height:100%;background:url('<?= ASSETS_URL ?>/img/blag/revenue-colony.png') center center;background-repeat:no-repeat;background-size:contain;">
</div>
