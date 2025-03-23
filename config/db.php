<?php
require_once __DIR__ . '/config.php';

$host = config(['MYSQLHOST', 'MYSQL_HOST'], 'localhost');
$port = config(['MYSQLPORT', 'MYSQL_PORT'], '3306');
$username = config(['MYSQLUSER', 'MYSQL_USER'], 'root');
$password = config(['MYSQLPASSWORD', 'MYSQL_PASSWORD'], '');
$database = config(['MYSQLDATABASE', 'MYSQL_DATABASE'], 'internship_db');

// Create database connection
try {
    $conn = new mysqli($host, $username, $password, $database, $port);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
