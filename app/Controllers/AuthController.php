<?php
/**
 * Authentication Controller
 */

class AuthController {
    private $userModel;
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleLogin();
        }
        
        return $this->showLoginForm();
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleRegister();
        }
        
        return $this->showRegisterForm();
    }
    
    public function logout() {
        session_start();
        session_destroy();
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        header('Location: /login');
        exit;
    }
    
    private function handleLogin() {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        }
        
        if (empty($errors)) {
            $user = $this->userModel->authenticate($username, $password);
            
            if ($user) {
                $this->createUserSession($user, $remember);
                header('Location: /dashboard');
                exit;
            } else {
                $errors[] = "Invalid username or password";
            }
        }
        
        return $this->showLoginForm($errors);
    }
    
    private function handleRegister() {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $displayName = trim($_POST['display_name'] ?? '');
        
        $errors = [];
        
        // Validation
        if (empty($username)) {
            $errors[] = "Username is required";
        } elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters";
        } elseif (!$this->userModel->findByUsername($username)) {
            $errors[] = "Username already exists";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } elseif ($this->userModel->findByEmail($email)) {
            $errors[] = "Email already exists";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($displayName)) {
            $errors[] = "Display name is required";
        }
        
        if (empty($errors)) {
            try {
                $userId = $this->userModel->create([
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $this->userModel->createPasswordHash($password),
                    'display_name' => $displayName
                ]);
                
                $user = $this->userModel->find($userId);
                $this->createUserSession($user);
                
                header('Location: /dashboard');
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Registration failed: " . $e->getMessage();
            }
        }
        
        return $this->showRegisterForm($errors);
    }
    
    private function createUserSession($user, $remember = false) {
        session_start();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        if ($remember) {
            $rememberToken = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store remember token in database (you might want to add this field)
            // For now, we'll just extend session
            $_SESSION['remember_token'] = $rememberToken;
            $_SESSION['remember_expires'] = $expires;
        }
    }
    
    public function showLoginForm($errors = []) {
        $title = "Login";
        $errorsJson = json_encode($errors);
        
        include __DIR__ . '/../Views/auth/login.php';
    }
    
    public function showRegisterForm($errors = []) {
        $title = "Register";
        $errorsJson = json_encode($errors);
        
        include __DIR__ . '/../Views/auth/register.php';
    }
    
    public static function checkAuth() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Session timeout check
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 3600)) {
            session_destroy();
            header('Location: /login?timeout=1');
            exit;
        }
        
        $_SESSION['last_activity'] = time();
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'is_admin' => $_SESSION['is_admin']
        ];
    }
    
    public static function checkAdmin() {
        $user = self::checkAuth();
        
        if (!$user['is_admin']) {
            header('HTTP/1.1 403 Forbidden');
            include __DIR__ . '/../Views/errors/403.php';
            exit;
        }
        
        return $user;
    }
    
    public static function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'is_admin' => $_SESSION['is_admin']
        ];
    }
}