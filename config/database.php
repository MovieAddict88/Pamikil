<?php
// Database Configuration
// Update these settings based on your hosting environment

// For InfinityFree or other shared hosting
define('DB_HOST', 'localhost');
define('DB_NAME', 'car_management_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// For production, use environment-specific credentials
// define('DB_HOST', 'sql123.infinityfree.com');
// define('DB_NAME', 'your_database_name');
// define('DB_USER', 'your_username');
// define('DB_PASS', 'your_password');

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Timezone for Philippines
date_default_timezone_set('Asia/Manila');
?>
