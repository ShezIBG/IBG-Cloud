<?php
ini_set("display_errors", 1);
//Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charst=UTF-8' );

include 'user_auth.php';

if($_SERVER['REQUEST_METHOD'] === 'GET'){
    
    $headers = getallheaders();
    //$bearer_token = $headers["Authorization"];


    try{
        $bearer_token = getBearerToken();
        
        
        if($bearer_token){
            //Connect to DB
            $db = new authDatabase();
            $connect = $db->connect();

            $user_obj = new Userapi($connect);
            
           

            $verifyToken = verifyToken($connect, $bearer_token);
            $permArray = getPermArr($connect, $verifyToken);

            if ($verifyToken){
                //Get all buildings if true
                $user_id = $verifyToken;
                //$user_object = getUserObj($connect, $user_id);
                $user = USER::check_user_login($user_id);
                // $userObject = (object) $user_object;
                
                $building = $user->get_default_building_api(Permission::METERS_ENABLED, '', true, $permArray);
                
                $list = Building::list_with_permission(Permission::METERS_ENABLED, true) ?: [];
                $list_array = array();
                foreach ($list as $b) {
                    $list_array[] = $b;

                }
                

                $user_obj->building_array = $list_array;
                
                try {
                    $auth_meters = $user_obj->get_user_meter_list('', true, true);
                } catch (Exception $e) {
                    // Handle the exception here
                    http_response_code(401);
                    echo json_encode(
                        array(
                            'status' => '0',
                            'response'=> "Unable to get meter list for this user. Please contact the system administrator",
                            )
                    );
                }

                
                //print_r($building_array);exit;



                http_response_code(200);
                echo json_encode(
                    array(
                        'status'=> '1',
                        'Request' =>  $auth_meters,
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