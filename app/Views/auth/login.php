<?php 
$title = "Login";
$bodyClass = "auth-page";
ob_start();
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-microphone-alt"></i>
                <h1>Karaoke App</h1>
            </div>
            <h2>Welcome Back</h2>
            <p>Sign in to your account to continue</p>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="auth-errors">
            <?php foreach ($errors as $error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-container">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= htmlspecialchars(old('username')) ?>"
                           required 
                           autocomplete="username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-container">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark"></span>
                    Remember me
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? 
                <a href="/register">Sign up here</a>
            </p>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>