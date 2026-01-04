<?php
/**
 * Delete Service
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireRole('admin');

$serviceId = intval($_GET['id'] ?? 0);

if (!$serviceId) {
    setFlashMessage('error', 'Invalid service ID');
    redirect(SITE_URL . '/services/index.php');
    exit;
}

$db = getDb();

try {
    $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    
    logActivity('service_delete', 'Service deleted: ID ' . $serviceId);
    setFlashMessage('success', 'Service record deleted successfully');
    
} catch (PDOException $e) {
    logError('Service deletion failed', ['error' => $e->getMessage()]);
    setFlashMessage('error', 'Failed to delete service');
}

redirect(SITE_URL . '/services/index.php');
exit;
