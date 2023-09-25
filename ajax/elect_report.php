<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<?php
include __DIR__ . '/get.php';

$selected_month = $_POST['electReport'][0];
$building_id = $_POST['building_id'];

$file_name = 'electricity_report_'.$selected_month.'.csv';

$file_url = $_SERVER['DOCUMENT_ROOT'].'/downloads/'.$building_id.'/'.$file_name.'';

if(file_exists($file_url)){
    header('Content-Type: application/x-csv');  
    header("Content-Transfer-Encoding: utf-8");   
    header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\"");
    ob_clean();
    flush();
    readfile($file_url);
    exit;
}else{
    echo'
    <div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
            <i class="eticon eticon-cross"></i>
        </button>
        <h4 class="modal-title">File Does Not Exist</h4>
    </div>
    <div class="modal-footer">
        <button type="button" id="close" class="btn btn-default" data-dismiss="modal" onclick="exitModal()">Close</button>
    </div>
    </div>
    </div>'; 
}
?>

<script>
function exitModal(){
    history.go(-1);
}
</script>






