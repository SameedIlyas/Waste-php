<?php
$host = "waste-segregation.mysql.database.azure.com"; // Azure MySQL host
$username = "waste_user"; // Your database username
$password = "Password123"; // Your database password
$database = "waste-segregation"; // Your database name
$port = 3306; // Default MySQL port

// Create a new MySQLi instance and connect to the database
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

echo "Connected successfully!";
?>
