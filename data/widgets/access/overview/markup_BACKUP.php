<script src="https://kit.fontawesome.com/f2a2eacd59.js" crossorigin="anonymous"></script>

<?php
$bearer_token = Access::bearer_token();

$all_persons =  Access::getall_person_details($bearer_token);

$paxton_users = Paxton::user_paxton();

$price = array_column($paxton_users, 'firstName');

array_multisort($price, SORT_ASC, $paxton_users);

$p_accesslevels = Paxton::accesslevels_paxton();

$all_doors = Paxton::doors_paxton();

?>


<style>
#outer-grid {
  display: grid;
    grid-template-rows: 1fr;
    grid-template-columns: 15% 84%;
    grid-gap: 8px;
    width: 94%;
    position: fixed;
    height: 89vh;
    margin-top: -21px;
}
#outer-grid > div {
  font-size: 4vw;
  padding: 0px;
  border-radius: 4px;
}

#outer-grid > .scroll_wrap > #btn_new{
  display: block;
  padding-top: 10px;
  padding-bottom: 11px;
  margin-top: 1px;
}



#inner-grid {
  display: grid;
  grid-template-columns: 1fr;
  grid-gap: 5px;
  background-color: #ffffff;
}
#inner-grid > div {
  padding: 8px;
}
#inner-grid > .scroll_wrap > #btn{
  width:10px;
}

#inner-contacts {
  display: grid;
  grid-template-rows: 10px;
  grid-gap: 5px;
  margin-top: -93px;
}
#inner-contacs > div {

  padding: 8px;
}

.myLine2 {
  height:5px!important;
}

.paxton_doors{
  font-weight: 500;
  color: #313f4c;
  font-size: 1.3vw !important;
  margin-bottom: 8px;
  text-align: center;
  padding-top: 5px;
}

.button_image_wrap {
  width:100%
}

.button_image_wrap > img {
float:right;
width:75%;
height:573px;
margin:-23px 2px;
}

.del_btn{
  font-weight: 500;
  color: white;
  font-size: 1vw !important;
  margin-bottom: 8px;
  text-align: center;
  background-color:red;
  width:15%;
  border:0;
}

.doors {
  width: 36px;
  font-size: 10px;
  float: right;
  margin-top: -33px;
  display: inline;
  border-radius: 39px;
  border:1px solid lightgrey;
}


.myWidget-title-access{
  color: #313f4c!important;
  font-weight: 500;
  font-size: 1.25rem!important;
  padding-left:10px;
  padding-top:54px;
}

/* .photo{

  height:50px;
  border:1px solid black;

} */

.allperson_text{

  font-size: 0.3em!important;
  color: black;

}
/*.contact_card{
   border:1px solid black;
  padding:2px;
  height:200px;

} */

/*button for content within the grid */
#btn {
   color: #465262;
    text-align: left;
    font-size: 1vw;
    border: 0;
    display: block;
    background: transparent;
    padding-left: 7px;
    margin-left: 6px;
    margin-top: 7px;
    margin-bottom: 11px;
}
#btn:hover{
    color:#313f4c ;
    background:rgba(190,222,24,1);
    box-shadow:none;
    width: 25%;
}
     
/*button for outergrid left navigation */
#btn_new{
  /* color: #006dff; */
  text-align:left;
  font-size:0.9vw;
  border:0;
  background: 0;
}

#btn_new:hover{
  color: #313f4c;
  background:rgba(190,222,24,1);
  width:100%;
}

.scroll_wrap{

position: relative;

overflow-y: scroll;

height: 90vh;

scrollbar-width: none;  /*for firefox*/

background: white;


color: #878787;

}

.scroll_wrap_inner{
  position: relative;
  overflow-y: scroll;
  height: 89vh;
  scrollbar-width: none;
  margin-top: 87px;
}
.scroll_wrap_inner::-webkit-scrollbar{
    display: none;    /*for chrome*/    
}


.collapsible {
  color: #ffffff;
    width: 50%;
    font-weight: 400;
    border: 1px solid;
    margin-top: 8px;
    border-radius: px;
    text-align: start;
    background: #313f4c;
    padding-left: 9px;
    height:40px;
}

.active, .collapsible:hover {
  color: white;
}

.users {
  padding: 0 18px;
  display: none;
  overflow: hidden;
  width:50%;
}

.button_image_wrap .door_btn {
  position: absolute;
  top: 35%;
  left: 34%;
  transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  background-color: red;
  color: white;
  font-size: 16px;
  padding: 0px 7px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
  text-align: center;
}

.button_image_wrap .door_btn2 {
  position: absolute;
  top: 42%;
  left: 52%;
  transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  background-color: red;
  color: white;
  font-size: 16px;
  padding: 0px 7px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
  text-align: center;
}

.button_image_wrap .door_btn3 {
  position: absolute;
  top: 12%;
  left: 44%;
  transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  background-color: red;
  color: white;
  font-size: 16px;
  padding: 0px 7px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
  text-align: center;
}


.button_image_wrap .door_btn4 {
  position: absolute;
  top: 22%;
  left: 88%;
  transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  background-color: red;
  color: white;
  font-size: 16px;
  padding: 0px 7px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
  text-align: center;
}


.button_image_wrap .door_btn5 {
  position: absolute;
  top: 46%;
  left: 42%;
  transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  background-color: red;
  color: white;
  font-size: 16px;
  padding: 0px 7px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
  text-align: center;
}


