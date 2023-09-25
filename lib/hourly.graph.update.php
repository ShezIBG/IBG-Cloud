<?php
// Database connection details (placeholders, replace with your actual database credentials)
$hostname = '109.74.202.153';
$username = 'root';
$password = 'k12ght6';
$database = 'eticom';

// Establish the MySQL connection
$mysqli = new mysqli($hostname, $username, $password, $database);

// Check if the connection was successful
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

if (isset($_POST['selected_date'])) {
    $selectedDate = $_POST['selected_date'];
    $meter_id = $_POST['meter_id'];

    // Perform your SQL query here to fetch the updated data based on the selected date
    // Replace the following code with your actual SQL query to fetch the new data
    $hourly_data = []; // Placeholder for fetched data

    // Example SQL query (assuming $meter->id is already defined)
    $qry_hours = "";
    for ($x = 0; $x <= 23; $x++) {
        $qry_hours .= "unit_imported_total_hour_" . $x . ", ";
    }
    $str_hours = substr($qry_hours, 0, -2);


    // get meter_type
    // Prepare the SQL statement with a placeholder for the id
    $stmt_type = $mysqli->prepare("SELECT meter_type FROM meter WHERE id = ?");
    $stmt_type->bind_param("i", $meter_id); // "i" indicates that the id is an integer
    // Execute the prepared statement
    $stmt_type->execute();
    // Bind the result to a variable
    $stmt_type->bind_result($meter_type);
    // Fetch the result
    $stmt_type->fetch();
    // Close the statement
    $stmt_type->close();



    // Prepare the SQL statement with placeholders and bind the selected date and meter ID
    $stmt = $mysqli->prepare("SELECT $str_hours FROM meter_usage_hourly_step_60_days WHERE meter_id = ? AND date = ?");
    $stmt->bind_param("ss", $meter_id, $selectedDate);

    // Execute the prepared statement
    $stmt->execute();

    // Get the result set
    $result = $stmt->get_result();

    // Fetch the data from the result set
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            for ($h = 0; $h <= 23; $h++) {
                array_push($hourly_data, $row['unit_imported_total_hour_' . $h]);
            }
            $hourly_data[] = $row['unit_imported_total_hour_' . $h];
        }
    }

    // Close the statement
    $stmt->close();

    // Set the meter_type variable as well (assuming $meter_type is already defined)
    //$meter_type = "E"; // Placeholder for meter_type, replace with actual value

    // Prepare the response data
    $response_data = array(
        'hourly_data' => $hourly_data,
        'barColors' => getBarColors($meter_type) // Function to return barColors based on meter_type
    );

    // Return the response data as JSON
    header('Content-Type: application/json');
    echo json_encode($response_data);
}

// Function to determine bar colors based on meter_type
function getBarColors($meter_type)
{
    $barColors = [];
    switch ($meter_type) {
        case "E":
            $barColors = ["#56b87c"];
            break;
        case "W":
            $barColors = ["#0097ce"];
            break;
        case "G":
            $barColors = ["#adb2b7"];
            break;
        case "H":
            $barColors = ["#F08080"];
            break;
        case "EG":
            $barColors = ["#A2B83A"];
            break;
        default:
            $barColors = ["#56b87c"]; // Default color if meter_type is unknown
            break;
    }
    return $barColors;
}
