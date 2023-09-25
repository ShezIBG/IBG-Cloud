<?php

class App {

	const MAIN_PAGE_HOME = 'home';
	const MAIN_PAGE_SETTINGS = 'settings';
	const MAIN_PAGE_ADMIN = 'admin';
	const MAIN_PAGE_SALES = 'sales';
	const MAIN_PAGE_ISP = 'isp';
	const MAIN_PAGE_STOCK = 'stock';
	const MAIN_PAGE_TENANTS = 'tenants';
	const MAIN_PAGE_LIGHTING = 'lighting';
	const MAIN_PAGE_BILLING = 'billing';
	const MAIN_PAGE_CONTROL = 'control';
	const MAIN_PAGE_MULTI = 'multisense';

	private static $_now = null;
	private static $_dbs = [];

	public static function monitoring_sql($id, $db_name = '') {
		$record = self::select('monitoring_server', $id);
		if(!$record) return null;

		if(!$db_name) $db_name = $record['db_name'];

		try {
			$monitor_ip = $record['ip_address'];
			$password = DB_PASSWORD;
			if($monitor_ip !== '109.74.202.153') $password = DB_PASSWORD.DB_PASSWORD;
			return new MySQL($monitor_ip, $db_name, DB_USER, $password, $record['db_port'], true);
		} catch(Exception $ex) { }

		return null;
	}

	public static function knx_sql($building_id) {
		$building_id = App::escape($building_id);
		$record = self::sql()->query_row("SELECT * FROM knx_server WHERE building_id = '$building_id';", MySQL::QUERY_ASSOC);
		if(!$record) return null;

		$db_name = $record['db_name'];

		try {
			$monitor_ip = $record['ip_address'];
			$password = DB_PASSWORD;
			if($monitor_ip !== '109.74.202.153') $password = DB_PASSWORD.DB_PASSWORD;
			return new MySQL($monitor_ip, $db_name, DB_USER, $password, $record['db_port'], true);
		} catch(Exception $ex) { }

		return null;
	}

	public static function sql($type = 'app') {
		if(isset(self::$_dbs[$type])) return self::$_dbs[$type];

		if(substr($type, 0, 4) === 'knx:') {
			// Format: 'knx:building_id'
			$mid = explode(':', $type)[1];
			if(!$mid) return null;

			$db = App::knx_sql($mid);
			if(!$db) return null;
		} else if(substr($type, 0, 11) === 'monitoring:') {
			// Format: 'monitoring:server_id'
			$mid = explode(':', $type)[1];
			if(!$mid) return null;

			$db = App::monitoring_sql($mid);
			if(!$db) return null;
		} else {
			$db_host = '';
			$db_name = '';
			$db_user = '';
			$db_password = '';

			switch($type) {
				case 'app':
					if(DB_HOST) {
						$db_host = DB_HOST;
						$db_user = DB_USER;
						$db_password = DB_PASSWORD;
						$db_name = DB_NAME;
					}
					break;

				case 'isp':
					if(ISP_DB_HOST) {
						$db_host = ISP_DB_HOST;
						$db_user = ISP_DB_USER;
						$db_password = ISP_DB_PASSWORD;
					} else {
						$db_host = DB_HOST;
						$db_user = DB_USER;
						$db_password = DB_PASSWORD;
					}
					$db_name = ISP_DB_NAME;
					break;

				case 'climate':
					if(CLIMATE_DB_HOST) {
						$db_host = CLIMATE_DB_HOST;
						$db_user = CLIMATE_DB_USER;
						$db_password = CLIMATE_DB_PASSWORD;
					} else {
						$db_host = DB_HOST;
						$db_user = DB_USER;
						$db_password = DB_PASSWORD;
					}
					$db_name = CLIMATE_DB_NAME;
					break;

				case 'relay':
					if(RELAY_DB_HOST) {
						$db_host = RELAY_DB_HOST;
						$db_user = RELAY_DB_USER;
						$db_password = RELAY_DB_PASSWORD;
					} else {
						$db_host = DB_HOST;
						$db_user = DB_USER;
						$db_password = DB_PASSWORD;
					}
					$db_name = RELAY_DB_NAME;
					break;

				case 'dali':
					if(DALI_DB_HOST) {
						$db_host = DALI_DB_HOST;
						$db_user = DALI_DB_USER;
						$db_password = DALI_DB_PASSWORD;
					} else {
						$db_host = DB_HOST;
						$db_user = DB_USER;
						$db_password = DB_PASSWORD;
					}
					$db_name = DALI_DB_NAME;
					break;
			}

			if(!$db_host) return null;

			$db = new MySQL($db_host, $db_name, $db_user, $db_password, 3306, true);
		}

		self::$_dbs[$type] = $db;
		return $db;
	}

