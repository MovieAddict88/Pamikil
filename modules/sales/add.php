<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$vehicles = $conn->query("SELECT id, stock_number, make, model, year, selling_price FROM vehicles WHERE status = 'Available' OR status = 'Reserved' ORDER BY stock_number DESC");
$customers = $conn->query("SELECT id, first_name, middle_name, last_name FROM customers ORDER BY last_name, first_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $customer_id = (int)$_POST['customer_id'];
    $sale_price = (float)$_POST['sale_price'];
    $down_payment = (float)$_POST['down_payment'];
    $balance = (float)$_POST['balance'];
    $payment_method = sanitize_input($_POST['payment_method']);
    $payment_status = sanitize_input($_POST['payment_status']);
    $sale_date = sanitize_input($_POST['sale_date']);
    $delivery_date = sanitize_input($_POST['delivery_date']);
    $notes = sanitize_input($_POST['notes']);
    
    $errors = [];
    
    if (empty($vehicle_id) || empty($customer_id) || empty($sale_price) || empty($sale_date)) {
        $errors[] = 'Please fill in all required fields.';
    }
    
    if (empty($errors)) {
        $invoice_number = generate_invoice_number($conn);
        $sold_by = $_SESSION['user_id'];
        $location_id = 1;
        
        $stmt = $conn->prepare("INSERT INTO sales (invoice_number, vehicle_id, customer_id, sale_price, down_payment, balance, payment_method, payment_status, sale_date, delivery_date, notes, sold_by, location_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("siidddsssssii", $invoice_number, $vehicle_id, $customer_id, $sale_price, $down_payment, $balance, $payment_method, $payment_status, $sale_date, $delivery_date, $notes, $sold_by, $location_id);
        
        if ($stmt->execute()) {
            $sale_id = $stmt->insert_id();
            
            $update_vehicle = $conn->prepare("UPDATE vehicles SET status = 'Sold' WHERE id = ?");
            $update_vehicle->bind_param("i", $vehicle_id);
            $update_vehicle->execute();
            $update_vehicle->close();
            
            log_transaction($conn, 'CREATE', 'sales', $sale_id, "Created sale: {$invoice_number}");
            $_SESSION['success'] = 'Sale recorded successfully.';
            header('Location: view.php?id=' . $sale_id);
            exit();
        } else {
            $errors[] = 'Failed to record sale.';
        }
        
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$page_title = 'New Sale';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-plus"></i> New Sale</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label for="vehicle_id" class="required">Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id" class="form-control" required onchange="updatePrice()">
                        <option value="">Select Vehicle</option>
                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                        <option value="<?php echo $vehicle['id']; ?>" data-price="<?php echo $vehicle['selling_price']; ?>">
                            <?php echo escape_output($vehicle['stock_number'] . ' - ' . $vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model'] . ' - ' . format_currency($vehicle['selling_price'])); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="customer_id" class="required">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-control" required>
                        <option value="">Select Customer</option>
                        <?php while ($customer = $customers->fetch_assoc()): ?>
                        <option value="<?php echo $customer['id']; ?>">
                            <?php echo escape_output(get_customer_name($customer)); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Don't see the customer? <a href="../customers/add.php" target="_blank">Add new customer</a></small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sale_price" class="required">Sale Price (₱)</label>
                    <input type="number" id="sale_price" name="sale_price" class="form-control" min="0" step="0.01" required data-currency>
                </div>
                
                <div class="form-group">
                    <label for="down_payment">Down Payment (₱)</label>
                    <input type="number" id="down_payment" name="down_payment" class="form-control" min="0" step="0.01" value="0" data-currency>
                </div>
                
                <div class="form-group">
                    <label for="balance">Balance (₱)</label>
                    <input type="number" id="balance" name="balance" class="form-control" min="0" step="0.01" value="0" readonly data-currency>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="payment_method" class="required">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Check">Check</option>
                        <option value="Financing">Financing</option>
                        <option value="Installment">Installment</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="payment_status" class="required">Payment Status</label>
                    <select id="payment_status" name="payment_status" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Partial">Partial</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sale_date" class="required">Sale Date</label>
                    <input type="date" id="sale_date" name="sale_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="delivery_date">Delivery Date</label>
                    <input type="date" id="delivery_date" name="delivery_date" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Additional notes about this sale"></textarea>
            </div>
            
            <div class="btn-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Record Sale
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updatePrice() {
    const select = document.getElementById('vehicle_id');
    const option = select.options[select.selectedIndex];
    const price = option.getAttribute('data-price');
    if (price) {
        document.getElementById('sale_price').value = price;
        calculateBalance();
    }
}

function calculateBalance() {
    const salePrice = parseFloat(document.getElementById('sale_price').value) || 0;
    const downPayment = parseFloat(document.getElementById('down_payment').value) || 0;
    const balance = salePrice - downPayment;
    document.getElementById('balance').value = balance.toFixed(2);
}

document.getElementById('sale_price').addEventListener('input', calculateBalance);
document.getElementById('down_payment').addEventListener('input', calculateBalance);
</script>

<?php require_once '../../includes/footer.php'; ?>
