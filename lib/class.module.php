<?php

class Module {
//HENRY - Need to add future modules here initially 
	const ELECTRICITY        = 1;
	const GAS                = 2;
	const WATER              = 3;
	const METERS             = 4;
	const EMERGENCY          = 5;
	const BUILDING           = 6;
	const REPORTS            = 11;
	const SETTINGS           = 12;
	const SALES              = 13;
	const ISP                = 14;
	const CLIMATE            = 15;
	const RELAY              = 16;
	const STOCK              = 17;
	const RENEWABLES         = 18;
	const SMOOTHPOWER        = 19;
	const LIGHTING           = 20;
	const BILLING            = 21;
	const CONTROL            = 22;
	const SECURITY           = 23;
	const SURVEILLANCE		 = 24;
	const FIRE				 = 27;
	const EVCHARGER			 = 28;
	const ACCESS			 = 29;
	const MULTISENSE		 = 30;

	public $id;
	public $info;

	// Get a module by ID or alias
	public function __construct($id) {
		// echo "SELECT * FROM module WHERE active = '0'"; exit;
		$search = App::escape($id);
		//print_r($search);exit;
		$this->id = 0;
		$this->info = App::sql()->query_row("SELECT * FROM module WHERE id = '$search' OR alias = '$search';");
		//$this->disabled = App::sql()->query_row("SELECT * FROM module WHERE id='$search' AND active = '0';");
		
		// print_r($this->disabled); exit;
		if($this->info) $this->id = $this->info->id;
		//if($this->disabled) $this->id = $this->disabled->id;
	}

	// Returns true if module is valid and current user has access to it
	public function validate() {
		return !!$this->info && Permission::any()->is_module_enabled($this->id);
	}


	public static function is_enabled($module_id) {
		return Permission::any()->is_module_enabled($module_id);
	}

	//HENRY EDIT
	// public static function get_modules_disabled($ids) {

	// 	if(!is_array($ids)) return [];

	// 	$result = [];
	// 	foreach($ids as $id) {
			
	// 		$m = new Module($id);
	// 		if($m->disabled) $result[] = $m;
	// 	}
	// 	return $result;
		
	// }
	
	
	
	/**
	 * Given an array of module IDs, returns a list of module objects
	 */
	public static function get_modules_by_id($ids) {
		if(!is_array($ids)) return [];

		$result = [];
		foreach($ids as $id) {
			$m = new Module($id);
			if($m->info) $result[] = $m;
		}
		return $result;
	}

	public static function get_module($id) {
		return new Module($id);
	}


