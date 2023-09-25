<?php

class Eticom {

	const DROPDOWN_BUILDING_TYPE      = 'building_type';

	const CATEGORY_BILLING = 11;

	public static function print_status($code = 403) {
		echo file_get_contents(APP_URL.'/view/error.php?code='. $code);
	}

	public static function get_dropdowns($type) {
		$type = App::escape($type);
		return App::sql()->query("SELECT value, description FROM dropdowns WHERE type = '$type' AND active = 1");
	}

	public static function get_days() {
		return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
	}

	// This function is a work-around for the issue that building timezones are lowercased for some weird reason.
	// This query should resolve the ID with the correct case.
	public static function find_timezone_id($tz) {
		$result = App::sql()->query_row("SELECT timezone FROM country WHERE timezone = '$tz' ORDER BY country LIMIT 1");
		return $result ? $result->timezone : $tz;
	}

	/**
	 *  Returns an array of available VAT rates, with an optional current value to include in the list
	 */
	public static function get_vat_rates($current) {
		$result = [
			[ 'value' => '0.00',  'description' => '0%'  ],
			[ 'value' => '5.00',  'description' => '5%'  ],
			[ 'value' => '20.00', 'description' => '20%' ]
		];

		$c = App::format_number($current);
		if ($c != '0.00' && $c != '5.00' && $c != '20.00') {
			$result[] = [ 'value' => $c, 'description' => $c.'%' ];
		}

		return $result;
	}

}
