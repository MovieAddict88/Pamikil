<?php
/**
 * Admin API - Export Users
 */

require_once APP_ROOT . '/includes/functions.php';
requireAdmin();

$period = get('period', 'week');

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$db = Database::getInstance();

// Query users with stats
$query = "SELECT u.id, u.username, u.email, u.role, u.first_name, u.last_name,
            u.current_level, u.coins, u.total_xp,
            u.is_active, u.created_at, u.last_login,
            COUNT(p.id) as activities_completed,
            SUM(p.completed) as completed_count
         FROM users u
         LEFT JOIN user_activity_progress p ON u.id = p.user_id
         GROUP BY u.id
         ORDER BY u.created_at DESC";

$users = $db->query($query);

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'ID', 'Username', 'Email', 'Role', 'First Name', 'Last Name',
    'Level', 'Coins', 'Total XP', 'Active', 'Activities Completed',
    'Created At', 'Last Login'
]);

// Write data rows
foreach ($users as $user) {
    fputcsv($output, [
        $user['id'],
        $user['username'],
        $user['email'],
        $user['role'],
        $user['first_name'],
        $user['last_name'],
        $user['current_level'],
        $user['coins'],
        $user['total_xp'],
        $user['is_active'] ? 'Yes' : 'No',
        $user['completed_count'] ?? 0,
        $user['created_at'],
        $user['last_login']
    ]);
}

fclose($output);
exit;
