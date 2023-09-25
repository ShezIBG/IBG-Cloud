<?php

class EMLight {

	const EMERGENCY_FUNCTION_PERIOD = 31;
	const EMERGENCY_DURATION_PERIOD = 365;

	public static function details_query($condition = '') {
		if($condition) $condition = "WHERE $condition";

		$max_function_test_age = self::EMERGENCY_FUNCTION_PERIOD;
		$max_duration_test_age = self::EMERGENCY_DURATION_PERIOD;

		// Statuses:
		// -1 - Fail
		//  0 - Warning
		//  1 - Pass

		$q = "SELECT
				*,
				COALESCE(IF(function_test_age IS NULL OR function_test_age > duration_test_age, CAST(dt_circuit_failure AS SIGNED) * -2 + 1, CAST(ft_circuit_failure AS SIGNED) * -2 + 1), 0)                   AS circuit_failure,
				COALESCE(IF(function_test_age IS NULL OR function_test_age > duration_test_age, CAST(dt_battery_duration_failure AS SIGNED) * -2 + 1, CAST(ft_battery_duration_failure AS SIGNED) * -2 + 1), 0) AS battery_duration_failure,
				COALESCE(IF(function_test_age IS NULL OR function_test_age > duration_test_age, CAST(dt_battery_failure AS SIGNED) * -2 + 1, CAST(ft_battery_failure AS SIGNED) * -2 + 1), 0)                   AS battery_failure,
				COALESCE(IF(function_test_age IS NULL OR function_test_age > duration_test_age, CAST(dt_emergency_lamp_failure AS SIGNED) * -2 + 1, CAST(ft_emergency_lamp_failure AS SIGNED) * -2 + 1), 0)     AS emergency_lamp_failure,

				LEAST(function_test_status, function_test_age_ok, duration_test_status, duration_test_age_ok, has_group) AS light_status
			FROM
				(
					SELECT
						l.*,
						a.description AS area_description, a.display_order AS area_display_order,
						f.id AS floor_id, f.description AS floor_description, f.display_order AS floor_display_order,
						b.id AS building_id, b.description AS building_description, b.timezone AS building_timezone,
						g.description AS group_description,
						t.description AS type_description, t.icon AS type_icon,
						fpi.floorplan_id, fpi.x, fpi.y, fpi.direction,

						/* Original result fields */

						r.function_test_finished_datetime,
						r.ft_function_test_done_and_result_is_valid,
						r.ft_circuit_failure,
						r.ft_battery_duration_failure,
						r.ft_battery_failure,
						r.ft_emergency_lamp_failure,
						r.ft_function_test_failed,

						r.duration_test_finished_datetime,
						r.dt_duration_test_done_and_result_is_valid,
						r.dt_circuit_failure,
						r.dt_battery_duration_failure,
						r.dt_battery_failure,
						r.dt_emergency_lamp_failure,
						r.dt_duration_test_failed,

						r.rated_duration_in_mins,
						r.total_lamp_emergency_time_in_hours_max_255,
						r.total_lamp_operating_time_in_4_hour_steps_max_255,
						r.test_stopped,

						/* Calculated result fields */

						DATEDIFF(NOW(), r.function_test_finished_datetime) AS function_test_age,
						DATEDIFF(NOW(), r.duration_test_finished_datetime) AS duration_test_age,

						IF(DATEDIFF(NOW(), r.function_test_finished_datetime) <= '$max_function_test_age', 1, 0) AS function_test_age_ok,
						IF(DATEDIFF(NOW(), r.duration_test_finished_datetime) <= '$max_duration_test_age', 1, 0) AS duration_test_age_ok,

						IF(r.ft_function_test_done_and_result_is_valid = 1, IF(r.ft_function_test_failed = 1, -1, 1), 0) AS function_test_status,
						IF(r.dt_duration_test_done_and_result_is_valid = 1, IF(r.dt_duration_test_failed = 1, -1, 1), 0) AS duration_test_status,

						IF(l.group_id IS NOT NULL, 1, 0) AS has_group,
						IF(mft.manual = 1, 1, 0) AS has_manual_function,
						IF(mdt.manual = 1, 1, 0) AS has_manual_duration,

						sft.datetime AS scheduled_function_datetime,
						sdt.datetime AS scheduled_duration_datetime,
						mft.datetime AS manual_function_datetime,
						mdt.datetime AS manual_duration_datetime

					FROM em_light AS l
					JOIN area AS a ON a.id = l.area_id
					JOIN floor AS f ON f.id = a.floor_id
					JOIN building AS b ON b.id = f.building_id
					LEFT JOIN em_result AS r ON r.em_light_id = l.id
					LEFT JOIN em_light_group AS g ON g.id = l.group_id
					LEFT JOIN em_light_type AS t ON t.id = l.type_id
					LEFT JOIN floorplan_item AS fpi ON fpi.item_type = 'em_light' AND fpi.item_id = l.id
					LEFT JOIN em_schedule AS mft ON mft.em_light_id = l.id AND mft.manual = 1 AND mft.test_type = 'function'
					LEFT JOIN em_schedule AS mdt ON mdt.em_light_id = l.id AND mdt.manual = 1 AND mdt.test_type = 'duration'
					LEFT JOIN em_schedule AS sft ON sft.em_light_id = l.id AND sft.manual = 0 AND sft.test_type = 'function'
					LEFT JOIN em_schedule AS sdt ON sdt.em_light_id = l.id AND sdt.manual = 0 AND sdt.test_type = 'duration'
					$condition
				) AS info
		";

		return $q;
	}

