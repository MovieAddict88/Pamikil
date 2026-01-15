<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM service_history WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Service not found.';
    header('Location: index.php');
    exit();
}
$service = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = sanitize_input($_POST['status']);
    $completion_date = sanitize_input($_POST['completion_date']);
    $cost = (float)$_POST['cost'];
    $payment_status = sanitize_input($_POST['payment_status']);
    $notes = sanitize_input($_POST['notes']);
    
    $stmt = $conn->prepare("UPDATE service_history SET status = ?, completion_date = ?, cost = ?, payment_status = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("ssdssi", $status, $completion_date, $cost, $payment_status, $notes, $id);
    
    if ($stmt->execute()) {
        log_transaction($conn, 'UPDATE', 'service_history', $id, "Updated service: {$service['service_number']}");
        $_SESSION['success'] = 'Service updated successfully.';
        header('Location: view.php?id=' . $id);
        exit();
    } else {
        $_SESSION['error'] = 'Failed to update service.';
    }
    $stmt->close();
}

$page_title = 'Edit Service';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-edit"></i> Edit Service</h2>
        <a href="view.php?id=<?php echo $service['id']; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label for="status" class="required">Status</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="Scheduled" <?php echo $service['status'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="In Progress" <?php echo $service['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $service['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $service['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="completion_date">Completion Date</label>
                    <input type="date" id="completion_date" name="completion_date" class="form-control" value="<?php echo $service['completion_date']; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cost">Cost (₱)</label>
                    <input type="number" id="cost" name="cost" class="form-control" min="0" step="0.01" value="<?php echo $service['cost']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="payment_status">Payment Status</label>
                    <select id="payment_status" name="payment_status" class="form-control">
                        <option value="Pending" <?php echo $service['payment_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Paid" <?php echo $service['payment_status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="Cancelled" <?php echo $service['payment_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="4"><?php echo escape_output($service['notes']); ?></textarea>
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Service</button>
                <a href="view.php?id=<?php echo $service['id']; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
