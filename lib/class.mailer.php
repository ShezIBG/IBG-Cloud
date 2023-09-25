<?php

require_once 'phpmailer/class.phpmailer.php';
require_once 'phpmailer/class.smtp.php';

class Mailer extends PHPMailer {

	public function __construct($host = null, $port = null, $secure = null, $username = null, $password = null) {
		if($host === null && $port === null && $secure === null && $username === null && $password === null) {
			$host = APP_EMAIL_HOST;
			$port = APP_EMAIL_PORT;
			$secure = APP_EMAIL_SECURE;
			$username = APP_EMAIL_USER;
			$password = APP_EMAIL_PASSWORD;
		}

		$this->IsSMTP();
		$this->Host = $host;
		$this->Port = $port;
		$this->SMTPSecure = $secure;
		$this->Username = $username;
		$this->Password = $password;
		$this->SMTPAuth = !!($username || $password);
		//$this->SMTPDebug = 1;
		//$this->Debugoutput = 'error_log';
	}

	public static function get_default_from($name = '') {
		return self::build_address_info(APP_EMAIL, $name ? $name : 'Eticom');
	}

	public static function format_address($email, $name = '') {
		$address = '"'.str_replace('"', '\'', $name).'" <'.$email.'>';
		return $address;
	}

	public static function build_address_info($email, $name = '') {
		$address_info = self::parse_address(self::format_address($email, $name));
		if ($address_info) {
			if (count($address_info) == 1) return $address_info[0];
			return $address_info;
		}
		return false;
	}

