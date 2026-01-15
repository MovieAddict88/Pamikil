<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$where_sql = '';
$params = [];
$types = '';

if (!empty($status_filter)) {
    $where_sql = "WHERE sh.status = ?";
    $params[] = $status_filter;
    $types = 's';
}

$count_query = "SELECT COUNT(*) as total FROM service_history sh $where_sql";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = get_pagination($total_records, $page);

$query = "SELECT sh.*, v.make, v.model, v.year, v.stock_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name
          FROM service_history sh
          JOIN vehicles v ON sh.vehicle_id = v.id
          LEFT JOIN customers c ON sh.customer_id = c.id
          $where_sql
          ORDER BY sh.service_date DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$services = $stmt->get_result();
$stmt->close();

$page_title = 'Service Management';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-wrench"></i> Service Management</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Schedule Service
        </a>
    </div>
    <div class="card-body">
        <div class="search-filter">
            <form method="GET" action="">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Scheduled" <?php echo $status_filter == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </form>
            <?php if (!empty($status_filter)): ?>
            <a href="index.php" class="btn btn-secondary">Clear Filter</a>
            <?php endif; ?>
        </div>
        
        <?php if ($services->num_rows > 0): ?>
        <div class="table-responsive">
            <table id="services-table">
                <thead>
                    <tr>
                        <th>Service #</th>
                        <th>Vehicle</th>
                        <th>Customer</th>
                        <th>Service Type</th>
                        <th>Service Date</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($service = $services->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo escape_output($service['service_number']); ?></strong></td>
                        <td><?php echo escape_output($service['year'] . ' ' . $service['make'] . ' ' . $service['model']); ?></td>
                        <td><?php echo escape_output($service['customer_name']) ?: 'N/A'; ?></td>
                        <td><?php echo escape_output($service['service_type']); ?></td>
                        <td><?php echo format_date($service['service_date']); ?></td>
                        <td><?php echo format_currency($service['cost']); ?></td>
                        <td><?php echo status_badge($service['status']); ?></td>
                        <td class="table-actions no-export">
                            <a href="view.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $pagination['total_pages']): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="btn-group mt-3">
            <button onclick="exportTableToCSV('services-table', 'services_<?php echo date('Y-m-d'); ?>.csv')" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
        </div>
        
        <?php else: ?>
        <p class="text-center text-muted">No service records found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
