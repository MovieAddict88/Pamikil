<?php
/**
 * Complete Activity API
 * Handles submission and scoring of activities
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

if (!isset($input['session_id']) || !isset($input['score']) || !isset($input['time_spent'])) {
    jsonError('Missing required parameters', 400);
}

// Complete activity session
$activity = new Activity();
$results = $activity->completeSession(
    $input['session_id'],
    $input['score'],
    $input['time_spent']
);

if ($results) {
    jsonSuccess($results, 'Activity completed successfully!');
} else {
    jsonError('Failed to complete activity', 500);
}
