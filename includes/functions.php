<?php
defined('APP_ACCESS') or die('Direct access not permitted');

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function getSetting($key, $default = '') {
    static $settings = null;
    
    if ($settings === null) {
        $db = getDB();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings[$key] ?? $default;
}

function updateSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' days ago';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' months ago';
    } else {
        return floor($diff / 31536000) . ' years ago';
    }
}

function formatRuntime($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . ($mins > 0 ? $mins . 'min' : '');
}

function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function logMovieView($movieId, $userId = null) {
    $db = getDB();
    
    $stmt = $db->prepare("INSERT INTO views_log (movie_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $movieId,
        $userId,
        getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $stmt = $db->prepare("UPDATE movies SET views = views + 1 WHERE id = ?");
    $stmt->execute([$movieId]);
}

function uploadFile($file, $destination = 'uploads/posters/', $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }
    
    if ($file['size'] > 10485760) {
        return ['success' => false, 'error' => 'File too large (max 10MB)'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . $filename;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    return ['success' => true, 'path' => $filepath, 'filename' => $filename];
}

function downloadImage($url, $destination) {
    if (empty($url)) {
        return false;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data !== false) {
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        file_put_contents($destination, $data);
        return true;
    }
    
    return false;
}

function pagination($total, $perPage, $currentPage, $baseUrl) {
    $totalPages = ceil($total / $perPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="page-link">« Previous</a>';
    }
    
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '&page=1" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="page-dots">...</span>';
        }
        $html .= '<a href="' . $baseUrl . '&page=' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
    }
    
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="page-link">Next »</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php?redirect=' . urlencode(getCurrentUrl()));
    }
}

function getUser($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
