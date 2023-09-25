<?php


class Overdue{
    public function get_billing_account() {
		$user = App::user();
		if(!$user) return $this->access_denied();
		return $this->success($user->evaluate_billing_account());
	}

    protected function access_denied() {
            http_response_code(403);
            return App::encode_result('FAIL', 'Access denied.', null);
        }

        protected function error($error, $data = null) {
            http_response_code(400);
            return App::encode_result('FAIL', $error, $data);
        }

        protected function success($result = null, $message = '') {
            return App::encode_result('OK', $message, $result);
        }
}