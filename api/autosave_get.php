<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_student();

$user = current_user();
$activityId = isset($_GET['activity_id']) ? (int)$_GET['activity_id'] : 0;
if ($activityId <= 0) {
    json_response(['ok' => false, 'message' => 'Missing activity_id'], 422);
}

$row = db()->fetch(
    'SELECT state_json FROM activity_autosave WHERE user_id = :u AND activity_id = :a LIMIT 1',
    ['u' => (int)$user['id'], 'a' => $activityId]
);

$state = null;
if ($row && is_string($row['state_json'] ?? null)) {
    $decoded = json_decode((string)$row['state_json'], true);
    if (is_array($decoded) || is_string($decoded) || is_numeric($decoded) || is_bool($decoded)) {
        $state = $decoded;
    }
}

json_response(['ok' => true, 'state' => $state]);