	/**
	* Parse email address string
	* @param  string $str       string input
	* @param  string $separator separator, default ","
	* @return array             array
	*/
	public static function parse_address($email_str, $separator = ",") {
		$email_str = str_replace(';', ',', $email_str);
		$email_str = trim(preg_replace('/\s+/', ' ', $email_str));
		$all = [];
		$emails = preg_split('/(".*?"\s*<.+?>)\s*'.$separator.'*|'.$separator.'+/', $email_str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		foreach ($emails as $email) {
			$name = "";
			$email = trim($email);
			$email_info = new stdClass;
			if (preg_match('/(.*?)<(.*)>/', $email, $regs)) {
				$name = trim(trim($regs[1]), '"');
				$name = $name ? $name : $email;
				$email = trim($regs[2]);
			} else {
				$name = $email;
				$email = $email;
			}

			$email_info->name = $name;
			$email_info->email = $email;

			if (strpos($email_info->email, $separator) !== false) {
				$addtl_emails = self::parse_address($email_info->email, $separator);
				foreach ($addtl_emails as $addtl_email_info) {
					if ($addtl_email_info->name == "" || $addtl_email_info->name == $addtl_email_info->email) {
						$addtl_email_info->name = $email_info->name.' <'.$addtl_email_info->email.'>';
					}
					$all[] = $addtl_email_info;
				}
			} else {
				if (filter_var($email_info->email, FILTER_VALIDATE_EMAIL)) {
					$all[] = $email_info;
				}
			}
		}
		return $all;
	}

	/**
	 * Context:
	 *   customer        - customer table record
	 *   payment_account - PaymentAccount object
	 *   contract        - Contract object
	 *   invoice         - Invoice object
	 */
	public static function send_from_template($owner_type, $owner_id, $template_type, $email_to, $email_name, $context = [], $attachments = []) {
		$smtp = App::sql()->query_row("SELECT * FROM email_smtp WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' LIMIT 1;", MySQL::QUERY_ASSOC);
		if($smtp) {
			$template = App::sql()->query_row("SELECT * FROM email_template WHERE owner_type = '$owner_type' AND owner_id = '$owner_id' AND template_type = '$template_type' LIMIT 1;", MySQL::QUERY_ASSOC, false);
			if($template) {
				try {
					$mailer = new Mailer($smtp['host'], $smtp['port'], $smtp['secure'], $smtp['user'], $smtp['password']);
					//new Mailer("smtp.gmail.com","587","ssl","shezan.nabiel@gmail.com","Bec");
					//SMTP details pulled from email_smtp db....
					
					
					$from_address = $template['from_address'];
					$owner_name = 'Accounts';
					if($owner_type == 'SI') {
						$si = App::select('system_integrator', $owner_id);
						$owner_name = $si['company_name'];
					}
					if(!$from_address) $from_address = $smtp['default_from_address'];
					$from = $mailer->build_address_info($from_address, $owner_name);
					$to = $mailer->build_address_info($email_to, $email_name);

					$subject = $template['subject'];
					$body = '<html><body>'.$template['body'].'</body></html>';

					$set_meta = function($tag, $value) use (&$subject, &$body, $context) {
						$subject = str_replace('{'.$tag.'}', $value, $subject);
						$body = str_replace('{'.$tag.'}', $value, $body);
					};

					// Resolve system integrator for branding
					$si_id = '';
					$chain = Permission::get_level_chain($owner_type, $owner_id);
					if($chain && isset($chain->SI_id)) {
						$si_id = $chain->SI_id;
					}

					// Resolve meta tags
					if(isset($context['payment_account']) && $context['payment_account']) {
						$pa = $context['payment_account'];
						$account_url = $pa->get_account_url(App::url($si_id));
						$outstanding = $pa->get_outstanding_pence() / 100;

						$set_meta('LINK', '<a href="'.$account_url.'">'.$account_url.'</a>');
						$set_meta('URL', $account_url);
						$set_meta('OUTSTANDINGAMOUNT', '&pound;'.App::format_number($outstanding, 2, 2));

						if($pa->record['trigger_card_payment_date']) {
							$set_meta('CARDPAYMENTDATE', date('d/m/Y', strtotime($pa->record['trigger_card_payment_date'])));
						}

						$card = $pa->get_payment_card();
						if($card) {
							$set_meta('CARDEXPMONTH', ($card['exp_month'] < 10 ? '0' : '').$card['exp_month']);
							$set_meta('CARDEXPYEAR', $card['exp_year']);
							$set_meta('CARDLAST4', $card['last4']);
						}
					}
					if(isset($context['customer']) && $context['customer']) {
						$signup_url = '';
						if($context['customer']['signup_hash']) {
							$signup_url = App::url($si_id);
							if(!$signup_url) $signup_url = APP_URL;
							$signup_url = $signup_url.'/v3/auth/customer-signup/'.$context['customer']['customer_id'].'/'.$context['customer']['signup_hash'];
						}

						$set_meta('CUSTOMERNAME', $context['customer']['name'] ?: 'customer');
						if($signup_url) {
							$set_meta('SIGNUPLINK', '<a href="'.$signup_url.'">'.$signup_url.'</a>');
							$set_meta('SIGNUPURL', $signup_url);
						} else {
							$set_meta('SIGNUPLINK', '');
							$set_meta('SIGNUPURL', '');
						}
					} else {
						$set_meta('CUSTOMERNAME', 'customer');
						$set_meta('SIGNUPLINK', '');
						$set_meta('SIGNUPURL', '');
					}

					if(isset($context['contract']) && $context['contract']) {
						$contract = $context['contract'];

						$wifi_ssid = '';
						$wifi_password = '';
						$area = $contract->get_isp_area();
						if($area) {
							$onu = $area->get_onu();
							if($onu) {
								$wifi_ssid = ($onu->record['pending_wifi_ssid'] ? $onu->record['pending_wifi_ssid'] : $onu->record['wifi_ssid']) ?: '';
								$wifi_password = ($onu->record['pending_wifi_password'] ? $onu->record['pending_wifi_password'] : $onu->record['wifi_password']) ?: '';
							}
						}

						$set_meta('WIFISSID', $wifi_ssid);
						$set_meta('WIFIPASSWORD', $wifi_password);
						$set_meta('PACKAGE', $contract->get_isp_package_name());
					}
					if(isset($context['invoice']) && $context['invoice']) {
						$invoice = $context['invoice']->record;
						$set_meta('INVOICEDATE', date('d/m/Y', strtotime($invoice['bill_date'])));
						$set_meta('INVOICEDUE', date('d/m/Y', strtotime($invoice['due_date'])));
						$set_meta('INVOICESTART', date('d/m/Y', strtotime($invoice['period_start_date'] ?: $invoice['bill_date'])));
						$set_meta('INVOICEEND', date('d/m/Y', strtotime($invoice['period_end_date'] ?: $invoice['bill_date'])));
						$set_meta('INVOICENO', $invoice['invoice_no']);
					}

					// Context-independent meta tags
					$date = date('Y-m-01');
					$date = strtotime('+1 month', strtotime($date));
					$set_meta('1STDAYOFNEXTMONTH', date('d/m/Y', $date));

					// Send email
					$return = $mailer->email($from, $to, $subject, $body, $attachments);
					
					return true;
				} catch (Exception $ex) {
					error_log($ex);
				}
			}
		}

		return false;
	}

	//array $to should be [<to_name>, <to_email>] as parameter
	//array $attachments should be [<type>, <path>] as parameter
	/**
	* Emailer::send_mail()
	*
	* @param string $from
	* @param array $to
	* @param string $subject
	* @param string $message
	* @param array $attachments
	* @return
	*/
	public function email($from, $to, $subject, $message, $attachments = []) {
		if (!$to) {
			$this->mail_log("[Mail error] No recepient address\n");
			return false;
		}
		$mail_from = is_object($from) ? $from : (object)[ 'name' => $from, 'email' => $from ];
		$this->SetFrom($mail_from->email, $mail_from->name);

		if (is_array($to)) {
			foreach ($to as $name => $email) {
				if (is_object($email)) {
					if (trim($email->email) != "") {
						$this->AddAddress(trim($email->email), $email->name);
					}
				} else {
					if (trim($email) != "") {
						$this->AddAddress(trim($email), is_int($name) ? trim($email) : $name);
					}
				}
			}
		} else if (is_object($to)) {
			$this->AddAddress($to->email, $to->name);
		} else {
			$this->AddAddress($to);
		}

		foreach ($attachments as $attachment) {
			if ($attachment != "") {
				if(substr($attachment, 0, 7) === "http://" || substr($attachment, 0, 8) === "https://") {
					// A URL|filename was passed, retrieve file and attach it
					$chunks = explode('|', $attachment);
					if(count($chunks) > 1) {
						$url = $chunks[0];
						$filename = $chunks[1];
						if($contents = file_get_contents($url)) {
							$this->addStringAttachment($contents, $filename);
						}
					}
				} else {
					// A filename was passed, directly use AddAttachment method
					// Custom file name can be passed by delimiting with ":"
					$chunks = explode(':', $attachment);
					$file_path = $chunks[0];
					$file_send = '';
					if(isset($chunks[1])) $file_send = $chunks[1];
					$this->AddAttachment($file_path, $file_send, "base64");
				}
			}
		}

		$this->Subject  = $subject;
		$this->MsgHTML($message);
		if(!$this->Send()) {
			$this->mail_log("[Mail error] ".$this->ErrorInfo."\n");
			return false;
		}

		return true;
	}

	public function get_error() {
		return $this->ErrorInfo;
	}

	private function mail_log($msg) {
		error_log($msg, 0);
	}
}
