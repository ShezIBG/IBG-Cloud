<?php

if (isset($_SERVER['HTTP_HOST'])) $app_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];

$url = $app_url.'/eticom/dashboard#view/dashboard/access';

//   /** Paxton Form Processing Starts */
if (isset($_POST['submit'])) {

  $firstname  = $_POST['firstname'];
  $lastname   = $_POST['lastname'];
  $telephone  = $_POST['telephone'];
  $pin        = $_POST['pin'];

  $create_data = array(
      "firstname" => $firstname,
      "lastname"  => $lastname,
      "telephone" => $telephone,
      "pin"       => $pin
  );
  if(empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['telephone']) || empty($_POST['pin'])){
    $arr = array('message' => 'All form fields are required');
    $json_arr = json_encode($arr, true);

    echo $json_arr;
    echo "<br><br>";
    echo "<a href='". $app_url."/eticom/dashboard#view/dashboard/access'>Go Back</button>";

  }else{
    $ans_hubspot = new Paxton();
    $ans_hubspot->paxton_create($create_data, $url);
    $ans_hubspot->user_paxton();
  }

}
/** Update User */
if (isset($_POST['update'])) {
  $updateId   =  $_POST['id'];
  $firstname  = $_POST['firstname'];
  $lastname   = $_POST['lastname'];
  $telephone  = $_POST['telephone'];
  $pin        = $_POST['pin'];

  $contact_data = array(
      "firstname" => $firstname,
      "lastname"  => $lastname,
      "telephone" => $telephone,
      "pin"       => $pin
  );
  $ans_hubspot = new Paxton();
  $ans_hubspot->update_levels();

}
/** Create Access */
if(isset($_POST['access'])){
  $id   = $_POST['id'];
  $name = $_POST['name'];

  $access_data = array(
    'id'    => $id,
    'name'  => $name
  );

  $access_decode = json_encode($access_data);

  if(empty($_POST['id']) || empty($_POST['name'])){
    $arr = array('message' => 'All form fields are required');
    $json_arr = json_encode($arr, true);

    echo $json_arr;
    echo "<br><br>";
    echo "<a href='". $app_url."/eticom/dashboard#view/dashboard/access'>Go Back</button>";

  }else{
    $ans_hubspot = new Paxton();
    $ans_hubspot->paxton_access($access_decode, $url);
  }

}

/**Update Access Levels */
if(isset($_POST['apply'])){
  $id = $_POST['id'];
  $name = $_POST['name'];
  $areaID = $_POST['areaId'];
  $timezoneID = $_POST['timezoneID'];

  $detailRows = array();

  array_push($detailRows, array(
    "areaID" => $areaID,
    "timezoneID" => $timezoneID
  ));

  $json = json_encode(array("detailRows" => $detailRows));

  //1 solution
  $array = json_decode($json, true);
  $detailRows = $array["detailRows"][0];

  $newArray = array();
  foreach ($detailRows["areaID"] as $key => $areaID) {
      $timezoneID = $detailRows["timezoneID"][$key];
      array_push($newArray, array(
          "areaID" => $areaID,
          "timezoneID" => $timezoneID
      ));
  }

  //2 solution
  // $array = json_decode($json, true);
  // $detailRows = $array["detailRows"];

  // $newArray = array();
  // foreach ($detailRows as $row) {
  //     array_push($newArray, array(
  //         "areaID" => (int)$row["areaID"],
  //         "timezoneID" => (int)$row["timezoneID"]
  //     ));
  // }

  // $newJson = json_encode(array("detailRows" => $newArray));

  $data = array (
        "id" => $id,
        "name" => $name,
        "detailRows" => $newArray
  );

  $ans_hubspot = new Paxton();
  $ans_hubspot->update_access_levels($data, $url);
}

