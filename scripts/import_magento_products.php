#!/usr/bin/php -q
<?php

/*
	SELECT
		cpe.entity_id AS product_id,
		cpe.sku,
		man.value AS manufacturer_id,
		man.value AS manufacturer_name,
		model.value AS model,
		name.value AS name,
		ldesc.value AS long_description,
		cost.value AS cost,
		reseller.value AS reseller_price,
		trade.value AS trade_price,
		price.value AS retail_price,
		image.value AS image
	FROM catalog_product_entity AS cpe

	LEFT JOIN catalog_product_entity_int AS man_link ON man_link.attribute_id = 81 AND man_link.entity_id = cpe.entity_id
	LEFT JOIN eav_attribute_option_value AS man ON man.option_id = man_link.value

	LEFT JOIN catalog_product_entity_varchar AS model ON model.attribute_id = 179 AND model.entity_id = cpe.entity_id

	LEFT JOIN catalog_product_entity_varchar AS name ON name.attribute_id = 71 AND name.entity_id = cpe.entity_id

	LEFT JOIN catalog_product_entity_text AS ldesc ON ldesc.attribute_id = 72 AND ldesc.entity_id = cpe.entity_id

	LEFT JOIN catalog_product_entity_decimal AS cost ON cost.attribute_id = 79 AND cost.entity_id = cpe.entity_id

	LEFT JOIN catalog_product_entity_decimal AS price ON price.attribute_id = 75 AND price.entity_id = cpe.entity_id

	LEFT JOIN catalog_product_entity_group_price AS reseller ON reseller.customer_group_id = 3 AND reseller.entity_id = cpe.entity_id

	LEFT JOIN catalog_product_entity_group_price AS trade ON trade.customer_group_id = 2 AND trade.entity_id = cpe.entity_id

	LEFT JOIN catalog_product_entity_varchar AS image ON image.attribute_id = 85 AND image.entity_id = cpe.entity_id

	;
*/

include '../inc/init.app.php';

/*
 * This script imports formatted CSV data into the products tables.
 * It's a one-off script, which will probably never be executed again as-is.
 */

function csvstring_to_array($string, $separatorChar = ',', $enclosureChar = '"', $newlineChar = PHP_EOL) {
	$array = array();
	$size = strlen($string);
	$columnIndex = 0;
	$rowIndex = 0;
	$fieldValue="";
	$isEnclosured = false;
	for($i=0; $i<$size; $i++) {

		$char = $string{$i};
		$addChar = "";

		if($isEnclosured) {
			if($char==$enclosureChar) {

				if($i+1<$size && $string{$i+1}==$enclosureChar){
					// escaped char
					$addChar=$char;
					$i++; // dont check next char
				} else{
					$isEnclosured = false;
				}
			} else {
				$addChar=$char;
			}
		} else {
			if($char==$enclosureChar) {
				$isEnclosured = true;
			} else {

				if($char==$separatorChar) {

					$array[$rowIndex][$columnIndex] = $fieldValue;
					$fieldValue="";

					$columnIndex++;
				} elseif($char==$newlineChar) {
					$array[$rowIndex][$columnIndex] = $fieldValue;
					$fieldValue="";
					$columnIndex=0;
					$rowIndex++;
				} else {
					$addChar=$char;
				}
			}
		}
		if($addChar!=""){
			$fieldValue.=$addChar;
		}
	}

	if($fieldValue) { // save last field
		$array[$rowIndex][$columnIndex] = $fieldValue;
	}

	return $array;
}

echo "Processing CSV data... ";
$data = file_get_contents('php://stdin');
$data = str_replace("\r\n", "\n", $data);
$data = str_replace("\r", "\n", $data);
$data = csvstring_to_array($data, ',');
echo "done.\n";

echo "Checking data integrity... ";
foreach($data as $row) {
	if(count($row) !== 12) {
		echo "FAILED\n\n";
		var_dump($row);
		echo "\n\n";
		exit;
	}
}
echo "done.\n\n";

echo "IMPORTING DATA\n\n";

$owner_level = 'SI';
$owner_id = '3';

echo "Creating base unit... ";
$unit_id = App::sql()->insert("INSERT INTO product_unit (owner_level, owner_id, name, description) VALUES ('$owner_level', '$owner_id', 'pc.', 'Pieces');");
echo "done.\n\n";

$manufacturer_cache = [];

// Remove header row
array_shift($data);

echo "Creating products...";
foreach($data as $row) {
	$row = array_map('trim', $row);

	// cpe.entity_id AS product_id,
	// cpe.sku,
	// man.value AS manufacturer_id,
	// man.value AS manufacturer_name,
	// model.value AS model,
	// name.value AS name,
	// ldesc.value AS long_description,
	// cost.value AS cost,
	// reseller.value AS reseller_price,
	// trade.value AS trade_price,
	// price.value AS retail_price,
	// image.value AS image

	$manufacturer = $row[3];
	$model = $row[4];
	$sku = $row[1];
	$short_description = $row[5];
	$long_description = $row[6];
	$unit_cost = $row[7];
	$reseller_price = $row[8];
	$trade_price = $row[9];
	$retail_price = $row[10];

	// Some fields CANNOT contain linebreaks
	$manufacturer = trim(str_replace("\n", " ", $manufacturer));
	$model = trim(str_replace("\n", " ", $model));
	$sku = trim(str_replace("\n", " ", $sku));
	$short_description = trim(str_replace("\n", " ", $short_description));

	// Remove null values
	if($manufacturer === 'NULL') $manufacturer = '';
	if($model === 'NULL') $model = '';
	if($sku === 'NULL') $sku = '';
	if($short_description === 'NULL') $short_description = '';
	if($long_description === 'NULL') $long_description = '';
	if($unit_cost === 'NULL') $unit_cost = '';
	if($reseller_price === 'NULL') $reseller_price = '';
	if($trade_price === 'NULL') $trade_price = '';
	if($retail_price === 'NULL') $retail_price = '';

	// Make sure prices are set
	if(!$unit_cost) $unit_cost = 0;
	if(!$reseller_price) $reseller_price = 0;
	if(!$trade_price) $trade_price = 0;
	if(!$retail_price) $retail_price = 0;

	$manufacturer_id = null;

	if($sku === '') $sku = null;

	if($manufacturer) {
		if(isset($manufacturer_cache[$manufacturer])) {
			$manufacturer_id = $manufacturer_cache[$manufacturer];
		} else {
			$manufacturer_id = App::insert('product_entity', [
				'owner_level' => $owner_level,
				'owner_id' => $owner_id,
				'name' => $manufacturer
			]);
			$manufacturer_cache[$manufacturer] = $manufacturer_id;
		}
	}

	$product_id = App::insert('product', [
		'owner_level' => $owner_level,
		'owner_id' => $owner_id,
		'sku' => $sku,
		'manufacturer_id' => $manufacturer_id,
		'model' => $model,
		'short_description' => $short_description,
		'long_description' => $long_description,
		'unit_id' => $unit_id,
		'sold_to_customer' => '1',
		'sold_to_reseller' => '1'
	]);

	App::sql()->insert(
		"INSERT INTO product_price (product_id, seller_level, seller_id, unit_cost, reseller_price, trade_price, retail_price)
		VALUES ('$product_id', '$owner_level', '$owner_id', '$unit_cost', '$reseller_price', '$trade_price', '$retail_price');
	");

	echo ".";
}

echo " done.\n\n";
echo "Import complete.\n\n";
