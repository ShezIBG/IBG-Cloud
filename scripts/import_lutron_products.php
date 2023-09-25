#!/usr/bin/php -q
<?php

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
	if(count($row) !== 5) {
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
$unit_id = '5';

$pricing = new ProductPricing();

echo "Creating products...";
foreach($data as $row) {
	$row = array_map('trim', $row);

	$model = $row[0];
	$description = $row[1] && $row[2] ? "$row[1] $row[2]" : ($row[1] ?: $row[2]);
	$sku = $row[3];
	$cost = str_replace(',','',$row[4]);

	// Resolve cost price
	if(is_numeric($cost)) {
		$price = $cost;
		$cost = $cost * 0.55;
	} else {
		$price = 0;
		$cost = 0;
	}

	// Some fields CANNOT contain linebreaks
	$model = App::escape(trim(str_replace("\n", " ", $model)));
	$sku = trim(str_replace("\n", " ", $sku));
	$description = trim(str_replace("\n", " ", $description));

	// Remove null values
	if($model === 'NULL') $model = '';
	if($sku === 'NULL') $sku = '';
	if($description === 'NULL' || $description === 'NULL NULL') $description = '';

	$manufacturer_id = 7;

	if($sku === '') $sku = null;

	$product = App::sql()->query_row(
		"SELECT id FROM product AS p
		JOIN product_price AS pp ON pp.seller_level = '$owner_level' AND pp.seller_id = '$owner_id' AND pp.product_id = p.id
		WHERE p.model = '$model' AND p.discontinued = 0
		LIMIT 1;
	", MySQL::QUERY_ASSOC);

	if($product) {
		// Found, update price only
		App::sql()->update(
			"UPDATE product_price
			SET unit_cost = '$cost', retail_price = '$price'
			WHERE product_id = '$product[id]' AND seller_level = '$owner_level' AND seller_id = '$owner_id';
		");

		$pricing->apply_product_change($product['id']);
	} else {
		// Not found, create product
		$product_id = App::insert('product', [
			'owner_level' => $owner_level,
			'owner_id' => $owner_id,
			'sku' => $sku,
			'manufacturer_id' => $manufacturer_id,
			'model' => $model,
			'short_description' => $description,
			'unit_id' => $unit_id,
			'sold_to_customer' => '1',
			'sold_to_reseller' => '1'
		]);

		App::sql()->insert(
			"INSERT INTO product_price (product_id, seller_level, seller_id, unit_cost, reseller_price, trade_price, retail_price)
			VALUES ('$product_id', '$owner_level', '$owner_id', '$cost', '0', '0', '$price');
		");
	}

	echo ".";
}

echo " done.\n\n";
echo "Import complete.\n\n";
