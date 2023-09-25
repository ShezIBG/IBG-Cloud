<?php
ini_set("display_errors", 1);
//Headers

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charst=UTF-8' );
include "../auth/api_header.php";


if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $data = json_decode(file_get_contents("php://input"));

 
    if(!empty($data->device_id) && !empty($data->building_id)){
        $bearer_token = getBearerToken();
        $slot_id = 1; //lights
        $type = "Lighting";
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
                $control_enabled = getControlEnabled($data->building_id, $connect);
                //print_r($buildinglist);exit;

                if($control_enabled){

                    // isset($list_array[$data->building_id]) 
                    $db_knx = $db->device_connect($data->building_id);
                    //$checkDeviceType = checkDevice($data->device_id, $type, $db_knx);
                    $deviceState = getDeviceState($buildinglist,  $data->building_id, $data->device_id, $slot_id, $db_knx);
                    //$device_list = getDeviceList($buildinglist,  $data->building_id, $db_knx);
                    
                    if($deviceState){

                        http_response_code(200);
                        echo json_encode(
                            array(
                                'status'=> '1',
                                'Response' => $deviceState
                                )
                        );
                    }else{
                        http_response_code(400);
                        echo json_encode(
                            array(
                                'status'=> '0',
                                'Response' => "Light device found for this building"
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