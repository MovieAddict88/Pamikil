<?php
/**
 * Delete Vehicle
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireRole('admin');

$vehicleId = intval($_GET['id'] ?? 0);

if (!$vehicleId) {
    setFlashMessage('error', 'Invalid vehicle ID');
    redirect(SITE_URL . '/vehicles/index.php');
    exit;
}

$db = getDb();

// Check if vehicle exists
$stmt = $db->prepare("SELECT id, stock_number, images FROM vehicles WHERE id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    setFlashMessage('error', 'Vehicle not found');
    redirect(SITE_URL . '/vehicles/index.php');
    exit;
}

// Check if vehicle has been sold
$stmt = $db->prepare("SELECT COUNT(*) as count FROM sales WHERE vehicle_id = ?");
$stmt->execute([$vehicleId]);
$salesCount = $stmt->fetch()['count'];

if ($salesCount > 0) {
    setFlashMessage('error', 'Cannot delete vehicle that has sales records');
    redirect(SITE_URL . '/vehicles/index.php');
    exit;
}

try {
    // Delete vehicle images
    if (!empty($vehicle['images'])) {
        $images = explode(',', $vehicle['images']);
        foreach ($images as $image) {
            deleteFile('vehicles/' . $image);
        }
    }
    
    // Delete vehicle
    $stmt = $db->prepare("UPDATE vehicles SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$vehicleId]);
    
    logActivity('vehicle_delete', 'Vehicle deleted: ' . $vehicle['stock_number']);
    setFlashMessage('success', 'Vehicle deleted successfully');
    
} catch (PDOException $e) {
    logError('Vehicle deletion failed', ['error' => $e->getMessage()]);
    setFlashMessage('error', 'Failed to delete vehicle');
}

redirect(SITE_URL . '/vehicles/index.php');
exit;
