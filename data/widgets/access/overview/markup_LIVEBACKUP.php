<script src="https://kit.fontawesome.com/f2a2eacd59.js" crossorigin="anonymous"></script>


<?php

$cache_file = $_SERVER['DOCUMENT_ROOT']."/eticom/pax-users/sample.txt";

$paxton_users = json_decode(file_get_contents($cache_file), true);

array_column($paxton_users, 'firstName');

$price = array_column($paxton_users, 'firstName');

array_multisort($price, SORT_ASC, $paxton_users);

$p_accesslevels = Paxton::accesslevels_paxton();

$all_doors = Paxton::doors_paxton();

$all_areas = Paxton::paxton_areas();

$timezones = Paxton::timezones();

$display_events = Paxton::get_events();

?>




<div id="outer-grid">
  <div class="grid_drop">
    <p class="myLine3"></p>
    <div class="paxton_doors">Paxton Door Access</div>
    <button id="dropdown_menu" onclick="dropdown_button()"><input id="toggle1" for="toggle1" type="checkbox">
      <label class="hamburger1">
      <div class="top"></div>
      <div class="meat"></div>
      <div class="bottom"></div>
      </label>
    </button>
    <div id ="dropdown_menu_1" style="display:block">
      <div class="scroll_wrap">
        <button id="btn_new" onclick="viewDashboard()">&nbsp; Dashboard</button>
        <button id="btn_new" onclick="viewContacts()">&nbsp; Users</button>
        <button id="btn_new" onclick="viewPlaceholder1()">&nbsp; Access Levels</button>
        <button id="btn_new" onclick="viewPlaceholder2()">&nbsp; Door Access</button>
        <button id="btn_new" onclick="viewPlaceholder3()">&nbsp; Timezones</button>
      </div>
    </div>
  </div>
  <!-- Add/Delete, Update Users -->
  <div id="inner-grid">
    <div id="inner-contacts" style="display:none">
      <div class="scroll_wrap_inner">
        <h1 class="paxtoon-door-users">Paxton Door users<h1>
        <p class="form_border-paxton"></p>
      <button id="btn-add-user" onclick="showAddUser()">Add New User :</button>
      <?php 
              $Contact_length = count($paxton_users);
              
              $access_length = count($p_accesslevels);
              $cache_file = $_SERVER['DOCUMENT_ROOT']."/eticom/pax-users/sample-keys-test.txt";
              
              for($x=0; $x < $Contact_length; $x++){
                
                $userId = $paxton_users[$x]['id'];
                
                $cache_file = $_SERVER['DOCUMENT_ROOT']."/eticom/pax-users/sample-keys'.$userId.'.txt";

                $allKeys = json_decode(file_get_contents($cache_file), true);

                $keys_le = count($allKeys);

                $perm_file = $_SERVER['DOCUMENT_ROOT']."/eticom/pax-users/sample-perm'.$userId.'.txt";
               
                $perm_decode = json_decode(file_get_contents($perm_file), true);
                
                $perm = $perm_decode['accessLevels']; 
          echo ' 
                <button id="btn" class="collapsible" onclick="showUserDetails('.$paxton_users[$x]['id'].', '.$keys_le.')">'.($paxton_users[$x]['firstName']).'&nbsp'.($paxton_users[$x]['lastName']).'<i class="fa fa-arrow-down" id="user-expand" style="floatpull-right;float: right;margin-right: 37px;"></i></button>
                
                <div class="users">
                <div class="users-grid">
                <button class="add_key" id="btn-key'.$paxton_users[$x]['id'].'" onclick="addKey('.$paxton_users[$x]['id'].')">Add Key</button>
                <button  class="del_btn" id="btn-delete'.$paxton_users[$x]['id'].'" value="'.$paxton_users[$x]['id'].'" onclick="deleteUser('.$paxton_users[$x]['id'].')">Delete</button>
                <button class="edit_btn" id="btn-edit'.$paxton_users[$x]['id'].'" value="'.$paxton_users[$x]['id'].'" onclick="editUser('.$paxton_users[$x]['id'].')">Edit</button>
                <button class="access_btn" id="btn-access'.$paxton_users[$x]['id'].'" onclick="accessLevel('.$paxton_users[$x]['id'].','.$perm[0].')">Access Level</button>
                <div id="show_key'.$paxton_users[$x]['id'].'" style="display:none;">
                <form action="./lib/class.access.php" class="form-grid-add-key" method="post">
                <input type="hidden" name="id" value="'.$paxton_users[$x]['id'].'">
                <select name="tokenType" class="token-buton">
                    <option value="card">Card</option>
                    <option value="keyfob">keyFob</option>
                  </select>
                  <input type="text" name="tokenValue" placeholder="Token Value" class="token-value">
                  <input type="submit" name="addKey" value="Add Key" class="add-key">
                </form>
                </div>
                <div id="inner-edit'.$paxton_users[$x]['id'].'" style="display:none">
                <form action="./lib/class.access.php" method="post" class="form-edit">
                      <input type="hidden" value="'. $paxton_users[$x]['id'].'" name="id">
                      <input type="text"  name="firstname" placeholder="First Name" value="'.$paxton_users[$x]['firstName'].'">
                      <input type="text" name="lastname" placeholder="Last Name" value="'.$paxton_users[$x]['lastName'].'">
                      <input type="tel" name="telephone" placeholder="Telephone" value="'.$paxton_users[$x]['telephone'].'">
                      <input type="text" name="pin" placeholder="Pin" '.$paxton_users[$x]['pin'].'>
                      <input type="submit" name="update" value="Update User">
                  </form>
                <button id="edit-form-button" onclick="goback('.$paxton_users[$x]['id'].')">Cancel</button>
                </div>
                </div>
          
                ';
                
                echo '
                     <div id="active-access'.$paxton_users[$x]['id'].'" class="per'.$perm[0].' per-user" style="display:none; font-size:1.5vw ;">Active Access Level: '.$perm[0].'</div>';
                    echo'<div id="active-list'.$paxton_users[$x]['id'].'" style="display:none; font-size:12px">
                    <form action="./lib/class.user_access.php" method="POST" class="access-grid-form">
                    <input type="hidden" value="'.$paxton_users[$x]['id'].'" name="id">
                  ';
                foreach($p_accesslevels as $p_levels){
                  echo '    
                    <label class="value-for-each">
                    '.$p_levels['name'].'
                    </label>
                    <input type="checkbox" id="input-access0-'.$paxton_users[$x]['id'].'" class="inputbox"name="accessLevels" value="'.$p_levels['id'].'" '.(( $p_levels['id'] == $perm[0]) ? "checked" : "" ).'>
                    
                  ';       
                }
                echo '<input type="submit" name="submit" value="Update Access Level" class="Update-Access-Level">
                </form></div>';
               echo ' </div>';
               for($y=0; $y < $keys_le; $y++){
                echo'
                <div id="active-keys'.$paxton_users[$x]['id'].'" class="keys-le'.$keys_le.'-'.$paxton_users[$x]['id'].'" style="font-size:16px; margin-top:1vh; display:none;"><i class="fa fa-key" style="margin-right:16px"></i>'.$allKeys[$y]['tokenValue'].'</div>';
                }
                echo '
                <div id="myModal'.$paxton_users[$x]['id'].'" class="modal">
                  <!-- Modal content -->
                  <div class="modal-content">
                  <p class="modal-text">Are you sure</p>
                  <button class="delete-modal" onclick="finalUserDelete('.$paxton_users[$x]['id'].')">Delete</button>
                  <button class="close-modal" onclick="closeUserDelete('.$paxton_users[$x]['id'].')">Close</button>
                  </div>
                </div>';
              }  
      ?>
      </div>
    </div>
    <div id="inner-add" style="display:none">
    <div class="background-add-user">
    <p class="user-text"><i class="fa-solid fa-user"></i></i>Add New User</p>
    <p class="form_border"></p>
      <form action="./lib/class.access.php" class="add-user" method="post">
          <div class="fontuser">
            <input type="text" 
               placeholder="First Name"
                name="firstname"> 
          </div>
          <div class="fontuser">
            <input type="text" 
               placeholder="Last Name"
                name="lastname"> 
          </div>
          <div class="fontuser">
            <input type="text" 
               placeholder="Telephone"
                name="telephone"> 
                <i class="fa-solid fa-phone"></i>
          </div>
          <div class="fontuser">
            <input type="text" 
               placeholder="Pin"
                name="pin"> 
                <i class="fa-solid fa-lock"></i>
          </div>
          <div class="fontuser">
          <input type="submit" name="submit" value="Add User" class="submit-hover">
          </div>
      </form>
          <button id="cancel-user" onclick="goback()">Cancel</button>
        </div>
    </div>

    <!-- List All Events     dashboard page-->
    <div class="allperson_text" id="page6"  style="display:block">
    <h1 class="paxtoon-door-users">Dashboard</h1>
        <p class="form_border-paxton"></p>
    <table class="dashboard-table">
      <tr>
        <th class="event-size">Event Date</th>
        <th class="event-size">Event ID</th>
        <th class="event-size">Device Name</th>
        <th class="event-size">Card No</th>
        <th class="event-size">Event Description</th>
        <th class="event-size">First Name</th>
        <th class="event-size">Last Name</th>
      </tr>
      <?php
      foreach (array_slice($display_events, 0, 10) as $de){
        echo '
        <tr>
        <td class="dashboard-table-td">'.date("F jS, Y, g:i a", strtotime($de[eventDate])).'</td>
        <td class="dashboard-table-td">'.$de[id].'</td>
        <td class="dashboard-table-td">'.$de[where].'</td>
        <td class="dashboard-table-td">'.$de[tokenNumber].'</td>
        <td class="dashboard-table-td">'.$de[eventTypeDescription].'</td>
        <td class="dashboard-table-td">'.$de[firstName].'</td>
        <td class="dashboard-table-td">'.$de[lastName].'</td>
        </tr>';
      }
      ?>
      
    </table>
    </div>
    <!-- Access Levels -->
    <div class="allperson_text" id="page7"  style="display:none";>
    <h1 class="paxtoon-door-users">Access Levels<h1>
        <p class="form_border-paxton"></p>
      <div class="scroll_wrap_inner_landscape" id="aclevelpage">
        <div class="paxton-door-position">
        <button class="access-button" onclick="accessCreate()">Create New Access:</button>
        <div id="access-form" style="display:none">
          <form action="./lib/class.access.php" method="POST" class="access-form">
            <input type="hidden" name="id">
            <input type="text" name="name" placeholder="Enter Area Name">
            <input type="submit" name="access" value="Create New Access" class="new-value-button">

          </form>
         </div>
       
        <?php
          $all_areas = Paxton::paxton_areas();

          $Contact_length = count($p_accesslevels);
         
          for($x=0; $x < $Contact_length; $x++){
            $dd = Paxton::accesslevels_details_paxton($p_accesslevels[$x]['id']);
            $cl = count($dd['detailRows']);
            echo ' <div id="collapse-access'.$p_accesslevels[$x]['id'].'" style="display:block"> 
                <button type="button" id="btn_collapse" class="new_access" onclick="showAllAreas('.$p_accesslevels[$x]['id'].')">'.($p_accesslevels[$x]['name']).'<i class="fa fa-arrow-down" id="user-expand" style="floatpull-right;float: right;margin-right: 37px;" aria-hidden="true"></i></button>
                <button class="edit-button" onclick="editAccessArea('.$p_accesslevels[$x]['id'].')">Edit</button>
            </div>';
            $dash = "";
            for($y=0; $y < $cl; $y++){
              foreach ($all_areas as $all)
              {
                if($dd['detailRows'][$y]['areaID'] == $all['areaId']){
                  echo '<div id="areasShow'.$p_accesslevels[$x]['id'].'" style="display:none">
                          <div id="all_areas">'.$all['name'].'</div>
                        </div> ';
                }

              }
            
              $dash .= $dd['detailRows'][$y]['areaID'];
            }
            echo '<form action="./lib/class.access.php" method="POST" id="editAccessPage'.$p_accesslevels[$x]['id'].'" style="display:none;">';
            echo '<input type="hidden" name="id" value="'.$p_accesslevels[$x]['id'].'">
            <input type="text" name="name" value="'.$p_accesslevels[$x]['name'].'"class="edit-access-button">';
            foreach ($all_areas as $p){
              echo'<div>
                    
                  <input type="checkbox" class="checkbox-class" name="areaId[]" value="'.$p['areaId'].'">
                      
                      <label class="ground-floor">'.$p['name'].'</label>';
                    echo '<select name="timezoneID[]" id="access_door">';
                    foreach ($timezones as $t){
                    echo '<option value="'.$t['id'].'">'.$t['name'].'</option>';
                    }
                  echo '</select>';
                  
              
                echo '</div>';
                
            }
            echo '<br>';
            
            echo '<input type="submit" name="apply" value="Apply" class="apply-button">';
            echo '</form>';
            }

        ?>
      </div>
      </div>
      
     </div>
    <div class="allperson_text" id="page8"  style="display:none";>
      <div class="button_image_wrap scroll_wrap_inner">
        <img src="<?= APP_URL ?>/images/Floor1_IBG.png">
        <button id="dr-3" class="door_btn">Door</button>
        <button id="dr-2" class="door_btn2">Door</button>
        <button id="dr-4" class="door_btn3">Door</button>
        <button id="dr-0" class="door_btn4">Door</button>
        <button id="dr-1" class="door_btn5">Door</button>
        <button id="dr-5" class="door_btn6">Door</button>
        <div class="button_grid">
          <?php
            $Contact_length = count($all_doors);
            for($x=0; $x < $Contact_length; $x++){
            echo ' 
              <div class="myWidget-title-access button_column">'.($all_doors[$x]['name']).'</div>
              <button id="door'.$x.'" class="doors" onclick="Opendoor('.$all_doors[$x]['id'].','.$x.')">Open Door</button>
              '; 
            }
          ?>
        </div>
      </div>  
    </div>

    <!-- Create/Update and Delete Timezones -->
    <div class="allperson_text" id="page9"  style="display:none";>
      <div id="inner-timezone" class="button_image_wrap scroll_wrap_inner">
      <h1 class="paxtoon-door-users">Timezone<h1>
        <p class="form_border-paxton"></p>
        <div class="paxton-door-position">
       <button class="table-timezone-button"onclick="new_timezone()">New Timezones:</button>
       <br>
       <?php
       $timezones_length = count($timezones);
       for($x=0; $x < $timezones_length; $x++){
        $detail_timezone = Paxton::timezone_details($timezones[$x]['id']);

        echo '<div class="timezone" onclick="editTimezone('.$timezones[$x]['id'].')">'.$timezones[$x]['name'].'</div>
              <div id="edit-timezones-'.$timezones[$x]['id'].'" class="step-content" style="display:none;">
              <button id="'.$timezones[$x]['id'].'" class="edit"onclick="mainEdit('.$timezones[$x]['id'].')">Edit</button>
              <button id="'.$timezones[$x]['id'].'" class="delete" onclick="mainDelete('.$timezones[$x]['id'].')">Delete</button>
              <br>
              <div id="edit-form-'.$timezones[$x]['id'].'" style="display:none">
              <form action="./lib/class.access.php" class="timezone-grid-form" method="POST">
                <input type="hidden" name="id" value="'.$detail_timezone['id'].'">
                <input type="text" name="name" value="'.$detail_timezone['name'].'">
                <table class="table table-forum no-border transparent">
                <thead>
                  <tr>
                    <th></th>
                    <th class="text-center">Start</th>
                    <th class="text-center">End</th>
                  </tr>
                </thead>
                <tbody>
                    <td>
                      <p class="txt-color-blueDark">
                        <strong>Monday</strong>
                      </p>
                    </td>
                    <input type="hidden" name="slotID_mon" value="'.$detail_timezone['timeslots'][0]['slotID'].'">
                    <input type="hidden" name="dayID_mon" value="'.$detail_timezone['timeslots'][0]['dayID'].'">
                    <td class="state-successMonday_start">
                      <input type="time" name="mon_start" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][0]['startTime'].'">
                    </td>
                    <td class="state-successMonday_end">
                      <input type="time" name="mon_end" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][0]['endTime'].'">
                    </td>
                  </tr>
                  <tr>
                    <td >
                      <p class="txt-color-blueDark">
                        <strong>Tuesday</strong>
                      </p>
                    </td>
                    <input type="hidden" name="slotID_tue" value="'.$detail_timezone['timeslots'][1]['slotID'].'">
                    <input type="hidden" name="dayID_tue" value="'.$detail_timezone['timeslots'][1]['dayID'].'">
                    <td class="state-successMonday_start">
                      <input type="time" name="tue_start" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][1]['startTime'].'">
                    </td>
                    <td class="state-successMonday_end">
                      <input type="time" name="tue_end" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][1]['endTime'].'">
                    </td>
                  </tr>
                  <tr>
                    <td >
                      <p class="txt-color-blueDark">
                        <strong>Wednesday</strong>
                      </p>
                    </td>
                    <input type="hidden" name="slotID_wed" value="'.$detail_timezone['timeslots'][2]['slotID'].'">
                    <input type="hidden" name="dayID_wed" value="'.$detail_timezone['timeslots'][2]['dayID'].'">
                    <td class="state-successMonday_start">
                      <input type="time" name="wed_start" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][2]['startTime'].'">
                    </td>
                    <td class="state-successMonday_end">
                      <input type="time" name="wed_end" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][2]['endTime'].'">
                    </td>
                  </tr>
                  <tr>
                    <td >
                      <p class="txt-color-blueDark">
                        <strong>Thursday</strong>
                      </p>
                    </td>
                    <input type="hidden" name="slotID_thurs" value="'.$detail_timezone['timeslots'][3]['slotID'].'">
                    <input type="hidden" name="dayID_thurs" value="'.$detail_timezone['timeslots'][3]['dayID'].'">
                    <td class="state-successMonday_start">
                      <input type="time" name="thurs_start" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][3]['startTime'].'">
                    </td>
                    <td class="state-successMonday_end">
                      <input type="time" name="thurs_end" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][3]['endTime'].'">
                    </td>
                  </tr>
                  <tr>
                    <td >
                      <p class="txt-color-blueDark">
                        <strong>Friday</strong>
                      </p>
                    </td>
                    <input type="hidden" name="slotID_fri" value="'.$detail_timezone['timeslots'][4]['slotID'].'">
                    <input type="hidden" name="dayID_fri" value="'.$detail_timezone['timeslots'][4]['dayID'].'">
                    <td class="state-successMonday_start">
                      <input type="time" name="fri_start" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][4]['startTime'].'">
                    </td>
                    <td class="state-successMonday_end">
                      <input type="time" name="fri_end" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][4]['endTime'].'">
                    </td>
                  </tr>
                  <tr>
                    <td >
                      <p class="txt-color-blueDark">
                        <strong>Saturday</strong>
                      </p>
                    </td>
                    <input type="hidden" name="slotID_sat" value="'.$detail_timezone['timeslots'][5]['slotID'].'">
                    <input type="hidden" name="dayID_sat" value="'.$detail_timezone['timeslots'][5]['dayID'].'">
                    <td class="state-successMonday_start">
                      <input type="time" name="sat_start" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][5]['startTime'].'">
                    </td>
                    <td class="state-successMonday_end">
                      <input type="time" name="sat_end" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][5]['endTime'].'">
                    </td>
                  </tr>
                  <tr>
                    <td >
                      <p class="txt-color-blueDark">
                        <strong>Sunday</strong>
                      </p>
                    </td>
                    <input type="hidden" name="slotID_sun" value="'.$detail_timezone['timeslots'][6]['slotID'].'">
                    <input type="hidden" name="dayID_sun" value="'.$detail_timezone['timeslots'][6]['dayID'].'">
                    <td class="state-successMonday_start">
                      <input type="time" name="sun_start" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][6]['startTime'].'">
                    </td>
                    <td class="state-successMonday_end">
                      <input type="time" name="sun_end" class="timepicker-ui-input form-control" value="'.$detail_timezone['timeslots'][6]['endTime'].'">
                    </td>
                  </tr>
                </tbody>
                </table>
                <br>
                <input type="submit" name="updatetimezone" value="Update Timezones" class="timezone-submit">
              </form>
              </div>
              </div>
        ';
        echo ' <div id="timezoneModal'.$timezones[$x]['id'].'" class="modal">
        <!-- Modal content -->
        <div class="modal-content">
          
          <p class="modal-text">Are you sure</p>
          <button class="delete-modal" onclick="finalTimezoneDelete('.$timezones[$x]['id'].')">Delete</button>
          <button class="close-modal" onclick="closeTimezoneDelete('.$timezones[$x]['id'].')">Close</button>
        </div>
      </div>';
       }
       ?>
      </div>
      </div>
      <div id="add-timezones" class="step-content" style="display:none;">
        <form action="./lib/class.access.php" class="timezone-grid-form" method="POST">
          <input type="text" name="name" placeholder="Name">
          <br><br>
          <table class="table table-forum no-border transparent">
          <thead>
            <tr>
              <th></th>
              <th class="text-center">Start</th>
              <th class="text-center">End</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td >
                <p class="txt-color-blueDark">
                  <strong>Monday</strong>
                </p>
              </td>
              <td class="state-successMonday_start">
                <input type="time" name="mon_start" class="timepicker-ui-input form-control">
              </td>
              <td class="state-successMonday_end">
                <input type="time" name="mon_end" class="timepicker-ui-input form-control">
              </td>
            </tr>
            <tr>
              <td >
                <p class="txt-color-blueDark">
                  <strong>Tuesday</strong>
                </p>
              </td>
              <td class="state-successMonday_start">
                <input type="time" name="tue_start" class="timepicker-ui-input form-control">
              </td>
              <td class="state-successMonday_end">
                <input type="time" name="tue_end" class="timepicker-ui-input form-control">
              </td>
            </tr>
            <tr>
              <td >
                <p class="txt-color-blueDark">
                  <strong>Wednesday</strong>
                </p>
              </td>
              <td class="state-successMonday_start">
                <input type="time" name="wed_start" class="timepicker-ui-input form-control">
              </td>
              <td class="state-successMonday_end">
                <input type="time" name="wed_end" class="timepicker-ui-input form-control">
              </td>
            </tr>
            <tr>
              <td >
                <p class="txt-color-blueDark">
                  <strong>Thursday</strong>
                </p>
              </td>
              <td class="state-successMonday_start">
                <input type="time" name="thurs_start" class="timepicker-ui-input form-control">
              </td>
              <td class="state-successMonday_end">
                <input type="time" name="thurs_end" class="timepicker-ui-input form-control">
              </td>
            </tr>
            <tr>
              <td >
                <p class="txt-color-blueDark">
                  <strong>Friday</strong>
                </p>
              </td>
              <td class="state-successMonday_start">
                <input type="time" name="fri_start" class="timepicker-ui-input form-control">
              </td>
              <td class="state-successMonday_end">
                <input type="time" name="fri_end" class="timepicker-ui-input form-control">
              </td>
            </tr>
            <tr>
              <td >
                <p class="txt-color-blueDark">
                  <strong>Saturday</strong>
                </p>
              </td>
              <td class="state-successMonday_start">
                <input type="time" name="sat_start" class="timepicker-ui-input form-control">
              </td>
              <td class="state-successMonday_end">
                <input type="time" name="sat_end" class="timepicker-ui-input form-control">
              </td>
            </tr>
            <tr>
              <td >
                <p class="txt-color-blueDark">
                  <strong>Sunday</strong>
                </p>
              </td>
              <td class="state-successMonday_start">
                <input type="time" name="sun_start" class="timepicker-ui-input form-control">
              </td>
              <td class="state-successMonday_end">
                <input type="time" name="sun_end" class="timepicker-ui-input form-control">
              </td>
            </tr>
          </tbody>
          </table>
          <br>
          <input type="submit" name="timezones" value="Create Timezones" class="timezone-submit">
        </form>
        <br>
        <button id="cancel-user-form" onclick="gobackTimezone()">Cancel</button>
      </div>
    </div>
  </div>
