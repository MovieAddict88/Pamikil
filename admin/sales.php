<?php
require_once '../config.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $car_id = (int)$_POST['car_id'];
        $customer_id = (int)$_POST['customer_id'];
        $sale_price = floatval($_POST['sale_price']);
        $sale_date = mysqli_real_escape_string($conn, $_POST['sale_date']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        
        $query = "INSERT INTO sales (car_id, customer_id, sale_price, sale_date, payment_method, payment_status, notes) 
            VALUES ($car_id, $customer_id, $sale_price, '$sale_date', '$payment_method', '$payment_status', '$notes')";
        
        if (mysqli_query($conn, $query)) {
            mysqli_query($conn, "UPDATE cars SET status = 'Sold' WHERE id = $car_id");
            $success = 'Sale recorded successfully!';
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT car_id FROM sales WHERE id = $id"));
        if (mysqli_query($conn, "DELETE FROM sales WHERE id = $id")) {
            mysqli_query($conn, "UPDATE cars SET status = 'Available' WHERE id = " . $sale['car_id']);
            $success = 'Sale deleted successfully!';
        }
    }
}

$sales = mysqli_query($conn, "
    SELECT s.*, c.make, c.model, c.year, cu.first_name, cu.last_name 
    FROM sales s
    JOIN cars c ON s.car_id = c.id
    JOIN customers cu ON s.customer_id = cu.id
    ORDER BY s.sale_date DESC
");

$available_cars = mysqli_query($conn, "SELECT id, make, model, year, price FROM cars WHERE status = 'Available' ORDER BY make, model");
$customers = mysqli_query($conn, "SELECT id, first_name, last_name FROM customers ORDER BY first_name, last_name");

$page_title = 'Sales Management';
include 'includes/header.php';
?>

<div class="content-area">
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="table-header">
            <h3>All Sales</h3>
            <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Record Sale</button>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Car</th>
                        <th>Customer</th>
                        <th>Sale Price</th>
                        <th>Date</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($sales) > 0): ?>
                        <?php while ($sale = mysqli_fetch_assoc($sales)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?></td>
                                <td><strong><?php echo formatCurrency($sale['sale_price']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                <td><?php echo $sale['payment_method']; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $sale['payment_status'] === 'Paid' ? 'success' : 
                                            ($sale['payment_status'] === 'Partial' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo $sale['payment_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="deleteSale(<?php echo $sale['id']; ?>)" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-handshake"></i><p>No sales recorded</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Record New Sale</h3>
            <button class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="car_id"><i class="fas fa-car"></i> Car</label>
                    <select name="car_id" id="car_id" required onchange="updatePrice()">
                        <option value="">Select a car</option>
                        <?php mysqli_data_seek($available_cars, 0); ?>
                        <?php while ($car = mysqli_fetch_assoc($available_cars)): ?>
                            <option value="<?php echo $car['id']; ?>" data-price="<?php echo $car['price']; ?>">
                                <?php echo htmlspecialchars($car['year'] . ' ' . $car['make'] . ' ' . $car['model'] . ' - ' . formatCurrency($car['price'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="customer_id"><i class="fas fa-user"></i> Customer</label>
                    <select name="customer_id" id="customer_id" required>
                        <option value="">Select a customer</option>
                        <?php mysqli_data_seek($customers, 0); ?>
                        <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="sale_price"><i class="fas fa-peso-sign"></i> Sale Price</label>
                        <input type="number" name="sale_price" id="sale_price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="sale_date"><i class="fas fa-calendar"></i> Sale Date</label>
                        <input type="date" name="sale_date" id="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_method"><i class="fas fa-credit-card"></i> Payment Method</label>
                        <select name="payment_method" id="payment_method" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Check">Check</option>
                            <option value="Financing">Financing</option>
                            <option value="Credit Card">Credit Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_status"><i class="fas fa-check-circle"></i> Payment Status</label>
                        <select name="payment_status" id="payment_status" required>
                            <option value="Paid">Paid</option>
                            <option value="Partial">Partial</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes"><i class="fas fa-sticky-note"></i> Notes</label>
                    <textarea name="notes" id="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Sale</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.querySelector('#modal form').reset();
    document.getElementById('sale_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('modal').classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

function updatePrice() {
    const select = document.getElementById('car_id');
    const price = select.options[select.selectedIndex].dataset.price;
    if (price) {
        document.getElementById('sale_price').value = price;
    }
}

function deleteSale(id) {
    if (confirm('Delete this sale? The car status will be reset to Available.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include 'includes/footer.php'; ?>
