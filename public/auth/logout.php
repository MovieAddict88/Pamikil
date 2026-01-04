<?php
/**
 * Logout
 * Logs out the current user and redirects to home
 */

require_once APP_ROOT . '/includes/functions.php';

$auth = new Auth();
$auth->logout();

setFlash('success', 'You have been logged out successfully.');
redirect(SITE_URL . '/');
