<?php

class Paxton_door{
    public static function bearer_token()
    {
      //Get User ID for current session
      $id = $_SESSION[SESSION_NAME_USER_ID];
      //print_r($id);
      $url = 'http://193.178.55.230:4005/api/v1/authorization/tokens';
      $data = array('username' => 'System engineer', 'password' => 'Renard1966', 'grant_type' => 'password',
                  'client_id'=>'5bda30f7-4f61-4be6-84fa-a253799e14c0');

      // use key 'http' even if you send the request to https://...
      $options = array(
          'http' => array(
              'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
              'method'  => 'POST',
              'content' => http_build_query($data)
          )
      );
      $context  = stream_context_create($options);
      $result = file_get_contents($url, false, $context);
      if ($result === FALSE) { /* Handle error */ }

      $jsonArray = json_decode($result,true);

      $key = 'access_token';
      $expire = 'expires_in';
      $ttype = 'bearer';

      $access_token = $jsonArray[$key];
      $token_type = $jsonArray[$ttype];
      $expires_in = $jsonArray[$expire];
      //var_dump($result);
      return $access_token;
    }
}
$user_paxton = $_POST['allusersid'];
$curl = curl_init();
$token = Paxton_door::bearer_token();
curl_setopt_array($curl, array(
CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users',
CURLOPT_RETURNTRANSFER => true,
CURLOPT_HEADER => false,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer '.$token
),
));
$response = curl_exec($curl);

// $jsonArray = json_decode($response,true);

curl_close($curl);

// echo $response;
// print_r(['response'=> $response]); exit;
// print_r($jsonArray); exit;
// $jsonArray = $jsonArray[0];

echo $response;