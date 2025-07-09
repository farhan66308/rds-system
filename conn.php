<?php
// Database connection details
$servername = "localhost"; // Hostname (usually 'localhost')
$username = "root";        // Your MySQL username
$password = "";            // Your MySQL password
$dbname = "alliora";       // The name of the database you want to connect to

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    //echo "Connected successfully to the database.";
}
?>