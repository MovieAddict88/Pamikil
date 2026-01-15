<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Customer not found.';
    header('Location: index.php');
    exit();
}

$customer = $result->fetch_assoc();
$stmt->close();

$sales_query = "SELECT s.*, v.make, v.model, v.year FROM sales s JOIN vehicles v ON s.vehicle_id = v.id WHERE s.customer_id = ?";
$sales_stmt = $conn->prepare($sales_query);
$sales_stmt->bind_param("i", $id);
$sales_stmt->execute();
$sales = $sales_stmt->get_result();
$sales_stmt->close();

$services_query = "SELECT sh.*, v.make, v.model FROM service_history sh JOIN vehicles v ON sh.vehicle_id = v.id WHERE sh.customer_id = ?";
$services_stmt = $conn->prepare($services_query);
$services_stmt->bind_param("i", $id);
$services_stmt->execute();
$services = $services_stmt->get_result();
$services_stmt->close();

$comm_query = "SELECT * FROM communication_logs WHERE customer_id = ? ORDER BY communication_date DESC";
$comm_stmt = $conn->prepare($comm_query);
$comm_stmt->bind_param("i", $id);
$comm_stmt->execute();
$communications = $comm_stmt->get_result();
$comm_stmt->close();

$page_title = get_customer_name($customer);
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user"></i> <?php echo escape_output(get_customer_name($customer)); ?></h2>
        <div class="btn-group">
            <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div>
                <h3>Personal Information</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Full Name:</td>
                        <td><?php echo escape_output(get_customer_name($customer)); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Phone:</td>
                        <td><?php echo format_phone($customer['phone']); ?></td>
                    </tr>
                    <?php if (!empty($customer['alternate_phone'])): ?>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Alt. Phone:</td>
                        <td><?php echo format_phone($customer['alternate_phone']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Email:</td>
                        <td><?php echo escape_output($customer['email']) ?: 'N/A'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3>Address</h3>
                <p>
                    <?php if (!empty($customer['address'])): ?>
                        <?php echo nl2br(escape_output($customer['address'])); ?><br>
                    <?php endif; ?>
                    <?php echo escape_output($customer['city']); ?><?php echo $customer['province'] ? ', ' . escape_output($customer['province']) : ''; ?>
                    <?php echo $customer['postal_code'] ? ' ' . escape_output($customer['postal_code']) : ''; ?>
                </p>
                
                <h3 class="mt-3">Identification</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">ID Type:</td>
                        <td><?php echo escape_output($customer['id_type']) ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">ID Number:</td>
                        <td><?php echo escape_output($customer['id_number']) ?: 'N/A'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (!empty($customer['notes'])): ?>
        <div style="margin-top: 2rem;">
            <h3>Notes</h3>
            <p><?php echo nl2br(escape_output($customer['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($sales->num_rows > 0): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-shopping-cart"></i> Purchase History</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Vehicle</th>
                        <th>Sale Price</th>
                        <th>Payment Status</th>
                        <th>Sale Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sale = $sales->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escape_output($sale['invoice_number']); ?></td>
                        <td><?php echo escape_output($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                        <td><?php echo format_currency($sale['sale_price']); ?></td>
                        <td><?php echo status_badge($sale['payment_status']); ?></td>
                        <td><?php echo format_date($sale['sale_date']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($services->num_rows > 0): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-wrench"></i> Service History</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Service #</th>
                        <th>Vehicle</th>
                        <th>Service Type</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($service = $services->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escape_output($service['service_number']); ?></td>
                        <td><?php echo escape_output($service['make'] . ' ' . $service['model']); ?></td>
                        <td><?php echo escape_output($service['service_type']); ?></td>
                        <td><?php echo format_date($service['service_date']); ?></td>
                        <td><?php echo status_badge($service['status']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($communications->num_rows > 0): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-comments"></i> Communication History</h2>
    </div>
    <div class="card-body">
        <?php while ($comm = $communications->fetch_assoc()): ?>
        <div style="border-left: 3px solid var(--primary-color); padding: 1rem; margin-bottom: 1rem; background: var(--light-color);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <strong><?php echo escape_output($comm['communication_type']); ?>: <?php echo escape_output($comm['subject']); ?></strong>
                <span class="text-muted"><?php echo format_datetime($comm['communication_date']); ?></span>
            </div>
            <p><?php echo nl2br(escape_output($comm['message'])); ?></p>
            <?php if ($comm['status'] != 'Completed'): ?>
            <span class="badge badge-warning"><?php echo escape_output($comm['status']); ?></span>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
