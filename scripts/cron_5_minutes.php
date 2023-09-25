#!/usr/bin/php -q
<?php

/*
 * This process should be run every 5 minutes as a cron job
 */

include '../inc/init.app.php';

MySQL::$clean = false;

echo "---------------\n";
echo "GATEWAY MONITOR\n";
echo "---------------\n\n";

echo "Connecting to monitoring servers...\n\n";

$sql = App::sql();

$dbs = [];
$dbnames = [];
$servers = $sql->query("SELECT * FROM monitoring_server;") ?: [];
foreach($servers as $server) {
	echo "$server->desc: ";
	$dbs[$server->id] = App::monitoring_sql($server->id);
	$dbnames[$server->id] = $server->db_name;
	echo "OK\n";
}

echo "\nProcessing collectors...\n\n";

$table_content = '';
$is_recovery = false;
$is_failure = false;

// Don't process DLC64 collectors for now
$gateways = $sql->query("SELECT * FROM gateway WHERE monitoring_server_id IS NOT NULL AND LENGTH(pi_serial) = 16 AND type <> 'DLC64';") ?: [];
foreach($gateways as $g) {
	echo "$g->pi_serial\t";

	$data_received = null;
	$status = 'error';
	$message = '';

	// Set how often we should receive data by collector type (in seconds)
	$timeout = 1800;
	if($g->type === 'MB30' || $g->type === 'RS32' || $g->type === 'MAR10') $timeout = 3600;

	$gateway_status = $sql->query_row("SELECT * FROM gateway_status WHERE gateway_id = '$g->id';");

	if(isset($dbs[$g->monitoring_server_id]) && $dbs[$g->monitoring_server_id] !== null) {
		$db = $dbs[$g->monitoring_server_id];
		$dbname = $dbnames[$g->monitoring_server_id];

		try {
			// Find table name(s) by pi serial
			$tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_name LIKE '%{$g->pi_serial}' AND table_schema = '$dbname';");

			if($tables) {
				// Find the latest record across ALL tables found
				$tq = [];
				foreach($tables as $t) {
					$tq[] = "(SELECT datetime FROM `$t->table_name` ORDER BY datetime DESC LIMIT 1)";
				}
				$tq = implode(' UNION ', $tq);
				$r = $dbs[$g->monitoring_server_id]->query_row("SELECT datetime, TIMESTAMPDIFF(SECOND, datetime, NOW()) as diff FROM ($tq) AS combined ORDER BY datetime DESC LIMIT 1;");

				if($r && $r->datetime) {
					$data_received = $r->datetime;
					if($data_received) {
						if($r->diff < -900) {
							$message = "Data from the future.";
						} else if($r->diff < $timeout) {
							$status = 'ok';
							$message = "OK";
						} else {
							$message = "Data stream stopped.";
						}
					} else {
						$message = "Empty timestamp in monitoring table.";
					}
				} else {
					$message = "No data in monitoring table.";
				}
			} else {
				$message = "No monitoring tables found.";
			}
		} catch(Exception $e) {
			$message = $e->getMessage();
		}
	} else {
		$message = "Cannot connect to monitoring server.";
	}

	$status_changed = $gateway_status ? $message != $gateway_status->message : true;

	$escaped_message = $message;
	$escaped_message = $sql->escape($escaped_message);

	if($gateway_status) {
		// Update
		$q = "UPDATE gateway_status SET last_checked = NOW()";
		if($data_received) $q .= ", last_data_received = '$data_received'";
		if($status_changed) $q .= ", last_status_change = NOW()";
		$q .= ", status = '$status', message = '$escaped_message' WHERE gateway_id = '$g->id';";

		$sql->update($q);
	} else {
		// Initial check, insert
		$sql->insert("INSERT INTO gateway_status(gateway_id, last_checked, last_data_received, last_status_change, status, message) VALUES ('$g->id', NOW(), ".($data_received ? "'$data_received'" : 'NULL' ).", NOW(), '$status', '$escaped_message');");
	}

	if($status_changed) {
		$sql->insert("INSERT INTO gateway_status_history(gateway_id, datetime, status, message) VALUES ('$g->id', NOW(), '$status', '$escaped_message');");

		$desc = $sql->query_row(
			"SELECT
				c.name AS client,
				b.description AS building,
				f.description AS floor,
				a.description AS area,
				g.description AS gateway,
				g.pi_serial AS gateway_serial,
				IF(gs.last_checked IS NULL, NULL, TIMESTAMPDIFF(SECOND, gs.last_checked, NOW())) AS last_checked,
				gs.last_data_received AS last_received,
				gs.message AS message,
				g.type AS gateway_type
			FROM gateway_status AS gs
			JOIN gateway AS g ON g.id = gs.gateway_id
			JOIN area AS a ON a.id = g.area_id
			JOIN floor AS f ON f.id = a.floor_id
			JOIN building AS b ON b.id = f.building_id
			JOIN client AS c ON c.id = b.client_id
			WHERE gs.ignore <> 1 AND gs.gateway_id = '$g->id'
			");

		if($desc) {
			$last_checked = App::time_since($desc->last_checked, true);
			$table_content .= '<tr style="color:'.($desc->message == 'OK' ? '#A2B83A' : '#E84F32').'">';
			$table_content .= '<td style="vertical-align:top;">'.$desc->client.'</td>';
			$table_content .= '<td style="vertical-align:top;">'.$desc->building.'<br><span style="font-size:80%;color:grey">'.$desc->floor.' / '.$desc->area.'</span></td>';
			$table_content .= '<td style="vertical-align:top;">'.$desc->gateway.'<br><span style="font-size:80%;color:grey">'.$desc->gateway_serial.'</span><br><span style="font-size:80%;color:grey">'.$desc->gateway_type.'</span></td>';
			$table_content .= '<td style="vertical-align:top;"><b>'.$desc->message.'</b><br>'.($last_checked ? '<span style="font-size:80%;color:grey">Last checked: '.$last_checked.'</span><br>' : '').'<span style="font-size:80%;color:grey">Last data received: '.($desc->last_received ?: 'None').'</span></td>';
			$table_content .= '</tr>';

			if($status == 'ok') {
				$is_recovery = true;
			} else {
				$is_failure = true;
			}
		}
	}

	echo $message."\n";
}

if($table_content) {
	$subj = 'STATUS CHANGES';
	if($is_recovery && !$is_failure) $subj = 'RECOVERY';
	if($is_failure && !$is_recovery) $subj = 'FAILURE';

	$date = new DateTime();
	$dt = $date->format('Y-m-d H:i:s');
	$subj .= ' - '.$dt;

	$body = '
		<h2>EticomCloud Collector Monitor</h2>
		<h3>'.$subj.'</h3>
		<table>'.$table_content.'</table>
	';
	//
	$mailer = new Mailer();
	$from = $mailer->get_default_from();
	$mailer->email($from, [ 'lee.roche@eticom.co.uk', 'john.wild@eticom.co.uk', 'robert.biro@eticom.co.uk', 'nikolas.giannakis@eticom.co.uk' ], '[Collector Monitor] '.$subj, $body);
	unset($mailer);
}

echo "\nCleaning up database connections... ";

foreach($dbs as $key => $val) {
	unset($val);
	unset($dbs[$key]);
}

echo "done.\n";

echo "\n--------\n";
echo "Finished\n";
echo "--------\n";
