<?php $currentPage = 'login'; ?>

<div class="form-container">
    <div class="form-title">
        <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
        <p>Welcome back to Karaoke!</p>
    </div>

    <?php if ($flash = $this->getFlash()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $this->generateCsrfToken(); ?>">
        
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" 
                   class="form-control" 
                   id="username" 
                   name="username" 
                   required 
                   autofocus
                   placeholder="Enter your username">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" 
                   class="form-control" 
                   id="password" 
                   name="password" 
                   required
                   placeholder="Enter your password">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </div>

        <div class="form-group text-center">
            <label>
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>
        </div>
    </form>

    <div class="form-footer">
        <p>Don't have an account?</p>
        <a href="/register" class="btn btn-secondary">
            <i class="fas fa-user-plus"></i> Register
        </a>
    </div>
</div>