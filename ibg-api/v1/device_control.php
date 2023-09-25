<?php


function getControlEnabled($building_id, $db){

    $qry = "SELECT module_control FROM building WHERE id = ?";
    $control_obj = $db->prepare($qry);
    
    $control_obj->bind_param("i", $building_id);

    if($control_obj->execute()){
        $data = $control_obj->get_result();
        if($data->num_rows>0){
            while($row = $data->fetch_assoc()){
                $control_enabled = $row['module_control'];      
            }

        }
        
        if($control_enabled){
            return true;
        }else{
            return false;
        }
    }
    
}


function getDeviceList($buildinglist,  $building_id, $db){
    if (!in_array($building_id, $buildinglist)){

        return "No access";
    }
    else{
        $i = 0;
        $qry = "SELECT id, type_id,description FROM item";
        $control_obj = $db->prepare($qry);
        
        if($control_obj->execute()){
            $data = $control_obj->get_result();
            if($data->num_rows>0){
                $row = $data->fetch_all();
                
                foreach($row as $list){
                    if($list[1] == 1){
                        $devicelist[$list[0]] = ['Type: Light',$list[2]];
                    }else if ($list[1] == 2){
                        $devicelist[$list[0]] = ['Type: Air-con',$list[2]];
                    }
                    
                    //$i = $i + 1;
                }
                
                return $devicelist;
                

            }else{
                return null;
            }
        }else{
            return null;
        }
    }
}


function getDeviceState($buildinglist,  $building_id, $item_id, $slot_id, $db){
    if (!in_array($building_id, $buildinglist)){

        return "No access";
    }
    else{
        $i = 0;
        foreach($item_id as $item){
            $qry = "SELECT knx_id FROM item_slot WHERE item_id = ? AND slot_id = ?";
            $control_obj = $db->prepare($qry);
            $control_obj->bind_param("ii", $item, $slot_id);
            if($control_obj->execute()){
                $data = $control_obj->get_result();
                if($data->num_rows>0){
                    $row = $data->fetch_assoc();
                    
                    $knx_id = $row['knx_id'];
                    // /print_r($knx_id);exit;

                    $knx_qry = "SELECT baos_value FROM device WHERE id = ?";
                    $knx_obj = $db->prepare($knx_qry);
                    
                    $knx_obj->bind_param("i", $knx_id);
                    
                    if($knx_obj->execute()){
                        $data_knx = $knx_obj->get_result();
                        if($data_knx->num_rows>0){
                            $knx_row = $data_knx->fetch_assoc();

                            $baos_val = $knx_row['baos_value'];
                        }
                    }
                   
                    $state_array[$item] = $baos_val;
    
                }else{
                    return null;
                }
            }else{
                return null;
            }

            
        }

      return $state_array;
    }
}


function checkDevice($device_id, $db_knx, $type){







}





 