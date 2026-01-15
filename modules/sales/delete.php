<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_role(['Admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT s.*, v.id as vehicle_id FROM sales s JOIN vehicles v ON s.vehicle_id = v.id WHERE s.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Sale not found.';
    header('Location: index.php');
    exit();
}

$sale = $result->fetch_assoc();
$stmt->close();

$update_vehicle = $conn->prepare("UPDATE vehicles SET status = 'Available' WHERE id = ?");
$update_vehicle->bind_param("i", $sale['vehicle_id']);
$update_vehicle->execute();
$update_vehicle->close();

$stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    log_transaction($conn, 'DELETE', 'sales', $id, "Deleted sale: {$sale['invoice_number']}");
    $_SESSION['success'] = 'Sale deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete sale.';
}

$stmt->close();
header('Location: index.php');
exit();
?>
