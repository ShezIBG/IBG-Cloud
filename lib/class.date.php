<?php

class Date {

	/**
	* Fuzzy date strings
	*
	* @var  array
	*/
	protected $_time_formats = [
		[60, 'just now'],
		[90, '1 minute'],
		[3600, 'minutes', 60],
		[5400, '1 hour'],
		[86400, 'hours', 3600],
		[129600, '1 day'],
		[604800, 'days', 86400],
		[907200, '1 week'],
		[2628000, 'weeks', 604800],
		[3942000, '1 month'],
		[31536000, 'months', 2628000],
		[47304000, '1 year'],
		[3153600000, 'years', 31536000]
	];

	/**
	* Timestamp
	*
	* @var  DateTime
	*/
	protected $_timestamp = null;

	/**
	* Use current timestamp
	*
	* @return  Date
	*/
	static public function now() {
		return self::with('now');
	}

	/**
	* Specified time
	*
	* @param   int|string  $date
	* @return  Date
	*/
	static public function with($date) {
		return new self($date);
	}

	/**
	* Constructor
	*
	* @param  int|string  $date
	*/
	public function __construct($date) {
		// If the timestamp is not valid, set the timestamp to now
		if (($this->_timestamp = strtotime($date)) === false) {
			$this->_timestamp = strtotime('now');
		}
	}

	/**
	* Pretty formatted date. Yes, this is an actual method. Deal with it!
	*
	* @return  string
	*/
	public function pretty_date() {
		return $this->formatted('F j, Y, g:i A');
	}

	/**
	* Timezone string. Or timezone abbreviation
	*
	* @param   bool  $abbrev
	* @return  string
	*/
	public function timezone($abbrev = false) {
		$abbrev = $abbrev ? 'T' : 'e';

		return $this->formatted($abbrev);
	}

	/**
	* Timezone offset in seconds
	*
	* @return  string
	*/
	public function timezone_offset() {
		return $this->formatted('z');
	}

	/**
	* Difference to Greenwich time (GMT)
	*
	* @param   bool  $colon
	* @return  string
	*/
	public function gmt_diff($colon = false) {
		return $this->formatted($colon ? 'P' : 'O');
	}

	/**
	* Current timestamp
	*
	* @return  int
	*/
	public function timestamp() {
		return (int) $this->formatted('U');
	}

	/**
	* Day
	*
	* @param   bool  $text
	* @param   bool  $full
	* @return  string
	*/
	public function day($text = false, $full = true) {
		if ($text) return $this->day_text($full);
		return $this->day_num($full);
	}

	/**
	* Day of month. Optionally with leading 0
	*
	* @param   bool  $leading
	* @return  string
	*/
	public function day_num($leading = false) {
		return $this->formatted($leading ? 'd' : 'j');
	}

	/**
	* A textual representation of a day
	*
	* @param   bool  $full
	* @return  string
	*/
	public function day_text($full = true) {
		return $this->formatted($full ? 'l' : 'D');
	}

	/**
	* English ordinal suffix for the day of the month, 2 characters
	*
	* @return  string
	*/
	public function ordinal() {
		return $this->formatted('S');
	}

	/**
	* Numeric representation of the day of the week
	*
	* @param   bool  $iso8601
	* @return  string
	*/
	public function day_of_week($iso8601 = false) {
		return $this->formatted($iso8601 ? 'N' : 'w');
	}

	/**
	* The day of the year (starting from 0)
	*
	* @return  string
	*/
	public function day_of_year() {
		return $this->formatted('z');
	}

	/**
	* Number of days in the given month
	*
	* @return  string
	*/
	public function days_in_month() {
		return $this->formatted('t');
	}

	/**
	* Month
	*
	* @param   bool  $text
	* @param   bool  $full
	* @return  string
	*/
	public function month($text = false, $full = true) {
		if ($text) return $this->monthText($full);
		return $this->month_num($full);
	}

	/**
	* Numeric representation of a month
	*
	* @param   bool  $leading
	* @return  string
	*/
	public function month_num($leading = true) {
		return $this->formatted($leading ? 'm' : 'n');
	}

	/**
	* A textual representation of a month
	*
	* @param   bool  $abbrev
	* @return  string
	*/
	public function monthText($abbrev = true) {
		return $this->formatted($abbrev ? 'M' : 'F');
	}

