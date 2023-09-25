<?php
include "./api_header.php";

class Userapi{

    // Public Variables used to grab POST request email and pass
    public $password;
    public $email;
    public $building_array;
	public $meter_array=[];
    // Private Variables
    private $conn;
    private $tbl_name;
    private $user_password;
    private $user_id;
    private $api_access;
    private $user_name;

    public function __construct($db){

        $this->conn = $db;
        $this->tbl_name= "userdb";

    }

    //Func to check if user exists and verify password
    public function check_user_exists(){
  
        $qry = "SELECT * FROM ".$this->tbl_name." WHERE email_addr = ?";

        $user_obj = $this->conn->prepare($qry);
        
        $user_obj->bind_param("s",$this->email);

        if($user_obj->execute()){
           $data = $user_obj->get_result();
            if($data->num_rows>0){
                
                $user_found[] = array();
                while($row = $data->fetch_assoc()){
                    
                    $this->user_password = $row['password'];
                    $this->user_id = $row['id'];
                    $this->api_access = $row['api_access'];
                    $this->user_name = $row['name'];

                    $verify = password_verify($this->password, $this->user_password);
                  
                    if($verify){
                        $user_account = array($this->user_id, $this->api_access, $this->user_name);
                        
                        return $user_account; 

                    }
                    else{
                        return false; 
                    }
                }
                
            }
            return false;

        }

        return false;
    }






    public function create_token($id){

        $token = bin2hex(random_bytes(16));
        $str_token = strval($token);
        
        $expTime = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +30 minutes"));
        
        $user_id = $id;
        $qry = "INSERT INTO user_auth SET token = ?, user_id = ?, exp_time = ? ON DUPLICATE KEY UPDATE token = ?, exp_time = ?";

        $user_obj = $this->conn->prepare($qry);
        $user_obj->bind_param("sisss",$str_token, $user_id, $expTime, $str_token, $expTime);

        //return multiple values using array
        $return_array = [$token,$expTime];

        if($user_obj->execute()){
            return $return_array;
        }


        return false;

    }


    public function create_token_mobile($id){

        $token = bin2hex(random_bytes(16));
        $str_token = strval($token);
        
        $expTime = date("Y-m-d H:i:s", strtotime("+1 year"));
        // $expTime = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +30 minutes"));
        
        $user_id = $id;
        $qry = "INSERT INTO user_auth SET token = ?, user_id = ?, exp_time = ? ON DUPLICATE KEY UPDATE token = ?, exp_time = ?";

        $user_obj = $this->conn->prepare($qry);
        $user_obj->bind_param("sisss",$str_token, $user_id, $expTime, $str_token, $expTime);

        //return multiple values using array
        $return_array = [$token,$expTime];

        if($user_obj->execute()){
            return $return_array;
        }


        return false;

    }

