<?php
/**
 * Authentication Functions
 * Car Management System - Pamikil
 */

/**
 * Login user
 */
function login($username, $password, $remember = false) {
    $db = getDb();
    
    $stmt = $db->prepare("SELECT id, username, password, full_name, email, phone, role, location_id, status FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account has been deactivated. Please contact administrator.'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['location_id'] = $user['location_id'];
    $_SESSION['last_activity'] = time();
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Handle remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + REMEMBER_ME_EXPIRY;
        
        $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
        $stmt->execute([$user['id'], hash('sha256', $token), $expiry]);
        
        setcookie('remember_token', $token, $expiry, '/', '', false, true);
    }
    
    logActivity('login', 'User logged in: ' . $user['username']);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logout() {
    $userId = getCurrentUserId();
    
    // Clear remember me token
    if (isset($_COOKIE['remember_token'])) {
        $token = hash('sha256', $_COOKIE['remember_token']);
        $db = getDb();
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$token]);
        
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Clear session
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    
    if ($userId) {
        logActivity('logout', 'User logged out: ID ' . $userId);
    }
}

/**
 * Check remember me token
 */
function checkRememberMe() {
    if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
        $token = hash('sha256', $_COOKIE['remember_token']);
        $db = getDb();
        
        $stmt = $db->prepare("
            SELECT rt.user_id, u.id, u.username, u.password, u.full_name, u.email, u.phone, u.role, u.location_id, u.status
            FROM remember_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Set session variables
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['full_name'] = $result['full_name'];
            $_SESSION['email'] = $result['email'];
            $_SESSION['phone'] = $result['phone'];
            $_SESSION['role'] = $result['role'];
            $_SESSION['location_id'] = $result['location_id'];
            $_SESSION['last_activity'] = time();
            
            logActivity('login', 'User logged in via remember me: ' . $result['username']);
            
            return true;
        }
    }
    return false;
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
    }
    return true;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Change user password
 */
function changePassword($userId, $currentPassword, $newPassword) {
    $db = getDb();
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    $hashedPassword = hashPassword($newPassword);
    
    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    logActivity('password_change', 'Password changed for user ID: ' . $userId);
    
    return ['success' => true, 'message' => 'Password changed successfully'];
}

/**
 * Check password strength
 */
function checkPasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Reset user password (admin function)
 */
function resetUserPassword($userId) {
    $db = getDb();
    
    $newPassword = generateRandomPassword();
    $hashedPassword = hashPassword($newPassword);
    
    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    logActivity('password_reset', 'Password reset for user: ' . $user['username']);
    
    return [
        'success' => true,
        'message' => 'Password has been reset',
        'new_password' => $newPassword,
        'user' => $user
    ];
}
?>
