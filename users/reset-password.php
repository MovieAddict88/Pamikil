<?php
/**
 * Reset User Password
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireRole('admin');

$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    setFlashMessage('error', 'Invalid user ID');
    redirect(SITE_URL . '/users/index.php');
    exit;
}

$db = getDb();

$stmt = $db->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('error', 'User not found');
    redirect(SITE_URL . '/users/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
        redirect($_SERVER['PHP_SELF'] . '?id=' . $userId);
        exit;
    }
    
    $newPassword = generateRandomPassword();
    $hashedPassword = hashPassword($newPassword);
    
    try {
        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        logActivity('password_reset', 'Password reset for user: ' . $user['username']);
        setFlashMessage('success', 'Password reset successfully');
        
    } catch (PDOException $e) {
        logError('Password reset failed', ['error' => $e->getMessage()]);
        setFlashMessage('error', 'Failed to reset password');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/users/index.php" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back to Users
</a>

<div class="card">
    <div class="card-header">Reset Password: <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['username']); ?>)</div>
    <div class="card-body">
        <?php if (isset($newPassword)): ?>
            <div class="alert alert-success">
                <h5>Password Reset Successful!</h5>
                <p><strong>New Password:</strong> <code style="font-size: 18px; padding: 5px 10px;"><?php echo htmlspecialchars($newPassword); ?></code></p>
                <p class="text-muted">Please share this password with the user securely. They can change it after logging in.</p>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <p>Are you sure you want to reset the password for <strong><?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['username']); ?>)</strong>?</p>
                
                <p class="text-warning">This action will generate a new random password that cannot be recovered. The user will need to change it after logging in.</p>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key"></i> Reset Password
                    </button>
                    <a href="<?php echo SITE_URL; ?>/users/index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
