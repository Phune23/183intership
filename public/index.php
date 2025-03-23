<?php
// Define the application root directory
define('ROOT_DIR', dirname(__DIR__));

// Start output buffering to prevent header issues
ob_start();

// Start session at the beginning
session_start();

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
}

// End output buffering
ob_end_flush();