<?php
// Database connection configuration

// Get database details from environment variables (Railway provides these)
$host = getenv('MYSQLHOST') ?: 'mysql.railway.internal'; 
$port = getenv('MYSQLPORT') ?: '3306';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'ZPzPPrrcfCaquTGfzfGOGzsoHqOaFFFQ';
$database = getenv('MYSQLDATABASE') ?: 'railway';

// Create connection with error handling
try {
    $conn = new mysqli($host, $username, $password, $database, (int)$port);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
