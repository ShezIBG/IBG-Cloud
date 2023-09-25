<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_smoothpower_update_url() {
		$server_version = App::get('server', 0, true);
		$screen_version = App::get('screen', 0, true);
		$channel = App::get('channel', 'release', true);

		$old_version = $server_version;
		if($screen_version < $old_version) $old_version = $screen_version;

		$update = App::sql()->query_row("SELECT id, version, channel FROM smoothpower_update WHERE (version > '$old_version' OR (version < '$old_version' AND rollback = 1)) AND channel = '$channel' ORDER BY version DESC LIMIT 1;", MySQL::QUERY_ASSOC);
		if($update) {
			$version = $update['version'];
			return $this->success(APP_URL.USER_CONTENT_URL."/smoothpower_update/smoothpower-$version.tar.gz");
		}

		return $this->success('');
	}

	public function get_smoothpower_info() {
		$serial = App::get('serial', '', true);
		if(!$serial) return $this->error('Not found.');

		// address_n to address_line_n transition: Had to keep returned field name, as this API endpoint is consumed by SmoothPower code as well.
		// Can only be changed together with SmoothPower codebase update.

		$info = App::sql()->query_row(
			"SELECT
				c.name AS client_name,
				si.id AS si_id,
				si.company_name,
				si.address_line_1 AS address_1,
				si.address_line_2 AS address_2,
				si.address_line_3 AS address_3,
				si.posttown,
				si.postcode,
				si.phone_number
			FROM smoothpower AS smp
			JOIN building AS b ON b.id = smp.building_id
			JOIN client AS c ON c.id = b.client_id
			JOIN system_integrator AS si ON si.id = smp.system_integrator_id
			WHERE serial = '$serial'
			LIMIT 1;
		");

		if(!$info) return $this->error('Not found.');

		return $this->success($info);
	}

	public function login() {
		$type = App::get('type');
		$data = App::json();
		
		if(!isset($data['email']) || !isset($data['password'])) return $this->error('Please enter your email address and password.');
		if($data['email'] == '' || $data['password'] == '') return $this->error('Please enter your email address and password.');

		$user = User::do_login($data['email'], $data['password']);
		if(!$user) return $this->error('Invalid email address or password.');

		if($type === 'smoothpower') {
			$GLOBALS['user'] = $user;
			$buildings = Permission::list_buildings([ 'with' => Permission::SMOOTHPOWER_ENABLED ]) ?: [];
			if(count($buildings) === 0) {
				$GLOBALS['user'] = null;
				User::logout();
				return $this->error('No SmoothPower units found.');
			}
		}

		return $this->success([
			'user_id' => $user->id,
			'user_email' => $user->info->email_addr,
			'user_name' => $user->info->name
		]);
	}

	public function cloud_login() {
		$type = App::get('type');
		$data = App::json();
		
		$data = App::keep($data, ['email', 'password', 'rememberme']);
		$data = App::ensure($data, ['email', 'password'], '');
		$data = App::ensure($data, ['rememberme'], 0);
		
		if(!$data['email'] || !$data['password']) return $this->error('Please enter your email address and password.');
		if($data['email'] == '' || $data['password'] == '') return $this->error('Please enter your email address and password.');

		$user = User::do_login($data['email'], $data['password']);

		//Shez update: If account has Noaccess or we suspend display error message
		$noaccess = User::noaccess_check_auth($data['email'], $data['password']);
		if($noaccess == 'No_access') return $this->error('Account deactivated, Please contact support@ibg-uk.com.');

		if(!$user) {
			// Override customer logins
			$email = App::escape(strtolower($data['email']));
			$customer = App::sql()->query_row("SELECT * FROM customer WHERE allow_login = 1 AND email_address = '$email' AND archived = 0 LIMIT 1;", MySQL::QUERY_ASSOC);
			if($customer) {
				if(password_verify($data['password'], $customer['password'])) {
					// Get payment account details
					$pa = new PaymentAccount($customer['owner_type'], $customer['owner_id'], 'CU', $customer['id']);
					$_SESSION['customer_id'] = $customer['id'];

					return $this->success([
						'account' => [
							'ok' => false,
							'redirect_route' => ['account', $pa->id, $pa->record['security_token']]
						]
					]);
				}
			}
			
			return $this->error('Invalid email address or password!');
		}

		// Only valid user logins get to this point

		if ($data['rememberme']) {
			$token_id = $user->create_token('login');
			if($token_id) {
				$cookie_time = (3600 * 24 * 30); // 30 days
				setcookie(AUTH_COOKIE_NAME, $token_id, time() + $cookie_time, '/');
			}
		}

		$GLOBALS['user'] = $user;
		
		return $this->success([
			'user_id' => $user->id,
			'user_email' => $user->info->email_addr,
			'user_name' => $user->info->name,
			'account' => $user->evaluate_billing_account()
		]);
	}

	public function logout() {
		User::logout();
		unset($_SESSION['customer_id']);
		return $this->success();
	}

	public function reset_password() {
		
		$data = App::json();
		$data = App::ensure($data, ['email']);

		$email_address = App::escape(trim(strtolower($data['email'])));

		if(!$email_address) return $this->error('Please enter your email address.');
		
		// Check if it belongs to a user
		$user = User::create($email_address);
		if(!$user) {
			// If not, check if it belongs to an enabled customer login
			$cust = App::sql()->query_row("SELECT id FROM customer WHERE email_address = '$email_address' AND allow_login = 1 LIMIT 1;", MySQL::QUERY_ASSOC);

			if(!$cust) return $this->error('There is no user account associated with your email address.');

			$customer = new Customer($cust['id']);
			if(!$customer->validate()) return $this->error('There is no user account associated with your email address.');

			//
			// Customer password reset
			//
			if(!$customer->reset_password()) return $this->error('We can\'t send an email right now. Please try again later.');

		} else {

			//
			// User password reset
			//
			if(!$user->reset_password()) return $this->error('We can\'t send an email right now. Please try again later.');

		}

		return $this->success();
	}

	public function check_reset_token() {
		$token_id = App::get('token', '');
		$token = new UserToken($token_id);

		$user = null;
		$email_address = null;
		if($token->validate()) {
			if($token->is_user_token()) {
				$user = $token->get_user();
				if($user->validate()) {
					$email_address = $user->info->email_addr;
				}
			} else if($token->is_customer_token()) {
				$customer = $token->get_customer();
				if($customer->validate()) {
					if($customer->record['allow_login']) {
						$email_address = $customer->record['email_address'];
					} else {
						$customer->revoke_tokens();
					}
				}
			}
		}

		if($email_address) {
			return $this->success([ 'email_addr' => $email_address ]);
		} else {
			return $this->error('Invalid password reset token.');
		}
	}

	public function update_password() {
		$data = App::json();
		$data = App::keep($data, ['token', 'new_password', 'new_password_conf']);
		$data = App::ensure($data, ['token', 'new_password', 'new_password_conf'], '');

		if($data['new_password'] === '') return $this->error('Please enter your new password.');
		if($data['new_password'] != $data['new_password_conf']) return $this->error('Please enter the same password twice to confirm.');
		if(strlen($data['new_password']) < 7) return $this->error('Password must be at least 7 characters long.');

		$token_id = $data['token'];
		$token = new UserToken($token_id);

		$user = null;
		if($token->validate()) {
			if($token->is_user_token()) {

				// User password reset
				$user = $token->get_user();
				if(!$user->validate()) return $this->access_denied();

				App::update('userdb', $user->id, [
					'password' => password_hash($data['new_password'], PASSWORD_DEFAULT)
				]);

				$user->insert_event('Password changed', User::USER_EVENT_PASSWORD_CHANGE, $token_id);
				$user->revoke_tokens();

			} else if($token->is_customer_token()) {

				// Customer password reset
				$customer = $token->get_customer();
				if(!$customer->validate()) return $this->access_denied();
				if(!$customer->record['allow_login']) {
					$customer->revoke_tokens();
					return $this->access_denied();
				}

				App::update('customer', $customer->id, [
					'password' => password_hash($data['new_password'], PASSWORD_DEFAULT)
				]);

				$customer->revoke_tokens();

			} else {

				// Invalid token
				return $this->access_denied();

			}
		}

		return $this->success();
	}

	public function get_customer_signup() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'hash'], '');

		if(!$data['id']) return $this->access_denied();
		if(!$data['hash']) return $this->access_denied();

		$customer = new Customer($data['id']);
		if(!$customer->validate()) return $this->access_denied();
		if($customer->get_signup_hash() !== $data['hash']) return $this->access_denied();
		if($customer->record['archived']) return $this->access_denied();
		if($customer->record['allow_login']) return $this->error('You have already signed up. If you forgot your password, you can reset it on the sign in form.');

		return $this->success([
			'name' => $customer->record['contact_name'] ?: $customer->record['company_name']
		]);
	}

	public function submit_customer_signup() {
		$data = App::json();
		$data = App::ensure($data, ['id', 'hash', 'password', 'password_conf'], '');

		if(!$data['id']) return $this->access_denied();
		if(!$data['hash']) return $this->access_denied();

		$customer = new Customer($data['id']);
		if(!$customer->validate()) return $this->access_denied();
		if($customer->get_signup_hash() !== $data['hash']) return $this->access_denied();
		if($customer->record['archived']) return $this->access_denied();
		if($customer->record['allow_login']) return $this->error('You have already signed up. If you forgot your password, you can reset it on the sign in form.');

		if($data['password'] === '') return $this->error('Please enter your new password.');
		if($data['password'] != $data['password_conf']) return $this->error('Please enter the same password twice to confirm.');
		if(strlen($data['password']) < 7) return $this->error('Password must be at least 7 characters long.');

		App::update('customer', $customer->id, [
			'allow_login' => 1,
			'password' => password_hash($data['password'], PASSWORD_DEFAULT)
		]);

		return $this->success();
	}

}