/** Create Timezones */
if(isset($_POST['timezones'])){
  $name         = $_POST['name'];
  $mon_start    = $_POST['mon_start'];
  $mon_end      = $_POST['mon_end'];
  $tue_start    = $_POST['tue_start'];
  $tue_end      = $_POST['tue_end'];
  $wed_start    = $_POST['wed_start'];
  $wed_end      = $_POST['wed_end'];
  $thurs_start  = $_POST['thurs_start'];
  $thurs_end    = $_POST['thurs_end'];
  $fri_start    = $_POST['fri_start'];
  $fri_end      = $_POST['fri_end'];
  $sat_start    = $_POST['sat_start'];
  $sat_end      = $_POST['sat_end'];
  $sun_start    = $_POST['sun_start']; 
  $sun_end      = $_POST['sun_end'];

  $slotID = 19;
  $slotID++;

  $timezone_slots1 = array(
    'slotID'       => $slotID,
    'startTime'    => $mon_start,
    'endTime'      => $mon_end,
    'dayID'        => 2
  );
  $timezone_slots2 = array(
    'slotID'       => $slotID,
    'startTime'    => $tue_start,
    'endTime'      => $tue_end,
    'dayID'        => 3
  );
  $timezone_slots3 = array(
    'slotID'      => $slotID,
    'startTime'   =>  $wed_end,
    'endTime'     =>  $wed_start,
    'dayID'       =>  4
  );

  $timezone_slots4 = array(
    'slotID'      => $slotID,
    'startTime'   =>  $thurs_end,
    'endTime'     =>  $thurs_start,
    'dayID'       =>  5
  );

  $timezone_slots5 = array(
    'slotID'      => $slotID,
    'startTime'   =>  $fri_end,
    'endTime'     =>  $fri_start,
    'dayID'       =>  6
  );

  $timezone_slots6 = array(
    'slotID'      => $slotID,
    'startTime'   =>  $pub_end,
    'endTime'     =>  $pub_start,
    'dayID'       =>  7
  );

  if(in_array(null, $timezone_slots1) || in_array("", array_map('trim', $timezone_slots1))){
    $mon = 0;
  }
  else{
    $mon = json_encode($timezone_slots1);
  }

  if(in_array(null, $timezone_slots2) || in_array("", array_map('trim', $timezone_slots2))){
    $tue = 0;
  }
  else{
    $tue = json_encode($timezone_slots2);
  }

  if(in_array(null, $timezone_slots3) || in_array("", array_map('trim', $timezone_slots3))){
    $wed = 0;
  }
  else{
    $wed = json_encode($timezone_slots3);
  }

  if(in_array(null, $timezone_slots4) || in_array("", array_map('trim', $timezone_slots4))){
    $thurs = 0;
  }
  else{
    $thurs = json_encode($timezone_slots4);
  }

  if(in_array(null, $timezone_slots5) || in_array("", array_map('trim', $timezone_slots5))){
    $fri = 0;
  }
  else{
    $fri = json_encode($timezone_slots5);
  }

  if(in_array(null, $timezone_slots6) || in_array("", array_map('trim', $timezone_slots6))){
    $pub = 0;
  }
  else{
    $pub = json_encode($timezone_slots6);
  }


  $name         = $_POST['name'];
  $mon_start    = $_POST['mon_start'];
  $mon_end      = $_POST['mon_end'];
  $tue_start    = $_POST['tue_start'];
  $tue_end      = $_POST['tue_end'];
  $wed_start    = $_POST['wed_start'];
  $wed_end      = $_POST['wed_end'];
  $thurs_start  = $_POST['thurs_start'];
  $thurs_end    = $_POST['thurs_end'];
  $fri_start    = $_POST['fri_start'];
  $fri_end      = $_POST['fri_end'];
  $sat_start    = $_POST['sat_start'];
  $sat_end      = $_POST['sat_end'];
  $sun_start    = $_POST['sun_start']; 
  $sun_end      = $_POST['sun_end'];
  if(empty($_POST['name'])){
    $arr = array('message' => 'Name Field is Required');
    $json_arr = json_encode($arr, true);

    echo $json_arr;
    echo "<br><br>";
    echo "<a href='". $app_url."/eticom/dashboard#view/dashboard/access'>Go Back</button>";

  }else{
    $ans_hubspot = new Paxton();
    $ans_hubspot->create_timezones($name, $mon, $tue, $wed, $thurs, $fri, $pub, $url);
  }

}

/** Get Single Timezones */

if(isset($_POST['singleId'])){

$singleId = $_POST['singleId'];
$timezone_d = new Paxton();
$timezone_d->timezone_details($singleId);

}

/** Update Timezones */

