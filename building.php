<?php
	require_once 'inc/init.user.php';
	if(!Module::is_enabled(Module::BUILDING)) {
		$user->launch_home_page();
		exit;
	}

	$main_page = App::MAIN_PAGE_TENANTS;
	require_once 'inc/config.ui.php';

	$page_body_prop = [ 'class' => 'fixed-header fixed-navigation fixed-ribbon minified' ];
	$page_css = [ 'screen-building.css?rev=8' ];
	include 'inc/header.php';

	$building = $user->get_default_building(Permission::BUILDING_ENABLED, 'building.is_tenanted = 1');
	$list = Permission::list_buildings([ 'with' => Permission::BUILDING_ENABLED ], 'building.is_tenanted = 1');

	if($building) {
		$total_buildings = count($list ?: []);
		$area_list = $building->get_tenanted_areas(
			[ 'building_id' => '= '.$building->id ],
			[ 'tenant.id as tenant_id', 'area.description as unit_name', 'floor.description as floor_name', 'tenant.name as tenant_name', 'area.id as area_id' ],
			[ 'INNER JOIN floor ON floor.id = area.floor_id', 'LEFT JOIN tenant ON tenant.id = tenant_id' ],
			'ORDER BY floor.display_order, area.display_order'
		);
		$occupied_list = $building->get_tenanted_areas(
			[ 'building_id' => '= '.$building->id, 'tenant_id' => 'IS NOT NULL' ],
			[],
			[ 'INNER JOIN floor ON floor.id = area.floor_id' ]
		);

		$total_units = $area_list ? count($area_list) : 0;
		$occupied_units = $occupied_list ? count($occupied_list) : 0;
	}

	echo '<div class="myMainContainerLeft" id="main" role="main">';

	if($building) {
?>

	<div class="container-fluid container-fluid-building">
		<div class="row layout-row">
			<div id="tenants-sidebar" class="layout-col col-sm-2">
				<header>Building Manager</header>
				<section>
					<div id="total-buildings">
						<strong>Total Buildings</strong> <span class="pull-right"><strong><?= $total_buildings; ?></strong></span>
					</div>
					<div class="select2-color-grey">
						<select class="select2" id="building" style="width:100%;">
							<?php
								foreach($list as $b) {
									if($b->id == $building->id) {
										echo "<option value=\"$b->id\" selected>$b->description</option>";
									} else {
										echo "<option value=\"$b->id\">$b->description</option>";
									}
								}
							?>
						</select>
					</div>
				</section>
				<!-- jglassell add menu styling -->
				<!-- <i class="fa fa-chevron-left"> -->
				<!-- <i class="fa-solid fa-sterling-sign"></i> -->
				<ul id="menu" class="list menu-buildings">
					<li><a class="btn-buildings" id="menu-summary" data-menu="" data-view="building_summary?building_id=<?= $building->id ?>" href="#"><i class="eticon eticon-info"></i>&nbsp;&nbsp;Summary <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>

<?php if(Permission::get_eticom()->check(Permission::ADMIN)) { ?>
					<li><a class="btn-buildings" id="menu-units" data-menu="unit" href="#"><i class="eticon eticon-area"></i>&nbsp;&nbsp;Units <strong class="field-total-units pull-right"><?= $total_units; ?></strong></a></li>
					<li><a class="btn-buildings" id="menu-tenants" data-menu="tenant" href="#"><i class="eticon eticon-tenants"></i>&nbsp;&nbsp;Tenants <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<li><a class="btn-buildings" id="menu-agents" data-menu="agent" href="#"><i class="eticon eticon-agent"></i>&nbsp;&nbsp;Agents <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<!-- <li><a class="btn-buildings" id="menu-tenantbills" href="#" data-view="building_tenantbills?building_id=<?= $building->id; ?>"><i class="eticon eticon-circle-pound"></i>&nbsp;&nbsp;Tenant Bills <i class="pull-right eticon eticon-arrow-right"></i></a></li> -->
					<li><a class="btn-buildings" id="menu-tenantbills" href="#" data-view="building_tenantbills?building_id=<?= $building->id; ?>"><i class="eticon eticon-circle-pound"></i>&nbsp;&nbsp;Tenant Bills <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>

					

					<li><a class="btn-buildings" id="menu-revenue" href="#" data-view="building_revenue?building_id=<?= $building->id; ?>"><i class="eticon eticon-circle-pound"></i>&nbsp;&nbsp;Revenue &amp; Costs <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<li><a class="btn-buildings" id="menu-supply" data-menu="" href="#" data-view="building_supply?building_id=<?= $building->id; ?>"><i class="eticon eticon-bolt"></i>&nbsp;&nbsp;Landlord's Supply <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<li><a class="btn-buildings" id="menu-tasks" data-menu="" href="#" data-view="building_tasks?building_id=<?= $building->id; ?>"><i class="eticon eticon-clipboard"></i>&nbsp;&nbsp;Task Manager <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<li><a class="btn-buildings" id="menu-enquiries" data-menu="" href="#" data-view="building_enquiries?building_id=<?= $building->id; ?>"><i class="eticon eticon-circle-user-add"></i>&nbsp;&nbsp;New Enquiries <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<li><a class="btn-buildings" id="menu-contacts" data-menu="" href="#" data-view="building_contacts?building_id=<?= $building->id; ?>"><i class="eticon eticon-contact"></i>&nbsp;&nbsp;Contacts <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
<?php } else { ?>
					<li><a class="btn-buildings" id="menu-units" data-menu="unit" href="#"><i class="eticon eticon-tenants"></i>&nbsp;&nbsp;Units <strong class="field-total-units pull-right"><?= $total_units; ?></strong></a></li>
					<li><a class="btn-buildings" id="menu-agents" data-menu="agent" href="#"><i class="eticon eticon-agent"></i>&nbsp;&nbsp;Agents <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<li><a class="btn-buildings" id="menu-tenantbills" href="#" data-view="building_tenantbills?building_id=<?= $building->id; ?>"><i class="eticon eticon-circle-pound"></i>&nbsp;&nbsp;Tenant Bills <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<!-- <li><a id="menu-tasks" data-menu="" href="#" data-view="building_tasks?building_id=<?= $building->id; ?>"><i class="eticon eticon-circle-clipboard"></i>&nbsp;&nbsp;Task Manager <i class="pull-right eticon eticon-arrow-right"></i></a></li> -->
					<li><a class="btn-buildings" id="menu-supply" data-menu="" href="#" data-view="building_supply?building_id=<?= $building->id; ?>"><i class="eticon eticon-bolt"></i>&nbsp;&nbsp;Landlord's Supply <!--<i class="pull-right eticon eticon-arrow-right"></i>--></a></li>
					<!-- <li><a id="menu-revenue" href="#" data-view="building_revenue?building_id=<?= $building->id; ?>"><i class="eticon eticon-circle-pound"></i>&nbsp;&nbsp;Revenue &amp; Costs <i class="pull-right eticon eticon-arrow-right"></i></a></li> -->
					
<?php } ?>

				</ul>
				<ul class="myMenu-ul4 myMenuList">
				<a href="https://ibg-uk.cloud/eticom/dashboard#view/dashboard/meters"><li>Something1</li></a>
				</ul>
			</div>

			<div id="list" class="layout-col col-sm-2" style="display:none">
				<header></header>
				<?php
					$lists = [
						'unit' => $building->html_building_manager_unit_list(),
						'agent' => $building->html_building_manager_agent_list($building->id),
						'tenant' => $building->html_building_manager_tenant_list()
					];

					foreach($lists as $list_name => $list_content) {
				?>
					<div id="<?= $list_name; ?>-list-search" class="list-search">
						<input id="<?= $list_name; ?>-search" type="text"><i class="txt-color-blueDark eticon eticon-lg eticon-search"></i>
					</div>
					<ul id="<?= $list_name; ?>-list" class="list">
						<?= $list_content; ?>
					</ul>
					<footer id="<?= $list_name; ?>-list-footer">
						<table>
							<tr>
								<td><a href="#" class="prev">Previous</a></td>
								<td>Page <b class="page">16</b> of <b class="pages">52</b></td>
								<td class="right"><a href="#" class="next">Next</a></td>
							</tr>
						</table>
					</footer>
				<?php
					}
				?>
			</div>

			<div id="main-content" class="layout-col col-sm-10">
				<header id="view-title" data-default="<?= $building->info->description; ?>"><i class="eticon eticon-building-2"></i>&nbsp;&nbsp;&nbsp;Portfolio Summary</header>
				<div id="view"></div>
			</div>
		</div>
	</div>
	<div class="footer"></div>

<?php
	} else {
		echo '<div>';
		$ui->print_alert('You don\'t have any buildings.', 'warning');
		echo '</div>';
	}

	echo '</div>';

	include 'inc/scripts.php';

	// Have to call it after jQuery is included
	$module = Module::get_module(Module::BUILDING);
	if(!$module->init()) return;

	include 'inc/google-analytics.php';