</div>



<!-- Functions to swap the main grid views from none to block/grid -->
<script>
dropdown_button()

function dropdown_button() {

  var down = document.getElementById("dropdown_menu_1");
  var inner_grid = document.getElementById("inner-grid");


  if((down.style.display === "none") && (window.innerWidth <= 480 )) {
     down.style.display = "block"
     inner_grid.style.margin = "2px 0px 0px 0px";


  } else if((down.style.display === "block") && (window.innerWidth <= 480 )) {
     down.style.display = "none"
     inner_grid.style.margin = "-16vh 0px 0px 0px";
  }

}
function goback(id){
  const form = document.getElementById("inner-add").style.display = "none";
  const contacts = document.getElementById("inner-contacts").style.display = "block";
  document.getElementById("inner-edit"+id).style.display = "none";
}
function showAllAreas(id){

  var elements = document.querySelectorAll(`[id^="areasShow${id}"]`);
  for (var i = 0; i < elements.length; i++) {
    if(elements[i].style.display === "none"){
      elements[i].style.display = "block";
    }else{
      elements[i].style.display = "none";
    }
  }
}
function editAccessArea(id){
  var editAccess = document.getElementById("editAccessPage"+id)
  if(editAccess.style.display === "none"){
    editAccess.style.display = "block"
  }else{
    editAccess.style.display = "none"
  }
}
function gobackTimezone(){
  document.getElementById("inner-timezone").style.display = "block";
  document.getElementById("add-timezones").style.display = "none"
}

