<?php
session_start();
define('APP_ACCESS', true);

require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if (!getSetting('enable_registration', 1)) {
    redirect('index.php');
}

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            try {
                $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword]);
                
                $success = 'Account created successfully! You can now <a href="login.php">login</a>.';
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
include 'includes/header.php';
?>

<div class="auth-container">
    <h1>Create Account</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required autofocus 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required minlength="6">
        </div>
        
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required minlength="6">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px; color: var(--text-secondary);">
        Already have an account? <a href="login.php" style="color: var(--primary-color);">Login</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
