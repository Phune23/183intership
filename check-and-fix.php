<?php
// Kích hoạt hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Kiểm tra kết nối cơ sở dữ liệu</h1>";

// Định nghĩa đường dẫn gốc
define('ROOT_DIR', __DIR__);

// Kiểm tra kết nối cơ sở dữ liệu
try {
    if (file_exists(ROOT_DIR . '/config/db.php')) {
        require_once ROOT_DIR . '/config/db.php';
        
        if (isset($conn)) {
            if ($conn->connect_error) {
                echo "<p style='color: red;'>Lỗi kết nối: " . $conn->connect_error . "</p>";
            } else {
                echo "<p style='color: green;'>Kết nối cơ sở dữ liệu thành công!</p>";
                
                // Hiển thị thông tin cơ sở dữ liệu
                echo "<h2>Chi tiết kết nối</h2>";
                echo "<pre>";
                echo "Host: " . $host . "\n";
                echo "Database: " . $database . "\n";
                echo "Username: " . $username . "\n";
                echo "</pre>";
                
                // Kiểm tra bảng users
                $tablesResult = $conn->query("SHOW TABLES");
                
                echo "<h2>Danh sách bảng:</h2>";
                echo "<ul>";
                $hasUsersTable = false;
                
                while ($table = $tablesResult->fetch_array()) {
                    echo "<li>" . $table[0] . "</li>";
                    if ($table[0] == 'users') {
                        $hasUsersTable = true;
                    }
                }
                echo "</ul>";
                
                // Tạo bảng users nếu chưa tồn tại
                if (!$hasUsersTable) {
                    echo "<h2>Tạo bảng users:</h2>";
                    
                    $createTableSQL = "CREATE TABLE `users` (
                        `user_id` INT(11) NOT NULL AUTO_INCREMENT,
                        `username` VARCHAR(50) NOT NULL,
                        `password` VARCHAR(255) NOT NULL,
                        `email` VARCHAR(100),
                        `role` ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student',
                        `is_first_login` TINYINT(1) NOT NULL DEFAULT 1,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`user_id`),
                        UNIQUE KEY (`username`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    
                    if ($conn->query($createTableSQL)) {
                        echo "<p style='color: green;'>Tạo bảng users thành công!</p>";
                        $hasUsersTable = true;
                    } else {
                        echo "<p style='color: red;'>Lỗi tạo bảng users: " . $conn->error . "</p>";
                    }
                }
                
                // Tạo người dùng mẫu
                if ($hasUsersTable) {
                    echo "<h2>Tạo người dùng mẫu:</h2>";
                    
                    // Kiểm tra số lượng người dùng
                    $countResult = $conn->query("SELECT COUNT(*) as count FROM users");
                    $countRow = $countResult->fetch_assoc();
                    echo "<p>Số lượng người dùng hiện tại: " . $countRow['count'] . "</p>";
                    
                    // Tạo người dùng mẫu nếu chưa có
                    if ($countRow['count'] == 0) {
                        // Admin user
                        $adminUsername = 'admin';
                        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                        
                        $stmt = $conn->prepare("INSERT INTO users (username, password, role, is_first_login) VALUES (?, ?, 'admin', 0)");
                        $stmt->bind_param("ss", $adminUsername, $adminPassword);
                        
                        if ($stmt->execute()) {
                            echo "<p style='color: green;'>Tạo tài khoản admin thành công!</p>";
                            echo "<p>Username: admin<br>Password: admin123</p>";
                        } else {
                            echo "<p style='color: red;'>Lỗi tạo admin: " . $stmt->error . "</p>";
                        }
                        
                        // Lecturer user
                        $lecturerUsername = 'lecturer';
                        $lecturerPassword = password_hash('lecturer123', PASSWORD_DEFAULT);
                        
                        $stmt = $conn->prepare("INSERT INTO users (username, password, role, is_first_login) VALUES (?, ?, 'lecturer', 0)");
                        $stmt->bind_param("ss", $lecturerUsername, $lecturerPassword);
                        
                        if ($stmt->execute()) {
                            echo "<p style='color: green;'>Tạo tài khoản lecturer thành công!</p>";
                            echo "<p>Username: lecturer<br>Password: lecturer123</p>";
                        } else {
                            echo "<p style='color: red;'>Lỗi tạo lecturer: " . $stmt->error . "</p>";
                        }
                        
                        // Student user
                        $studentUsername = 'student';
                        $studentPassword = password_hash('student123', PASSWORD_DEFAULT);
                        
                        $stmt = $conn->prepare("INSERT INTO users (username, password, role, is_first_login) VALUES (?, ?, 'student', 0)");
                        $stmt->bind_param("ss", $studentUsername, $studentPassword);
                        
                        if ($stmt->execute()) {
                            echo "<p style='color: green;'>Tạo tài khoản student thành công!</p>";
                            echo "<p>Username: student<br>Password: student123</p>";
                        } else {
                            echo "<p style='color: red;'>Lỗi tạo student: " . $stmt->error . "</p>";
                        }
                    } else {
                        echo "<p>Đã có người dùng trong hệ thống. Bạn có thể thử đăng nhập với một trong các tài khoản dưới đây:</p>";
                        echo "<ul>";
                        echo "<li>Username: admin, Password: admin123</li>";
                        echo "<li>Username: lecturer, Password: lecturer123</li>";
                        echo "<li>Username: student, Password: student123</li>";
                        echo "</ul>";
                    }
                }
            }
        } else {
            echo "<p style='color: red;'>Biến kết nối cơ sở dữ liệu không tồn tại!</p>";
        }
    } else {
        echo "<p style='color: red;'>Không tìm thấy file cấu hình cơ sở dữ liệu!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>

<hr>
<p><a href="/183intership">Quay lại trang đăng nhập</a></p>