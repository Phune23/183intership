<?php
session_start();
require_once '../config/db.php';
require_once '../auth/middleware.php';

// Ensure only lecturers can access this page
Middleware::requireLecturer();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Access Denied - Internship Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            text-align: center;
        }
        
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 100px auto;
        }
        
        h1 {
            color: #d9534f;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #337ab7;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>403 - Access Denied</h1>
        <p>You don't have permission to access this page.</p>
        <a href="index.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>