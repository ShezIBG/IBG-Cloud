
<div class="container-fluid myContainer-fluid">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-5 myTab-mainContainer">
	<script src="https://kit.fontawesome.com/76ce47de3a.js" crossorigin="anonymous"></script>
<?php
//ACTIVE MODULES
$modules = Module::get_modules_by_id(Permission::any()->get_enabled_module_ids());

$m_url = APP_URL.$m->info->url;
$m_target = '_self';
if($m->id == Module::SMOOTHPOWER) {
$m_url = 'https://smoothpower.co.uk/portal';
$m_target = '_blank';
}
foreach($modules as $m) {
if(($m->id != Module::SETTINGS && $m->info->url) && ($m->id != Module::SECURITY && $m->info->url)&& ($m->id != Module::SURVEILLANCE && $m->info->url)
&& ($m->id != Module::STOCK && $m->info->url)&& ($m->id != Module::FIRE && $m->info->url)&& ($m->id != Module::CONTROL && $m->info->url)
&& ($m->id != Module::EVCHARGER && $m->info->url)&& ($m->id != Module::ACCESS && $m->info->url) && ($m->id != Module::MULTISENSE && $m->info->url)){
#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon"><i class="eticon eticon-2x '.$m->info->icon.'"></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->ibg_alias.'</p></div></a></div>';}
if($m->id == Module::EVCHARGER && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa fa-plug"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->alias.'</p></div></a></div>';}
if($m->id == Module::CONTROL && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa fa-building"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->ibg_alias.'</p></div></a></div>';}
if($m->id == Module::SURVEILLANCE && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa fa-eye-slash"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->ibg_alias.'</p></div></a></div>';}
	if($m->id == Module::FIRE && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa fa-fire-extinguisher"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->ibg_alias.'</p></div></a></div>';}
 if($m->id == Module::SECURITY && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa fa-shield"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->ibg_alias.'</p></div></a></div>';}
if($m->id == Module::STOCK && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa fa-archive"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->ibg_alias.'</p></div></a></div>';}
if($m->id == Module::ACCESS && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa fa-unlock-alt"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->alias.'</p></div></a></div>';}
if($m->id == Module::MULTISENSE && $m->info->url){
	#Href for database modules linkss
echo '<div class="col myTab-container"><a class="myTab-btn" href="'.APP_URL.$m->info->url.'">';
#Tab content including Icons
echo '<div class="myTab-content"><div class="myTab-icon2 fa-solid fa-temperature-high"><i class=""></i></div>';
#Use alias as a description field of each module
echo '<p class="myTab-title myText-caps">'.$m->info->alias.'</p></div></a></div>';}
}
?>


<?php
//INACTIVE MODULES
$inactive_modules = Module::get_modules_by_id(Permission::any()->get_disabled_modules_ids());
//added change to advert page when inactive
foreach($inactive_modules as $nm) {
    if($nm->id != Module::SETTINGS && $nm->info->url){
    #Href for database modules links
    echo '<div class="col myTab-container"><a class="myTab-btn-inactive myRibbonBox" href="'.APP_URL.$nm->info->inactive_url.'">';
    #Tab content including Icons
    echo '<div class="myTab-content"><div id="ribbonOption" class="ribbon ribbon-top-right"><span>Add-on</span></div><div class="myTab-icon"><i class="eticon eticon-2x '.$nm->info->ibg_icon.'"></i></div>';
    #Use alias as a description field of each module
    echo '<p class="myTab-title myText-caps">'.$nm->info->ibg_alias.'</p></div></a></div>';
    }
}
?>


    </div>
</div>

<!-- <div class="container-fluid myContainer-fluid myContainer-fluid-footer">
<div class="p-3 myContainer-footer">
	<div class="row myFooter-row">
		<div class="col-sm-4 myFooter-left">
			
			<i class="fas fa-phone-alt myBrand-colorB"></i>&nbsp;<a class="myH8 myBrand-colorB" href="tel:03309125044">0330 912 5044</a>&nbsp; &nbsp; <i class="fa fa-envelope myBrand-colorB"></i>&nbsp;<a class="myH8 myBrand-colorB" href="mailto:info@ibg-uk.com">info@ibg-uk.com</a>&nbsp; &nbsp; <i class="fa fa-twitter B"></i>
			
		</div>
		
		<div class="col-sm-4 text-center myFooter-mid">
			<p class="myH8 mt-2"> Intelligent Building Group Limited Reg No: 07977817. Terms & Conditions
			</p>
		</div>

		<div class="col-sm-4 myFooter-right" >
			<img class="float-end mx-4" src="v3/assets/img/mine/logo_eticom-dark.svg" width="80px">
			<img class="float-end" src="v3/assets/img/mine/logo_renard-dark.svg" width="85px">

		</div>
		
	</div>
</div> -->
</div>
<style>

@media (max-width: 360px){
	#left-panel{
		display:none;

	}
	#main{
		margin-left:auto;
		

	}

}

@media (max-width: 820px){
	#left-panel{
		display:none;


	}
	#main{
		margin-left:auto;
		

	}
}


</style>
