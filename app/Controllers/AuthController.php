<?php
namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    public function showLogin()
    {
        if ($this->isAuth()) {
            $this->redirect('/');
        }
        
        $this->render('auth/login', [
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    public function showRegister()
    {
        if ($this->isAuth()) {
            $this->redirect('/');
        }
        
        $this->render('auth/register', [
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrfToken($csrfToken)) {
            $this->setFlash('error', 'Invalid CSRF token');
            $this->redirect('/login');
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $this->setFlash('error', 'Username and password are required');
            $this->redirect('/login');
        }
        
        $user = $this->userModel->findByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            $this->userModel->updateLastLogin($user['id']);
            
            $this->setFlash('success', 'Welcome back, ' . htmlspecialchars($user['username']) . '!');
            
            $redirectUrl = $_POST['redirect_url'] ?? '/';
            $this->redirect($redirectUrl);
        } else {
            $this->setFlash('error', 'Invalid username or password');
            $this->redirect('/login');
        }
    }
    
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
        }
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrfToken($csrfToken)) {
            $this->setFlash('error', 'Invalid CSRF token');
            $this->redirect('/register');
        }
        
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        } elseif ($this->userModel->findByUsername($username)) {
            $errors[] = 'Username already exists';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($this->userModel->findByEmail($email)) {
            $errors[] = 'Email already registered';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            $this->redirect('/register');
        }
        
        $userId = $this->userModel->createUser([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user'
        ]);
        
        if ($userId) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            $_SESSION['login_time'] = time();
            
            $this->setFlash('success', 'Registration successful! Welcome to Karaoke!');
            $this->redirect('/');
        } else {
            $this->setFlash('error', 'Registration failed. Please try again.');
            $this->redirect('/register');
        }
    }
    
    public function logout()
    {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        session_start();
        
        $this->setFlash('success', 'You have been logged out successfully.');
        $this->redirect('/login');
    }
}