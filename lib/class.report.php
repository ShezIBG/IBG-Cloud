
<?php

class Report {
	
	
	const URL = 'http://18.184.105.188/reports/reports/';
	// <!-- JCHANGE MYPDF -->
	/**
	 * Must be called from a valid URL context (browser only)
	 */
	public static function get_report($id, $content_disp = "attachment") {
		$id = filter_var($id, FILTER_VALIDATE_INT);
		$report_info = App::sql()->query_row("SELECT report_history.*, building.description as building_name FROM `report_history` LEFT JOIN building ON report_history.building_id = building.id WHERE report_history.id = '$id'");

		if (Permission::get_building($report_info->building_id)->check(Permission::REPORTS_ENABLED)) {
			$date = (date("Y-m-d", strtotime($report_info->year .'-'. ($report_info->month ? $report_info->month : '1') .'-'. ($report_info->day ? $report_info->day : 1))));
			$building_name = str_replace(' ', '-', $report_info->building_name);
			$building_name = preg_replace('/[^A-Za-z0-9\-]/', '', $building_name);
			$filename = $date."_". $building_name ."_".$report_info->report_type.".pdf";
			header("Content-type:application/pdf");
			header("Content-Disposition:".$content_disp.";filename=".$filename);
			if($report_info->generator_url) {
				$url = APP_URL.$report_info->generator_url.'&key='.INTERNAL_SECRET;
				$temp_filename = '/tmp/'.md5($filename.strtotime('now')).'.pdf';
				exec(APP_PATH."/print/bin/wkhtmltopdf -T 0 -B 0 -L 0 -R 0 --viewport-size 1024x768 --print-media-type \"$url\" $temp_filename", $output);
				$bytes = readfile($temp_filename);
				unlink($temp_filename);
				return $bytes;
			} else {
				return readfile(self::URL . $report_info->directory);
			}
		} else {
			return "You don't have permission to view this file.";
		}
	}

	/**
	 * Must be called from a valid URL context (browser only)
	 */
	public static function generate_pdf_report($url, $filename, $content_disp = "attachment", $header_url = '', $footer_url = '', $options = '') {
		if(!$url) return '';
		//print_r(APP_PATH);exit;
		header("Content-type:application/pdf");
		header("Content-Disposition:".$content_disp.";filename=".$filename);

		$url = APP_URL.$url.'&key='.INTERNAL_SECRET;
		
		$temp_filename = '/tmp/'.md5($filename.strtotime('now')).'.pdf';
		
		if($header_url) {
			$header_url = APP_URL.$header_url.'&key='.INTERNAL_SECRET;
			$header_url = "--header-html \"$header_url\"";
		}

		if($footer_url) {
			$footer_url = APP_URL.$footer_url.'&key='.INTERNAL_SECRET;
			$footer_url = "--footer-html \"$footer_url\"";
		}
		
		exec(APP_PATH."/print/bin/wkhtmltopdf -T 0 -B 0 -L 0 -R 0 $options --viewport-size 1024x768 $header_url $footer_url --print-media-type \"$url\" $temp_filename", $output);
	
		$bytes = readfile($temp_filename);
		
		unlink($temp_filename);
		return $bytes;
	}

	/**
	 * Takes a FULL URL, including protocol and domain.
	 */
	public static function generate_pdf_report_file($url, $filename) {
		if(!$url) return '';

		$url = $url.'&key='.INTERNAL_SECRET;
		$temp_filename = '/tmp/'.md5($filename.strtotime('now')).'.pdf';
		exec(APP_PATH."/print/bin/wkhtmltopdf -T 0 -B 0 -L 0 -R 0 --viewport-size 1024x768 --print-media-type \"$url\" $temp_filename", $output);
		return $temp_filename;
	}

	static function type_switch($r_type) {
		$return = new stdClass;
		switch ($r_type) {
			default:
			case 'hour':
				$return->report_type = 'daily_electric_kWh_usage_by_hour';
				$return->report_description = "CONCAT(report_history.year, '-', report_history.month, '-', report_history.day, ' (kWh usage by hour)')";
				break;

			case 'eod':
				$return->report_type = 'end_of_day';
				$return->report_description = "CONCAT(report_history.year, '-', report_history.month, '-', report_history.day, ' (Daily Summary)')";
				break;

			case 'eow':
				$return->report_type = 'weekly_electric_kWh_usage_by_day';
				$return->report_description = "CONCAT(report_history.year, '-', report_history.month, ' Week: ', report_history.week_number, ' (End of week)')";
				break;

			case 'eom':
				$return->report_type = 'end_of_month';
				$return->report_description = "CONCAT(report_history.year, '-', report_history.month, ' (End of Month)')";
				break;

			case 'eoy':
				$return->report_type = 'end_of_year';
				$return->report_description = "CONCAT(report_history.year, ' (End of Year)')";
				break;
		}
		return $return;
	}

}
