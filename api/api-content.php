<?php

require_once 'shared-api.php';

class API extends SharedAPI {

	public function download_user_content() {
		$data = App::json();

		if(!isset($data['id']) || !isset($data['url'])) {
			http_response_code(400);
			return '';
		}

		$uc = new UserContent($data['id']);
		$url = $uc->get_url();
		$fn = $uc->get_filename();
		$path = $uc->get_path();

		if(!$uc->info || !$fn || $url !== $data['url']) {
			http_response_code(400);
			return '';
		}

		// Can be called from anywhere

		if(isset($_SERVER['HTTP_REFERER'])) {
			preg_match('~^(.*?//.*?)/.*~', $_SERVER['HTTP_REFERER'], $matches);

			if(isset($matches[1])) {
				header('Access-Control-Allow-Origin: '.$matches[1]);
				header('Access-Control-Allow-Methods: GET,POST');
				header('Access-Control-Allow-Headers: Content-Type');
				header('Access-Control-Allow-Credentials: true');
			}
		} else if(isset($_SERVER['HTTP_ORIGIN'])) {
			header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
			header('Access-Control-Allow-Methods: GET,POST');
			header('Access-Control-Allow-Headers: Content-Type');
			header('Access-Control-Allow-Credentials: true');
		}

		header("Content-type:application/octet-stream");
		header("Content-Disposition:inline;filename=".$fn);

		return readfile($path);
	}

}