?>

<script>
	// Unit list

	var $unitList = $('#unit-list');
	var $unitListFooter = $('#unit-list-footer');
	var $unitListSearch = $('#unit-search');
	var $tenantList = $('#tenant-list');
	var $tenantListFooter = $('#tenant-list-footer');
	var $tenantListSearch = $('#tenant-search');
	var $agentList = $('#agent-list');
	var $agentListFooter = $('#agent-list-footer');
	var $agentListSearch = $('#agent-search');

	function resizeScrolltables() {
		$('.scrolltable').each(function() {
			var $s = $(this);
			var i = 0;
			$s.find('.body table td').each(function() {
				$s.find('.header table th').eq(i).innerWidth($(this).innerWidth());
				i++;
			});
			$s.find('.header table th:last-child').innerWidth('auto');
		});

		$('.dataTable.resizeme').each(function() {
			var $dtWrapper = $(this).closest('div.dataTables_wrapper');
			var padding = $(this).data('padding-bottom') || 0;
			var $scrollBody = $dtWrapper.find('div.dataTables_scrollBody');
			var $footer = $dtWrapper.find('div.dt-toolbar-footer');
			var height = $('#view').innerHeight() - $scrollBody.position().top - $footer.outerHeight() - padding;
			$scrollBody.css('height', height+'px');

			var dt = $(this).data('dt');
			if(dt) {
				dt.api().columns.adjust();
			}
		});
	}

	// Side menu
	function selectMenuItem($e) {
		$('#menu .active').removeClass('active');
		if($e) $e.addClass('active');

		// Show the list that belongs to the menu item
		var listName = $e ? $e.data('menu') : '';

		$('#list > ul, #list > footer, #list > div').hide();
		if(listName) {
			$('#' + listName + '-list, #' + listName + '-list-footer, #' + listName + '-list-search').show();
			$('#' + listName + '-list').trigger('dynamicList:refresh');
		}

		$('#main-content').removeClass('col-sm-8 col-sm-10');
		switch(listName) {
			case 'unit':
				$('#list > header').html('<i class="eticon eticon-area"></i>&nbsp;&nbsp;Your Units');
				$('#list').show();
				$('#main-content').addClass('col-sm-8');
				break;

			case 'tenant':
				$('#list > header').html('<i class="eticon eticon-tenants"></i>&nbsp;&nbsp;Your Tenants');
				$('#list').show();
				$('#main-content').addClass('col-sm-8');
				break;

			case 'agent':
				$('#list > header').html('<i class="eticon eticon-agent"></i>&nbsp;&nbsp;Your Agents');
				$('#list').show();
				$('#main-content').addClass('col-sm-8');
				break;

			default:
				$('#list > header').html('');
				$('#list').hide();
				$('#main-content').addClass('col-sm-10');
				break;
		}
	}

	$(function() {
		// Building drop-down

		$('#building').initSelect2().on('change', function(e) {
			var $this = $(this);
			$.post('<?= APP_URL ?>/ajax/post/set_default_building', {
				building_id: $this.val()
			}, function(data) {
				$.ajaxResult(data, function() {
					window.location.reload();
				});
			})
		});

		// Main menu

		$('#menu').on('click', 'a', function(e) {
			e.preventDefault();
			selectMenuItem($(this));
		});

		$('#menu-units').on('click', function(e) {
			e.preventDefault();
			$unitList.find('li a').eq(0).click();
		});

		$('#menu-tenants').on('click', function(e) {
			e.preventDefault();
			$tenantList.find('li a').eq(0).click();
		});

		$('#menu-agents').on('click', function(e) {
			e.preventDefault();
			$agentList.find('li a').eq(0).click();
		});

		$('#menu-summary, #menu-tenantbills, #menu-revenue, #menu-tasks, #menu-supply, #menu-enquiries, #menu-contacts').on('click', function(e) {
			e.preventDefault();

			// Reset form title to building name
			var $title = $('#view-title');
			$title.html($title.data('default'));

			LoadTenantView($(this).data('view'));
		});

		// Unit and agent lists
		createDynamicList($unitList, $unitListFooter, $unitListSearch);
		createDynamicList($tenantList, $tenantListFooter, $tenantListSearch);
		createDynamicList($agentList, $agentListFooter, $agentListSearch);

		$unitList.on('click', 'li a', function(e) {
			e.preventDefault();
			var $this = $(this);

			// select first sidemenu item
			selectMenuItem($('#menu-units'));

			// Update selection
			$unitList.find('.active').removeClass('active');
			$this.addClass('active');

			// Update view title
			var $title = $('#view-title');
			$title.html($this.data('title') || $title.data('default'));

			// Load view
			LoadTenantView($this.data('view'));
		});

		$tenantList.on('click', 'li a', function(e) {
			e.preventDefault();
			var $this = $(this);

			// select first sidemenu item
			selectMenuItem($('#menu-tenants'));

			// Update selection
			$tenantList.find('.active').removeClass('active');
			$this.addClass('active');

			// Update view title
			var $title = $('#view-title');
			$title.html($this.data('title') || $title.data('default'));

			// Load view
			LoadTenantView($this.data('view'));
		});

		$agentList.on('click', 'li a', function(e) {
			e.preventDefault();
			var $this = $(this);

			// select first sidemenu item
			selectMenuItem($('#menu-agents'));

			// Update selection
			$agentList.find('.active').removeClass('active');
			$this.addClass('active');

			// Update view title
			var $title = $('#view-title');
			$title.html($this.data('title') || $title.data('default'));

			// Load view
			LoadTenantView($this.data('view'));
		});

		// Scrolling tables
		$(window).on('resize', resizeScrolltables);

		// Load building summary
		selectMenuItem($('#menu-summary'));
		LoadTenantView('building_summary?building_id=<?= $building->id; ?>');
	});

	var lastTenantView = '';

	var defaultTabs = {
		building_supply: 'building_supply_electricity'
	};

	var viewData = {
		building_unit: {
			tab: ''
		}
	}

	function LoadTenantView(view) {
		var baseURL = '<?= APP_URL.'/view/partials/'; ?>';

		if(view) {
			lastTenantView = view;

			// Split view into view name and query string
			var chunks = ('' + view).split('?');
			var viewName = chunks[0];
			var viewQuery = chunks.length > 1 ? chunks[1] : '';

			// Resolve default tabs (will be deprecated soon)
			if(defaultTabs[viewName]) {
				viewName = defaultTabs[viewName];
			} else {
				$.each(defaultTabs, function(k, v) {
					if(viewName.startsWith(k)) {
						defaultTabs[k] = viewName;
						return false;
					}
				});
			}

			switch(viewName) {
				case 'building_unit':
					viewQuery += '&tab=' + viewData.building_unit.tab;
					break;
			}

			viewURL = baseURL + viewName + '.php' + (viewQuery ? '?' + viewQuery : '');
			$('#view').load(viewURL);
		}
	}

	function refreshTenantView() {
		// Reload unit list
		$.getJSON('<?= APP_URL.'/ajax/get/html_building_manager_unit_list?building_id='.$building->id; ?>', function(result) {
			if(result.data) {
				var view = $unitList.find('.active').data('view');
				$unitList.empty().html(result.data).find('a[data-view="' + view + '"]').addClass('active');
				$unitList.trigger('dynamicList:refresh');
			}
		});

		// Reload tenant list
		$.getJSON('<?= APP_URL.'/ajax/get/html_building_manager_tenant_list?building_id='.$building->id; ?>', function(result) {
			if(result.data) {
				var view = $tenantList.find('.active').data('view');
				$tenantList.empty().html(result.data).find('a[data-view="' + view + '"]').addClass('active');
				$tenantList.trigger('dynamicList:refresh');
			}
		});

		// Reload agent list
		$.getJSON('<?= APP_URL.'/ajax/get/html_building_manager_agent_list?building_id='.$building->id; ?>', function(result) {
			if(result.data) {
				var view = $agentList.find('.active').data('view');
				$agentList.empty().html(result.data).find('a[data-view="' + view + '"]').addClass('active');
				$agentList.trigger('dynamicList:refresh');
			}
		});

		// Reload total fields on the dashboard
		$.getJSON('<?= APP_URL.'/ajax/get/building_manager_fields?building_id='.$building->id; ?>', function(result) {
			if(result.data) {
				$('.field-total-units').text(result.data.total_units);
				$('.field-occupied-units').text(result.data.occupied_units);
				$('.field-vacant-units').text(result.data.vacant_units);
			}
		});

		// Reload the last selected view
		LoadTenantView(lastTenantView);
	}

	// Add default handlers (can be overridden by loaded views)

	window.refreshTariffs = function() { };
	window.tenantSelected = function() { };
</script>
