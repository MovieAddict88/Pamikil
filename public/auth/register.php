<?php
/**
 * Registration Page
 */

require_once APP_ROOT . '/templates/header.php';

$errors = [];

// Get available parent accounts for student registration
$db = Database::getInstance();
$parents = $db->query("SELECT id, username, first_name, last_name FROM users WHERE role = 'parent' AND is_active = 1");

if (isPost()) {
    // Verify CSRF token
    if (!Security::verifyCSRFToken(post('csrf_token'))) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $auth = new Auth();
        $userData = [
            'username' => trim(post('username')),
            'email' => trim(post('email')),
            'password' => post('password'),
            'confirm_password' => post('confirm_password'),
            'role' => post('role'),
            'first_name' => trim(post('first_name')),
            'last_name' => trim(post('last_name')),
            'parent_id' => post('parent_id') ?: null,
            'date_of_birth' => !empty(post('date_of_birth')) ? post('date_of_birth') : null
        ];
        
        // Validate
        if (empty($userData['username']) || strlen($userData['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        
        if (empty($userData['email'])) {
            $errors[] = 'Email is required.';
        } elseif (!Security::validateEmail($userData['email'])) {
            $errors[] = 'Invalid email format.';
        }
        
        if (empty($userData['password']) || strlen($userData['password']) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($userData['password'] !== $userData['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!in_array($userData['role'], ['student', 'parent'])) {
            $errors[] = 'Invalid role selected.';
        }
        
        // For students, require parent or date of birth
        if ($userData['role'] === 'student' && empty($userData['parent_id']) && empty($userData['date_of_birth'])) {
            $errors[] = 'Please link to a parent account or enter date of birth.';
        }
        
        if (empty($errors)) {
            // Attempt registration
            $userId = $auth->register($userData);
            
            if ($userId) {
                setFlash('success', 'Account created successfully! You can now log in.');
                redirect(SITE_URL . '/login');
            } else {
                $errors[] = 'Unable to create account. Username or email may already be in use.';
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card auth-card-wide">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join and start learning!</p>
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
        
        <form method="POST" action="<?= SITE_URL ?>/register" class="auth-form">
            <?= Security::getCSRFTokenField() ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?= htmlspecialchars(post('first_name')) ?>" 
                           required placeholder="First name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?= htmlspecialchars(post('last_name')) ?>" 
                           required placeholder="Last name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars(post('username')) ?>" 
                       required placeholder="Choose a username"
                       pattern="[a-zA-Z0-9_]{3,}"
                       title="Username must be at least 3 characters (letters, numbers, underscores only)">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars(post('email')) ?>" 
                       required placeholder="Your email address">
            </div>
            
            <div class="form-group">
                <label for="role">Account Type</label>
                <select id="role" name="role" required class="form-control">
                    <option value="">Select account type</option>
                    <option value="student" <?= post('role') === 'student' ? 'selected' : '' ?>>Student (Child)</option>
                    <option value="parent" <?= post('role') === 'parent' ? 'selected' : '' ?>>Parent</option>
                </select>
            </div>
            
            <div id="student-fields" style="display: <?= post('role') === 'student' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label for="parent_id">Link to Parent Account (Optional)</label>
                    <select id="parent_id" name="parent_id" class="form-control">
                        <option value="">No parent account</option>
                        <?php foreach ($parents as $parent): ?>
                            <option value="<?= $parent['id'] ?>" 
                                    <?= post('parent_id') == $parent['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']) ?> 
                                (<?= htmlspecialchars($parent['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Ask your parent for their account to link your account</small>
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" 
                           value="<?= htmlspecialchars(post('date_of_birth')) ?>"
                           class="form-control">
                    <small class="form-text text-muted">Used to show age-appropriate content</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           required placeholder="Choose a password"
                           minlength="<?= PASSWORD_MIN_LENGTH ?>">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required placeholder="Confirm password"
                           minlength="<?= PASSWORD_MIN_LENGTH ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="<?= SITE_URL ?>/login">Log in</a></p>
        </div>
    </div>
</div>

<script>
document.getElementById('role').addEventListener('change', function() {
    const studentFields = document.getElementById('student-fields');
    studentFields.style.display = this.value === 'student' ? 'block' : 'none';
});

// Show/hide on page load
if (document.getElementById('role').value === 'student') {
    document.getElementById('student-fields').style.display = 'block';
}
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
