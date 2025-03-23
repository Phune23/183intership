<?php
// Kiểm tra xem có yêu cầu chế độ debug không
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug) {
    // Kích hoạt hiển thị lỗi cho debug
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Define the application root directory
define('ROOT_DIR', dirname(__DIR__));

// Start output buffering to prevent header issues
ob_start();

// Start session at the beginning
session_start();

// Hiển thị thông tin debug nếu được yêu cầu
if ($debug) {
    echo "<h1>Debug Information</h1>";
    echo "<h2>Session Data</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

try {
    // Include the main application
    if (file_exists(ROOT_DIR . '/index.php')) {
        require_once ROOT_DIR . '/index.php';
    } else {
        throw new Exception("Main application file not found!");
    }
} catch (Exception $e) {
    // Display any errors
    echo '<h1>Application Error</h1>';
    echo '<p>' . $e->getMessage() . '</p>';
    
    if ($debug) {
        // Hiển thị stack trace trong chế độ debug
        echo "<h2>Stack Trace</h2>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// End output buffering
ob_end_flush();
?>