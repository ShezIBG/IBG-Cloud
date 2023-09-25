<script>

	pageSetUp();

	var $selectedDashboard = $('#selected-dashboard'),
		dbType = '<?= $db_type; ?>',
		hasResponsiveHeight = <?= Dashboard::has_responsive_height($db_type) ? 1 : 0; ?>;

	// data-exec methods

	var loadWidget = function($widgetItem, data) {
		var dbWidgetId = $widgetItem.data('id'),
			widgetId = $widgetItem.data('widget-id'),
			$content = $widgetItem.find('.grid-stack-item-content'),
			data = $.extend(true, {}, {
				id: dbWidgetId,
				widget_id: widgetId,
				dashboard_id: $selectedDashboard.val(),
				db_type: dbType
			}, data);

		$content.html('<i class="eticon eticon-gear eticon-spin eticon-2x"></i>');

		$.get('<?= APP_URL."/view/widget.php"; ?>', data, function(content) {
			$content.html(content);
		}, 'html');
	};

	// pagefunction

	var pagefunction = function() {

		$('.grid-stack').gridstack({
			width: hasResponsiveHeight ? (ismobile ? 12 : 12) : (ismobile ? 9 : 12),
			cellHeight: hasResponsiveHeight ? $.gridCellHeight : $.gridCellHeightDevice,
			verticalMargin : 16,
			minWidth: 480,
			handle: 'header, footer',
			alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
		});

		// Destroy grid if form changes to prevent JavaScript exception on resize
		registerRefreshListener(function() {
			var grid = $('.grid-stack').data('gridstack');
			try {
				grid.destroy(false);
			} catch(ex) { }
		}, true);

		$('.js-widget-item').each(function() {
			loadWidget($(this));
		});

		$(window).on('resize',function() {
			var grid = $('.grid-stack').data('gridstack');

			if (hasResponsiveHeight && grid) {
				var test = (($(this).height() - $('#header').height() + 65) / 20) - $.gridCellHeight;
				if(test < 16) test = 16;
				if (test != grid.cellHeight()) {
					grid.cellHeight(test)
				}
			}
		});
	};

	loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.cust.min.js", function() {
		loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.resize.min.js", function() {
			loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.fillbetween.min.js", function() {
				loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.orderBar.min.js", function() {
					loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.pie.min.js", function() {
						loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.axislabels.js", function() {
							loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.time.min.js", function() {
								loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.stack.min.js", function() {
									loadScript("<?= ASSETS_URL ?>/js/plugin/flot/jquery.flot.tooltip.min.js", function() {
										loadScript("<?= ASSETS_URL ?>/js/plugin/bootstrap-progressbar/bootstrap-progressbar.min.js", function() {
											loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/jquery.dataTables.min.js", function() {
												loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.colVis.min.js", function() {
													loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.tableTools.min.js", function() {
														loadScript("<?= ASSETS_URL ?>/js/plugin/datatables/dataTables.bootstrap.min.js", function() {
															loadScript("<?= ASSETS_URL ?>/js/plugin/datatable-responsive/datatables.responsive.min.js", pagefunction);//{
																// loadScript("<?= ASSETS_URL ?>/js/plugin/chartjs/chart.min.js", pagefunction);
															//});
														});
													});
												});
											});
										});
									});
								});
							});
						});
					});
				});
			});
		});
	});

</script>
