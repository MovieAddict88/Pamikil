<?php
/**
 * Equip Avatar Item API
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

if (!isset($input['item_id']) || !isset($input['item_type'])) {
    jsonError('Missing required parameters', 400);
}

$gamification = new Gamification();
$result = $gamification->updateAvatarSettings(
    $auth->getCurrentUser()['id'],
    [$input['item_type'] . '_id' => $input['item_id']]
);

if ($result) {
    jsonSuccess(null, 'Item equipped successfully!');
} else {
    jsonError('Failed to equip item');
}
