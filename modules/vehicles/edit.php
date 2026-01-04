<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Vehicle not found.';
    header('Location: index.php');
    exit();
}

$vehicle = $result->fetch_assoc();
$stmt->close();

$locations = $conn->query("SELECT * FROM locations WHERE is_active = 1");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stock_number = sanitize_input($_POST['stock_number']);
    $make = sanitize_input($_POST['make']);
    $model = sanitize_input($_POST['model']);
    $year = (int)$_POST['year'];
    $color = sanitize_input($_POST['color']);
    $vin = sanitize_input($_POST['vin']);
    $plate_number = sanitize_input($_POST['plate_number']);
    $engine_number = sanitize_input($_POST['engine_number']);
    $transmission = sanitize_input($_POST['transmission']);
    $fuel_type = sanitize_input($_POST['fuel_type']);
    $mileage = (int)$_POST['mileage'];
    $purchase_price = (float)$_POST['purchase_price'];
    $selling_price = (float)$_POST['selling_price'];
    $status = sanitize_input($_POST['status']);
    $location_id = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $description = sanitize_input($_POST['description']);
    $features = sanitize_input($_POST['features']);
    
    $errors = [];
    
    if (empty($stock_number) || empty($make) || empty($model) || empty($year) || empty($selling_price)) {
        $errors[] = 'Please fill in all required fields.';
    }
    
    $check_stmt = $conn->prepare("SELECT id FROM vehicles WHERE stock_number = ? AND id != ?");
    $check_stmt->bind_param("si", $stock_number, $id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $errors[] = 'Stock number already exists.';
    }
    $check_stmt->close();
    
    $image_updates = [];
    $image_params = [];
    $image_types = '';
    
    if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_result = upload_file($_FILES['image_main'], $stock_number);
        if (is_array($upload_result) && isset($upload_result['error'])) {
            $errors[] = 'Main image: ' . $upload_result['error'];
        } else {
            if (!empty($vehicle['image_main'])) delete_file($vehicle['image_main']);
            $image_updates[] = 'image_main = ?';
            $image_params[] = $upload_result;
            $image_types .= 's';
        }
    }
    
    for ($i = 1; $i <= 4; $i++) {
        $field_name = "image_{$i}";
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_file($_FILES[$field_name], $stock_number);
            if (is_array($upload_result) && isset($upload_result['error'])) {
                $errors[] = "Image {$i}: " . $upload_result['error'];
            } else {
                if (!empty($vehicle[$field_name])) delete_file($vehicle[$field_name]);
                $image_updates[] = "{$field_name} = ?";
                $image_params[] = $upload_result;
                $image_types .= 's';
            }
        }
    }
    
    if (empty($errors)) {
        $image_update_sql = !empty($image_updates) ? ', ' . implode(', ', $image_updates) : '';
        
        $sql = "UPDATE vehicles SET stock_number = ?, make = ?, model = ?, year = ?, color = ?, vin = ?, plate_number = ?, engine_number = ?, transmission = ?, fuel_type = ?, mileage = ?, purchase_price = ?, selling_price = ?, status = ?, location_id = ?, description = ?, features = ?" . $image_update_sql . " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        
        $params = [$stock_number, $make, $model, $year, $color, $vin, $plate_number, $engine_number, $transmission, $fuel_type, $mileage, $purchase_price, $selling_price, $status, $location_id, $description, $features];
        $types = 'sssississsiddsiss';
        
        if (!empty($image_params)) {
            $params = array_merge($params, $image_params);
            $types .= $image_types;
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            log_transaction($conn, 'UPDATE', 'vehicles', $id, "Updated vehicle: {$stock_number}");
            $_SESSION['success'] = 'Vehicle updated successfully.';
            header('Location: view.php?id=' . $id);
            exit();
        } else {
            $errors[] = 'Failed to update vehicle: ' . $stmt->error;
        }
        
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$page_title = 'Edit Vehicle';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-edit"></i> Edit Vehicle</h2>
        <a href="view.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data" data-validate>
            <h3>Basic Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="stock_number" class="required">Stock Number</label>
                    <input type="text" id="stock_number" name="stock_number" class="form-control" value="<?php echo escape_output($vehicle['stock_number']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status" class="required">Status</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="Available" <?php echo $vehicle['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Sold" <?php echo $vehicle['status'] == 'Sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="Reserved" <?php echo $vehicle['status'] == 'Reserved' ? 'selected' : ''; ?>>Reserved</option>
                        <option value="In Service" <?php echo $vehicle['status'] == 'In Service' ? 'selected' : ''; ?>>In Service</option>
                        <option value="Inactive" <?php echo $vehicle['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="make" class="required">Make</label>
                    <input type="text" id="make" name="make" class="form-control" value="<?php echo escape_output($vehicle['make']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="model" class="required">Model</label>
                    <input type="text" id="model" name="model" class="form-control" value="<?php echo escape_output($vehicle['model']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="year" class="required">Year</label>
                    <input type="number" id="year" name="year" class="form-control" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo $vehicle['year']; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" id="color" name="color" class="form-control" value="<?php echo escape_output($vehicle['color']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="transmission">Transmission</label>
                    <select id="transmission" name="transmission" class="form-control">
                        <option value="Manual" <?php echo $vehicle['transmission'] == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                        <option value="Automatic" <?php echo $vehicle['transmission'] == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                        <option value="CVT" <?php echo $vehicle['transmission'] == 'CVT' ? 'selected' : ''; ?>>CVT</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fuel_type">Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control">
                        <option value="Gasoline" <?php echo $vehicle['fuel_type'] == 'Gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                        <option value="Diesel" <?php echo $vehicle['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Hybrid" <?php echo $vehicle['fuel_type'] == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                        <option value="Electric" <?php echo $vehicle['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                    </select>
                </div>
            </div>
            
            <h3 class="mt-3">Vehicle Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="vin">VIN</label>
                    <input type="text" id="vin" name="vin" class="form-control" maxlength="17" value="<?php echo escape_output($vehicle['vin']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="plate_number">Plate Number</label>
                    <input type="text" id="plate_number" name="plate_number" class="form-control" value="<?php echo escape_output($vehicle['plate_number']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="engine_number">Engine Number</label>
                    <input type="text" id="engine_number" name="engine_number" class="form-control" value="<?php echo escape_output($vehicle['engine_number']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="mileage">Mileage (km)</label>
                    <input type="number" id="mileage" name="mileage" class="form-control" min="0" value="<?php echo $vehicle['mileage']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="location_id">Location</label>
                    <select id="location_id" name="location_id" class="form-control">
                        <option value="">Select Location</option>
                        <?php while ($location = $locations->fetch_assoc()): ?>
                        <option value="<?php echo $location['id']; ?>" <?php echo $vehicle['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                            <?php echo escape_output($location['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <h3 class="mt-3">Pricing</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="purchase_price">Purchase Price (₱)</label>
                    <input type="number" id="purchase_price" name="purchase_price" class="form-control" min="0" step="0.01" value="<?php echo $vehicle['purchase_price']; ?>" data-currency>
                </div>
                
                <div class="form-group">
                    <label for="selling_price" class="required">Selling Price (₱)</label>
                    <input type="number" id="selling_price" name="selling_price" class="form-control" min="0" step="0.01" value="<?php echo $vehicle['selling_price']; ?>" required data-currency>
                </div>
            </div>
            
            <h3 class="mt-3">Additional Information</h3>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?php echo escape_output($vehicle['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="features">Features</label>
                <textarea id="features" name="features" class="form-control" rows="4"><?php echo escape_output($vehicle['features']); ?></textarea>
            </div>
            
            <h3 class="mt-3">Update Images</h3>
            <p class="text-muted">Upload new images to replace existing ones (JPG, PNG, GIF - Max 5MB each)</p>
            
            <div class="form-group">
                <label for="image_main">Main Image <?php echo !empty($vehicle['image_main']) ? '(Current image will be replaced)' : ''; ?></label>
                <input type="file" id="image_main" name="image_main" class="form-control" accept="image/*">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="image_1">Image 1 <?php echo !empty($vehicle['image_1']) ? '(Current)' : ''; ?></label>
                    <input type="file" id="image_1" name="image_1" class="form-control" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="image_2">Image 2 <?php echo !empty($vehicle['image_2']) ? '(Current)' : ''; ?></label>
                    <input type="file" id="image_2" name="image_2" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="image_3">Image 3 <?php echo !empty($vehicle['image_3']) ? '(Current)' : ''; ?></label>
                    <input type="file" id="image_3" name="image_3" class="form-control" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="image_4">Image 4 <?php echo !empty($vehicle['image_4']) ? '(Current)' : ''; ?></label>
                    <input type="file" id="image_4" name="image_4" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Vehicle
                </button>
                <a href="view.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
