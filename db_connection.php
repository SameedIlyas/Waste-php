<?php

$host = getenv('DB_HOST'); // Azure MySQL host
$username = getenv('DB_USERNAME'); // Your database username
$password = getenv('DB_PASSWORD'); // Your database password
$database = getenv('DB_NAME'); // Your database name
$port = getenv('DB_PORT'); // Default MySQL port


// Create a new MySQLi instance and connect to the database
$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}
?>
