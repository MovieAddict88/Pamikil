<?php
/**
 * Admin API - Save User
 */

header('Content-Type: application/json');
require_once APP_ROOT . '/includes/functions.php';

requireAdmin();

if (!isPost() || !isAjax()) {
    jsonError('Invalid request', 405);
}

$auth = new Auth();
$admin = new Admin();

$userData = [
    'id' => post('id') ?: null,
    'username' => trim(post('username')),
    'email' => trim(post('email')),
    'password' => post('password') ?: null,
    'first_name' => trim(post('first_name')),
    'last_name' => trim(post('last_name')),
    'role' => post('role'),
    'date_of_birth' => post('date_of_birth') ?: null,
    'parent_id' => post('parent_id') ?: null,
    'is_active' => post('is_active') === '1'
];

$userId = $admin->saveUser($userData);

if ($userId) {
    jsonSuccess(['user_id' => $userId], 'User saved successfully!');
} else {
    jsonError('Failed to save user. Username or email may already exist.');
}
