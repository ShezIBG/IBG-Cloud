<?php


function getUserObj($db, $user_id){

    $qry = "SELECT * FROM userdb WHERE id = ?";
    $token_obj = $db->prepare($qry);
    
    $token_obj->bind_param("i",$user_id);
    if($token_obj->execute()){
        $data = $token_obj->get_result();
        if($data->num_rows>0){
           
            $row = $data->fetch_assoc();

           return $row;

        }
    }


}



function arrayToObject(array $array, $className) {
    return unserialize(sprintf(
        'O:%d:"%s"%s',
        strlen($className),
        $className,
        strstr(serialize($array), ':')
    ));
}

?>