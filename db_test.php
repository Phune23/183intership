<?php
// Include database configuration file
require_once 'config/db.php';

// Test database connection
if ($conn->ping()) {
    echo "<h2 style='color: green'>✓ Database connection successful!</h2>";
    echo "<p>Connected to: {$host} as {$username}</p>";
    echo "<p>Database: {$database}</p>";
    
    // Check if tables exist
    $tables = array(
        'users', 
        'lecturers', 
        'students', 
        'internship_courses', 
        'student_courses', 
        'internship_details'
    );
    
    echo "<h3>Checking database tables:</h3>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result->num_rows > 0) {
            echo "<li style='color: green'>✓ Table '{$table}' exists</li>";
        } else {
            echo "<li style='color: red'>✗ Table '{$table}' does not exist</li>";
        }
    }
    
    echo "</ul>";
    
} else {
    echo "<h2 style='color: red'>✗ Database connection failed!</h2>";
    echo "<p>Error: " . $conn->error . "</p>";
}
?>

<p><a href="create_tables.php">Create missing tables</a></p>