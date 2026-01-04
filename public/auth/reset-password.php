<?php
/**
 * Reset Password - Complete Password Reset
 */

require_once APP_ROOT . '/templates/header.php';

$token = get('token');
$errors = [];
$success = false;

if (!$token) {
    setFlash('error', 'Invalid reset token');
    redirect(SITE_URL . '/forgot-password');
}

if (isPost()) {
    if (!Security::verifyCSRFToken(post('csrf_token'))) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $password = post('password');
        $confirmPassword = post('confirm_password');
        
        if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        } else {
            $auth = new Auth();
            $result = $auth->resetPassword($token, $password);
            
            if ($result) {
                setFlash('success', 'Password reset successfully! You can now log in.');
                redirect(SITE_URL . '/login');
            } else {
                $errors[] = 'Invalid or expired reset token. Please request a new reset link.';
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Create New Password</h1>
            <p>Enter your new password below</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= SITE_URL ?>/reset-password?token=<?= htmlspecialchars($token) ?>" 
              class="auth-form">
            <?= Security::getCSRFTokenField() ?>
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" 
                       required placeholder="Enter new password"
                       minlength="<?= PASSWORD_MIN_LENGTH ?>"
                       data-confirm="confirm_password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       required placeholder="Confirm new password"
                       minlength="<?= PASSWORD_MIN_LENGTH ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
