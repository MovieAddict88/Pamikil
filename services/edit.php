<?php
/**
 * Services Edit
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'Edit Service';
$db = getDb();
$errors = [];
$serviceId = intval($_GET['id'] ?? 0);

if (!$serviceId) {
    setFlashMessage('error', 'Invalid service ID');
    redirect(SITE_URL . '/services/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    setFlashMessage('error', 'Service not found');
    redirect(SITE_URL . '/services/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
        redirect($_SERVER['PHP_SELF'] . '?id=' . $serviceId);
        exit;
    }
    
    $updateData = [
        'service_date' => $_POST['service_date'] ?? getMySQLDate(),
        'service_type' => sanitize($_POST['service_type'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'parts_used' => sanitize($_POST['parts_used'] ?? ''),
        'labor_cost' => floatval($_POST['labor_cost'] ?? 0),
        'parts_cost' => floatval($_POST['parts_cost'] ?? 0),
        'total_cost' => floatval($_POST['total_cost'] ?? 0),
        'performed_by' => sanitize($_POST['performed_by'] ?? ''),
        'mechanic_name' => sanitize($_POST['mechanic_name'] ?? ''),
        'status' => sanitize($_POST['status'] ?? ''),
        'next_service_date' => $_POST['next_service_date'] ?? '',
        'notes' => sanitize($_POST['notes'] ?? '')
    ];
    
    $required = ['service_type', 'service_date', 'description', 'performed_by'];
    foreach ($required as $field) {
        if (empty($updateData[$field])) {
            $errors[$field] = 'Required';
        }
    }
    
    if ($updateData['total_cost'] <= 0) {
        $errors['total_cost'] = 'Must be greater than 0';
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE services SET 
                service_date = ?, service_type = ?, description = ?, parts_used = ?,
                labor_cost = ?, parts_cost = ?, total_cost = ?, performed_by = ?,
                mechanic_name = ?, status = ?, next_service_date = ?, notes = ?
                WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge(array_values($updateData), [$serviceId]));
            
            logActivity('service_update', 'Service updated: ID ' . $serviceId);
            setFlashMessage('success', 'Service updated');
            redirect(SITE_URL . '/services/view.php?id=' . $serviceId);
            exit;
        } catch (PDOException $e) {
            $errors['database'] = $e->getMessage();
        }
    }
}

$serviceTypes = getEnumValues('services', 'service_type');
$statuses = getEnumValues('services', 'status');

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/services/view.php?id=<?php echo $service['id']; ?>" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="card">
    <div class="card-header">Edit Service Record</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Service Date *</label>
                    <input type="date" class="form-control" name="service_date" 
                           value="<?php echo $service['service_date']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Service Type *</label>
                    <select class="form-select" name="service_type" required>
                        <option value="">Select Type</option>
                        <?php foreach ($serviceTypes as $type): ?>
                            <option value="<?php echo $type; ?>" 
                                    <?php echo $service['service_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="">Select Status</option>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?php echo $st; ?>" 
                                    <?php echo $service['status'] === $st ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $st)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Performed By *</label>
                    <input type="text" class="form-control" name="performed_by" 
                           value="<?php echo htmlspecialchars($service['performed_by']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mechanic Name</label>
                    <input type="text" class="form-control" name="mechanic_name" 
                           value="<?php echo htmlspecialchars($service['mechanic_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Next Service Date</label>
                    <input type="date" class="form-control" name="next_service_date" 
                           value="<?php echo htmlspecialchars($service['next_service_date'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($service['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Parts Used</label>
                <textarea class="form-control" name="parts_used" rows="2"><?php echo htmlspecialchars($service['parts_used'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Labor Cost (₱)</label>
                    <input type="number" step="0.01" class="form-control" name="labor_cost" 
                           value="<?php echo $service['labor_cost']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Parts Cost (₱)</label>
                    <input type="number" step="0.01" class="form-control" name="parts_cost" 
                           value="<?php echo $service['parts_cost']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Total Cost (₱) *</label>
                    <input type="number" step="0.01" class="form-control" name="total_cost" 
                           value="<?php echo $service['total_cost']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"><?php echo htmlspecialchars($service['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Service</button>
                <a href="<?php echo SITE_URL; ?>/services/view.php?id=<?php echo $service['id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