function viewContacts(){

    const elem1 = document.getElementById("page6").style.display = "none";
    const elem2 = document.getElementById("page7").style.display = "none";
    const elem3 = document.getElementById("page8").style.display = "none";
    const elem4 = document.getElementById("inner-contacts").style.display = "grid";
    const elem5 = document.getElementById("page9").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}
 
var myXHR=new XMLHttpRequest();
myXHR.open("GET","<?= APP_URL ?>/lib/class.pax_users.php", true);
myXHR.send(null);
function viewDashboard() {
    const elem1 = document.getElementById("page6").style.display = "block";
    const elem2 = document.getElementById("page7").style.display = "none";
    const elem3 = document.getElementById("page8").style.display = "none";
    const elem5 = document.getElementById("page9").style.display = "none";
    const elem4 = document.getElementById("inner-contacts").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}
function viewPlaceholder1() {
    const elem1 = document.getElementById("page6").style.display = "none";
    const elem2 = document.getElementById("page7").style.display = "block";
    const elem3 = document.getElementById("page8").style.display = "none";
    const elem4 = document.getElementById("inner-contacts").style.display = "none";
    const elem5 = document.getElementById("page9").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}
function viewPlaceholder2() {
    const elem1 = document.getElementById("page6").style.display = "none";
    const elem2 = document.getElementById("page7").style.display = "none";
    const elem3 = document.getElementById("page8").style.display = "block";
    const elem4 = document.getElementById("inner-contacts").style.display = "none";
    const elem5 = document.getElementById("page9").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}
