<?php
/**
 * Purchase Avatar Item API
 */

header('Content-Type: application/json');
require_once APP_ROOT . '/includes/functions.php';

if (!isPost() || !isAjax()) {
    jsonError('Invalid request', 405);
}

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isStudent()) {
    jsonError('Unauthorized', 401);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['item_id'])) {
    jsonError('Missing item ID', 400);
}

$gamification = new Gamification();
$result = $gamification->purchaseAvatarItem($auth->getCurrentUser()['id'], $input['item_id']);

if ($result && $result['success']) {
    jsonSuccess($result, 'Item purchased successfully!');
} else {
    $errorMsg = $result['error'] ?? 'Purchase failed';
    $errorMap = [
        'insufficient_coins' => 'Not enough coins',
        'already_owned' => 'You already own this item',
        'level_requirement_not_met' => 'You need to reach a higher level'
    ];
    jsonError($errorMap[$errorMsg] ?? $errorMsg);
}
