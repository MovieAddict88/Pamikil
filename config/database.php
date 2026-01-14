<?php
/**
 * Database Configuration File
 * Car Management System - Pamikil
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_username');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDbConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}

// Get database instance (singleton pattern)
function getDb() {
    static $conn = null;
    if ($conn === null) {
        $conn = getDbConnection();
    }
    return $conn;
}

// Close database connection
function closeDbConnection($conn) {
    if ($conn !== null) {
        $conn = null;
    }
}

// Test database connection
function testDbConnection() {
    try {
        $conn = getDbConnection();
        $conn->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}
?>
