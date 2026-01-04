<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(stock_number LIKE ? OR make LIKE ? OR model LIKE ? OR year LIKE ? OR color LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= 'sssss';
}

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$count_query = "SELECT COUNT(*) as total FROM vehicles $where_sql";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = get_pagination($total_records, $page);

$query = "SELECT v.*, l.name as location_name 
          FROM vehicles v 
          LEFT JOIN locations l ON v.location_id = l.id 
          $where_sql 
          ORDER BY v.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$vehicles = $stmt->get_result();
$stmt->close();

$page_title = 'Vehicle Inventory';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-car"></i> Vehicle Inventory</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Vehicle
        </a>
    </div>
    <div class="card-body">
        <div class="search-filter">
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" class="form-control" placeholder="Search by stock number, make, model, year, or color..." value="<?php echo escape_output($search); ?>">
                </form>
            </div>
            <form method="GET" action="">
                <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?php echo escape_output($search); ?>">
                <?php endif; ?>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Available" <?php echo $status_filter == 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Sold" <?php echo $status_filter == 'Sold' ? 'selected' : ''; ?>>Sold</option>
                    <option value="Reserved" <?php echo $status_filter == 'Reserved' ? 'selected' : ''; ?>>Reserved</option>
                    <option value="In Service" <?php echo $status_filter == 'In Service' ? 'selected' : ''; ?>>In Service</option>
                    <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </form>
            <?php if (!empty($search) || !empty($status_filter)): ?>
            <a href="index.php" class="btn btn-secondary">Clear Filters</a>
            <?php endif; ?>
        </div>
        
        <?php if ($vehicles->num_rows > 0): ?>
        <div class="table-responsive">
            <table id="vehicles-table">
                <thead>
                    <tr>
                        <th>Stock #</th>
                        <th>Vehicle</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Mileage</th>
                        <th>Selling Price</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escape_output($vehicle['stock_number']); ?></td>
                        <td><strong><?php echo escape_output($vehicle['make'] . ' ' . $vehicle['model']); ?></strong></td>
                        <td><?php echo escape_output($vehicle['year']); ?></td>
                        <td><?php echo escape_output($vehicle['color']); ?></td>
                        <td><?php echo number_format($vehicle['mileage']); ?> km</td>
                        <td><?php echo format_currency($vehicle['selling_price']); ?></td>
                        <td><?php echo status_badge($vehicle['status']); ?></td>
                        <td><?php echo escape_output($vehicle['location_name']); ?></td>
                        <td class="table-actions no-export">
                            <a href="view.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (has_role(['Admin', 'Manager'])): ?>
                            <a href="delete.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-error btn-delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $pagination['total_pages']): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="btn-group mt-3">
            <button onclick="exportTableToCSV('vehicles-table', 'vehicles_<?php echo date('Y-m-d'); ?>.csv')" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
        
        <?php else: ?>
        <p class="text-center text-muted">No vehicles found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
