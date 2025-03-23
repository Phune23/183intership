<?php
// Kích hoạt hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Định nghĩa đường dẫn gốc
define('ROOT_DIR', dirname(__DIR__));

echo "<h1>Kiểm tra class Auth</h1>";

// Kiểm tra file Auth.php
if (file_exists(ROOT_DIR . '/auth/Auth.php')) {
    echo "<p style='color: green;'>File Auth.php tồn tại!</p>";
    
    // Kiểm tra kết nối CSDL
    try {
        if (file_exists(ROOT_DIR . '/config/db.php')) {
            require_once ROOT_DIR . '/config/db.php';
            
            if (isset($conn) && !$conn->connect_error) {
                echo "<p style='color: green;'>Kết nối CSDL thành công!</p>";
                
                // Hiển thị mã nguồn của Auth.php
                echo "<h2>Mã nguồn của Auth.php</h2>";
                echo "<pre>";
                highlight_file(ROOT_DIR . '/auth/Auth.php');
                echo "</pre>";
                
                // Yêu cầu file Auth.php
                require_once ROOT_DIR . '/auth/Auth.php';
                
                // Khởi tạo Auth class
                $auth = new Auth($conn);
                echo "<p style='color: green;'>Đã khởi tạo class Auth thành công!</p>";
                
                // Hiển thị phương thức login()
                echo "<h2>Kiểm tra phương thức login()</h2>";
                $reflection = new ReflectionMethod('Auth', 'login');
                echo "<pre>";
                echo $reflection->getDocComment() ?: "Không có document comment\n";
                echo "\n";
                
                // Lấy mã nguồn của phương thức login
                $startLine = $reflection->getStartLine();
                $endLine = $reflection->getEndLine();
                $source = file(ROOT_DIR . '/auth/Auth.php');
                $methodSource = implode("", array_slice($source, $startLine - 1, $endLine - $startLine + 1));
                echo htmlspecialchars($methodSource);
                echo "</pre>";
                
                // Thử đăng nhập với tài khoản admin
                echo "<h2>Thử đăng nhập với tài khoản admin</h2>";
                $user = $auth->login('admin', 'admin123');
                
                if ($user) {
                    echo "<p style='color: green;'>Đăng nhập thành công!</p>";
                    echo "<pre>";
                    print_r($user);
                    echo "</pre>";
                } else {
                    echo "<p style='color: red;'>Đăng nhập thất bại!</p>";
                }
            } else {
                echo "<p style='color: red;'>Lỗi kết nối CSDL!</p>";
            }
        } else {
            echo "<p style='color: red;'>Không tìm thấy file cấu hình CSDL!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>Không tìm thấy file Auth.php!</p>";
}
?>