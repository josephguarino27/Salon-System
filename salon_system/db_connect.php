<?php
// Standard XAMPP settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "salon_system"; // Unified database name
$port = 3306; // Standard MySQL port

// Create connection without selecting the database first
$conn = new mysqli($servername, $username, $password, "", $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . 
        "<br>Ensure MySQL is running on port $port.");
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($dbname);
} else {
    die("Error creating database: " . $conn->error);
}

// Set encoding
$conn->set_charset("utf8mb4");
?>