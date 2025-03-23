<?php
// Start session at the beginning before any output
session_start();

// Try to include database configuration
try {
    require_once __DIR__ . '/config/db.php';
    
    // If we get here, DB connection was successful
    // echo "<p style='color:green'>Database connection successful!</p>";
    
} catch (Exception $e) {
    echo "<h2>Database Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    // Continue with limited functionality
}

// Rest of your application code
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/auth/Auth.php';
    
    // Existing code continues...
    
    // Use PhpOffice\PhpSpreadsheet\IOFactory;
    // Use PhpOffice\PhpSpreadsheet\Spreadsheet;
    
} catch (Exception $e) {
    echo "<h2>Application Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

// Continue with the rest of your application

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'lecturer') {
        header('Location: lecturer/dashboard.php');
        exit;
    } else {
        header('Location: student/dashboard.php');
        exit;
    }
}

$error = '';
$auth = new Auth($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $user = $auth->login($username, $password);
    
    if ($user) {
        if ($user['is_first_login'] && $user['role'] == 'student') {
            header('Location: student/change_password.php');
            exit;
        } else if ($user['role'] == 'lecturer') {
            header('Location: lecturer/dashboard.php');
            exit;
        } else {
            header('Location: student/dashboard.php');
            exit;
        }
    } else {
        $error = "Invalid username or password";
    }
}
// phpinfo(); ẩn table thông tin php
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
            height: 100vh;
            background-color: #f4f4f4;
        }
        
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        
        h2 {
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            width: 100%;
            padding: 10px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>