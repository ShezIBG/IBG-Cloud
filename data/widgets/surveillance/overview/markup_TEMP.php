<script src="https://kit.fontawesome.com/f2a2eacd59.js" crossorigin="anonymous"></script>


<!-- Query to update user Nonce value to assign to auth token -->
<?php
//Update nonce  field camera_auth table
$nonce_auth = Surveillance::update_nonce();

//Gets the Auth Digest Value from the calculated algorithm
$auth_digest = Surveillance::auth_digest_algorithm();

//Update and SET auth_digest in camera table
$id = $user->info->id;
$query = App::sql()->insert("UPDATE camera SET auth_id = '$auth_digest', updated_time = now() WHERE cust_id = '$id';");
?>


<!-- video display section --> 
    <div class="container_img">
        <div class="box_1 video-controls" id="video-controls">
        <?php
            $id = $user->info->id;

            $c = App::sql()->query_row("SELECT * FROM camera WHERE cust_id = '$id' AND camera_name = 'IBG Lobby'");
            echo '<video class="myVideo" id="myVideo" controls  autoplay playsinline >
                <source id="vidsource" src="http://'.trim($c->server_addr).':'.trim($c->port).'/media/'.trim($c->camera_id).'.'.trim($c->video_format).'?resolution='.trim($c->cam_res).'&auth='.trim($c->auth_id).'" type="video/webm"></source>
                Your browser does not support HTML5 video.   
            </video>
            <video class="myVideo" id="liveVideo"  controls autoplay playsinline style="display:none" >
                    <source id="vidsource" src="http://'.trim($c->server_addr).':'.trim($c->port).'/media/'.trim($c->camera_id).'.'.trim($c->video_format).'?resolution='.trim($c->cam_res).'&auth='.trim($c->auth_id).'" type="video/webm"></source>
                Your browser does not support HTML5 video.   
            </video>'; 
        ?>
        </div>
    </div>
    <br>
    <br>
    <button onclick="goLive()" style="background:red; color:white; background: red; transform: translate(66vw, -6vh); width: 7vw; height: 5vh;">Live Feed</button>
 
<!---  Calendar Section-->
 <div class="container_2">
 <input placeholder="Select Date..." class="date" style="display:none" />
</div> 

      
<!--- Camera Swap Section -->
<div class="container_box">
    <p class="myWidget-title">Select Camera</p> 
    <?php

        $id = $user->info->id;

        $camera = App::sql()->query("SELECT * FROM camera WHERE cust_id = '$id'");

    foreach ($camera as $c){
        echo '<a class="swap" onclick=swap("http://'.trim($c->server_addr).':'.trim($c->port).'/media/'.trim($c->camera_id).'.'.trim($c->video_format).'?resolution='.trim($c->cam_res).'&auth='.trim($c->auth_id).'")>
                <img src="http://'.trim($c->server_addr).':'.trim($c->port).'/ec2/cameraThumbnail?ignoreExternalArchive&time=LATEST&cameraId='.trim($c->camera_id).'&height=58&auth='.trim($c->auth_id).'">
            </a><p class="myH8" style="font-weight: bold;">'.$c->camera_name.'</p>';
    }
    ?>
</div>
<!--- Script Tag for Live Video, Video Swap and Calendar Picker -->
<script>

    const video = document.getElementById("myVideo")
    const live = document.getElementById("liveVideo")
    const videoSwap = document.getElementById("swapVideo")

    function goLive(){
        video.style.display = "block"
        live.style.display = "none"
        live.play();
    }

    const source = document.getElementById("vidsource")
    const fp = document.getElementsByClassName("date").flatpickr({
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        inline: true,
        minDate: new Date().fp_incr(-32),
        maxDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            const seconds = Date.parse(selectedDates)
            const pos = "&pos=" +  seconds
            live.src = source.src + pos
            video.style.display = "none"
            live.style.display = "block"
            video.play()
        }
    })

    function swap(id){
        var video = document.getElementById("myVideo")
        const source = document.getElementById("vidsource")
        const live = document.getElementById("liveVideo")
        var camera_id = id
        const cameraId = camera_id
        source.src  = cameraId
        video.src = source.src

        video.style.display = "block"
        live.style.display = "none"
        video.play()
    }