function viewPlaceholder3() {
    const elem1 = document.getElementById("page6").style.display = "none";
    const elem2 = document.getElementById("page7").style.display = "none";
    const elem3 = document.getElementById("page8").style.display = "none";
    const elem5 = document.getElementById("page9").style.display = "block";
    const elem4 = document.getElementById("inner-contacts").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}

function Opendoor(id, door_id)
{


  
  const doorled = document.getElementById("dr-"+door_id).style.backgroundColor  = "green";
  let x = id
  let btn = document.getElementById("door"+door_id);
  const clicked_btn = btn.style.backgroundColor = "green";
  const clicked_btn_white = btn.style.color = "white";
  btn.addEventListener("click", function(){
        fetch("<?= APP_URL ?>/lib/open_door.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: `id=${x}`,
        })
      })

  const close_door = async () => {
  await sleep(9000);
  const doorled_close = document.getElementById("dr-"+door_id).style.backgroundColor  = "red";
  const clicked_btn_close = btn.style.backgroundColor = "";
  const clicked_btn_white = btn.style.color = "black";
  }

  close_door();
}


function showAddUser(){
    const form = document.getElementById("inner-add").style.display = "block";
    const contacts = document.getElementById("inner-contacts").style.display = "none";
}

function showUserDetails(id,key_amount){
  let deleteId = id

  let btn = document.getElementById("btn-delete" + id).value
  let editbtn = document.getElementById("btn-edit" + id).value

  let allKeys = document.getElementsByClassName("keys-le"+key_amount+"-"+id)
  

  for(var i = 0; i < allKeys.length; i++){
    if(allKeys[i].style.display === "none"){
      allKeys[i].style.display = "block";
    }else{
      allKeys[i].style.display = "none";
    }
  }
  
  
  let deleteValue = btn
  if ( deleteId == deleteValue ){
      document.getElementById("btn-delete"+id).style.display = "block";
      document.getElementById("btn-edit"+id).style.display = "block";
      document.getElementById("btn-access"+id).style.display = "block";
  }

  addEventListener("click", function(){
    fetch("<?= APP_URL ?>/lib/class.access.php",{
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: `mainUserKeyId=${deleteId}`,
    })
  })

}

