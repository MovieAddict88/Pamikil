<?php
/**
 * Admin API - Delete User
 */

header('Content-Type: application/json');
require_once APP_ROOT . '/includes/functions.php';

requireAdmin();

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    jsonError('Method not allowed', 405);
}

$auth = new Auth();
$admin = new Admin();
$userId = get('id');

if (!$userId) {
    jsonError('User ID required', 400);
}

// Prevent deleting own account
if ($userId == $auth->getCurrentUser()['id']) {
    jsonError('You cannot delete your own account', 403);
}

$result = $admin->deleteUser($userId);

if ($result) {
    jsonSuccess(null, 'User deleted successfully!');
} else {
    jsonError('Failed to delete user');
}
