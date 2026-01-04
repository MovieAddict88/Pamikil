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
if ($activityId <= 0) {
    json_response(['ok' => false, 'message' => 'Invalid activity_id'], 422);
}

db()->execute('DELETE FROM activity_autosave WHERE user_id = :u AND activity_id = :a', ['u' => (int)$user['id'], 'a' => $activityId]);

json_response(['ok' => true]);
