<?php

class Multisense {


public static function dbConnect(){
    $yesterday = date('Y-m-d',strtotime("-1 days"));
    //print_r($yesterday.'%');exit;
    $servername = "52.211.218.106";
    $username = "root";
    $password = "k12ght6k12ght6";
    $dbname = "multisense";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }
    
    $sql = "SELECT * FROM sensor_data WHERE DATETIME LIKE '".$yesterday."%'";
    $result = $conn->query($sql);
    
    //Array initialisations
    $pressure = array();
    $temp = array();
    $tvoc = array();
    $co2 = array();
    $humidity = array();
    $datetime = array();

    
    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            array_push($pressure, $row['pressure']);
            array_push($temp, $row['temp']);
            array_push($tvoc, $row['tvoc']);
            array_push($co2, $row['co2']);
            array_push($humidity, $row['humidity']);
            array_push($datetime, $row['datetime']);
        }
      } else {
        // echo "0 results";
      }
    $conn->close();
    $return_list = [$pressure, $temp, $tvoc, $co2, $humidity, $datetime];

    return $return_list;

}


public static function updateData($date){
  $yesterday = $date;
  //print_r($yesterday.'%');exit;
  $servername = "52.211.218.106";
  $username = "root";
  $password = "k12ght6k12ght6";
  $dbname = "multisense";
  
  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  
  $sql = "SELECT * FROM sensor_data WHERE DATETIME LIKE '".$yesterday."%'";
  $result = $conn->query($sql);
  
  //Array initialisations
  $pressure = array();
  $temp = array();
  $tvoc = array();
  $co2 = array();
  $humidity = array();
  $datetime = array();

  
  if ($result->num_rows > 0) {
      // output data of each row
      while($row = $result->fetch_assoc()) {
          array_push($pressure, $row['pressure']);
          array_push($temp, $row['temp']);
          array_push($tvoc, $row['tvoc']);
          array_push($co2, $row['co2']);
          array_push($humidity, $row['humidity']);
          array_push($datetime, $row['datetime']);
      }
    } else {
      echo "0 results";
    }
  $conn->close();
  $return_list = [$pressure, $temp, $tvoc, $co2, $humidity, $datetime];

  return $return_list;

}


















    
}





?>