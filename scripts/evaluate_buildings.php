#!/usr/bin/php -q
<?php

// This script refreshes all buildings' module fields
// Saves us from opening and saving every building in the configurator manually

include '../inc/init.app.php';

echo "Selecting buildings...\n\n";

$buildings = App::sql()->query("SELECT id FROM building;") ?: [];

foreach($buildings as $b) {
	$building = new Building($b->id);
	if($building) {
		echo "Processing building '$b->id'.\n";
		$building->evaluate_modules();
	} else {
		echo "Skipping building '$b->id'.\n";
	}
}

echo "\n\ndone.\n";
