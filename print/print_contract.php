<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/eticom-icons.css">
	<link rel="stylesheet" href="css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" href="css/styles.css">

	<link rel="stylesheet" href="print_contract.css">

	<title>Invoice</title>

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">

</head>
<body>

<?php
	if(!$print_auth) return;

	MySQL::$clean = false;

	$id = App::get('id', 0, true);
	$token = App::get('token', '', true);
	$contract = App::get('contract', 0, true);

	if(!$id) return;

	$pa = App::sql()->query_row("SELECT * FROM payment_account WHERE id = '$id' AND security_token = '$token';", MySQL::QUERY_ASSOC);
	if(!$pa) return;

	$contract = App::select('contract', $contract);
	if(!$contract) return;

	if($contract['customer_type'] !== $pa['customer_type'] || $contract['customer_id'] !== $pa['customer_id']) return;

	$pdf_id = $contract['pdf_contract_id'];
	if(!$pdf_id) return;

	$pdf = App::select('pdf_contract_template', $pdf_id);
	if(!$pdf) return;

	$html = $pdf['html'];

	$cu = Customer::resolve_details($contract['customer_type'], $contract['customer_id']);

	$cu_addr = [];
	if($cu['address_line_1']) $cu_addr[] = $cu['address_line_1'];
	if($cu['address_line_2']) $cu_addr[] = $cu['address_line_2'];
	if($cu['address_line_3']) $cu_addr[] = $cu['address_line_3'];
	if($cu['posttown']) $cu_addr[] = $cu['posttown'];
	if($cu['postcode']) $cu_addr[] = $cu['postcode'];
	$cu_addr = implode(', ', $cu_addr);

	$signed_date = 'DATE';
	if($contract['pdf_contract_signed_datetime']) $signed_date = date('d/m/Y', strtotime($contract['pdf_contract_signed_datetime']));

	$area_addr = [];
	$area = App::select('area', $contract['area_id']);
	if($area) {
		$floor = App::select('floor', $area['floor_id']);
		if($floor) {
			$building = App::select('building', $floor['building_id']);
			if($building) {
				$new_address = [
					'address_line_1' => $area['description'],
					'address_line_2' => $building['address'],
					'address_line_3' => '',
					'posttown' => $building['posttown'],
					'postcode' => $area['postcode'] ? $area['postcode'] : $building['postcode'] // Postcode only override (if any)
				];
				if($area['address_line_1'] || $area['address_line_2'] || $area['address_line_3'] || $area['posttown']) {
					// Full address override
					$new_address = [
						'address_line_1' => $area['address_line_1'],
						'address_line_2' => $area['address_line_2'],
						'address_line_3' => $area['address_line_3'],
						'posttown' => $area['posttown'],
						'postcode' => $area['postcode']
					];
				}
				if($new_address['address_line_1']) $area_addr[] = $new_address['address_line_1'];
				if($new_address['address_line_2']) $area_addr[] = $new_address['address_line_2'];
				if($new_address['address_line_3']) $area_addr[] = $new_address['address_line_3'];
				if($new_address['posttown']) $area_addr[] = $new_address['posttown'];
				if($new_address['postcode']) $area_addr[] = $new_address['postcode'];
			}
		}
	}
	$area_addr = implode(', ', $area_addr);

	$html = str_replace('{AREA_ADDRESS}', htmlentities($area_addr), $html);
	$html = str_replace('{CUSTOMER_ADDRESS}', htmlentities($cu_addr), $html);
	$html = str_replace('{SIGNATURE}', '<span class="signature">'.htmlentities($contract['pdf_contract_signature'] ?: 'Customer Signature').'</span>', $html);
	$html = str_replace('{SIGNED_DATE}', htmlentities($signed_date), $html);
	$html = str_replace('{SIGNED_NAME}', htmlentities($contract['pdf_contract_signature'] ?: 'Customer Name'), $html);

?>

	<?= $html ?>

</body>
</html>
