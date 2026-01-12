<?php
/**
 * Pamikil Learning Platform - Configuration File
 * 
 * This file contains all configuration settings for the platform.
 * Copy this file and rename to config.local.php for local development.
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pamikil_learning');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// SITE CONFIGURATION
// ============================================================
define('SITE_NAME', 'Pamikil Learning');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost');
define('SITE_DESCRIPTION', 'Fun and engaging learning for kids!');
define('ADMIN_EMAIL', 'admin@pamikil.com');

// ============================================================
// PATH CONFIGURATION
// ============================================================
define('PUBLIC_PATH', APP_ROOT . '/public');
define('INCLUDES_PATH', APP_ROOT . '/includes');
define('TEMPLATES_PATH', APP_ROOT . '/templates');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('LOGS_PATH', APP_ROOT . '/logs');

// ============================================================
// SECURITY CONFIGURATION
// ============================================================
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('REMEMBER_ME_EXPIRY', 2592000); // 30 days in seconds
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour in seconds

// ============================================================
// GAMIFICATION CONFIGURATION
// ============================================================
define('COINS_PER_LEVEL', 10);
define('XP_PER_LEVEL', 50);
define('MAX_DAILY_ACTIVITIES', 20);
define('STAR_RATING_THRESHOLD', 90); // Score percentage for 5 stars

// ============================================================
// FILE UPLOAD CONFIGURATION
// ============================================================
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/wav', 'audio/ogg']);

// ============================================================
// EMAIL CONFIGURATION
// ============================================================
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@pamikil.com');
define('SMTP_FROM_NAME', SITE_NAME);

// ============================================================
// ERROR REPORTING
// ============================================================
// Set to 0 in production
define('DEBUG_MODE', getenv('DEBUG_MODE') ?: 0);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/error.log');
}

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set('UTC');

// ============================================================
// PHP VERSION CHECK
// ============================================================
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP 7.4 or higher is required. Your version: ' . PHP_VERSION);
}