    public function get_user_meter_list($meter_type_filter = '', $building_structure = false, $virtual = true, $generated = false) {
		$final_array=[];
		$meters_auth=[];
		$b = $this->building_array;
		
		foreach ($b as $building) {
			$area_access = Building::API_get_area_ids_with_permission(Permission::METERS_ENABLED, $building->id);
			
			if(!$area_access) return;
			$area_access = '('.implode(',', $area_access).')';
		
			$building_access = Permission::get_building($building->id)->check(Permission::METERS_ENABLED);

			$floors_data = [];
			
			$get_meters = function($filter, $show_sub) use ($building, &$get_meters, $building_structure) {
				$structure = [];
				// print_r('START');exit;
				if ($meters = Building::get_meters($filter, ['meter.*'], [], 'ORDER BY meter.meter_type, meter.description')) {
					
					foreach($meters as $meter) {
						$meter_filter = [ 'parent_id' => "='$meter->id'"];

						if(isset($filter['meter_type']) && $filter['meter_type']) $meter_filter['meter_type'] = $filter['meter_type'];
						
						$submeters = $show_sub ? $get_meters($meter_filter, true) : [];

						$meter_class = $meter->parent_id ? ' Sub' : '';

						$icon_class = '';
						switch($meter->meter_type) {
							case 'E':
								$icon_class = 'eticon eticon-bolt txt-color-yellow';
								break;

							case 'G':
								$icon_class = 'eticon eticon-flame txt-color-blue';
								break;

							case 'W':
								$icon_class = 'eticon eticon-droplet txt-color-blueWater';
								break;

							case 'H':
								$icon_class = 'eticon eticon-heat txt-color-red';
								break;
						}
						
						$desc = '';
						if($meter->parent_id && !$building_structure) {
							$m = new Meter($meter->id);
							$tn = $m->get_tenant_name();
							if($tn) {
								$desc = '<strong>'.$meter->description.'</strong> <i>('.$tn.')</i>';
								
								
								
							} else {
								$desc = '<strong>'.$meter->description.'</strong>';
								
								
							}
						} else {
							$desc = '<strong>'.$meter->description.'</strong>';
							
							
							array_push($this->meter_array,$meter->id);
							//print_r($meters_auth);
							// $final_array=$meter->id;
							
						}

					
					}
				}
				
				return $structure;
			};
			$get_areas = function($floor) use ($building, $get_meters, $meter_type_filter, $building_structure, $generated, $area_access, $virtual) {
				$areas_structure = [];
				$client_id = $building->client_id;
				
				$areas = App::sql()->query(
					"SELECT
						a.id, a.description,
						IF(cu.id IS NULL,
							t.company,
							IF(COALESCE(cu.contact_name, '') <> '' AND COALESCE(cu.company_name, '') <> '', CONCAT(cu.contact_name, ', ', cu.company_name), COALESCE(NULLIF(cu.contact_name, ''), cu.company_name))
						) AS tenant_name
					FROM area AS a
					LEFT JOIN tenanted_area AS ta ON ta.area_id = a.id
					LEFT JOIN tenant AS t ON t.id = ta.tenant_id
					LEFT JOIN (
						SELECT
							c.area_id,
							MIN(c.customer_id) AS customer_id
						FROM contract AS c
						WHERE c.owner_type = 'C' AND c.owner_id = '$client_id' AND c.customer_type = 'CU' AND c.area_id IS NOT NULL AND c.status IN ('active', 'ending')
						GROUP BY c.area_id
					) AS c ON c.area_id = a.id
					LEFT JOIN customer AS cu ON cu.id = c.customer_id
					WHERE a.floor_id = '$floor->id' AND a.id IN $area_access
					ORDER BY a.display_order;
				") ?: [];
				
				foreach($areas as $area) {
					if($virtual) {
						$meter_filter = [ 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "='$area->id'", "'$area->id'" => "IN $area_access" ];

					} else {
						$meter_filter = [ 'meter.area_id' => "='$area->id'", 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "IN $area_access" ];
					}
					if(!$building_structure) $meter_filter['parent_id'] = 'IS NULL';
					if($meter_type_filter) {
						$meter_filter['meter_type'] = "= '$meter_type_filter'";
						$meter_filter['meter_direction'] = $generated ? "= 'generation'" : "<> 'generation'";
					}

					$device_content = $get_meters($meter_filter, !$building_structure);

					
					if($device_content && count($device_content)) {
						$area_html = '<span class="label label-default">Area</span> <strong>'.$area->description.'</strong>';
						if($area->tenant_name) $area_html .= ' <i>- '.$area->tenant_name.'</i>';
						$areas_structure[] = [
							'content'  => $area_html,
							'attr'     => [ 'data-li' => 'a'.$area->id, 'data-building' => $building->id, 'data-floor' => $floor->id, 'data-area' => $area->id ],
							'class'    => 'item-type-area',
							'subitems' => $device_content
						];
					}
				}
				
				return $areas_structure;
			};
			if ($area_access && $floors = Building::API_get_floors([],['floor.*'],[],$building->id)) {
				foreach($floors as $floor) {
					$areas = $get_areas($floor);
					if($areas && count($areas)) {
						$floors_data[] = [
							'content'  => '<span class="label label-default">Block</span> <strong class="txt-color-greyDark">'.$floor->description.'</strong>',
							'attr'     => [ 'data-li' => 'f'.$floor->id, 'data-building' => $building->id, 'data-floor' => $floor->id ],
							'class'    => 'item-type-floor',
							'subitems' => $areas
						];
					}
				}	
			} else {
				print_r('area access and floor error');
			}
			$final_array[$building->description]=$this->meter_array;
		}
		//$return_array = json_encode($final_array, JSON_PRETTY_PRINT); // Encode and print as JSON
		return $final_array;
		
	}


	public function mobile_get_user_meter_list($meter_type_filter = '', $building_structure = false, $virtual = true, $generated = false, $auth_building_id) {
		$final_array=[];
		$meters_auth=[];
		$b = $this->building_array;
		
		foreach ($b as $building) {
			
			if($building->id == $auth_building_id){
				$area_access = Building::API_get_area_ids_with_permission(Permission::METERS_ENABLED, $building->id);
				
				if(!$area_access) return;
				$area_access = '('.implode(',', $area_access).')';
			
				$building_access = Permission::get_building($building->id)->check(Permission::METERS_ENABLED);
	
				$floors_data = [];
				
				$get_meters = function($filter, $show_sub) use ($building, &$get_meters, $building_structure) {
					$structure = [];
					// print_r('START');exit;
					if ($meters = Building::get_meters($filter, ['meter.*'], [], 'ORDER BY meter.meter_type, meter.description')) {
						
						foreach($meters as $meter) {
							$meter_filter = [ 'parent_id' => "='$meter->id'"];
	
							if(isset($filter['meter_type']) && $filter['meter_type']) $meter_filter['meter_type'] = $filter['meter_type'];
							
							$submeters = $show_sub ? $get_meters($meter_filter, true) : [];
	
							$meter_class = $meter->parent_id ? ' Sub' : '';
	
							$icon_class = '';
							switch($meter->meter_type) {
								case 'E':
									$icon_class = 'eticon eticon-bolt txt-color-yellow';
									break;
	
								case 'G':
									$icon_class = 'eticon eticon-flame txt-color-blue';
									break;
	
								case 'W':
									$icon_class = 'eticon eticon-droplet txt-color-blueWater';
									break;
	
								case 'H':
									$icon_class = 'eticon eticon-heat txt-color-red';
									break;
							}
							
							$desc = '';
							if($meter->parent_id && !$building_structure) {
								$m = new Meter($meter->id);
								$tn = $m->get_tenant_name();
								if($tn) {
									$desc = '<strong>'.$meter->description.'</strong> <i>('.$tn.')</i>';
									
									print($meter);exit;
									
								} else {
									$desc = '<strong>'.$meter->description.'</strong>';
									
									print($meter);exit;
								}
							} else {
								//$desc = '<strong>'.$meter->description.'</strong>';
								
								array_push($this->meter_array , $found_meters = array(
									"id" => $meter->id,
									"type" => $meter->meter_type,
									"name" => $meter->description,
								));

								// if (!isset($this->meter_array[$meter->id])) {
								// 	$this->meter_array[$meter->id] = array(); // Initialize the inner array if it doesn't exist
								// }
								// array_push($this->meter_array[$meter->id], $meter->description);
								
							}
	
						
						}
					}
					
					return $structure;
				};
				$get_areas = function($floor) use ($building, $get_meters, $meter_type_filter, $building_structure, $generated, $area_access, $virtual) {
					$areas_structure = [];
					$client_id = $building->client_id;
					
					$areas = App::sql()->query(
						"SELECT
							a.id, a.description,
							IF(cu.id IS NULL,
								t.company,
								IF(COALESCE(cu.contact_name, '') <> '' AND COALESCE(cu.company_name, '') <> '', CONCAT(cu.contact_name, ', ', cu.company_name), COALESCE(NULLIF(cu.contact_name, ''), cu.company_name))
							) AS tenant_name
						FROM area AS a
						LEFT JOIN tenanted_area AS ta ON ta.area_id = a.id
						LEFT JOIN tenant AS t ON t.id = ta.tenant_id
						LEFT JOIN (
							SELECT
								c.area_id,
								MIN(c.customer_id) AS customer_id
							FROM contract AS c
							WHERE c.owner_type = 'C' AND c.owner_id = '$client_id' AND c.customer_type = 'CU' AND c.area_id IS NOT NULL AND c.status IN ('active', 'ending')
							GROUP BY c.area_id
						) AS c ON c.area_id = a.id
						LEFT JOIN customer AS cu ON cu.id = c.customer_id
						WHERE a.floor_id = '$floor->id' AND a.id IN $area_access
						ORDER BY a.display_order;
					") ?: [];
					
					foreach($areas as $area) {
						if($virtual) {
							$meter_filter = [ 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "='$area->id'", "'$area->id'" => "IN $area_access" ];
	
						} else {
							$meter_filter = [ 'meter.area_id' => "='$area->id'", 'visible = 1 AND COALESCE(meter.virtual_area_id, meter.area_id)' => "IN $area_access" ];
						}
						if(!$building_structure) $meter_filter['parent_id'] = 'IS NULL';
						if($meter_type_filter) {
							$meter_filter['meter_type'] = "= '$meter_type_filter'";
							$meter_filter['meter_direction'] = $generated ? "= 'generation'" : "<> 'generation'";
						}
	
						$device_content = $get_meters($meter_filter, !$building_structure);
	
						
						if($device_content && count($device_content)) {
							$area_html = '<span class="label label-default">Area</span> <strong>'.$area->description.'</strong>';
							if($area->tenant_name) $area_html .= ' <i>- '.$area->tenant_name.'</i>';
							$areas_structure[] = [
								'content'  => $area_html,
								'attr'     => [ 'data-li' => 'a'.$area->id, 'data-building' => $building->id, 'data-floor' => $floor->id, 'data-area' => $area->id ],
								'class'    => 'item-type-area',
								'subitems' => $device_content
							];
						}
					}
					
					return $areas_structure;
				};
				if ($area_access && $floors = Building::API_get_floors([],['floor.*'],[],$building->id)) {
					foreach($floors as $floor) {
						$areas = $get_areas($floor);
						if($areas && count($areas)) {
							$floors_data[] = [
								'content'  => '<span class="label label-default">Block</span> <strong class="txt-color-greyDark">'.$floor->description.'</strong>',
								'attr'     => [ 'data-li' => 'f'.$floor->id, 'data-building' => $building->id, 'data-floor' => $floor->id ],
								'class'    => 'item-type-floor',
								'subitems' => $areas
							];
						}
					}	
				} else {
					print_r('area access and floor error');
				}
				$building_list = array(
					"building_id" => $building->id,
					"meters" => $this->meter_array,
				);
				$final_array=$building_list;
			}

		}
		//$return_array = json_encode($final_array, JSON_PRETTY_PRINT); // Encode and print as JSON
		return $final_array;
		
	}

}