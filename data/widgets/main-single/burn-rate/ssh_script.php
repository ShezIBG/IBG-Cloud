<?php
// PHPinfo();
// Load the SSH2 extension
if (!extension_loaded('ssh2')) {
    die('The ssh2 extension is not available.');
}

//Received arguments
$arg1 = $_POST['arg1'];
$arg2 = $_POST['arg2'];


// SSH connection details
$host = '139.162.205.219';
$port = 22;
$username = 'root';
$password = 'k12ght6k12ght6';

// Establish SSH connection
$connection = ssh2_connect($host, $port);
if (!$connection) {
    die('Failed to establish SSH connection.');
}

// Authenticate with username and password
if (!ssh2_auth_password($connection, $username, $password)) {
    die('Failed to authenticate with SSH credentials.');
}


// Execute a command or run a script
$command = "python3 test_scripts/ssh_script.py $arg1 $arg2";
$stream = ssh2_exec($connection, $command);
if (!$stream) {
    die('Failed to execute SSH command.');
}

// Wait for the command to finish
stream_set_blocking($stream, true);
$output = stream_get_contents($stream);
// print_r($output);
// Close the SSH connection
ssh2_disconnect($connection);

// Display the output
echo $output;



?>
