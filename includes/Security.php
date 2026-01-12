<?php
/**
 * Security Class
 * 
 * Provides security utilities including CSRF protection,
 * input sanitization, and XSS prevention.
 */

class Security {
    
    /**
     * Generate CSRF token
     * 
     * @return string The generated token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        // Regenerate if token is expired
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if valid
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Check if token is expired
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
            return false;
        }
        
        // Verify token
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token HTML input field
     * 
     * @return string HTML input element
     */
    public static function getCSRFTokenField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Sanitize input data
     * 
     * @param mixed $input Input to sanitize
     * @return mixed Sanitized output
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([__CLASS__, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize input for database (only use if prepared statements can't be used)
     * Note: Prefer prepared statements
     * 
     * @param string $input Input to escape
     * @return string Escaped output
     */
    public static function escapeDB($input) {
        $db = Database::getInstance();
        return $db->escape($input);
    }
    
    /**
     * Validate and sanitize email
     * 
     * @param string $email Email address
     * @return string|false Validated email or false
     */
    public static function validateEmail($email) {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        
        return false;
    }
    
    /**
     * Validate URL
     * 
     * @param string $url URL to validate
     * @return string|false Validated URL or false
     */
    public static function validateURL($url) {
        $url = trim($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        return false;
    }
    
    /**
     * Validate integer
     * 
     * @param mixed $value Value to validate
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int|false Validated integer or false
     */
    public static function validateInt($value, $min = null, $max = null) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            return false;
        }
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return $value;
    }
    
    /**
     * Validate string length
     * 
     * @param string $value String to validate
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return string|false Validated string or false
     */
    public static function validateLength($value, $min = 0, $max = null) {
        $length = mb_strlen($value, 'UTF-8');
        
        if ($length < $min) {
            return false;
        }
        
        if ($max !== null && $length > $max) {
            return false;
        }
        
        return $value;
    }
    
    /**
     * Hash a password
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify a password
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if valid
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random token
     * 
     * @param int $length Token length
     * @return string Random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate random string
     * 
     * @param int $length String length
     * @return string Random string
     */
    public static function generateRandomString($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool True if AJAX request
     */
    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is HTTPS
     * 
     * @return bool True if HTTPS
     */
    public static function isHTTPS() {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    public static function getIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Get user agent
     * 
     * @return string User agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Prevent clickjacking
     * 
     * @param string $policy X-Frame-Options header value
     */
    public static function preventClickjacking($policy = 'DENY') {
        header("X-Frame-Options: {$policy}");
    }
    
    /**
     * Set content security policy
     * 
     * @param string $policy CSP header value
     */
    public static function setCSP($policy) {
        header("Content-Security-Policy: {$policy}");
    }
    
    /**
     * Set XSS protection
     */
    public static function setXSSProtection() {
        header("X-XSS-Protection: 1; mode=block");
    }
    
    /**
     * Set no-sniff
     */
    public static function setNoSniff() {
        header("X-Content-Type-Options: nosniff");
    }
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        self::preventClickjacking();
        self::setXSSProtection();
        self::setNoSniff();
        
        if (self::isHTTPS()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return array|false Validated file data or false
     */
    public static function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . $file['error']);
            return false;
        }
        
        // Check if file was actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Check file size
        $maxSize = $maxSize ?? MAX_UPLOAD_SIZE;
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Check file type
        $allowedTypes = $allowedTypes ?? array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_AUDIO_TYPES);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            error_log("Invalid file type: " . $mimeType);
            return false;
        }
        
        return [
            'tmp_name' => $file['tmp_name'],
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $mimeType,
            'extension' => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
        ];
    }
    
    /**
     * Sanitize filename
     * 
     * @param string $filename Filename
     * @return string Sanitized filename
     */
    public static function sanitizeFilename($filename) {
        // Remove any directory paths
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Remove leading/trailing underscores
        $filename = trim($filename, '_');
        
        return $filename;
    }
}
