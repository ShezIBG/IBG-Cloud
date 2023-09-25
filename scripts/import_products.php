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
	if(count($row) !== 59) {
		echo "FAILED\n\n";
		var_dump($row);
		echo "\n\n";
		exit;
	}
}
echo "done.\n\n";

echo "IMPORTING DATA\n\n";

echo "Creating base unit... ";
$unit_id = App::sql()->insert("INSERT INTO product_unit (name, description) VALUES ('pc.', 'Pieces');");
echo "done.\n\n";

echo "Creating default tag group...";
$tag_group = App::sql()->insert("INSERT INTO product_tag_group (name) VALUES ('Subcategory');");
echo "done.\n\n";

$manufacturer_cache = [];
$category_cache = [];
$tag_cache = [];
$labour_type_cache = [];

// Remove header row
array_shift($data);

echo "Creating products...";
foreach($data as $row) {
	$row = array_map('trim', $row);

	$manufacturer = $row[0];
	$model = $row[1];
	$category = $row[2];
	$subcategory = $row[3];
	$sku = $row[5];
	$short_description = $row[7];
	$long_description = $row[8];
	$phase = $row[11];
	$labour_hours = $row[12];
	$unit_cost = $row[14];
	$reseller_price = $row[15];
	$height = $row[22];
	$width = $row[23];

	// Some fields CANNOT contain linebreaks
	$manufacturer = trim(str_replace("\n", " ", $manufacturer));
	$model = trim(str_replace("\n", " ", $model));
	$category = trim(str_replace("\n", " ", $category));
	$subcategory = trim(str_replace("\n", " ", $subcategory));
	$sku = trim(str_replace("\n", " ", $sku));
	$short_description = trim(str_replace("\n", " ", $short_description));
	$phase = trim(str_replace("\n", " ", $phase));

	$manufacturer_id = null;
	$category_id = null;
	$labour_type_id = null;
	$tag_id = null;

	if($sku === '') $sku = null;

	if($manufacturer) {
		if(isset($manufacturer_cache[$manufacturer])) {
			$manufacturer_id = $manufacturer_cache[$manufacturer];
		} else {
			$manufacturer_id = App::insert('product_entity', [
				'name' => $manufacturer
			]);
			$manufacturer_cache[$manufacturer] = $manufacturer_id;
		}
	}

	if($category) {
		if(isset($category_cache[$category])) {
			$category_id = $category_cache[$category];
		} else {
			$category_id = App::insert('product_category', [
				'name' => $category
			]);
			$category_cache[$category] = $category_id;
		}
	}

	if($phase) {
		if(isset($labour_type_cache[$phase])) {
			$labour_type_id = $labour_type_cache[$phase];
		} else {
			$labour_type_id = App::insert('product_labour_type', [
				'description' => $phase
			]);
			$labour_type_cache[$phase] = $labour_type_id;
		}
	}

	if($subcategory) {
		if(isset($tag_cache[$subcategory])) {
			$tag_id = $tag_cache[$subcategory];
		} else {
			$tag_id = App::insert('product_tag', [
				'name' => $subcategory,
				'group_id' => $tag_group
			]);
			$tag_cache[$subcategory] = $tag_id;
		}
	}

	$product_id = App::insert('product', [
		'sku' => $sku,
		'manufacturer_id' => $manufacturer_id,
		'model' => $model,
		'category_id' => $category_id,
		'short_description' => $short_description,
		'long_description' => $long_description,
		'unit_id' => $unit_id,
		'height' => $height,
		'width' => $width,
		'sold_to_customer' => '1',
		'sold_to_reseller' => '1'
	]);

	App::sql()->insert(
		"INSERT INTO product_price (product_id, seller_level, seller_id, unit_cost, reseller_price)
		VALUES ('$product_id', 'SP', '1', '$unit_cost', '$reseller_price');
	");

	$labour_hours = $labour_hours ?: 0;
	if($labour_type_id && $labour_hours > 0) {
		App::insert('product_labour', [
			'product_id' => $product_id,
			'labour_type_id' => $labour_type_id,
			'labour_hours' => $labour_hours,
			'seller_level' => 'SP',
			'seller_id' => 1
		]);
	}

	if($tag_id) {
		App::insert('product_tags', [
			'product_id' => $product_id,
			'tag_id' => $tag_id
		]);
	}

	echo ".";
}

echo " done.\n\n";
echo "Import complete.\n\n";
