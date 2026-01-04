<?php
/**
 * Create Customer
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Add Customer';
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
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'middle_name' => sanitize($_POST['middle_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'city' => sanitize($_POST['city'] ?? ''),
        'province' => sanitize($_POST['province'] ?? ''),
        'zip_code' => sanitize($_POST['zip_code'] ?? ''),
        'identification_type' => sanitize($_POST['identification_type'] ?? ''),
        'identification_number' => sanitize($_POST['identification_number'] ?? ''),
        'notes' => sanitize($_POST['notes'] ?? '')
    ];
    
    $required = ['first_name', 'last_name', 'phone', 'address', 'city', 'province'];
    foreach ($required as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }
    
    if (!empty($formData['email']) && !validateEmail($formData['email'])) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (!validatePHMobileNumber($formData['phone'])) {
        $errors['phone'] = 'Invalid Philippine mobile number format';
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO customers (first_name, middle_name, last_name, email, phone, address, city, province, zip_code, identification_type, identification_number, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $formData['first_name'], $formData['middle_name'], $formData['last_name'],
                $formData['email'], $formData['phone'], $formData['address'],
                $formData['city'], $formData['province'], $formData['zip_code'] ?: null,
                $formData['identification_type'] ?: null, $formData['identification_number'] ?: null,
                $formData['notes'] ?: null
            ]);
            
            logActivity('customer_create', 'Customer created: ' . $formData['last_name'] . ', ' . $formData['first_name']);
            setFlashMessage('success', 'Customer added successfully');
            redirect(SITE_URL . '/customers/index.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

$idTypes = getEnumValues('customers', 'identification_type');

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/customers/index.php" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back to Customers
</a>

<div class="card">
    <div class="card-header">Add New Customer</div>
    <div class="card-body">
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <h5 class="mt-2 mb-3">Personal Information</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Middle Name</label>
                    <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($formData['middle_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" required>
                </div>
            </div>
            
            <h5 class="mt-4 mb-3">Contact Information</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Mobile Number *</label>
                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" 
                           placeholder="0917 123 4567" required>
                    <small class="text-muted">Philippine mobile number format</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                </div>
            </div>
            
            <h5 class="mt-4 mb-3">Address</h5>
            <div class="form-group mb-3">
                <label class="form-label">Street Address *</label>
                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($formData['address'] ?? ''); ?>" required>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">City/Municipality *</label>
                    <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($formData['city'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Province *</label>
                    <input type="text" class="form-control" name="province" value="<?php echo htmlspecialchars($formData['province'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ZIP Code</label>
                    <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($formData['zip_code'] ?? ''); ?>">
                </div>
            </div>
            
            <h5 class="mt-4 mb-3">Identification</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">ID Type</label>
                    <select class="form-select" name="identification_type">
                        <option value="">Select ID Type</option>
                        <?php foreach ($idTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo isset($formData['identification_type']) && $formData['identification_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ID Number</label>
                    <input type="text" class="form-control" name="identification_number" value="<?php echo htmlspecialchars($formData['identification_number'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Customer</button>
                <a href="<?php echo SITE_URL; ?>/customers/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