	public static function get_log_with_details($condition) {
		$q = "SELECT
			log.*,
			u.name AS user_name,
			u.email_addr AS user_email,
			l.description AS light_description,
			l.zone_number AS light_zone_number,
			g.description AS group_description,
			b.description AS building_description,
			b.timezone AS building_timezone
			FROM em_log AS log
			JOIN userdb AS u ON u.id = log.user_id
			LEFT JOIN em_light AS l ON l.id = log.light_id
			LEFT JOIN em_light_group AS g ON g.id = log.group_id
			LEFT JOIN building AS b ON b.id = log.building_id
			$condition;";

		$result = [];

		$r = App::sql()->query($q) ?: [];
		foreach($r as $l) {
			$ld = htmlentities($l->light_description);
			if($l->light_zone_number) $ld .= ' <span class="subtitle">'.htmlentities("$l->light_zone_number").'</span>';
			$gd = htmlentities($l->group_description);
			$nt = htmlentities($l->notes);

			switch($l->event) {
				case 'group_create':
					$log_html = "Group <b>$gd</b> has been created.";
					$log_icon = 'md md-add';
					break;

				case 'group_delete':
					$log_html = "Group <b>$gd</b> has beed deleted.";
					$log_icon = 'md md-delete';
					break;

				case 'light_assign':
					$log_html = "Light <b>$ld</b> has been assigned to group <b>$gd</b>.";
					$log_icon = 'md md-add-circle';
					break;

				case 'light_unassign':
					$log_html = "Light <b>$ld</b> has been unassigned from group.";
					$log_icon = 'md md-remove-circle';
					break;

				case 'light_repair':
					$log_html = "Light <b>$ld</b> repaired: <b>$nt</b>";
					$log_icon = 'md md-done';
					break;

				default:
					continue;
			}

			$result[] = [
				'id' => $l->id,
				'building_id' => $l->building_id,
				'client_id' => $l->client_id,
				'user_id' => $l->user_id,
				'datetime' => App::timezone($l->datetime, 'UTC', $l->building_timezone),
				'light_id' => $l->light_id,
				'group_id' => $l->group_id,
				'event' => $l->event,
				'notes' => $l->notes,
				'user_name' => $l->user_name,
				'user_email' => $l->user_email,
				'light_description' => $l->light_description,
				'group_description' => $l->group_description,
				'building_description' => $l->building_description,
				'log_html' => $log_html,
				'log_icon' => $log_icon
			];
		}

		return $result;
	}

}
