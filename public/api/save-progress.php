<?php
/**
 * Save Activity Progress API
 * Handles auto-save for activity sessions
 */

header('Content-Type: application/json');
require_once APP_ROOT . '/includes/functions.php';

// Validate request
if (!isPost() || !isAjax()) {
    jsonError('Invalid request', 405);
}

// Verify session
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isStudent()) {
    jsonError('Unauthorized', 401);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['session_id']) || !isset($input['session_data'])) {
    jsonError('Missing required parameters', 400);
}

// Validate session ownership
$activity = new Activity();
$session = $activity->getActiveSession($auth->getCurrentUser()['id'], null);

if (!$session || $session['id'] != $input['session_id']) {
    jsonError('Invalid session', 403);
}

// Save progress
$result = $activity->saveSession($input['session_id'], $input['session_data']);

if ($result) {
    jsonSuccess(null, 'Progress saved');
} else {
    jsonError('Failed to save progress', 500);
}
