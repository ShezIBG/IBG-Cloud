<!doctype html>
<html>

<?php

if(!$print_auth) return;

class Render {
	public static $contents = [];

	public static $project = [];
	public static $customer = [];
	public static $user = [];
	public static $si = [];
	public static $proposal = [];

	public static $items = [];

	public static $logo_on_light = '';
	public static $logo_on_dark = '';

	public static $hide_labour = 0;

	function quote_header() {

		$sales_name = htmlentities(self::$user['name']) ?: '';
		$sales_email = htmlentities(self::$user['email_addr']) ?: '';
		$sales_phone = htmlentities(self::$user['mobile_no']) ?: '';

		$expiry_date = self::$project['expiry_date'];
		if($expiry_date) $expiry_date = date('d/m/Y', strtotime($expiry_date));

		$quote_date = self::$project['quote_date'];
		if($quote_date) $quote_date = date('d/m/Y', strtotime($quote_date));

		?>
			<div class="content" style="margin-bottom: 20px;">
				<hr>
				<div class="row">
					<div class="col-sm-6">
						<table>
							<tbody>
								<?php if($sales_name) { ?>
									<tr>
										<td style="padding-left: 0;"><b>Quoted by</b></td>
										<td><?= $sales_name ?></td>
									</tr>
								<?php } ?>
								<?php if($sales_email) { ?>
									<tr>
										<td style="padding-left: 0;"><b>Email address</b></td>
										<td><?= $sales_email ?></td>
									</tr>
								<?php } ?>
								<?php if($sales_phone) { ?>
									<tr>
										<td style="padding-left: 0;"><b>Phone</b></td>
										<td><?= $sales_phone ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="col-sm-6">
						<table>
							<tbody>
								<?php if($quote_date) { ?>
									<tr>
										<td style="padding-left: 0;"><b>Quote date</b></td>
										<td><?= $quote_date ?></td>
									</tr>
								<?php } ?>
								<?php if($expiry_date) { ?>
									<tr>
										<td style="padding-left: 0;"><b>Expiry date</b></td>
										<td><?= $expiry_date ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php
	}

	function render_pages() {
		foreach(Render::$contents as &$page) {
			switch($page['type']) {
				case 'summary_simple':    Render::render_summary_simple($page);    break;
				case 'summary_itemised':  Render::render_summary_itemised($page);  break;
				case 'subscriptions':     Render::render_subscriptions($page);     break;
				case 'payment':           Render::render_payment($page);           break;
				case 'terms':             Render::render_terms($page);             break;
			}
		}
		unset($page);
	}

	function compile_address($array, $delimiter = ', ') {
		$addr = [];
		foreach($array as $s) {
			if($s) $addr[] = $s;
		}
		return implode($delimiter, $addr);
	}

	function render_summary_simple(&$page) {
		$project_id = self::$project['id'];
		$vat_rate = self::$project['vat_rate'] ?: 0;

		$col_subtotal_width = '180px';
		$col_qty_width = '100px';

		if(self::$hide_labour) {
			$hardware = App::sql()->query(
				"SELECT
					m.id,
					m.description,
					SUM(pl.unit_price * pl.quantity) AS subtotal,
					SUM(pl.quantity) AS quantity
				FROM project_line AS pl
				JOIN project_line AS ppl ON ppl.id = COALESCE(pl.parent_id, pl.id)
				JOIN project_system AS s ON s.id = ppl.system_id
				JOIN project_module AS m ON m.id = s.module_id
				WHERE pl.project_id = '$project_id'
				GROUP BY m.id, m.description
				HAVING subtotal > 0 AND quantity > 0
				ORDER BY m.display_order
			", MySQL::QUERY_ASSOC) ?: [];
		} else {
			$hardware = App::sql()->query(
				"SELECT
					m.id,
					m.description,
					SUM((pl.unit_price + COALESCE(lab.labour_price, 0)) * pl.quantity) AS subtotal,
					SUM(pl.quantity) AS quantity
				FROM project_line AS pl
				JOIN project_line AS ppl ON ppl.id = COALESCE(pl.parent_id, pl.id)
				JOIN project_system AS s ON s.id = ppl.system_id
				JOIN project_module AS m ON m.id = s.module_id
				LEFT JOIN (
					SELECT
						subpl.id, SUM(sublab.labour_hours * sublab.hourly_price) AS labour_price
					FROM project_labour AS sublab
					JOIN project_line AS subpl ON subpl.id = sublab.line_id AND subpl.project_id = '$project_id'
					GROUP BY subpl.id
				) AS lab ON lab.id = pl.id
				WHERE pl.project_id = '$project_id'
				GROUP BY m.id, m.description
				HAVING subtotal > 0 AND quantity > 0
				ORDER BY m.display_order
			", MySQL::QUERY_ASSOC) ?: [];
		}

		$hardware_subtotal = 0;
		foreach($hardware as $l) {
			$hardware_subtotal += $l['subtotal'];
		}

		$hardware_vat = ($hardware_subtotal / 100) * $vat_rate;
		$hardware_total = $hardware_subtotal + $hardware_vat;

		$software_monthly = App::sql()->query(
			"SELECT
				pst.id,
				pst.description,
				SUM(pl.quantity * ps.quantity * ps.unit_price) AS subtotal,
				SUM(pl.quantity * ps.quantity) AS quantity
			FROM project_line AS pl
			JOIN project_subscription AS ps ON ps.line_id = pl.id
			JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
			WHERE pl.project_id = '$project_id' AND ps.frequency = 'monthly'
			GROUP BY pst.id, pst.description
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY pst.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$software_monthly_subtotal = 0;
		foreach($software_monthly as $l) {
			$software_monthly_subtotal += $l['subtotal'];
		}

		$software_monthly_vat = ($software_monthly_subtotal / 100) * $vat_rate;
		$software_monthly_total = $software_monthly_subtotal + $software_monthly_vat;

		$software_annual = App::sql()->query(
			"SELECT
				pst.id,
				pst.description,
				SUM(pl.quantity * ps.quantity * ps.unit_price) AS subtotal,
				SUM(pl.quantity * ps.quantity) AS quantity
			FROM project_line AS pl
			JOIN project_subscription AS ps ON ps.line_id = pl.id
			JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
			WHERE pl.project_id = '$project_id' AND ps.frequency = 'annual'
			GROUP BY pst.id, pst.description
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY pst.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$software_annual_subtotal = 0;
		foreach($software_annual as $l) {
			$software_annual_subtotal += $l['subtotal'];
		}

		$software_annual_vat = ($software_annual_subtotal / 100) * $vat_rate;
		$software_annual_total = $software_annual_subtotal + $software_annual_vat;

		?>
			<div class="page">
				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<table class="hardware" style="width: 100%">
								<thead class="text-m">
									<tr>
										<th>Summary</th>
									</tr>
								</thead>
							</table>

							<?php if(self::$proposal['proposal']['text_quotation']) { ?>
								<div class="custom-text" style="margin-bottom: -30px;"><?= self::$proposal['proposal']['text_quotation'] ?: '' ?></div>
							<?php } ?>

							<?php if($hardware) { ?>
								<div class="table-container print-group">
									<div style="margin-top: 30px;">
										<table class="hardware" style="width: 100%;">
											<thead class="text-m">
												<tr>
													<th>Hardware</th>
													<th class="text-right">Sub Total</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach($hardware as $row) { ?>
													<tr>
														<td><?= $row['description'] ?></td>
														<td class="text-right shrink" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($row['subtotal'], 2, 2) ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
									<div class="print-group" style="margin-top: 30px;">
										<table class="hardware-total" style="width: 43%; margin: 0 0 0 auto;">
											<tr>
												<th class="text-right text-m"><b>Hardware Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($hardware_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($hardware_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>Total Cost</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($hardware_total, 2, 2) ?></b></td>
											</tr>
										</table>
									</div>
								</div>
							<?php } ?>

							<?php if($software_monthly) { ?>
								<div class="table-container print-group">
									<div style="margin-top: 30px;">
										<table class="software" style="width: 100%;">
											<thead class="text-m">
												<tr>
													<th>Monthly Software Subscription</th>
													<th class="text-center">QTY</th>
													<th class="text-right">Sub Total</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach($software_monthly as $row) { ?>
													<tr>
														<td><?= $row['description'] ?></td>
														<td class="text-center shrink" style="width: <?= $col_qty_width ?>;"><?= App::format_number_sep($row['quantity'], 0, 2) ?></td>
														<td class="text-right shrink" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($row['subtotal'], 2, 2) ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
									<div class="print-group" style="margin-top: 30px;">
										<table class="software-total" style="width: 43%; margin: 0 0 0 auto;">
											<tr>
												<th class="text-right text-m"><b>Monthly Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_monthly_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($software_monthly_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>Total Monthly Cost</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_monthly_total, 2, 2) ?></b></td>
											</tr>
										</table>
									</div>
								</div>
							<?php } ?>

							<?php if($software_annual) { ?>
								<div class="table-container">
									<div style="margin-top: 30px;">
										<table class="software text-m" style="width: 100%;">
											<thead class="text-m">
												<tr>
													<th>Annual Software Subscription</th>
													<th class="text-center">QTY</th>
													<th class="text-right">Sub Total</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach($software_annual as $row) { ?>
													<tr>
														<td><?= $row['description'] ?></td>
														<td class="text-center shrink" style="width: <?= $col_qty_width ?>;"><?= App::format_number_sep($row['quantity'], 0, 2) ?></td>
														<td class="text-right shrink" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($row['subtotal'], 2, 2) ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
									<div style="margin-top: 20px;">
										<table class="software-total text-m" style="width: 43%; margin: 0 0 0 auto;">
											<tr>
												<th class="text-right text-m"><b>Annual Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_annual_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($software_annual_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>Total Annual Cost</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_annual_total, 2, 2) ?></b></td>
											</tr>
										</table>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	function render_summary_itemised(&$page) {
		$project_id = self::$project['id'];
		$vat_rate = self::$project['vat_rate'] ?: 0;

		$col_subtotal_width = '180px';
		$col_qty_width = '100px';

		if(self::$hide_labour) {
			// Without labour
			$hardware = App::sql()->query(
				"SELECT
					CONCAT(floor.description, ': ', area.description) AS area_description,
					p.id, p.model, p.sku, p.image_id,
					COALESCE(p.short_description, ln.description) AS description,
					p.long_description,
					ln.unit_price,
					SUM(ln.quantity) AS quantity,
					SUM(ln.quantity * ln.unit_price) AS total,
					uc.path AS image_url,
					pm.name AS manufacturer_name
				FROM project_line AS ln
				JOIN project_structure AS area ON area.id = ln.structure_id
				JOIN project_structure AS floor ON floor.id = area.parent_id
				LEFT JOIN product AS p ON p.id = ln.product_id
				LEFT JOIN user_content AS uc ON uc.id = p.image_id
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				LEFT JOIN project_line AS pln ON pln.id = ln.parent_id
				WHERE ln.project_id = '$project_id' AND (ln.parent_id IS NULL OR pln.show_accessories = 1)
				GROUP BY CONCAT(floor.description, ': ', area.description), p.id, p.model, p.sku, p.image_id, COALESCE(p.short_description, ln.description), p.long_description, ln.unit_price, uc.path, pm.name
				ORDER BY floor.id, area.id, manufacturer_name, description;
			", MySQL::QUERY_ASSOC) ?: [];
		} else {
			// With labour
			$hardware = App::sql()->query(
				"SELECT
					CONCAT(floor.description, ': ', area.description) AS area_description,
					p.id, p.model, p.sku, p.image_id,
					COALESCE(p.short_description, ln.description) AS description,
					p.long_description,
					AVG(ln.unit_price + COALESCE(lab.labour_price, 0)) AS unit_price,
					SUM(ln.quantity) AS quantity,
					SUM(ln.quantity * (ln.unit_price + COALESCE(lab.labour_price, 0))) AS total,
					uc.path AS image_url,
					pm.name AS manufacturer_name
				FROM project_line AS ln
				JOIN project_structure AS area ON area.id = ln.structure_id
				JOIN project_structure AS floor ON floor.id = area.parent_id
				LEFT JOIN product AS p ON p.id = ln.product_id
				LEFT JOIN user_content AS uc ON uc.id = p.image_id
				LEFT JOIN product_entity AS pm ON pm.id = p.manufacturer_id
				LEFT JOIN (
					SELECT
						subpl.id, SUM(sublab.labour_hours * sublab.hourly_price) AS labour_price
					FROM project_labour AS sublab
					JOIN project_line AS subpl ON subpl.id = sublab.line_id AND subpl.project_id = '$project_id'
					GROUP BY subpl.id
				) AS lab ON lab.id = ln.id
				LEFT JOIN project_line AS pln ON pln.id = ln.parent_id
				WHERE ln.project_id = '$project_id' AND (ln.parent_id IS NULL OR pln.show_accessories = 1)
				GROUP BY CONCAT(floor.description, ': ', area.description), p.id, p.model, p.sku, p.image_id, COALESCE(p.short_description, ln.description), p.long_description, uc.path, pm.name
				ORDER BY floor.id, area.id, manufacturer_name, description;
			", MySQL::QUERY_ASSOC) ?: [];
		}

		foreach($hardware as &$item) {
			if($item['image_url']) {
				$item['image_url'] = UserContent::url_by_path($item['image_url']);
			}
		}
		unset($item);

		$hardware_subtotal = 0;
		foreach($hardware as $l) {
			$hardware_subtotal += $l['total'];
		}

		$hardware_vat = ($hardware_subtotal / 100) * $vat_rate;
		$hardware_total = $hardware_subtotal + $hardware_vat;

		?>
			<div class="page">
				<?= Render::quote_header() ?>

				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<!-- <table class="hardware" style="width: 100%">
								<thead class="text-m">
									<tr>
										<th>Scope of Works</th>
									</tr>
								</thead>
							</table> -->

							<?php if(self::$proposal['proposal']['text_quotation']) { ?>
								<!-- <div class="custom-text" style="margin-bottom: -30px;"><?= self::$proposal['proposal']['text_quotation'] ?: '' ?></div> -->
							<?php } ?>

							<?php
								$area_description = null;

								foreach($hardware as $row) {
									$first = $area_description === null;
									$new_area = $area_description !== $row['area_description'];
									if($new_area) $area_description = $row['area_description'];
							?>

							<?php if($new_area && !$first) { ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php } ?>

							<?php if($new_area) { ?>
								<div class="table-container" style="margin: 0;">
									<div style="margin-top: 30px;">
										<table class="hardware vat noborder spaced" style="width: 100%;">
											<thead class="text-m">
												<tr>
													<th colspan="3"><?= $area_description ?></th>
												</tr>
											</thead>
											<tbody>
							<?php } ?>

												<tr>
													<td style="width: 100px;">
															<div class="product-image-container">
																<?php if($row['image_url']) { ?>
																	<img *ngIf="p.image_url" class="product-image" src="<?= $row['image_url'] ?>">
																<?php } ?>
															</div>
													</td>
													<td class="text-center" style="width: 80px;"><?= $row['quantity'] ?></td>
													<td>
														<?php if($row['id']) { ?>
															<div><b><?= htmlentities($row['manufacturer_name']) ?></b></div>
															<div style="white-space: pre-line;"><?= htmlentities($row['long_description']) ?></div>
														<?php } else { ?>
															<div><b><?= htmlentities($row['description']) ?></b></div>
														<?php } ?>
													</td>
												</tr>

							<?php
								}
							?>

											<!-- Close last area tags after iteration -->
											</tbody>
										</table>
									</div>
								</div>

						</div>
					</div>
				</div>
			</div>
		<?php
	}

	function render_subscriptions(&$page) {
		$project_id = self::$project['id'];
		$vat_rate = self::$project['vat_rate'] ?: 0;

		$col_subtotal_width = '180px';
		$col_qty_width = '100px';

		$software_monthly = App::sql()->query(
			"SELECT
				pst.id,
				pst.description,
				SUM(pl.quantity * ps.quantity * ps.unit_price) AS subtotal,
				SUM(pl.quantity * ps.quantity) AS quantity
			FROM project_line AS pl
			JOIN project_subscription AS ps ON ps.line_id = pl.id
			JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
			WHERE pl.project_id = '$project_id' AND ps.frequency = 'monthly'
			GROUP BY pst.id, pst.description
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY pst.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$software_monthly_subtotal = 0;
		foreach($software_monthly as $l) {
			$software_monthly_subtotal += $l['subtotal'];
		}

		$software_monthly_vat = ($software_monthly_subtotal / 100) * $vat_rate;
		$software_monthly_total = $software_monthly_subtotal + $software_monthly_vat;

		$software_annual = App::sql()->query(
			"SELECT
				pst.id,
				pst.description,
				SUM(pl.quantity * ps.quantity * ps.unit_price) AS subtotal,
				SUM(pl.quantity * ps.quantity) AS quantity
			FROM project_line AS pl
			JOIN project_subscription AS ps ON ps.line_id = pl.id
			JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
			WHERE pl.project_id = '$project_id' AND ps.frequency = 'annual'
			GROUP BY pst.id, pst.description
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY pst.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$software_annual_subtotal = 0;
		foreach($software_annual as $l) {
			$software_annual_subtotal += $l['subtotal'];
		}

		$software_annual_vat = ($software_annual_subtotal / 100) * $vat_rate;
		$software_annual_total = $software_annual_subtotal + $software_annual_vat;

		if(!$software_monthly && !$software_annual) return;

		?>
			<div class="page">
				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<table class="hardware" style="width: 100%">
								<thead class="text-m">
									<tr>
										<th>
											Subscriptions
										</th>
									</tr>
								</thead>
							</table>

							<?php if(self::$proposal['proposal']['text_subscriptions']) { ?>
								<div class="custom-text" style="margin-bottom: -30px;"><?= self::$proposal['proposal']['text_subscriptions'] ?: '' ?></div>
							<?php } ?>

							<?php if($software_monthly) { ?>
								<div class="table-container print-group">
									<div style="margin-top: 30px;">
										<table class="software" style="width: 100%;">
											<thead class="text-m">
												<tr>
													<th>Monthly Software Subscription</th>
													<th class="text-center">QTY</th>
													<th class="text-right">Sub Total</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach($software_monthly as $row) { ?>
													<tr>
														<td><?= $row['description'] ?></td>
														<td class="text-center shrink" style="width: <?= $col_qty_width ?>;"><?= App::format_number_sep($row['quantity'], 0, 2) ?></td>
														<td class="text-right shrink" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($row['subtotal'], 2, 2) ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
									<div class="print-group" style="margin-top: 30px;">
										<table class="software-total" style="width: 43%; margin: 0 0 0 auto;">
											<tr>
												<th class="text-right text-m"><b>Monthly Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_monthly_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($software_monthly_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>Total Monthly Cost</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_monthly_total, 2, 2) ?></b></td>
											</tr>
										</table>
									</div>
								</div>
							<?php } ?>

							<?php if($software_annual) { ?>
								<div class="table-container print-group">
									<div style="margin-top: 30px;">
										<table class="software" style="width: 100%;">
											<thead class="text-m">
												<tr>
													<th>Annual Software Subscription</th>
													<th class="text-center">QTY</th>
													<th class="text-right">Sub Total</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach($software_annual as $row) { ?>
													<tr>
														<td><?= $row['description'] ?></td>
														<td class="text-center shrink" style="width: <?= $col_qty_width ?>;"><?= App::format_number_sep($row['quantity'], 0, 2) ?></td>
														<td class="text-right shrink" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($row['subtotal'], 2, 2) ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
									<div class="print-group" style="margin-top: 20px;">
										<table class="software-total" style="width: 43%; margin: 0 0 0 auto;">
											<tr>
												<th class="text-right text-m"><b>Annual Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_annual_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($software_annual_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right text-m"><b>Total Annual Cost</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_annual_total, 2, 2) ?></b></td>
											</tr>
										</table>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	function render_payment(&$page) {
		?>
			<div class="page">
				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<table class="hardware" style="width: 100%">
								<thead class="text-m">
									<tr>
										<th>
											Payment Options
										</th>
									</tr>
								</thead>
							</table>
							<div class="custom-text"><?= self::$proposal['proposal']['text_payment'] ?: '' ?></div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	function render_terms(&$page) {
		?>
			<div class="page">
				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<table class="hardware" style="width: 100%">
								<thead class="text-m">
									<tr>
										<th>
											Terms and Conditions
										</th>
									</tr>
								</thead>
							</table>
							<div class="custom-text"><?= self::$proposal['proposal']['text_terms'] ?: '' ?></div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

}

//
// Main processing loop
//

MySQL::$clean = false;

$project_id = App::get('project_id');
$variant = App::get('variant');
Render::$hide_labour = App::get('hide_labour', 0);
Render::$project = App::select('project', $project_id) ?: [];

$customer_id = Render::$project['customer_id'];
Render::$customer = App::select('sales_customer', $customer_id) ?: [];

$user_id = Render::$project['user_id'];
Render::$user = App::select('userdb', $user_id);

$si_id = Render::$project['system_integrator_id'];
Render::$si = App::select('system_integrator', $si_id);

if(Render::$si['logo_on_light_id']) {
	$uc = new UserContent(Render::$si['logo_on_light_id']);
	Render::$logo_on_light = $uc->get_url();
}
if(Render::$si['logo_on_dark_id']) {
	$uc = new UserContent(Render::$si['logo_on_dark_id']);
	Render::$logo_on_dark = $uc->get_url();
}
if(Render::$logo_on_light && !Render::$logo_on_dark) Render::$logo_on_dark = Render::$logo_on_light;
if(Render::$logo_on_dark && !Render::$logo_on_light) Render::$logo_on_light = Render::$logo_on_dark;

$p = new Project($project_id);
Render::$proposal = $p->get_proposal();

?>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/eticom-icons.css">
	<link rel="stylesheet" href="css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" href="css/styles.css">

	<link rel="stylesheet" href="print_area_summary.css">

	<title>EticomCloud quotation</title>

	<?php
		echo '<style>';

		if ($si_id != 1) {
			// Override primary colours
			$colour = '#666';
			$colour_light = '#999';

			?>
				table.hardware td, table.hardware th { border-color: <?=$colour ?>; }
				table.hardware th { background: <?=$colour ?>; }

				table.hardware-total td, table.hardware-total th { border-color: <?=$colour ?>; }
				table.hardware-total th { background: <?=$colour ?>; }

				table.software td, table.software th { border-color: <?=$colour_light ?>; }
				table.software th { background: <?=$colour_light ?>; }

				table.software-total td, table.software-total th { border-color: <?=$colour_light ?>; }
				table.software-total th { background: <?=$colour_light ?>; }
			<?php
		}

		echo '</style>';
	?>
</head>
<body>

	<?php

		//
		// Rendering loop
		//

		Render::$contents[] = [ 'type' => 'summary_itemised' ];
		Render::$contents[] = [ 'type' => 'summary_simple' ];
		// Render::$contents[] = [ 'type' => 'subscriptions' ];
		if(Render::$proposal['proposal']['text_payment']) Render::$contents[] = [ 'type' => 'payment' ];
		if(Render::$proposal['proposal']['text_terms']) Render::$contents[] = [ 'type' => 'terms' ];

		// if(Render::$proposal['proposal']['text_payment']) Render::$contents[] = [ 'type' => 'payment' ];
		// if(Render::$proposal['proposal']['text_terms']) Render::$contents[] = [ 'type' => 'terms' ];

		Render::render_pages();

	?>

</body>
</html>
