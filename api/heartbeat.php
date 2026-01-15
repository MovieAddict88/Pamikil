<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_student();
Csrf::requireValidToken();

$user = current_user();

$now = time();
$last = $_SESSION['__heartbeat_last'] ?? 0;
if (!is_int($last)) {
    $last = 0;
}

// Count at most 1 minute per 60 seconds to avoid runaway counting from multiple pings.
if ($now - $last >= 60) {
    $_SESSION['__heartbeat_last'] = $now;

    db()->execute(
        'INSERT INTO student_daily_usage (user_id, usage_date, minutes_used, updated_at)
         VALUES (:u, CURDATE(), 1, NOW())
         ON DUPLICATE KEY UPDATE minutes_used = minutes_used + 1, updated_at = NOW()',
        ['u' => (int)$user['id']]
    );
}

json_response(['ok' => true]);
