<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function get_billing_account() {
		$user = App::user();
		if(!$user) return $this->access_denied();
		return $this->success($user->evaluate_billing_account());
	}

	public function get_customer_mandate_url() {
		$user = App::user();
		if(!$user) return $this->access_denied();

		$account = $user->evaluate_billing_account();
		if($account['ok']) return $this->access_denied();
		if(!isset($account['dd_payment_gateway']) || !$account['dd_payment_gateway']) return $this->access_denied();
		if(!isset($account['pa_id']) || !$account['pa_id']) return $this->access_denied();
		if(!isset($account['contract_id']) || !$account['contract_id']) return $this->access_denied();

		$pa = PaymentAccount::from_id($account['pa_id']);
		if(!$pa) return $this->access_denied();

		$pg_id = $account['dd_payment_gateway'];
		$pg = new PaymentGateway($pg_id);
		if(!$pg->is_valid()) return $this->access_denied();

		$m = $pg->new_customer_mandate();
		if(!$m) return $this->error('Error getting redirect URL.');

		$_SESSION['gocardless_customer_account'] = implode('-', [ $pa->owner_type, $pa->owner_id, $pa->customer_type, $pa->customer_id ]);
		$_SESSION['gocardless_payment_gateway'] = $pg_id;
		$_SESSION['gocardless_from_login'] = 1;
		$_SESSION['gocardless_contract_id'] = $account['contract_id'];

		return $this->success($m->redirect_url);
	}

}
