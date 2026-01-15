<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$where_sql = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_sql = "WHERE first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ?";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = 'ssss';
}

$count_query = "SELECT COUNT(*) as total FROM customers $where_sql";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = get_pagination($total_records, $page);

$query = "SELECT * FROM customers $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$customers = $stmt->get_result();
$stmt->close();

$page_title = 'Customer Management';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-users"></i> Customer Management</h2>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add Customer
        </a>
    </div>
    <div class="card-body">
        <div class="search-filter">
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, phone, or email..." value="<?php echo escape_output($search); ?>">
                </form>
            </div>
            <?php if (!empty($search)): ?>
            <a href="index.php" class="btn btn-secondary">Clear Search</a>
            <?php endif; ?>
        </div>
        
        <?php if ($customers->num_rows > 0): ?>
        <div class="table-responsive">
            <table id="customers-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Date Added</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo escape_output(get_customer_name($customer)); ?></strong></td>
                        <td><?php echo format_phone($customer['phone']); ?></td>
                        <td><?php echo escape_output($customer['email']); ?></td>
                        <td><?php echo escape_output($customer['city'] ? $customer['city'] . ', ' . $customer['province'] : 'N/A'); ?></td>
                        <td><?php echo format_date($customer['created_at']); ?></td>
                        <td class="table-actions no-export">
                            <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (has_role(['Admin', 'Manager'])): ?>
                            <a href="delete.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-error btn-delete" title="Delete">
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
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $pagination['total_pages']): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="btn-group mt-3">
            <button onclick="exportTableToCSV('customers-table', 'customers_<?php echo date('Y-m-d'); ?>.csv')" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
        </div>
        
        <?php else: ?>
        <p class="text-center text-muted">No customers found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
