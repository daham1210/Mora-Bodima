<?php



$servername = "localhost";
$username = "superuser";
$password = "1234";
$dbname = "morabodima";

// Create Connection to Bodim DB
$conn = new mysqli($servername, $username, $password, $dbname);

//Error Check
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>