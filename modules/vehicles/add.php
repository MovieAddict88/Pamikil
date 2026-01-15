<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

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
    
    $check_stmt = $conn->prepare("SELECT id FROM vehicles WHERE stock_number = ?");
    $check_stmt->bind_param("s", $stock_number);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $errors[] = 'Stock number already exists.';
    }
    $check_stmt->close();
    
    $image_main = null;
    $image_1 = null;
    $image_2 = null;
    $image_3 = null;
    $image_4 = null;
    
    if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_result = upload_file($_FILES['image_main'], $stock_number);
        if (is_array($upload_result) && isset($upload_result['error'])) {
            $errors[] = 'Main image: ' . $upload_result['error'];
        } else {
            $image_main = $upload_result;
        }
    }
    
    for ($i = 1; $i <= 4; $i++) {
        $field_name = "image_{$i}";
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = upload_file($_FILES[$field_name], $stock_number);
            if (is_array($upload_result) && isset($upload_result['error'])) {
                $errors[] = "Image {$i}: " . $upload_result['error'];
            } else {
                $$field_name = $upload_result;
            }
        }
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO vehicles (stock_number, make, model, year, color, vin, plate_number, engine_number, transmission, fuel_type, mileage, purchase_price, selling_price, status, location_id, description, features, image_main, image_1, image_2, image_3, image_4, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("sssississsiddsssssssssi", 
            $stock_number, $make, $model, $year, $color, $vin, $plate_number, $engine_number, 
            $transmission, $fuel_type, $mileage, $purchase_price, $selling_price, $status, 
            $location_id, $description, $features, $image_main, $image_1, $image_2, $image_3, $image_4, $user_id
        );
        
        if ($stmt->execute()) {
            $vehicle_id = $stmt->insert_id;
            log_transaction($conn, 'CREATE', 'vehicles', $vehicle_id, "Added vehicle: {$stock_number}");
            $_SESSION['success'] = 'Vehicle added successfully.';
            header('Location: view.php?id=' . $vehicle_id);
            exit();
        } else {
            $errors[] = 'Failed to add vehicle: ' . $stmt->error;
        }
        
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$suggested_stock_number = generate_stock_number($conn);

$page_title = 'Add Vehicle';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-plus"></i> Add Vehicle</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data" data-validate>
            <h3>Basic Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="stock_number" class="required">Stock Number</label>
                    <input type="text" id="stock_number" name="stock_number" class="form-control" value="<?php echo $suggested_stock_number; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status" class="required">Status</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="Available">Available</option>
                        <option value="Reserved">Reserved</option>
                        <option value="In Service">In Service</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="make" class="required">Make</label>
                    <input type="text" id="make" name="make" class="form-control" placeholder="e.g., Toyota, Honda, Ford" required>
                </div>
                
                <div class="form-group">
                    <label for="model" class="required">Model</label>
                    <input type="text" id="model" name="model" class="form-control" placeholder="e.g., Vios, City, Ranger" required>
                </div>
                
                <div class="form-group">
                    <label for="year" class="required">Year</label>
                    <input type="number" id="year" name="year" class="form-control" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo date('Y'); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" id="color" name="color" class="form-control" placeholder="e.g., White, Black, Silver">
                </div>
                
                <div class="form-group">
                    <label for="transmission">Transmission</label>
                    <select id="transmission" name="transmission" class="form-control">
                        <option value="Manual">Manual</option>
                        <option value="Automatic" selected>Automatic</option>
                        <option value="CVT">CVT</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fuel_type">Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control">
                        <option value="Gasoline" selected>Gasoline</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="Electric">Electric</option>
                    </select>
                </div>
            </div>
            
            <h3 class="mt-3">Vehicle Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="vin">VIN</label>
                    <input type="text" id="vin" name="vin" class="form-control" maxlength="17" placeholder="Vehicle Identification Number">
                </div>
                
                <div class="form-group">
                    <label for="plate_number">Plate Number</label>
                    <input type="text" id="plate_number" name="plate_number" class="form-control" placeholder="ABC 1234">
                </div>
                
                <div class="form-group">
                    <label for="engine_number">Engine Number</label>
                    <input type="text" id="engine_number" name="engine_number" class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="mileage">Mileage (km)</label>
                    <input type="number" id="mileage" name="mileage" class="form-control" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label for="location_id">Location</label>
                    <select id="location_id" name="location_id" class="form-control">
                        <option value="">Select Location</option>
                        <?php while ($location = $locations->fetch_assoc()): ?>
                        <option value="<?php echo $location['id']; ?>"><?php echo escape_output($location['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <h3 class="mt-3">Pricing</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="purchase_price">Purchase Price (₱)</label>
                    <input type="number" id="purchase_price" name="purchase_price" class="form-control" min="0" step="0.01" value="0" data-currency>
                </div>
                
                <div class="form-group">
                    <label for="selling_price" class="required">Selling Price (₱)</label>
                    <input type="number" id="selling_price" name="selling_price" class="form-control" min="0" step="0.01" required data-currency>
                </div>
            </div>
            
            <h3 class="mt-3">Additional Information</h3>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Additional notes about this vehicle"></textarea>
            </div>
            
            <div class="form-group">
                <label for="features">Features</label>
                <textarea id="features" name="features" class="form-control" rows="4" placeholder="List vehicle features (e.g., leather seats, sunroof, navigation system)"></textarea>
            </div>
            
            <h3 class="mt-3">Vehicle Images</h3>
            <p class="text-muted">Upload images (JPG, PNG, GIF - Max 5MB each)</p>
            
            <div class="form-group">
                <label for="image_main">Main Image</label>
                <input type="file" id="image_main" name="image_main" class="form-control" accept="image/*">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="image_1">Image 1</label>
                    <input type="file" id="image_1" name="image_1" class="form-control" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="image_2">Image 2</label>
                    <input type="file" id="image_2" name="image_2" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="image_3">Image 3</label>
                    <input type="file" id="image_3" name="image_3" class="form-control" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="image_4">Image 4</label>
                    <input type="file" id="image_4" name="image_4" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Vehicle
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
