<?php
// Database configuration for XAMPP
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'university_wallet';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Europe/London');
?>