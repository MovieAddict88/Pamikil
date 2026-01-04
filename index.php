<?php
/**
 * Main Entry Point
 * 
 * This file handles all routing and serves as the main entry point
 * for the application.
 */

// Define root path
define('APP_ROOT', __DIR__);

// Load configuration
require_once APP_ROOT . '/config/config.php';

// Load includes
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once INCLUDES_PATH . '/Security.php';
require_once INCLUDES_PATH . '/functions.php';

// Set security headers
Security::setSecurityHeaders();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session timeout
if (isset($_SESSION['user_id'])) {
    validateSession();
}

// Parse request URI
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove query string and base path if present
$requestPath = strtok($requestPath, '?');

// Handle routing
$routes = [
    '/' => 'home.php',
    '/login' => 'auth/login.php',
    '/register' => 'auth/register.php',
    '/forgot-password' => 'auth/forgot-password.php',
    '/reset-password' => 'auth/reset-password.php',
    '/logout' => 'auth/logout.php',
    '/dashboard' => 'dashboard.php',
    '/activities' => 'activities.php',
    '/activity' => 'activity.php',
    '/progress' => 'progress.php',
    '/rewards' => 'rewards.php',
    '/avatar' => 'avatar.php',
    '/leaderboard' => 'leaderboard.php',
    '/profile' => 'profile.php',
    '/admin' => 'admin/dashboard.php',
    '/admin/users' => 'admin/users.php',
    '/admin/activities' => 'admin/activities.php',
    '/admin/reports' => 'admin/reports.php',
    '/admin/settings' => 'admin/settings.php',
];

// Check for route match
if (isset($routes[$requestPath])) {
    $file = PUBLIC_PATH . '/' . $routes[$requestPath];
    
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

// Check for activity with slug
if (preg_match('#^/activity/([^/]+)$#', $requestPath, $matches)) {
    $_GET['slug'] = $matches[1];
    require PUBLIC_PATH . '/activity.php';
    exit;
}

// Check for admin routes with ID
if (preg_match('#^/admin/(users|activities)/(\d+)$#', $requestPath, $matches)) {
    $_GET['page'] = $matches[1];
    $_GET['id'] = $matches[2];
    require PUBLIC_PATH . '/admin/' . $matches[1] . '.php';
    exit;
}

// 404 Not Found
http_response_code(404);
require PUBLIC_PATH . '/404.php';
