<?php
ini_set("display_errors", 1);
//Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json; charst=UTF-8' );

include_once 'db_auth.php';
include_once 'user_auth.php';

//Check for POST request
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    //Extract data from POST request
    $data = json_decode(file_get_contents("php://input"));

    if(!empty($data->email) && !empty($data->password)){
        //Create DB connection
        $db = new authDatabase();

        $connect = $db->connect();
        $user_obj = new Userapi($connect);
        //pass in POST variable to user_auth class
        $user_obj->email = $data->email;
        $user_obj->password = $data->password;
        //Check if user exists
        $user_exists = $user_obj->check_user_exists();
        
        if($user_exists[0] > 0){
            //Return token and exp_time
            if($user_exists[1] == true){
                $token =  $user_obj->create_token($user_exists[0]);
                http_response_code(200);
                echo json_encode(
                    array(
                        'status'=> '1',
                        'token' =>  $token[0],
                        'expire_time' => $token[1])
                );


            }else{

                http_response_code(401);
                echo json_encode(
                    array(
                        'status' => '0',
                        'response'=> 'This User does not have valid API access, please contact your system administrator or IBG support to gain access.')
                );
            }

           
        }else{
            http_response_code(404);
            echo json_encode(
                array(
                    'status' => '0',
                    'response'=> 'User credentials not valid!')
            );


        }

    }else{
        http_response_code(406);
        echo json_encode(
            array(
                'status' => '0',
                'response'=> 'Please provide all required inputs')
        );
        
    }
}else{
    http_response_code(407);
    echo json_encode(
        array(
            'status' => '0',
            'response'=> 'Expected POST request')
    );


}



// Sql Example
// App::sql()->query(
//     "SELECT....", MySQL::QUERY_ASSOC);

