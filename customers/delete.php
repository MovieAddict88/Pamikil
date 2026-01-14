<?php
/**
 * Delete Customer
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireRole('admin');

$customerId = intval($_GET['id'] ?? 0);

if (!$customerId) {
    setFlashMessage('error', 'Invalid customer ID');
    redirect(SITE_URL . '/customers/index.php');
    exit;
}

$db = getDb();

// Check if customer has sales
$stmt = $db->prepare("SELECT COUNT(*) as count FROM sales WHERE customer_id = ?");
$stmt->execute([$customerId]);
$salesCount = $stmt->fetch()['count'];

if ($salesCount > 0) {
    setFlashMessage('error', 'Cannot delete customer with existing sales records');
    redirect(SITE_URL . '/customers/index.php');
    exit;
}

try {
    $stmt = $db->prepare("UPDATE customers SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$customerId]);
    
    logActivity('customer_delete', 'Customer deleted: ID ' . $customerId);
    setFlashMessage('success', 'Customer deleted successfully');
    
} catch (PDOException $e) {
    logError('Customer deletion failed', ['error' => $e->getMessage()]);
    setFlashMessage('error', 'Failed to delete customer');
}

redirect(SITE_URL . '/customers/index.php');
exit;
