<?php
// Kích hoạt hiển thị lỗi cho debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";

// Test database connection
try {
    // Load database config
    if (file_exists(__DIR__ . '/../config/db.php')) {
        require_once __DIR__ . '/../config/db.php';
        
        // Display connection info (remove in production)
        echo "<h2>Connection Details</h2>";
        echo "<pre>";
        echo "Host: " . $host . "\n";
        echo "Port: " . $port . "\n";
        echo "Username: " . $username . "\n";
        echo "Database: " . $database . "\n";
        echo "</pre>";
        
        if (isset($conn)) {
            if ($conn->connect_error) {
                echo "<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>";
            } else {
                echo "<p style='color: green;'>Database connected successfully</p>";
                
                // Test query
                $result = $conn->query("SHOW TABLES");
                if ($result) {
                    echo "<h2>Tables in database:</h2>";
                    echo "<ul>";
                    while ($row = $result->fetch_array()) {
                        echo "<li>" . $row[0] . "</li>";
                    }
                    echo "</ul>";
                    
                    // Check if users table exists and has records
                    $users = $conn->query("SELECT COUNT(*) as count FROM users");
                    if ($users) {
                        $count = $users->fetch_assoc()['count'];
                        echo "<p>Users table contains $count records</p>";
                    } else {
                        echo "<p style='color: red;'>Could not query users table: " . $conn->error . "</p>";
                    }
                } else {
                    echo "<p style='color: red;'>Error listing tables: " . $conn->error . "</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>Database connection variable not available</p>";
        }
    } else {
        echo "<p style='color: red;'>Database configuration file not found!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}
?>