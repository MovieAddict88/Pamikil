<?php
/**
 * Common Functions
 * 
 * Collection of utility functions used throughout the application.
 */

/**
 * Autoload classes
 * 
 * @param string $className Class name
 */
spl_autoload_register(function ($className) {
    $paths = [
        INCLUDES_PATH . "/{$className}.php",
        INCLUDES_PATH . "/classes/{$className}.php"
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 */
function redirect($url, $statusCode = 302) {
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Get POST data
 * 
 * @param string $key Key to get
 * @param mixed $default Default value if not set
 * @return mixed
 */
function post($key = null, $default = null) {
    if ($key === null) {
        return $_POST;
    }
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data
 * 
 * @param string $key Key to get
 * @param mixed $default Default value if not set
 * @return mixed
 */
function get($key = null, $default = null) {
    if ($key === null) {
        return $_GET;
    }
    return $_GET[$key] ?? $default;
}

/**
 * Get request data (GET or POST)
 * 
 * @param string $key Key to get
 * @param mixed $default Default value if not set
 * @return mixed
 */
function input($key = null, $default = null) {
    return get($key, post($key, $default));
}

/**
 * Check if request method is POST
 * 
 * @return bool
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request method is GET
 * 
 * @return bool
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 * 
 * @param mixed $data Data to send
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send error JSON response
 * 
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

/**
 * Send success JSON response
 * 
 * @param mixed $data Data to send
 * @param string $message Success message
 */
function jsonSuccess($data = null, $message = 'Success') {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message or null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if flash message exists
 * 
 * @return bool
 */
function hasFlash() {
    return isset($_SESSION['flash']);
}

/**
 * Format date
 * 
 * @param string $date Date string or timestamp
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format date and time
 * 
 * @param string $date Date string or timestamp
 * @return string Formatted date and time
 */
function formatDateTime($date) {
    return date('F j, Y \a\t g:i A', strtotime($date));
}

/**
 * Format relative time (e.g., "2 hours ago")
 * 
 * @param string $date Date string
 * @return string Relative time
 */
function timeAgo($date) {
    $time = strtotime($date);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes == 1 ? '1 minute ago' : "{$minutes} minutes ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours == 1 ? '1 hour ago' : "{$hours} hours ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days == 1 ? '1 day ago' : "{$days} days ago";
    } else {
        return formatDate($date);
    }
}

/**
 * Format number with commas
 * 
 * @param int|float $number Number to format
 * @return string Formatted number
 */
function formatNumber($number) {
    return number_format($number);
}

/**
 * Truncate text
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add
 * @return string Truncated text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
}

/**
 * Generate slug from string
 * 
 * @param string $string String to slugify
 * @return string Slug
 */
function slugify($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

/**
 * Sanitize output
 * 
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user has permission
 * 
 * @param string $permission Permission to check
 * @return bool
 */
function can($permission) {
    $auth = new Auth();
    
    // Admins have all permissions
    if ($auth->isAdmin()) {
        return true;
    }
    
    $user = $auth->getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Role-based permissions
    $permissions = [
        'parent' => [
            'view_child_progress', 'set_time_limits', 'restrict_content',
            'view_reports', 'manage_child_account'
        ],
        'student' => [
            'view_dashboard', 'complete_activities', 'customize_avatar',
            'view_own_progress', 'earn_rewards'
        ],
        'admin' => [
            'manage_users', 'manage_content', 'view_all_reports',
            'manage_settings', 'manage_system'
        ]
    ];
    
    return isset($permissions[$user['role']]) && 
           in_array($permission, $permissions[$user['role']]);
}

/**
 * Require authentication
 * Redirects to login if not authenticated
 * 
 * @param string $role Optional required role
 */
function requireAuth($role = null) {
    $auth = new Auth();
    
    if (!$auth->isLoggedIn()) {
        setFlash('warning', 'Please log in to continue');
        redirect(SITE_URL . '/login.php');
    }
    
    if ($role && !$auth->hasRole($role)) {
        setFlash('error', 'You do not have permission to access this page');
        redirect(SITE_URL . '/dashboard.php');
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireAuth('admin');
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function currentUrl() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
           '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get asset URL
 * 
 * @param string $path Asset path
 * @return string Asset URL
 */
function asset($path) {
    return SITE_URL . '/public/' . ltrim($path, '/');
}

/**
 * Get image URL
 * 
 * @param string $path Image path
 * @return string Image URL
 */
function image($path) {
    return asset('images/' . ltrim($path, '/'));
}

/**
 * Get current theme color
 * 
 * @return string Color
 */
function themeColor($name) {
    $colors = [
        'primary' => '#4a90d9',
        'success' => '#2ecc71',
        'warning' => '#f39c12',
        'danger' => '#e74c3c',
        'info' => '#3498db',
        'secondary' => '#95a5a6'
    ];
    
    return $colors[$name] ?? $colors['primary'];
}

/**
 * Calculate level from XP
 * 
 * @param int $xp Total XP
 * @return int Level
 */
function calculateLevel($xp) {
    return floor(sqrt($xp / XP_PER_LEVEL)) + 1;
}

/**
 * Calculate XP required for a level
 * 
 * @param int $level Target level
 * @return int XP required
 */
function xpForLevel($level) {
    return pow($level - 1, 2) * XP_PER_LEVEL;
}

/**
 * Calculate XP progress to next level
 * 
 * @param int $currentXP Current XP
 * @param int $currentLevel Current level
 * @return array Progress data
 */
function xpProgress($currentXP, $currentLevel) {
    $currentLevelXP = xpForLevel($currentLevel);
    $nextLevelXP = xpForLevel($currentLevel + 1);
    $xpInCurrentLevel = $currentXP - $currentLevelXP;
    $xpNeeded = $nextLevelXP - $currentLevelXP;
    $progress = $xpNeeded > 0 ? ($xpInCurrentLevel / $xpNeeded) * 100 : 0;
    
    return [
        'current_level' => $currentLevel,
        'xp_in_level' => $xpInCurrentLevel,
        'xp_needed' => $xpNeeded,
        'progress' => min(100, max(0, $progress))
    ];
}

/**
 * Generate star rating HTML
 * 
 * @param int $rating Rating (1-5)
 * @param int $max Maximum rating
 * @return string HTML
 */
function starRating($rating, $max = 5) {
    $html = '<div class="star-rating">';
    
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $rating) {
            $html .= '<span class="star filled">★</span>';
        } else {
            $html .= '<span class="star">☆</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Format duration
 * 
 * @param int $seconds Duration in seconds
 * @return string Formatted duration
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return $minutes . 'm ' . ($seconds % 60) . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}

/**
 * Get file size in human-readable format
 * 
 * @param int $bytes Size in bytes
 * @return string Formatted size
 */
function fileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Validate session timeout
 * 
 * @return bool True if session is valid
 */
function validateSession() {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Debug helper
 * 
 * @param mixed $var Variable to debug
 * @param bool $die Die after output
 */
function debug($var, $die = false) {
    if (DEBUG_MODE) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        if ($die) {
            die();
        }
    }
}

/**
 * Log message to file
 * 
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error)
 */
function logMessage($message, $level = 'info') {
    $logFile = LOGS_PATH . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    error_log($entry, 3, $logFile);
}
