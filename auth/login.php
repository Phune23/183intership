<?php
// filepath: auth/login.php
session_start();
require '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']); // Loại bỏ khoảng trắng thừa
    $password = $_POST['password'];
    
    // Debug - xóa dòng này sau khi kiểm tra xong
    error_log("Đang thử đăng nhập với username: $username");
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Debug - xóa dòng này sau khi kiểm tra xong
        error_log("Tìm thấy user: " . print_r($user, true));
        error_log("Password nhập vào: $password, Password trong DB: " . $user['password']);
        
        // Thử cả hai phương pháp xác thực
        if ($password === $user['password'] || (function_exists('password_verify') && password_verify($password, $user['password']))) {
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