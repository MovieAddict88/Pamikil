<?php
// Application Configuration

// Application Settings
define('APP_NAME', 'Car Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/car-management');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/vehicles/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Philippine Settings
define('CURRENCY_SYMBOL', '₱');
define('DATE_FORMAT', 'm/d/Y');
define('DATETIME_FORMAT', 'm/d/Y h:i A');
define('PHONE_PATTERN', '/^(\+63|0)?9\d{9}$/');

// Pagination
define('RECORDS_PER_PAGE', 10);

// Security Settings
define('PASSWORD_MIN_LENGTH', 6);

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}
?>