	/**
	* ISO-8601 week number of year, weeks starting on Monday
	*
	* @return  string
	*/
	public function week_of_year() {
		return $this->formatted('W');
	}

	/**
	* Year representation
	*
	* @param   bool  $full
	* @return  string
	*/
	public function year($full = true) {
		return $this->formatted($full ? 'Y' : 'y');
	}

	/**
	* Whether it's a leap year
	*
	* @return  bool
	*/
	public function leapyear() {
		return (bool) $this->formatted('L');
	}

	/**
	* 12 or 24-hour format of an hour with or without leading zeros
	*
	* @param   bool  $leading
	* @param   bool  $military
	* @return  string
	*/
	public function hour($leading = true, $military = true) {
		if ($military) return $this->hour12($leading);
		return $this->hour24($leading);
	}

	/**
	* 12-hour format of an hour with or without leading zeros
	*
	* @param   bool  $leading
	* @return  string
	*/
	public function hour12($leading = true) {
		return $this->formatted($leading ? 'h' : 'g');
	}

	/**
	* 24-hour format of an hour with or without leading zeros
	*
	* @param   bool  $leading
	* @return  string
	*/
	public function hour24($leading = true) {
		return $this->formatted($leading ? 'H' : 'G');
	}

	/**
	* Minutes with leading zeros
	*
	* @return  string
	*/
	public function minutes() {
		return $this->formatted('i');
	}

	/**
	* Seconds with leading zeros
	*
	* @return  string
	*/
	public function seconds() {
		return $this->formatted('s');
	}

	/**
	* Microseconds
	*
	* @return  string
	*/
	public function microseconds() {
		return $this->formatted('u');
	}

	/**
	* Ante meridiem or Post meridiem
	*
	* @param   bool  $upper
	* @return  string
	*/
	public function meridiem($upper = true) {
		return $this->formatted($upper ? 'A' : 'a');
	}

	/**
	* Whether or not the date is in daylight saving time
	*
	* @return  bool
	*/
	public function daylight_saving() {
		return (bool) $this->formatted('I');
	}

	/**
	* ISO 8601 date (eg. 2004-02-12T15:19:21+00:00)
	*
	* @return  string
	*/
	public function iso8601() {
		return $this->formatted('c');
	}

	/**
	* RFC 2822 formatted date (eg. Thu, 21 Dec 2000 16:01:07 +0200)
	*
	* @return  string
	*/
	public function rfc2822() {
		return $this->formatted('r');
	}

	/**
	* Find the age in years
	*
	* @return  int
	*/
	public function age() {
		// Current
		$now = self::now();

		// Differences
		$year_diff  = $now->year() - $this->year();
		$month_diff = $now->month_num() - $this->month_num();
		$day_diff   = $now->day_num() - $this->day_num();

		if ($day_diff < 0 || $month_diff < 0) {
			$year_diff--;
		}

		return $year_diff;
	}

	/**
	* Find MySQL Date format (eg. 2014-02-25 00:00:00)
	*
	* @return string
	*/
	public function mysql() {
		return $this->formatted('Y-m-d H:i:s');
	}

	/**
	* Find the relative date
	*
	* @return  string
	*/
	public function fuzzy() {
		// Difference between now and the passed date
		$diff = time() - $this->_timestamp;

		$val = '';

		// Waaaayyyy to long ago (earlier than 1970)
		if ($this->_timestamp <= 0) {
			// A long time ago... in a galaxy far, far away
			$val = 'a long time ago';
		}

		// Future date
		else if ($diff < 0) {
			$val = 'in the future';
		}

		// Past date
		else {
			// Loop through each format measurement
			foreach ($this->_time_formats as $format) {
				// if the difference from now and passed time is less than first option in format measurement
				if ($diff < $format[0]) {
					// If the format array item has no calculation value
					if (count($format) == 2) {
						$val = $format[1].($format[0] === 60 ? '' : ' ago');
						break;
					}

					// Divide difference by format item value to get number of units
					else {
						$val = ceil($diff / $format[2]).' '.$format[1].' ago';
						break;
					}
				}
			}
		}

		return $val;
	}

	/**
	* Generic date format. See http://php.net/manual/en/function.date.php
	*
	* @param   string  $format
	* @return  mixed
	*/
	public function formatted($format = 'F j, Y, g:i A T') {
		return date($format, $this->_timestamp);
	}

}