	public static function print_sidebar_active(){
		$newarray =array();
		$modules = Module::get_modules_by_id(Permission::any()->get_enabled_module_ids());

// JGLASSELL  the menus are created differently over time and each function the margin moves different so here it is specified how much marging for each ul
		echo
				'<nav>	
				<ul class="myMenu-ul1">
					<li>
					';
		
		//Not the best solution but it works!
		foreach($modules as $m){
			if(($m->id != Module::SETTINGS && $m->info->url) && ($m->id != Module::METERS && $m->info->url)&& ($m->id != Module::EMERGENCY && $m->info->url)&& 
			($m->id != Module::BILLING && $m->info->url)&& ($m->id != Module::CONTROL && $m->info->url)&& ($m->id != Module::SECURITY && $m->info->url)
			&& ($m->id != Module::LIGHTING && $m->info->url)&& ($m->id != Module::SMOOTHPOWER && $m->info->url)&& ($m->id != Module::STOCK && $m->info->url)
			&& ($m->id != Module::RELAY && $m->info->url)&& ($m->id != Module::CLIMATE && $m->info->url)&& ($m->id != Module::ISP && $m->info->url)
			&& ($m->id != Module::SALES && $m->info->url)&& ($m->id != Module::BUILDING && $m->info->url)&& ($m->id != Module::REPORTS && $m->info->url)
			&& ($m->id != Module::SURVEILLANCE && $m->info->url)&& ($m->id != Module::FIRE && $m->info->url)&& ($m->id != Module::EVCHARGER && $m->info->url)&& ($m->id != Module::ACCESS && $m->info->url)&& ($m->id != Module::MULTISENSE && $m->info->url)){
				echo '
				<li class="myShow module-id-'.$m->id.'">
					<a href="'.$m->info->sidebar_url.'">
						<i class="eticon eticon-2x '.$m->info->icon.'"></i>
					
					</a>
				</li>';
			}
		}
		echo '
		</li>
		</ul></nav>
		';	



		echo
				'<nav>	
				<ul class="myMenu-ul2">
					<li>
					<a class="myHide" href="#" style="display: none;"><i class="fa fa-tachometer"></i><span class="menu-item-parent">Metering & Monitoring</span></a>
					<ul>';
		
		//Not the best solution but it works!
		foreach($modules as $m){
			if(($m->id != Module::SETTINGS && $m->info->url) && ($m->id != Module::METERS && $m->info->url)&& ($m->id != Module::EMERGENCY && $m->info->url)&& 
			($m->id != Module::BILLING && $m->info->url)&& ($m->id != Module::CONTROL && $m->info->url)&& ($m->id != Module::SECURITY && $m->info->url)
			&& ($m->id != Module::LIGHTING && $m->info->url)&& ($m->id != Module::SMOOTHPOWER && $m->info->url)&& ($m->id != Module::STOCK && $m->info->url)
			&& ($m->id != Module::RELAY && $m->info->url)&& ($m->id != Module::CLIMATE && $m->info->url)&& ($m->id != Module::ISP && $m->info->url)
			&& ($m->id != Module::SALES && $m->info->url)&& ($m->id != Module::BUILDING && $m->info->url)&& ($m->id != Module::REPORTS && $m->info->url)
			&& ($m->id != Module::SURVEILLANCE && $m->info->url)&& ($m->id != Module::FIRE && $m->info->url)&& ($m->id != Module::EVCHARGER && $m->info->url)&& ($m->id != Module::ACCESS && $m->info->url)&& ($m->id != Module::MULTISENSE && $m->info->url)){
			
				echo '<li class="module-id-'.$m->id.'"><a href="'.$m->info->sidebar_url.'" class="module-link myNav-dropdown"><i class="eticon eticon-2x '.$m->info->icon.'"></i>'.$m->info->alias.'</a></li>';

			}
		}
		echo '</ul>
		</li>
		</ul>
		';	

	}

	//this is what doesnt work 
	// http://192.168.10.16/jeane/eticom/dashboard#/isp   //M
	// http://192.168.10.16/jeane/eticom/dashboard#http://192.168.10.16/jeane/eticom/isp WITH URL
	// http://192.168.10.16/jeane/eticom/isp  WORKING
	public static function print_sidebar_remaining() {
		$modules = Module::get_modules_by_id(Permission::any()->get_enabled_module_ids());
	
		echo '<ul class="myMenu-ul3">';
		
		// $my_url = 'isp';
		// echo '<li><a href="'.$my_url.'"><i class="fa fa-tachometer"></i><span class="menu-item-parent myText-caps">Test1</span></a></li>';
		
		foreach($modules as $m) {
			if(($m->id != Module::SETTINGS && $m->info->url) && ($m->id != Module::WATER && $m->info->url)&& ($m->id != Module::ELECTRICITY && $m->info->url)&& 
			($m->id != Module::GAS && $m->info->url)&& ($m->id != Module::RENEWABLES && $m->info->url)&&($m->id != Module::ISP && $m->info->url)&& ($m->id != Module::CONTROL && $m->info->url)&& ($m->id != Module::BILLING && $m->info->url)&& ($m->id != Module::BUILDING && $m->info->url)
			&& ($m->id != Module::LIGHTING && $m->info->url)&& ($m->id != Module::STOCK && $m->info->url)&& ($m->id != Module::SALES && $m->info->url)) {
				// test these two and then test the top two in the variable outside the loop                               
				echo '<li><a href="'.$m->info->sidebar_url.'"><i class="eticon '.$m->info->icon.'"></i><span class="menu-item-parent myText-caps">'.$m->info->alias.'</span></a></li>';
				//echo '<li><a href="'.APP_URL.$m->info->sidebar_url.'"><i class="eticon '.$m->info->icon.'"></i><span class="menu-item-parent myText-caps">AAA'.$m->info->alias.'</span></a></li>';
			}              

		}
		echo '</ul></nav>';
	}

