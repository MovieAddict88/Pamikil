<?php
/**
 * Services View
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$pageTitle = 'Service Details';
$db = getDb();
$serviceId = intval($_GET['id'] ?? 0);

if (!$serviceId) {
    setFlashMessage('error', 'Invalid service ID');
    redirect(SITE_URL . '/services/index.php');
    exit;
}

$stmt = $db->prepare("
    SELECT s.*, v.stock_number, v.make, v.model, v.year, v.plate_number
    FROM services s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE s.id = ?
");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    setFlashMessage('error', 'Service not found');
    redirect(SITE_URL . '/services/index.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?php echo SITE_URL; ?>/services/index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Services
    </a>
    <?php if (hasAnyRole(['admin', 'manager'])): ?>
        <div class="btn-group">
            <a href="<?php echo SITE_URL; ?>/services/edit.php?id=<?php echo $service['id']; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">Service Record #<?php echo str_pad($service['id'], 6, '0', STR_PAD_LEFT); ?></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th width="40%">Service Date</th>
                        <td><?php echo formatDate($service['service_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Service Type</th>
                        <td><?php echo getStatusBadge($service['service_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo getStatusBadge($service['status']); ?></td>
                    </tr>
                    <tr>
                        <th>Performed By</th>
                        <td><?php echo htmlspecialchars($service['performed_by']); ?></td>
                    </tr>
                    <?php if ($service['mechanic_name']): ?>
                    <tr>
                        <th>Mechanic</th>
                        <td><?php echo htmlspecialchars($service['mechanic_name']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($service['next_service_date']): ?>
                    <tr>
                        <th>Next Service</th>
                        <td><?php echo formatDate($service['next_service_date']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th width="40%">Vehicle</th>
                        <td>
                            <strong><?php echo htmlspecialchars($service['stock_number']); ?></strong>
                            <br><?php echo htmlspecialchars($service['year'] . ' ' . $service['make'] . ' ' . $service['model']); ?>
                            <?php if ($service['plate_number']): ?>
                                <br>Plate: <?php echo htmlspecialchars($service['plate_number']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Labor Cost</th>
                        <td><?php echo formatCurrency($service['labor_cost']); ?></td>
                    </tr>
                    <tr>
                        <th>Parts Cost</th>
                        <td><?php echo formatCurrency($service['parts_cost']); ?></td>
                    </tr>
                    <tr>
                        <th>Total Cost</th>
                        <td><strong><?php echo formatCurrency($service['total_cost']); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Description</div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                    </div>
                </div>
            </div>
            
            <?php if ($service['parts_used'] || $service['notes']): ?>
            <div class="col-md-6">
                <?php if ($service['parts_used']): ?>
                <div class="card mb-3">
                    <div class="card-header">Parts Used</div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($service['parts_used'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($service['notes']): ?>
                <div class="card">
                    <div class="card-header">Notes</div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($service['notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
