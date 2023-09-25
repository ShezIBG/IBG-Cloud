<?php
	//CONFIGURATION for SmartAdmin UI

	$User_id = $user->info->id;
	//print_r($User_logged_in); exit;
	$User_tenant = User::check_tenant($User_id);
	//$User_building = User::check_building_admin(User_id);
	$User_nc = User::check_ba_newcentury($User_id);

	// print_r($User_nc->description);exit;

	if(($User_tenant->description) == 'tenant' || ($User_tenant->description) == 'Tenant' || ($User_tenant->description) == 'Tenant MM' || ($User_tenant->description) == 'tenant MM')
	{
		//print_r($User_tenant->description); exit;
		$main_page = 'tenants';
		switch ($main_page) {
			case App::MAIN_PAGE_TENANTS:
				$page_nav = [
					"Multi-meter" => [
						"title" => "Meters",
						"icon" => "eticon-meter",
						"url" => "view/dashboard/meters",
						"class" => "bg-color-purple"
					]
				];
			break;

		}

	}
	elseif(($User_nc->description) == 'BA New Century' || ($User_nc->description) == 'ba new century')
	{
		//print_r($User_tenant->description); exit;
		$main_page = 'tenants';
		switch ($main_page) {
			case App::MAIN_PAGE_TENANTS:
				$page_nav = [
					"Multi-meter" => [
						"title" => "Meters",
						"icon" => "eticon-meter",
						"url" => "view/dashboard/meters",
						"class" => "bg-color-purple"
					]
				];
			break;

		}

	}
	elseif(($User_nc->description) == 'Client Admin MM' || ($User_nc->description) == 'client admin mm')
	{
		//print_r($User_tenant->description); exit;
		$main_page = 'tenants';
		switch ($main_page) {
			case App::MAIN_PAGE_TENANTS:
				$page_nav = [
					"Multi-meter" => [
						"title" => "Meters",
						"icon" => "eticon-meter",
						"url" => "view/dashboard/meters",
						"class" => "bg-color-purple"
					]
				];
			break;

		}

	}
	elseif(($User_nc->description) == 'Building Admin MM' || ($User_nc->description) == 'building admin mm')
	{
		//print_r($User_tenant->description); exit;
		$main_page = 'tenants';
		switch ($main_page) {
			case App::MAIN_PAGE_TENANTS:
				$page_nav = [
					"Multi-meter" => [
						"title" => "Meters",
						"icon" => "eticon-meter",
						"url" => "view/dashboard/meters",
						"class" => "bg-color-purple"
					]
				];
			break;

		}

	}
	elseif(($User_nc->description) == 'MultiSense')
	{
		//print_r($User_tenant->description); exit;
		$main_page = 'tenants';
		switch ($main_page) {
			case App::MAIN_PAGE_TENANTS:
				$page_nav = [
					"Multi-meter" => [
						"title" => "MultiSense",
						"icon" => "eticon-meter",
						"url" => "view/dashboard/multisense",
						"class" => "bg-color-purple"
					]
				];
			break;

		}

	}
	else{
	$main_page = 'home';
	switch ($main_page) {
		
		case App::MAIN_PAGE_HOME:
		default:
			$page_nav = [
				"home" => [
					"title" => "Home",
					"icon" => "eticon-home",
					"url" => "view/home",
					"class" => "bg-color-purple"
				]
				
			 ];
			break;
	}
	}
	//configuration variables
	$page_title = "IBG Cloud";
	$page_css = [];
	$page_body_prop = []; //optional properties for <body>
?>
