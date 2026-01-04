<?php
/**
 * Services Index
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Services';
$db = getDb();

$status = sanitize($_GET['status'] ?? '');
$vehicleId = intval($_GET['vehicle_id'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));

$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "s.status = ?";
    $params[] = $status;
}

if ($vehicleId) {
    $where[] = "s.vehicle_id = ?";
    $params[] = $vehicleId;
}

$whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';

$countSql = "SELECT COUNT(*) as total FROM services s WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalServices = $stmt->fetch()['total'];

$pagination = getPagination($totalServices, $page, ITEMS_PER_PAGE);

$sql = "
    SELECT s.*, v.stock_number, v.make, v.model, v.year, v.plate_number
    FROM services s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE $whereClause
    ORDER BY s.service_date DESC, s.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $pagination['items_per_page'];
$params[] = $pagination['offset'];
$stmt->execute($params);
$services = $stmt->fetchAll();

$statuses = getEnumValues('services', 'status');

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo SITE_URL; ?>/services/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Service
        </a>
        <a href="<?php echo SITE_URL; ?>/services/export.php" class="btn btn-success">
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
        <a href="<?php echo SITE_URL; ?>/services/index.php" class="btn btn-outline-secondary">Clear</a>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="services-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th>Service Type</th>
                        <th>Description</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No services found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo formatDate($service['service_date']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($service['stock_number']); ?></strong>
                                    <br><?php echo htmlspecialchars($service['year'] . ' ' . $service['make'] . ' ' . $service['model']); ?>
                                </td>
                                <td><?php echo ucwords(str_replace('_', ' ', $service['service_type'])); ?></td>
                                <td><?php echo truncate($service['description'], 50); ?></td>
                                <td><strong><?php echo formatCurrency($service['total_cost']); ?></strong></td>
                                <td><?php echo getStatusBadge($service['status']); ?></td>
                                <td class="no-export">
                                    <div class="action-buttons">
                                        <a href="<?php echo SITE_URL; ?>/services/view.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasAnyRole(['admin', 'manager'])): ?>
                                            <a href="<?php echo SITE_URL; ?>/services/edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasRole('admin')): ?>
                                            <a href="<?php echo SITE_URL; ?>/services/delete.php?id=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-danger btn-delete" data-confirm="Delete this service record?">
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
        
        <?php echo renderPagination($pagination, SITE_URL . '/services/index.php' . (!empty($status) ? '?status=' . $status . '&' : '?')); ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
