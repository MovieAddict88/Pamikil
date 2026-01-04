<?php
/**
 * View Vehicle
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Vehicle Details';
$db = getDb();

$vehicleId = intval($_GET['id'] ?? 0);

if (!$vehicleId) {
    setFlashMessage('error', 'Invalid vehicle ID');
    redirect(SITE_URL . '/vehicles/index.php');
    exit;
}

// Get vehicle details
$stmt = $db->prepare("
    SELECT v.*, l.name as location_name
    FROM vehicles v
    LEFT JOIN locations l ON v.location_id = l.id
    WHERE v.id = ?
");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    setFlashMessage('error', 'Vehicle not found');
    redirect(SITE_URL . '/vehicles/index.php');
    exit;
}

// Get vehicle images
$images = $vehicle['images'] ? explode(',', $vehicle['images']) : [];

// Get sales history
$stmt = $db->prepare("
    SELECT s.*, c.first_name, c.last_name, u.full_name as agent_name
    FROM sales s
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.sales_agent_id = u.id
    WHERE s.vehicle_id = ?
    ORDER BY s.sale_date DESC
    LIMIT 10
");
$stmt->execute([$vehicleId]);
$salesHistory = $stmt->fetchAll();

// Get service history
$stmt = $db->prepare("
    SELECT * FROM services
    WHERE vehicle_id = ?
    ORDER BY service_date DESC
    LIMIT 10
");
$stmt->execute([$vehicleId]);
$serviceHistory = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Back Button -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?php echo SITE_URL; ?>/vehicles/index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Vehicles
    </a>
    <?php if (hasAnyRole(['admin', 'manager'])): ?>
        <div class="btn-group">
            <a href="<?php echo SITE_URL; ?>/vehicles/edit.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?php echo SITE_URL; ?>/sales/create.php?vehicle_id=<?php echo $vehicle['id']; ?>" 
               class="btn btn-success" <?php echo $vehicle['status'] !== 'available' ? 'disabled' : ''; ?>>
                <i class="bi bi-cart-plus"></i> Create Sale
            </a>
            <a href="<?php echo SITE_URL; ?>/services/create.php?vehicle_id=<?php echo $vehicle['id']; ?>" 
               class="btn btn-info">
                <i class="bi bi-tools"></i> Add Service
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Vehicle Details -->
<div class="row">
    <!-- Left Column - Vehicle Info -->
    <div class="col-md-8">
        <!-- Images -->
        <?php if (!empty($images)): ?>
            <div class="card mb-4">
                <div class="card-header">Vehicle Images</div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="col-md-4 mb-3">
                                <img src="<?php echo SITE_URL; ?>/public/uploads/vehicles/<?php echo htmlspecialchars($image); ?>" 
                                     alt="Vehicle Image <?php echo $index + 1; ?>" 
                                     class="vehicle-image">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Vehicle Information -->
        <div class="card">
            <div class="card-header">Vehicle Information</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="30%">Stock Number</th>
                        <td><strong><?php echo htmlspecialchars($vehicle['stock_number']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Vehicle</th>
                        <td>
                            <strong><?php echo htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?></strong>
                            <?php if (!empty($vehicle['variant'])): ?>
                                <br><?php echo htmlspecialchars($vehicle['variant']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Color</th>
                        <td><?php echo htmlspecialchars(ucwords($vehicle['color'])); ?></td>
                    </tr>
                    <tr>
                        <th>Body Type</th>
                        <td><?php echo ucwords(str_replace('_', ' ', $vehicle['body_type'])); ?></td>
                    </tr>
                    <tr>
                        <th>Fuel Type</th>
                        <td><?php echo ucwords(str_replace('_', ' ', $vehicle['fuel_type'])); ?></td>
                    </tr>
                    <tr>
                        <th>Transmission</th>
                        <td><?php echo ucwords(str_replace('_', ' ', $vehicle['transmission'])); ?></td>
                    </tr>
                    <?php if ($vehicle['engine_cc']): ?>
                    <tr>
                        <th>Engine</th>
                        <td><?php echo number_format($vehicle['engine_cc']); ?> cc</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Condition</th>
                        <td><?php echo getStatusBadge($vehicle['condition']); ?></td>
                    </tr>
                    <?php if ($vehicle['mileage']): ?>
                    <tr>
                        <th>Mileage</th>
                        <td><?php echo number_format($vehicle['mileage']); ?> km</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Status</th>
                        <td><?php echo getStatusBadge($vehicle['status']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Identification Numbers -->
        <?php if ($vehicle['plate_number'] || $vehicle['chassis_number'] || $vehicle['engine_number']): ?>
        <div class="card">
            <div class="card-header">Identification Numbers</div>
            <div class="card-body">
                <table class="table">
                    <?php if ($vehicle['plate_number']): ?>
                    <tr>
                        <th width="30%">Plate Number</th>
                        <td><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($vehicle['chassis_number']): ?>
                    <tr>
                        <th>Chassis Number</th>
                        <td><?php echo htmlspecialchars($vehicle['chassis_number']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($vehicle['engine_number']): ?>
                    <tr>
                        <th>Engine Number</th>
                        <td><?php echo htmlspecialchars($vehicle['engine_number']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Description & Features -->
        <?php if ($vehicle['description'] || $vehicle['features']): ?>
        <div class="card">
            <div class="card-header">Additional Information</div>
            <div class="card-body">
                <?php if ($vehicle['description']): ?>
                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($vehicle['description'])); ?></p>
                <?php endif; ?>
                <?php if ($vehicle['features']): ?>
                <p><strong>Features:</strong><br><?php echo nl2br(htmlspecialchars($vehicle['features'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Column - Pricing & History -->
    <div class="col-md-4">
        <!-- Pricing -->
        <div class="card">
            <div class="card-header">Pricing</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Purchase Price</th>
                        <td><?php echo formatCurrency($vehicle['purchase_price']); ?></td>
                    </tr>
                    <tr>
                        <th>Selling Price</th>
                        <td><strong><?php echo formatCurrency($vehicle['selling_price']); ?></strong></td>
                    </tr>
                    <?php if ($vehicle['selling_price'] > $vehicle['purchase_price']): ?>
                    <tr>
                        <th>Profit</th>
                        <td class="text-success">
                            +<?php echo formatCurrency($vehicle['selling_price'] - $vehicle['purchase_price']); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Location & Date -->
        <div class="card">
            <div class="card-header">Location & Date</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="40%">Location</th>
                        <td><?php echo htmlspecialchars($vehicle['location_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php if ($vehicle['supplier']): ?>
                    <tr>
                        <th>Supplier</th>
                        <td><?php echo htmlspecialchars($vehicle['supplier']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Date Acquired</th>
                        <td><?php echo formatDate($vehicle['date_acquired']); ?></td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td><?php echo formatDateTime($vehicle['created_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Last Updated</th>
                        <td><?php echo formatDateTime($vehicle['updated_at']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Sales History -->
        <?php if (!empty($salesHistory)): ?>
        <div class="card">
            <div class="card-header">
                Sales History
                <a href="<?php echo SITE_URL; ?>/sales/index.php?vehicle_id=<?php echo $vehicle['id']; ?>" 
                   class="btn btn-sm btn-primary float-end">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesHistory as $sale): ?>
                                <tr>
                                    <td><?php echo formatDate($sale['sale_date']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?></td>
                                    <td><?php echo formatCurrency($sale['sale_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Service History -->
        <?php if (!empty($serviceHistory)): ?>
        <div class="card">
            <div class="card-header">
                Service History
                <a href="<?php echo SITE_URL; ?>/services/index.php?vehicle_id=<?php echo $vehicle['id']; ?>" 
                   class="btn btn-sm btn-primary float-end">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceHistory as $service): ?>
                                <tr>
                                    <td><?php echo formatDate($service['service_date']); ?></td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $service['service_type'])); ?></td>
                                    <td><?php echo formatCurrency($service['total_cost']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
