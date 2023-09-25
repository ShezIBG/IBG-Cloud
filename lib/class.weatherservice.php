<?php

class WeatherService {

	const LEVEL_ACTUAL   = 'actual';
	const LEVEL_FORECAST = 'forecast';

	// The FILL level is not a value in the database, but a status to say that there was NO DATA in the database.
	// Marks auto-generated records to fill in plot holes.
	const LEVEL_FILL     = 'fill';

	private $building_id;
	private $latitude = null;
	private $longitude = null;

	public function __construct($building_id) {
		$this->building_id = App::escape($building_id);

		$info = App::sql()->query_row("SELECT latitude, longitude FROM building WHERE id = '$this->building_id';");
		if($info) {
			list($this->latitude, $this->longitude) = [$info->latitude, $info->longitude];
		}
	}

	public function validate() {
		return DARK_SKY_API_KEY && $this->building_id && $this->latitude != null && $this->longitude != null;
	}

	/**
	 * Retrieves weather information from the online weather service and stores it in the database
	 * @param $day is in ISO format (YYYY-MM-DD)
	 */
	private function fetch_weather_data($day) {
		if(!DARK_SKY_API_KEY) return false;
		if($this->latitude == null || $this->longitude == null) return false;

		$date_string = $day . 'T00:00:00';
		$dd = strtotime($date_string);

		//$url = 'https://api.darksky.net/forecast/'.DARK_SKY_API_KEY.'/'.$this->latitude.','.$this->longitude.','.$day.'T00:00:00?units=si';
		$url = 'https://api.pirateweather.net/forecast/'.DARK_SKY_API_KEY.'/'.$this->latitude.','.$this->longitude.','.$dd.'?units=si';
		
		$json = App::curl_get_data($url);
		if(!$json) return false;

		$data = json_decode($json);
		if(json_last_error() !== JSON_ERROR_NONE) return false;

		if(isset($data->daily->data[0])) {
			
			$daily_data = $data->daily->data[0];
			$level = strtotime($day) >= strtotime('today') ? self::LEVEL_FORECAST : self::LEVEL_ACTUAL;

			$rec = [
				'building_id' => $this->building_id,
				'date' => $day,
				'level' => $level
			];

			if(isset($daily_data->sunriseTime)) $rec['sunriseTime'] = date('Y-m-d H:i:s', $daily_data->sunriseTime);
			if(isset($daily_data->sunsetTime)) $rec['sunsetTime'] = date('Y-m-d H:i:s', $daily_data->sunsetTime);
			if(isset($daily_data->temperatureMinTime)) $rec['temperatureMinTime'] = date('Y-m-d H:i:s', $daily_data->temperatureMinTime);
			if(isset($daily_data->temperatureMaxTime)) $rec['temperatureMaxTime'] = date('Y-m-d H:i:s', $daily_data->temperatureMaxTime);
			if(isset($daily_data->apparentTemperatureMinTime)) $rec['apparentTemperatureMinTime'] = date('Y-m-d H:i:s', $daily_data->apparentTemperatureMinTime);
			if(isset($daily_data->apparentTemperatureMaxTime)) $rec['apparentTemperatureMaxTime'] = date('Y-m-d H:i:s', $daily_data->apparentTemperatureMaxTime);
			if(isset($daily_data->summary) && $daily_data->summary !== null) $rec['summary'] = $daily_data->summary;
			if(isset($daily_data->icon) && $daily_data->icon !== null) $rec['icon'] = $daily_data->icon;
			if(isset($daily_data->moonPhase) && $daily_data->moonPhase !== null) $rec['moonPhase'] = $daily_data->moonPhase;
			if(isset($daily_data->precipIntensity) && $daily_data->precipIntensity !== null) $rec['precipIntensity'] = $daily_data->precipIntensity;
			if(isset($daily_data->precipIntensityMax) && $daily_data->precipIntensityMax !== null) $rec['precipIntensityMax'] = $daily_data->precipIntensityMax;
			if(isset($daily_data->precipProbability) && $daily_data->precipProbability !== null) $rec['precipProbability'] = $daily_data->precipProbability;
			if(isset($daily_data->temperatureMin) && $daily_data->temperatureMin !== null) $rec['temperatureMin'] = $daily_data->temperatureMin;
			if(isset($daily_data->temperatureMax) && $daily_data->temperatureMax !== null) $rec['temperatureMax'] = $daily_data->temperatureMax;
			if(isset($daily_data->apparentTemperatureMin) && $daily_data->apparentTemperatureMin !== null) $rec['apparentTemperatureMin'] = $daily_data->apparentTemperatureMin;
			if(isset($daily_data->apparentTemperatureMax) && $daily_data->apparentTemperatureMax !== null) $rec['apparentTemperatureMax'] = $daily_data->apparentTemperatureMax;
			if(isset($daily_data->dewPoint) && $daily_data->dewPoint !== null) $rec['dewPoint'] = $daily_data->dewPoint;
			if(isset($daily_data->humidity) && $daily_data->humidity !== null) $rec['humidity'] = $daily_data->humidity;
			if(isset($daily_data->windSpeed) && $daily_data->windSpeed !== null) $rec['windSpeed'] = $daily_data->windSpeed;
			if(isset($daily_data->windBearing) && $daily_data->windBearing !== null) $rec['windBearing'] = $daily_data->windBearing;
			if(isset($daily_data->visibility) && $daily_data->visibility !== null) $rec['visibility'] = $daily_data->visibility;
			if(isset($daily_data->cloudCover) && $daily_data->cloudCover !== null) $rec['cloudCover'] = $daily_data->cloudCover;
			if(isset($daily_data->pressure) && $daily_data->pressure !== null) $rec['pressure'] = $daily_data->pressure;
			if(isset($daily_data->ozone) && $daily_data->ozone !== null) $rec['ozone'] = $daily_data->ozone;

			App::replace('weather', $rec);
		}

		if(isset($data->hourly->data) && is_array($data->hourly->data)) {
			foreach($data->hourly->data as $hour => $hourly_data) {
				$rec = [
					'building_id' => $this->building_id,
					'date' => $day,
					'hour_of_day' => $hour
				];

				if(isset($hourly_data->summary) && $hourly_data->summary !== null) $rec['summary'] = $hourly_data->summary;
				if(isset($hourly_data->icon) && $hourly_data->icon !== null) $rec['icon'] = $hourly_data->icon;
				if(isset($hourly_data->precipIntensity) && $hourly_data->precipIntensity !== null) $rec['precipIntensity'] = $hourly_data->precipIntensity;
				if(isset($hourly_data->precipProbability) && $hourly_data->precipProbability !== null) $rec['precipProbability'] = $hourly_data->precipProbability;
				if(isset($hourly_data->temperature) && $hourly_data->temperature !== null) $rec['temperature'] = $hourly_data->temperature;
				if(isset($hourly_data->apparentTemperature) && $hourly_data->apparentTemperature !== null) $rec['apparentTemperature'] = $hourly_data->apparentTemperature;
				if(isset($hourly_data->dewPoint) && $hourly_data->dewPoint !== null) $rec['dewPoint'] = $hourly_data->dewPoint;
				if(isset($hourly_data->humidity) && $hourly_data->humidity !== null) $rec['humidity'] = $hourly_data->humidity;
				if(isset($hourly_data->windSpeed) && $hourly_data->windSpeed !== null) $rec['windSpeed'] = $hourly_data->windSpeed;
				if(isset($hourly_data->windBearing) && $hourly_data->windBearing !== null) $rec['windBearing'] = $hourly_data->windBearing;
				if(isset($hourly_data->visibility) && $hourly_data->visibility !== null) $rec['visibility'] = $hourly_data->visibility;
				if(isset($hourly_data->cloudCover) && $hourly_data->cloudCover !== null) $rec['cloudCover'] = $hourly_data->cloudCover;
				if(isset($hourly_data->pressure) && $hourly_data->pressure !== null) $rec['pressure'] = $hourly_data->pressure;
				if(isset($hourly_data->ozone) && $hourly_data->ozone !== null) $rec['ozone'] = $hourly_data->ozone;

				App::replace('weather_hourly', $rec);
			}
		}

		return true;
	}

