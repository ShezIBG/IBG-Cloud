<?php

function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * get access token from header
 * */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}




function verifyToken($db, $token){
    $Requested_time = date("Y-m-d H:i:s"); //current time
    
    $qry = "SELECT * FROM user_auth WHERE token = ?";
    $token_obj = $db->prepare($qry);
    
    $token_obj->bind_param("s",$token);
    if($token_obj->execute()){
        $data = $token_obj->get_result();
        if($data->num_rows>0){
            while($row = $data->fetch_assoc()){
                $user_token = $row['token'];
                $user_token_exp = $row['exp_time']; //user_token time
                $user_id = $row['user_id'];
            }
            $diff_qry = "SELECT TIMESTAMPDIFF(MINUTE,?,?)";
            $diff_obj = $db->prepare($diff_qry);
            $diff_obj->bind_param("ss", $user_token_exp, $Requested_time);
            
            if($diff_obj->execute()){
                $data_diff = $diff_obj->get_result();
                
                if($data_diff->num_rows>0){
                    while($row = $data_diff->fetch_assoc()){
                        $token_time_difference = $row["TIMESTAMPDIFF(MINUTE,?,?)"];
                    }

                    if ($token_time_difference >= 30){
                        return false;

                    }
                }
                return  $user_id;
            }
        }
    
        return null;
    }
}


?>