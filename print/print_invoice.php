<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/eticom-icons.css">
	<link rel="stylesheet" href="css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" href="css/styles.css">

	<link rel="stylesheet" href="print_invoice.css">

	<title>Invoice</title>
</head>
<body>

<?php
	if(!$print_auth) return;

	MySQL::$clean = false;

	$id = App::get('invoice_id', 0, true);
	$invoice = App::select('invoice', $id);
	if(!$invoice) return;

	$owner_info = Customer::resolve_details($invoice['owner_type'], $invoice['owner_id']);
	if(!$owner_info) return;

	$lines = App::sql()->query("SELECT * FROM invoice_line WHERE invoice_id = '$id';", MySQL::QUERY_ASSOC) ?: [];

	$customer_address = [];
	if($invoice['customer_address_line_1']) $customer_address[] = $invoice['customer_address_line_1'];
	if($invoice['customer_address_line_2']) $customer_address[] = $invoice['customer_address_line_2'];
	if($invoice['customer_address_line_3']) $customer_address[] = $invoice['customer_address_line_3'];
	if($invoice['customer_posttown']) $customer_address[] = $invoice['customer_posttown'];
	if($invoice['customer_postcode']) $customer_address[] = $invoice['customer_postcode'];

	$owner_address = [];
	if($invoice['owner_address_line_1']) $owner_address[] = $invoice['owner_address_line_1'];
	if($invoice['owner_address_line_2']) $owner_address[] = $invoice['owner_address_line_2'];
	if($invoice['owner_address_line_3']) $owner_address[] = $invoice['owner_address_line_3'];
	if($invoice['owner_posttown']) $owner_address[] = $invoice['owner_posttown'];
	if($invoice['owner_postcode']) $owner_address[] = $invoice['owner_postcode'];

	$logo_url = '';
	if($owner_info['logo_on_light_id']) {
		$uc = new UserContent($owner_info['logo_on_light_id']);
		if($uc->info) $logo_url = $uc->get_url();
	}
	if($invoice['invoice_entity_id']) {
		$ie = App::select('invoice_entity', $invoice['invoice_entity_id']);
		if($ie && $ie['image_id']) {
			$uc = new UserContent($ie['image_id']);
			if($uc->info) $logo_url = $uc->get_url();
		}
	}

	if($invoice['bank_sort_code']) {
		$invoice['bank_sort_code'] = implode('-', str_split($invoice['bank_sort_code'], 2));
	}
?>

	<div id="print" class="content" style="padding: 32px;">

		<div class="row">
			<div class="col-sm-3" style="background-image: url('<?=$logo_url?>'); background-size: contain; background-repeat: no-repeat; background-position: left center; margin-left: 15px; margin-right: -15px;">
			</div>
			<div class="col-sm-9 text-right">
				<div><b><?=htmlentities($invoice['owner_name'])?></b></div>
				<?php foreach($owner_address as $line) { ?>
					<div><?=htmlentities($line)?></div>
				<?php } ?>
				<?php if($invoice['vat_reg_number']) { ?>
					<div>&nbsp;</div>
					<div>VAT reg: <?=htmlentities($invoice['vat_reg_number'])?></div>
				<?php } ?>
			</div>
		</div>

		<div class="shaded-3" style="height: 3px; margin: 30px 0 40px 0;"></div>

		<h3 style="margin-top: 0; margin-bottom: 32px;"><?=htmlentities($invoice['description'])?></h3>
		<div><?=htmlentities($invoice['customer_name'])?></div>
		<div>&nbsp;</div>
		<?php foreach($customer_address as $line) { ?>
			<div><?=htmlentities($line)?></div>
		<?php } ?>

		<div class="row" style="margin-top: 40px;">
			<div class="col-sm-12">
				<div class="large"><b>Date of issue: <?=date('d/m/Y', strtotime($invoice['bill_date']))?></b></div>
				<div>Period from <?=date('d/m/Y', strtotime($invoice['period_start_date']))?> to <?=date('d/m/Y', strtotime($invoice['period_end_date']))?></div>
				<div>&nbsp;</div>
				<?php if($invoice['customer_ref']) { ?>
					<div>Customer Reference: <?=htmlentities($invoice['customer_ref'])?></div>
				<?php } ?>
				<div>Invoice Number: <?=htmlentities($invoice['invoice_no'])?></div>
			</div>
		</div>
		<br>

		<table id="tbl" class="table no-border" style="margin-top: 32px; margin-bottom: 32px;">
			<thead>
				<tr>
					<th class="shaded-3 large">Items</th>
					<th class="shaded-3 large text-right">Unit price</th>
					<th class="shaded-3 large text-right">Quantity</th>
					<th class="shaded-3 large text-right">Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($lines as $line) { ?>
					<tr class="large">
						<td><?=htmlentities($line['description'])?></td>
						<td class="text-right">&pound;<?=App::format_number_sep($line['unit_price'], 2, 4)?></td>
						<td class="text-right"><?=App::format_number_sep($line['quantity'], 0, 2)?></td>
						<td class="text-right">&pound;<?=App::format_number_sep($line['line_total'], 2, 2)?></td>
					</tr>
				<?php } ?>
				<tr class="large">
					<td colspan="4">&nbsp;</td>
				</tr>
				<tr class="large">
					<td colspan="2"></td>
					<td class="shaded-1">Subtotal</td>
					<td class="shaded-1 text-right">&pound;<?=App::format_number_sep($invoice['subtotal'], 2, 2)?></td>
				</tr>
				<tr class="large">
					<td colspan="2"></td>
					<td class="shaded-2">VAT (<?=App::format_number_sep($invoice['vat_rate'], 0, 2)?>%)</td>
					<td class="shaded-2 text-right">&pound;<?=App::format_number_sep($invoice['vat_due'], 2, 2)?></td>
				</tr>
				<tr class="large total">
					<td colspan="2"></td>
					<td class="shaded-3 info">Total now due</td>
					<td class="shaded-3 text-right info">&pound;<?=App::format_number_sep($invoice['bill_total'], 2, 2)?></td>
				</tr>
			</tbody>
		</table>

		<div class="print-footer" style="padding: 0 0 0 50px;">
			<table class="table no-border">
				<td style="vertical-align: bottom;">
					<div><b>Payment is due by <?=date('d/m/Y', strtotime($invoice['due_date']))?></b></div>
					<?php if($invoice['bank_name'] && $invoice['bank_sort_code'] && $invoice['bank_account_number']) { ?>
						<div>
							<br>Payment by BACS to <?=htmlentities($invoice['owner_name'])?><br>
							<?=htmlentities($invoice['bank_name'])?><br>
							Sort Code: <?=htmlentities($invoice['bank_sort_code'])?><br>
							Account: <?=htmlentities($invoice['bank_account_number'])?>
						</div>
					<?php } ?>
				</td>
				<td class="text-right" style="vertical-align: bottom;">
					<img src="<?=$logo_url?>" style="width: 150px;">
				</td>
			</table>
		</div>
	</div>

</body>
</html>
