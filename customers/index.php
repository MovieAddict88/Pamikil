<?php
/**
 * Customers Index
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Customers';
$db = getDb();

$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));

$where = ["status != 'inactive'"];
$params = [];

if (!empty($status)) {
    $where[] = "status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

$whereClause = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) as total FROM customers WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalCustomers = $stmt->fetch()['total'];

$pagination = getPagination($totalCustomers, $page, ITEMS_PER_PAGE);

$sql = "
    SELECT * FROM customers
    WHERE $whereClause
    ORDER BY last_name, first_name ASC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $pagination['items_per_page'];
$params[] = $pagination['offset'];
$stmt->execute($params);
$customers = $stmt->fetchAll();

$statuses = getEnumValues('customers', 'status');

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo SITE_URL; ?>/customers/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Customer
        </a>
        <a href="<?php echo SITE_URL; ?>/customers/export.php" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
    
    <form method="GET" action="" class="d-flex gap-2">
        <select name="status" class="form-select" style="width: auto;">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $st): ?>
                <?php if ($st !== 'inactive'): ?>
                    <option value="<?php echo $st; ?>" <?php echo $status === $st ? 'selected' : ''; ?>>
                        <?php echo ucwords(str_replace('_', ' ', $st)); ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" class="form-control" placeholder="Search customers..." 
               value="<?php echo htmlspecialchars($search); ?>" style="width: 200px;">
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="<?php echo SITE_URL; ?>/customers/index.php" class="btn btn-outline-secondary">Clear</a>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="customers-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Identification</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="6" class="text-center text-muted">No customers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['last_name'] . ', ' . $customer['first_name']); ?></strong>
                                    <?php if (!empty($customer['middle_name'])): ?>
                                        <br><small><?php echo htmlspecialchars($customer['middle_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="bi bi-telephone"></i> <?php echo htmlspecialchars(formatPHMobileNumber($customer['phone'])); ?>
                                    <?php if (!empty($customer['email'])): ?>
                                        <br><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($customer['city'] ?? ''); ?>
                                    <?php if (!empty($customer['province'])): ?>
                                        <br><small><?php echo htmlspecialchars($customer['province']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($customer['identification_type'])): ?>
                                        <?php echo ucfirst(str_replace('_', ' ', $customer['identification_type'])); ?>
                                        <br><small><?php echo htmlspecialchars($customer['identification_number']); ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo getStatusBadge($customer['status']); ?></td>
                                <td class="no-export">
                                    <div class="action-buttons">
                                        <a href="<?php echo SITE_URL; ?>/customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasAnyRole(['admin', 'manager'])): ?>
                                            <a href="<?php echo SITE_URL; ?>/customers/edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasRole('admin')): ?>
                                            <a href="<?php echo SITE_URL; ?>/customers/delete.php?id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-sm btn-danger btn-delete" data-confirm="Delete this customer?">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php echo renderPagination($pagination, SITE_URL . '/customers/index.php' . (!empty($status) || !empty($search) ? '?' . http_build_query(['status' => $status, 'search' => $search]) . '&' : '?')); ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