	public static function user() {
		return isset($GLOBALS['user']) ? $GLOBALS['user'] : null;
	}

	/**
	 * Returns a special symbol to denote NOW() in records passed to SQL methods.
	 */
	public static function now() {
		if(self::$_now === null) self::$_now = new stdClass();
		return self::$_now;
	}

	/**
	 * Returns the correct app URL, even in background processes.
	 * Passing the system integrator ID makes sure it returns the correctly branded URL.
	 */
	public static function url($system_integrator_id = '') {
		if(APP_URL) return APP_URL;
		if($system_integrator_id == 4) return 'https://portal.elanet.co.uk';
		return 'https://ibg-uk.cloud';
	}

	public static function rnd_string($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$rnd_string = '';
		for ($i = 0; $i < $length; $i++) {
			$rnd_string .= $characters[rand(0, $charactersLength - 1)];
		}
		return $rnd_string;
	}

	public static function url_base64_encode($str) {
		return strtr(base64_encode($str), [
			'+' => '.',
			'=' => '-',
			'/' => '~'
		]);
	}

	public static function url_base64_decode($str) {
		return base64_decode(strtr($str, [
			'.' => '+',
			'-' => '=',
			'~' => '/'
		]));
	}

	public static function array_to_csv($data, $headers = true) {
		// Generate CSV data from array
		$fh = fopen('php://temp', 'rw'); // don't create a file, attempt to use memory instead

		// write out the headers
		if($headers) fputcsv($fh, array_keys(current($data)));

		// write out the data
		foreach($data as $row) {
			fputcsv($fh, $row);
		}
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);

