<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_role(['Admin', 'Manager']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
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

$check_sales = $conn->prepare("SELECT COUNT(*) as count FROM sales WHERE vehicle_id = ?");
$check_sales->bind_param("i", $id);
$check_sales->execute();
$sales_count = $check_sales->get_result()->fetch_assoc()['count'];
$check_sales->close();

if ($sales_count > 0) {
    $_SESSION['error'] = 'Cannot delete vehicle with existing sales records.';
    header('Location: view.php?id=' . $id);
    exit();
}

if (!empty($vehicle['image_main'])) delete_file($vehicle['image_main']);
for ($i = 1; $i <= 4; $i++) {
    if (!empty($vehicle["image_{$i}"])) delete_file($vehicle["image_{$i}"]);
}

$stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    log_transaction($conn, 'DELETE', 'vehicles', $id, "Deleted vehicle: {$vehicle['stock_number']}");
    $_SESSION['success'] = 'Vehicle deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete vehicle.';
}

$stmt->close();
header('Location: index.php');
exit();
?>
