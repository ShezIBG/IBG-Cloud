<div class="grid-stack grid-stack-responsive-height" data-gs-animate="true">
<?php
include (APP_PATH.'/data/widgets/security/overview/markup.php');

?>
</div>
<input type="hidden" value="<?= $current_dashboard->id; ?>" name="selected-dashboard" id="selected-dashboard">
<?php include_once(APP_PATH.'/view/partials/dashboard_scripts.php') ?>