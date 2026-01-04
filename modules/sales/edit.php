<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Sale not found.';
    header('Location: index.php');
    exit();
}
$sale = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $down_payment = (float)$_POST['down_payment'];
    $balance = (float)$_POST['balance'];
    $payment_status = sanitize_input($_POST['payment_status']);
    $delivery_date = sanitize_input($_POST['delivery_date']);
    $notes = sanitize_input($_POST['notes']);
    
    $stmt = $conn->prepare("UPDATE sales SET down_payment = ?, balance = ?, payment_status = ?, delivery_date = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("ddsssi", $down_payment, $balance, $payment_status, $delivery_date, $notes, $id);
    
    if ($stmt->execute()) {
        log_transaction($conn, 'UPDATE', 'sales', $id, "Updated sale: {$sale['invoice_number']}");
        $_SESSION['success'] = 'Sale updated successfully.';
        header('Location: view.php?id=' . $id);
        exit();
    } else {
        $_SESSION['error'] = 'Failed to update sale.';
    }
    $stmt->close();
}

$page_title = 'Edit Sale';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-edit"></i> Edit Sale</h2>
        <a href="view.php?id=<?php echo $sale['id']; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="alert alert-info">
                <strong>Note:</strong> You can only update payment information. Vehicle and customer cannot be changed.
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="down_payment">Down Payment (₱)</label>
                    <input type="number" id="down_payment" name="down_payment" class="form-control" min="0" step="0.01" value="<?php echo $sale['down_payment']; ?>" data-currency>
                </div>
                
                <div class="form-group">
                    <label for="balance">Balance (₱)</label>
                    <input type="number" id="balance" name="balance" class="form-control" min="0" step="0.01" value="<?php echo $sale['balance']; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="payment_status" class="required">Payment Status</label>
                    <select id="payment_status" name="payment_status" class="form-control" required>
                        <option value="Pending" <?php echo $sale['payment_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Partial" <?php echo $sale['payment_status'] == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                        <option value="Paid" <?php echo $sale['payment_status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="delivery_date">Delivery Date</label>
                <input type="date" id="delivery_date" name="delivery_date" class="form-control" value="<?php echo $sale['delivery_date']; ?>">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="4"><?php echo escape_output($sale['notes']); ?></textarea>
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Sale</button>
                <a href="view.php?id=<?php echo $sale['id']; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('down_payment').addEventListener('input', function() {
    const salePrice = <?php echo $sale['sale_price']; ?>;
    const downPayment = parseFloat(this.value) || 0;
    const balance = salePrice - downPayment;
    document.getElementById('balance').value = balance.toFixed(2);
});
</script>

<?php require_once '../../includes/footer.php'; ?>
