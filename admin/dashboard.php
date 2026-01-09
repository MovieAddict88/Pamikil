<?php
require_once '../config.php';
requireLogin();

$stats = [];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cars");
$stats['total_cars'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cars WHERE status = 'Available'");
$stats['available_cars'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM sales");
$stats['total_sales'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT SUM(sale_price) as total FROM sales");
$stats['revenue'] = mysqli_fetch_assoc($result)['total'] ?? 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM customers");
$stats['total_customers'] = mysqli_fetch_assoc($result)['count'];

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM inquiries WHERE status = 'New'");
$stats['pending_inquiries'] = mysqli_fetch_assoc($result)['count'];

$recent_sales = mysqli_query($conn, "
    SELECT s.*, c.make, c.model, c.year, cu.first_name, cu.last_name 
    FROM sales s
    JOIN cars c ON s.car_id = c.id
    JOIN customers cu ON s.customer_id = cu.id
    ORDER BY s.created_at DESC
    LIMIT 5
");

$recent_inquiries = mysqli_query($conn, "
    SELECT i.*, c.make, c.model 
    FROM inquiries i
    LEFT JOIN cars c ON i.car_id = c.id
    ORDER BY i.created_at DESC
    LIMIT 5
");

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="content-area">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-car"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_cars']; ?></h3>
                <p>Total Cars</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['available_cars']; ?></h3>
                <p>Available Cars</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_sales']; ?></h3>
                <p>Total Sales</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-peso-sign"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo formatCurrency($stats['revenue']); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_customers']; ?></h3>
                <p>Customers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['pending_inquiries']; ?></h3>
                <p>New Inquiries</p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3>Recent Sales</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Car</th>
                            <th>Customer</th>
                            <th>Price</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent_sales) > 0): ?>
                            <?php while ($sale = mysqli_fetch_assoc($recent_sales)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?></td>
                                    <td><?php echo formatCurrency($sale['sale_price']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--secondary);">No sales yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer" style="text-align: center;">
                <a href="sales.php" class="btn-link">View All Sales <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Recent Inquiries</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Car</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent_inquiries) > 0): ?>
                            <?php while ($inquiry = mysqli_fetch_assoc($recent_inquiries)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                                    <td><?php echo $inquiry['make'] ? htmlspecialchars($inquiry['make'] . ' ' . $inquiry['model']) : 'General'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $inquiry['status'] === 'New' ? 'danger' : 
                                                ($inquiry['status'] === 'Contacted' ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo $inquiry['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d', strtotime($inquiry['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--secondary);">No inquiries yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer" style="text-align: center;">
                <a href="inquiries.php" class="btn-link">View All Inquiries <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
