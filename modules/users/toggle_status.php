<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_role(['Admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'You cannot deactivate your own account.';
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'User not found.';
    header('Location: index.php');
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

$new_status = $user['is_active'] == 1 ? 0 : 1;

$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $new_status, $id);

if ($stmt->execute()) {
    $action = $new_status == 1 ? 'activated' : 'deactivated';
    log_transaction($conn, 'UPDATE', 'users', $id, "User {$action}: {$user['username']}");
    $_SESSION['success'] = "User {$action} successfully.";
} else {
    $_SESSION['error'] = 'Failed to update user status.';
}

$stmt->close();
header('Location: index.php');
exit();
?>