</script>





<!--  
 <section id="el_2">
    <div class="btn-group">
  <button>
  <i class="fa-solid fa-backward fa-1x"></i>
  </button>
  <button id="play">
  <i class="fa-solid fa-play fa-1x"></i>
  </button>
  <button onclick="pauseVid()" id="pause">
  <i class="fa-solid fa-pause fa-1x"></i>
  </button>
  <button>
  <i class="fa-solid fa-forward fa-1x"></i>
  </button>
  <button onclick="stopVid()" id="stop">
  <i class="fa-solid fa-stop fa-1x"></i>
  </button>
  <button>=
  <i class="fa-solid fa-circle fa-1x "></i>
  </button>
  <button>
  <i class="fa-solid fa-power-off fa-1x"></i>
  </button>
</div>
</section>  -->



 


<style>
span.flatpickr-weekday{
    background: #465262 !important;
    color: rgba(255,255,255) !important;
}
.flatpickr-weekdays{
    background: #465262 !important;
    color: rgba(255,255,255) !important;
}
.flatpickr-current-month .flatpickr-monthDropdown-months{
    background: #465262 !important;
    color: rgba(255,255,255) !important;
}
.flatpickr-months .flatpickr-month{
    background: #465262 !important;
    color: rgba(255,255,255) !important;
}
.flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month{
    background: #465262 !important;
    color: rgba(255,255,255) !important;
}
.flatpickr-day.selected{
    background: #465262 !important;
    border-color: #465262 !important;
}

.flatpickr-current-month input.cur-year[disabled]{

color: rgba(255,255,255) !important;

}
* {
    padding: 0;
    margin: 0;
    box-sizing: border-box;
}

    .myWidget-title{
    color: #313f4c!important;
    font-weight: bold;
    font-size: 2rem!important;
    padding: 13px 0px
    }

    .container_img{
    width: 50%;
    height: 68vh;
    margin: auto;
    }

    .container_box{
    margin: auto;
    width: 22vw;
    height: 87.9vh;
    float: left;
    position: absolute;
    top: 1px;
    }

    .container_2 {
    margin: auto;
    width: 22vw;
    float: right;
    margin-top: -75vh;
    padding-right: 37px;
    overflow: hidden;

    }
    .button{
    background: red;
    color: white;
    transform: translate(66vw, -6vh);
    width: 7vw;
    height: 5vh;
    }

    .myVideo{
    width: 100%;

    }
    #video-date{
        width:25%;
    }

    .swap{
        cursor: pointer !important;
    }

    .button {
        transform: translate(1vw,1vw);
    }

    .box_2{
        padding-top:0px
    }

    .cam {
        font-size: 0.8em!important;
        color: #465262;
        text-align:center;
        font-weight:bold
    } 

    :root{
    --dark-body: #4d4c5a;
    --dark-main: #141529;
    --dark-second: #79788c;
    --dark-hover: #323048;
    --dark-text: #f8fbff;

    --light-body: #f3f8fe;
    --light-main: #fdfdfd;
    --light-second: #c3c2c8;
    --light-hover: #edf0f5;
    --light-text: #151426;

    --blue: #0000ff;
    --white: #fff;

    --shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
}

/* .dark {
    --bg-body: var(--dark-body);
    --bg-main: var(--dark-main);
    --bg-second: var(--dark-second);
    --color-hover: var(--dark-hover);
    --color-txt: var(--dark-text);
} */

.light {
    --bg-body: var(--light-body);
    --bg-main: var(--light-main) ;
    --bg-second: var(--light-second)!;
    --color-hover: var(--light-hover);
    --color-txt: var(--light-text);
}

