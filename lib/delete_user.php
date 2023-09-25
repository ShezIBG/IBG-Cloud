<?php
class Paxton_door{
    public static function bearer_token()
    {
      //Get User ID for current session
      $id = $_SESSION[SESSION_NAME_USER_ID];
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
      return $access_token;
    }
}

$userId = $_POST['id'];
$curl = curl_init();
$token = Paxton_door::bearer_token();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users/'.$userId,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'DELETE',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json',
    'Authorization: Bearer '.$token.'',
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
