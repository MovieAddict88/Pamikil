<?php
/**
 * Helper Functions
 * Car Management System - Pamikil
 */

// Security functions

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Check if user has one of the specified roles
 */
function hasAnyRole($roles) {
    return isLoggedIn() && in_array($_SESSION['role'], (array)$roles);
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . SITE_URL . '/unauthorized.php');
        exit;
    }
}

/**
 * Require one of specified roles
 */
function requireAnyRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        header('Location: ' . SITE_URL . '/unauthorized.php');
        exit;
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    static $user = null;
    if ($user === null) {
        $db = getDb();
        $stmt = $db->prepare("SELECT id, username, full_name, email, phone, role, location_id, profile_image FROM users WHERE id = ?");
        $stmt->execute([getCurrentUserId()]);
        $user = $stmt->fetch();
    }
    return $user;
}

// Date and time functions

/**
 * Format date to Philippine format (MM/DD/YYYY)
 */
function formatDate($date, $format = 'm/d/Y') {
    if (empty($date) || $date === '0000-00-00') return '-';
    return date($format, strtotime($date));
}

/**
 * Format date and time
 */
function formatDateTime($datetime, $format = 'm/d/Y h:i A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '-';
    return date($format, strtotime($datetime));
}

/**
 * Get current date in Philippine format
 */
function getCurrentDate($format = 'm/d/Y') {
    return date($format);
}

/**
 * Get current date in MySQL format
 */
function getMySQLDate() {
    return date('Y-m-d');
}

/**
 * Get current datetime in MySQL format
 */
function getMySQLDateTime() {
    return date('Y-m-d H:i:s');
}

// Currency functions

/**
 * Format amount to Philippine Peso
 */
function formatCurrency($amount) {
    return CURRENCY . ' ' . number_format((float)$amount, DECIMAL_PLACES, '.', ',');
}

/**
 * Parse currency string to float
 */
function parseCurrency($currencyString) {
    return (float)preg_replace('/[^\d.-]/', '', $currencyString);
}

// Philippine-specific functions

/**
 * Validate Philippine mobile number
 * Accepts formats: 09171234567, +639171234567, 0917-123-4567
 */
function validatePHMobileNumber($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^\d]/', '', $phone);
    
    // Check if it starts with 09 or 639
    if (preg_match('/^09\d{9}$/', $phone)) {
        return true;
    }
    if (preg_match('/^639\d{9}$/', $phone)) {
        return true;
    }
    
    return false;
}

/**
 * Format Philippine mobile number
 * Returns in format: 0917 123 4567
 */
function formatPHMobileNumber($phone) {
    $phone = preg_replace('/[^\d]/', '', $phone);
    
    // Convert to 09 format
    if (preg_match('/^639(\d{2})(\d{3})(\d{4})$/', $phone, $matches)) {
        return '09' . $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
    }
    if (preg_match('/^09(\d{2})(\d{3})(\d{4})$/', $phone, $matches)) {
        return '09' . $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
    }
    
    return $phone;
}

/**
 * Format Filipino name properly
 */
function formatFilipinoName($firstName, $middleName, $lastName) {
    $name = ucwords(strtolower($firstName));
    if (!empty($middleName)) {
        $name .= ' ' . ucwords(strtolower(substr($middleName, 0, 1))) . '.';
    }
    $name .= ' ' . ucwords(strtolower($lastName));
    return $name;
}

/**
 * Check if string contains common Filipino prefixes
 */
function hasFilipinoNamePrefix($name) {
    $prefixes = ['Del', 'De', 'De La', 'Van', 'Von', 'Mac', 'Mc'];
    foreach ($prefixes as $prefix) {
        if (stripos($name, $prefix) === 0) {
            return true;
        }
    }
    return false;
}

// File upload functions

/**
 * Upload file
 */
function uploadFile($file, $subfolder = '') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES) && !in_array($mime, ALLOWED_DOCUMENT_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    
    $uploadDir = UPLOAD_PATH;
    if (!empty($subfolder)) {
        $uploadDir .= '/' . $subfolder;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return ['success' => true, 'filename' => $filename, 'path' => $subfolder ? $subfolder . '/' . $filename : $filename];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    $fullPath = UPLOAD_PATH . '/' . $filepath;
    if (file_exists($fullPath)) {
        return @unlink($fullPath);
    }
    return false;
}

// Pagination functions

/**
 * Get pagination data
 */
function getPagination($totalItems, $currentPage, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
        'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
    ];
}

/**
 * Render pagination links
 */
function renderPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    if ($pagination['has_prev']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['prev_page'] . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $startPage = max(1, $pagination['current_page'] - 2);
    $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $pagination['total_pages']) {
        if ($endPage < $pagination['total_pages'] - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
    }
    
    // Next button
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['next_page'] . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

// Export functions

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if (!empty($headers)) {
        fputcsv($output, $headers);
    }

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber($prefix = 'INV', $separator = '-') {
    return $prefix . $separator . date('Ymd') . $separator . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate stock number
 */
function generateStockNumber($year) {
    $db = getDb();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM vehicles WHERE YEAR(date_acquired) = ?");
    $stmt->execute([$year]);
    $result = $stmt->fetch();
    
    $sequence = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
    return 'STK-' . $year . '-' . $sequence;
}

// Validation functions

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate positive number
 */
function validatePositiveNumber($number) {
    return is_numeric($number) && $number > 0;
}

/**
 * Get enum values from database
 */
function getEnumValues($table, $column) {
    $db = getDb();
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE '$column'");
    $stmt->execute();
    $row = $stmt->fetch();
    
    preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
    $values = explode("','", $matches[1]);
    return $values;
}

// Flash message functions

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = '';
        switch ($flash['type']) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            case 'info':
                $alertClass = 'alert-info';
                break;
        }
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

// URL functions

/**
 * Build URL with query parameters
 */
function buildUrl($path, $params = []) {
    $url = SITE_URL . '/' . ltrim($path, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * Redirect to URL
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Redirect back
 */
function redirectBack() {
    if (isset($_SERVER['HTTP_REFERER'])) {
        redirect($_SERVER['HTTP_REFERER']);
    } else {
        redirect(SITE_URL . '/dashboard.php');
    }
}

// Logging functions

/**
 * Log activity
 */
function logActivity($action, $details = '', $userId = null) {
    $userId = $userId ?? getCurrentUserId();
    if (!$userId) return;
    
    $db = getDb();
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt->execute([$userId, $action, $details, $ipAddress, $userAgent]);
}

/**
 * Log error
 */
function logError($message, $context = []) {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    error_log($logMessage . PHP_EOL, 3, LOGS_PATH . '/error.log');
}

// String functions

/**
 * Truncate string
 */
function truncate($string, $length = 50, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $suffix;
}

/**
 * Slugify string
 */
function slugify($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

// Status badge HTML

/**
 * Get status badge HTML
 */
function getStatusBadge($status, $type = 'default') {
    $badgeClasses = [
        'default' => [
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'available' => 'bg-success',
            'sold' => 'bg-primary',
            'reserved' => 'bg-warning',
            'pending' => 'bg-warning',
            'approved' => 'bg-info',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
            'blacklisted' => 'bg-danger',
            'scheduled' => 'bg-info',
            'in_progress' => 'bg-warning',
            'under_maintenance' => 'bg-warning',
            'brand_new' => 'bg-success',
            'used' => 'bg-info',
            'refurbished' => 'bg-warning'
        ]
    ];
    
    $class = $badgeClasses[$type][$status] ?? 'bg-secondary';
    return '<span class="badge ' . $class . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
}
?>
