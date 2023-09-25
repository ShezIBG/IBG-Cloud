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
    <p class="paxtoon-door-users">Paxton Door users</p>
        <p class="form_border-paxton"></p>
      <div class="scroll_wrap_inner">
      <div class="paxton-door-position-el-1">
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
                <button id="btn" class="collapsible" onclick="showUserDetails('.$paxton_users[$x]['id'].', '.$keys_le.')">'.($paxton_users[$x]['firstName']).'&nbsp'.($paxton_users[$x]['lastName']).'</button>
                
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
                <div id="myModal'.$paxton_users[$x]['id'].'" class="modal-access">
                  <!-- Modal content -->
                  <div class="modal-content-access">
                  <p class="modal-text">Are you sure</p>
                  <button class="delete-modal" onclick="finalUserDelete('.$paxton_users[$x]['id'].')">Delete</button>
                  <button class="close-modal" onclick="closeUserDelete('.$paxton_users[$x]['id'].')">Close</button>
                  </div>
                </div>';
              }  
      ?>
      </div>
      </div>
    </div>
    <div id="inner-add" style="display:none">
    <div class="background-add-user">
    <p class="user-text"><i class="fa-solid fa-user"></i></i>Add New User</p>
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
    <div id="inner-contacts">
    <p class="paxtoon-door-users">Dashboard</p>
        <p class="form_border-paxton"></p>
    <div class="scroll_wrap_inner">
    <div class="paxton-door-position">
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
        <td class="dashboard-table-td">'.date("F jS, Y, g:i a", strtotime($de['eventDate'])).'</td>
        <td class="dashboard-table-td">'.(isset($de['id']) ? $de['id'] : 'Not configured').'</td>
        <td class="dashboard-table-td">'.(isset($de['where']) ? $de['where'] : 'Not configured').'</td>
        <td class="dashboard-table-td">'.(isset($de['tokenNumber']) ? $de['tokenNumber'] : 'Not configured').'</td>
        <td class="dashboard-table-td">'.(isset($de['eventTypeDescription']) ? $de['eventTypeDescription'] : 'Not configured').'</td>
        <td class="dashboard-table-td">'.(isset($de['firstName']) ? $de['firstName'] : 'Not configured').'</td>
        <td class="dashboard-table-td">'.(isset($de['lastName']) ? $de['lastName'] : 'Not configured').'</td>
        </tr>';
      }
      ?> 
    </table>
    </div>
    </div>
    </div>
    </div>
    <!-- Access Levels -->
    <div class="allperson_text" id="page7"  style="display:none";>
        <div id="inner-contacts">
        <p class="paxtoon-door-users">Access Levels</p>
        <p class="form_border-paxton"></p>
        <div class=" scroll_wrap_inner" id="aclevelpage">
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
                <button type="button" id="btn_collapse" class="new_access collapsible" onclick="showAllAreas('.$p_accesslevels[$x]['id'].')">'.($p_accesslevels[$x]['name']).'</button>
                <div class="users">
                  <div class="users-grid-el-2">
                    <button class="edit-button" onclick="editAccessArea('.$p_accesslevels[$x]['id'].')">Edit</button>
                    <button class="delete" onclick="deleteAccessArea('.$p_accesslevels[$x]['id'].')">Delete</button>
                  </div>
                </div>
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
              echo'<div class="grid-ground-floor">
                    
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

            echo '<div id="accesslevelModal'.$p_accesslevels[$x]['id'].'" class="modal-access">
                    <!-- Modal content -->
                    <div class="modal-content-access">
                      
                      <p id="deleting_first_text" class="modal-text">Are you sure</p>
                      <p id="deleting-text" class="modal-text" style="display:none;"></p>
                      <button id="deleting-btn" class="delete-modal" onclick="finalAccessLevelsDelete('.$p_accesslevels[$x]['id'].')">Delete</button>
                      <button id="canceling-btn" class="close-modal" onclick="closeAccessLevelsDelete('.$p_accesslevels[$x]['id'].')">Close</button>
                    </div>
                  </div>';
            }
        ?>
      </div>
      </div>
      </div>
     </div>
    <div class="allperson_text" id="page8"  style="display:none";>
    <div id="inner-contacts">
    <p class="paxtoon-door-users">Door Access</p>
        <p class="form_border-paxton"></p>
        <div class="scroll_wrap_inner">
        <div class="button_image_wrap">
        <img src="<?= APP_URL ?>/images/Floor1_IBG.png"class="image0">
        <img src="<?= APP_URL ?>/images/floor2_IBG.png"class="image1">
        <img src="<?= APP_URL ?>/images/floor3_IBG.png" class="image2">
        <button id="dr-3" class="door_btn">Door</button>
        <button id="dr-2" class="door_btn2">Door</button>
        <button id="dr-4" class="door_btn3">Door</button>
        <button id="dr-0" class="door_btn4">Door</button>
        <button id="dr-1" class="door_btn5">Door</button>
        <button id="dr-5" class="door_btn6">Door</button>
        <?php
          $Contact_length = count($all_doors);
          for($x=0; $x < $Contact_length; $x++){
          echo ' 
            <div class="myWidget-title-access">'.($all_doors[$x]['name']).'</div>
            <button id="door'.$x.'" class="doors" onclick="Opendoor('.$all_doors[$x]['id'].','.$x.')">Open Door</button>
            '; 
          }
        ?>
      </div>
      </div>
    </div>  
    </div>

    <!-- Create/Update and Delete Timezones -->
    <div class="allperson_text" id="page9"  style="display:none";>
    <div id="inner-contacts">
    <p class="paxtoon-door-users">Timezone</p>
        <p class="form_border-paxton"></p>
       <div class="scroll_wrap_inner">
      <div id="inner-timezone">
        <div class="paxton-door-position">
       <button class="table-timezone-button"onclick="new_timezone()">New Timezones:</button>
       <br>
       <?php
       $timezones_length = count($timezones);
       for($x=0; $x < $timezones_length; $x++){
        $detail_timezone = Paxton::timezone_details($timezones[$x]['id']);

        echo ' <button type="button" id="btn_collapse" class="new_access collapsible" onclick="editTimezone('.$timezones[$x]['id'].')">'.$timezones[$x]['name'].'</button>
               <div class="users-timezone">
              <div class="users-grid-el-3">
              <div id="edit-timezones-'.$timezones[$x]['id'].'" class="step-content-el-1" style="display:none;">
              <button id="'.$timezones[$x]['id'].'" class="edit"onclick="mainEdit('.$timezones[$x]['id'].')">Edit</button>
              <button id="'.$timezones[$x]['id'].'" class="delete" onclick="mainDelete('.$timezones[$x]['id'].')">Delete</button>
              </div>
              <br>
              <div id="edit-form-'.$timezones[$x]['id'].'" style="display:none">
              <form action="./lib/class.access.php" class="timezone-grid-form" method="POST">
                <input type="hidden" name="id" class="mobile-title" value="'.$detail_timezone['id'].'">
                <input type="text" name="name" class="mobile-title" value="'.$detail_timezone['name'].'">
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
                        <strong class="strong-access">Monday</strong>
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
                        <strong class="strong-access">Tuesday</strong>
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
                        <strong class="strong-access">Wednesday</strong>
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
                        <strong class="strong-access">Thursday</strong>
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
                        <strong class="strong-access">Friday</strong>
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
                        <strong class="strong-access">Saturday</strong>
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
                        <strong class="strong-access">Sunday</strong>
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
              </div>
        ';
        echo ' <div id="timezoneModal'.$timezones[$x]['id'].'" class="modal-access ">
        <!-- Modal content -->
        <div class="modal-content-access">
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
          <input type="text" name="name" placeholder="Name" class="mobile-name-timezone">
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
                  <strong class="strong-access">Monday</strong>
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
                  <strong class="strong-access">Tuesday</strong>
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
                  <strong class="strong-access">Thursday</strong>
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
                  <strong class="strong-access">Friday</strong>
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
                  <strong class="strong-access">Sunday</strong>
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
  </div>
</div>



<!-- Functions to swap the main grid views from none to block/grid -->
<script>

  // var test = document.getElementsByClassName("scroll_wrap_inner");

  //  document.getElementById('btn_new').onclick = function(){
  //   if((test.style.display === "none") && (window.innerWidth <= 480 )) {
  //     test.style.display = "block"


  // } else if((test.style.display === "block") && (window.innerWidth <= 480 )) {
  //   test.style.display = "none"
  // }
  //   }

dropdown_button()

function dropdown_button() {

  var down = document.getElementById("dropdown_menu_1");
  var inner_grid = document.getElementById("inner-grid");
  var inner_c = document.getElementById("inner-contacts");


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
function deleteAccessArea(id){
  var modal = document.getElementById("accesslevelModal"+id)

  modal.style.display = "block";
}
function finalAccessLevelsDelete(id){
  var modal = document.getElementById("accesslevelModal"+id)

  let x = id

  fetch("<?= APP_URL ?>/lib/class.access.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: `accesslevelDeleteId=${x}`,
  })

  addEventListener("click", function(){
    document.getElementById("deleting_first_text").style.display = "none";
    document.getElementById("deleting-text").style.display = "block";
    document.getElementById("deleting-text").innerHTML = "Please Wait Deleting.."
    document.getElementById("deleting-btn").style.display = "none"
    document.getElementById("canceling-btn").style.display = "none"
  })
  
  

  // setTimeout(function(){
  //   location.reload()
  // }, 3000)
  // location.reload()
}
function closeAccessLevelsDelete(id){
  var modal = document.getElementById("accesslevelModal"+id)

  if(modal.style.display == "block"){
    modal.style.display = "none";
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

  fetch("<?= APP_URL ?>/lib/open_door.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: `id=${x}`,
  })
     
  const close_door = async () => {
    await sleep(8000);
    const doorled_close = document.getElementById("dr-"+door_id).style.backgroundColor  = "red";
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
  
.collapsible:after {content: '\002B';color: black;font-weight: bold;float: right;margin-right: 12px;}
  .active:after {content: "\2212";}
  .content {padding: 0 18px;max-height: 0;overflow: hidden;transition: max-height 0.2s ease-out;}
  

</style>