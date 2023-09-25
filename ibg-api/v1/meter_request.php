<?php


function GetMeters($building_id, $db){
    $qry = "SELECT * FROM meter WHERE building_id = ?";
    $meter_obj = $db->prepare($qry);
    
    $meter_obj->bind_param("i", $building_id);
    if($meter_obj->execute()){
        $data = $meter_obj->get_result();
        if($data->num_rows>0){
            $row = $data->fetch_all();
            
            foreach($row as $meter){
                $meter_name[$meter[0]]= $meter[1];

            }
           
          
            return $meter_name;

        }
    }else{


        return null;
    }
}




function GetMeterMonthly($start_date, $end_date, $meters, $db){
    //print_r($meters);exit;
    $i = 0;
    foreach($meters as $m){
        $qry = "SELECT meter_id, reading_day, total_imported_total FROM automated_meter_reading_history 
        WHERE (reading_day BETWEEN ? AND ? ) AND meter_id IN ($m)";
        
        $meter_obj = $db->prepare($qry);
        
        $meter_obj->bind_param("ss", $start_date, $end_date);
            if($meter_obj->execute()){
                $data = $meter_obj->get_result();
                
                if($data->num_rows>0){
                    $row = $data->fetch_all();

                    foreach($row as $meter){
                        $meter_name[$i][]= $meter;
        
                    }
                    $i = $i + 1;
                }else{
                    return false;
                }



            }
    }
    //print_r($meter_name);exit;
    return $meter_name;





}


function GetMeterHourly($date, $meter, $db){

    
    for ($x = 0; $x <= 23; $x++) {
        $hourly_cols[] = "unit_imported_total_hour_".$x ;
    } 
    $hourl_qry = implode(', ', $hourly_cols);

    $qry = "SELECT $hourl_qry FROM meter_usage_hourly_step_60_days 
    WHERE date = ? AND meter_id = ?";
    
    $meter_obj = $db->prepare($qry);
            
    $meter_obj->bind_param("si", $date, $meter);
        if($meter_obj->execute()){
            $data = $meter_obj->get_result();
            
            if($data->num_rows>0){
                $c = 0;
                $row = $data->fetch_all();
               
                foreach($row as $hourly){
                   
                  $hourly_arr = $hourly;

                }
                // while($row = $data->fetch_assoc()){
                //     $meter_type[] = $row["unit_imported_total_hour_".$c];
                //     $c = $c + 1;  
                   
                // }
                return $hourly;
            }


            return false;
        }
    return null;
        

}

function CheckMeterAuth($buildinglist, $request_meter, $db, $type = false){
    
    foreach($buildinglist as $b){
        $qry = "SELECT id FROM meter WHERE building_id = ?";
        $meter_obj = $db->prepare($qry);
        
        $meter_obj->bind_param("i", $b);
        if($meter_obj->execute()){
            $data = $meter_obj->get_result();
            if($data->num_rows>0){
                $row = $data->fetch_all();
            
                foreach($row as $meter){
                    $meter_name[]= $meter[0];
    
                }

            }else{
                return null;
            }
        }else{
            return null;
        }

    }

    // print_r($meter_name);exit;
    if($type == true){
        if (!in_array($request_meter, $meter_name)){

            return false;
        }
        return true;
    }else{
        foreach($request_meter as $rm){
            if (!in_array($rm, $meter_name)){
    
                return false;
            }
        }
    
        return true;
    }
   
    
}

function meter_type($meter_id, $db){
   
    $qry = "SELECT meter_type FROM meter WHERE id = ?";

    $meter_obj = $db->prepare($qry);
    
    $meter_obj->bind_param("i", $meter_id);
    if($meter_obj->execute()){
        $data = $meter_obj->get_result();
      
        if($data->num_rows>0){
            while($row = $data->fetch_assoc()){
                $meter_type = $row['meter_type'];      
            }

        }
    }
    
    return $meter_type;


}

function meter_desc($meter_id, $db){
   
    $qry = "SELECT description FROM meter WHERE id = ?";
   
    $meter_obj = $db->prepare($qry);
    
    $meter_obj->bind_param("i", $meter_id);
    if($meter_obj->execute()){
        
        $data = $meter_obj->get_result();
       
        if($data->num_rows>0){
            
            while($row = $data->fetch_assoc()){
               
                $meter_desc = $row['description'];      
            }

        }
    }
   
    return $meter_desc;


}



