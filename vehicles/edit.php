<?php
/**
 * Edit Vehicle
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'Edit Vehicle';
$db = getDb();
$errors = [];
$vehicleId = intval($_GET['id'] ?? 0);

if (!$vehicleId) {
    setFlashMessage('error', 'Invalid vehicle ID');
    redirect(SITE_URL . '/vehicles/index.php');
    exit;
}

// Get vehicle
$stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    setFlashMessage('error', 'Vehicle not found');
    redirect(SITE_URL . '/vehicles/index.php');
    exit;
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
        redirect($_SERVER['PHP_SELF'] . '?id=' . $vehicleId);
        exit;
    }
    
    $updateData = [
        'make' => sanitize($_POST['make'] ?? ''),
        'model' => sanitize($_POST['model'] ?? ''),
        'year' => intval($_POST['year'] ?? 0),
        'variant' => sanitize($_POST['variant'] ?? ''),
        'color' => sanitize($_POST['color'] ?? ''),
        'body_type' => sanitize($_POST['body_type'] ?? ''),
        'fuel_type' => sanitize($_POST['fuel_type'] ?? ''),
        'transmission' => sanitize($_POST['transmission'] ?? ''),
        'engine_cc' => intval($_POST['engine_cc'] ?? 0),
        'plate_number' => sanitize($_POST['plate_number'] ?? ''),
        'chassis_number' => sanitize($_POST['chassis_number'] ?? ''),
        'engine_number' => sanitize($_POST['engine_number'] ?? ''),
        'purchase_price' => floatval($_POST['purchase_price'] ?? 0),
        'selling_price' => floatval($_POST['selling_price'] ?? 0),
        'mileage' => intval($_POST['mileage'] ?? 0),
        'condition' => sanitize($_POST['condition'] ?? ''),
        'status' => sanitize($_POST['status'] ?? 'available'),
        'location_id' => intval($_POST['location_id'] ?? 0) ?: null,
        'supplier' => sanitize($_POST['supplier'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'features' => sanitize($_POST['features'] ?? '')
    ];
    
    if (empty($updateData['make']) || empty($updateData['model'])) {
        $errors[] = 'Make and Model are required';
    }
    
    if ($updateData['purchase_price'] <= 0 || $updateData['selling_price'] <= 0) {
        $errors[] = 'Prices must be greater than 0';
    }
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE vehicles SET 
                make = ?, model = ?, year = ?, variant = ?, color = ?, body_type = ?,
                fuel_type = ?, transmission = ?, engine_cc = ?, plate_number = ?,
                chassis_number = ?, engine_number = ?, purchase_price = ?, selling_price = ?,
                mileage = ?, condition = ?, status = ?, location_id = ?, supplier = ?,
                description = ?, features = ?, updated_at = NOW()
                WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge(array_values($updateData), [$vehicleId]));
            
            logActivity('vehicle_update', 'Vehicle updated: ' . $vehicle['stock_number']);
            setFlashMessage('success', 'Vehicle updated successfully');
            redirect(SITE_URL . '/vehicles/view.php?id=' . $vehicleId);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$bodyTypes = getEnumValues('vehicles', 'body_type');
$fuelTypes = getEnumValues('vehicles', 'fuel_type');
$transmissions = getEnumValues('vehicles', 'transmission');
$conditions = getEnumValues('vehicles', 'condition');
$statuses = getEnumValues('vehicles', 'status');

$stmt = $db->query("SELECT id, name FROM locations WHERE status = 'active' ORDER BY name");
$locations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<a href="<?php echo SITE_URL; ?>/vehicles/view.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back to Vehicle
</a>

<div class="card">
    <div class="card-header">Edit Vehicle: <?php echo htmlspecialchars($vehicle['stock_number']); ?></div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Make *</label>
                    <input type="text" class="form-control" name="make" value="<?php echo htmlspecialchars($vehicle['make']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Model *</label>
                    <input type="text" class="form-control" name="model" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Year *</label>
                    <input type="number" class="form-control" name="year" value="<?php echo $vehicle['year']; ?>" min="1990" max="<?php echo date('Y') + 2; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Variant</label>
                    <input type="text" class="form-control" name="variant" value="<?php echo htmlspecialchars($vehicle['variant']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Color *</label>
                    <input type="text" class="form-control" name="color" value="<?php echo htmlspecialchars($vehicle['color']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Body Type *</label>
                    <select class="form-select" name="body_type" required>
                        <?php foreach ($bodyTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $vehicle['body_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fuel Type *</label>
                    <select class="form-select" name="fuel_type" required>
                        <?php foreach ($fuelTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $vehicle['fuel_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Transmission *</label>
                    <select class="form-select" name="transmission" required>
                        <?php foreach ($transmissions as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $vehicle['transmission'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Engine CC</label>
                    <input type="number" class="form-control" name="engine_cc" value="<?php echo $vehicle['engine_cc']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Condition *</label>
                    <select class="form-select" name="condition" required>
                        <?php foreach ($conditions as $cond): ?>
                            <option value="<?php echo $cond; ?>" <?php echo $vehicle['condition'] === $cond ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $cond)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mileage (km)</label>
                    <input type="number" class="form-control" name="mileage" value="<?php echo $vehicle['mileage']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" required>
                        <?php foreach ($statuses as $st): ?>
                            <?php if ($st !== 'inactive'): ?>
                                <option value="<?php echo $st; ?>" <?php echo $vehicle['status'] === $st ? 'selected' : ''; ?>>
                                    <?php echo ucwords(str_replace('_', ' ', $st)); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Plate Number</label>
                    <input type="text" class="form-control" name="plate_number" value="<?php echo htmlspecialchars($vehicle['plate_number']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Chassis Number</label>
                    <input type="text" class="form-control" name="chassis_number" value="<?php echo htmlspecialchars($vehicle['chassis_number']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Engine Number</label>
                    <input type="text" class="form-control" name="engine_number" value="<?php echo htmlspecialchars($vehicle['engine_number']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Purchase Price (₱) *</label>
                    <input type="number" step="0.01" class="form-control" name="purchase_price" value="<?php echo $vehicle['purchase_price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Selling Price (₱) *</label>
                    <input type="number" step="0.01" class="form-control" name="selling_price" value="<?php echo $vehicle['selling_price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <select class="form-select" name="location_id">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>" <?php echo $vehicle['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Supplier</label>
                    <input type="text" class="form-control" name="supplier" value="<?php echo htmlspecialchars($vehicle['supplier']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Features</label>
                <textarea class="form-control" name="features" rows="2"><?php echo htmlspecialchars($vehicle['features']); ?></textarea>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Vehicle</button>
                <a href="<?php echo SITE_URL; ?>/vehicles/view.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
