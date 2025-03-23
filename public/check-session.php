<?php
// Kích hoạt hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bắt đầu session
session_start();

echo "<h1>Kiểm tra Session</h1>";

// Hiển thị cấu hình session
echo "<h2>Cấu hình Session</h2>";
echo "<pre>";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . " seconds\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . " seconds\n";
echo "</pre>";

// Hiển thị thông tin session
echo "<h2>Thông tin Session ID</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . " (1=disabled, 2=enabled but empty, 3=enabled and not empty)</p>";

// Hiển thị dữ liệu session
echo "<h2>Dữ liệu Session</h2>";
if (empty($_SESSION)) {
    echo "<p>Session trống.</p>";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

// Kiểm tra cookies
echo "<h2>Cookies</h2>";
if (empty($_COOKIE)) {
    echo "<p>Không có cookies.</p>";
} else {
    echo "<pre>";
    print_r($_COOKIE);
    echo "</pre>";
}

// Form thêm dữ liệu vào session
echo "<h2>Thêm dữ liệu vào session</h2>";
echo "<form method='post'>";
echo "<div>";
echo "<label for='key'>Key:</label>";
echo "<input type='text' id='key' name='key' required>";
echo "</div><br>";
echo "<div>";
echo "<label for='value'>Value:</label>";
echo "<input type='text' id='value' name='value' required>";
echo "</div><br>";
echo "<button type='submit' name='add_session'>Thêm vào session</button>";
echo "</form>";

// Xử lý form
if (isset($_POST['add_session'])) {
    $key = $_POST['key'];
    $value = $_POST['value'];
    $_SESSION[$key] = $value;
    echo "<p style='color: green;'>Đã thêm vào session: $key = $value</p>";
    echo "<p><a href='" . $_SERVER['PHP_SELF'] . "'>Tải lại trang</a></p>";
}

// Form đăng nhập test
echo "<hr>";
echo "<h2>Form đăng nhập test</h2>";
echo "<form method='post'>";
echo "<div>";
echo "<label for='test_username'>Username:</label>";
echo "<input type='text' id='test_username' name='test_username' required>";
echo "</div><br>";
echo "<div>";
echo "<label for='test_password'>Password:</label>";
echo "<input type='password' id='test_password' name='test_password' required>";
echo "</div><br>";
echo "<button type='submit' name='test_login'>Đăng nhập</button>";
echo "</form>";

// Xử lý đăng nhập test
if (isset($_POST['test_login'])) {
    $username = $_POST['test_username'];
    $password = $_POST['test_password'];
    
    // Set session manually for testing
    $_SESSION['user_id'] = 999;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'admin';
    $_SESSION['logged_in'] = true;
    
    echo "<p style='color: green;'>Đã set session đăng nhập test!</p>";
    echo "<p><a href='" . $_SERVER['PHP_SELF'] . "'>Tải lại trang</a></p>";
}

// Button xóa session
echo "<hr>";
echo "<form method='post'>";
echo "<button type='submit' name='clear_session'>Xóa Session</button>";
echo "</form>";

// Xử lý xóa session
if (isset($_POST['clear_session'])) {
    session_unset();
    session_destroy();
    echo "<p style='color: green;'>Đã xóa session!</p>";
    echo "<p><a href='" . $_SERVER['PHP_SELF'] . "'>Tải lại trang</a></p>";
}

echo "<hr>";
echo "<p><a href='/183intership'>Quay lại trang chủ</a></p>";
?>