function deleteUser(id)
{
  var modal = document.getElementById("myModal"+id)

  let x = id

  let userBtn = document.getElementById("btn"+id)

  let deletBtn = document.getElementById("btn-delete"+id)

  modal.style.display = "block";

  window.onclick = function(event){
    if(event.target == modal){
      modal.style.display = "none";
    }
  }

}
function finalUserDelete(id){
  let x = id
  fetch("<?= APP_URL ?>/lib/delete_user.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: `id=${x}`,
  })

  setTimeout(function(){
    location.reload()
  }, 3000)
  
}

function closeUserDelete(id){
  var modal = document.getElementById("myModal"+id)

  if(modal.style.display == "block"){
    modal.style.display = "none";
  }
}
function addKey(id)
{
  addkeyFrom = document.getElementById("show_key"+id)
  if(addkeyFrom.style.display === "none"){
    addkeyFrom.style.display = "block";
  }else{
    addkeyFrom.style.display = "none";
  }

}

function editUser(id){
  let x = id
  let userBtn = document.getElementById("btn"+id)
  let editBtn = document.getElementById("btn-edit"+id).value
  let btnEdit = editBtn

  if( btnEdit == editBtn ){
    document.getElementById("inner-edit"+id).style.display = "block";
  }
  else {
    document.getElementById("inner-edit"+id).style.display = "none";
  }
}
function accessLevel(id, active_id){
  let x = id

  let accessBtn = document.getElementById("btn-access"+id).value
  let btnAccess = accessBtn
  let acbtn = document.getElementById("btn-access"+id)
  let showlvl = document.getElementById("active-access"+id)

  let active_access = document.getElementById("input-access"+active_id+"-"+id);

  if(showlvl.style.display === "none"){
    showlvl.style.display = "block"
  }else{
    showlvl.style.display = "none"
  }
  let listaccesslvl = document.getElementById("active-list"+id)
  if(listaccesslvl.style.display === "none"){
    listaccesslvl.style.display = "block"
  }else{
    listaccesslvl.style.display = "none"
  }

}

