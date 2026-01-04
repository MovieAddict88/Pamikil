<?php
/**
 * View Customer
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Customer Details';
$db = getDb();
$customerId = intval($_GET['id'] ?? 0);

if (!$customerId) {
    setFlashMessage('error', 'Invalid customer ID');
    redirect(SITE_URL . '/customers/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlashMessage('error', 'Customer not found');
    redirect(SITE_URL . '/customers/index.php');
    exit;
}

$stmt = $db->prepare("
    SELECT s.*, v.make, v.model, v.year
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE s.customer_id = ?
    ORDER BY s.sale_date DESC
    LIMIT 10
");
$stmt->execute([$customerId]);
$salesHistory = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT * FROM customer_communications
    WHERE customer_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$customerId]);
$communications = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?php echo SITE_URL; ?>/customers/index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Customers
    </a>
    <?php if (hasAnyRole(['admin', 'manager'])): ?>
        <div class="btn-group">
            <a href="<?php echo SITE_URL; ?>/customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?php echo SITE_URL; ?>/sales/create.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-success">
                <i class="bi bi-cart-plus"></i> New Sale
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="35%">Full Name</th>
                        <td>
                            <strong><?php echo htmlspecialchars($customer['last_name'] . ', ' . $customer['first_name']); ?></strong>
                            <?php if (!empty($customer['middle_name'])): ?>
                                <br><?php echo htmlspecialchars($customer['middle_name']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo getStatusBadge($customer['status']); ?></td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td><?php echo formatDateTime($customer['created_at']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Contact Information</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="35%">Mobile</th>
                        <td><?php echo htmlspecialchars(formatPHMobileNumber($customer['phone'])); ?></td>
                    </tr>
                    <?php if ($customer['email']): ?>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Address</div>
            <div class="card-body">
                <p><strong><?php echo htmlspecialchars($customer['address']); ?></strong></p>
                <p><?php echo htmlspecialchars($customer['city']); ?></p>
                <p><?php echo htmlspecialchars($customer['province']); ?></p>
                <?php if ($customer['zip_code']): ?>
                    <p><strong>ZIP:</strong> <?php echo htmlspecialchars($customer['zip_code']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($customer['identification_type'] || $customer['identification_number']): ?>
        <div class="card">
            <div class="card-header">Identification</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="35%">ID Type</th>
                        <td><?php echo $customer['identification_type'] ? ucwords(str_replace('_', ' ', $customer['identification_type'])) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>ID Number</th>
                        <td><?php echo htmlspecialchars($customer['identification_number'] ?? '-'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($customer['notes']): ?>
        <div class="card">
            <div class="card-header">Notes</div>
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($customer['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($salesHistory)): ?>
<div class="card mt-4">
    <div class="card-header">
        Sales History
        <a href="<?php echo SITE_URL; ?>/sales/index.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary float-end">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th>Amount</th>
                        <th>Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesHistory as $sale): ?>
                        <tr>
                            <td><?php echo formatDate($sale['sale_date']); ?></td>
                            <td><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                            <td><?php echo formatCurrency($sale['sale_price']); ?></td>
                            <td><?php echo htmlspecialchars($sale['agent_name'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
