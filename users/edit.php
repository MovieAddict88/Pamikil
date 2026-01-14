<?php
/**
 * User Edit
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireRole('admin');

$pageTitle = 'Edit User';
$db = getDb();
$errors = [];
$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    setFlashMessage('error', 'Invalid user ID');
    redirect(SITE_URL . '/users/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
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
    
    $updateData = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'role' => sanitize($_POST['role'] ?? ''),
        'location_id' => intval($_POST['location_id'] ?? 0) ?: null,
        'status' => sanitize($_POST['status'] ?? 'active')
    ];
    
    $required = ['full_name', 'email', 'phone', 'role'];
    foreach ($required as $field) {
        if (empty($updateData[$field])) {
            $errors[$field] = 'Required';
        }
    }
    
    if (!validateEmail($updateData['email'])) {
        $errors['email'] = 'Invalid email';
    }
    
    if (!validatePHMobileNumber($updateData['phone'])) {
        $errors['phone'] = 'Invalid mobile number';
    }
    
    // Check if email already exists (excluding this user)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$updateData['email'], $userId]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Email already exists';
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET 
                full_name = ?, email = ?, phone = ?, role = ?,
                location_id = ?, status = ?, updated_at = NOW()
                WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge(array_values($updateData), [$userId]));
            
            logActivity('user_update', 'User updated: ' . $user['username']);
            setFlashMessage('success', 'User updated');
            redirect(SITE_URL . '/users/index.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = $e->getMessage();
        }
    }
}

$stmt = $db->query("SELECT id, name FROM locations WHERE status = 'active' ORDER BY name");
$locations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/users/index.php" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back to Users
</a>

<div class="card">
    <div class="card-header">Edit User: <?php echo htmlspecialchars($user['username']); ?></div>
    <div class="card-body">
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small class="text-muted">Username cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mobile *</label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select class="form-select" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <select class="form-select" name="location_id">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>" 
                                    <?php echo $user['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update User</button>
                <a href="<?php echo SITE_URL; ?>/users/index.php" class="btn btn-secondary">Cancel</a>
                <a href="<?php echo SITE_URL; ?>/users/reset-password.php?id=<?php echo $userId; ?>" class="btn btn-info">
                    <i class="bi bi-key"></i> Reset Password
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
