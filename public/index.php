<?php
// Buffer output to avoid "headers already sent" error
ob_start();

// Set the root directory
define('ROOT_DIR', dirname(__DIR__));

// Include the main application file
try {
    // Include main application file
    require_once ROOT_DIR . '/index.php';
} catch (Exception $e) {
    echo "<h2>Error loading application:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

// Flush the output buffer
ob_end_flush();