	 public static function print_external_pages(){
		$modules = Module::get_modules_by_id(Permission::any()->get_enabled_module_ids());
		
		echo '<ul class="myMenu-ul4 myMenuList">';
		foreach($modules as $m) {
			// JGLASSELL ONCLICK CLASS IS ADDED CALLED MYHIDE OR MYSHOW
			if(($m->id == Module::ISP && $m->info->url)){
		echo '<a href="'.APP_URL.$m->info->url.'"><li><i class="myMenuList-icon eticon '.$m->info->icon.'"></i><span class="myMenuList-textWrapper menu-item-parent"><span class="myMenuList-text myHide">'.$m->info->alias.'</span></span></li></a>';
				}
			if(($m->id == Module::BILLING && $m->info->url)){
					echo '<a href="'.APP_URL.$m->info->url.'"><li><i class="myMenuList-icon eticon '.$m->info->icon.'"></i><span class="myMenuList-textWrapper menu-item-parent"><span class="myMenuList-text myHide">'.$m->info->alias.'</span></span></li></a>';
				}
			if(($m->id == Module::STOCK && $m->info->url)){
					echo '<a href="'.APP_URL.$m->info->url.'"><li><i class="myMenuList-icon eticon '.$m->info->icon.'"></i><span class="myMenuList-textWrapper menu-item-parent"><span class="myMenuList-text myHide">'.$m->info->alias.'</span></span></li></a>';
				}
			if(($m->id == Module::CONTROL && $m->info->url)){
					echo '<a href="'.APP_URL.$m->info->url.'"><li><i class="myMenuList-icon eticon '.$m->info->ibg_icon.'"></i><span class="myMenuList-textWrapper menu-item-parent"><span class="myMenuList-text myHide">'.$m->info->ibg_alias.'</span></span></li></a>';
				}
			if(($m->id == Module::SALES && $m->info->url)){
					echo '<a href="'.APP_URL.$m->info->url.'"><li><i class="myMenuList-icon eticon '.$m->info->icon.'"></i><span class="myMenuList-textWrapper menu-item-parent"><span class="myMenuList-text myHide">'.$m->info->alias.'</span></span></li></a>';
				}	
			if(($m->id == Module::BUILDING && $m->info->url)){
					echo '<a href="'.APP_URL.$m->info->url.'"><li><i class="myMenuList-icon eticon '.$m->info->icon.'"></i><span class="myMenuList-textWrapper menu-item-parent"><span class="myMenuList-text myHide">'.$m->info->alias.'</span></span></li></a>';
				}	
			}
		echo '</ul>';
	 }


	public static function print_icon_bar() {
		$modules = Module::get_modules_by_id(Permission::any()->get_enabled_module_ids());

		echo '<ul class="myMenu-ul5 list-inline">';
		foreach($modules as $m) {
			if($m->id != Module::SETTINGS && $m->info->url) {
				echo '<li class="module-id-'.$m->id.'"><a href="'.APP_URL.$m->info->url.'" class="module-link" title="'.$m->info->description.'"><i class="eticon eticon-2x '.$m->info->icon.'"></i></a></li>';
			}
		}
		echo '</ul>';
	}

	public function init() {
		if($this->validate()) {
			// All ok, select module icon and add refresh listener
			?>
				<script>
					$(function() {
						$('#module-nav li a').removeClass('active');
						$('#module-nav').find('li.module-id-<?= $this->id ?> a').addClass('active');

						registerRefreshListener(function() {
							$('#module-nav li a').removeClass('active');
						}, true);
					});
				</script>
			<?php
			return true;
		} else {
			http_response_code(403);
			return false;
		}
	}

}
