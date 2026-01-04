<?php
/**
 * Create Service
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Add Service';
$db = getDb();
$errors = [];
$formData = [];

$vehicleId = intval($_GET['vehicle_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
        redirect($_SERVER['PHP_SELF']);
        exit;
    }
    
    $formData = [
        'vehicle_id' => intval($_POST['vehicle_id'] ?? 0),
        'service_type' => sanitize($_POST['service_type'] ?? ''),
        'service_date' => $_POST['service_date'] ?? getMySQLDate(),
        'description' => sanitize($_POST['description'] ?? ''),
        'parts_used' => sanitize($_POST['parts_used'] ?? ''),
        'labor_cost' => floatval($_POST['labor_cost'] ?? 0),
        'parts_cost' => floatval($_POST['parts_cost'] ?? 0),
        'total_cost' => floatval($_POST['total_cost'] ?? 0),
        'performed_by' => sanitize($_POST['performed_by'] ?? ''),
        'mechanic_name' => sanitize($_POST['mechanic_name'] ?? ''),
        'status' => sanitize($_POST['status'] ?? 'scheduled'),
        'next_service_date' => $_POST['next_service_date'] ?? '',
        'notes' => sanitize($_POST['notes'] ?? '')
    ];
    
    $required = ['vehicle_id', 'service_type', 'service_date', 'description', 'performed_by'];
    foreach ($required as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }
    
    if ($formData['total_cost'] <= 0) {
        $errors['total_cost'] = 'Total cost must be greater than 0';
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO services (
                vehicle_id, service_type, service_date, description, parts_used,
                labor_cost, parts_cost, total_cost, performed_by, mechanic_name,
                status, next_service_date, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $formData['vehicle_id'], $formData['service_type'], $formData['service_date'],
                $formData['description'], $formData['parts_used'] ?: null,
                $formData['labor_cost'], $formData['parts_cost'], $formData['total_cost'],
                $formData['performed_by'], $formData['mechanic_name'] ?: null,
                $formData['status'], $formData['next_service_date'] ?: null,
                $formData['notes'] ?: null
            ]);
            
            logActivity('service_create', 'Service created for vehicle ID: ' . $formData['vehicle_id']);
            setFlashMessage('success', 'Service record added successfully');
            redirect(SITE_URL . '/services/index.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get vehicles
$stmt = $db->query("SELECT id, stock_number, make, model, year FROM vehicles WHERE status != 'inactive' ORDER BY created_at DESC");
$vehicles = $stmt->fetchAll();

$serviceTypes = getEnumValues('services', 'service_type');
$statuses = getEnumValues('services', 'status');

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/services/index.php" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back to Services
</a>

<div class="card">
    <div class="card-header">Add Service Record</div>
    <div class="card-body">
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Vehicle *</label>
                    <select class="form-select" name="vehicle_id" required>
                        <option value="">Select Vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" 
                                    <?php echo isset($formData['vehicle_id']) && $formData['vehicle_id'] == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['stock_number'] . ' - ' . $v['year'] . ' ' . $v['make'] . ' ' . $v['model']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Service Type *</label>
                    <select class="form-select" name="service_type" required>
                        <option value="">Select Type</option>
                        <?php foreach ($serviceTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo isset($formData['service_type']) && $formData['service_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Service Date *</label>
                    <input type="date" class="form-control" name="service_date" 
                           value="<?php echo $formData['service_date'] ?? getMySQLDate(); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="">Select Status</option>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?php echo $st; ?>" <?php echo isset($formData['status']) && $formData['status'] === $st ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $st)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Performed By *</label>
                    <input type="text" class="form-control" name="performed_by" 
                           value="<?php echo htmlspecialchars($formData['performed_by'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mechanic Name</label>
                    <input type="text" class="form-control" name="mechanic_name" 
                           value="<?php echo htmlspecialchars($formData['mechanic_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Next Service Date</label>
                    <input type="date" class="form-control" name="next_service_date" 
                           value="<?php echo htmlspecialchars($formData['next_service_date'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Parts Used</label>
                <textarea class="form-control" name="parts_used" rows="2"><?php echo htmlspecialchars($formData['parts_used'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Labor Cost (₱)</label>
                    <input type="number" step="0.01" class="form-control" name="labor_cost" value="<?php echo $formData['labor_cost'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Parts Cost (₱)</label>
                    <input type="number" step="0.01" class="form-control" name="parts_cost" value="<?php echo $formData['parts_cost'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Total Cost (₱) *</label>
                    <input type="number" step="0.01" class="form-control" name="total_cost" value="<?php echo $formData['total_cost'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Service</button>
                <a href="<?php echo SITE_URL; ?>/services/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
