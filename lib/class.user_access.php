<?php
if (isset($_SERVER['HTTP_HOST'])) $app_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];

$url = $app_url.'/eticom/dashboard#view/dashboard/access';

$userId = $_POST['id'];

if(isset($_POST['submit'])){
    $accessLevels   = $_POST['accessLevels'];
    $id             = $_POST['id'];
    
    $access_data = array(
       'accesslevels'   => $accessLevels,
       'id'             => $id
    );

    $ans_paxton = new Access_perm();
    $ans_paxton->update_access($access_data, $url);
    $ans_paxton->user_access($access_data['id']);
}

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




class Access_perm{
    
    public static function user_access($userId)
    {

        $token = Paxton_door::bearer_token();
        $cache_file = $_SERVER['DOCUMENT_ROOT']."/eticom/pax-users/sample-perm'.$userId.'.txt";
        
        if(file_exists($cache_file) && (time() - filemtime($cache_file) < 2 )){
            $response = json_decode(file_get_contents($cache_file), true);
            return $response;
        }else{
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users/'.$userId.'/doorpermissionset',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_TCP_NODELAY  => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token.'',
            ),
            ));

            $response = curl_exec($curl);

            
            curl_close($curl);

            if(!file_exists($cache_file)){
                $fp = fopen($cache_file, 'w');
                fwrite($fp, $userId);
                fclose($fp);
            }

            file_put_contents($cache_file, $response);
        }

        $jsonArray = json_decode($response,true);
        return $jsonArray['accessLevels'];
    }

    public static function update_access($access_data, $url)
    {

        $token = Paxton_door::bearer_token();

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users/'.$access_data['id'].'/doorpermissionset',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS =>'{
            "accessLevels": [
            '.$access_data['accesslevels'].'
            ],
            "individualPermissions": []
        }',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token.'',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        
    
        curl_close($curl);

        echo $response;

        header("Location: $url");

    }
}
  