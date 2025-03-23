<?php
// Kích hoạt hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Định nghĩa đường dẫn gốc
define('ROOT_DIR', dirname(__DIR__));

// Bắt đầu session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Cập nhật thông tin chi tiết giảng viên</h1>";

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'lecturer') {
    echo "<p style='color: red;'>Bạn phải đăng nhập với vai trò giảng viên để sử dụng tính năng này!</p>";
    echo "<p><a href='/183intership'>Đăng nhập</a></p>";
    exit;
}

// Kết nối CSDL
try {
    if (file_exists(ROOT_DIR . '/config/db.php')) {
        require_once ROOT_DIR . '/config/db.php';
        
        if (isset($conn) && !$conn->connect_error) {
            echo "<p style='color: green;'>Kết nối CSDL thành công!</p>";
            
            // Kiểm tra cấu trúc bảng users
            $columnsResult = $conn->query("DESCRIBE users");
            $columns = [];
            $hasIdColumn = false;
            $hasUserIdColumn = false;
            
            while ($column = $columnsResult->fetch_assoc()) {
                $columns[$column['Field']] = $column;
                if ($column['Field'] == 'id') $hasIdColumn = true;
                if ($column['Field'] == 'user_id') $hasUserIdColumn = true;
            }
            
            echo "<h3>Cấu trúc bảng users hiện tại:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Xác định tên cột ID
            $id_column = $hasIdColumn ? 'id' : ($hasUserIdColumn ? 'user_id' : null);
            
            if (!$id_column) {
                echo "<p style='color: red;'>Không tìm thấy cột ID trong bảng users!</p>";
                exit;
            }
            
            // Kiểm tra và thêm các cột cần thiết
            $neededColumns = [
                'first_name' => "VARCHAR(50) NULL",
                'last_name' => "VARCHAR(50) NULL",
                'email' => "VARCHAR(100) NULL",
                'department' => "VARCHAR(100) NULL",
                'profile_picture' => "VARCHAR(255) NULL"
            ];
            
            $columnsAdded = false;
            foreach ($neededColumns as $columnName => $columnType) {
                if (!isset($columns[$columnName])) {
                    $addColumnQuery = "ALTER TABLE users ADD COLUMN $columnName $columnType";
                    if ($conn->query($addColumnQuery)) {
                        echo "<p style='color: green;'>Đã thêm cột '$columnName' vào bảng users</p>";
                        $columnsAdded = true;
                    } else {
                        echo "<p style='color: red;'>Lỗi khi thêm cột '$columnName': " . $conn->error . "</p>";
                    }
                }
            }
            
            // Hiển thị cấu trúc bảng sau khi thêm cột
            if ($columnsAdded) {
                $updatedColumnsResult = $conn->query("DESCRIBE users");
                echo "<h3>Cấu trúc bảng users sau khi thêm cột:</h3>";
                echo "<table border='1'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
                
                while ($column = $updatedColumnsResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $column['Field'] . "</td>";
                    echo "<td>" . $column['Type'] . "</td>";
                    echo "<td>" . $column['Null'] . "</td>";
                    echo "<td>" . $column['Default'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            
            // Lấy thông tin hiện tại của giảng viên
            $user_id = $_SESSION['user_id'];
            
            // Sử dụng đúng tên cột ID
            $query = "SELECT * FROM users WHERE $id_column = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Hiển thị form để cập nhật thông tin
                echo "<h3>Cập nhật thông tin cá nhân</h3>";
                echo "<form method='post' action=''>";
                echo "<input type='hidden' name='user_id' value='" . $user[$id_column] . "'>";
                
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='first_name'>Họ:</label><br>";
                echo "<input type='text' id='first_name' name='first_name' value='" . htmlspecialchars($user['first_name'] ?? '') . "' style='width: 300px;'>";
                echo "</div>";
                
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='last_name'>Tên:</label><br>";
                echo "<input type='text' id='last_name' name='last_name' value='" . htmlspecialchars($user['last_name'] ?? '') . "' style='width: 300px;'>";
                echo "</div>";
                
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='email'>Email:</label><br>";
                echo "<input type='email' id='email' name='email' value='" . htmlspecialchars($user['email'] ?? '') . "' style='width: 300px;'>";
                echo "</div>";
                
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='department'>Khoa/Bộ môn:</label><br>";
                echo "<input type='text' id='department' name='department' value='" . htmlspecialchars($user['department'] ?? '') . "' style='width: 300px;'>";
                echo "</div>";
                
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='profile_picture'>URL Ảnh đại diện:</label><br>";
                echo "<input type='text' id='profile_picture' name='profile_picture' value='" . htmlspecialchars($user['profile_picture'] ?? '') . "' style='width: 300px;'>";
                echo "</div>";
                
                echo "<button type='submit' name='update_profile'>Cập nhật thông tin</button>";
                echo "</form>";
                
                // Xử lý cập nhật thông tin
                if (isset($_POST['update_profile'])) {
                    $first_name = $_POST['first_name'];
                    $last_name = $_POST['last_name'];
                    $email = $_POST['email'];
                    $department = $_POST['department'];
                    $profile_picture = $_POST['profile_picture'];
                    
                    // Câu lệnh UPDATE sử dụng đúng tên cột ID
                    $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, department = ?, profile_picture = ? WHERE $id_column = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("sssssi", $first_name, $last_name, $email, $department, $profile_picture, $user_id);
                    
                    if ($updateStmt->execute()) {
                        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin: 15px 0; border-radius: 4px;'>";
                        echo "<p>Thông tin đã được cập nhật thành công!</p>";
                        echo "</div>";
                    } else {
                        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 15px 0; border-radius: 4px;'>";
                        echo "<p>Lỗi khi cập nhật: " . $updateStmt->error . "</p>";
                        echo "</div>";
                    }
                }
            } else {
                echo "<p style='color: red;'>Không tìm thấy thông tin giảng viên!</p>";
            }
        } else {
            echo "<p style='color: red;'>Lỗi kết nối CSDL: " . ($conn ? $conn->connect_error : "Không có kết nối") . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Không tìm thấy file cấu hình CSDL!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>

<hr>
<p><a href="/183intership/lecturer/dashboard.php">Quay lại trang quản lý</a></p>