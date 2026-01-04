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
$score = (int)($payload['score_percent'] ?? -1);
$details = $payload['details'] ?? [];

if ($activityId <= 0 || $score < 0 || $score > 100) {
    json_response(['ok' => false, 'message' => 'Invalid input'], 422);
}

$activity = db()->fetch('SELECT id FROM activities WHERE id = :id LIMIT 1', ['id' => $activityId]);
if (!$activity) {
    json_response(['ok' => false, 'message' => 'Activity not found'], 404);
}

if (!is_array($details)) {
    $details = [];
}

$engine = new ActivityEngine(db());
$stars = ActivityEngine::starsFromScore($score);
$coins = ActivityEngine::coinsFromStars($stars);

try {
    $engine->completeActivity((int)$user['id'], $activityId, $score, $stars, $coins, $details);
    db()->execute('DELETE FROM activity_autosave WHERE user_id = :u AND activity_id = :a', ['u' => (int)$user['id'], 'a' => $activityId]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Failed to save progress'], 500);
}

$updated = db()->fetch('SELECT coins FROM users WHERE id = :u', ['u' => (int)$user['id']]);
$newCoins = (int)($updated['coins'] ?? 0);

$latestBadges = db()->fetchAll(
    'SELECT b.name, b.icon
     FROM user_badges ub
     JOIN badges b ON b.id = ub.badge_id
     WHERE ub.user_id = :u
     ORDER BY ub.earned_at DESC
     LIMIT 3',
    ['u' => (int)$user['id']]
);

json_response([
    'ok' => true,
    'stars' => $stars,
    'coins_earned' => $coins,
    'coins_total' => $newCoins,
    'badges' => $latestBadges,
]);
