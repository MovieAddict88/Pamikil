<?php
/**
 * Login Page
 */

require_once APP_ROOT . '/templates/header.php';

$errors = [];
$success = false;

if (isPost()) {
    // Verify CSRF token
    if (!Security::verifyCSRFToken(post('csrf_token'))) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $username = trim(post('username'));
        $password = post('password');
        $remember = post('remember') === '1';
        
        if (empty($username) || empty($password)) {
            $errors[] = 'Please enter both username and password.';
        } else {
            $auth = new Auth();
            $user = $auth->login($username, $password);
            
            if ($user) {
                // Set remember me cookie
                if ($remember) {
                    $token = Security::generateToken();
                    setcookie('remember_token', $token, time() + REMEMBER_ME_EXPIRY, '/', '', false, true);
                    // Store token in database (simplified - in production, use secure token management)
                }
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'student':
                        redirect(SITE_URL . '/activities');
                        break;
                    case 'parent':
                        redirect(SITE_URL . '/dashboard');
                        break;
                    case 'admin':
                        redirect(SITE_URL . '/admin');
                        break;
                }
            } else {
                $errors[] = 'Invalid username or password.';
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back!</h1>
            <p>Log in to continue learning</p>
        </div>
        
        <?php if (hasFlash()): ?>
            <?php $flash = getFlash(); ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= SITE_URL ?>/login" class="auth-form">
            <?= Security::getCSRFTokenField() ?>
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars(post('username')) ?>" 
                       required autofocus
                       placeholder="Enter your username or email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       required placeholder="Enter your password">
            </div>
            
            <div class="form-group form-check">
                <input type="checkbox" id="remember" name="remember" value="1" 
                       class="form-check-input" <?= post('remember') === '1' ? 'checked' : '' ?>>
                <label for="remember" class="form-check-label">Remember me</label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p><a href="<?= SITE_URL ?>/forgot-password">Forgot password?</a></p>
            <p>Don't have an account? <a href="<?= SITE_URL ?>/register">Sign up</a></p>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
