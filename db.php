<?php
// db.php - This file connects your PHP website code to your MySQL database

$host = "localhost";
$user = "root";
$password = ""; // Default WAMP password is completely empty/blank
$dbname = "suni_db"; // The database we just made in your terminal

// Create the connection link
$conn = new mysqli($host, $user, $password, $dbname);

// Check if the connection has an error
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>