.button_image_wrap .door_btn6 {
  position: absolute;
  top: 39%;
  left: 45%;
  transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  background-color: red;
  color: white;
  font-size: 16px;
  padding: 0px 7px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
  text-align: center;
}



</style>



<div id="outer-grid">
  <div class="scroll_wrap">
    <p class="paxton_doors">Paxton Door Access</p> 
    <p class="myLine2"></p>
    <button id="btn_new" onclick="viewContacts()">&nbsp; Users</button>
    <button id="btn_new" onclick="viewPlaceholder1()">&nbsp; Access Levels</button>
    <button id="btn_new" onclick="viewPlaceholder2()">&nbsp; Door Access</button>
  </div>
  <div id="inner-grid">
    <div id="inner-contacts" style="display:grid">
      <div class="scroll_wrap_inner">
      <button id="btn" onclick="showAddUser()">Add New User</button>
      <?php

              $Contact_length = count($paxton_users);
              for($x=0; $x < $Contact_length; $x++){
          echo ' 
          
                <button id="btn" class="collapsible" onclick="showDelete('.$paxton_users[$x]['id'].')">'.($paxton_users[$x]['firstName']).'&nbsp'.($paxton_users[$x]['lastName']).'</button>
                <div class="users">
                <button  class="del_btn" id="btn-delete'.$paxton_users[$x]['id'].'" value="'.$paxton_users[$x]['id'].'" onclick="deleteUser('.$paxton_users[$x]['id'].')" style="display:none;">Delete</button>
                </div>'
                ;
              }
      ?>
      </div>
    </div>
    <div id="inner-add" style="display:none">
      <form action="./lib/class.access.php" method="post">
          <input type="text" name="firstname" placeholder="First Name">
          <input type="text" name="lastname" placeholder="Last Name">
          <input type="text" name="email" placeholder="Email">
          <input type="tel" name="telephone" placeholder="Telephone">
          <input type="text" name="pin" placeholder="Pin">
          <input type="submit" name="submit" value="Add User">
      </form>
    <button id="btn_new" onclick="goback()">Cancel</button>
    </div>
    <div class="allperson_text" id="page6"  style="display:none";>DASHBOARD PAGE</div>
    <div class="allperson_text" id="page7"  style="display:none";>
      <?php

        $Contact_length = count($p_accesslevels);
        for($x=0; $x < $Contact_length; $x++){
          $area_details = Paxton::accesslevels_details_paxton($x);
        echo '  
          <button type="button" id="btn_collapse" class="collapsible">'.($p_accesslevels[$x]['name']).'</button>
          <div class="users">'.$area_details['name'].'</div>';
        }
      ?>
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
        <?php
          // $open_door = Paxton::open_door(7471775);
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



<!-- Functions to swap the main grid views from none to block/grid -->
<script>
function goback(){
  const form = document.getElementById("inner-add").style.display = "none";
  const contacts = document.getElementById("inner-contacts").style.display = "block";

}
function viewContacts(){
    const elem1 = document.getElementById("page6").style.display = "none";
    const elem2 = document.getElementById("page7").style.display = "none";
    const elem3 = document.getElementById("page8").style.display = "none";
    const elem4 = document.getElementById("inner-contacts").style.display = "grid";
    const form = document.getElementById("inner-add").style.display = "none";
}
function viewDashboard() {
    const elem1 = document.getElementById("page6").style.display = "block";
    const elem2 = document.getElementById("page7").style.display = "none";
    const elem3 = document.getElementById("page8").style.display = "none";
    const elem4 = document.getElementById("inner-contacts").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}
function viewPlaceholder1() {
    const elem1 = document.getElementById("page6").style.display = "none";
    const elem2 = document.getElementById("page7").style.display = "block";
    const elem3 = document.getElementById("page8").style.display = "none";
    const elem4 = document.getElementById("inner-contacts").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}
function viewPlaceholder2() {
    const elem1 = document.getElementById("page6").style.display = "none";
    const elem2 = document.getElementById("page7").style.display = "none";
    const elem3 = document.getElementById("page8").style.display = "block";
    const elem4 = document.getElementById("inner-contacts").style.display = "none";
    const form = document.getElementById("inner-add").style.display = "none";
}
function Opendoor(id, door_id)
{
const doorled = document.getElementById("dr-"+door_id).style.backgroundColor  = "green";
let x = id
let btn = document.getElementById("door"+door_id);
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
await sleep(8000);
const doorled_close = document.getElementById("dr-"+door_id).style.backgroundColor  = "red";
}

close_door();
}

function showAddUser(){
    const form = document.getElementById("inner-add").style.display = "block";
    const contacts = document.getElementById("inner-contacts").style.display = "none";
}

function showDelete(id){
  let deleteId = id
  //console.log(deleteId)
  let btn = document.getElementById("btn-delete" + id).value
  let deleteValue = btn
  //console.log(deleteValue)
  if ( deleteId == deleteValue ){
      document.getElementById("btn-delete"+id).style.display = "block";
    //console.log('hi')
  }
  
}

function deleteUser(id)
{
  let x = id
  let userBtn = document.getElementById("btn"+id)

  let deletBtn = document.getElementById("btn-delete"+id)

  deletBtn.addEventListener("click", function(){
    fetch("http://192.168.10.18:80/eticom/lib/delete_user.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: `id=${x}`,
    })
  })
  location.reload()
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
