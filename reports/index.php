<?php
/**
 * Reports Dashboard
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$pageTitle = 'Reports & Analytics';
$db = getDb();

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Sales summary
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_sales,
        SUM(sale_price) as total_revenue,
        SUM(sale_price - (SELECT purchase_price FROM vehicles WHERE id = s.vehicle_id)) as total_profit
    FROM sales s
    WHERE s.status = 'completed'
        AND s.sale_date BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$salesSummary = $stmt->fetch();

// Sales by vehicle
$stmt = $db->prepare("
    SELECT v.make, v.model, COUNT(*) as count, SUM(s.sale_price) as total
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE s.status = 'completed'
        AND s.sale_date BETWEEN ? AND ?
    GROUP BY v.make, v.model
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$salesByVehicle = $stmt->fetchAll();

// Top customers
$stmt = $db->prepare("
    SELECT c.first_name, c.last_name, COUNT(*) as purchase_count, SUM(s.sale_price) as total_spent
    FROM sales s
    JOIN customers c ON s.customer_id = c.id
    WHERE s.status = 'completed'
        AND s.sale_date BETWEEN ? AND ?
    GROUP BY c.id, c.first_name, c.last_name
    ORDER BY purchase_count DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$topCustomers = $stmt->fetchAll();

// Vehicle inventory summary
$stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold,
        SUM(CASE WHEN status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance,
        SUM(purchase_price) as total_inventory_value,
        SUM(selling_price) as total_potential_revenue
    FROM vehicles
    WHERE status != 'inactive'
");
$inventorySummary = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="d-flex gap-3 align-items-end">
            <div>
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
            </div>
            <div>
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Update Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Sales Summary -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Sales Summary</div>
            <div class="card-body">
                <h4><?php echo number_format($salesSummary['total_sales'] ?? 0); ?></h4>
                <p class="text-muted">Total Sales</p>
                <hr>
                <h5><?php echo formatCurrency($salesSummary['total_revenue'] ?? 0); ?></h5>
                <p class="text-muted">Total Revenue</p>
                <hr>
                <h5 class="<?php echo ($salesSummary['total_profit'] ?? 0) > 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo formatCurrency($salesSummary['total_profit'] ?? 0); ?>
                </h5>
                <p class="text-muted">Total Profit</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Inventory Overview</div>
            <div class="card-body">
                <h4><?php echo number_format($inventorySummary['total']); ?></h4>
                <p class="text-muted">Total Vehicles</p>
                <hr>
                <p><span class="badge bg-success">Available: <?php echo number_format($inventorySummary['available']); ?></span></p>
                <p><span class="badge bg-warning">Reserved: <?php echo number_format($inventorySummary['reserved']); ?></span></p>
                <p><span class="badge bg-primary">Sold: <?php echo number_format($inventorySummary['sold']); ?></span></p>
                <p><span class="badge bg-info">In Maintenance: <?php echo number_format($inventorySummary['maintenance']); ?></span></p>
                <hr>
                <h5><?php echo formatCurrency($inventorySummary['total_inventory_value'] ?? 0); ?></h5>
                <p class="text-muted">Inventory Value</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Financial Summary</div>
            <div class="card-body">
                <h5><?php echo formatCurrency($inventorySummary['total_inventory_value'] ?? 0); ?></h5>
                <p class="text-muted">Total Investment</p>
                <hr>
                <h5><?php echo formatCurrency($inventorySummary['total_potential_revenue'] ?? 0); ?></h5>
                <p class="text-muted">Potential Revenue</p>
                <hr>
                <h5 class="text-success">
                    <?php echo formatCurrency(($inventorySummary['total_potential_revenue'] ?? 0) - ($inventorySummary['total_inventory_value'] ?? 0)); ?>
                </h5>
                <p class="text-muted">Potential Profit</p>
            </div>
        </div>
    </div>
</div>

<!-- Top Selling Vehicles -->
<div class="card mt-4">
    <div class="card-header">Top Selling Vehicles</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Vehicle</th>
                        <th>Units Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salesByVehicle)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No sales data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($salesByVehicle as $index => $vehicle): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                                <td><?php echo number_format($vehicle['count']); ?></td>
                                <td><?php echo formatCurrency($vehicle['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Customers -->
<div class="card mt-4">
    <div class="card-header">Top Customers</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Customer</th>
                        <th>Purchases</th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topCustomers)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No customer data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topCustomers as $index => $customer): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($customer['last_name'] . ', ' . $customer['first_name']); ?></td>
                                <td><?php echo number_format($customer['purchase_count']); ?></td>
                                <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
