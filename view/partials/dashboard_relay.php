<?php
	$relay_url = APP_URL.'/v3/relay';
	$building_id = App::get('building', 0, true);
	if($building_id) $relay_url .= "/building/$building_id";
?>
<div style="position:fixed; top:50px; left:0; width:100%; height:100%; margin:0; padding:0;">
	<div style="position:absolute; top:0; bottom:50px; left:0; right:0;">
		<iframe src="<?= $relay_url ?>" style="border:none; width:100%; height:100%;">
		</iframe>
	</div>
</div>
