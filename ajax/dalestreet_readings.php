<?php 

include __DIR__ . '/get.php';
//  Build the Sql query with values from the FORM
$meter_list = get_monthly_building_cost();

$Selected_month = $_POST['month'][0];
$Selected_year = $_POST['month'][1];

$con = new mysqli("109.74.202.153", "root", "k12ght6", "eticom");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="Meter_readings_('.$Selected_month.'/'.$Selected_year.').csv"');

$fp = fopen('php://output', 'w');
/* associative array */
$headings = array(array('Meter Description', 'Meter Reading ('.$Selected_month.'/'.$Selected_year.')'));
foreach ($headings as $fields) {
  fputcsv($fp, $fields);
}



foreach($meter_list as $meters){
    $query = $con->query( "
    SELECT id, description, init_date, replaced_meter_reading FROM meter
    WHERE id = $meters->id AND meter_type IN ('E','G');"
    );

    foreach($query as $data){
        // $reading_total = $data['reading_total'];
        $description = $data['description'];
        $init_date = $data['init_date'];
        $m_id = $data['id'];
        $replaced_meter_reading = $data['replaced_meter_reading'];
        // print_r($m_id);
        // echo '&nbsp';
        // print_r($description);
        // echo '&nbsp';
        

        $r_query = $con->query( "
        SELECT reading_date, reading_1, imported_rate_1 FROM meter_reading
        WHERE meter_id = $m_id AND reading_date = '$Selected_year-$Selected_month-01';"
        );

        // if(mysqli_num_rows($r_query) == 0){
        //     // print_r('0');
        //     // echo '&nbsp';
        //     // print_r($replaced_meter_reading);
        //     // echo '<br/>';
        // };
        

        foreach($r_query as $new_data){
            // $reading_total = $data['reading_total'];
            $reading_1 = $new_data['reading_1'];
            $imported_rate_1 = $new_data['imported_rate_1'];
            $reading_date = $new_data['reading_date'];
            // echo '&nbsp';
            // print_r($replaced_meter_reading);
            // echo '&nbsp';
            // echo $replaced_meter_reading+$reading_1;
            // echo '<br/>';
        }

        $arr = array(array($description,$replaced_meter_reading+$reading_1));
        foreach ($arr as $fields) {
            fputcsv($fp, $fields);
        }

    }



}
