<?php
/**
 * Vehicles Index
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Vehicles';
$db = getDb();

// Get filters
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));

// Build query
$where = ["v.status != 'inactive'"];
$params = [];

if (!empty($status)) {
    $where[] = "v.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(v.stock_number LIKE ? OR v.make LIKE ? OR v.model LIKE ? OR v.plate_number LIKE ? OR v.year LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) as total FROM vehicles v WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalVehicles = $stmt->fetch()['total'];

// Get pagination
$pagination = getPagination($totalVehicles, $page, ITEMS_PER_PAGE);

// Get vehicles
$sql = "
    SELECT v.*, l.name as location_name
    FROM vehicles v
    LEFT JOIN locations l ON v.location_id = l.id
    WHERE $whereClause
    ORDER BY v.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $pagination['items_per_page'];
$params[] = $pagination['offset'];
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Get status options
$statuses = getEnumValues('vehicles', 'status');

include __DIR__ . '/../includes/header.php';
?>

<!-- Page Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo SITE_URL; ?>/vehicles/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Vehicle
        </a>
        <a href="<?php echo SITE_URL; ?>/vehicles/export.php" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
    
    <!-- Filters -->
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
        <input type="text" name="search" class="form-control" placeholder="Search vehicles..." 
               value="<?php echo htmlspecialchars($search); ?>" style="width: 200px;">
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="<?php echo SITE_URL; ?>/vehicles/index.php" class="btn btn-outline-secondary">Clear</a>
    </form>
</div>

<!-- Vehicles Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="vehicles-table">
                <thead>
                    <tr>
                        <th>Stock #</th>
                        <th>Vehicle</th>
                        <th>Details</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicles)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No vehicles found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($vehicle['stock_number']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                    <?php if (!empty($vehicle['variant'])): ?>
                                        <br><small><?php echo htmlspecialchars($vehicle['variant']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(ucwords($vehicle['color'])); ?> |
                                    <?php echo htmlspecialchars(ucwords($vehicle['body_type'])); ?> |
                                    <?php echo htmlspecialchars(ucwords($vehicle['transmission'])); ?>
                                    <?php if (!empty($vehicle['plate_number'])): ?>
                                        <br><small>Plate: <?php echo htmlspecialchars($vehicle['plate_number']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo formatCurrency($vehicle['selling_price']); ?></strong>
                                    <?php if ($vehicle['selling_price'] > $vehicle['purchase_price']): ?>
                                        <br><small class="text-success">
                                            +<?php echo formatCurrency($vehicle['selling_price'] - $vehicle['purchase_price']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo getStatusBadge($vehicle['status']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['location_name'] ?? 'N/A'); ?></td>
                                <td class="no-export">
                                    <div class="action-buttons">
                                        <a href="<?php echo SITE_URL; ?>/vehicles/view.php?id=<?php echo $vehicle['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasAnyRole(['admin', 'manager'])): ?>
                                            <a href="<?php echo SITE_URL; ?>/vehicles/edit.php?id=<?php echo $vehicle['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasRole('admin')): ?>
                                            <a href="<?php echo SITE_URL; ?>/vehicles/delete.php?id=<?php echo $vehicle['id']; ?>" 
                                               class="btn btn-sm btn-danger btn-delete" title="Delete"
                                               data-confirm="Are you sure you want to delete this vehicle?">
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
        
        <!-- Pagination -->
        <?php echo renderPagination($pagination, SITE_URL . '/vehicles/index.php' . (!empty($status) || !empty($search) ? '?' . http_build_query(['status' => $status, 'search' => $search]) . '&' : '?')); ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
