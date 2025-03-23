<?php
// Xác định môi trường đang chạy (local hay trên Railway)
$isLocal = !getenv('RAILWAY_ENVIRONMENT');

if ($isLocal) {
    // Cấu hình cho môi trường local (Laragon)
    $host = 'localhost'; // hoặc 127.0.0.1
    $user = 'root';
    $password = ''; // Laragon thường dùng mật khẩu rỗng cho tài khoản root
    $database = 'railway'; // Đã thay đổi thành tên database "railway"
} else {
    // Cấu hình cho Railway
    $host = getenv('MYSQL_HOST') ?: 'mysql-dukp.railway.internal';
    $user = getenv('MYSQL_USER') ?: 'root';
    $password = getenv('MYSQL_PASSWORD') ?: 'ZPzPPrrcfCaquTGfzfGOGzsoHqOaFFFQ';
    $database = getenv('MYSQL_DATABASE') ?: 'railway';
}

// Tạo kết nối
$conn = new mysqli($host, $user, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
// echo "Kết nối MySQL thành công!";
?>