function accessCreate(){
  let accessForm = document.getElementById("access-form")
  if(accessForm.style.display === "none"){
    accessForm.style.display = "block";
  }else{
    accessForm.style.display = "none";
  }
}
function new_timezone(){
  document.getElementById("inner-timezone").style.display = "none";
  let timezoneForm = document.getElementById("add-timezones")
  if(timezoneForm.style.display === "none"){
    timezoneForm.style.display = "block"
  }else{
    timezoneForm.style.display = "none";
  }
}
function editTimezone(id){
 zoneEdit =  document.getElementById("edit-timezones-"+id)
  if(zoneEdit.style.display==="none"){
    zoneEdit.style.display = "block"
  }else{
    zoneEdit.style.display = "none"
  }
}
function mainEdit(id){
  let x = id
  editTime = document.getElementById("edit-timezones-"+id)

  editForm = document.getElementById("edit-form-"+id)
  if(editForm.style.display==="none"){
    editForm.style.display = "block"
  }else{
    editForm.style.display = "none"
  }

  editTime.addEventListener("click", function(){
    fetch("<?= APP_URL ?>/lib/class.access.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: `singleId=${x}`,
    })
  })
}
function mainDelete(id){
  let x = id

  var modal = document.getElementById("timezoneModal"+id)

  modal.style.display = "block";
  
}

