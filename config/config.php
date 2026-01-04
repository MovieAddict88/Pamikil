<?php
/**
 * Main Configuration File
 * Car Management System - Pamikil
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Site configuration
define('SITE_NAME', 'Pamikil - Car Management System');
define('SITE_URL', 'http://localhost/pamikil');
define('ADMIN_EMAIL', 'admin@pamikil.com');

// File paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');
define('REPORTS_PATH', BASE_PATH . '/public/reports');
define('LOGS_PATH', BASE_PATH . '/logs');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Pagination settings
define('ITEMS_PER_PAGE', 20);
define('ADMIN_ITEMS_PER_PAGE', 50);

// Date and time settings
define('TIMEZONE', 'Asia/Manila');
date_default_timezone_set(TIMEZONE);

// Currency settings
define('CURRENCY', '₱');
define('CURRENCY_CODE', 'PHP');
define('DECIMAL_PLACES', 2);

// Password settings
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('REMEMBER_ME_EXPIRY', 2592000); // 30 days

// CSV export settings
define('CSV_ENCODING', 'UTF-8');

// Enable/disable features
define('ENABLE_REGISTRATION', false); // Disable public registration
define('ENABLE_EMAIL_NOTIFICATIONS', false); // Email notifications
define('ENABLE_SMS_NOTIFICATIONS', false); // SMS notifications

// Create necessary directories if they don't exist
$directories = [UPLOAD_PATH, REPORTS_PATH, LOGS_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Load database configuration
require_once __DIR__ . '/database.php';

// Load helper functions
require_once __DIR__ . '/../includes/functions.php';

// Check database connection
if (!testDbConnection()) {
    error_log("Database connection failed in config.php");
}
?>
