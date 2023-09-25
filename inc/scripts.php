<!-- /* JEANE CHANGE COLOUR CHART AND STEP */  -->
			<script>
			// Polyfills

			if (!String.prototype.startsWith) {
				String.prototype.startsWith = function(searchString, position) {
					position = position || 0;
					return this.substr(position, searchString.length) === searchString;
				};
			}
		</script>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script>
			if (!window.jQuery) {
				document.write('<script src="<?= ASSETS_URL ?>/js/libs/jquery-2.1.3.min.js"><\/script>');
			}
		</script>
	
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/i18n/jquery-ui-i18n.min.js"></script>
		<script>
			if (!window.jQuery.ui) {
				document.write('<script src="<?= ASSETS_URL ?>/js/libs/jquery-ui-1.11.2.min.js"><\/script>');
				document.write('<script src="<?= ASSETS_URL ?>/js/libs/jquery-ui-i18n.min.js"><\/script>');
			}
		</script>
		<!-- Redirects customer who are overdue payments to payment request page -->
		<script>
			if (isInDebt.data.ok === false) {
				window.location.href = isInDebt.data.redirect_url;
			}
		</script>
		<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY; ?>"></script>

		<!-- APP CONFIG -->
		<script src="<?= ASSETS_URL ?>/js/app.config.js"></script>

		<!-- JS TOUCH -->
		<script src="<?= ASSETS_URL ?>/js/plugin/jquery-touch/jquery.ui.touch-punch.min.js"></script>

		<!-- BOOTSTRAP JS -->
		<script src="<?= ASSETS_URL ?>/js/bootstrap/bootstrap.min.js"></script>

		<!-- CUSTOM NOTIFICATION -->
		<script src="<?= ASSETS_URL ?>/js/notification/SmartNotification.min.js"></script>

		<!-- Gridstack -->
		<script src="<?= ASSETS_URL ?>/js/plugin/gridstack/underscore-min.js"></script>
		<script src="<?= ASSETS_URL ?>/js/plugin/gridstack/gridstack.min.js"></script>

		<!-- EASY PIE CHARTS -->
		<script src="<?= ASSETS_URL ?>/js/plugin/easy-pie-chart/jquery.easy-pie-chart.min.js"></script>

		<!-- SPARKLINES -->
		<script src="<?= ASSETS_URL ?>/js/plugin/sparkline/jquery.sparkline.min.js"></script>

		<!-- JQUERY VALIDATE -->
		<script src="<?= ASSETS_URL ?>/js/plugin/jquery-validate/jquery.validate.min.js"></script>
		<script src="<?= ASSETS_URL ?>/js/plugin/jquery-validate/jquery.validate.additional.min.js"></script>

		<!-- JQUERY MASKED INPUT -->
		<script src="<?= ASSETS_URL ?>/js/plugin/masked-input/jquery.maskedinput.min.js"></script>

		<!-- JQUERY SELECT2 INPUT -->
		<script src="<?= ASSETS_URL ?>/js/plugin/select2/select2.min.js"></script>

		<!-- JQUERY UI + Bootstrap Slider -->
		<script src="<?= ASSETS_URL ?>/js/plugin/bootstrap-slider/bootstrap-slider.min.js"></script>

		<!-- browser msie issue fix -->
		<script src="<?= ASSETS_URL ?>/js/plugin/msie-fix/jquery.mb.browser.min.js"></script>

		<!-- FastClick: For mobile devices -->
		<script src="<?= ASSETS_URL ?>/js/plugin/fastclick/fastclick.min.js"></script>

		<!-- SlimScroll: For fixed navigation scrolling -->
		<script src="<?= ASSETS_URL ?>/js/plugin/slimscroll/jquery.slimscroll.min.js"></script>

		<!-- Console Log -->
		<script src="<?= ASSETS_URL ?>/js/plugin/consolelog/consolelog.js"></script>

		<!-- bootstrap dropselect -->
		<script src="<?= ASSETS_URL ?>/js/plugin/bootstrap-dropselect/bootstrap-dropselect.js"></script>

		<!--[if IE 8]>
			<h1>Your browser is out of date, please update your browser by going to www.microsoft.com/download</h1>
		<![endif]-->

		<!-- MAIN APP JS FILE -->
		<script src="<?= ASSETS_URL ?>/js/app.min.js"></script>

		<!-- CUSTOM APP JS FILE -->
		<script src="<?= ASSETS_URL ?>/js/app.global.js"></script>

		<script src="<?= ASSETS_URL ?>/js/skycons.min.js"></script>

		<script>
			(function($, window) {
				// This function is used to replace the dropdown select content when needed
				$.fn.replaceOptions = function(options) {
					var self, $option;

					this.empty();
					self = this;

					$.each(options, function(index, option) {
						$option = $("<option></option>")
							.attr("value", option.value)
							.text(option.description);
						self.append($option);
					});
				};
			})(jQuery, window);

			$(document).ajaxError(function(event, request, settings) {
				if (request.status == 401) window.location.href = '<?= APP_URL . "/auth?r=" . urlencode($_SERVER["REQUEST_URI"]); ?>';
			});

			var initFlot = function(placeholder, data, options) {
				var options = $.extend(true, {}, {
					// colors: ['#F9C02B', '#E84F32', '#88B4CB', '#A2B83A', '#ED7339', '#6DBFAA', '#BB5979'],ORIGINAL
					// colors: ['#3e464c', '#2aa06a', '#70e3b2', '#9ee2c7', '#8BABB5', '#4F5D6B', '#394d5c'], SHEZ
					colors: ['#2E3C47','#0097ce','#2EA8A1','#6CBF65','#bede18', '#9399A3','red'],
					grid: {
						hoverable: true,
						// clickable: true,
						tickColor: '#C6C2BD',
						backgroundColor: { colors: [ "#fff", "#fff" ] },
						borderWidth: 0,
						borderColor: '#aaa'
					},
					axisLabels: { show: true },
					xaxis: {
						tickDecimals: 0,
						mode: "categories",
						tickLength: 5,
						color: '#333'
					},
					yaxis: {},
					lines: { lineWidth: 1, fill: 0 },
					points: {
						lineWidth: 2,
						radius: 1,
						fill: true
					},
					bars: {
						barWidth: 0.6,
						align: "center",
						lineWidth: 0,
						fill: 0.1,
						fillColor: {
							colors: [
								{ opacity: 1.0 },
								{ opacity: 1.0 }
							]
						}
					}
				}, options);

				if(!options.series) options.series = {};
				if(!options.series.highlightColor) options.series.highlightColor = 'rgba(255, 255, 255, 0.25)';
				if(!options.tooltip) {
					options.tooltip = {
						show: true,
						content: options.series && options.series.pie ? '%s' : '%y'
					}
				}

				var plot = $.plot(placeholder, data, options);
				return plot;
			}

			// The following hijacks SmartAdmin's checkURL function so that we can register a refresh listener
			// This way we can easily implement partial refreshes without messing with existing modal code

			var listenURL =  '';
			var listenCallback = [];
			var listenAnyURLCallback = [];

			var registerRefreshListener = function(listener, anyURL) {
				if(anyURL) {
					listenAnyURLCallback.push(listener);
				} else {
					listenURL = window.location.href;
					listenCallback.push(listener);
				}
			};

			if(window.checkURL) {
				var _checkURL = window.checkURL;
				window.checkURL = function() {
					if(listenAnyURLCallback.length > 0) {
						$.each(listenAnyURLCallback, function(k, f) { f(); })
					}
					if(window.location.href == listenURL && listenCallback.length > 0) {
						$.each(listenCallback, function(k, f) { f(); })
					} else {
						listenURL = '';
						listenCallback = [];
						listenAnyURLCallback = [];

						// This is a work-around for the message box bug
						// If this is not set, messageboxes will not show after reload
						ExistMsg = 0;

						_checkURL();
					}
				}
			}

			// Create dynamically paged lists
			var createDynamicList = function($list, $listFooter, $listSearch) {
				$list.find('li').addClass('matched');

				var refreshList = function() {
					var itemHeight = $list.find('li:first').outerHeight();
					var listHeight = $list.parent().innerHeight() - $list.position().top - $listFooter.outerHeight();
					var perPage = Math.floor(listHeight / itemHeight);
					var items = $list.find('li.matched').length;
					var page = $list.data('page') || 0;
					var pages = Math.ceil(items / perPage);
					if(page >= pages) page = pages - 1;
					if(page < 0) page = 0;

					var $first = $list.find('li.matched').eq(page * perPage);
					$list.find('li').hide();
					var $current = $first;
					var shown = 0;
					while(shown < perPage && $current.length > 0) {
						if($current.is('.matched')) {
							shown += 1;
							$current.show();
						}
						$current = $current.next('li');
					}

					$listFooter.find('.page').text(page + 1);
					$listFooter.find('.pages').text(pages);
					$list.data('page', page);

					$listFooter.find('.prev').closest('td').toggle(page > 0);
					$listFooter.find('.next').closest('td').toggle(page < pages - 1);
				};

				$listFooter.find('.next').on('click', function(e) {
					e.preventDefault();
					var page = $list.data('page') || 0;
					page += 1;
					$list.data('page', page);
					refreshList();
				});

				$listFooter.find('.prev').on('click', function(e) {
					e.preventDefault();
					var page = $list.data('page') || 0;
					page -= 1;
					$list.data('page', page);
					refreshList();
				});

				var triggerSearch = function() {
					$list.find('li').removeClass('matched');
					if (!$listSearch) {
						$list.find('li').addClass('matched');
						return;
					}

					var words = ('' + $listSearch.val()).trim().replace(/\s\s+/g, ' ').split(' ');
					var r = [];
					$.each(words, function(k, v) {
						r.push(new RegExp(v, 'i'));
					});

					$list.find('li').each(function() {
						var $e = $(this);
						var ok = true;
						var txt = '' + $e.text();
						$.each(r, function(k, reg) {
							return ok = reg.test(txt);
						});

						if(ok) $e.addClass('matched');
					});

					refreshList();
				};

				if ($listSearch) {
					var searchTimer;
					$listSearch.on('input', function() {
						clearTimeout(searchTimer);
						searchTimer = setTimeout(triggerSearch, 500);
					});
				}

				refreshList();

				$(window).on('resize.dynamicList', function() {
					refreshList();
				});

				$list.on('dynamicList:refresh', function() {
					triggerSearch();
					refreshList();
				});

				registerRefreshListener(function() {
					$(window).off('resize.dynamicList');
					$list.off('dynamicList:refresh');
				}, true);
			};

			$(function() {
				// Set default datepicker locale
				$.datepicker.setDefaults($.datepicker.regional['en-GB']);

				$('nav ul li > a[href!=#]').on('click', function() {
					if ($(this).hasClass('js-ribbon-visible')) $('#ribbon').removeClass('hidden');
					else $('#ribbon').addClass('hidden');
				});
			});
		</script>
