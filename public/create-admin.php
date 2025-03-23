<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Admin User Creation</h1>";

// Create default admin user
try {
    // Load database config
    if (file_exists(__DIR__ . '/../config/db.php')) {
        require_once __DIR__ . '/../config/db.php';
        
        if (isset($conn) && !$conn->connect_error) {
            // Check if users table exists
            $tables = $conn->query("SHOW TABLES LIKE 'users'");
            if ($tables->num_rows == 0) {
                echo "<p>Creating users table...</p>";
                
                // Create users table if it doesn't exist
                $createTable = "CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `username` VARCHAR(50) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `email` VARCHAR(100),
                    `role` ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student',
                    `is_first_login` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if ($conn->query($createTable)) {
                    echo "<p style='color: green;'>Users table created successfully</p>";
                } else {
                    echo "<p style='color: red;'>Error creating users table: " . $conn->error . "</p>";
                }
            }
            
            // Create admin user
            $username = 'admin';
            $password = password_hash('admin123', PASSWORD_DEFAULT); // Đổi mật khẩu mặc định tại đây
            $email = 'admin@example.com';
            $role = 'admin';
            
            // Check if admin exists
            $checkAdmin = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkAdmin->bind_param("s", $username);
            $checkAdmin->execute();
            $result = $checkAdmin->get_result();
            
            if ($result->num_rows == 0) {
                // Admin doesn't exist, create one
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, is_first_login) VALUES (?, ?, ?, ?, 0)");
                $stmt->bind_param("ssss", $username, $password, $email, $role);
                
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>Admin user created successfully</p>";
                    echo "<p>Username: $username<br>Password: admin123</p>";
                } else {
                    echo "<p style='color: red;'>Error creating admin: " . $stmt->error . "</p>";
                }
            } else {
                echo "<p>Admin user already exists</p>";
            }
            
            // Create a test lecturer account
            $username = 'lecturer';
            $password = password_hash('lecturer123', PASSWORD_DEFAULT);
            $email = 'lecturer@example.com';
            $role = 'lecturer';
            
            $checkLecturer = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkLecturer->bind_param("s", $username);
            $checkLecturer->execute();
            $result = $checkLecturer->get_result();
            
            if ($result->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, is_first_login) VALUES (?, ?, ?, ?, 0)");
                $stmt->bind_param("ssss", $username, $password, $email, $role);
                
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>Lecturer account created successfully</p>";
                    echo "<p>Username: $username<br>Password: lecturer123</p>";
                } else {
                    echo "<p style='color: red;'>Error creating lecturer: " . $stmt->error . "</p>";
                }
            } else {
                echo "<p>Lecturer account already exists</p>";
            }
            
            // Create a test student account
            $username = 'student';
            $password = password_hash('student123', PASSWORD_DEFAULT);
            $email = 'student@example.com';
            $role = 'student';
            
            $checkStudent = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkStudent->bind_param("s", $username);
            $checkStudent->execute();
            $result = $checkStudent->get_result();
            
            if ($result->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, is_first_login) VALUES (?, ?, ?, ?, 0)");
                $stmt->bind_param("ssss", $username, $password, $email, $role);
                
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>Student account created successfully</p>";
                    echo "<p>Username: $username<br>Password: student123</p>";
                } else {
                    echo "<p style='color: red;'>Error creating student: " . $stmt->error . "</p>";
                }
            } else {
                echo "<p>Student account already exists</p>";
            }
            
        } else {
            echo "<p style='color: red;'>Database connection error</p>";
        }
    } else {
        echo "<p style='color: red;'>Database configuration file not found!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}
?>

<p><a href="/index.php">Return to Login Page</a></p>