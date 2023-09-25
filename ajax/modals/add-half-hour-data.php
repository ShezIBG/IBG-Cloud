<?php require_once '../init.ajax.php';
    require_once '../../lib/include.php'; ?>
<div class="modal-content-form">
<div class="modal-header-form">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		<i class="eticon eticon-cross"></i>
	</button>
	<h4 class="modal-title">Download Custom Data Reports</h4>
	<img class="modal-logo" src="<?php echo APP_URL?>/v3/assets/img/mine/logo_ibg-dark.svg"></img>
</div>
<p class="form_border"></p>
<div class="modal-body-form">
<?php
	//Get User Info
	$user = User::check_login_session(isset($redirect_on_error) ? $redirect_on_error : true, isset($is_ajax_request) ? $is_ajax_request : false);
	$User_id = $user->info->id;
	
	$building = $user->get_default_building(Permission::METERS_ENABLED, '', true);
	$area_access = $building->get_area_ids_with_permission(Permission::METERS_ENABLED);
	if($area_access) $area_access = '('.implode(',', $area_access).')';
	
	$building_access = Permission::get_building($building->id)->check(Permission::METERS_ENABLED);
	
	
	$meter_list = $building->get_meters(['floor.building_id' => "='$building->id'", 'COALESCE(virtual_area_id, area_id)' => "IN $area_access" ]);
	//$meter_list[0]->id;

echo'
            <form action="../eticom/ajax/custom_chart.php" method="post" id="form">
				<div class="wrap_1">
                <div class="myWidget-title-popup">Select Date From:</div>
                    <input type="date" name="mm_datefrom" class="from_button"><br>
				</div>
                <div class="wrap_2">
                <div class="myWidget-title-popup">Select Date To:<div>
                    <input type="date" name="mm_dateto" class="select_button";"><br>
				</div>
				<div class="wrap_3">
				<input type="submit"  class="submit_button" value="Submit">
	           </div>
   
	
	';
echo "<div class='scroll_wrap_main_1'>
       	<div class='wrap_4'>";
	foreach ($meter_list as $value){

		echo "
		<input type='checkbox' id='content_block' name='meter_list[]' value='$value->id' />$value->description:
		<label for='content_block'></label>
		";
		
	};

	echo
	'    </div>
	</div>
	</form>'


	//get_meter_list($meter_list);
	

?>
</div>
</div>


<style>
	
 .modal-backdrop.in {
  background:#465262;
  opacity: 1;
}
.modal-content{
	margin: 160px 36px;
	overflow:none;
}
.modal-body-form {
    position: relative;
    padding: 15px;
    margin: -3px 24px;
}
.modal-header-form {
	padding: 15px;
    border-bottom: 1px solid #e5e5e5;
    min-height: 16.42857143px;
	display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1rem;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: calc(0.3rem - 1px);
    border-top-right-radius: calc(0.3rem - 1px);

}


@media only screen and (min-width: 320px) and (max-width: 479px) {

.modal-content{
 /* margin: 75px 8px; */
 margin:10vh 0vw;
 font-size:9px; 
}	

::-webkit-calendar-picker-indicator {
    filter: invert(1);
	zoom: 1.5;
}
.modal-body-form {
    position: relative;
    padding: 15px;
    margin: unset;
}


#form input {
    font-size: 4vw;
}
.from_button{
	width:100% !important;
	height: 4vh;
}

.select_button{
	width: 100% !important;
	height: 4vh;
}

.submit_button{
	width: 20vw !important;
    height: 3vh;
}

.scroll_wrap_main_1 {
    height: 299px;
}
.submit_button{
	width: 29vw !important;
    height: 3.5vh;
	font-size: 4vw !important;
}
#content_block {
	transform: translate(79vw,0%);
    position: relative;
    left: unset;
    display: block;
    height: 23px;
    width: 14%;
    top: 5%;
}
.wrap_3 {
	right: 5%;
}

}

.modal-logo{
    width: 30%;
}
</style>
