<?php
// Database configuration with Railway environment support

// Function to get configuration from environment with fallback
function getDbConfig($key, $default = null) {
    // Check for Railway-specific variables first
    $railwayVars = [
        'host' => ['MYSQLHOST', 'MYSQL_HOST', 'DB_HOST'],
        'port' => ['MYSQLPORT', 'MYSQL_PORT', 'DB_PORT'],
        'user' => ['MYSQLUSER', 'MYSQL_USER', 'DB_USER'],
        'password' => ['MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DB_PASSWORD'],
        'database' => ['MYSQLDATABASE', 'MYSQL_DATABASE', 'DB_NAME']
    ];
    
    if (isset($railwayVars[$key])) {
        foreach ($railwayVars[$key] as $var) {
            if (getenv($var) !== false) {
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
$database = getDbConfig('database', 'railway');

// Create database connection with better error handling
try {
    // Create the connection with error reporting
    $conn = @new mysqli($host, $username, $password, $database, (int)$port);
    
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
