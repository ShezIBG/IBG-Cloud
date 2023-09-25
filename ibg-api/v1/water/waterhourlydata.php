<?php
//Headers

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charst=UTF-8' );
include "../auth/api_header.php";

if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $date = $_GET['date'];
    $meter_id = $_GET['meter_id'];

    if(!empty($date) && !empty($meter_id)){
        $bearer_token = getBearerToken();
        
        if($bearer_token){
            //Connect to DB
            $db = new authDatabase();
            $connect = $db->connect();
            $verifyToken = verifyToken($connect, $bearer_token);
            
            if($verifyToken){
                $permArray = getPermArr($connect, $verifyToken);
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
                    $meter_elec = CheckMeter($meter_id, 'W', $connect, true);
                    //Check correct format is used Y/m/d
                    $date_check = validateDate($date);

                    $initDate = initdateVerify($date, $meter_id, $connect, true);
                    
                    if(is_bool($initDate)){
                        //print_r($str_date_check);exit;
                        if($date_check == true){
                            if($meter_elec){
                                $meter_data = GetMeterHourly($date, $meter_id, $connect);
                                
                                if($meter_data){
                                    
                                    http_response_code(200);
                                    echo json_encode(
                                        array(
                                            'status'=> '1',
                                            'Response' => $meter_data
                                            )
                                    );
    
                                }else{
                                    
                                    http_response_code(400);
                                    echo json_encode(
                                        array(
                                            'status'=> '0',
                                            'Response' => "Not data found for meter/period"
                                            )
                                    );
    
                                }
                            }
                            else{
                                http_response_code(400);
                                echo json_encode(
                                    array(
                                        'status'=> '0',
                                        'Response' => "Incorrect Meter Type - Expected Water meter only."
                                        )
                                );
    
    
    
                            }
                        }else{
                            http_response_code(400);
                            echo json_encode(
                                array(
                                    'status'=> '0',
                                    'Response' => "Incorrect date - Expected date format: Y-m-d."
                                    )
                            );
    
                        }
                    }else{
                        http_response_code(400);
                        echo json_encode(
                            array(
                                'status'=> '0',
                                'Response' => $initDate
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
        }else{
            http_response_code(400);
            echo json_encode(
                array(
                    'status'=> '0',
                    'Response' => "Token Missing"
                    )
            );
        }


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