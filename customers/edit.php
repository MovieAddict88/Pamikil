<?php
/**
 * Customer Edit
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'Edit Customer';
$db = getDb();
$errors = [];
$customerId = intval($_GET['id'] ?? 0);

if (!$customerId) {
    setFlashMessage('error', 'Invalid customer ID');
    redirect(SITE_URL . '/customers/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlashMessage('error', 'Customer not found');
    redirect(SITE_URL . '/customers/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
        redirect($_SERVER['PHP_SELF'] . '?id=' . $customerId);
        exit;
    }
    
    $updateData = [
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
        if (empty($updateData[$field])) {
            $errors[$field] = 'Required';
        }
    }
    
    if (!empty($updateData['email']) && !validateEmail($updateData['email'])) {
        $errors['email'] = 'Invalid email';
    }
    
    if (!validatePHMobileNumber($updateData['phone'])) {
        $errors['phone'] = 'Invalid mobile number';
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE customers SET 
                first_name = ?, middle_name = ?, last_name = ?, email = ?, phone = ?,
                address = ?, city = ?, province = ?, zip_code = ?,
                identification_type = ?, identification_number = ?, notes = ?
                WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge(array_values($updateData), [$customerId]));
            
            logActivity('customer_update', 'Customer updated: ID ' . $customerId);
            setFlashMessage('success', 'Customer updated');
            redirect(SITE_URL . '/customers/view.php?id=' . $customerId);
            exit;
        } catch (PDOException $e) {
            $errors['database'] = $e->getMessage();
        }
    }
}

$idTypes = getEnumValues('customers', 'identification_type');

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="card">
    <div class="card-header">Edit Customer</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" class="form-control" name="first_name" 
                           value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Middle Name</label>
                    <input type="text" class="form-control" name="middle_name" 
                           value="<?php echo htmlspecialchars($customer['middle_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" class="form-control" name="last_name" 
                           value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile *</label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" 
                           value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Address *</label>
                <input type="text" class="form-control" name="address" 
                       value="<?php echo htmlspecialchars($customer['address']); ?>" required>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">City *</label>
                    <input type="text" class="form-control" name="city" 
                           value="<?php echo htmlspecialchars($customer['city']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Province *</label>
                    <input type="text" class="form-control" name="province" 
                           value="<?php echo htmlspecialchars($customer['province']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">ZIP</label>
                    <input type="text" class="form-control" name="zip_code" 
                           value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">ID Type</label>
                    <select class="form-select" name="identification_type">
                        <option value="">Select</option>
                        <?php foreach ($idTypes as $type): ?>
                            <option value="<?php echo $type; ?>" 
                                    <?php echo $customer['identification_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">ID Number</label>
                    <input type="text" class="form-control" name="identification_number" 
                           value="<?php echo htmlspecialchars($customer['identification_number'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update</button>
                <a href="<?php echo SITE_URL; ?>/customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
