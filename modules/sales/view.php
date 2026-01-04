<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT s.*, v.*, c.first_name, c.middle_name, c.last_name, c.phone, c.email, c.address,
          u.full_name as sold_by_name, l.name as location_name
          FROM sales s
          JOIN vehicles v ON s.vehicle_id = v.id
          JOIN customers c ON s.customer_id = c.id
          LEFT JOIN users u ON s.sold_by = u.id
          LEFT JOIN locations l ON s.location_id = l.id
          WHERE s.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Sale record not found.';
    header('Location: index.php');
    exit();
}

$sale = $result->fetch_assoc();
$stmt->close();

$page_title = 'Sale Invoice - ' . $sale['invoice_number'];
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-file-invoice"></i> Invoice: <?php echo escape_output($sale['invoice_number']); ?></h2>
        <div class="btn-group">
            <a href="edit.php?id=<?php echo $sale['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid var(--primary-color);">
            <h1><?php echo APP_NAME; ?></h1>
            <h2>SALES INVOICE</h2>
            <p><strong>Invoice #: <?php echo escape_output($sale['invoice_number']); ?></strong></p>
            <p>Date: <?php echo format_date($sale['sale_date']); ?></p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div>
                <h3>Customer Information</h3>
                <p>
                    <strong><?php echo escape_output(get_customer_name($sale)); ?></strong><br>
                    <?php echo escape_output($sale['address']); ?><br>
                    Phone: <?php echo format_phone($sale['phone']); ?><br>
                    <?php if (!empty($sale['email'])): ?>
                    Email: <?php echo escape_output($sale['email']); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <div>
                <h3>Sale Information</h3>
                <p>
                    Sold By: <?php echo escape_output($sale['sold_by_name']); ?><br>
                    Location: <?php echo escape_output($sale['location_name']); ?><br>
                    Payment Method: <?php echo escape_output($sale['payment_method']); ?><br>
                    Status: <?php echo status_badge($sale['payment_status']); ?>
                </p>
            </div>
        </div>
        
        <h3>Vehicle Details</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Stock #</th>
                        <th>Vehicle</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Mileage</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo escape_output($sale['stock_number']); ?></td>
                        <td><?php echo escape_output($sale['make'] . ' ' . $sale['model']); ?></td>
                        <td><?php echo escape_output($sale['year']); ?></td>
                        <td><?php echo escape_output($sale['color']); ?></td>
                        <td><?php echo number_format($sale['mileage']); ?> km</td>
                        <td><?php echo format_currency($sale['sale_price']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: var(--light-color); border-radius: 8px;">
            <table style="width: 100%; max-width: 500px; margin-left: auto;">
                <tr>
                    <td style="padding: 0.5rem 0;"><strong>Sale Price:</strong></td>
                    <td style="text-align: right;"><?php echo format_currency($sale['sale_price']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem 0;"><strong>Down Payment:</strong></td>
                    <td style="text-align: right;"><?php echo format_currency($sale['down_payment']); ?></td>
                </tr>
                <tr style="border-top: 2px solid var(--border-color);">
                    <td style="padding: 0.5rem 0;"><strong>Balance:</strong></td>
                    <td style="text-align: right;"><strong style="font-size: 1.2rem; color: var(--error-color);"><?php echo format_currency($sale['balance']); ?></strong></td>
                </tr>
            </table>
        </div>
        
        <?php if (!empty($sale['notes'])): ?>
        <div style="margin-top: 2rem;">
            <h3>Notes</h3>
            <p><?php echo nl2br(escape_output($sale['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 3rem; padding-top: 1rem; border-top: 1px solid var(--border-color); text-align: center; color: var(--text-light);">
            <p>Thank you for your business!</p>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