		return $csv;
	}

	public static function is_mobile() {
		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$result = preg_match('/iphone|ipad|ipod|android|blackberry|mini|windows\sce|palm/i', strtolower($useragent));
		return !!$result;
	}

	/**
	* encode the result to json (used for ajax routines)
	* @param  string $status  status
	* @param  string $message message
	* @param  mixed $data     data
	* @return string          json encoded string
	*/
	public static function encode_result($status = 'OK', $message = '', $data = null) {
		return json_encode([
			'status' => $status,
			'message' => $message ?: ($status == 'OK' ? 'Success' : 'Failed'),
			'data' => $data
		]);
	}

	// Returns a value from $_POST if exists. If an array of field passed, an array is returned.
	// Also applies optional default value, and optionally escapes via MySQL
	public static function post($field, $default = null, $escape = false) {
		if(is_array($field)) {
			return array_map(function($f) use ($default, $escape) { return self::post($f, $default, $escape); }, $field);
		} else {
			if(!empty($_POST[$field])) {
				return $escape ? self::escape($_POST[$field]) : $_POST[$field];
			} else {
				return $default;
			}
		}
	}

	// Returns a value from $_GET if exists. If an array of fields passed, an array is returned.
	// Also applies optional default value, and optionally escapes via MySQL
	public static function get($field, $default = null, $escape = false) {
		if(is_array($field)) {
			return array_map(function($f) use ($default, $escape) { return self::get($f, $default, $escape); }, $field);
		} else {
			if(!empty($_GET[$field])) {
				return $escape ? self::escape($_GET[$field]) : $_GET[$field];
			} else {
				return $default;
			}
		}
	}

	/**
	* Convert a string to friendly SEO string
	* @param  string $text input
	* @return string       output
	*/
	public static function slugify($text) {
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		// trim
		$text = trim($text, '-');

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		return $text ?: 'n-a';
	}

	/**
	* Set default properties of an array
	* @param array $default_structure  The defualt array structure
	* @param array $array_value        The input array
	* @param string $set_to_key_if_fail Default key if input is a string or something
	* @return array                    Returns the right array
	*/
	public static function set_array_prop_def($default_structure, $array_value, $set_to_key_if_fail = "") {
		if ($set_to_key_if_fail != "") {
			if (!is_array($array_value)) {
				if (isset($default_structure[$set_to_key_if_fail]))
					$default_structure[$set_to_key_if_fail] = $array_value;
				return $default_structure;
			}
		}

		foreach($array_value as $key => $value) {
			if (array_key_exists($key, $default_structure)) {
				$default_structure[$key] = $value;
			}
		}
		return $default_structure;
	}

	public static function set_content_type($type = 'application/json; charset=utf-8') {
		header('Content-Type: '.$type);
	}

	public static function new_uid($md5 = false, $length = 11) {
		$rand = function($min, $max) {
			$range = $max - $min;
			if ($range < 1) return $min; // not so random...
			$log = ceil(log($range, 2));
			$bytes = (int) ($log / 8) + 1; // length in bytes
			$bits = (int) $log + 1; // length in bits
			$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
			do {
				$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
				$rnd = $rnd & $filter; // discard irrelevant bits
			} while ($rnd >= $range);
			return $min + $rnd;
		};

		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet) - 1;
		for ($i=0; $i < $length; $i++)
			$token .= $codeAlphabet[$rand(0, $max)];

		return $md5 ? md5($token) : $token;
	}

	/**
	* redirect()
	*
	* @param mixed $location
	* @return
	*/
	public static function redirect($location = null) {
		if (!is_null($location)) {
			header("Location: {$location}");
			exit;
		}
	}

	/**
	* format_datetime()
	*
	* @param mixed $format
	* @param mixed $dt
	* @return
	*/
	public static function format_datetime($format, $dt = '', $src_format = null) {
		if ($src_format) {
			$date = DateTime::createFromFormat($src_format, $dt) ? : new DateTime($dt);
		} else {
			$date = new DateTime($dt);
		}

		return $date->format($format);
	}

	/**
	 * Internal function to check and fix date ranges.
	 * @return [$date_from, $date_to]
	 */
	public static function resolve_date_range($date_from = null, $date_to = null) {
		if(!$date_from) {
			$date_from = date('Y-m-d');
			$date_to = $date_from;
		} else if(!$date_to) {
			$date_to = $date_from;
		}

		// Swap dates if they're in the wrong order
		if(strtotime($date_from) > strtotime($date_to)) {
			list($date_from, $date_to) = [$date_to, $date_from];
		}

		return [$date_from, $date_to];
	}

	public static function timezone($datetime, $tz_from, $tz_to) {
		$dt = new DateTime($datetime, new DateTimeZone($tz_from));
		$dt->setTimezone(new DateTimeZone($tz_to));
		return $dt->format('Y-m-d H:i:s');
	}

	/**
	* Returns approximate text description of time passed. Parameter is the number of seconds passed.
	*
	* @param int $since
	* @return string
	*/
	public static function time_since($since, $ago = false) {
		if($since == 0) return 'just now';
		if(!$since || $since < 0) return '';

		$chunks = [
			[60 * 60 * 24 * 365, 'year'],
			[60 * 60 * 24 * 30, 'month'],
			[60 * 60 * 24 * 7, 'week'],
			[60 * 60 * 24, 'day'],
			[60 * 60, 'hour'],
			[60, 'minute'],
			[1, 'second']
		];

		for ($i = 0, $j = count($chunks); $i < $j; $i++) {
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
			if (($count = floor($since / $seconds)) != 0) break;
		}

		$print = ($count == 1) ? '1 '.$name : "$count {$name}s";
		if ($ago && $print) $print .= ' ago';
		return $print;
	}

	/**
	* clean_str()
	*
	* @param mixed $str_value
	* @return
	*/
	public static function clean_str($str_value, $nl2br = true) {
		if (is_null($str_value)) $str_value = '';
		$new_str = is_string($str_value) ? htmlentities(html_entity_decode($str_value, ENT_QUOTES)) : $str_value;
		return $nl2br ? nl2br(utf8_encode($new_str)) : utf8_encode($new_str);
	}

	// Formats a number, specifying minimum and maximum precision
	public static function format_number($x, $min = 2, $max = 6) {
		if(!is_numeric($x)) $x = 0;
		if(!is_numeric($min)) $min = 0;
		if(!is_numeric($max)) $max = 0;

		$e_min = pow(10, $min);
		$e_max = pow(10, $max);
		$epsilon = pow(10, -8);

		$xmin = $x * $e_min;
		$xmax = $x * $e_max;

		return abs($xmin - floor($xmin)) < $epsilon ? sprintf("%.${min}f", $x) : (abs($xmax - floor($xmax)) > $epsilon ? sprintf("%.${max}f", $x) : $x);
	}

	// Formats a number, specifying minimum and maximum precision, optionally using thousand separators
	public static function format_number_sep($x, $min = 2, $max = 6) {
		$result = self::format_number($x, $min, $max);

		// Count the actual number of decimal places in the output
		$dc = 0;
		if($result) {
			$chunks = explode('.', $result);
			if(isset($chunks[1])) $dc = strlen($chunks[1]) ?: 0;
		}

		return number_format($result, $dc);
	}

	public static function curl_get_data($url, $usr = false) {
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,2);
		if ($usr) curl_setopt($ch, CURLOPT_USERPWD, 'admin:admin');
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	/**
	 * Return JSON sent via POST encoded into associative arrays.
	 */
	public static function json() {
		$input = file_get_contents('php://input');
		return json_decode($input, true);
	}

	/**
	 * Escape the passed value or arrays of values for use in SQL queries.
	 */
	public static function escape($v) {
		if(is_array($v)) {
			return array_map(function($i) { return self::escape($i); }, $v);
		} else if(is_string($v)) {
			return self::sql()->escape($v);
		} else {
			return $v;
		}
	}

	/**
	 * Wraps the passed value or arrays of values for use in SQL queries.
	 * All values will be enclosed in single quotes. Handles special cases for null values and $this->now
	 */
	public static function wrap($v) {
		if(is_array($v)) {
			return array_map(function($i) { return self::wrap($i); }, $v);
		} else {
			if($v === self::now()) return 'NOW()';
			return $v === null ? 'NULL' : "'$v'";
		}
	}

	/**
	 * Executes both escape() and wrap() on the passed data.
	 */
	public static function escape_and_wrap($v) {
		return self::wrap(self::escape($v));
	}

	/**
	 * With $data being an associative array, returns a copy with properties in the $fields list.
	 */
	public static function keep($data, $fields) {
		if(!is_array($data)) return [];
		if(!is_array($fields)) $fields = [$fields];

		$result = [];
		foreach($data as $k => $v) {
			if(in_array($k, $fields)) $result[$k] = $v;
		}
		return $result;
	}

	/**
	 * Returns a copy of $data. Sets $default value for all properties in the $fields list that are NOT in the original array.
	 */
	public static function ensure($data, $fields, $default = null) {
		if(!is_array($fields)) $fields = [$fields];
		if(!is_array($data)) $data = [];

		foreach($fields as $f) {
			if(!isset($data[$f]) || $data[$f] === null) $data[$f] = $default;
		}

		return $data;
	}

	/**
	 * A combination of keep() and ensure(). Returns an array with only the properties specified in the $fields associative array and defaults each property to its passed value.
	 */
	public static function defaults($data, $fields) {
		// TODO: Make it more efficient
		$result = self::keep($data, array_keys($fields));
		foreach($fields as $f => $def) {
			$result = self::ensure($result, $f, $def);
		}
		return $result;
	}

	/**
	 * Updates a single table record by its ID field. To use a different database, use the format 'table@db'.
	 */
	public static function update($table, $id, $record) {
		$type = 'app';
		if(strpos($table, '@') !== false) {
			$chunks = explode('@', $table);
			$table = $chunks[0];
			$type = $chunks[1];
		}

		$record = self::escape_and_wrap($record);
		$id = self::escape_and_wrap($id);
		$fields = [];
		foreach($record as $key => $value) {
			$fields[] = "`$key` = $value";
		}
		$fields = implode(', ', $fields);
		$q = "UPDATE $table SET $fields WHERE `id` = $id;";
		return self::sql($type)->update($q);
	}

	/**
	 * Inserts a table record and returns the generated ID or false. To use a different database, use the format 'table@db'.
	 */
	public static function insert($table, $record) {
		$type = 'app';
		if(strpos($table, '@') !== false) {
			$chunks = explode('@', $table);
			$table = $chunks[0];
			$type = $chunks[1];
		}

		$record = self::escape_and_wrap($record);
		$fields = '`'.implode('`, `', array_keys($record)).'`';
		$values = implode(', ', array_values($record));
		$q = "INSERT INTO $table ($fields) VALUES ($values);";
		return self::sql($type)->insert($q);
	}

	/**
	 * Replaces a table record and returns the generated ID or false. To use a different database, use the format 'table@db'.
	 */
	public static function replace($table, $record) {
		$type = 'app';
		if(strpos($table, '@') !== false) {
			$chunks = explode('@', $table);
			$table = $chunks[0];
			$type = $chunks[1];
		}

		$record = self::escape_and_wrap($record);
		$fields = '`'.implode('`, `', array_keys($record)).'`';
		$values = implode(', ', array_values($record));
		$q = "REPLACE INTO $table ($fields) VALUES ($values);";
		return self::sql($type)->insert($q);
	}

	/**
	 * Same as insert(), but ignores duplicate key errors. To use a different database, use the format 'table@db'.
	 */
	public static function insert_ignore($table, $record) {
		$type = 'app';
		if(strpos($table, '@') !== false) {
			$chunks = explode('@', $table);
			$table = $chunks[0];
			$type = $chunks[1];
		}

		$record = self::escape_and_wrap($record);
		$fields = '`'.implode('`, `', array_keys($record)).'`';
		$values = implode(', ', array_values($record));
		$q = "INSERT IGNORE INTO $table ($fields) VALUES ($values);";
		return self::sql($type)->insert($q);
	}

	/**
	 * Insert record if $id is new, update otherwise. To use a different database, use the format 'table@db'.
	 * @return int|false The $id of the affected row, or false is unsuccessful
	 */
	public static function upsert($table, $id, $record) {
		if($id === 'new') {
			return App::insert($table, $record);
		} else {
			$result = App::update($table, $id, $record);
			return $result ? $id : false;
		}
	}

	/**
	 * Deletes a record from a table by its ID. To use a different database, use the format 'table@db'.
	 */
	public static function delete($table, $id) {
		$type = 'app';
		if(strpos($table, '@') !== false) {
			$chunks = explode('@', $table);
			$table = $chunks[0];
			$type = $chunks[1];
		}

		$id = self::escape_and_wrap($id);
		$q = "DELETE FROM $table WHERE `id` = $id;";
		return self::sql($type)->delete($q);
	}

	/**
	 * Returns a table record by its ID. To use a different database, use the format 'table@db'.
	 */
	public static function select($table, $id) {
		$type = 'app';
		if(strpos($table, '@') !== false) {
			$chunks = explode('@', $table);
			$table = $chunks[0];
			$type = $chunks[1];
		}

		$id = self::escape_and_wrap($id);
		$q = "SELECT * FROM $table WHERE `id` = $id LIMIT 1;";
		return self::sql($type)->query_row($q, MySQL::QUERY_ASSOC, false);
	}

	public static function human_filesize($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}

	public static function safe_string($s) {
		if($s === null) return '';
		return preg_replace('/[^A-Za-z0-9_()\-\s]/', '', $s);
	}

}