if(isset($_POST['updatetimezone'])){
  $id           = $_POST['id'];
  $name         = $_POST['name'];
  $mon_start    = $_POST['mon_start'];
  $mon_end      = $_POST['mon_end'];
  $tue_start    = $_POST['tue_start'];
  $tue_end      = $_POST['tue_end'];
  $wed_start    = $_POST['wed_start'];
  $wed_end      = $_POST['wed_end'];
  $thurs_start  = $_POST['thurs_start'];
  $thurs_end    = $_POST['thurs_end'];
  $fri_start    = $_POST['fri_start'];
  $fri_end      = $_POST['fri_end'];
  $sat_start    = $_POST['sat_start'];
  $sat_end      = $_POST['sat_end'];
  $sun_start    = $_POST['sun_start']; 
  $sun_end      = $_POST['sun_end'];
  
  $slotID = 0;
  $slotID++;

  $dayMon = 2;
  if($_POST['dayID_tue'] == null){
    $dayID = $dayMon;
  }else{
    $dayID = $_POST['dayID_tue'];
  }
  $updatetimezone_slots1 = array(
    'slotID'       => $_POST['slotID_mon'],
    'startTime'    => $mon_start,
    'endTime'      => $mon_end,
    'dayID'        => $dayID
  );

  $dayTue = 3;
  if($_POST['dayID_tue'] == null){
    $dayID = $dayTue;
  }else{
    $dayID = $_POST['dayID_tue'];
  }

  $updatetimezone_slots2 = array(
    'slotID'       => $_POST['slotID_tue'],
    'startTime'    => $tue_start,
    'endTime'      => $tue_end,
    'dayID'        => $dayID
  );

  $dayWed = 4;
  if($_POST['dayID_wed'] == null){
    $dayID = $dayWed;
  }else{
    $dayID = $_POST['dayID_wed'];
  }
  $updatetimezone_slots3 = array(
    'slotID'       => $_POST['slotID_wed'],
    'startTime'    => $wed_start,
    'endTime'      => $wed_end,
    'dayID'        => $dayID
  );

  $dayThurs = 5;
  if($_POST['dayID_thurs'] == null){
    $dayID = $dayThurs;
  }else{
    $dayID = $_POST['dayID_thurs'];
  }
  $updatetimezone_slots4 = array(
    'slotID'       => $_POST['slotID_thurs'],
    'startTime'    => $thurs_start,
    'endTime'      => $thurs_end,
    'dayID'        => $dayID
  );

  $dayFri = 6;
  if($_POST['dayID_fri'] == null){
    $dayID = $dayFri;
  }else{
    $dayID = $_POST['dayID_fri'];
  }

  $updatetimezone_slots5 = array(
    'slotID'       => $_POST['slotID_fri'],
    'startTime'    => $fri_start,
    'endTime'      => $fri_end,
    'dayID'        => $dayID
  );

  $updatetimezone_slots6 = array(
    'slotID'       => $_POST['slotID_sat'],
    'startTime'    => $sat_start,
    'endTime'      => $sat_end,
    'dayID'        => $_POST['dayID_sat']
  );

  $updatetimezone_slots7 = array(
    'slotID'       => $_POST['slotID_sun'],
    'startTime'    => $sun_start,
    'endTime'      => $sun_end,
    'dayID'        => $_POST['dayID_sun']
  );

  if(in_array(null, $updatetimezone_slots1) || in_array("", array_map('trim', $updatetimezone_slots1))){
    $mon = 0;
  }
  else{
    $mon = json_encode($updatetimezone_slots1);
  }

  if(in_array(null, $updatetimezone_slots2) || in_array("", array_map('trim', $updatetimezone_slots2))){
    $tue = 0;
  }
  else{
    $tue = json_encode($updatetimezone_slots2);
  }

  if(in_array(null, $updatetimezone_slots3) || in_array("", array_map('trim', $updatetimezone_slots3))){
    $wed = 0;
  }
  else{
    $wed = json_encode($updatetimezone_slots3);
  }

  if(in_array(null, $updatetimezone_slots4) || in_array("", array_map('trim', $updatetimezone_slots4))){
    $thurs = 0;
  }
  else{
    $thurs = json_encode($updatetimezone_slots4);
  }

  if(in_array(null, $updatetimezone_slots4) || in_array("", array_map('trim', $updatetimezone_slots4))){
    $thurs = 0;
  }
  else{
    $thurs = json_encode($updatetimezone_slots4);
  }

  if(in_array(null, $updatetimezone_slots5) || in_array("", array_map('trim', $updatetimezone_slots5))){
    $fri = 0;
  }
  else{
    $fri = json_encode($updatetimezone_slots5);
  }

  if(in_array(null, $updatetimezone_slots6) || in_array("", array_map('trim', $updatetimezone_slots6))){
    $sat = 0;
  }
  else{
    $sat = json_encode($updatetimezone_slots6);
  }

  if(in_array(null, $updatetimezone_slots7) || in_array("", array_map('trim', $updatetimezone_slots7))){
    $sun = 0;
  }
  else{
    $sun = json_encode($updatetimezone_slots7);
  }

  $ans_hubspot = new Paxton();
  $ans_hubspot->update_timezones($name, $id, $mon, $tue, $wed, $thurs, $fri, $sat, $sun);
}

