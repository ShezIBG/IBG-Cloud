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

            if ($verifyToken){

                http_response_code(200);
                echo json_encode(
                    array(
                        'status'=> '1',
                        'Request' => 'true',
                        )
                );
    

            }else{
                http_response_code(401);
                echo json_encode(
                    array(
                        'status' => '0',
                        'response'=> "false",
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