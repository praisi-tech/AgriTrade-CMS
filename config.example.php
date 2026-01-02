<?php
/**
 * File: config.php.example
 * Instruction: Copy this file to 'config.php' and fill in your database credentials.
 */

$host = "localhost";
$user = "your_username";
$pass = "your_password";
$dbname = "your_database_name";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>