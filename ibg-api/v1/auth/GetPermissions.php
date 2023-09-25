<?php


function getPermArr($db, $user_id){

    $qry = "SELECT * FROM user_role_assignment WHERE user_id = ?";
    $permission_obj = $db->prepare($qry);
    
    $permission_obj->bind_param("i",$user_id);
    if($permission_obj->execute()){
        $data = $permission_obj->get_result();
        if($data->num_rows>0){
           
            while($row = $data->fetch_assoc()){
                $user_role_id = $row['user_role_id'];
               


                $perm_array = getPermissionArray($db, $user_role_id);
                return $perm_array;
            }

        }
    }
    return false;

}



function getPermissionArray($db, $user_role_id){

    $qry = "SELECT * FROM user_role WHERE id = ?";
    $permission_obj = $db->prepare($qry);
    
    $permission_obj->bind_param("i",$user_role_id);
    if($permission_obj->execute()){
        $data = $permission_obj->get_result();
        if($data->num_rows>0){
           
            $row = $data->fetch_assoc();
            
            $removeKeys = array('id', 'owner_level', 'is_admin', 'is_level_default','description','owner_id');

            foreach($removeKeys as $key) {
            unset($row[$key]);
            }
            return $row;
        }
    }

    return false;
}