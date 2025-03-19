<?php
$host = getenv('MYSQL_HOST') ?: 'mysql.railway.internal';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: 'ZPzPPrrcfCaquTGfzfGOGzsoHqOaFFFQ';
$database = getenv('MYSQL_DATABASE') ?: 'railway';

$mysqli = new mysqli($host, $user, $password, $database);

// if ($mysqli->connect_error) {
//     die("Kết nối thất bại: " . $mysqli->connect_error);
// }
// echo "Kết nối MySQL thành công!";
?>
