<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

$sales_stats = $conn->query("
    SELECT 
        COUNT(*) as total_sales,
        SUM(sale_price) as total_revenue,
        SUM(down_payment) as total_down_payments,
        SUM(balance) as total_balance
    FROM sales
    WHERE YEAR(sale_date) = {$year}
")->fetch_assoc();

$monthly_sales = $conn->query("
    SELECT 
        MONTH(sale_date) as month,
        COUNT(*) as count,
        SUM(sale_price) as revenue
    FROM sales
    WHERE YEAR(sale_date) = {$year}
    GROUP BY MONTH(sale_date)
    ORDER BY MONTH(sale_date)
");

$top_sellers = $conn->query("
    SELECT 
        u.full_name,
        COUNT(s.id) as sales_count,
        SUM(s.sale_price) as total_sales
    FROM sales s
    JOIN users u ON s.sold_by = u.id
    WHERE YEAR(s.sale_date) = {$year}
    GROUP BY s.sold_by
    ORDER BY total_sales DESC
    LIMIT 5
");

$inventory_summary = $conn->query("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(selling_price) as total_value
    FROM vehicles
    GROUP BY status
");

$page_title = 'Reports & Analytics';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
        <form method="GET" action="">
            <select name="year" class="form-control" style="display:inline-block; width:auto;" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
    <div class="card-body">
        <h3>Sales Summary for <?php echo $year; ?></h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-value"><?php echo $sales_stats['total_sales']; ?></div>
                <div class="stat-label">Total Sales</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                <div class="stat-value"><?php echo format_currency($sales_stats['total_revenue']); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-money-bill"></i></div>
                <div class="stat-value"><?php echo format_currency($sales_stats['total_down_payments']); ?></div>
                <div class="stat-label">Down Payments</div>
            </div>
            
            <div class="stat-card error">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo format_currency($sales_stats['total_balance']); ?></div>
                <div class="stat-label">Outstanding Balance</div>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-calendar"></i> Monthly Sales</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Sales Count</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        while ($row = $monthly_sales->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $months[$row['month']]; ?></td>
                            <td><?php echo $row['count']; ?></td>
                            <td><?php echo format_currency($row['revenue']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-trophy"></i> Top Sellers</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Sales Person</th>
                            <th>Sales Count</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($seller = $top_sellers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo escape_output($seller['full_name']); ?></td>
                            <td><?php echo $seller['sales_count']; ?></td>
                            <td><?php echo format_currency($seller['total_sales']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-warehouse"></i> Inventory Summary</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($inv = $inventory_summary->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo status_badge($inv['status']); ?></td>
                            <td><?php echo $inv['count']; ?></td>
                            <td><?php echo format_currency($inv['total_value']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-download"></i> Export Reports</h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="export.php?type=sales&year=<?php echo $year; ?>" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export Sales
                </a>
                <a href="export.php?type=inventory" class="btn btn-primary">
                    <i class="fas fa-file-csv"></i> Export Inventory
                </a>
                <a href="export.php?type=customers" class="btn btn-warning">
                    <i class="fas fa-file-csv"></i> Export Customers
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
