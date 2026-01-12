<?php
/**
 * Forgot Password - Request Password Reset
 */

require_once APP_ROOT . '/templates/header.php';

$errors = [];
$success = false;

if (isPost()) {
    if (!Security::verifyCSRFToken(post('csrf_token'))) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $email = trim(post('email'));
        
        if (empty($email)) {
            $errors[] = 'Please enter your email address.';
        } elseif (!Security::validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $auth = new Auth();
            $token = $auth->requestPasswordReset($email);
            
            if ($token) {
                $success = true;
                // In production, email the reset link
                // For now, show the token for testing
                $resetLink = SITE_URL . '/reset-password?token=' . $token;
            } else {
                // Don't reveal if email exists or not
                $success = true;
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Reset Password</h1>
            <p>Enter your email to reset your password</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Check your email!</strong>
                <p>We've sent you instructions to reset your password.</p>
            </div>
            <div class="text-center mt-4">
                <a href="<?= SITE_URL ?>/login" class="btn btn-primary">Back to Login</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($resetLink)): ?>
                <div class="alert alert-info">
                    <strong>Development Mode:</strong>
                    <a href="<?= $resetLink ?>"><?= $resetLink ?></a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= SITE_URL ?>/forgot-password" class="auth-form">
                <?= Security::getCSRFTokenField() ?>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars(post('email')) ?>" 
                           required placeholder="Enter your email">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </div>
            </form>
            
            <div class="auth-footer">
                <p>Remember your password? <a href="<?= SITE_URL ?>/login">Log in</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
