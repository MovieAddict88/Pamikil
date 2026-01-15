<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

require_student();
Csrf::requireValidToken();

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    json_response(['ok' => false, 'message' => 'Invalid JSON'], 400);
}

$user = current_user();
$activityId = (int)($payload['activity_id'] ?? 0);
$state = $payload['state'] ?? null;

if ($activityId <= 0) {
    json_response(['ok' => false, 'message' => 'Invalid activity_id'], 422);
}

$stateJson = json_encode($state, JSON_UNESCAPED_UNICODE);
if ($stateJson === false) {
    json_response(['ok' => false, 'message' => 'State could not be encoded'], 422);
}

if (strlen($stateJson) > 20000) {
    json_response(['ok' => false, 'message' => 'State too large'], 413);
}

db()->execute(
    'INSERT INTO activity_autosave (user_id, activity_id, state_json, updated_at)
     VALUES (:u, :a, :s, NOW())
     ON DUPLICATE KEY UPDATE state_json = VALUES(state_json), updated_at = NOW()',
    ['u' => (int)$user['id'], 'a' => $activityId, 's' => $stateJson]
);

json_response(['ok' => true]);
