<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT v.*, l.name as location_name, u.full_name as created_by_name
          FROM vehicles v
          LEFT JOIN locations l ON v.location_id = l.id
          LEFT JOIN users u ON v.created_by = u.id
          WHERE v.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Vehicle not found.';
    header('Location: index.php');
    exit();
}

$vehicle = $result->fetch_assoc();
$stmt->close();

$sales_query = "SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                WHERE s.vehicle_id = ?";
$sales_stmt = $conn->prepare($sales_query);
$sales_stmt->bind_param("i", $id);
$sales_stmt->execute();
$sales = $sales_stmt->get_result();
$sales_stmt->close();

$service_query = "SELECT * FROM service_history WHERE vehicle_id = ? ORDER BY service_date DESC";
$service_stmt = $conn->prepare($service_query);
$service_stmt->bind_param("i", $id);
$service_stmt->execute();
$services = $service_stmt->get_result();
$service_stmt->close();

$page_title = $vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model'];
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-car"></i> <?php echo escape_output($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?></h2>
        <div class="btn-group">
            <a href="edit.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($vehicle['image_main']) || !empty($vehicle['image_1'])): ?>
        <div class="vehicle-images">
            <?php if (!empty($vehicle['image_main'])): ?>
            <div class="vehicle-image main">
                <img src="<?php echo BASE_URL; ?>/assets/uploads/vehicles/<?php echo escape_output($vehicle['image_main']); ?>" alt="Vehicle Image">
            </div>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <?php if (!empty($vehicle["image_{$i}"])): ?>
                <div class="vehicle-image">
                    <img src="<?php echo BASE_URL; ?>/assets/uploads/vehicles/<?php echo escape_output($vehicle["image_{$i}"]); ?>" alt="Vehicle Image <?php echo $i; ?>">
                </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
            <div>
                <h3>Basic Information</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Stock Number:</td>
                        <td><?php echo escape_output($vehicle['stock_number']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Make:</td>
                        <td><?php echo escape_output($vehicle['make']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Model:</td>
                        <td><?php echo escape_output($vehicle['model']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Year:</td>
                        <td><?php echo escape_output($vehicle['year']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Color:</td>
                        <td><?php echo escape_output($vehicle['color']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Status:</td>
                        <td><?php echo status_badge($vehicle['status']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3>Specifications</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Transmission:</td>
                        <td><?php echo escape_output($vehicle['transmission']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Fuel Type:</td>
                        <td><?php echo escape_output($vehicle['fuel_type']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Mileage:</td>
                        <td><?php echo number_format($vehicle['mileage']); ?> km</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">VIN:</td>
                        <td><?php echo escape_output($vehicle['vin']) ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Plate Number:</td>
                        <td><?php echo escape_output($vehicle['plate_number']) ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Engine Number:</td>
                        <td><?php echo escape_output($vehicle['engine_number']) ?: 'N/A'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3>Pricing & Location</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Purchase Price:</td>
                        <td><?php echo format_currency($vehicle['purchase_price']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Selling Price:</td>
                        <td><strong style="color: var(--success-color); font-size: 1.2rem;"><?php echo format_currency($vehicle['selling_price']); ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Location:</td>
                        <td><?php echo escape_output($vehicle['location_name']) ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Added By:</td>
                        <td><?php echo escape_output($vehicle['created_by_name']) ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Date Added:</td>
                        <td><?php echo format_datetime($vehicle['created_at']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (!empty($vehicle['description'])): ?>
        <div style="margin-top: 2rem;">
            <h3>Description</h3>
            <p><?php echo nl2br(escape_output($vehicle['description'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($vehicle['features'])): ?>
        <div style="margin-top: 2rem;">
            <h3>Features</h3>
            <p><?php echo nl2br(escape_output($vehicle['features'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($sales->num_rows > 0): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-shopping-cart"></i> Sales History</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Sale Price</th>
                        <th>Payment Status</th>
                        <th>Sale Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sale = $sales->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escape_output($sale['invoice_number']); ?></td>
                        <td><?php echo escape_output($sale['customer_name']); ?></td>
                        <td><?php echo format_currency($sale['sale_price']); ?></td>
                        <td><?php echo status_badge($sale['payment_status']); ?></td>
                        <td><?php echo format_date($sale['sale_date']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($services->num_rows > 0): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-wrench"></i> Service History</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Service #</th>
                        <th>Service Type</th>
                        <th>Service Date</th>
                        <th>Cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($service = $services->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escape_output($service['service_number']); ?></td>
                        <td><?php echo escape_output($service['service_type']); ?></td>
                        <td><?php echo format_date($service['service_date']); ?></td>
                        <td><?php echo format_currency($service['cost']); ?></td>
                        <td><?php echo status_badge($service['status']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
