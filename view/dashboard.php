<?php
	require_once 'init.view.php';

	$db_type = App::get('type', Dashboard::DASHBOARD_TYPE_MAIN);
	$sm_id = 0;
	switch($db_type) {
		case Dashboard::DASHBOARD_TYPE_GAS:         $sm_id = Module::GAS;                 break;
		case Dashboard::DASHBOARD_TYPE_MAIN:        $sm_id = Module::ELECTRICITY;         break;
		case Dashboard::DASHBOARD_TYPE_METERS:      $sm_id = Module::METERS;              break;
		case Dashboard::DASHBOARD_TYPE_WATER:       $sm_id = Module::WATER;               break;
		case Dashboard::DASHBOARD_TYPE_RENEWABLES:  $sm_id = Module::RENEWABLES;          break;
		case 'emergency':                           $sm_id = Module::EMERGENCY;           break;
		case 'climate':                             $sm_id = Module::CLIMATE;             break;
		case 'relay':                               $sm_id = Module::RELAY;               break;
		case 'lighting':                            $sm_id = Module::LIGHTING;            break;
		case 'billing':                             $sm_id = Module::BILLING;             break;
		case 'control':                             $sm_id = Module::CONTROL;             break;
	}

	if($sm_id) {
		$module = Module::get_module($sm_id);
		if(!$module->init()) return;
	}

	$db_partial_path = APP_PATH.'/view/partials/dashboard_'.$db_type.'.php';
	if (isset($db_type) && file_exists($db_partial_path)) include_once($db_partial_path);