.calendar {
    height: max-content;
    width: max-content;
    background-color: var(--bg-main);
    border-radius: 10px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 25px;
    font-weight: 600;
    color: var(--color-txt);
    padding: 10px;
}
.month-picker {
    padding: 5px 10px;
    border-radius: 10px;
    cursor: pointer;
}

.month-picker:hover {
    background-color: var(--color-hover);
}

.year-picker {
    display: flex;
    align-items: center;
    color: white;
    
}

.year-change {
    height: 40px;
    width: 40px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    margin: 0 10px;
    cursor: pointer;
    color: white;
}

.year-change:hover {
    background-color: var(--color-hover);
}
.calendar-week-day {
    height: 50px;
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    font-weight: 600;
}

.calendar-week-day div {
    display: grid;
    place-items: center;
    color: var(--bg-second);
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    color: var(--color-txt);
}

.calendar-days div {
    width: 2.7vw;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 5px;
    position: relative;
    cursor: pointer;
    animation: to-top 1s forwards;
}
@keyframes to-top {
    0% {
        transform: translateY(100%);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
} 
.calendar-days div span {
    position: absolute;
}

.calendar-days div:hover span {
    transition: width 0.2s ease-in-out, height 0.2s ease-in-out;
}

.calendar-days div span:nth-child(1),
.calendar-days div span:nth-child(3) {
    width: 2px;
    height: 0;
    background-color: var(--color-txt);
}

.calendar-days div:hover span:nth-child(1),
.calendar-days div:hover span:nth-child(3) {
    height: 100%;
}

.calendar-days div span:nth-child(1) {
    bottom: 0;
    left: 0;
}

.calendar-days div span:nth-child(3) {
    top: 0;
    right: 0;
}

.calendar-days div span:nth-child(2),
.calendar-days div span:nth-child(4) {
    width: 0;
    height: 2px;
    background-color: var(--color-txt);
}

.calendar-days div:hover span:nth-child(2),
.calendar-days div:hover span:nth-child(4) {
    width: 100%;
}

.calendar-days div span:nth-child(2) {
    top: 0;
    left: 0;
}

.calendar-days div span:nth-child(4) {
    bottom: 0;
    right: 0;
}

.calendar-days div:hover span:nth-child(2) {
    transition-delay: 0.2s;
}

.calendar-days div:hover span:nth-child(3) {
    transition-delay: 0.4s;
}

.calendar-days div:hover span:nth-child(4) {
    transition-delay: 0.6s;
}

.calendar-days div.curr-date,
.calendar-days div.curr-date:hover {
    background-color: var(--blue);
    color: var(--white);
    border-radius: 50%;
}

.calendar-days div.curr-date span {
    display: none;
}

.calendar-footer {
    padding: 10px;
    display: none;
    justify-content: flex-end;
    align-items: center;

}

/* .toggle {
    display: flex;
}

.toggle span {
    margin-right: 10px;
    color: var(--color-txt);
}

.dark-mode-switch {
    position: relative;
    width: 48px;
    height: 25px;
    border-radius: 14px;
    background-color: var(--bg-second);
    cursor: pointer;
}

.dark-mode-switch-ident {
    width: 21px;
    height: 21px;
    border-radius: 50%;
    background-color: var(--bg-main);
    position: absolute;
    top: 2px;
    left: 2px;
    transition: left 0.2s ease-in-out;
}

.dark .dark-mode-switch .dark-mode-switch-ident {
    top: 2px;
    left: calc(2px + 50%);
} */

.month-list {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background-color: var(--bg-main);
    padding: 20px;
    grid-template-columns: repeat(3, auto);
    gap: 5px;
    display: grid;
    transform: scale(1.5);
    visibility: hidden;
    pointer-events: none;
}

.month-list.show {
    transform: scale(1);
    visibility: visible;
    pointer-events: visible;
    transition: all 0.2s ease-in-out;
}

.month-list > div {
    display: grid;
    place-items: center;
}

.month-list > div > div {
    width: 100%;
    padding: 5px 20px;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    color: var(--color-txt)!important;
}

.month-list > div > div:hover {
    background-color: var(--color-hover)!important;
}
</style>