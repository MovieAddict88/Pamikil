<?php
session_start();
define('APP_ACCESS', true);

require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            redirect($redirect);
        } else {
            $error = 'Invalid username or password';
        }
    }
}

$pageTitle = 'Login';
include 'includes/header.php';
?>

<div class="auth-container">
    <h1>Login</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Username or Email</label>
            <input type="text" name="username" required autofocus>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
    </form>
    
    <?php if (getSetting('enable_registration', 1)): ?>
        <p style="text-align: center; margin-top: 20px; color: var(--text-secondary);">
            Don't have an account? <a href="register.php" style="color: var(--primary-color);">Register</a>
        </p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
