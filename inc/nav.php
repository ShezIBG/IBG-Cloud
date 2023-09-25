
		<script src="https://kit.fontawesome.com/76ce47de3a.js" crossorigin="anonymous"></script>	
		<aside id="left-panel" class="animated slideInLeft">
			<div class="scroll_wrap_nav">
			<!-- <span onclick="minMenu()" class="minifyme" data-action="minifyMenu" style=""> <i class="fa fa-arrow-circle-left hit"></i> </span> -->
				<nav>	
					<?php
				
						$ui->create_nav($page_nav)->print_html();
						

					?>
				</nav>
				
					<?php
						require_once dirname($app_path).'/lib/class.module.php';
						$User_id = $user->info->id;
						//print_r($User_logged_in); exit;
						$User_tenant = User::check_tenant($User_id);
						$User_nc = User::check_ba_newcentury($User_id);
						if(($User_tenant->description) == 'tenant' || ($User_tenant->description) == 'Tenant' || ($User_tenant->description) == 'Tenant MM' || ($User_tenant->description) == 'tenant MM'){

							//Removes sidebar duplicates for tenants
						}
						elseif(($User_nc->description) == 'BA New Century' || ($User_nc->description) == 'ba new century'){

							//Removes sidebar duplicates for tenants
						}
						elseif(($User_nc->description) == 'MultiSense'){
							// Module::print_sidebar_active();
							//Removes sidebar duplicates for tenants
						}
						elseif(($User_nc->description) == 'Building Admin MM' || ($User_nc->description) == 'building admin mm'){

							Module::print_sidebar_active();
						}
						elseif(($User_nc->description) == 'Client Admin MM' || ($User_nc->description) == 'client admin mm'){

							Module::print_sidebar_active();
						}
						else{
						Module::print_sidebar_active();
						Module::print_sidebar_remaining();
						Module::print_external_pages();
						}
					?>
			</div>	
			<span onclick="minMenu()" class="minifyme" data-action="minifyMenu" style=""> <i class="fa fa-arrow-circle-left hit"></i> </span>
			<!-- <span class="minifyme" data-action="minifyMenu" style=""> <i class="fa fa-arrow-circle-left hit"></i> </span> -->
		</aside>
