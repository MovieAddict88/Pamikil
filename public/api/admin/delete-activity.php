<?php
/**
 * Admin API - Delete Activity
 */

header('Content-Type: application/json');
require_once APP_ROOT . '/includes/functions.php';

requireAdmin();

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    jsonError('Method not allowed', 405);
}

$admin = new Admin();
$activityId = get('id');

if (!$activityId) {
    jsonError('Activity ID required', 400);
}

$result = $admin->deleteActivity($activityId);

if ($result) {
    jsonSuccess(null, 'Activity deleted successfully!');
} else {
    jsonError('Failed to delete activity');
}