	/**
	 * Retrieves daily weather information from the database
	 * @param $date_from is in ISO format (YYYY-MM-DD), defaults to today
	 * @param $date_to is in ISO format (YYYY-MM-DD), defaults to $date_from
	 */
	private function query_daily_weather_data($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);
		return App::sql()->query("SELECT * FROM weather WHERE building_id = '$this->building_id' AND date BETWEEN '$date_from' AND '$date_to' ORDER BY date;");
	}

	/**
	 * Retrieves hourly weather information from the database
	 * @param $date_from is in ISO format (YYYY-MM-DD), defaults to today
	 * @param $date_to is in ISO format (YYYY-MM-DD), defaults to $date_from
	 */
	private function query_hourly_weather_data($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);
		return App::sql()->query("SELECT * FROM weather_hourly WHERE building_id = '$this->building_id' AND date BETWEEN '$date_from' AND '$date_to' ORDER BY date, hour_of_day;");
	}

	/**
	 * Makes sure weather information is stored in the database, fetching it from the weather service if needed
	 * @param $date_from is in ISO format (YYYY-MM-DD), defaults to today
	 * @param $date_to is in ISO format (YYYY-MM-DD), defaults to $date_from
	 */
	public function ensure_weather_data($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);
		$data = App::sql()->query("SELECT date, level FROM weather WHERE building_id = '$this->building_id' AND date BETWEEN '$date_from' AND '$date_to';") ?: [];
		$dates = [];
		foreach($data as $row) {
			// Don't allow forecast level data for dates in the past
			if($row->level == self::LEVEL_ACTUAL || strtotime($row->date) >= strtotime('today')) {
				$dates[] = $row->date;
			}
		}

		$all_ok = true;

		// Loop through days and fetch data if needed
		$day = $date_from;
		while(true) {
			if(!in_array($day, $dates)) {
				$result = $this->fetch_weather_data($day);
				$all_ok = $all_ok && $result;
			}
			if($day == $date_to) break;

			$day = date('Y-m-d', strtotime('+1 day', strtotime($day)));
		}

		return $all_ok;
	}

	/**
	 * Query daily weather information from the database, fetching it from the weather service if needed
	 * @param $date_from is in ISO format (YYYY-MM-DD), defaults to today
	 * @param $date_to is in ISO format (YYYY-MM-DD), defaults to $date_from
	 */
	public function get_daily_weather_data($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);
		$this->ensure_weather_data($date_from, $date_to);
		return $this->query_daily_weather_data($date_from, $date_to);
	}

	/**
	 * Query hourly weather information from the database, fetching it from the weather service if needed
	 * @param $date_from is in ISO format (YYYY-MM-DD), defaults to today
	 * @param $date_to is in ISO format (YYYY-MM-DD), defaults to $date_from
	 */
	public function get_hourly_weather_data($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);
		$this->ensure_weather_data($date_from, $date_to);
		return $this->query_hourly_weather_data($date_from, $date_to);
	}

	/**
	 * Query daily weather information from the database, fetching it from the weather service if needed
	 * This function will also fill in the blanks in case of missing data.
	 * @param $date_from is in ISO format (YYYY-MM-DD), defaults to today
	 * @param $date_to is in ISO format (YYYY-MM-DD), defaults to $date_from
	 */
	public function get_daily_weather_plot($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);

		// Initialise plot with all days in range
		$plot = [];
		$day = $date_from;
		while(true) {
			$plot[$day] = false;

			if($day == $date_to) break;
			$day = date('Y-m-d', strtotime('+1 day', strtotime($day)));
		}

		// Fill in values from the database, putting in the first valid record to the $last_data variable
		// $last_data will be used to pad the start of the date range in case there's no valid data there
		$last_data = false;
		$data = $this->get_daily_weather_data($date_from, $date_to);
		if($data) {
			$last_data = $data[0];
			foreach($data as $row) {
				if(isset($row->date) && isset($plot[$row->date])) {
					$plot[$row->date] = $row;
				}
			}
		}

		// If $last_data has not been set, create an empty object
		if(!$last_data) {
			$last_data = (object)[
				'building_id' => $this->building_id,
				'date' => '',
				'level' => self::LEVEL_FILL,
				'summary' => '',
				'icon' => '',
				'sunriseTime' => '',
				'sunsetTime' => '',
				'moonPhase' => 0,
				'precipIntensity' => 0,
				'precipIntensityMax' => 0,
				'precipProbability' => 0,
				'temperatureMin' => 0,
				'temperatureMinTime' => '',
				'temperatureMax' => 0,
				'temperatureMaxTime' => '',
				'apparentTemperatureMin' => 0,
				'apparentTemperatureMinTime' => '',
				'apparentTemperatureMax' => 0,
				'apparentTemperatureMaxTime' => '',
				'dewPoint' => 0,
				'humidity' => 0,
				'windSpeed' => 0,
				'windBearing' => 0,
				'visibility' => 0,
				'cloudCover' => 0,
				'pressure' => 0,
				'ozone' => 0
			];
		}

		// Loop through days and fill in the holes
		foreach($plot as $day => $data) {
			if(!$data) {
				$last_data = clone $last_data;
				$last_data->date = $day;
				$last_data->level = self::LEVEL_FILL;
				$last_data->sunriseTime = "$day 00:00:00";
				$last_data->sunsetTime = "$day 00:00:00";
				$last_data->temperatureMinTime = "$day 00:00:00";
				$last_data->temperatureMaxTime = "$day 00:00:00";
				$last_data->apparentTemperatureMinTime = "$day 00:00:00";
				$last_data->apparentTemperatureMaxTime = "$day 00:00:00";
				$plot[$day] = $last_data;
			} else {
				$last_data = $data;
			}
		}

		return $plot;
	}

	/**
	 * Query hourly weather information from the database, fetching it from the weather service if needed
	 * This function will also fill in the blanks in case of missing data.
	 * @param $date_from is in ISO format (YYYY-MM-DD), defaults to today
	 * @param $date_to is in ISO format (YYYY-MM-DD), defaults to $date_from
	 */
	public function get_hourly_weather_plot($date_from = null, $date_to = null) {
		list($date_from, $date_to) = App::resolve_date_range($date_from, $date_to);

		// Initialise plot with all days and hours in range
		$plot = [];
		$day = $date_from;
		while(true) {
			$plot[$day] = array_fill(0, 24, false);

			if($day == $date_to) break;
			$day = date('Y-m-d', strtotime('+1 day', strtotime($day)));
		}

		// Fill in values from the database, putting in the first valid record to the $last_data variable
		// $last_data will be used to pad the start of the date range in case there's no valid data there
		$last_data = false;
		$data = $this->get_hourly_weather_data($date_from, $date_to);
		if($data) {
			$last_data = $data[0];
			foreach($data as $row) {
				if(isset($row->date) && isset($row->hour_of_day) && isset($plot[$row->date]) && is_array($plot[$row->date])) {
					$plot[$row->date][$row->hour_of_day] = $row;
				}
			}
		}

		// If $last_data has not been set, create an empty object
		if(!$last_data) {
			$last_data = (object)[
				'building_id' => $this->building_id,
				'date' => '',
				'hour_of_day' => 0,
				'summary' => '',
				'icon' => '',
				'precipIntensity' => 0,
				'precipProbability' => 0,
				'temperature' => 0,
				'apparentTemperature' => 0,
				'dewPoint' => 0,
				'humidity' => 0,
				'windSpeed' => 0,
				'windBearing' => 0,
				'visibility' => 0,
				'cloudCover' => 0,
				'pressure' => 0,
				'ozone' => 0
			];
		}

		// Loop through days/hours and fill in the holes
		foreach($plot as $day => $day_data) {
			foreach($day_data as $hour => $data) {
				if(!$data) {
					$last_data = clone $last_data;
					$last_data->date = $day;
					$last_data->hour_of_day = $hour;
					$plot[$day][$hour] = $last_data;
				} else {
					$last_data = $data;
				}
			}
		}

		return $plot;
	}
}