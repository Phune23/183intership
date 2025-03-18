<?php
session_start();
require '../config/db.php';
require_once '../auth/Auth.php';
require_once '../auth/middleware.php';

// Ensure only students can access this page
Middleware::requireStudent();

$error = '';
$success = '';
$auth = new Auth($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match";
    } else if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        $result = $auth->changePassword($_SESSION['user_id'], $oldPassword, $newPassword);
        
        if ($result) {
            $_SESSION['is_first_login'] = 0;
            $success = "Password changed successfully";
            
            // Redirect to dashboard after 2 seconds
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "Current password is incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password - Internship Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
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
        
        input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            padding: 10px 15px;
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
        }
        
        .success {
            color: green;
            margin-bottom: 15px;
        }
        
        .note {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Password</h2>
        
        <?php if ($_SESSION['is_first_login']): ?>
            <div class="note">
                <strong>Note:</strong> This is your first login. Please change your password for security reasons.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="old_password">Current Password:</label>
                <input type="password" id="old_password" name="old_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">Change Password</button>
        </form>
    </div>
</body>
</html>