function finalTimezoneDelete(id){
  let x = id
  fetch("<?= APP_URL ?>/lib/class.access.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: `deleteId=${x}`,
  })
  setTimeout(function(){
    location.reload()
  }, 3000)
  
}

function closeTimezoneDelete(id){
  var modal = document.getElementById("timezoneModal"+id)

  if(modal.style.display == "block"){
    modal.style.display = "none";
  }
}
function editNewAccess(id, details_id){
  let detailsId = details_id

  let select_access = document.getElementById("select-num"+detailsId).value = detailsId;
  select_access.selected = true
  if(document.getElementById("just-areas"+id).style.display ==="none"){
    document.getElementById("just-areas"+id).style.display="block"
  }else{
    document.getElementById("just-areas"+id).style.display="none"
  }
  
}
const sleep = async (milliseconds) => {
    await new Promise(resolve => {
        return setTimeout(resolve, milliseconds)
    });
};
</script>

<!-- New script to expand and collapse side menu button with class collapsible-->
<script>
var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.display === "block") {
      content.style.display = "none";
    } else {
      content.style.display = "block";
    }
  });
}
</script>

<style>

  .per-user{
    padding-top: 20px !important;
    font-size: 17px !important;
    font-weight: bold;
  }
  /* #active-keys19, .keys-le7-19 i{
    font-size:16 !important;
    margin-right:16px;
  } */
  /* #active-keys19, .keys-le7-19 i {
    margin-right:16px;
  } */
  .value-for-each{
    
  }
  b, strong {
    font-weight: bold;
    font-size: 1vw;
}
  .paxton-door-position {
    position: relative;
    top: 5vh;
  }
  .new_access {
    font-size:20px;
    font-weight: bold;
  }

#all_areas{
  font-size:13px;
  line-height: 5vh;
  margin-left: 10px;
}
.modal {
    display: none;
    overflow: hidden;
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1050;
    background: #465262;
    -webkit-overflow-scrolling: touch;
    outline: 0;
}
.modal-content {
    margin: auto;
    position: relative;
    top: 40vh;
    gap: 10px;
    display: grid;
    background: transparent;
    width: 20%;
    height: 25vh;
    box-shadow: none;
    border: 0;
}
.modal-text {
  font-size: 30px !important;
  text-align: center;
  color: #fff !important;
}
.delete-modal {
  font-size: 2vw;
    height: 6vh;
    color: #646A58;
    border:none;
}
.delete-modal:hover {
  background: linear-gradient(to right, #bede18 0%, #0097ce 100%) !important;
    border:0;
}
.close-modal {
  font-size: 2vw;
    height: 6vh;
    color: #646A58 !important;
    border:none;
}  #active-keys19, .keys-le7-19 i{
    font-size:16 !important;
    margin-right:16px;
  }
.close-modal:hover{
  background: linear-gradient(to right, #bede18 0%, #0097ce 100%)!important;
    border:0;
}

/* .active-access19,.per1 {
    margin-bottom: 2vh;
    margin-top: 2vh;
    background: #2C3742;
    color: #fff;
    height: 5vh;
    width: 20vw;
    padding-top: 1vh;
    padding-left: 1vh;
  } */


 
/* 1025px â€” 1200px: Desktops, large screens.
1201px and more â€” Extra large screens, TV.  */

/* Mobile devices.*/
@media only screen and (min-width:280px) and (max-width:480px) and (orientation : portrait){

#left-panel{
  display:none;
}

#outer-grid {
  width: 100%;
  padding-right: 19px;
  display: grid;
  grid-template-rows: 21vh 69vh;
  /* height: 89vh; */
  position: fixed;
  left: 8px;
}

  #inner-grid {
    display: grid;
    grid-template-rows: 1fr;
    grid-gap: 8px;
    width: 100%;
    height: 68vh;
    margin-top:-118px;
 }
	
   .scroll_wrap {
    position: relative;
    overflow-y: scroll;
    height: 15vh;
    scrollbar-width: none;
    background: white;
    color: #878787;
  }
   #dropdown_menu {
    display:block;
  }
  .grid_drop {
    height: 6vh;
  }

   #outer-grid > .grid_drop {
    grid-column:1/3;
    grid-template-rows: 5vh 1vh 15vh;
  }

   #outer-grid > #inner-grid {
    grid-column:1/3;
    margin-top: -16vh;
  }
   #outer-grid > .grid_drop > #btn_new {
   padding-bottom: 4px;
    margin-top: 1px;
    font-size:1.2em;
    text-align: left;
  }

  .grid_drop > .myLine3 {
  grid-column:1/2;
  grid-row:2/3;
}

  .myLine3 {
    margin-top:2px;
  }

   .paxton_doors{
    font-size:1em !important;
    text-align:center;
    margin-top: 2px
  }

   #inner-contacts {
    grid-template-rows: 606px;
    grid-gap: 5px;
    margin-top: 0;
 }

   .scroll_wrap_inner {
    height:66vh;
    margin:0;
  }

  .scroll_wrap_inner_landscape{
    height:66vh;
    margin:0;

  }

   #btn {
    font-size: 5vw;
   }

   #btn:hover{
   width:100%;
  }

   .collapsible {
    width: 100%;
    font-size: 1em;
  }

   .users {
    width:100%;
    font-size: 0.8em;
  }


   .allperson_text {
    font-size: 0.9em!important;
    color: black;
   }
  

  .button_image_wrap > img {
  float: none;
  width: 100%;
  height: 173px;
}


.button_image_wrap > .myWidget-title {
    width: 100%;
    font-size: 0.8em !important;
    margin-top: 19px;

}
#btn_new {
  font-size:4.3vw;
}

