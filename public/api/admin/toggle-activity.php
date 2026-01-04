<?php
/**
 * Admin API - Toggle Activity Status
 */

header('Content-Type: application/json');
require_once APP_ROOT . '/includes/functions.php';

requireAdmin();

if (!isPost()) {
    jsonError('Method not allowed', 405);
}

$activityId = get('id');
$status = get('status') == '1' ? 1 : 0;

if (!$activityId) {
    jsonError('Activity ID required', 400);
}

$db = Database::getInstance();
$result = $db->execute(
    "UPDATE activities SET is_active = ?, updated_at = NOW() WHERE id = ?",
    [$status, $activityId]
);

if ($result) {
    jsonSuccess(null, $status ? 'Activity activated!' : 'Activity deactivated!');
} else {
    jsonError('Failed to update activity status');
}
