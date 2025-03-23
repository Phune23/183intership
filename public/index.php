<?php
// Buffer output to avoid "headers already sent" error
ob_start();

// Set the root directory
define('ROOT_DIR', dirname(__DIR__));

// Debug database connection variables
echo "<h1>Database Connection Debug</h1>";
echo "<pre>";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ?: 'Not set') . "\n";
echo "MYSQLPORT: " . (getenv('MYSQLPORT') ?: 'Not set') . "\n";
echo "MYSQLUSER: " . (getenv('MYSQLUSER') ?: 'Not set') . "\n";
echo "MYSQLDATABASE: " . (getenv('MYSQLDATABASE') ?: 'Not set') . "\n";
echo "MYSQL_HOST: " . (getenv('MYSQL_HOST') ?: 'Not set') . "\n";
echo "MYSQL_PORT: " . (getenv('MYSQL_PORT') ?: 'Not set') . "\n";
echo "MYSQL_USER: " . (getenv('MYSQL_USER') ?: 'Not set') . "\n";
echo "MYSQL_DATABASE: " . (getenv('MYSQL_DATABASE') ?: 'Not set') . "\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Root directory: " . ROOT_DIR . "\n";
echo "</pre>";

// Include the main application file
try {
    require_once ROOT_DIR . '/index.php';
} catch (Exception $e) {
    echo "<h2>Error loading application:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

// Flush the output buffer
ob_end_flush();