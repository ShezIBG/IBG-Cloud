<?php

class Tenant {

	public $id;
	public $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM tenant WHERE id = '$this->id';");
	}

	public function validate() {
		return !!$this->info;
	}

	public function get_active_leases() {
		$status_current = "'".Lease::STATUS_CURRENT_ACTIVE."', '".Lease::STATUS_CURRENT_EXPIRING."', '".Lease::STATUS_CURRENT_ENDING."'";

		$lease_list = App::sql()->query(
			"SELECT tl.id AS lease_id
			FROM tenanted_area AS ta
			JOIN area AS a ON ta.area_id = a.id
			JOIN tenant_lease AS tl ON tl.area_id = a.id AND tl.status IN ($status_current)
			WHERE tl.tenant_id = '$this->id'
			ORDER BY a.description;
		");

		return array_map(function($l) {
			return new Lease($l->lease_id);
		}, $lease_list ?: []);
	}

	/**
	 * Get a list of all areas for which the tenant has been billed.
	 */
	public function get_billed_areas() {
		$q = '';
		foreach(TenantBill::$tables as $type => $table) {
			if($q) $q .= ' UNION ALL ';
			$q .= "(SELECT DISTINCT t.area_id AS id, a.description FROM $table AS t JOIN area AS a ON a.id = t.area_id WHERE t.tenant_id = '$this->id')";
		}

		$q = "SELECT DISTINCT id, description FROM ($q) AS bills ORDER BY description";

		return App::sql()->query($q) ?: [];
	}

	public function reset_password() {
		if($this->info && $this->info->email_address) {
			$password = App::rnd_string(8);
			$password_hash = App::escape(password_hash($password, PASSWORD_DEFAULT));
			$result = App::sql()->update("UPDATE tenant SET password = '$password_hash' WHERE id = '$this->id';");

			if($result) {
				if($this->info->password) {
					$body = "
						<p>Your password for the tenancy app has been reset. Here are your new sign in details:</p>
						<ul>
							<li>Email: <b>{$this->info->email_address}</b></li>
							<li>Password: <b>$password</b></li>
						</ul>
						<p>
						Regards,<br>
						Your friends at Eticom
						</p>
					";
				} else {
					$body = "
						<p>Welcome to Eticom!</p>
						<p>Here are your tenancy app sign in details:</p>
						<ul>
							<li>Email: <b>{$this->info->email_address}</b></li>
							<li>Password: <b>$password</b></li>
						</ul>
						<p>
						Regards,<br>
						Your friends at Eticom
						</p>
					";
				}

				$mailer = new Mailer();
				$from = $mailer->get_default_from();
				$to = $mailer->build_address_info($this->info->email_address, $this->info->name);
				$mailer->email($from, $to, 'Eticom Tenancy '.($this->info->password ? 'password reset' : 'sign in details'), $body);

				// Update password hash in the object instance once everything is done
				$this->info->password = $password_hash;

				return true;
			}
		}
		return false;
	}

}
