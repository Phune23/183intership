<?php
// filepath: auth/login.php
session_start();
require '../config/db.php';

// Xử lý tự động đăng nhập nếu nhấn nút debug
if (isset($_POST['debug_login'])) {
    // Thiết lập session như đã đăng nhập
    $_SESSION['user_id'] = 1; // ID của tài khoản test
    $_SESSION['username'] = 'test_user';
    $_SESSION['role'] = 'student'; // hoặc 'lecturer' tùy vào mục đích test
    $_SESSION['is_first_login'] = 0;
    
    // Chuyển hướng đến dashboard
    header('Location: ../student/dashboard.php');
    exit;
}

// DEBUG START - XÓA SAU KHI XONG
echo "<div style='position:fixed; top:0; left:0; background:red; color:white; padding:10px; z-index:9999;'>";
echo "DEBUG ACTIVE - " . date('H:i:s');
echo "</div>";
// DEBUG END

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']); // Loại bỏ khoảng trắng thừa
    $password = trim($_POST['password']); // Thêm trim() ở đây để loại bỏ khoảng trắng
    
    // Hiển thị giá trị POST
    echo "<div style='position:fixed; top:40px; left:0; background:blue; color:white; padding:10px; z-index:9999;'>";
    echo "Username: '$username' <br>Password: '$password'";
    echo "</div>";
    
    // Debug - xóa dòng này sau khi kiểm tra xong
    error_log("Đang thử đăng nhập với username: $username");
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Trim cả hai mật khẩu để loại bỏ khoảng trắng thừa
        $cleanPassword = trim($password);
        $cleanDbPassword = trim($user['password']);
        
        echo "<div style='background: red; color: white; padding: 10px; margin: 10px; position:fixed; top:80px; left:0; z-index:9999;'>";
        echo "<strong>DEBUG CRITICAL:</strong><br>";
        echo "Password nhập (sau trim): '" . $cleanPassword . "'<br>";
        echo "Password DB (sau trim): '" . $cleanDbPassword . "'<br>";
        echo "Khớp không: " . ($cleanPassword === $cleanDbPassword ? "CÓ" : "KHÔNG") . "<br>";
        echo "Độ dài password nhập: " . strlen($cleanPassword) . "<br>";
        echo "Độ dài password DB: " . strlen($cleanDbPassword);
        echo "</div>";
        
        // So sánh sau khi trim() cả hai giá trị
        if ($cleanPassword === $cleanDbPassword || (function_exists('password_verify') && password_verify($cleanPassword, $user['password']))) {
            // Successful login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_first_login'] = $user['is_first_login'];
            
            // Redirect based on role and first login status
            if ($user['is_first_login'] == 1) {
                if ($user['role'] == 'student') {
                    header('Location: ../student/change_password.php');
                } else {
                    header('Location: ../lecturer/change_password.php');
                }
            } else {
                if ($user['role'] == 'student') {
                    header('Location: ../student/dashboard.php');
                } else {
                    header('Location: ../lecturer/dashboard.php');
                }
            }
            exit;
        } else {
            $error = "Sai mật khẩu";
            error_log("Sai mật khẩu cho user: $username");
        }
    } else {
        $error = "Không tìm thấy tên đăng nhập";
        error_log("Không tìm thấy username: $username");
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Internship Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            /* Thay đổi từ flex thành block để các phần tử hiển thị theo chiều dọc */
            display: block;
            height: auto;
            min-height: 100vh;
        }
        
        .login-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            margin: 0 auto; /* Để căn giữa theo chiều ngang */
        }
        
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Internship Management System</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <!-- Debug Bypass - XÓA KHI ĐƯA LÊN PRODUCTION -->
        <div style="margin-top:20px; padding:10px; background:#ffecb3; border:2px dashed #ff9800; border-radius:5px;">
            <h4 style="color:#e65100; text-align:center; margin-top:0;">DEBUG - BYPASS ĐĂNG NHẬP</h4>
            <p style="color:red; font-weight:bold; font-size:12px; text-align:center;">
                Chỉ dùng cho quá trình phát triển, xóa khi release!
            </p>
            <div style="display:flex; justify-content:space-between;">
                <a href="../student/dashboard.php" style="display:inline-block; width:48%; background:#2196F3; color:white; padding:8px 0; text-align:center; text-decoration:none; border-radius:4px; font-weight:bold;">
                    Vào Dashboard Sinh viên
                </a>
                <a href="../lecturer/dashboard.php" style="display:inline-block; width:48%; background:#9C27B0; color:white; padding:8px 0; text-align:center; text-decoration:none; border-radius:4px; font-weight:bold;">
                    Vào Dashboard Giảng viên
                </a>
            </div>
            
            <div style="margin-top:10px;">
                <form method="post" action="">
                    <input type="hidden" name="debug_login" value="1">
                    <button type="submit" style="width:100%; background:#FF5722; color:white; padding:8px 0; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">
                        Tự động đăng nhập với tài khoản test
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Thêm phần debug vào trong container đăng nhập -->
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($user)): ?>
            <div style="margin-top:20px; padding:10px; background:#f8f8f8; border:1px solid #ddd; border-radius:3px;">
                <h3>Thông tin debug:</h3>
                <pre style="overflow-x: auto; white-space: pre-wrap;">
Username đã nhập: <?php echo htmlspecialchars($username); ?>
Username trong DB: <?php echo htmlspecialchars($user['username']); ?>
Mật khẩu đã nhập: <?php echo htmlspecialchars($password) . " (độ dài: " . strlen($password) . ")"; ?>
Mật khẩu trong DB: <?php echo htmlspecialchars($user['password']) . " (độ dài: " . strlen($user['password']) . ")"; ?>
So sánh trực tiếp: <?php echo ($password === $user['password'] ? "KHỚP" : "KHÔNG KHỚP"); ?>
So sánh sau khi trim: <?php echo (trim($password) === trim($user['password']) ? "KHỚP" : "KHÔNG KHỚP"); ?>
                </pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>