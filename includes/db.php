<?php
$servername = "localhost";
$username = "root";
$password = ""; // Assuming default XAMPP settings
$dbname = "4_indoor_gardening"; // Updated to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>