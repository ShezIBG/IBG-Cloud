<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/eticom-icons.css">
	<link rel="stylesheet" href="css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" href="css/styles.css">

	<link rel="stylesheet" href="print_stock_location.css">

	<title>Stock Location</title>
</head>
<body>

<?php
	if(!$print_auth) return;

	$warehouse_id = App::get('warehouse', '', true);
	if(!$warehouse_id) return;

	$record = App::select('stock_warehouse', $warehouse_id);
	if(!$record) return;

	$rack = App::get('rack', '', true);
	$bay = App::get('bay', '', true);
	$level = App::get('level', '', true);

	$condition = "l.warehouse_id = '$warehouse_id'";
	if($rack !== '') $condition .= " AND l.rack = '$rack'";
	if($bay !== '') $condition .= " AND l.bay = '$bay'";
	if($level !== '') $condition .= " AND l.level = '$level'";
	$condition .= ' AND l.archived = 0';

	$bays = App::sql()->query("SELECT DISTINCT l.bay FROM stock_location AS l WHERE $condition AND l.bay IS NOT NULL AND l.bay <> '' ORDER BY l.bay;", MySQL::QUERY_ASSOC) ?: [];

	$locations = App::sql()->query("SELECT * FROM stock_location AS l WHERE $condition ORDER BY l.rack, l.bay, l.level;", MySQL::QUERY_ASSOC) ?: [];

	$products = App::sql()->query(
		"SELECT
			l.*,
			p.model,
			p.sku, p.manufacturer_sku, p.short_description,
			pm.name AS manufacturer_name,
			uc.path AS image_url
		FROM stock_location AS l
		JOIN stock_warehouse_product AS swp ON swp.warehouse_id = l.warehouse_id AND swp.location_id = l.id
		JOIN product AS p ON p.id = swp.product_id
		LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
		LEFT JOIN user_content AS uc ON uc.id = p.image_id
		WHERE $condition
		ORDER BY l.rack, l.bay, l.level, p.model, p.short_description;
	", MySQL::QUERY_ASSOC) ?: [];

	foreach($products as &$item) {
		if($item['image_url']) {
			$item['image_url'] = UserContent::url_by_path($item['image_url']);
		}
	}
	unset($item);

?>

	<!-- Rack label -->
	<h5>Racks</h5>
	<div class="location">
		<h1>Rack <?= $rack ?></h1>
	</div>

	<!-- Bay labels -->
	<?php if(count($bays)) { ?>
		<hr>
		<h5>Bays</h5>
		<?php foreach($bays as $item) { ?>
			<div class="location">
				<h1>Bay <?= $item['bay'] ?></h1>
			</div>
		<?php } ?>
	<?php } ?>

	<!-- Location labels -->
	<hr>
	<h5>Locations</h5>
	<?php foreach($locations as $item) { ?>
		<div class="location">
			<h1>
				<i class="eticon eticon-arrow-<?= $item['label'] ?>"></i>
				<?= $item['description'] ?>
			</h1>
		</div>
	<?php } ?>

	<!-- Product labels -->
	<?php if(count($products)) { ?>
		<hr>
		<h5>Products</h5>
		<?php
			foreach($products as $item) {
				$sku = $item['sku'];
				if($sku === null || $sku === '') $sku = $item['manufacturer_sku'];
		?>
			<div class="location">
				<h1>
					<i class="eticon eticon-arrow-<?= $item['label'] ?>"></i>
					<?= $item['description'] ?>
				</h1>
				<div class="product-info">
					<div class="sku"><?= $sku ?></div>
					<?php if($sku !== null && $sku !== '') { ?>
						<div>
							<img src="../barcode?output=svg&wf=1&h=20&code=<?= urlencode($sku) ?>" class="barcode">
						</div>
					<?php } ?>
					<div class="text-small"><?= $item['model'] ?></div>
				</div>
				<?php if($item['image_url']) { ?>
					<img src="<?= $item['image_url'] ?>" class="product-image">
				<?php } ?>
			</div>
		<?php } ?>
	<?php } ?>

</body>
</html>
