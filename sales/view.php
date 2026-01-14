<?php
/**
 * View Sale
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'Sale Details';
$db = getDb();
$saleId = intval($_GET['id'] ?? 0);

if (!$saleId) {
    setFlashMessage('error', 'Invalid sale ID');
    redirect(SITE_URL . '/sales/index.php');
    exit;
}

$stmt = $db->prepare("
    SELECT s.*, v.stock_number, v.make, v.model, v.year, v.plate_number,
           c.first_name, c.last_name, c.middle_name, c.phone, c.email, c.address, c.city, c.province,
           u.full_name as agent_name, u.phone as agent_phone
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.sales_agent_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$saleId]);
$sale = $stmt->fetch();

if (!$sale) {
    setFlashMessage('error', 'Sale not found');
    redirect(SITE_URL . '/sales/index.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?php echo SITE_URL; ?>/sales/index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Sales
    </a>
    <div class="btn-group">
        <a href="<?php echo SITE_URL; ?>/sales/invoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-primary" target="_blank">
            <i class="bi bi-receipt"></i> Print Invoice
        </a>
        <?php if ($sale['status'] === 'pending' && hasAnyRole(['admin', 'manager'])): ?>
            <a href="<?php echo SITE_URL; ?>/sales/approve.php?id=<?php echo $sale['id']; ?>" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Approve
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="invoice">
    <div class="invoice-header">
        <div>
            <h2>Pamikil Auto Sales</h2>
            <p>123 Auto Street, Makati City, Metro Manila</p>
            <p>Phone: 09171234567 | Email: info@pamikil.com</p>
        </div>
        <div style="text-align: right;">
            <h1>INVOICE</h1>
            <p><strong>Invoice #:</strong> <?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></p>
            <p><strong>Date:</strong> <?php echo formatDate($sale['sale_date']); ?></p>
            <p><strong>Status:</strong> <?php echo getStatusBadge($sale['status']); ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <h5>Customer Information</h5>
            <p><strong><?php echo htmlspecialchars($sale['last_name'] . ', ' . $sale['first_name']); ?></strong></p>
            <?php if ($sale['middle_name']): ?>
                <p><?php echo htmlspecialchars($sale['middle_name']); ?></p>
            <?php endif; ?>
            <p><?php echo htmlspecialchars($sale['address']); ?></p>
            <p><?php echo htmlspecialchars($sale['city'] . ', ' . $sale['province']); ?></p>
            <p><?php echo htmlspecialchars(formatPHMobileNumber($sale['phone'])); ?></p>
            <?php if ($sale['email']): ?>
                <p><?php echo htmlspecialchars($sale['email']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <h5>Vehicle Information</h5>
            <p><strong><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></strong></p>
            <p>Stock #: <?php echo htmlspecialchars($sale['stock_number']); ?></p>
            <?php if ($sale['plate_number']): ?>
                <p>Plate #: <?php echo htmlspecialchars($sale['plate_number']); ?></p>
            <?php endif; ?>
            <p>Sales Agent: <?php echo htmlspecialchars($sale['agent_name']); ?></p>
        </div>
    </div>

    <div class="mt-4">
        <h5>Payment Details</h5>
        <table class="table">
            <tr>
                <th width="70%">Description</th>
                <th width="30%" style="text-align: right;">Amount</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                <td style="text-align: right;"><?php echo formatCurrency($sale['sale_price']); ?></td>
            </tr>
            <?php if ($sale['down_payment'] > 0): ?>
            <tr>
                <td>Less: Down Payment</td>
                <td style="text-align: right; color: red;">(<?php echo formatCurrency($sale['down_payment']); ?>)</td>
            </tr>
            <?php endif; ?>
        </table>
        
        <div class="invoice-total">
            <p><strong>Financing Type:</strong> <?php echo ucwords(str_replace('_', ' ', $sale['financing_type'])); ?></p>
            <?php if ($sale['bank_name']): ?>
                <p><strong>Bank:</strong> <?php echo htmlspecialchars($sale['bank_name']); ?></p>
            <?php endif; ?>
            <?php if ($sale['financing_term_months']): ?>
                <p><strong>Term:</strong> <?php echo $sale['financing_term_months']; ?> months</p>
            <?php endif; ?>
            <?php if ($sale['monthly_amortization']): ?>
                <p><strong>Monthly Amortization:</strong> <?php echo formatCurrency($sale['monthly_amortization']); ?></p>
            <?php endif; ?>
            <p class="total">Total Amount: <?php echo formatCurrency($sale['sale_price']); ?></p>
        </div>
    </div>

    <?php if ($sale['notes']): ?>
    <div class="mt-4">
        <h5>Notes</h5>
        <p><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
