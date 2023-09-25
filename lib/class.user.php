<?php

class User {

	const USER_EVENT_PASSWORD_CHANGE = 'PASSWORD_CHANGE';

	public $id;
	public $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM userdb WHERE id = '$id' AND active = 1");
	}

	public static function create($email) {
		$email = App::escape(strtolower($email));
		$user = App::sql()->query_row("SELECT id FROM userdb WHERE email_addr = '$email' LIMIT 1;");
		if (!$user) return false;

		return new self($user->id);
	}

	private static function check_auth($email, $pword) {
		$email = App::escape(strtolower($email));
		$user_data = App::sql()->query_row("SELECT id FROM userdb WHERE email_addr = '" . $email . "' AND active = 1");
		if (!$user_data) return false;

		$user = new User($user_data->id);
		if (!password_verify($pword, $user->info->password)) return false;
		if (Permission::any([ 'user' => $user ])->has_no_access()) return false;

		return $user;
	}

	public static function noaccess_check_auth($email, $pword) {
		$email = App::escape(strtolower($email));
		$user_data = App::sql()->query_row("SELECT id FROM userdb WHERE email_addr = '" . $email . "' AND active = 1");
		if (!$user_data) return false;

		$user = new User($user_data->id);
		if (!password_verify($pword, $user->info->password)) return false;
		if (Permission::any([ 'user' => $user ])->has_no_access()) return 'No_access';

		return $user;
	}


	public static function do_login($email, $pword) {
		$user = self::check_auth($email, $pword);
		if (!$user) return false;

		$user->init_user_session();
		return $user;
	}

	public static function check_tenant($id) {
		
		$user_role_id = App::sql()->query_row("SELECT * FROM user_role_assignment WHERE user_id = '$id';");
		$user_role_id = $user_role_id->user_role_id;
		
		$user_tenat = App::sql()->query_row("SELECT * FROM user_role WHERE id = '$user_role_id';");

		return $user_tenat;
	}

	public static function check_ba_newcentury($id) {
		
		$user_role_id = App::sql()->query_row("SELECT * FROM user_role_assignment WHERE user_id = '$id';");
		$user_role_id = $user_role_id->user_role_id;
		
		$user_nc = App::sql()->query_row("SELECT * FROM user_role WHERE id = '$user_role_id';");

		return $user_nc;
	}

	public function init_user_session() {
		// Reset custom dashboard dates to yesterday after login
		App::sql()->update("UPDATE dashboard SET time_period = 'yesterday' WHERE time_period LIKE 'today_minus_%' AND user_id = '$this->id';");

		$_SESSION[SESSION_NAME_USER_ID] = $this->id;
		$_SESSION[SESSION_NAME_PASSWORD] = $this->info->password;
		//$user->launch_home_page();
		App::sql()->insert("INSERT INTO login_logs(user_id) values('" . $this->id . "')");
	}

	public function insert_event($description, $event, $reference = '') {
		$description = App::escape($description);
		$reference = App::escape($reference);
		return App::sql()->insert("INSERT INTO user_event (user_id, event, description, reference) VALUES($this->id, '$event', '$description', '$reference')");
	}

	public function reset_password() {
		// Generate reset token
		$token_id = $this->create_token('reset');

		// Send password reset email

		$base_reset_url = APP_URL.'/v3/auth/reset';
		$reset_url = $base_reset_url.'/'.urlencode($token_id);

		$brand_name = 'Eticom';
		if(BRANDING === 'elanet') $brand_name = 'Elanet';

		$body = '
			<p>Hi '.$this->info->name.',</p>
			<p>We have received a password reset request for your account. You can use the following link within the next day to reset your password:</p>
			<p><a href="'.$reset_url.'">'.$reset_url.'</a></p>
			<p>If you don\'t use this link within 24 hours, it will expire. To get a new password reset link, visit <a href="'.$base_reset_url.'">'.$base_reset_url.'</a></p>
			<p>If you didn\'t make the request, please ignore this email.</p>

			<p>
			Thanks,<br>
			The '.$brand_name.' Team
			</p>
		';

		$mailer = new Mailer();
		$from = $mailer->get_default_from($brand_name);
		return $mailer->email($from, $this->info->email_addr, "$brand_name password reset", $body);
	}

	public static function check_login_session($redirect_if_fail = true, $set_response_code = false) {
		$user = false;
		if (isset($_SESSION[SESSION_NAME_USER_ID])) {
			$user_id = $_SESSION[SESSION_NAME_USER_ID];
			$pword = $_SESSION[SESSION_NAME_PASSWORD];

			$user = new User($user_id);
			if($user->validate()) {
				if($user->info->password !== $pword) $user = false;
			} else {
				$user = false;
			}
		}

		if (!$user) {
			if ($redirect_if_fail) App::redirect(APP_URL . "/auth?r=" . urlencode($_SERVER["REQUEST_URI"]));
			if ($set_response_code) http_response_code(401);
		} else {
			if ($user->validate()) {
				return $user;
			} else {
				if ($redirect_if_fail) App::redirect(APP_URL . "/auth?r=" . urlencode($_SERVER["REQUEST_URI"]));
				if ($set_response_code) http_response_code(401);
			}
		}
	}

	public static function Current_user(){
		$user_id = $_SESSION[SESSION_NAME_USER_ID];
		return $user_id;
	} 


	public static function check_user_login($userid) {
		$user = new User($userid);
		$user->validate();

		if (!$user) {
			return null;
		} else {
			if ($user->validate()) {
				return $user;
			} else {
				return null;
			}
		}
	}


	public static function logout() {
		$cookie_time = (3600 * 24 * 30); // 30 days
		setcookie(AUTH_COOKIE_NAME, '', time() - $cookie_time, '/');
		$_SESSION[SESSION_NAME_USER_ID] = false;
		$_SESSION[SESSION_NAME_PASSWORD] = false;
		session_unset();
		session_destroy();
	}

	public function launch_home_page() {
		App::redirect(APP_URL . "/");
	}

	public function evaluate_billing_account() {
		// TODO: URLs need to be updated when getting rid of all old stuff.

		// Get the highest role set for the user
		// Note: it ignores not_signed contract, as that feature is currently Elanet (ISP) only.
		$contract = App::sql()->query_row(
			"SELECT
				c.id, c.owner_type, c.owner_id, c.customer_type, c.customer_id, c.status, ci.last_due_date, ci.card_payment_gateway, ci.dd_payment_gateway, ci.frequency, ci.id AS contract_invoice_id
			FROM user_role_assignment AS ura
			JOIN contract AS c ON c.customer_type = ura.assigned_level AND c.customer_id = ura.assigned_id AND c.provides_access = 1 AND c.status IN ('unconfirmed', 'pending', 'active', 'ending')
			JOIN payment_account AS pa ON pa.owner_type = c.owner_type AND pa.owner_id = c.owner_id AND pa.customer_type = c.customer_type AND pa.customer_id = c.customer_id
			LEFT JOIN contract_invoice AS ci ON ci.contract_id = c.id
			WHERE ura.user_id = '$this->id' AND ura.user_role_id <> 0 AND ura.assigned_level IN ('SI', 'HG', 'C')
			ORDER BY ura.assigned_level, c.id DESC
			LIMIT 1;
		", MySQL::QUERY_ASSOC);

		if(!$contract) {
			// No billing restrictions
			return [ 'ok' => true ];
		}

		// Found a customer account related to the current user.
		// Check if the customer's account is in good standing.

		$result = [ 'ok' => true ];

		$pa = new PaymentAccount($contract['owner_type'], $contract['owner_id'], $contract['customer_type'], $contract['customer_id']);
		$outstanding = $pa->get_outstanding_pence() / 100;

		$frequency = $contract['frequency'] === 'annual' ? 'pa' : 'pm';

		if($contract['status'] === 'unconfirmed') {
			// Customer has an unconfirmed contract that needs starting

			$line_data = App::sql()->query("SELECT * FROM contract_invoice_line WHERE contract_invoice_id = '$contract[contract_invoice_id]';", MySQL::QUERY_ASSOC);
			$lines = [];
			$total = 0;

			foreach($line_data as $l) {
				switch($l['type']) {
					case 'isp_routers':
						$lines[] = [
							'description' => $l['description'],
							'amount' => $l['unit_price'],
							'extra' => 'each'
						];
						break;

					case 'custom':
						$amount = $l['unit_price'] * $l['quantity'];
						$total += $amount;
						$lines[] = [
							'description' => $l['description'],
							'amount' => $amount,
							'extra' => ''
						];
						break;
				}
			}

			$result = [
				'ok' => false,
				'redirect_url' => APP_URL.'/v3/auth/start-subscription',
				'redirect_route' => ['auth', 'start-subscription'],
				'contract_id' => $contract['id'],
				'outstanding' => $outstanding,
				'pa_id' => $pa->id,
				'card_payment_gateway' => $contract['card_payment_gateway'],
				'dd_payment_gateway' => $contract['dd_payment_gateway'],
				'frequency' => $frequency,
				'lines' => $lines,
				'total' => $total,
				'account_url' => $pa->get_account_url()
			];
		} else {
			if($outstanding > 0 && $contract['last_due_date'] && strtotime($contract['last_due_date']) < strtotime(date('Y-m-d'))) {
				// Customer owes us money
				$result = [
					'ok' => false,
					'redirect_url' => APP_URL.'/v3/auth/payment-overdue',
					'redirect_route' => ['auth', 'payment-overdue'],
					'contract_id' => $contract['id'],
					'outstanding' => $outstanding,
					'pa_id' => $pa->id,
					'card_payment_gateway' => $contract['card_payment_gateway'],
					'dd_payment_gateway' => $contract['dd_payment_gateway'],
					'frequency' => $frequency,
					'lines' => [], // We don't need the lines here
					'total' => 0, // We don't need the total here
					'account_url' => $pa->get_account_url()
				];
			}
		}

		// User has to be admin to reach payment pages
		if(!$result['ok'] && !Permission::get($contract['customer_type'], $contract['customer_id'])->check(Permission::ADMIN)) {
			User::logout();
			$result = [
				'ok' => false,
				'redirect_url' => APP_URL.'/v3/auth/access-denied',
				'redirect_route' => ['auth', 'access-denied']
			];
		}

		return $result;
	}

	public function validate($filters = [ 'active' => 1 ]) {
		$is_valid = !!$this->info;
		if (!$is_valid) return false;

		if($filters) {
			foreach($filters as $filter => $value) {
				if (isset($this->info->{$filter}) && $this->info->{$filter} != $value) {
					$is_valid = false;
					break;
				}
			}
		}

		return $is_valid;
	}

	public function get_dashboard($type = Dashboard::DASHBOARD_TYPE_MAIN) {
		$dashboard_id = null;

		$dashboard_field = "dashboard_{$type}_id";
		if(isset($this->info->{$dashboard_field})) $dashboard_id = $this->info->{$dashboard_field};
		if (!$dashboard_id) return false;

		$dasboard = new Dashboard($dashboard_id);
		return $dasboard->validate() ? $dasboard : false;
	}

	public function set_dashboard($dashboard_or_id) {
		$dashboard = $dashboard_or_id instanceof Dashboard ? $dashboard_or_id : new Dashboard($dashboard_or_id);
		$field = 'dashboard_'.$dashboard->type.'_id';
		return App::sql()->update("UPDATE userdb set $field = $dashboard->id where id = $this->id");
	}

	public function get_default_building($permission = null, $condition = '', $include_tenants = false) {
		// TODO: Not too efficient

		$c = $condition ? " AND ($condition) " : '';
		$building_id = $this->info->default_building_id;

		if($building_id) {
			$allowed = $permission ? Permission::get_building($building_id)->check($permission) : true;
		} else {
			$allowed = false;
		}

		if (!$allowed) {
			$buildings = Building::list_with_permission($permission, $include_tenants);
			if ($buildings) {
				$building_id = $buildings[0]->id;
			} else {
				return false;
			}
		}

		return $building_id ? new Building($building_id) : null;
	}

	public function get_default_building_api($permission = null, $condition = '', $include_tenants = false, $permArray) {
		// TODO: Not too efficient

		$c = $condition ? " AND ($condition) " : '';
		$building_id = $this->info->default_building_id;

		if($building_id) {
			$allowed = $permission ? Permission::get_building($building_id)->api_check($permission, $permArray) : true;
			
		} else {
			$allowed = $permission ;
			
		}

		if (!$allowed) {
			$buildings = Building::list_with_permission($permission, $include_tenants);
			if ($buildings) {
				$building_id = $buildings[0]->id;
			} else {
				return false;
			}
		}

		return $building_id ? new Building($building_id) : null;
	}









	public function set_default_building($building_id) {
		$building_id = App::escape($building_id);
		if(Permission::get_building($building_id)->has_access()) {
			App::sql()->update("UPDATE userdb SET default_building_id = '$building_id' WHERE id = '$this->id';");
			$this->info->default_building_id = $building_id;
		}
	}

	public function update_dashboard($dashboard_or_id, $fields) {
		$dashboard = $dashboard_or_id instanceof Dashboard ? $dashboard_or_id : new Dashboard($dashboard_or_id);
		if (!$dashboard->validate()) return false;
		if ($dashboard->is_default()) return false;

		// Update time period across all user dashboards
		$user = App::user();
		if($user && isset($fields['time_period'])) {
			$tp = $fields['time_period'];
			if($tp) {
				$tp = App::escape($tp);
				App::sql()->update("UPDATE dashboard SET time_period = '$tp' WHERE user_id = '$user->id';");
			}
		}

		return $dashboard->update($fields);
	}

	public function add_dashboard($title, $desc = '', $time_period = null, $set_current = false, $type = Dashboard::DASHBOARD_TYPE_MAIN) {
		$user = App::user();

		if($user) {
			if($time_period === null) {
				// Get time period from user's other dashboards (if any)
				$res = App::sql()->query_row("SELECT MIN(time_period) AS time_period FROM dashboard WHERE user_id = '$user->id';");
				if($res) $time_period = $res->time_period;
			} else {
				// Update time period across all user dashboards
				$tp = App::escape($time_period);
				App::sql()->update("UPDATE dashboard SET time_period = '$tp' WHERE user_id = '$user->id';");
			}
		}

		$dashboard = Dashboard::add([
			'title'       => $title,
			'description' => $desc,
			'user_id'     => $this->id,
			'time_period' => $time_period,
			'type'        => $type
		]);

		if ($set_current && $dashboard) $this->set_dashboard($dashboard);

		return $dashboard;
	}

	public function create_token($type) {
		$ok = false;

		$expiry = strtotime('now');
		switch($type) {
			case 'login': $expiry = strtotime('+31 days'); break;
			case 'reset': $expiry = strtotime('+1 day'); break;
			default: return false;
		}

		do {
			$id = App::new_uid(true);
			$ok = !App::select('user_token', $id);
		} while (!$ok);

		App::insert('user_token', [
			'id' => $id,
			'user_id' => $this->id,
			'type' => $type,
			'expiry' => date('Y-m-d H:i:s', $expiry)
		]);

		return $id;
	}

	public function revoke_tokens() {
		App::sql()->delete("DELETE FROM user_token WHERE user_id = '$this->id';");
	}

}

class UserToken {

	public $id;
	public $record;

	public function __construct($id) {
		// Remove expired tokens for easy validation
		App::sql()->delete("DELETE FROM user_token WHERE expiry < NOW();");

		$this->id = App::escape($id);
		$this->record = App::select('user_token', $id);
	}

	public function validate() {
		return !!$this->record;
	}

	public function revoke() {
		App::delete('user_token', $this->id);
	}

	public function is_user_token() {
		return !!$this->record['user_id'];
	}

	public function is_customer_token() {
		return !!$this->record['customer_id'];
	}

	public function get_user() {
		$id = $this->record['user_id'];
		return $id ? new User($id) : null;
	}

	public function get_customer() {
		$id = $this->record['customer_id'];
		return $id ? new Customer($id) : null;
	}

}
