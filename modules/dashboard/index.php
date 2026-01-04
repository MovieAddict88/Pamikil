<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$total_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
$available_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'Available'")->fetch_assoc()['count'];
$sold_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'Sold'")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];

$total_sales_result = $conn->query("SELECT SUM(sale_price) as total FROM sales WHERE YEAR(sale_date) = YEAR(CURDATE())");
$total_sales = $total_sales_result->fetch_assoc()['total'] ?? 0;

$pending_payments = $conn->query("SELECT COUNT(*) as count FROM sales WHERE payment_status IN ('Pending', 'Partial')")->fetch_assoc()['count'];

$scheduled_services = $conn->query("SELECT COUNT(*) as count FROM service_history WHERE status = 'Scheduled' AND service_date >= CURDATE()")->fetch_assoc()['count'];

$recent_sales_query = "
    SELECT s.*, v.make, v.model, v.year, v.stock_number,
           CONCAT(c.first_name, ' ', c.last_name) as customer_name
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN customers c ON s.customer_id = c.id
    ORDER BY s.created_at DESC
    LIMIT 5
";
$recent_sales = $conn->query($recent_sales_query);

$recent_vehicles_query = "
    SELECT * FROM vehicles 
    ORDER BY created_at DESC 
    LIMIT 5
";
$recent_vehicles = $conn->query($recent_vehicles_query);

$low_inventory_threshold = 5;
$low_inventory = $available_vehicles < $low_inventory_threshold;

$page_title = 'Dashboard';
require_once '../../includes/header.php';
?>

<h1><i class="fas fa-home"></i> Dashboard</h1>
<p class="text-muted">Welcome back, <?php echo escape_output($_SESSION['user_name']); ?>! Here's your overview.</p>

<?php if ($low_inventory): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> <strong>Low Inventory Alert:</strong> Only <?php echo $available_vehicles; ?> vehicles available in stock.
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-car"></i>
        </div>
        <div class="stat-value"><?php echo $total_vehicles; ?></div>
        <div class="stat-label">Total Vehicles</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value"><?php echo $available_vehicles; ?></div>
        <div class="stat-label">Available Vehicles</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-value"><?php echo $sold_vehicles; ?></div>
        <div class="stat-label">Sold This Year</div>
    </div>
    
    <div class="stat-card error">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?php echo $total_customers; ?></div>
        <div class="stat-label">Total Customers</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="fas fa-peso-sign"></i>
        </div>
        <div class="stat-value"><?php echo format_currency($total_sales); ?></div>
        <div class="stat-label">Sales This Year</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-value"><?php echo $pending_payments; ?></div>
        <div class="stat-label">Pending Payments</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-wrench"></i>
        </div>
        <div class="stat-value"><?php echo $scheduled_services; ?></div>
        <div class="stat-label">Scheduled Services</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-shopping-cart"></i> Recent Sales</h2>
            <a href="../sales/index.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if ($recent_sales->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Vehicle</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo escape_output($sale['invoice_number']); ?></td>
                            <td><?php echo escape_output($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                            <td><?php echo escape_output($sale['customer_name']); ?></td>
                            <td><?php echo format_currency($sale['sale_price']); ?></td>
                            <td><?php echo status_badge($sale['payment_status']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No sales recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-car"></i> Recent Vehicles</h2>
            <a href="../vehicles/index.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if ($recent_vehicles->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Stock #</th>
                            <th>Vehicle</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($vehicle = $recent_vehicles->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo escape_output($vehicle['stock_number']); ?></td>
                            <td><?php echo escape_output($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                            <td><?php echo format_currency($vehicle['selling_price']); ?></td>
                            <td><?php echo status_badge($vehicle['status']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No vehicles in inventory.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-tasks"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="btn-group">
            <a href="../vehicles/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Vehicle
            </a>
            <a href="../customers/add.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Add Customer
            </a>
            <a href="../sales/add.php" class="btn btn-warning">
                <i class="fas fa-shopping-cart"></i> New Sale
            </a>
            <a href="../services/add.php" class="btn btn-info">
                <i class="fas fa-wrench"></i> Schedule Service
            </a>
            <a href="../reports/index.php" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> View Reports
            </a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
