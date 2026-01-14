<?php
/**
 * Sales Index
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'Sales';
$db = getDb();

$status = sanitize($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));

$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "s.status = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';

$countSql = "SELECT COUNT(*) as total FROM sales s WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalSales = $stmt->fetch()['total'];

$pagination = getPagination($totalSales, $page, ITEMS_PER_PAGE);

$sql = "
    SELECT s.*, v.make, v.model, v.year, v.stock_number,
           c.first_name, c.last_name, c.phone,
           u.full_name as agent_name, l.name as location_name
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.sales_agent_id = u.id
    LEFT JOIN locations l ON s.location_id = l.id
    WHERE $whereClause
    ORDER BY s.sale_date DESC, s.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $pagination['items_per_page'];
$params[] = $pagination['offset'];
$stmt->execute($params);
$sales = $stmt->fetchAll();

$statuses = getEnumValues('sales', 'status');

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo SITE_URL; ?>/sales/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Sale
        </a>
        <a href="<?php echo SITE_URL; ?>/sales/export.php" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
    
    <form method="GET" action="" class="d-flex gap-2">
        <select name="status" class="form-select" style="width: auto;">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo $status === $st ? 'selected' : ''; ?>>
                    <?php echo ucwords(str_replace('_', ' ', $st)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="<?php echo SITE_URL; ?>/sales/index.php" class="btn btn-outline-secondary">Clear</a>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="sales-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Agent</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No sales found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo formatDate($sale['sale_date']); ?></td>
                                <td><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                                <td><?php echo htmlspecialchars($sale['last_name'] . ', ' . $sale['first_name']); ?></td>
                                <td><strong><?php echo formatCurrency($sale['sale_price']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sale['agent_name']); ?></td>
                                <td><?php echo getStatusBadge($sale['status']); ?></td>
                                <td class="no-export">
                                    <div class="action-buttons">
                                        <a href="<?php echo SITE_URL; ?>/sales/view.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/sales/invoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php echo renderPagination($pagination, SITE_URL . '/sales/index.php' . (!empty($status) ? '?status=' . $status . '&' : '?')); ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
