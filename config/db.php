<?php
// Database configuration with Railway environment variables

// Log environment variables for debugging (xóa trong production)
error_log("MYSQLHOST: " . getenv('MYSQLHOST'));
error_log("MYSQLUSER: " . getenv('MYSQLUSER'));
error_log("MYSQLDATABASE: " . getenv('MYSQLDATABASE'));

// Get database details from environment variables ======> laragon
$host = getenv('MYSQLHOST') ?: 'localhost'; 
$port = getenv('MYSQLPORT') ?: '3306';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: 'railway';

// Get database details from environment variables ======> railway
// $host = getenv('MYSQLHOST') ?: 'mysql.railway.internal'; 
// $port = getenv('MYSQLPORT') ?: '3306';
// $username = getenv('MYSQLUSER') ?: 'root';
// $password = getenv('MYSQLPASSWORD') ?: 'ZPzPPrrcfCaquTGfzfGOGzsoHqOaFFFQ';
// $database = getenv('MYSQLDATABASE') ?: 'railway';

// Create connection with improved error handling
try {
    $conn = @new mysqli($host, $username, $password, $database, (int)$port);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        // Continue without database for basic functionality
    } else {
        // Set charset
        $conn->set_charset("utf8mb4");
        error_log("Database connection successful");
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    // Allow the application to continue without the database
}
?>
