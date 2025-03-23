<?php
// Database configuration using environment variables

// Function to get configuration from environment with fallback
function getDbConfig($key, $default = null) {
    // Check for Railway-specific variables first
    $railwayVars = [
        'host' => ['MYSQLHOST', 'RAILWAY_MYSQL_HOST', 'DB_HOST'],
        'port' => ['MYSQLPORT', 'RAILWAY_MYSQL_PORT', 'DB_PORT'],
        'user' => ['MYSQLUSER', 'RAILWAY_MYSQL_USER', 'DB_USER'],
        'password' => ['MYSQLPASSWORD', 'RAILWAY_MYSQL_PASSWORD', 'DB_PASSWORD'],
        'database' => ['MYSQLDATABASE', 'RAILWAY_MYSQL_DATABASE', 'DB_NAME']
    ];
    
    if (isset($railwayVars[$key])) {
        foreach ($railwayVars[$key] as $var) {
            if (getenv($var) !== false && !empty(getenv($var))) {
                return getenv($var);
            }
        }
    }
    
    return $default;
}

// Get database configuration
$host = getDbConfig('host', 'localhost');
$port = getDbConfig('port', '3306');
$username = getDbConfig('user', 'root');
$password = getDbConfig('password', '');
$database = getDbConfig('database', 'internship_db');

// Create database connection
try {
    // Create database connection
    $conn = new mysqli($host, $username, $password, $database, (int)$port);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
