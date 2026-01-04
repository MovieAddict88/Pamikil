<?php
/**
 * Create User
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireRole('admin');

$pageTitle = 'Add User';
$db = getDb();
$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
        redirect($_SERVER['PHP_SELF']);
        exit;
    }
    
    $formData = [
        'username' => sanitize($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'role' => sanitize($_POST['role'] ?? ''),
        'location_id' => intval($_POST['location_id'] ?? 0) ?: null
    ];
    
    $required = ['username', 'password', 'full_name', 'email', 'phone', 'role'];
    foreach ($required as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }
    
    if (!validateEmail($formData['email'])) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (!validatePHMobileNumber($formData['phone'])) {
        $errors['phone'] = 'Invalid Philippine mobile number';
    }
    
    if (strlen($formData['password']) < MIN_PASSWORD_LENGTH) {
        $errors['password'] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check if username exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$formData['username']]);
    if ($stmt->fetch()) {
        $errors['username'] = 'Username already exists';
    }
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$formData['email']]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Email already exists';
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = hashPassword($formData['password']);
            
            $sql = "INSERT INTO users (username, password, full_name, email, phone, role, location_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $formData['username'], $hashedPassword, $formData['full_name'],
                $formData['email'], $formData['phone'], $formData['role'],
                $formData['location_id']
            ]);
            
            logActivity('user_create', 'User created: ' . $formData['username']);
            setFlashMessage('success', 'User added successfully');
            redirect(SITE_URL . '/users/index.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
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
    <div class="card-header">Add New User</div>
    <div class="card-body">
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-control" name="username" 
                           value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="full_name" 
                           value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" 
                           value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mobile Number *</label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select class="form-select" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?php echo isset($formData['role']) && $formData['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="manager" <?php echo isset($formData['role']) && $formData['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="staff" <?php echo isset($formData['role']) && $formData['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <select class="form-select" name="location_id">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>" 
                                    <?php echo isset($formData['location_id']) && $formData['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" class="form-control" name="password" required>
                    <small class="text-muted">Minimum <?php echo MIN_PASSWORD_LENGTH; ?> characters</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add User</button>
                <a href="<?php echo SITE_URL; ?>/users/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
