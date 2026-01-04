<?php
/**
 * Generate Sales Invoice (Printable)
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$saleId = intval($_GET['id'] ?? 0);

if (!$saleId) {
    die('Invalid sale ID');
}

$db = getDb();

$stmt = $db->prepare("
    SELECT s.*, v.stock_number, v.make, v.model, v.year, v.variant, v.color, v.plate_number, v.chassis_number,
           c.first_name, c.last_name, c.middle_name, c.phone, c.email, c.address, c.city, c.province, c.zip_code,
           u.full_name as agent_name
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.sales_agent_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$saleId]);
$sale = $stmt->fetch();

if (!$sale) {
    die('Sale not found');
}

$pageTitle = 'Invoice #' . str_pad($sale['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .company-info h1 { font-size: 24px; margin-bottom: 10px; color: #0066cc; }
        .company-info p { margin: 5px 0; }
        .invoice-info { text-align: right; }
        .invoice-info h2 { font-size: 28px; margin-bottom: 10px; }
        .invoice-info p { margin: 5px 0; }
        .invoice-number { font-size: 18px; font-weight: bold; }
        .row { display: flex; gap: 20px; margin-bottom: 20px; }
        .col { flex: 1; }
        .section-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 11px; }
        .status.completed { background: #d4edda; color: #155724; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d1ecf1; color: #0c5460; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc; text-align: center; font-size: 11px; }
        @media print {
            .container { margin: 0; padding: 0; max-width: 100%; }
            body { font-size: 11px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>Pamikil Auto Sales</h1>
                <p>123 Auto Street, Makati City, Metro Manila</p>
                <p>Phone: 09171234567 | Email: info@pamikil.com</p>
                <p>VAT Reg TIN: 123-456-789-000</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> <?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Date:</strong> <?php echo formatDate($sale['sale_date']); ?></p>
                <p><strong>Status:</strong> <span class="status <?php echo $sale['status']; ?>"><?php echo ucfirst($sale['status']); ?></span></p>
            </div>
        </div>

        <!-- Customer & Vehicle Info -->
        <div class="row">
            <div class="col">
                <div class="section-title">BILL TO</div>
                <p><strong><?php echo htmlspecialchars($sale['last_name'] . ', ' . $sale['first_name']); ?></strong></p>
                <?php if ($sale['middle_name']): ?>
                    <p><?php echo htmlspecialchars($sale['middle_name']); ?></p>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($sale['address']); ?></p>
                <p><?php echo htmlspecialchars($sale['city']); ?>, <?php echo htmlspecialchars($sale['province']); ?></p>
                <?php if ($sale['zip_code']): ?>
                    <p><?php echo htmlspecialchars($sale['zip_code']); ?></p>
                <?php endif; ?>
                <p>Phone: <?php echo htmlspecialchars(formatPHMobileNumber($sale['phone'])); ?></p>
                <?php if ($sale['email']): ?>
                    <p>Email: <?php echo htmlspecialchars($sale['email']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col">
                <div class="section-title">VEHICLE DETAILS</div>
                <p><strong><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></strong></p>
                <?php if ($sale['variant']): ?>
                    <p>Variant: <?php echo htmlspecialchars($sale['variant']); ?></p>
                <?php endif; ?>
                <p>Color: <?php echo htmlspecialchars($sale['color']); ?></p>
                <p>Stock #: <?php echo htmlspecialchars($sale['stock_number']); ?></p>
                <?php if ($sale['plate_number']): ?>
                    <p>Plate #: <?php echo htmlspecialchars($sale['plate_number']); ?></p>
                <?php endif; ?>
                <?php if ($sale['chassis_number']): ?>
                    <p>Chassis #: <?php echo htmlspecialchars($sale['chassis_number']); ?></p>
                <?php endif; ?>
                <p>Sales Agent: <?php echo htmlspecialchars($sale['agent_name']); ?></p>
            </div>
        </div>

        <!-- Invoice Items -->
        <table>
            <thead>
                <tr>
                    <th width="60%">Description</th>
                    <th width="40%" style="text-align: right;">Amount (₱)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></strong>
                        <?php if ($sale['variant']): ?>
                            <br><?php echo htmlspecialchars($sale['variant']); ?>
                        <?php endif; ?>
                        <br>Stock Number: <?php echo htmlspecialchars($sale['stock_number']); ?>
                    </td>
                    <td style="text-align: right;"><?php echo number_format($sale['sale_price'], 2); ?></td>
                </tr>
                <?php if ($sale['down_payment'] > 0): ?>
                    <tr>
                        <td>Less: Down Payment</td>
                        <td style="text-align: right; color: red;">(<?php echo number_format($sale['down_payment'], 2); ?>)</td>
                    </tr>
                    <tr>
                        <td><strong>Balance Due</strong></td>
                        <td style="text-align: right;"><strong><?php echo number_format($sale['sale_price'] - $sale['down_payment'], 2); ?></strong></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Financing Details -->
        <div class="row">
            <div class="col">
                <div class="section-title">FINANCING DETAILS</div>
                <p><strong>Financing Type:</strong> <?php echo ucwords(str_replace('_', ' ', $sale['financing_type'])); ?></p>
                <?php if ($sale['bank_name']): ?>
                    <p><strong>Bank:</strong> <?php echo htmlspecialchars($sale['bank_name']); ?></p>
                <?php endif; ?>
                <?php if ($sale['financing_term_months']): ?>
                    <p><strong>Term:</strong> <?php echo $sale['financing_term_months']; ?> months</p>
                <?php endif; ?>
                <?php if ($sale['monthly_amortization']): ?>
                    <p><strong>Monthly Amortization:</strong> ₱<?php echo number_format($sale['monthly_amortization'], 2); ?></p>
                <?php endif; ?>
            </div>
            <div class="col">
                <div class="section-title">PAYMENT SUMMARY</div>
                <p><strong>Total Sale Amount:</strong> ₱<?php echo number_format($sale['sale_price'], 2); ?></p>
                <?php if ($sale['down_payment'] > 0): ?>
                    <p><strong>Down Payment:</strong> ₱<?php echo number_format($sale['down_payment'], 2); ?></p>
                    <p><strong>Balance:</strong> ₱<?php echo number_format($sale['sale_price'] - $sale['down_payment'], 2); ?></p>
                <?php endif; ?>
                <p class="total" style="margin-top: 15px; font-size: 20px;">TOTAL: ₱<?php echo number_format($sale['sale_price'], 2); ?></p>
            </div>
        </div>

        <?php if ($sale['notes']): ?>
        <div style="margin-top: 30px;">
            <div class="section-title">NOTES / REMARKS</div>
            <p><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Terms and Conditions:</strong></p>
            <p>1. All sales are final. No returns or exchanges.</p>
            <p>2. Full payment must be received before vehicle release.</p>
            <p>3. Vehicle sold "as is" unless specified warranty applies.</p>
            <p>4. This invoice is not valid without official company seal and authorized signature.</p>
            <br>
            <p><strong>Thank you for your business!</strong></p>
            <p>For inquiries, please call us at 09171234567</p>
            <br>
            <p style="color: #666;">Generated: <?php echo formatDateTime(date('Y-m-d H:i:s')); ?></p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px; padding: 20px;">
        <button onclick="window.print();" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
            <i class="bi bi-printer"></i> Print Invoice
        </button>
        <button onclick="window.close();" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>
