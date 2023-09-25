<?php
	$ui_widget = $ui->create_widget([
		'editbutton' => false,
		'fullscreenbutton' => false,
		'colorbutton' => false,
		'deletebutton' => false
	]);
	$ui_widget->id = $widget_info->ui_id;
	$ui_widget->color = 'blueLight';
	$ui_widget->header('title', '<p class="myWidget-title">Your Portfolio</p><img class="pull-right" src="'.ASSETS_URL.'/img/icons/widget-buildings.svg">');

	$content = '<div id="portfolio-map" style="width:100%;height:100%;margin:0;padding:0;"></div>';

	$ui_widget->body('content', $content);
	$ui_widget->body('class', 'padding-0');
	$ui_widget->class = 'dashboard-widget dashboard-widget-fixed';
	$ui_widget->footer = ' ';
	$ui_widget->print_html();
?>

<script>
	$(function() {
		setTimeout(function() {
			var map = new google.maps.Map(document.getElementById('portfolio-map'), { zoom: 17 });

			var buildings =
				<?php
					$module_electricity = new Module(Module::ELECTRICITY);
					$module_gas = new Module(Module::GAS);
					$module_water = new Module(Module::WATER);
					$module_renewables = new Module(Module::RENEWABLES);
					$module_meters = new Module(Module::METERS);
					$module_emergency = new Module(Module::EMERGENCY);
					$module_lighting = new Module(Module::LIGHTING);
					$module_building = new Module(Module::BUILDING);
					$module_smoothpower = new Module(Module::SMOOTHPOWER);
					$module_control = new Module(Module::CONTROL);

					$list = Building::list_with_permission(null, true);

					$coords = [];
					if($list) {
						foreach($list as $b) {
							if(!$b->latitude || !$b->longitude) continue;

							$info = "<b>$b->description</b>";
							if($b->posttown) $info .= "<br>$b->posttown";
							if($b->postcode) $info .= "<br>$b->postcode";

							$links = [];
							$perm = new Permission($b);

							if($perm->check(Permission::ELECTRICITY_ENABLED)) $links[] = $module_electricity;
							if($perm->check(Permission::GAS_ENABLED)) $links[] = $module_gas;
							if($perm->check(Permission::WATER_ENABLED)) $links[] = $module_water;
							if($perm->check(Permission::RENEWABLES_ENABLED)) $links[] = $module_renewables;
							if($perm->check(Permission::METERS_ENABLED)) $links[] = $module_meters;
							if($perm->check(Permission::EMERGENCY_ENABLED)) $links[] = $module_emergency;
							if($perm->check(Permission::LIGHTING_ENABLED)) $links[] = $module_lighting;
							if($perm->check(Permission::BUILDING_ENABLED)) $links[] = $module_building;
							if($perm->check(Permission::SMOOTHPOWER_ENABLED)) $links[] = $module_smoothpower;
							if($perm->check(Permission::CONTROL_ENABLED)) $links[] = $module_control;

							if(count($links) > 0) {
								$info .= '<br><br>';
								foreach($links as $m) {
									$m_color = $m->info->color;
									$m_icon = $m->info->icon;
									$m_desc = $m->info->description;
									$m_url = APP_URL.$m->info->url;
									$m_target = '_self';
									if($m->id == Module::EMERGENCY || $m->id == Module::LIGHTING || $m->id == Module::CONTROL) $m_url .= "?building=$b->id";
									if($m->id == Module::SMOOTHPOWER) {
										$m_url = 'https://smoothpower.co.uk/portal';
										$m_target = '_blank';
									}
									$info .= "<a href=\"$m_url\" target=\"$m_target\" style=\"display: inline-block; width: 26px; padding: 4px; border-radius: 3px; margin-right: 2px; text-align: center; background: $m_color; color: white;\" title=\"$m_desc\"><i class=\"eticon $m_icon\"></i></a>";
								}
							}

							$coords[] = [
								'id' => $b->id,
								'lat' => $b->latitude,
								'lng' => $b->longitude,
								'description' => $b->description,
								'info' => $info
							];
						}
					}

					echo json_encode($coords);
				?>
			;

			var infoWindow = new google.maps.InfoWindow();

			var bounds = new google.maps.LatLngBounds();
			$.each(buildings, function(k, v) {
				var marker = new google.maps.Marker({
					position: { lat: v.lat, lng: v.lng },
					map: map,
					animation: google.maps.Animation.DROP,
					title: v.description
				});

				marker.addListener('click', function() {
					var $content = $('<div></div>').html(v.info);
					$content.on('click', 'a', function(e) {
						var $this = $(this);
						e.preventDefault();
						$.post('<?= APP_URL ?>/ajax/post/set_default_building', {
							building_id: v.id
						}, function(data) {
							if($this.attr('target') === '_blank') {
								window.open($this.prop('href'));
							} else {
								window.location = $this.prop('href');
							}
						});
					});
					infoWindow.setContent($content[0]);
					infoWindow.open(map, this);
				});

				bounds.extend(marker.position);
			});

			map.fitBounds(bounds);

			// Restore zoom level
			var listener = google.maps.event.addListener(map, "idle", function () {
				if(map.getZoom() > 17) map.setZoom(17);
				google.maps.event.removeListener(listener);
			});
		}, 200);
	});
</script>
