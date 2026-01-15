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
    $where_clauses[] = "(s.invoice_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR CONCAT(v.make, ' ', v.model) LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.payment_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$count_query = "SELECT COUNT(*) as total FROM sales s JOIN vehicles v ON s.vehicle_id = v.id JOIN customers c ON s.customer_id = c.id $where_sql";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = get_pagination($total_records, $page);

$query = "SELECT s.*, v.make, v.model, v.year, v.stock_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name, u.full_name as sold_by_name
          FROM sales s
          JOIN vehicles v ON s.vehicle_id = v.id
          JOIN customers c ON s.customer_id = c.id
          LEFT JOIN users u ON s.sold_by = u.id
          $where_sql
          ORDER BY s.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sales = $stmt->get_result();
$stmt->close();

$page_title = 'Sales Management';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-shopping-cart"></i> Sales Management</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Sale
        </a>
    </div>
    <div class="card-body">
        <div class="search-filter">
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" class="form-control" placeholder="Search by invoice, customer, or vehicle..." value="<?php echo escape_output($search); ?>">
                </form>
            </div>
            <form method="GET" action="">
                <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?php echo escape_output($search); ?>">
                <?php endif; ?>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Partial" <?php echo $status_filter == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </form>
            <?php if (!empty($search) || !empty($status_filter)): ?>
            <a href="index.php" class="btn btn-secondary">Clear Filters</a>
            <?php endif; ?>
        </div>
        
        <?php if ($sales->num_rows > 0): ?>
        <div class="table-responsive">
            <table id="sales-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Vehicle</th>
                        <th>Customer</th>
                        <th>Sale Price</th>
                        <th>Down Payment</th>
                        <th>Balance</th>
                        <th>Payment Status</th>
                        <th>Sale Date</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sale = $sales->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo escape_output($sale['invoice_number']); ?></strong></td>
                        <td><?php echo escape_output($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                        <td><?php echo escape_output($sale['customer_name']); ?></td>
                        <td><?php echo format_currency($sale['sale_price']); ?></td>
                        <td><?php echo format_currency($sale['down_payment']); ?></td>
                        <td><?php echo format_currency($sale['balance']); ?></td>
                        <td><?php echo status_badge($sale['payment_status']); ?></td>
                        <td><?php echo format_date($sale['sale_date']); ?></td>
                        <td class="table-actions no-export">
                            <a href="view.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (has_role(['Admin'])): ?>
                            <a href="delete.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-error btn-delete" title="Delete">
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
            <button onclick="exportTableToCSV('sales-table', 'sales_<?php echo date('Y-m-d'); ?>.csv')" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
        
        <?php else: ?>
        <p class="text-center text-muted">No sales recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
