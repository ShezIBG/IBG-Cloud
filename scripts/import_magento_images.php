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

echo "Creating upload folder... ";
$sub_dir = date('/Y/m/d');
$dir = USER_CONTENT_PATH.$sub_dir;
if(!file_exists($dir)) {
	if(!mkdir($dir, 0777, true)) {
		echo "FAILED\n\n";
		exit;
	}
}
echo "done.\n\n";

echo "IMPORTING DATA\n\n";

$owner_level = 'SI';
$owner_id = '3';

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

	$model = $row[4];
	$sku = $row[1];
	$short_description = $row[5];
	$image_path = $row[11];

	// Some fields CANNOT contain linebreaks
	$model = trim(str_replace("\n", " ", $model));
	$sku = trim(str_replace("\n", " ", $sku));
	$image_path = trim(str_replace("\n", " ", $image_path));

	// Remove null values
	if($model === 'NULL') $model = '';
	if($sku === 'NULL') $sku = '';
	if($short_description === 'NULL') $short_description = '';
	if($image_path === 'NULL') $image_path = '';

	if(!$image_path || $image_path === 'no_selection') {
		echo 'x';
		continue;
	}

	$q = [];

	if($model) {
		$q[] = 'model = \''.App::escape($model).'\'';
	} else {
		$q[] = '(model = \'\' OR model IS NULL)';
	}

	if($sku) {
		$q[] = 'sku = \''.App::escape($sku).'\'';
	} else {
		$q[] = '(sku = \'\' OR sku IS NULL)';
	}

	if($short_description) {
		$short_description = substr($short_description, 0, 100);
		$q[] = 'short_description = \''.App::escape($short_description).'\'';
	} else {
		$q[] = '(short_description = \'\' OR short_description IS NULL)';
	}

	if(count($q) === 0) {
		echo "\nNo identifiable information. ";
		continue;
	}

	$q = 'SELECT id FROM product WHERE '.implode(' AND ', $q).' AND image_id IS NULL;';
	$result = App::sql()->query($q);

	if(!$result) {
		echo "\nProduct not found: $short_description ";
		continue;
	}

	$product_id = $result[0]->id;
	if(!$product_id) {
		echo "\nCannot extract product id. ";
		continue;
	}

	$image_path = "/tmp/products$image_path";

	if(!file_exists($image_path)) {
		echo "\nFile not found: $image_path ";
		continue;
	}

	// Copy file to user content folder
	$ext = explode('.', $image_path);
	$ext = '.'.$ext[count($ext) - 1];
	$filename = App::new_uid(false, 32);
	$fullpath = $sub_dir.'/'.$filename.$ext;
	$abspath = USER_CONTENT_PATH.$fullpath;
	copy($image_path, $abspath);

	// Register new image
	$new_id = App::sql()->insert("INSERT INTO user_content (user_id, path, used, datetime) VALUES ('0', '$fullpath', 0, NOW());");
	$uc = new UserContent($new_id);

	App::update('product', $product_id, [
		'image_id' => $new_id
	]);

	$uc->add_usage();
	$uc->shrink_image(512, 512);

	echo ".";
}

echo " done.\n\n";
echo "Import complete.\n\n";
