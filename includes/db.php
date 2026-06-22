<?php
// Database connection using MySQLi
$host = "localhost";
$user = "root";
$password = ""; // WAMP default has no password
$database = "tours_travels";

// Connect to MySQL
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
