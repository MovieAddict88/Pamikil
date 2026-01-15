<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = "SELECT sh.*, v.make, v.model, v.year, v.stock_number, 
          CONCAT(c.first_name, ' ', c.last_name) as customer_name,
          u.full_name as created_by_name
          FROM service_history sh
          JOIN vehicles v ON sh.vehicle_id = v.id
          LEFT JOIN customers c ON sh.customer_id = c.id
          LEFT JOIN users u ON sh.created_by = u.id
          WHERE sh.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Service record not found.';
    header('Location: index.php');
    exit();
}
$service = $result->fetch_assoc();
$stmt->close();

$page_title = 'Service Record - ' . $service['service_number'];
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-wrench"></i> Service: <?php echo escape_output($service['service_number']); ?></h2>
        <div class="btn-group">
            <a href="edit.php?id=<?php echo $service['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div>
                <h3>Service Information</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Service #:</td>
                        <td><?php echo escape_output($service['service_number']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Service Type:</td>
                        <td><?php echo escape_output($service['service_type']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Service Date:</td>
                        <td><?php echo format_date($service['service_date']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Status:</td>
                        <td><?php echo status_badge($service['status']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3>Vehicle & Customer</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Vehicle:</td>
                        <td><?php echo escape_output($service['year'] . ' ' . $service['make'] . ' ' . $service['model']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Stock #:</td>
                        <td><?php echo escape_output($service['stock_number']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Customer:</td>
                        <td><?php echo escape_output($service['customer_name']) ?: 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Technician:</td>
                        <td><?php echo escape_output($service['technician']) ?: 'N/A'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h3>Cost & Payment</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Cost:</td>
                        <td><?php echo format_currency($service['cost']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; font-weight: bold;">Payment Status:</td>
                        <td><?php echo status_badge($service['payment_status']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <h3>Service Description</h3>
            <p><?php echo nl2br(escape_output($service['description'])); ?></p>
        </div>
        
        <?php if (!empty($service['notes'])): ?>
        <div style="margin-top: 2rem;">
            <h3>Notes</h3>
            <p><?php echo nl2br(escape_output($service['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
