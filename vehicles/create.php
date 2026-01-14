<?php
/**
 * Create Vehicle
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'Add Vehicle';
$db = getDb();
$errors = [];
$formData = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect($_SERVER['PHP_SELF']);
        exit;
    }
    
    // Get form data
    $formData = [
        'stock_number' => sanitize($_POST['stock_number'] ?? generateStockNumber(date('Y'))),
        'make' => sanitize($_POST['make'] ?? ''),
        'model' => sanitize($_POST['model'] ?? ''),
        'year' => intval($_POST['year'] ?? date('Y')),
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
        'date_acquired' => $_POST['date_acquired'] ?? getMySQLDate(),
        'description' => sanitize($_POST['description'] ?? ''),
        'features' => sanitize($_POST['features'] ?? '')
    ];
    
    // Validate required fields
    $required = ['stock_number', 'make', 'model', 'year', 'color', 'body_type', 'fuel_type', 'transmission', 'purchase_price', 'selling_price', 'condition', 'date_acquired'];
    
    foreach ($required as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = 'This field is required';
        }
    }
    
    // Validate prices
    if ($formData['purchase_price'] <= 0) {
        $errors['purchase_price'] = 'Purchase price must be greater than 0';
    }
    if ($formData['selling_price'] <= 0) {
        $errors['selling_price'] = 'Selling price must be greater than 0';
    }
    
    // Check if stock number already exists
    $stmt = $db->prepare("SELECT id FROM vehicles WHERE stock_number = ?");
    $stmt->execute([$formData['stock_number']]);
    if ($stmt->fetch()) {
        $errors['stock_number'] = 'Stock number already exists';
    }
    
    // Handle file uploads
    $uploadedImages = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $fileData = [
                'name' => $_FILES['images']['name'][$key],
                'type' => $_FILES['images']['type'][$key],
                'tmp_name' => $_FILES['images']['tmp_name'][$key],
                'error' => $_FILES['images']['error'][$key],
                'size' => $_FILES['images']['size'][$key]
            ];
            
            $result = uploadFile($fileData, 'vehicles');
            if ($result['success']) {
                $uploadedImages[] = $result['filename'];
            }
        }
    }
    
    if (!empty($uploadedImages)) {
        $formData['images'] = implode(',', $uploadedImages);
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $sql = "
                INSERT INTO vehicles (
                    stock_number, make, model, year, variant, color, body_type, fuel_type,
                    transmission, engine_cc, plate_number, chassis_number, engine_number,
                    purchase_price, selling_price, mileage, condition, status, location_id,
                    supplier, date_acquired, description, features, images
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $formData['stock_number'],
                $formData['make'],
                $formData['model'],
                $formData['year'],
                $formData['variant'],
                $formData['color'],
                $formData['body_type'],
                $formData['fuel_type'],
                $formData['transmission'],
                $formData['engine_cc'] ?: null,
                $formData['plate_number'] ?: null,
                $formData['chassis_number'] ?: null,
                $formData['engine_number'] ?: null,
                $formData['purchase_price'],
                $formData['selling_price'],
                $formData['mileage'] ?: null,
                $formData['condition'],
                $formData['status'],
                $formData['location_id'],
                $formData['supplier'] ?: null,
                $formData['date_acquired'],
                $formData['description'] ?: null,
                $formData['features'] ?: null,
                $formData['images'] ?? null
            ]);
            
            logActivity('vehicle_create', 'Vehicle created: ' . $formData['stock_number']);
            setFlashMessage('success', 'Vehicle added successfully!');
            redirect(SITE_URL . '/vehicles/index.php');
            exit;
            
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
            logError('Vehicle creation failed', ['error' => $e->getMessage()]);
        }
    }
}

// Set default form data if empty
if (empty($formData)) {
    $formData = [
        'year' => date('Y'),
        'condition' => 'brand_new',
        'status' => 'available',
        'date_acquired' => getMySQLDate()
    ];
}

// Get enum values
$bodyTypes = getEnumValues('vehicles', 'body_type');
$fuelTypes = getEnumValues('vehicles', 'fuel_type');
$transmissions = getEnumValues('vehicles', 'transmission');
$conditions = getEnumValues('vehicles', 'condition');
$statuses = getEnumValues('vehicles', 'status');

// Get locations
$stmt = $db->query("SELECT id, name FROM locations WHERE status = 'active' ORDER BY name");
$locations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Back Button -->
<a href="<?php echo SITE_URL; ?>/vehicles/index.php" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Back to Vehicles
</a>

<!-- Form -->
<div class="card">
    <div class="card-header">Add New Vehicle</div>
    <div class="card-body">
        <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errors['database']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <!-- Basic Information -->
            <h5 class="mt-4 mb-3">Basic Information</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label for="stock_number" class="form-label">Stock Number *</label>
                    <input type="text" class="form-control <?php echo isset($errors['stock_number']) ? 'is-invalid' : ''; ?>" 
                           id="stock_number" name="stock_number" value="<?php echo htmlspecialchars($formData['stock_number']); ?>" required>
                    <?php if (isset($errors['stock_number'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['stock_number']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="year" class="form-label">Year *</label>
                    <input type="number" class="form-control <?php echo isset($errors['year']) ? 'is-invalid' : ''; ?>" 
                           id="year" name="year" value="<?php echo $formData['year']; ?>" min="1990" max="<?php echo date('Y') + 2; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="make" class="form-label">Make *</label>
                    <input type="text" class="form-control <?php echo isset($errors['make']) ? 'is-invalid' : ''; ?>" 
                           id="make" name="make" value="<?php echo htmlspecialchars($formData['make']); ?>" placeholder="e.g., Toyota, Honda" required>
                </div>
                
                <div class="form-group">
                    <label for="model" class="form-label">Model *</label>
                    <input type="text" class="form-control <?php echo isset($errors['model']) ? 'is-invalid' : ''; ?>" 
                           id="model" name="model" value="<?php echo htmlspecialchars($formData['model']); ?>" placeholder="e.g., Vios, City" required>
                </div>
                
                <div class="form-group">
                    <label for="variant" class="form-label">Variant</label>
                    <input type="text" class="form-control" id="variant" name="variant" 
                           value="<?php echo htmlspecialchars($formData['variant']); ?>" placeholder="e.g., 1.5 G CVT">
                </div>
                
                <div class="form-group">
                    <label for="color" class="form-label">Color *</label>
                    <input type="text" class="form-control <?php echo isset($errors['color']) ? 'is-invalid' : ''; ?>" 
                           id="color" name="color" value="<?php echo htmlspecialchars($formData['color']); ?>" placeholder="e.g., White Pearl" required>
                </div>
            </div>
            
            <!-- Vehicle Details -->
            <h5 class="mt-4 mb-3">Vehicle Details</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label for="body_type" class="form-label">Body Type *</label>
                    <select class="form-select <?php echo isset($errors['body_type']) ? 'is-invalid' : ''; ?>" 
                            id="body_type" name="body_type" required>
                        <option value="">Select Body Type</option>
                        <?php foreach ($bodyTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $formData['body_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fuel_type" class="form-label">Fuel Type *</label>
                    <select class="form-select <?php echo isset($errors['fuel_type']) ? 'is-invalid' : ''; ?>" 
                            id="fuel_type" name="fuel_type" required>
                        <option value="">Select Fuel Type</option>
                        <?php foreach ($fuelTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $formData['fuel_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transmission" class="form-label">Transmission *</label>
                    <select class="form-select <?php echo isset($errors['transmission']) ? 'is-invalid' : ''; ?>" 
                            id="transmission" name="transmission" required>
                        <option value="">Select Transmission</option>
                        <?php foreach ($transmissions as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $formData['transmission'] === $type ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="engine_cc" class="form-label">Engine CC</label>
                    <input type="number" class="form-control" id="engine_cc" name="engine_cc" 
                           value="<?php echo $formData['engine_cc']; ?>" placeholder="e.g., 1498">
                </div>
                
                <div class="form-group">
                    <label for="condition" class="form-label">Condition *</label>
                    <select class="form-select <?php echo isset($errors['condition']) ? 'is-invalid' : ''; ?>" 
                            id="condition" name="condition" required>
                        <option value="">Select Condition</option>
                        <?php foreach ($conditions as $cond): ?>
                            <option value="<?php echo $cond; ?>" <?php echo $formData['condition'] === $cond ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $cond)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="mileage" class="form-label">Mileage (km)</label>
                    <input type="number" class="form-control" id="mileage" name="mileage" 
                           value="<?php echo $formData['mileage']; ?>" placeholder="0 if brand new">
                </div>
            </div>
            
            <!-- Identification Numbers -->
            <h5 class="mt-4 mb-3">Identification Numbers</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label for="plate_number" class="form-label">Plate Number</label>
                    <input type="text" class="form-control" id="plate_number" name="plate_number" 
                           value="<?php echo htmlspecialchars($formData['plate_number']); ?>" placeholder="e.g., ABC 1234">
                </div>
                
                <div class="form-group">
                    <label for="chassis_number" class="form-label">Chassis Number</label>
                    <input type="text" class="form-control" id="chassis_number" name="chassis_number" 
                           value="<?php echo htmlspecialchars($formData['chassis_number']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="engine_number" class="form-label">Engine Number</label>
                    <input type="text" class="form-control" id="engine_number" name="engine_number" 
                           value="<?php echo htmlspecialchars($formData['engine_number']); ?>">
                </div>
            </div>
            
            <!-- Pricing -->
            <h5 class="mt-4 mb-3">Pricing</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label for="purchase_price" class="form-label">Purchase Price (₱) *</label>
                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['purchase_price']) ? 'is-invalid' : ''; ?>" 
                           id="purchase_price" name="purchase_price" value="<?php echo $formData['purchase_price']; ?>" required>
                    <?php if (isset($errors['purchase_price'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['purchase_price']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="selling_price" class="form-label">Selling Price (₱) *</label>
                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['selling_price']) ? 'is-invalid' : ''; ?>" 
                           id="selling_price" name="selling_price" value="<?php echo $formData['selling_price']; ?>" required>
                    <?php if (isset($errors['selling_price'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['selling_price']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Status & Location -->
            <h5 class="mt-4 mb-3">Status & Location</h5>
            <div class="form-grid">
                <div class="form-group">
                    <label for="status" class="form-label">Status *</label>
                    <select class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" 
                            id="status" name="status" required>
                        <option value="">Select Status</option>
                        <?php foreach ($statuses as $st): ?>
                            <?php if ($st !== 'inactive'): ?>
                                <option value="<?php echo $st; ?>" <?php echo $formData['status'] === $st ? 'selected' : ''; ?>>
                                    <?php echo ucwords(str_replace('_', ' ', $st)); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="location_id" class="form-label">Location</label>
                    <select class="form-select" id="location_id" name="location_id">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['id']; ?>" <?php echo $formData['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="supplier" class="form-label">Supplier</label>
                    <input type="text" class="form-control" id="supplier" name="supplier" 
                           value="<?php echo htmlspecialchars($formData['supplier']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_acquired" class="form-label">Date Acquired *</label>
                    <input type="date" class="form-control <?php echo isset($errors['date_acquired']) ? 'is-invalid' : ''; ?>" 
                           id="date_acquired" name="date_acquired" value="<?php echo $formData['date_acquired']; ?>" required>
                </div>
            </div>
            
            <!-- Description & Features -->
            <h5 class="mt-4 mb-3">Additional Information</h5>
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($formData['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="features" class="form-label">Features</label>
                <textarea class="form-control" id="features" name="features" rows="2" 
                          placeholder="e.g., ABS, Airbags, GPS, Bluetooth, Parking Camera"><?php echo htmlspecialchars($formData['features']); ?></textarea>
            </div>
            
            <!-- Image Upload -->
            <h5 class="mt-4 mb-3">Vehicle Images</h5>
            <div class="form-group">
                <label for="images" class="form-label">Upload Images</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                <small class="text-muted">You can upload multiple images. Accepted formats: JPG, PNG, GIF, WebP</small>
            </div>
            
            <!-- Submit Buttons -->
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Vehicle
                </button>
                <a href="<?php echo SITE_URL; ?>/vehicles/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