/** Delete Timezone */
if(isset($_POST['deleteId'])){

  $deleteId = $_POST['deleteId'];
  $timezone_delete = new Paxton();
  $timezone_delete->timezone_delete($deleteId, $url);
  
}

/** Add new key */
if(isset($_POST['addKey'])){
  $userTId   = $_POST['id'];
  $tokenType = $_POST['tokenType'];
  $tokenValue = $_POST['tokenValue'];

  $add_key = new Paxton();
  $add_key->event_key($userTId, $tokenType, $tokenValue, $url);
}

/** Get all keys */
if(isset($_POST['mainUserKeyId'])){
  $userKeyId = $_POST['mainUserKeyId'];
  
  $show_key = new Paxton();
  $show_key->get_all_keys($userKeyId);
  $show_key->get_all_keys($userKeyId);
}
//Get All users
  $allusers = $_POST['allusersid'];
 
  $show_users = new Paxton();
  $show_users->user_paxton($allusers);

  function dd($value){
    echo "<prev>";
    var_dump($value);
    echo "</prev>";
    die();
  }
    class Access {

      

        public static function bearer_token()
        {
            //Get User ID for current session
            $id = $_SESSION[SESSION_NAME_USER_ID];
            $url = 'http://193.178.55.230:5074/identity/connect/token';
            $data = array('client_id' => 'development', 'client_secret' => '0u2xJZEnaAqmmAb1cJkO9Eda1QtahR1L', 'grant_type' => 'password',
                       'scope'=>'gardisapi', 'username'=>'GARDIS', 'password'=>'@Renard1966' );

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
            $ttype = 'token_type';

            $access_token = $jsonArray[$key];
            $token_type = $jsonArray[$ttype];
            $expires_in = $jsonArray[$expire];
            return $access_token;
           

            
        }


        public static function getall_person_details($bearer_token)
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'http://193.178.55.230:53198/api/Person/Details',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearer_token
              ),
            ));
            
            $response = curl_exec($curl);
            $jsonArray = json_decode($response,true);

            curl_close($curl);
            return $jsonArray;
        }




        public static function getperson_photoid($id)
        {
            $curl = curl_init();
            $bearer_token = Access::bearer_token();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'http://193.178.55.230:53198/api/PersonPhoto/'.$id,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearer_token
              ),
            ));
            
            $response = curl_exec($curl);
            $jsonArray = json_decode($response,true);

            
            curl_close($curl);
            return $jsonArray;

        }
        public static function system_overview()
        {
          $curl = curl_init();
          $bearer_token = Access::bearer_token();
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:53198/api/system/overview',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$bearer_token
            ),
          ));
  
          $response = curl_exec($curl);
          $jsonArray = json_decode($response,true);
  
          
          curl_close($curl);
          return $jsonArray;
  
        }
  
        public static function input_status()
        {
          $curl = curl_init();
          $bearer_token = Access::bearer_token();
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:53198/api/Input',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$bearer_token
            ),
          ));
  
          $response = curl_exec($curl);
          $jsonArray = json_decode($response,true);
  
          
          curl_close($curl);
          return $jsonArray;
        }
  
        public static function output_status()
        {
          $curl = curl_init();
          $bearer_token = Access::bearer_token();
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:53198/api/Output',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$bearer_token
            ),
          ));
  
          $response = curl_exec($curl);
          $jsonArray = json_decode($response,true);
  
          
          curl_close($curl);
          return $jsonArray;
        }
  
        public static function door()
        {
          $curl = curl_init();
          $bearer_token = Access::bearer_token();
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:53198/api/Door',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$bearer_token
            ),
          ));
  
          $response = curl_exec($curl);
          $jsonArray = json_decode($response,true);
  
          
          curl_close($curl);
          return $jsonArray;
        }
    }
  
    /** This seection is for Net2 */
    class Paxton{
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
  
      public static function user_paxton()
      {
        $token = Paxton::bearer_token();
        $cache_file = $_SERVER['DOCUMENT_ROOT']."/eticom/pax-users/sample.txt";

        //Check if the cache file exists and is not expired (1min)
        if(file_exists($cache_file) && (time() - filemtime($cache_file) < 5 )){
          $response = file_get_contents($cache_file);
        }else{
          //make the curl request
          $curl = curl_init();
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_TCP_NODELAY  => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$token
            ),
          ));

          $response = curl_exec($curl);
          curl_close($curl);

          if(!file_exists($cache_file)){
            $fp = fopen($cache_file, 'w');
            fwrite($fp, '');
            fclose($fp);
        }

          //save the response to the cache file
          file_put_contents($cache_file, $response);
        }
      }
    
      /**Paxton Form Processing Begins */
      public static function paxton_create($create_data, $url)
      {
          $token = Paxton::bearer_token();

          $ch = curl_init();
          $headers  = [
                      'Authorization: Bearer '.$token,
                      'Content-Type: application/json'
                  ];

          curl_setopt($ch, CURLOPT_URL,"http://193.178.55.230:4005/api/v1/users");
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($create_data));           
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          $result = curl_exec($ch);
        
          $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

          curl_close($ch);

        
          // header("Refresh:2; url=$url");
          header("Location: $url");
      }

      public static function paxton_update($paxton_data, $url)
      {
        $token = Paxton::bearer_token();
        $updateId   =  $_POST['id'];
          $ch = curl_init();
          $headers  = [
                      'Authorization: Bearer '.$token,
                      'Content-Type: application/json'
                  ];
          $postData = array(
              'id'          => $_POST['id'],
              'firstName'   => $_POST['firstname'],
              'lastName'    => $_POST['lastname'],
              'telephone'   => $_POST['telephone'],
              'pin'         => $_POST['pin']
          );
          curl_setopt_array($ch, array(
            CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users/'.$updateId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_HTTPHEADER => array(
              'Accept: application/json',
              'Authorization: Bearer '.$token.'',
              'Content-Type: application/x-www-form-urlencoded'
            ),
          ));
          $response = curl_exec($ch);
          curl_close($ch);

          header("Location: $url");
      }

      public static function accesslevels_paxton()
      {
        $token = Paxton::bearer_token();
  
        $curl = curl_init();
  
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/accesslevels',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$token
            ),
          ));
          $response = curl_exec($curl);
          $jsonArray = json_decode($response,true);
  
          curl_close($curl);
          return $jsonArray;
      }

      public static function accesslevels_details_paxton($id)
      {
        $token = Paxton::bearer_token();
  
        $curl = curl_init();
  
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/accesslevels/'.$id.'/detail',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$token
            ),
          ));
          $response = curl_exec($curl);
          $jsonArray = json_decode($response,true);
  
          curl_close($curl);
          return $jsonArray;
      }


      public static function doors_paxton()
      {
        $token = Paxton::bearer_token();
  
        $curl = curl_init();
  
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/doors/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer '.$token
            ),
          ));
          $response = curl_exec($curl);
          $jsonArray = json_decode($response,true);
  
          curl_close($curl);
          return $jsonArray;
      }

      public static function open_door($door_id)
      {
        $curl = curl_init();
        $token = Paxton::bearer_token();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/commands/door/open',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => 'username=System%20engineer&password=Renard1966&grant_type=password&client_id=5bda30f7-4f61-4be6-84fa-a253799e14c0&DoorId='.$door_id.'',
          CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer '.$token.'',
            'Content-Type: application/x-www-form-urlencoded'
          ),
        ));
        
        $response = curl_exec($curl);
        $jsonArray = json_decode($response,true);
        
        curl_close($curl);
        return $jsonArray;
      }
    public static function paxton_access($access_decode, $url)
    {
      $curl = curl_init();

      $token = Paxton::bearer_token();

      $id     = $_POST['id'];
      $name   = $_POST['name'];
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/accesslevels',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
          "id": '.$id.',
          "name": "'.$name.'",
        "detailRows": [
          
        ]
      }',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer  '.$token.'',
          'Content-Type: application/json'
        ),
      ));
      $response = curl_exec($curl);
      curl_close($curl);

      header("Location: $url");
      
    }
     
    public static function paxton_areas(){


      $curl = curl_init();

      $token = Paxton::bearer_token();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/accesslevels/areas',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.''
        ),
      ));

      $response = curl_exec($curl);
      $jsonArray = json_decode($response,true);
      curl_close($curl);
      return $jsonArray;

    }

    public static function timezones()
    {

        $curl = curl_init();

        $token = Paxton::bearer_token();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/timezones',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token.''
          ),
        ));

        $response = curl_exec($curl);
        $jsonArray = json_decode($response,true);
        curl_close($curl);
        return $jsonArray;

    }

    public static function create_timezones($name, $mon, $tue, $wed, $thurs, $fri, $pub, $url)
    {
        $token = Paxton::bearer_token();

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/timezones',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "Name": "'.$name.'",
            "Timeslots": [
              '.$mon.',
              '.$tue.',
              '.$wed.',
              '.$thurs.',
              '.$fri.',
              '.$pub.'
            ]
          }',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token.'',
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        header("Location: $url");

    }

    public static function update_access_levels($data, $url)
    {
      $id     = $_POST['id'];
      $token = Paxton::bearer_token();
      $curl = curl_init();
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/accesslevels/'.$id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.'',
          'Content-Type: application/json'
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);

      header("Location: $url");

    }

    public static function area_details($id)
    {
      $token = Paxton::bearer_token();
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/accesslevels/'.$id.'/detail',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.''
        ),
      ));

      $response = curl_exec($curl);
      
      curl_close($curl);
      $jsonArray = json_decode($response,true);
      return $jsonArray['detailRows'];

    }

    public static function timezone_details($singleId)
    {
      $token = Paxton::bearer_token();
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/timezones/'.$singleId.'/detail',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.''
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      $jsonArray = json_decode($response,true);
      return $jsonArray;
    }

    public static function update_timezones($name, $id, $mon, $tue, $wed, $thurs, $fri, $sat, $sun)
    {
      $token = Paxton::bearer_token();
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/timezones/'.$id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS =>'{
        "Id": '.$id.',
        "Name": "'.$name.'",
        "Timeslots": [
         '.$mon.',
         '.$tue.',
         '.$wed.',
         '.$thurs.',
         '.$fri.',
         '.$sat.',
         '.$sun.'
        ]
      }',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.'',
          'Content-Type: application/json'
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      echo $response;

    }

    public static function timezone_delete($deleteId, $url)
    {
      $token = Paxton::bearer_token();
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/timezones/'.$deleteId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.'',
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);

      header("Location: $url");
    }

    public static function get_events()
    {
      $token = Paxton::bearer_token();
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/events/latestunknowntokens',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.'',
        ),
      ));

      $response = curl_exec($curl);

      $jsonArray = json_decode($response,true);

      curl_close($curl);

      return $jsonArray;
    }

    public static function event_key($userTId, $tokenType, $tokenValue, $url)
    {
      $token = Paxton::bearer_token();
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users/'.$userTId.'/tokens',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
          "tokenType": "'.$tokenType.'",
          "tokenValue": "'.$tokenValue.'",
          "isLost": false
        }',
        CURLOPT_HTTPHEADER => array(
          'Authorization: Bearer '.$token.'',
          'Content-Type: application/json'
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);

      header("Location: $url");
    }

    public static function get_all_keys($userKeyId)
    {
      $token = Paxton::bearer_token();
      $cache_file = $_SERVER['DOCUMENT_ROOT']."/eticom/pax-users/sample-keys'.$userKeyId.'.txt";

      if(file_exists($cache_file) && (time() - filemtime($cache_file) < 60 )){
        $response = json_decode(file_get_contents($cache_file), true);
      }else{
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users/'.$userKeyId.'/tokens',
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_TCP_NODELAY  => true,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token.'',
          ),
        ));
        $response = curl_exec($curl);
        
        curl_close($curl);

        if(!file_exists($cache_file)){
          $fp = fopen($cache_file, 'w');
          fwrite($fp, $userKeyId);
          fclose($fp);
        }
        file_put_contents($cache_file, $response);
      }
        $jsonArray = json_decode($response,true);
        return $jsonArray;

    }

  }

