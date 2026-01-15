<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_auth();

$vehicles = $conn->query("SELECT id, stock_number, make, model, year FROM vehicles ORDER BY stock_number DESC");
$customers = $conn->query("SELECT id, first_name, middle_name, last_name FROM customers ORDER BY last_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $service_type = sanitize_input($_POST['service_type']);
    $description = sanitize_input($_POST['description']);
    $service_date = sanitize_input($_POST['service_date']);
    $cost = (float)$_POST['cost'];
    $technician = sanitize_input($_POST['technician']);
    $notes = sanitize_input($_POST['notes']);
    
    if (empty($vehicle_id) || empty($service_type) || empty($service_date)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
    } else {
        $service_number = generate_service_number($conn);
        $user_id = $_SESSION['user_id'];
        $status = 'Scheduled';
        
        $stmt = $conn->prepare("INSERT INTO service_history (service_number, vehicle_id, customer_id, service_type, description, service_date, status, cost, technician, notes, created_by, location_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("siissssdssii", $service_number, $vehicle_id, $customer_id, $service_type, $description, $service_date, $status, $cost, $technician, $notes, $user_id);
        
        if ($stmt->execute()) {
            log_transaction($conn, 'CREATE', 'service_history', $stmt->insert_id, "Scheduled service: {$service_number}");
            $_SESSION['success'] = 'Service scheduled successfully.';
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['error'] = 'Failed to schedule service.';
        }
        $stmt->close();
    }
}

$page_title = 'Schedule Service';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-plus"></i> Schedule Service</h2>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label for="vehicle_id" class="required">Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                        <option value="">Select Vehicle</option>
                        <?php while ($v = $vehicles->fetch_assoc()): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo escape_output($v['stock_number'] . ' - ' . $v['year'] . ' ' . $v['make'] . ' ' . $v['model']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-control">
                        <option value="">Select Customer</option>
                        <?php while ($c = $customers->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo escape_output(get_customer_name($c)); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="service_type" class="required">Service Type</label>
                    <input type="text" id="service_type" name="service_type" class="form-control" placeholder="e.g., Oil Change, Brake Service" required>
                </div>
                
                <div class="form-group">
                    <label for="service_date" class="required">Service Date</label>
                    <input type="date" id="service_date" name="service_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cost">Estimated Cost (₱)</label>
                    <input type="number" id="cost" name="cost" class="form-control" min="0" step="0.01" value="0">
                </div>
                
                <div class="form-group">
                    <label for="technician">Technician</label>
                    <input type="text" id="technician" name="technician" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="required">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Schedule Service</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
