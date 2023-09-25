<?php

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $mysqli = new mysqli("109.74.202.153", "root", "k12ght6", "eticom");


  $Date_from = $_POST['mm_datefrom'];
  $Date_to = $_POST['mm_dateto'];
  $Selected_Meters = $_POST['meter_list'];
  $Meter_Name = $_POST['meter_description'];
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="CustomDate_data.csv"');

  $fp = fopen('php://output', 'w');
  /* associative array */
  $headings = array(array('Meter description','Date', 'Total Kwh Imported', 'Total Cost'));
  foreach ($headings as $fields) {
    fputcsv($fp, $fields);
  }


  foreach ($Selected_Meters as $meters){
    $query_description = $mysqli->query( "SELECT id, description FROM meter WHERE id = $meters");



    foreach ($query_description as $data){
      $m_id = $data['id'];
      $m_description = $data['description'];




      $query = "SELECT meter_id, reading_day, total_imported_total, total_cost_total FROM automated_meter_reading_history WHERE (reading_day BETWEEN '$Date_from' AND '$Date_to') AND meter_id IN ($m_id)";

      $result = $mysqli->query($query);
    

      foreach ($result as $reading_data){
        $reading_day = $reading_data['reading_day'];
        $total_imported_total = $reading_data['total_imported_total'];
        $total_cost_total = $reading_data['total_cost_total'];


        $arr = array(array($m_description, $reading_day, $total_imported_total, $total_cost_total));
        foreach ($arr as $fields) {
            fputcsv($fp, $fields);
        }



      }


     

    }

  }
  

  // $query = "SELECT meter_id, reading_day, total_imported_total, total_cost_total FROM automated_meter_reading_history WHERE (reading_day BETWEEN '$Date_from' AND '$Date_to') AND meter_id IN (" . implode(',', $Selected_Meters) . ")";

  // $result = $mysqli->query($query);

  // /* numeric array */
  // $row = $result->fetch_array(MYSQLI_NUM);
  // printf("%s (%s)\n", $row[0], $row[1]);
  // header('Content-Type: text/csv');
  // header('Content-Disposition: attachment; filename="CustomDate_data.csv"');

  // $fp = fopen('php://output', 'w');
  // /* associative array */
  // $headings = array(array('meter_id','Date', 'Total Kwh Imported', 'Total Cost'));
  // foreach ($headings as $fields) {
  //   fputcsv($fp, $fields);
  // }


  // while($row = $result->fetch_array(MYSQLI_ASSOC)){
  //     $arr = array(array($row["meter_id"],$row["reading_day"],$row["total_imported_total"], $row["total_cost_total"]));
  //     foreach ($arr as $fields) {
  //       fputcsv($fp, $fields);
  //   }


  // }

?>
