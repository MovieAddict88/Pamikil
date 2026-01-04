<?php
/**
 * Main Application Entry Point
 */

// Start session
session_start();

// Error reporting
if ($_ENV['APP_ENV'] ?? 'development' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Include autoloader
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/Song.php';
require_once __DIR__ . '/app/Models/Room.php';
require_once __DIR__ . '/app/Models/SongQueue.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/DashboardController.php';
require_once __DIR__ . '/app/Controllers/AdminController.php';
require_once __DIR__ . '/app/Controllers/SongsController.php';

// Include routes
require_once __DIR__ . '/routes/web.php';

// Dispatch the request
try {
    $router->dispatch();
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    
    if ($_ENV['APP_ENV'] ?? 'development' === 'development') {
        echo "<h1>Application Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        include __DIR__ . '/Views/errors/500.php';
    }
}