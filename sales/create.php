<?php
/**
 * Create Sale
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'New Sale';
$db = getDb();
$errors = [];
$formData = [];

$vehicleId = intval($_GET['vehicle_id'] ?? 0);
$customerId = intval($_GET['customer_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
        redirect($_SERVER['PHP_SELF']);
        exit;
    }
    
    $formData = [
        'vehicle_id' => intval($_POST['vehicle_id'] ?? 0),
        'customer_id' => intval($_POST['customer_id'] ?? 0),
        'sale_date' => $_POST['sale_date'] ?? getMySQLDate(),
        'sale_price' => floatval($_POST['sale_price'] ?? 0),
        'down_payment' => floatval($_POST['down_payment'] ?? 0),
        'financing_type' => sanitize($_POST['financing_type'] ?? 'cash'),
        'bank_name' => sanitize($_POST['bank_name'] ?? ''),
        'financing_term_months' => intval($_POST['financing_term_months'] ?? 0),
        'monthly_amortization' => floatval($_POST['monthly_amortization'] ?? 0),
        'notes' => sanitize($_POST['notes'] ?? '')
    ];
    
    $required = ['vehicle_id', 'customer_id', 'sale_date', 'sale_price', 'financing_type'];
    foreach ($required as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }
    
    if ($formData['sale_price'] <= 0) {
        $errors['sale_price'] = 'Sale price must be greater than 0';
    }
    
    // Verify vehicle is available
    $stmt = $db->prepare("SELECT id, status FROM vehicles WHERE id = ?");
    $stmt->execute([$formData['vehicle_id']]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle || $vehicle['status'] !== 'available') {
        $errors['vehicle_id'] = 'Vehicle not available for sale';
    }
    
    // Verify customer exists
    $stmt = $db->prepare("SELECT id FROM customers WHERE id = ? AND status = 'active'");
    $stmt->execute([$formData['customer_id']]);
    if (!$stmt->fetch()) {
        $errors['customer_id'] = 'Customer not found';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $sql = "INSERT INTO sales (
                vehicle_id, customer_id, sale_date, sale_price, down_payment,
                financing_type, bank_name, financing_term_months, monthly_amortization,
                sales_agent_id, status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $formData['vehicle_id'], $formData['customer_id'], $formData['sale_date'],
                $formData['sale_price'], $formData['down_payment'], $formData['financing_type'],
                $formData['bank_name'] ?: null, $formData['financing_term_months'] ?: null,
                $formData['monthly_amortization'] ?: null, getCurrentUserId(),
                $formData['notes'] ?: null
            ]);
            
            $saleId = $db->lastInsertId();
            
            // Update vehicle status to reserved
            $stmt = $db->prepare("UPDATE vehicles SET status = 'reserved' WHERE id = ?");
            $stmt->execute([$formData['vehicle_id']]);
            
            $db->commit();
            
            logActivity('sale_create', 'Sale created: #' . $saleId);
            setFlashMessage('success', 'Sale created successfully');
            redirect(SITE_URL . '/sales/view.php?id=' . $saleId);
            exit;
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get available vehicles
$stmt = $db->query("SELECT id, stock_number, make, model, year, selling_price FROM vehicles WHERE status = 'available' ORDER BY created_at DESC");
$vehicles = $stmt->fetchAll();

// Get customers
$stmt = $db->query("SELECT id, first_name, middle_name, last_name, phone FROM customers WHERE status = 'active' ORDER BY last_name, first_name");
$customers = $stmt->fetchAll();

$financingTypes = getEnumValues('sales', 'financing_type');

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/sales/index.php" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back to Sales
</a>

<div class="card">
    <div class="card-header">Create New Sale</div>
    <div class="card-body">
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Vehicle *</label>
                    <select class="form-select" name="vehicle_id" required id="vehicle_select">
                        <option value="">Select Vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" 
                                    data-price="<?php echo $v['selling_price']; ?>"
                                    <?php echo isset($formData['vehicle_id']) && $formData['vehicle_id'] == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['stock_number'] . ' - ' . $v['year'] . ' ' . $v['make'] . ' ' . $v['model']); ?>
                                (<?php echo formatCurrency($v['selling_price']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Customer *</label>
                    <select class="form-select" name="customer_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>" 
                                    <?php echo isset($formData['customer_id']) && $formData['customer_id'] == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['last_name'] . ', ' . $c['first_name']); ?>
                                (<?php echo htmlspecialchars(formatPHMobileNumber($c['phone'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Sale Date *</label>
                    <input type="date" class="form-control" name="sale_date" value="<?php echo $formData['sale_date'] ?? getMySQLDate(); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Sale Price (₱) *</label>
                    <input type="number" step="0.01" class="form-control" name="sale_price" 
                           id="sale_price" value="<?php echo $formData['sale_price'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Down Payment (₱)</label>
                    <input type="number" step="0.01" class="form-control" name="down_payment" value="<?php echo $formData['down_payment'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Financing Type *</label>
                    <select class="form-select" name="financing_type" required id="financing_type">
                        <option value="">Select Type</option>
                        <?php foreach ($financingTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo isset($formData['financing_type']) && $formData['financing_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="bank_name_group" style="display: none;">
                    <label class="form-label">Bank Name</label>
                    <input type="text" class="form-control" name="bank_name" value="<?php echo htmlspecialchars($formData['bank_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group" id="financing_term_group" style="display: none;">
                    <label class="form-label">Term (Months)</label>
                    <input type="number" class="form-control" name="financing_term_months" value="<?php echo $formData['financing_term_months'] ?? ''; ?>">
                </div>
                
                <div class="form-group" id="amortization_group" style="display: none;">
                    <label class="form-label">Monthly Amortization (₱)</label>
                    <input type="number" step="0.01" class="form-control" name="monthly_amortization" value="<?php echo $formData['monthly_amortization'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Sale</button>
                <a href="<?php echo SITE_URL; ?>/sales/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('vehicle_select').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.dataset.price;
    if (price) {
        document.getElementById('sale_price').value = price;
    }
});

document.getElementById('financing_type').addEventListener('change', function() {
    const type = this.value;
    const bankGroup = document.getElementById('bank_name_group');
    const termGroup = document.getElementById('financing_term_group');
    const amortGroup = document.getElementById('amortization_group');
    
    if (type === 'bank_finance' || type === 'in_house') {
        bankGroup.style.display = 'block';
        termGroup.style.display = 'block';
        amortGroup.style.display = 'block';
    } else {
        bankGroup.style.display = 'none';
        termGroup.style.display = 'none';
        amortGroup.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
