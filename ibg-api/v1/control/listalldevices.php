<?php
ini_set("display_errors", 1);
//Headers

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charst=UTF-8' );
include "../auth/api_header.php";


if($_SERVER['REQUEST_METHOD'] === 'GET'){
    
    $building_id = $_GET['building_id'];
 
    if(!empty($building_id)){
        $bearer_token = getBearerToken();
        
        if($bearer_token){
            //Connect to DB
            $db = new authDatabase();
            $connect = $db->connect();
            //Verify usertoken
            $verifyToken = verifyToken($connect, $bearer_token);
            //Get permission for user level
            $permArray = getPermArr($connect, $verifyToken);
            if($verifyToken){
                
                $user_id = $verifyToken;
                // $user_object = getUserObj($connect, $user_id);
                $user = USER::check_user_login($user_id);
                // $userObject = (object) $user_object;
                
                $building = $user->get_default_building_api(Permission::METERS_ENABLED, '', true, $permArray);
                $list = Building::list_with_permission(Permission::METERS_ENABLED, true) ?: [];

                
                $buildinglist = array();
                foreach ($list as $b) {
                    $buildinglist[] = $b->id ;

                }
                //check if the control module is enabled 
                $control_enabled = getControlEnabled($building_id, $connect);
                //print_r($buildinglist);exit;

                if($control_enabled){

                    // isset($list_array[$data->building_id]) 
                    $db_knx = $db->device_connect($building_id);
                    $device_list = getDeviceList($buildinglist,  $building_id, $db_knx);

                    if($device_list=='No access'){
                        http_response_code(400);
                        echo json_encode(
                            array(
                                'status'=> '0',
                                'Response' => "You do not have permission to view this building"
                                )
                        );
                        exit;
                    }
                    if($device_list){

                        http_response_code(200);
                        echo json_encode(
                            array(
                                'status'=> '1',
                                'Response' => $device_list
                                )
                        );
                    }else{
                        http_response_code(400);
                        echo json_encode(
                            array(
                                'status'=> '0',
                                'Response' => "No Control items found for this building"
                                )
                        );


                    }
                }else{
                    http_response_code(400);
                    echo json_encode(
                        array(
                            'status'=> '0',
                            'Response' => "This building is not authorised for the Control Module"
                            )
                    );
                }
            }else{
                http_response_code(400);
                echo json_encode(
                    array(
                        'status'=> '0',
                        'Response' => "Token Invalid"
                        )
                );
            }
        }else{
            http_response_code(400);
            echo json_encode(
                array(
                    'status'=> '0',
                    'Response' => "Token Missing"
                    )
            );
        }


    }else{
        http_response_code(400);
        echo json_encode(
            array(
                'status'=> '0',
                'Response' => "All inputs required"
                )
        );

    }
}else{
    http_response_code(400);
    echo json_encode(
        array(
            'status'=> '0',
            'Response' => "Incorrect POST/GET method used"
            )
    );
}