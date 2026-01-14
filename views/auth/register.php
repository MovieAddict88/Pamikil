<?php $currentPage = 'register'; ?>

<div class="form-container">
    <div class="form-title">
        <h2><i class="fas fa-user-plus"></i> Register</h2>
        <p>Create your Karaoke account</p>
    </div>

    <?php if ($flash = $this->getFlash()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($flash['message']); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/register" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $this->generateCsrfToken(); ?>">
        
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" 
                   class="form-control" 
                   id="username" 
                   name="username" 
                   required 
                   minlength="3"
                   maxlength="50"
                   pattern="[a-zA-Z0-9_]+"
                   placeholder="Choose a username (3-50 chars, letters, numbers, underscores)">
            <small class="form-text">Username must be 3-50 characters and can only contain letters, numbers, and underscores.</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" 
                   class="form-control" 
                   id="email" 
                   name="email" 
                   required
                   placeholder="Enter your email address">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" 
                   class="form-control" 
                   id="password" 
                   name="password" 
                   required 
                   minlength="8"
                   placeholder="Create a password (min 8 chars)">
            <small class="form-text">Password must be at least 8 characters and contain uppercase, lowercase, and numbers.</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input type="password" 
                   class="form-control" 
                   id="confirm_password" 
                   name="confirm_password" 
                   required
                   placeholder="Confirm your password">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </div>

        <div class="form-group text-center">
            <label>
                <input type="checkbox" name="terms" value="1" required>
                I agree to the <a href="/terms">Terms of Service</a>
            </label>
        </div>
    </form>

    <div class="form-footer">
        <p>Already have an account?</p>
        <a href="/login" class="btn btn-secondary">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    </div>
</div>