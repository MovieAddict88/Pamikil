<?php
/**
 * Dashboard
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check session timeout
checkSessionTimeout();

// Require login
requireLogin();

$pageTitle = 'Dashboard';
$db = getDb();
$user = getCurrentUser();

// Get dashboard statistics
$stats = [];

// Total vehicles
$stmt = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE status != 'inactive'");
$stats['total_vehicles'] = $stmt->fetch()['count'];

// Available vehicles
$stmt = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'available'");
$stats['available_vehicles'] = $stmt->fetch()['count'];

// Sold vehicles this month
$stmt = $db->query("SELECT COUNT(*) as count FROM sales WHERE YEAR(sale_date) = YEAR(CURRENT_DATE) AND MONTH(sale_date) = MONTH(CURRENT_DATE) AND status = 'completed'");
$stats['sold_this_month'] = $stmt->fetch()['count'];

// Total customers
$stmt = $db->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'");
$stats['total_customers'] = $stmt->fetch()['count'];

// Pending services
$stmt = $db->query("SELECT COUNT(*) as count FROM services WHERE status IN ('scheduled', 'in_progress')");
$stats['pending_services'] = $stmt->fetch()['count'];

// Total sales amount this month
$stmt = $db->query("SELECT SUM(sale_price) as total FROM sales WHERE YEAR(sale_date) = YEAR(CURRENT_DATE) AND MONTH(sale_date) = MONTH(CURRENT_DATE) AND status = 'completed'");
$result = $stmt->fetch();
$stats['sales_this_month'] = $result['total'] ?? 0;

// Recent sales
$stmt = $db->query("
    SELECT s.*, v.make, v.model, v.year, c.first_name, c.last_name, u.full_name as agent_name
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.sales_agent_id = u.id
    ORDER BY s.sale_date DESC, s.created_at DESC
    LIMIT 5
");
$recent_sales = $stmt->fetchAll();

// Vehicles needing attention (low stock, pending service)
$stmt = $db->query("
    SELECT * FROM vehicles
    WHERE status IN ('under_maintenance', 'pending_service')
    ORDER BY updated_at DESC
    LIMIT 5
");
$vehicles_needing_attention = $stmt->fetchAll();

// Upcoming services
$stmt = $db->query("
    SELECT s.*, v.make, v.model, v.plate_number
    FROM services s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE s.status = 'scheduled'
    ORDER BY s.service_date ASC
    LIMIT 5
");
$upcoming_services = $stmt->fetchAll();

// Sales data for the last 6 months
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(sale_date, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(sale_price) as total
    FROM sales
    WHERE sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        AND status = 'completed'
    GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
    ORDER BY month DESC
");
$sales_by_month = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Dashboard Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="bi bi-car-front"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['total_vehicles']); ?></div>
            <div class="stat-label">Total Vehicles</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="bi bi-check-circle"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['available_vehicles']); ?></div>
            <div class="stat-label">Available</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="bi bi-cart-check"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['sold_this_month']); ?></div>
            <div class="stat-label">Sold This Month</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="bi bi-people"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
            <div class="stat-label">Customers</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="bi bi-tools"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo number_format($stats['pending_services']); ?></div>
            <div class="stat-label">Pending Services</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="bi bi-cash-coin"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?php echo formatCurrency($stats['sales_this_month']); ?></div>
            <div class="stat-label">Sales This Month</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Sales -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Recent Sales
                <a href="<?php echo SITE_URL; ?>/sales/index.php" class="btn btn-sm btn-primary float-end">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_sales)): ?>
                    <p class="text-muted text-center">No recent sales found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Vehicle</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <tr>
                                        <td><?php echo formatDate($sale['sale_date']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?></td>
                                        <td><?php echo formatCurrency($sale['sale_price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Vehicles Needing Attention -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Vehicles Needing Attention
                <a href="<?php echo SITE_URL; ?>/vehicles/index.php?status=under_maintenance" class="btn btn-sm btn-warning float-end">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($vehicles_needing_attention)): ?>
                    <p class="text-success text-center">No vehicles needing attention.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Stock #</th>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vehicles_needing_attention as $vehicle): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($vehicle['stock_number']); ?></td>
                                        <td><?php echo htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                                        <td><?php echo getStatusBadge($vehicle['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Upcoming Services -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Upcoming Services
                <a href="<?php echo SITE_URL; ?>/services/index.php" class="btn btn-sm btn-info float-end">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_services)): ?>
                    <p class="text-muted text-center">No upcoming services scheduled.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Vehicle</th>
                                    <th>Type</th>
                                    <th>Plate #</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_services as $service): ?>
                                    <tr>
                                        <td><?php echo formatDate($service['service_date']); ?></td>
                                        <td><?php echo htmlspecialchars($service['make'] . ' ' . $service['model']); ?></td>
                                        <td><?php echo ucwords(str_replace('_', ' ', $service['service_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($service['plate_number']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Monthly Sales Summary -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Monthly Sales Summary (Last 6 Months)
            </div>
            <div class="card-body">
                <?php if (empty($sales_by_month)): ?>
                    <p class="text-muted text-center">No sales data available.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Sales Count</th>
                                    <th>Total Amount</th>
                                    <th>Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_by_month as $month): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                        <td><?php echo number_format($month['count']); ?></td>
                                        <td><?php echo formatCurrency($month['total']); ?></td>
                                        <td><?php echo formatCurrency($month['count'] > 0 ? $month['total'] / $month['count'] : 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
