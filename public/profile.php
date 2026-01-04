<?php
/**
 * Profile Page
 * User profile settings
 */

require_once APP_ROOT . '/templates/header.php';

requireAuth();

$errors = [];
$success = false;

$userId = $auth->getCurrentUser()['id'];
$db = Database::getInstance();

if (isPost()) {
    if (!Security::verifyCSRFToken(post('csrf_token'))) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $action = post('action');
        
        if ($action === 'profile') {
            // Update profile
            $firstName = trim(post('first_name'));
            $lastName = trim(post('last_name'));
            $email = trim(post('email'));
            
            if (empty($firstName) || empty($lastName)) {
                $errors[] = 'Name fields are required.';
            } elseif (!Security::validateEmail($email)) {
                $errors[] = 'Invalid email address.';
            } else {
                $result = $db->execute(
                    "UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() WHERE id = ?",
                    [$firstName, $lastName, $email, $userId]
                );
                
                if ($result) {
                    $success = 'Profile updated successfully!';
                    // Update session data
                    $_SESSION['user']['first_name'] = $firstName;
                    $_SESSION['user']['last_name'] = $lastName;
                } else {
                    $errors[] = 'Failed to update profile.';
                }
            }
        } elseif ($action === 'password') {
            // Change password
            $currentPassword = post('current_password');
            $newPassword = post('new_password');
            $confirmPassword = post('confirm_password');
            
            $auth = new Auth();
            $result = $auth->changePassword($userId, $currentPassword, $newPassword);
            
            if ($result) {
                $success = 'Password changed successfully!';
            } else {
                $errors[] = 'Current password is incorrect or new password is too weak.';
            }
        }
    }
}

// Get current user data
$user = $db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
?>

<div class="page-header">
    <div class="container">
        <h1>My Profile</h1>
        <p>Manage your account settings</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="container">
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="container">
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Profile Settings -->
<section class="profile-section">
    <div class="container">
        <div class="profile-grid">
            <!-- Profile Form -->
            <div class="profile-card">
                <h2><i class="fa fa-user"></i> Profile Information</h2>
                <form method="POST" class="settings-form">
                    <?= Security::getCSRFTokenField() ?>
                    <input type="hidden" name="action" value="profile">
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                        <small class="form-text">Username cannot be changed</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?= htmlspecialchars($user['first_name']) ?>" 
                                   class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?= htmlspecialchars($user['last_name']) ?>" 
                                   class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email']) ?>" 
                               class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Account Type</label>
                        <input type="text" class="form-control" 
                               value="<?= ucfirst($user['role']) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Member Since</label>
                        <input type="text" class="form-control" 
                               value="<?= formatDate($user['created_at']) ?>" disabled>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
            
            <!-- Password Change -->
            <div class="profile-card">
                <h2><i class="fa fa-lock"></i> Change Password</h2>
                <form method="POST" class="settings-form">
                    <?= Security::getCSRFTokenField() ?>
                    <input type="hidden" name="action" value="password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-control" required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        <small class="form-text">Must be at least <?= PASSWORD_MIN_LENGTH ?> characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Accessibility Settings -->
<section class="accessibility-section">
    <div class="container">
        <h2 class="section-title">Accessibility Options</h2>
        <div class="a11y-settings">
            <div class="a11y-info">
                <p>Use the floating accessibility button in the bottom-right corner to adjust:</p>
                <ul>
                    <li><strong>Font Size:</strong> Make text larger or smaller</li>
                    <li><strong>High Contrast:</strong> Improve visibility</li>
                    <li><strong>Audio Feedback:</strong> Enable sounds for interactions</li>
                </ul>
            </div>
            <div class="a11y-demo">
                <button class="a11y-toggle" onclick="document.getElementById('a11yControls').classList.toggle('active')">
                    <i class="fa fa-universal-access"></i>
                    Open Accessibility Menu
                </button>
            </div>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
