<?php
// ini_set("display_errors", 1);
//Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charst=UTF-8' );
include "../auth/api_header.php";

if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $headers = getallheaders();
    //$bearer_token = $headers["Authorization"];

    try{
        $bearer_token = getBearerToken();
        
        
        if($bearer_token){
            //Connect to DB
            $db = new authDatabase();
            $connect = $db->connect();

            $verifyToken = verifyToken($connect, $bearer_token);
            $permArray = getPermArr($connect, $verifyToken);

            if ($verifyToken){
                //Get all buildings if true
                $user_id = $verifyToken;
                // $user_object = getUserObj($connect, $user_id);
                $user = USER::check_user_login($user_id);
                // $userObject = (object) $user_object;
                
                $building = $user->get_default_building_api(Permission::METERS_ENABLED, '', true, $permArray);
                $list = Building::list_with_permission(Permission::METERS_ENABLED, true) ?: [];
                $list_array = array();
                foreach ($list as $b) {
                    $list_array[$b->id ] = $b->description;

                }

                http_response_code(200);
                echo json_encode(
                    array(
                        'status'=> '1',
                        'Request' => $list_array,
                        )
                );
    

            }else{
                http_response_code(401);
                echo json_encode(
                    array(
                        'status' => '0',
                        'response'=> "Invalid token used",
                        )
                );

            }


           
        }else{
            http_response_code(404);
            echo json_encode(
                array(
                    'status' => '0',
                    'response'=> "Valid token required"
                    )
            );

        }

    }catch(Expection $ex){
        http_response_code(500);
        echo json_encode(
            array(
                'status' => '0',
                'response'=> $ex->getMessage()
                )
        );
    }





}










?>