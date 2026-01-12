<?php
/**
 * Admin API - Export Activities
 */

require_once APP_ROOT . '/includes/functions.php';
requireAdmin();

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="activities_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$db = Database::getInstance();

// Query activities with stats
$query = "SELECT a.id, a.title, a.slug, a.activity_type, a.is_active, a.is_featured,
            a.xp_reward, a.coin_reward, a.passing_score, a.time_limit,
            s.name as subject_name, ag.name as age_group_name,
            d.name as difficulty_name, a.min_level_required,
            u.username as creator_username,
            a.created_at, a.updated_at,
            COUNT(p.id) as completion_count,
            AVG(p.best_score) as avg_score
         FROM activities a
         LEFT JOIN subjects s ON a.subject_id = s.id
         LEFT JOIN age_groups ag ON a.age_group_id = ag.id
         LEFT JOIN difficulty_levels d ON a.difficulty_id = d.id
         LEFT JOIN users u ON a.created_by = u.id
         LEFT JOIN user_activity_progress p ON a.id = p.activity_id
         GROUP BY a.id
         ORDER BY a.created_at DESC";

$activities = $db->query($query);

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'ID', 'Title', 'Slug', 'Type', 'Subject', 'Age Group', 'Difficulty',
    'XP Reward', 'Coin Reward', 'Passing Score', 'Time Limit', 'Min Level',
    'Creator', 'Completions', 'Average Score', 'Is Active', 'Is Featured',
    'Created At', 'Updated At'
]);

// Write data rows
foreach ($activities as $activity) {
    fputcsv($output, [
        $activity['id'],
        $activity['title'],
        $activity['slug'],
        $activity['activity_type'],
        $activity['subject_name'],
        $activity['age_group_name'],
        $activity['difficulty_name'],
        $activity['xp_reward'],
        $activity['coin_reward'],
        $activity['passing_score'] . '%',
        $activity['time_limit'] ?? 'Unlimited',
        $activity['min_level_required'],
        $activity['creator_username'],
        $activity['completion_count'] ?? 0,
        round($activity['avg_score'] ?? 0, 1) . '%',
        $activity['is_active'] ? 'Yes' : 'No',
        $activity['is_featured'] ? 'Yes' : 'No',
        $activity['created_at'],
        $activity['updated_at']
    ]);
}

fclose($output);
exit;
