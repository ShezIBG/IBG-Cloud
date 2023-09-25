<?php
//   /** Paxton Form Processing Starts */
if (isset($_POST['submit'])) {

  $firstname  = $_POST['firstname'];
  $lastname   = $_POST['lastname'];
  $email      = $_POST['email'];
  $telephone  = $_POST['telephone'];
  $pin        = $_POST['pin'];

  $contact_data = array(
      "firstname" => $firstname,
      "lastname"  => $lastname,
      "email"     => $email,
      "telephone" => $telephone,
      "pin"       => $pin
  );
  //print_r($contact_data['firstname']); exit;
  $ans_hubspot = new Paxton();
  $ans_hubspot->paxton_create($paxton_data);

}
    class Access {

        public static function bearer_token()
        {
            //Get User ID for current session
            $id = $_SESSION[SESSION_NAME_USER_ID];
            //print_r($id);
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
            //var_dump($result);
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
  
      public static function user_paxton()
      {
        $token = Paxton::bearer_token();
        // print_r($token); exit;
  
        $curl = curl_init();
  
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://193.178.55.230:4005/api/v1/users',
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
      /**Paxton Form Processing Begins */
      public static function paxton_create($paxton_data)
      {
          $token = Paxton::bearer_token();

          $ch = curl_init();
          $headers  = [
                      'Authorization: Bearer '.$token,
                      'Content-Type: application/json'
                  ];
          $postData = [
              'firstName'   => $_POST['firstname'],
              'lastName'    => $_POST['lastname'],
              'email'       => $_POST['email'],
              'telephone'   => $_POST['telephone'],
              'pin'         => $_POST['pin']
          ];
          //print_r($postData); exit;
          curl_setopt($ch, CURLOPT_URL,"http://193.178.55.230:4005/api/v1/users");
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));           
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          $result = curl_exec($ch);
        
          $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

          header('Location: http://192.168.10.18/eticom/dashboard#view/dashboard/access');
      }

      public static function accesslevels_paxton()
      {
        $token = Paxton::bearer_token();
        // print_r($token); exit;
  
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
        // print_r($token); exit;
  
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
        // print_r($token); exit;
  
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

    }