.grid_drop > #dropdown_menu_1 {
  display:none;
  margin-top:0px;
  font-size: 1em;
  /* animation: growDown 0.9s ease-in-out forwards;
  transform-origin: top left; */
  /* transform: scale(0);
  animation: 1s ease-in-out opacity, 0.2s ease-out transform;  */
 }

/* @keyframes  growDown {
    0% {
        transform: scaleY(0) 
    }
    80% {
        transform: scaleY(1.1)
    }
    100% {
        transform: scaleY(1)
    }
} */



.grid_drop >.hamburger1 div {
  display:block;
}


.hamburger1 {
    display: block;
    margin: 34px;
    position: relative;
    left: -10vw;
    top: -1vh;
    z-index: 0;
}

.hamburger1 div {
  display:block;
  margin-left: -34px;
}

#toggle1 {
    display: block;
    width: 19px;
    left: -9vw;
    z-index: 1;
}
 #toggle1:checked + .hamburger1 .top {
          display:block;

}

#toggle1:checked + .hamburger1 .meat {
          display:block;
}

 #toggle1:checked + .hamburger1 .bottom {
          display:block;
}
.dashboard-table{
    background-color: transparent;
    margin-top: 3vh;
    height: 41vh;
    
}

.dashboard-table-td{
    border: 1px solid #dddddd;
    text-align: left;
    padding: 4px !important;
    font-size: 2.5vw;
}

.event-size {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 5px;
    font-size: 2.4vw;
    width: 11vw;
    background-color: #313f4c!important;
    color: #ffffff;
}
.background-add-user {
    box-shadow: transparent;
    width: 92vw;
    margin-left: 1vw;
    height: 58vh;
    margin-top: 6vh;
}
.add-user {
    display: grid;
    grid-gap: 18px;
    position: relative;
    top: 4vh;
}
.add-user input {
    background: #2C3742;
    color: white;
    height: 5.4vh;
    font-weight: bold;
    font-size: 4.5vw;
    border: 1px solid #0097ce;
    margin: auto;
    width: 82vw;
    padding-left: 7px;
}
#cancel-user {
    color: #fff;
    font-size: 5vw;
    position: relative;
    height: 5vh;
    left: 50.2vw;
    top: 6vh;
    width: 40%;
    border: 1px solid #0097ce;
    text-align: center;
    background: #2C3742;
}

#btn-add-user {
    color: #fff;
    font-size: 4vw;
    font-size: 3;
    text-align: center;
    background: #2C3742;
    height: 5vh;
    width: 88vw;
    position: relative;
    left: 2.9vw;
    margin-top: 1vh;
}
.user-text {
    font-size: 4vw !important;
    margin-left: 2vw;
    padding-top: 1vh;
}
.fontuser i {
    position: absolute;
    left: 6.75vw;
    top: 34.6%;
    color: gray;
    font-size: 2.5vw;
    display:none;
}

}



	
                                                                                  /*for landcsape */

@media only screen and (min-width:653px) and (max-width:916px) and (orientation : landscape) {

  #left-panel{
    display:none;
  }

  #outer-grid {
    width: 100%;
    padding-right: 19px;
    display: grid;
    grid-template-columns: 15% 85%;
    /* grid-template-columns: 20% 77%; */
    /* height: 89vh; */
    position: fixed;
    left: 8px;
  }

  #inner-grid {
    display: grid;
    grid-template-rows: 1fr;
    grid-gap: 8px;
    width: 100%;
    height: 81vh;
    margin-top: 3px;
  }

  .scroll_wrap {
    height:82vh;
  }


  #outer-grid > .scroll_wrap > #btn_new {
   padding-bottom: 4px;
    margin-top: 1px;
    font-size:1.8vw;
    text-align: left;
  }


  .paxton_doors {
    font-size:1.4vw !important;
    
  }


  .scroll_wrap_inner {
    height: 79vh;
    margin-top: 0;
    top: -6px;
  }

  #btn {
    font-size:1.9vw;
  }

  #btn:hover {
    width:50%;
  }

  .scroll_wrap_inner_landscape{
    height: 82vh;
    margin-top: 0;
    top: -12px;
  }

  .allperson_text {
    font-size: 0.9em!important;
    color: black;
  }

  .collapsible {
    width: 100%;
    font-size: 0.7em;
  }

  .users {
    width: 100%;
    font-size: 0.6em;
  }
  .button_image_wrap {
    width: 100%;
    height: 100%;
  }

  .button_image_wrap > img {
    float: right;
    width: 70%;
    height: 173px;
    display:none;
  }
  .image1 {
    display: block;
    float: right;
    width: 56%;
    height: 50%;
    margin-right: 1px;
  }

  .image2 {
    display: block;
    float: right;
    width: 55%;
    height: 50%;
    margin-right: -18px;
    margin-top: 1px;
  }

   .button_image_wrap > .myWidget-title {
   width:100%;
   font-size: 1.1vw !important;
   padding-top: 15px;
    
  }

  .doors {
    width: 36px;
    font-size: 9px;
    margin-top: -23px;
    display: block;
    float: none;
    position: relative;
    left: 35.4%;
  }
  

  
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
  
  /* iPads, Tablets.*/
@media (min-width:481px) and ( max-width:768px)and (orientation : portrait){

    #left-panel{
    display:none;
  }
  
}










    /* Small screens, laptops.*/

    @media (min-width:769px) and (max-width:1024px)and (orientation : portrait){
    
}

</style>