<?php
//Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charst=UTF-8' );


include "../auth/api_header.php";


if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $data = json_decode(file_get_contents("php://input"));
    $building_id = $_GET['building_id'];
    if(!empty($building_id)){
        try{
            $bearer_token = getBearerToken();
            if($bearer_token){
                $db = new authDatabase();
                $connect = $db->connect();
                
                //outputs user id once verified
                $verifyToken = verifyToken($connect, $bearer_token);
                $permArray = getPermArr($connect, $verifyToken);
                //Connect to DB



                
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

                // print_r($list_array);exit;

                $bulding_access = isset($list_array[$building_id]) ? $list_array[$building_id] : false;
                
                if($bulding_access){
                    if($verifyToken){
                        $meter_list = GetMeters($building_id, $connect);
    
                        http_response_code(200);
                        echo json_encode(
                            array(
                                'status'=> '1',
                                'Request' => $meter_list
                                )
                        );
                    }else{
                       
    
                        http_response_code(401);
                        echo json_encode(
                            array(
                                'status'=> '1',
                                'Request' => "Token invalid"
                                )
                        );
    
                    }
                }else{
                    http_response_code(401);
                    echo json_encode(
                        array(
                            'status'=> '1',
                            'Request' => "You do not have permission to view this buildings meters"
                            )
                    );




                }
               



            }
        }catch(Expection $ex){
            http_response_code(400);
            echo json_encode(
                array(
                    'status'=> '0',
                    'Request' => "www"
                    )
            );

        }
    }else{
        http_response_code(400);
            echo json_encode(
                array(
                    'status'=> '0',
                    'Request' => "All inputs fields required"
                    )
            );
    }
}else{
    http_response_code(400);
    echo json_encode(
        array(
            'status'=> '0',
            'Request' => "Incorrect POST/GET method used"
            )
    );
}