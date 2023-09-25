<?php
//Headers

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charst=UTF-8' );
include "../auth/api_header.php";


if($_SERVER['REQUEST_METHOD'] === 'GET'){
    //$data = json_decode(file_get_contents("php://input"));

    $meter_id = $_GET['meter_id'];

    if(!empty($meter_id)){
        $bearer_token = getBearerToken();
        
        if($bearer_token){
            //Connect to DB
            $db = new authDatabase();
            $connect = $db->connect();
            $verifyToken = verifyToken($connect, $bearer_token);
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
                
                //print_r($buildinglist);exit;

                // isset($list_array[$data->building_id]) 
                $meter_list = CheckMeterAuth($buildinglist, $meter_id, $connect, true);
                
                
                if($meter_list){
                    
                    //Check if all meters are of correct type 'E' Elec
                    $meter_type = meter_type($meter_id, $connect);
                    
                    
                    if($meter_type){
                        http_response_code(200);
                            echo json_encode(
                                array(
                                    'status'=> '1',
                                    'Response' => $meter_type
                                    )
                            );
                        }
                    }else{
                        http_response_code(400);
                        echo json_encode(
                            array(
                                'status'=> '0',
                                'Response' => 'Error getting meter type'
                                )
                        );
                    }
                }else{
                    http_response_code(400);
                    echo json_encode(
                        array(
                            'status'=> '0',
                            'Response' => 'Not authorized to view this meter'
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
    }
    else{
    http_response_code(400);
    echo json_encode(
        array(
            'status'=> '0',
            'Response' => "Token Missing"
            )
    );
    }


}
else{
    http_response_code(400);
    echo json_encode(
        array(
            'status'=> '0',
            'Response' => "Incorrect POST/GET method used"
            )
    );
}