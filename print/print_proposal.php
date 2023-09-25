<!doctype html>
<html>

<?php

if(!$print_auth) return;

class Render {
	public static $contents = [];
	public static $page_no = 1;

	public static $project = [];
	public static $customer = [];
	public static $user = [];
	public static $si = [];
	public static $proposal = [];

	public static $logo_on_light = '';
	public static $logo_on_dark = '';

	function page_header($title = '') {
		?>
			<header>
				<span class="logo" style="background-image: url('<?= self::$logo_on_light ?>');"></span>
				<h1 class="title"><?= htmlentities($title) ?></h1>
			</header>
		<?php
	}

	function page_footer($show_page_no = true) {
		$footer_text = htmlentities(self::$si['proposal_footer'] ?: '');
		$footer_text = str_replace(' ', '&nbsp;', $footer_text);

		?>
			<footer>
				<?= $footer_text ?>
				<span class="pageno"><?= $show_page_no ? Render::$page_no : '' ?></span>
			</footer>
		<?php
	}

	function render_pages() {
		foreach(Render::$contents as &$page) {
			switch($page['type']) {
				case 'cover':             Render::render_cover($page);             break;
				case 'contacts':          Render::render_contacts($page);          break;
				case 'intro':             Render::render_intro($page);             break;
				case 'benefits':          Render::render_benefits($page);          break;
				case 'module':            Render::render_module($page);            break;
				case 'summary':           Render::render_summary($page);           break;
				case 'payment':           Render::render_payment($page);           break;
				case 'terms':             Render::render_terms($page);             break;
				case 'payback':           Render::render_payback($page);           break;
				case 'accept':            Render::render_accept($page);            break;
			}
		}
		unset($page);
	}

	function render_cover(&$page) {
		$footer_text = htmlentities(self::$si['proposal_cover_footer'] ?: '');
		$footer_text = str_replace(' ', '&nbsp;', $footer_text);

		if(self::$si['id'] === 3) {

			$si = self::$si;

			?>
				<div class="page cover">
					<img src="img/proposal/lcr-cover-image.jpg" style="position: absolute; top: 220px; left: 0; width: 100%;">
					<img src="img/proposal/lcr-cover-frame.svg" style="position: absolute; top: -180px; left: 0; width: 100%;">

					<!-- <p class="text-center pa" style="color: #fff; font-size: 28px; font-style: italic; width: 100%; left: 0; top: 18%;"><?= htmlentities(self::$si['proposal_strapline']) ?></p> -->
					<!-- <p class="pa" style="top: 67%; left: 0; width: 100%; text-align: center; color: #fff; font-weight: bolder; font-size: 42px; line-height: 1.2em;">
						<span style="font-size: 120%;"><?= htmlentities(self::$customer['name']) ?></span><br>
						<?= htmlentities(self::$project['description']) ?>
					</p> -->
					<div style="color: #c4a06d; position: absolute; top: 79%; left: 70%; width: 30%;">
						<h3 style="margin: 0; margin-bottom: 20px; font-weight: normal;">QUOTATION no. <?= self::$project['project_no'] ?></h3>
						<span style="display: inline-block; background: #c4a06d; width: 150px; height: 3px; margin: 0; padding: 0; margin-bottom: 15px;"></span>
						<?php
							if ($si['address_line_1']) echo '<div>'.htmlentities($si['address_line_1']).'</div>';
							if ($si['address_line_2']) echo '<div>'.htmlentities($si['address_line_2']).'</div>';
							if ($si['address_line_3']) echo '<div>'.htmlentities($si['address_line_3']).'</div>';
							if ($si['posttown']) {
								echo '<div>';
								echo htmlentities($si['posttown']);
								if ($si['postcode']) echo ' | '.htmlentities($si['postcode']);
								echo '</div>';
							}
						?>
						<br>
						<div><b>Email:</b> info@lcris.co.uk</div>
						<div><b>Phone:</b> 07920 092 361</div>
						<div><b>Website:</b> www.lcris.co.uk</div>
					</div>
					<div style="color: #c4a06d; position: absolute; top: 79%; left: 4%; width: 50%;">
						<h3 style="margin: 0; margin-bottom: 20px;"><?= htmlentities(self::$customer['name']) ?></h3>
						<span style="display: inline-block; background: #c4a06d; width: 150px; height: 3px; margin: 0; padding: 0; margin-bottom: 15px;"></span>
						<h3 style="margin: 0; margin-bottom: 20px; font-weight: normal;"><?= htmlentities(self::$project['description']) ?></h3>
					</div>
				</div>
			<?php
		} else {
			?>
				<div class="page cover" style="background-image: url('img/proposal/cover-page.svg'); background-repeat: no-repeat; background-size: 100% 100%; background-position: center center;">
					<img src="<?= self::$logo_on_dark ?>" class="pa" style="width: 23%; right: 62px; top: 92px;">
					<p class="text-center pa" style="color: #fff; font-size: 28px; font-style: italic; width: 100%; left: 0; top: 18%;"><?= htmlentities(self::$si['proposal_strapline']) ?></p>
					<img src="img/proposal/cover-devices.png" class="pa" style="width: 63.36%; left: 18.31%; top: 28%;">
					<p class="text-center pa" style="color: #f9c02d; font-size: 41px; font-weight: bold; width: 100%; left: 0; top: 59.15%;">Your Quotation</p>
					<p class="pa" style="top: 67%; left: 0; width: 100%; text-align: center; color: #fff; font-weight: bolder; font-size: 42px; line-height: 1.2em;">
						<span style="font-size: 120%;"><?= htmlentities(self::$customer['name']) ?></span><br>
						<?= htmlentities(self::$project['description']) ?>
					</p>
					<p class="text-center pa" style="color: #f9c02d; font-size: 41px; font-weight: bold; width: 100%; left: 0; bottom: 32px;"><?= $footer_text ?></p>
				</div>
			<?php
		}

		Render::$page_no += 1;
	}

