<?php
// Debugging - uncomment if needed
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Start session at the beginning before any output
session_start();

// Try to include database configuration
try {
    if (file_exists(__DIR__ . '/config/db.php')) {
        require_once __DIR__ . '/config/db.php';
    } else {
        throw new Exception("Database configuration file not found!");
    }
} catch (Exception $e) {
    echo "<h2>Database Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    // Continue with limited functionality
}

// Rest of your application code
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/auth/Auth.php';
} catch (Exception $e) {
    echo "<h2>Application Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] == 'lecturer') {
        header('Location: lecturer/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] == 'student') {
        header('Location: student/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    }
}

$error = '';
$auth = new Auth($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Đặt tên biến rõ ràng để tránh nhầm với biến từ db.php
    $login_username = isset($_POST['username']) ? $conn->real_escape_string($_POST['username']) : '';
    $login_password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($login_username) || empty($login_password)) {
        $error = "Username and password are required";
    } else {
        $user = $auth->login($login_username, $login_password);
        
        if ($user) {
            // Log successful login
            error_log("Login successful for: " . $login_username);
            
            if ($user['is_first_login'] && $user['role'] == 'student') {
                header('Location: student/change_password.php');
                exit;
            } else if ($user['role'] == 'lecturer') {
                header('Location: lecturer/dashboard.php');
                exit;
            } else if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            } else {
                header('Location: student/dashboard.php');
                exit;
            }
        } else {
            // Log login failure
            error_log("Login failed for: " . $login_username);
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Internship Management System - Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Internship Management System</h2>
        <h3>Login</h3>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
        <div class="debug-info">
            <h4>Debug Information</h4>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>Session ID: <?php echo session_id(); ?></p>
            <h5>Session Data:</h5>
            <pre><?php print_r($_SESSION); ?></pre>
            <h5>Database Status:</h5>
            <?php 
                if (isset($conn)) {
                    echo $conn->connect_error ? "Error: " . $conn->connect_error : "Connected";
                } else {
                    echo "Connection variable not set";
                }
            ?>
            <p><a href="/183intership/public/debug-login.php">Go to Debug Login</a></p>
        </div>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 20px;">
            <small>
                <a href="/183intership/public/debug-login.php">Debug Login</a> | 
                <a href="/183intership/check-and-fix.php">Check Database</a>
            </small>
        </p>
    </div>
</body>
</html>