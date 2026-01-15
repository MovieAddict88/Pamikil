<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_role(['Admin', 'Manager']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Customer not found.';
    header('Location: index.php');
    exit();
}

$customer = $result->fetch_assoc();
$stmt->close();

$check_sales = $conn->prepare("SELECT COUNT(*) as count FROM sales WHERE customer_id = ?");
$check_sales->bind_param("i", $id);
$check_sales->execute();
$sales_count = $check_sales->get_result()->fetch_assoc()['count'];
$check_sales->close();

if ($sales_count > 0) {
    $_SESSION['error'] = 'Cannot delete customer with existing sales records.';
    header('Location: view.php?id=' . $id);
    exit();
}

$stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    log_transaction($conn, 'DELETE', 'customers', $id, "Deleted customer: {$customer['first_name']} {$customer['last_name']}");
    $_SESSION['success'] = 'Customer deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete customer.';
}

$stmt->close();
header('Location: index.php');
exit();
?>