	function compile_address($array, $delimiter = ', ') {
		$addr = [];
		foreach($array as $s) {
			if($s) $addr[] = $s;
		}
		return implode($delimiter, $addr);
	}

	function render_contacts(&$page) {
		$main_name = htmlentities(self::$customer['contact_name']) ?: '';
		$main_email = htmlentities(self::$customer['contact_email']) ?: '';
		$main_phone = htmlentities(self::$customer['contact_mobile']) ?: '';
		$main_address = htmlentities(self::compile_address([
			self::$customer['address_line_1'],
			self::$customer['address_line_2'],
			self::$customer['address_line_3'],
			self::$customer['posttown'],
			self::$customer['postcode']
		])) ?: '';

		$site_name = htmlentities(self::$project['contact_name']) ?: '';
		$site_email = htmlentities(self::$project['contact_email']) ?: '';
		$site_phone = htmlentities(self::$project['contact_mobile']) ?: '';
		$site_address = htmlentities(self::compile_address([
			self::$project['address_line_1'],
			self::$project['address_line_2'],
			self::$project['address_line_3'],
			self::$project['posttown'],
			self::$project['postcode']
		])) ?: '';

		$sales_name = htmlentities(self::$user['name']) ?: '';
		$sales_email = htmlentities(self::$user['email_addr']) ?: '';
		$sales_phone = htmlentities(self::$user['mobile_no']) ?: '';
		$sales_address = htmlentities(self::compile_address([
			self::$si['address_line_1'],
			self::$si['address_line_2'],
			self::$si['address_line_3'],
			self::$si['posttown'],
			self::$si['postcode']
		])) ?: '';

		$has_main = $main_name || $main_email || $main_phone || $main_address;
		$has_site = $site_name || $site_email || $site_phone || $site_address;
		$has_sales = $sales_name || $sales_email || $sales_phone || $sales_address;

		// Don't show site contact if the details are the same as the main contact's
		if($main_name === $site_name && $main_email === $site_email && $main_phone === $site_phone && $main_address === $site_address) $has_site = false;

		?>
			<div class="page contact">
				<?= Render::page_header('Contacts') ?>

				<?php if($has_main) { ?>
					<section class="icon" style="min-height: 200px;">
						<div class="icon" style="background-image: url('img/proposal/icon-main-contact.svg');"></div>
						<div class="content">
							<div class="row">
								<div class="col-sm-12"><h6>Main Contact</h6></div>
							</div>
							<hr>
							<div class="row">
								<div class="col-sm-12">
									<table class="w100 vatb" style="line-height: 2em;">
										<?php if($main_name) { ?><tr><td class="text-m" style="width: 160px;">Name</td><td><?= $main_name ?></td></tr><?php } ?>
										<?php if($main_email) { ?><tr><td class="text-m" style="width: 160px;">Email</td><td><?= $main_email ?></td></tr><?php } ?>
										<?php if($main_phone) { ?><tr><td class="text-m" style="width: 160px;">Telephone</td><td><?= $main_phone ?></td></tr><?php } ?>
										<?php if($main_address) { ?><tr><td class="text-m" style="width: 160px;">Address</td><td><?= $main_address ?></td></tr><?php } ?>
									</table>
								</div>
							</div>
						</div>
					</section>
				<?php } ?>

				<?php if($has_site) { ?>
					<section class="icon" style="margin-top: 80px; min-height: 200px;">
						<div class="icon" style="background-image: url('img/proposal/icon-site-contact.svg');"></div>
						<div class="content">
							<div class="row">
								<div class="col-sm-12"><h6>Site Contact</h6></div>
							</div>
							<hr>
							<div class="row">
								<div class="col-sm-12">
									<table class="w100 vatb" style="line-height: 2em;">
										<?php if($site_name) { ?><tr><td class="text-m" style="width: 160px;">Name</td><td><?= $site_name ?></td></tr><?php } ?>
										<?php if($site_email) { ?><tr><td class="text-m" style="width: 160px;">Email</td><td><?= $site_email ?></td></tr><?php } ?>
										<?php if($site_phone) { ?><tr><td class="text-m" style="width: 160px;">Telephone</td><td><?= $site_phone ?></td></tr><?php } ?>
										<?php if($site_address) { ?><tr><td class="text-m" style="width: 160px;">Address</td><td><?= $site_address ?></td></tr><?php } ?>
									</table>
								</div>
							</div>
						</div>
					</section>
				<?php } ?>

				<?php if($has_sales) { ?>
					<section class="icon" style="margin-top: 80px; min-height: 200px;">
						<div class="icon" style="background-image: url('img/proposal/icon-sales-contact.svg');"></div>
						<div class="content">
							<div class="row">
								<div class="col-sm-12"><h6>Sales Contact</h6></div>
							</div>
							<hr>
							<div class="row">
								<div class="col-sm-12">
									<table class="w100 vatb" style="line-height: 2em;">
										<?php if($sales_name) { ?><tr><td class="text-m" style="width: 160px;">Name</td><td><?= $sales_name ?></td></tr><?php } ?>
										<?php if($sales_email) { ?><tr><td class="text-m" style="width: 160px;">Email</td><td><?= $sales_email ?></td></tr><?php } ?>
										<?php if($sales_phone) { ?><tr><td class="text-m" style="width: 160px;">Telephone</td><td><?= $sales_phone ?></td></tr><?php } ?>
										<?php if($sales_address) { ?><tr><td class="text-m" style="width: 160px;">Address</td><td><?= $sales_address ?></td></tr><?php } ?>
									</table>
								</div>
							</div>
						</div>
					</section>
				<?php } ?>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_intro(&$page) {
		$intro_text = self::$proposal['proposal']['text_introduction'] ?: '';
		$solution_text = self::$proposal['proposal']['text_solution'] ?: '';

		$section_height = '560px';
		if(!$intro_text || !$solution_text) $section_height = '1170px';

		?>
			<div class="page intro">
				<?= Render::page_header() ?>

				<div class="content">
					<?php if($intro_text) { ?>
						<div class="row">
							<div class="col-sm-12">
								<h2>Introduction</h2>
								<div class="custom-text" style="height: <?= $section_height ?>;"><?= $intro_text ?></div>
							</div>
						</div>
					<?php } ?>

					<?php if($solution_text) { ?>
						<div class="row">
							<div class="col-sm-12">
								<h2>Our Solution For You</h2>
								<div class="custom-text" style="height: <?= $section_height ?>;"><?= $solution_text ?></div>
							</div>
						</div>
					<?php } ?>
				</div>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_benefits(&$page) {
		?>
			<div class="page benefits">
				<?= Render::page_header() ?>

				<section class="icon">
					<h6>Other Eticom System Features and Benefits</h6>
					<hr>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-1.svg');"></div>
					<div class="point">
						1. Cloud-based and paperless
					</div>
					<div>
						All of your management information is collected automatically and stored securely in the cloud, available for viewing whenever you want.
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-2.svg');"></div>
					<div class="point">
						2. Easy to use, user-level access
					</div>
					<div>
						Provide your staff with access to what you want them to see and nothing more. Our easy-to-use interface is intuitive, straightforward and accessible from your desktop, tablet or smartphone.
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-3.svg');"></div>
					<div class="point">
						3. Monitor all electricity, gas and water use
					</div>
					<div>
						Our monitoring systems allow you to see the running costs at the level of detail you need for your business. View individual circuits or your entire building - you can view all of this on your easy-to-use dashboard.
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-4.svg');"></div>
					<div class="point">
						4. Automated control
					</div>
					<div>
						Control your lighting, air conditioning, plant and machinery from anywhere in the world! Our system allows you to create scheduled events allowing your building to enter operational modes for reduced power out-of-hours, or simply turn off anything that is left on accidentally.
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-5.svg');"></div>
					<div class="point">
						5. Self-testing of Emergency Lighting
					</div>
					<div>
						Save time and man hours as your system automatically tests the emergency lighting system once a month, and undertakes an annual, full-duration emergency lighting test to ensure that all systems are working fully, generating reports and alerting you instantly to any problems. It will generate an annual End of Year report which is fully compliant to BS 5266 and acts as your Emergency Lighting End of Year Testing Certificate.
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-6.svg');"></div>
					<div class="point">
						6. Retro-fit installations
					</div>
					<div>
						All our products are designed as open protocol, ensuring they are as widely compatible as possible. Not only does this mean we can offer fast and trouble-free installations, but it increases longevity and the effective life-span of the system.
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-7.svg');"></div>
					<div class="point">
						7. Convenience
					</div>
					<div>
						Our system can fully integrate with and provide a central ‘nerve system’ or access point for all existing control and monitoring systems of industrial machinery and processes, lighting, compressed-air, alarms, cooling and heating systems.
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-8.svg');"></div>
					<div class="point">
						8. Drive down costs
					</div>
					<div>
						Implementation of our systems and solutions will enable real, measurable reductions in energy and water use of between 5% to 30%
					</div>
				</section>

				<section class="icon">
					<div class="icon" style="background-image: url('img/proposal/icon-benefits-9.svg');"></div>
					<div class="point">
						9. Reduce impact on the environment
					</div>
					<div>
						Real, measurable improvements in energy and water use will help to you to manage sustainability impacts going forward. This will not only benefit your organisation but will provide wider-reaching benefits to society as a whole.
					</div>
				</section>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_module(&$page) {
		$module_id = $page['id'];
		$module = App::select('project_module', $module_id);

		$show_qty = self::$proposal['proposal']['show_quantities'] ?: 0;
		$show_subtotal = self::$proposal['proposal']['show_subtotals'] ?: 0;

		$col_title_width = '150px';
		$col_qty_width = $show_subtotal ? '100px' : '150px';
		$col_subtotal_width = '150px';

		$project_id = self::$project['id'];

		$proposal = [];
		foreach(self::$proposal['modules'] as $m) {
			if($m['id'] == $module_id) $proposal = $m;
		}

		$hardware = App::sql()->query(
			"SELECT
				COALESCE(p.short_description, pl.description) AS description,
				SUM((pl.unit_price + COALESCE(lab.labour_price, 0)) * pl.quantity) AS subtotal,
				SUM(IF(pl.parent_id IS NULL, pl.quantity, 0)) AS quantity
			FROM project_line AS pl
			JOIN project_line AS ppl ON ppl.id = COALESCE(pl.parent_id, pl.id)
			JOIN project_system AS s ON s.id = ppl.system_id
			LEFT JOIN product AS p ON p.id = ppl.product_id

			LEFT JOIN (
				SELECT
					subpl.id,
					SUM(sublab.labour_hours * sublab.hourly_price) AS labour_price
				FROM project_line AS subpl
				JOIN project_labour AS sublab ON subpl.id = sublab.line_id
				WHERE subpl.project_id = '$project_id'
				GROUP BY subpl.id
			) AS lab ON lab.id = pl.id

			WHERE pl.project_id = '$project_id' AND s.module_id = '$module_id'
			GROUP BY COALESCE(p.short_description, pl.description)
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY description;
		", MySQL::QUERY_ASSOC) ?: [];

		$hardware_count = count($hardware);
		$hardware_total = 0;
		foreach($hardware as $l) {
			$hardware_total += $l['subtotal'];
		}

		$software = App::sql()->query(
			"SELECT
				pst.id,
				pst.description,
				ps.frequency,
				SUM(pl.quantity * ps.quantity * ps.unit_price) AS subtotal,
				SUM(pl.quantity * ps.quantity) AS quantity
			FROM project_line AS pl
			JOIN project_line AS ppl ON ppl.id = COALESCE(pl.parent_id, pl.id)
			JOIN project_system AS s ON s.id = ppl.system_id
			JOIN project_subscription AS ps ON ps.line_id = pl.id
			JOIN product_subscription_type AS pst ON pst.id = ps.subscription_type_id
			WHERE pl.project_id = '$project_id' AND s.module_id = '$module_id'
			GROUP BY pst.id, pst.description, ps.frequency
			HAVING subtotal > 0 AND quantity > 0
			ORDER BY s.description;
		", MySQL::QUERY_ASSOC) ?: [];

		$software_count = count($software);
		$software_totals = [
			'monthly' => 0,
			'annual' => 0
		];

		foreach($software as $l) {
			$software_totals[$l['frequency']] += $l['subtotal'];
		}

		foreach($software_totals as $type => $total) {
			if(!$total) unset($software_totals[$type]);
		}

		$hs_title = [];
		$hs_table_title = [];

		if($hardware) {
			$hs_title[] = 'HARDWARE';
			$hs_table_title[] = 'Hardware';
		}
		if($software) {
			$hs_table_title[] = 'Software Subscription';
		}
		$hs_title[] = 'SOFTWARE';

		$hs_title = implode(' &amp; ', $hs_title);
		$hs_table_title = implode(' and ', $hs_table_title);

		if($module['description'] === 'Smooth Power') $hs_title = 'PROTECT, MONITOR, SAVE';

		$item_header = 1;
		$row_no = 0;

		?>
			<div class="page module module-<?= $module_id ?>">
				<?= Render::page_header($module['description']) ?>

				<div class="top-section">
					<section class="icon">
						<div class="icon" style="font-size: 38px;">
							<span class="eticon-stack text-center">
								<i class="eticon eticon-circle eticon-stack-2x" style="color: <?= $module['colour'] ?>;"></i>
								<i class="<?= $module['icon'] ?> eticon-stack-1x eticon-inverse" style="color: rgba(0, 0, 0, 0.25); position: relative; top: 3px; left: 4px;"></i>
							</span>
						</div>
						<div class="icon" style="font-size: 38px;">
							<span class="eticon-stack text-center">
								<i class="eticon eticon-circle eticon-stack-2x" style="color: rgba(0, 0, 0, 0);"></i>
								<i class="<?= $module['icon'] ?> eticon-stack-1x eticon-inverse"></i>
							</span>
						</div>
						<h6><?= $hs_title ?></h6>
						<hr style="margin-bottom: 20px;">
						<?= $module['proposal_text'] ?>
					</section>
					<?= $module['proposal_content'] ?>
				</div>

				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<table class="mtable">
								<tr>
									<td colspan="<?= 2 + $show_qty + $show_subtotal ?>" class="mbg mht">
										<h4 style="margin: 0; padding: 5px; vertical-align: bottom;">
											<span class="eticon-stack text-center" style="font-size: 60%;">
												<i class="eticon eticon-circle eticon-stack-2x" style="color: #fff;"></i>
												<i class="<?= $module['icon'] ?> eticon-stack-2x eticon-inverse mc" style="font-size: 1.4em; left: 1px; top: 4px;"></i>
											</span>
											&nbsp;<?= $module['description'] ?> <?= $hs_table_title ?>
										</h4>
									</td>
								</tr>

								<?php if($proposal['text_features']) { ?>
									<tr>
										<td class="mbg-35 mht text-m" style="width: <?= $col_title_width ?>;"><b>Features</b></td>
										<td class="features-text" colspan="<?= 1 + $show_qty + $show_subtotal ?>"><?= $proposal['text_features'] ?: '' ?></td>
									</tr>
								<?php } ?>

								<?php
									if($hardware) {
										$row_count = $hardware_count + $item_header;
										if($item_header) {
											array_unshift($hardware, 'header');
											$item_header = 0;
										}

										foreach($hardware as $i => $row) {
								?>
									<tr>
										<?php if($i === 0) { ?><td rowspan="<?= $row_count ?>" class="mbg mht<?= $hardware && $software ? ' bbl' : '' ?> text-m" style="width: <?= $col_title_width ?>;"><b>Hardware</b></td><?php } ?>
										<?php if($row === 'header') { ?>
											<td class="mbg-50 mht text-center"><b>Item Description</b></td>
											<?php if($show_qty) { ?><td class="mbg-50 mht text-center" style="width: <?= $col_qty_width ?>;"><b>QTY</b></td><?php } ?>
											<?php if($show_subtotal) { ?><td class="mbg-50 mht text-center" style="width: <?= $col_subtotal_width ?>;"><b>Sub Total</b></td><?php } ?>
										<?php
											} else {
												$row_no++;
												$row_class = $row_no % 2 ? 'mbg-10' : '';
										?>
											<td class="<?= $row_class ?>"><?= $row['description'] ?></td>
											<?php if($show_qty) { ?><td class="text-center <?= $row_class ?>" style="width: <?= $col_qty_width ?>;"><?= $row['quantity'] ?></td><?php } ?>
											<?php if($show_subtotal) { ?><td class="text-right <?= $row_class ?>" style="width: <?= $col_subtotal_width ?>;"><?= App::format_number_sep($row['subtotal'], 2, 2) ?></td><?php } ?>
										<?php } ?>
									</tr>
								<?php
										}
									}
								?>

								<?php
									if($software) {
										$row_count = $software_count + $item_header;
										if($item_header) {
											array_unshift($software, 'header');
											$item_header = 0;
										}

										foreach($software as $i => $row) {
								?>
									<tr>
										<?php if($i === 0) { ?><td rowspan="<?= $row_count ?>" class="mbg mht<?= $hardware && $software ? ' btl' : '' ?> text-m" style="width: <?= $col_title_width ?>;"><b>Software Subscription</b></td><?php } ?>
										<?php if($row === 'header') { ?>
											<td class="mbg-50 mht text-center"><b>Item Description</b></td>
											<?php if($show_qty) { ?><td class="mbg-50 mht text-center" style="width: <?= $col_qty_width ?>;"><b>QTY</b></td><?php } ?>
											<?php if($show_subtotal) { ?><td class="mbg-50 mht text-center" style="width: <?= $col_subtotal_width ?>;"><b>Sub Total</b></td><?php } ?>
										<?php
											} else {
												$row_no++;
												$row_class = $row_no % 2 ? 'mbg-10' : '';
										?>
											<td class="<?= $row_class ?>"><?= $row['description'] ?></td>
											<?php if($show_qty) { ?><td class="text-center <?= $row_class ?>" style="width: <?= $col_qty_width ?>;"><?= $row['quantity'] ?></td><?php } ?>
											<?php if($show_subtotal) { ?><td class="text-right <?= $row_class ?>" style="width: <?= $col_subtotal_width ?>;"><?= App::format_number_sep($row['subtotal'], 2, 2) ?></td><?php } ?>
										<?php } ?>
									</tr>
								<?php
										}
									}
								?>
							</table>
						</div>
					</div>
					<div class="row" style="margin-top: 20px;">
						<div class="col-sm-12">
							<table class="mtable text-m" style="width: 43%; margin: 0 0 0 auto;">
								<?php if($hardware) { ?>
									<tr>
										<td class="mbg mht text-center bbl"><b>Hardware total</b></td>
										<td class="text-center" style="width: <?= $col_subtotal_width ?>; padding-top: 20px; padding-bottom: 20px;"><b>&pound;<?= App::format_number_sep($hardware_total, 2, 2) ?></b></td>
									</tr>
								<?php } ?>

								<?php
									if($software) {
										foreach($software_totals as $type => $total) {
								?>
									<tr>
										<td class="mbg mht text-center btl"><b>Software subscription <?= $type ?> total</b></td>
										<td class="text-center" style="width: <?= $col_subtotal_width ?>; padding-top: 20px; padding-bottom: 20px;"><b>&pound;<?= App::format_number_sep($total, 2, 2) ?></b></td>
									</tr>
								<?php
										}
									}
								?>
							</table>
						</div>
					</div>
				</div>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_summary(&$page) {
		$project_id = self::$project['id'];
		$vat_rate = self::$project['vat_rate'] ?: 0;

		$col_subtotal_width = '180px';
		$col_qty_width = '100px';

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
			<div class="page summary">
				<?= Render::page_header('Summary') ?>

				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<h2>Summary of Systems</h2>

							<?php if(self::$proposal['proposal']['text_summary']) { ?>
								<div class="custom-text" style="margin-bottom: -30px;"><?= self::$proposal['proposal']['text_summary'] ?: '' ?></div>
							<?php } ?>

							<?php if($hardware) { ?>
								<div class="table-container">
									<div style="margin-top: 30px;">
										<table class="hardware text-m" style="width: 100%;">
											<thead>
												<tr>
													<th>HARDWARE</th>
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
									<div style="margin-top: 30px;">
										<table class="hardware-total text-m" style="width: 43%; margin: 0 0 0 auto;">
											<tr>
												<th class="text-right"><b>Hardware Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($hardware_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($hardware_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right"><b>Total Cost</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($hardware_total, 2, 2) ?></b></td>
											</tr>
										</table>
									</div>
								</div>
							<?php } ?>

							<?php if($software_monthly) { ?>
								<div class="table-container">
									<div style="margin-top: 30px;">
										<table class="software text-m" style="width: 100%;">
											<thead>
												<tr>
													<th>MONTHLY SOFTWARE SUBSCRIPTION</th>
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
									<div style="margin-top: 30px;">
										<table class="software-total text-m" style="width: 43%; margin: 0 0 0 auto;">
											<tr>
												<th class="text-right"><b>Monthly Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_monthly_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($software_monthly_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right"><b>Total Monthly Cost</b></th>
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
											<thead>
												<tr>
													<th>ANNUAL SOFTWARE SUBSCRIPTION</th>
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
												<th class="text-right"><b>Annual Net Total</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_annual_subtotal, 2, 2) ?></b></td>
											</tr>
											<tr>
												<th class="text-right"><b>VAT</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;">&pound;<?= App::format_number_sep($software_annual_vat, 2, 2) ?></td>
											</tr>
											<tr>
												<th class="text-right"><b>Total Annual Cost</b></th>
												<td class="total text-right" style="width: <?= $col_subtotal_width ?>;"><b>&pound;<?= App::format_number_sep($software_annual_total, 2, 2) ?></b></td>
											</tr>
										</table>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_payment(&$page) {
		?>
			<div class="page payment">
				<?= Render::page_header() ?>

				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<h2>Payment Options</h2>
							<div class="custom-text" style="height: 1170px;"><?= self::$proposal['proposal']['text_payment'] ?: '' ?></div>
						</div>
					</div>
				</div>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_terms(&$page) {
		?>
			<div class="page terms">
				<?= Render::page_header() ?>

				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<h2>Terms and Conditions</h2>
							<div class="custom-text" style="height: 1170px;"><?= self::$proposal['proposal']['text_terms'] ?: '' ?></div>
						</div>
					</div>
				</div>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_payback(&$page) {
		?>
			<div class="page payback">
				<?= Render::page_header('Payback') ?>

				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<h2>Effects of Installing Eticom Cloud Building Management System</h2>
							<div class="custom-text" style="height: 1170px;"><?= self::$proposal['proposal']['text_payback'] ?: '' ?></div>
						</div>
					</div>
				</div>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

	function render_accept(&$page) {
		$payments = self::$proposal['proposal']['preferred_payment'] ?: '';
		$payments = explode(',', $payments);
		$payments = array_filter($payments, function($p) { return $p !== ''; });

		?>
			<div class="page accept">
				<?= Render::page_header() ?>

				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<h2>Acceptance</h2>
							<div class="custom-text" style="height: 1170px;">
								<table style="width: 100%;">
									<tr>
										<td colspan="2" style="border-top: none;">
											We accept the proposal for the Eticom Cloud Smart Building Management System.<br>
											Please arrange for the installation of the system as soon as possible.
										</td>
									</tr>
									<tr><td colspan="2">Order number</td></tr>
									<?php if(count($payments)) { ?>
										<tr>
											<td style="width: 195px; border-right: none;">Preferred payment</td>
											<td style="padding: 0; border-left: none;">
												<?php foreach($payments as $p) { ?>
													<span class="paybox">
														<span class="pay"><?= htmlentities(trim($p)) ?></span>
														<span class="box"></span>
													</span>
												<?php } ?>
											</td>
										</tr>
									<?php } ?>
									<tr><td colspan="2">Signed</td></tr>
									<tr><td colspan="2">Name</td></tr>
									<tr><td colspan="2">Position</td></tr>
								</table>
							</div>
						</div>
					</div>
				</div>

				<?= Render::page_footer() ?>
			</div>
		<?php

		Render::$page_no += 1;
	}

}

//
// Main processing loop
//

MySQL::$clean = false;

$project_id = App::get('project_id');
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

$modules = App::sql()->query(
	"SELECT DISTINCT
		pm.id, pm.colour, pm.text_colour
	FROM project_line AS pl
	JOIN project_system AS ps ON ps.id = pl.system_id
	JOIN project_module AS pm ON pm.id = ps.module_id
	WHERE pl.project_id = '$project_id' AND pl.parent_id IS NULL
	ORDER BY pm.display_order;
", MySQL::QUERY_ASSOC) ?: [];

?>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/eticom-icons.css">
	<link rel="stylesheet" href="css/eticon.css?rev=1.0.11">
	<link rel="stylesheet" href="css/styles.css">

	<link rel="stylesheet" href="print_proposal.css">

	<title>EticomCloud proposal</title>

	<?php
		echo '<style>';

		foreach($modules as $m) {
			$mid = $m['id'];
			$c = $m['colour'];
			$tc = $m['text_colour'];
			if($c[0] !== '#' || strlen($c) !== 7) $c = '#666666';

			$r = hexdec(substr($c, 1, 2)) ?: 0;
			$g = hexdec(substr($c, 3, 2)) ?: 0;
			$b = hexdec(substr($c, 5, 2)) ?: 0;

			$module_class = "module-$mid";

			echo ".$module_class .mbg { background: rgba($r, $g, $b, 1); }";
			echo ".$module_class .mbg-50 { background: rgba($r, $g, $b, 0.5); }";
			echo ".$module_class .mbg-35 { background: rgba($r, $g, $b, 0.35); }";
			echo ".$module_class .mbg-10 { background: rgba($r, $g, $b, 0.1); }";
			echo ".$module_class .mc { color: rgba($r, $g, $b, 1); }";
			echo ".$module_class .mht { color: $tc; }";
			echo ".$module_class .mtable td { border: 1px solid rgba($r, $g, $b, 1); }";
			echo ".$module_class .mtable td.btl { border-top: 1px solid rgba(255, 255, 255, 0.35); }";
			echo ".$module_class .mtable td.bbl { border-bottom: 1px solid rgba(255, 255, 255, 0.35); }";
		}

		if ($si_id != 1) {
			// Override primary colours
			$colour = '#666';
			$colour_light = '#999';

			?>
				.intro h2 { background: <?=$colour ?>; }
				.summary h2 { background: <?=$colour ?>; }
				.payment h2 { background: <?=$colour ?>; }
				.terms h2 { background: <?=$colour ?>; }

				.summary table.hardware td { border-color: <?=$colour ?>; }
				.summary table.hardware th { background: <?=$colour ?>; }

				.summary table.hardware-total td { border-color: <?=$colour ?>; }
				.summary table.hardware-total th { background: <?=$colour ?>; }

				.summary table.software td { border-color: <?=$colour_light ?>; }
				.summary table.software th { background: <?=$colour_light ?>; }

				.summary table.software-total td { border-color: <?=$colour_light ?>; }
				.summary table.software-total th { background: <?=$colour_light ?>; }
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

		Render::$contents[] = [ 'type' => 'cover' ];
		Render::$contents[] = [ 'type' => 'contacts' ];
		if(Render::$proposal['proposal']['text_introduction'] || Render::$proposal['proposal']['text_solution']) Render::$contents[] = [ 'type' => 'intro' ];
		if(Render::$si['id'] !== 3) Render::$contents[] = [ 'type' => 'benefits' ];

		foreach($modules as $m) {
			Render::$contents[] = [ 'type' => 'module', 'id' => $m['id'] ];
		}

		Render::$contents[] = [ 'type' => 'summary' ];
		if(Render::$proposal['proposal']['text_payment']) Render::$contents[] = [ 'type' => 'payment' ];
		if(Render::$proposal['proposal']['text_terms']) Render::$contents[] = [ 'type' => 'terms' ];
		if(Render::$proposal['proposal']['text_payback']) Render::$contents[] = [ 'type' => 'payback' ];
		if(Render::$si['id'] !== 3) if(Render::$proposal['proposal']['show_acceptance']) Render::$contents[] = [ 'type' => 'accept' ];

		Render::render_pages();

	?>

</body>
</html>