function CheckMeter($meter_list, $type, $db, $arr_type = false){
    
    if($type == 'E' && $arr_type == false){
        foreach($meter_list as $m){
            $qry = "SELECT meter_type FROM meter WHERE id = ?";
            $meter_obj = $db->prepare($qry);

            $meter_obj->bind_param("i", $m);
            if($meter_obj->execute()){
                $data = $meter_obj->get_result();
              
                if($data->num_rows>0){
                    while($row = $data->fetch_assoc()){
                        $meter_type = $row['meter_type'];      
                    }

                }
            }
            if($meter_type != 'E'){
                return false;
            }
        }
        return true;
    }

    if($type == 'E' && $arr_type == true){
       
        $qry = "SELECT meter_type FROM meter WHERE id = ?";
        $meter_obj = $db->prepare($qry);

        $meter_obj->bind_param("i", $meter_list);
        if($meter_obj->execute()){
            $data = $meter_obj->get_result();
            
            if($data->num_rows>0){
                while($row = $data->fetch_assoc()){
                    $meter_type = $row['meter_type'];      
                }

            }
        }
        if($meter_type != 'E'){
            return false;
        }

        return true;

    }


    if($type == 'W' && $arr_type == false){
        foreach($meter_list as $m){
            $qry = "SELECT meter_type FROM meter WHERE id = ?";
            $meter_obj = $db->prepare($qry);

            $meter_obj->bind_param("i", $m);
            if($meter_obj->execute()){
                $data = $meter_obj->get_result();
              
                if($data->num_rows>0){
                    while($row = $data->fetch_assoc()){
                        $meter_type = $row['meter_type'];      
                    }

                }
            }
            if($meter_type != 'W'){
                return false;
            }
        }
        return true;
    }


    if($type == 'W' && $arr_type == true){
       
        $qry = "SELECT meter_type FROM meter WHERE id = ?";
        $meter_obj = $db->prepare($qry);

        $meter_obj->bind_param("i", $meter_list);
        if($meter_obj->execute()){
            $data = $meter_obj->get_result();
            
            if($data->num_rows>0){
                while($row = $data->fetch_assoc()){
                    $meter_type = $row['meter_type'];      
                }

            }
        }
        if($meter_type != 'W'){
            return false;
        }

        return true;

    }




    if($type == 'G' && $arr_type == false){
        foreach($meter_list as $m){
            $qry = "SELECT meter_type FROM meter WHERE id = ?";
            $meter_obj = $db->prepare($qry);

            $meter_obj->bind_param("i", $m);
            if($meter_obj->execute()){
                $data = $meter_obj->get_result();
              
                if($data->num_rows>0){
                    while($row = $data->fetch_assoc()){
                        $meter_type = $row['meter_type'];      
                    }

                }
            }
            if($meter_type != 'G'){
                return false;
            }
        }
        return true;
    }


    if($type == 'G' && $arr_type == true){
       
        $qry = "SELECT meter_type FROM meter WHERE id = ?";
        $meter_obj = $db->prepare($qry);

        $meter_obj->bind_param("i", $meter_list);
        if($meter_obj->execute()){
            $data = $meter_obj->get_result();
            
            if($data->num_rows>0){
                while($row = $data->fetch_assoc()){
                    $meter_type = $row['meter_type'];      
                }

            }
        }
        if($meter_type != 'G'){
            return false;
        }

        return true;

    }




}


function validateDate($dateStr, $format = 'Y-m-d')
{
    // $Newdate = new DateTime($dateStr);
    //$result = $Newdate->format('Y-m-d');
    $check = strtotime($dateStr);
   
    //print_r(date($format, $check));exit;
    date_default_timezone_set('UTC');

    $date = date($format, $check);
    //$date = DateTime::createFromFormat($format, $dateStr);
    
    // print_r($date);exit;
    if($dateStr != $date){
        return false;




    }
    
    return true;


}




function initdateVerify($date, $meter_list, $db, $type_arr = false){
    
    if($type_arr){
        $qry = "SELECT init_date FROM meter WHERE id = ?";
            
        $meter_obj = $db->prepare($qry);
                
        $meter_obj->bind_param("i", $meter_list);

        if($meter_obj->execute()){
            $data = $meter_obj->get_result();
        
            if($data->num_rows>0){
                $row = $data->fetch_assoc();
               $initDate = $row['init_date'];
            }
        }
        if($initDate > $date){
            return ("The requested date cannot be before your meters installation date of: ".$initDate);
        }
    
        return true;

    }else{
        foreach($meter_list as $m){
            $qry = "SELECT init_date FROM meter WHERE id = ?";
            
            $meter_obj = $db->prepare($qry);
                    
            $meter_obj->bind_param("i", $m);
    
            if($meter_obj->execute()){
                $data = $meter_obj->get_result();
            
                if($data->num_rows>0){
                    $row = $data->fetch_assoc();
                   $initDate = $row['init_date'];
    
                }
            }
            if($initDate > $date){
                return ("The requested date cannot be before your meters installation date of: ".$initDate);
            }
    
        }
    
        return true;
    }

}


?>