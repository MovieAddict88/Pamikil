<?php
/**
 * Authentication Class
 * 
 * Handles user authentication, registration, password management,
 * and session security.
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Attempt to log in a user
     * 
     * @param string $username Username or email
     * @param string $password Plain text password
     * @return array|false User data on success, false on failure
     */
    public function login($username, $password) {
        // Sanitize input
        $username = $this->sanitize($username);
        
        // Find user by username or email
        $query = "SELECT id, username, email, password_hash, role, first_name, last_name, 
                         is_active, avatar_image, coins, total_xp, current_level
                  FROM users 
                  WHERE (username = ? OR email = ?) AND is_active = 1 
                  LIMIT 1";
        
        $user = $this->db->queryOne($query, [$username, $username]);
        
        if (!$user) {
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // Create session
        $this->createSession($user);
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Remove sensitive data before returning
        unset($user['password_hash']);
        
        return $user;
    }
    
    /**
     * Register a new user
     * 
     * @param array $userData User data array
     * @return int|false New user ID on success, false on failure
     */
    public function register($userData) {
        // Validate required fields
        $required = ['username', 'email', 'password', 'role', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                error_log("Registration failed: Missing required field - {$field}");
                return false;
            }
        }
        
        // Validate email format
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Registration failed: Invalid email format");
            return false;
        }
        
        // Validate password strength
        if (strlen($userData['password']) < PASSWORD_MIN_LENGTH) {
            error_log("Registration failed: Password too short");
            return false;
        }
        
        // Sanitize data
        $userData['username'] = $this->sanitize($userData['username']);
        $userData['email'] = $this->sanitize($userData['email']);
        $userData['first_name'] = $this->sanitize($userData['first_name']);
        $userData['last_name'] = $this->sanitize($userData['last_name']);
        
        // Check if username exists
        if ($this->userExists('username', $userData['username'])) {
            error_log("Registration failed: Username already exists");
            return false;
        }
        
        // Check if email exists
        if ($this->userExists('email', $userData['email'])) {
            error_log("Registration failed: Email already exists");
            return false;
        }
        
        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users (username, email, password_hash, role, first_name, last_name, 
                                     parent_id, date_of_birth, is_active, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $params = [
            $userData['username'],
            $userData['email'],
            $passwordHash,
            $userData['role'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['parent_id'] ?? null,
            $userData['date_of_birth'] ?? null
        ];
        
        $result = $this->db->execute($query, $params);
        
        if ($result) {
            $userId = $this->db->lastInsertId();
            
            // Initialize avatar settings for students
            if ($userData['role'] === 'student') {
                $this->initializeAvatarSettings($userId);
            }
            
            return $userId;
        }
        
        return false;
    }
    
    /**
     * Log out the current user
     * 
     * @return bool True on success
     */
    public function logout() {
        // Update session in database
        if (isset($_SESSION['user_id'])) {
            $this->endSession($_SESSION['user_id']);
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        return true;
    }
    
    /**
     * Get the currently logged-in user
     * 
     * @return array|false User data or false if not logged in
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user'];
    }
    
    /**
     * Check if a user is logged in
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if current user has a specific role
     * 
     * @param string $role Role to check
     * @return bool True if user has the role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user']['role'] === $role;
    }
    
    /**
     * Check if current user is an admin
     * 
     * @return bool True if admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Check if current user is a parent
     * 
     * @return bool True if parent
     */
    public function isParent() {
        return $this->hasRole('parent');
    }
    
    /**
     * Check if current user is a student
     * 
     * @return bool True if student
     */
    public function isStudent() {
        return $this->hasRole('student');
    }
    
    /**
     * Verify that the current user owns a specific resource
     * 
     * @param string $resourceType Type of resource (e.g., 'user', 'activity_progress')
     * @param int $resourceId ID of the resource
     * @return bool True if user owns the resource or is admin
     */
    public function ownsResource($resourceType, $resourceId) {
        if ($this->isAdmin()) {
            return true;
        }
        
        $userId = $_SESSION['user_id'];
        
        switch ($resourceType) {
            case 'user':
                return $userId == $resourceId;
                
            case 'child_user':
                // Check if the user is a parent and the resource is their child
                $query = "SELECT id FROM users WHERE id = ? AND parent_id = ?";
                $result = $this->db->queryOne($query, [$resourceId, $userId]);
                return $result !== false;
                
            case 'activity_progress':
                $query = "SELECT user_id FROM user_activity_progress WHERE id = ?";
                $result = $this->db->queryOne($query, [$resourceId]);
                return $result && $result['user_id'] == $userId;
                
            default:
                return false;
        }
    }
    
    /**
     * Change user password
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool True on success
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current password hash
        $query = "SELECT password_hash FROM users WHERE id = ? LIMIT 1";
        $user = $this->db->queryOne($query, [$userId]);
        
        if (!$user) {
            return false;
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }
        
        // Validate new password
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $query = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->execute($query, [$newPasswordHash, $userId]);
        
        return $result !== false;
    }
    
    /**
     * Request a password reset
     * 
     * @param string $email User email
     * @return string|false Reset token on success
     */
    public function requestPasswordReset($email) {
        $email = $this->sanitize($email);
        
        // Find user
        $query = "SELECT id, username, first_name FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
        $user = $this->db->queryOne($query, [$email]);
        
        if (!$user) {
            return false;
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing tokens for this user
        $this->db->execute("DELETE FROM password_resets WHERE user_id = ?", [$user['id']]);
        
        // Insert new token
        $query = "INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)";
        $result = $this->db->execute($query, [$user['id'], $token, $expires]);
        
        if ($result) {
            // In production, send email here
            // For now, return the token (for testing)
            return $token;
        }
        
        return false;
    }
    
    /**
     * Reset password using token
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool True on success
     */
    public function resetPassword($token, $newPassword) {
        // Find valid token
        $query = "SELECT user_id, expires_at FROM password_resets 
                  WHERE reset_token = ? AND used_at IS NULL AND expires_at > NOW() 
                  LIMIT 1";
        $reset = $this->db->queryOne($query, [$token]);
        
        if (!$reset) {
            return false;
        }
        
        // Validate new password
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        
        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // Update password
            $this->db->execute(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                [$passwordHash, $reset['user_id']]
            );
            
            // Mark token as used
            $this->db->execute(
                "UPDATE password_resets SET used_at = NOW() WHERE reset_token = ?",
                [$token]
            );
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
    
    /**
     * Create a user session
     * 
     * @param array $user User data
     */
    private function createSession($user) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Store session in database
        $sessionToken = session_id();
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $query = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, login_time)
                  VALUES (?, ?, ?, ?, NOW())";
        
        $this->db->execute($query, [$user['id'], $sessionToken, $ipAddress, $userAgent]);
    }
    
    /**
     * End a user session
     * 
     * @param int $userId User ID
     */
    private function endSession($userId) {
        $sessionToken = session_id();
        
        $query = "UPDATE user_sessions 
                  SET logout_time = NOW(), is_active = 0 
                  WHERE user_id = ? AND session_token = ?";
        
        $this->db->execute($query, [$userId, $sessionToken]);
    }
    
    /**
     * Update user's last login time
     * 
     * @param int $userId User ID
     */
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->db->execute($query, [$userId]);
    }
    
    /**
     * Check if a user exists by field
     * 
     * @param string $field Field name (username or email)
     * @param string $value Field value
     * @return bool True if user exists
     */
    private function userExists($field, $value) {
        $query = "SELECT id FROM users WHERE {$field} = ? LIMIT 1";
        $result = $this->db->queryOne($query, [$value]);
        return $result !== false;
    }
    
    /**
     * Initialize avatar settings for new student
     * 
     * @param int $userId User ID
     */
    private function initializeAvatarSettings($userId) {
        // Get default avatar items
        $defaultHair = $this->db->queryOne(
            "SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'hair' LIMIT 1"
        );
        $defaultFace = $this->db->queryOne(
            "SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'face' LIMIT 1"
        );
        $defaultClothing = $this->db->queryOne(
            "SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'clothing' LIMIT 1"
        );
        $defaultBackground = $this->db->queryOne(
            "SELECT id FROM avatar_items WHERE cost = 0 AND item_type = 'background' LIMIT 1"
        );
        
        // Create avatar settings
        $query = "INSERT INTO user_avatar_settings (user_id, hair_id, face_id, clothing_id, background_id)
                  VALUES (?, ?, ?, ?, ?)";
        $this->db->execute($query, [
            $userId,
            $defaultHair['id'] ?? null,
            $defaultFace['id'] ?? null,
            $defaultClothing['id'] ?? null,
            $defaultBackground['id'] ?? null
        ]);
        
        // Give user ownership of default items
        $defaultItems = [$defaultHair, $defaultFace, $defaultClothing, $defaultBackground];
        foreach ($defaultItems as $item) {
            if ($item) {
                $this->db->execute(
                    "INSERT IGNORE INTO user_owned_avatar_items (user_id, item_id) VALUES (?, ?)",
                    [$userId, $item['id']]
                );
            }
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Sanitize input string
     * 
     * @param string $input Input string
     * @return string Sanitized string
     */
    private function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
