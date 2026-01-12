<?php
/**
 * Activity Detail Page
 */

require_once APP_ROOT . '/templates/header.php';

$slug = get('slug');

if (!$slug) {
    setFlash('error', 'Activity not found');
    redirect(SITE_URL . '/activities');
}

$activity = new Activity();
$activityData = $activity->getBySlug($slug);

if (!$activityData) {
    setFlash('error', 'Activity not found');
    redirect(SITE_URL . '/activities');
}

// Check level requirement
if ($auth->isLoggedIn() && $auth->getCurrentUser()['current_level'] < $activityData['min_level_required']) {
    setFlash('warning', 'You need to reach level ' . $activityData['min_level_required'] . ' to access this activity.');
    redirect(SITE_URL . '/activities');
}

// Start or resume session
$sessionData = null;
if ($auth->isLoggedIn() && $auth->isStudent()) {
    $userId = $auth->getCurrentUser()['id'];
    
    // Check for existing active session
    $activeSession = $activity->getActiveSession($userId, $activityData['id']);
    
    if ($activeSession) {
        $sessionData = $activeSession;
    } else {
        // Start new session
        $sessionId = $activity->startSession($userId, $activityData['id']);
        $sessionData = $activity->getActiveSession($userId, $activityData['id']);
    }
    
    // Store session in App
    echo '<script>App.activitySession = ' . json_encode($sessionData) . ';</script>';
}

// Get user progress
$userProgress = null;
if ($auth->isLoggedIn()) {
    $userProgress = $activity->getUserProgress($auth->getCurrentUser()['id'], $activityData['id']);
}
?>

<div class="activity-container">
    <div class="activity-header">
        <a href="<?= SITE_URL ?>/activities" class="btn btn-outline">
            <i class="fa fa-arrow-left"></i> Back to Activities
        </a>
        <h1><?= htmlspecialchars($activityData['title']) ?></h1>
        <p class="activity-description"><?= htmlspecialchars($activityData['description']) ?></p>
    </div>
    
    <div class="activity-info-bar">
        <div class="activity-stat">
            <i class="fa fa-book"></i>
            <span><?= htmlspecialchars($activityData['subject_name']) ?></span>
        </div>
        <div class="activity-stat">
            <i class="fa fa-layer-group"></i>
            <span><?= ucfirst($activityData['activity_type']) ?></span>
        </div>
        <div class="activity-stat">
            <i class="fa fa-signal"></i>
            <span><?= htmlspecialchars($activityData['difficulty_name']) ?></span>
        </div>
        <div class="activity-stat">
            <i class="fa fa-users"></i>
            <span>Ages <?= $activityData['min_age'] ?>-<?= $activityData['max_age'] ?></span>
        </div>
        <?php if ($activityData['time_limit']): ?>
            <div class="activity-stat">
                <i class="fa fa-clock"></i>
                <span><?= formatDuration($activityData['time_limit']) ?></span>
            </div>
        <?php endif; ?>
        <div class="activity-stat">
            <i class="fa fa-star"></i>
            <span>Passing: <?= $activityData['passing_score'] ?>%</span>
        </div>
    </div>
    
    <?php if ($activityData['instructions']): ?>
        <div class="activity-instructions">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <?= htmlspecialchars($activityData['instructions']) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($userProgress && $userProgress['completed']): ?>
        <div class="completed-message">
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                <strong>You've completed this activity!</strong>
                <p>Best Score: <?= $userProgress['best_score'] ?>% | 
                   Stars: <?= starRating($userProgress['star_rating'] ?? 0) ?></p>
                <a href="<?= SITE_URL ?>/activity/<?= $activityData['slug'] ?>" 
                   class="btn btn-primary">Try Again</a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Activity Content Container -->
    <div id="activityContent" class="activity-content">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin"></i>
            <p>Loading activity...</p>
        </div>
    </div>
</div>

<script>
// Initialize activity based on type
document.addEventListener('DOMContentLoaded', function() {
    const activityData = <?= json_encode($activityData) ?>;
    const sessionData = App.activitySession || {};
    
    const contentDiv = document.getElementById('activityContent');
    const activityType = activityData.activity_type;
    
    // Load appropriate activity handler
    switch (activityType) {
        case 'quiz':
        case 'story':
            contentDiv.innerHTML = '<div id="quizContainer"></div>';
            QuizActivity.init(activityData.content);
            break;
            
        case 'flashcard':
            contentDiv.innerHTML = '<div id="flashcardContainer"></div>';
            FlashcardActivity.init(activityData.content);
            break;
            
        case 'dragdrop':
            // Drag and Drop would be implemented similarly
            contentDiv.innerHTML = '<p>Drag and Drop activity loading...</p>';
            break;
            
        case 'puzzle':
            // Puzzle would be implemented similarly
            contentDiv.innerHTML = '<p>Puzzle activity loading...</p>';
            break;
            
        case 'crossword':
            // Crossword would be implemented similarly
            contentDiv.innerHTML = '<p>Crossword activity loading...</p>';
            break;
            
        case 'matching':
            // Matching would be implemented similarly
            contentDiv.innerHTML = '<p>Matching activity loading...</p>';
            break;
            
        default:
            contentDiv.innerHTML = '<p>Unknown activity type.</p>';
    }
    
    // Auto-resume session if available
    if (sessionData && sessionData.data) {
        // Would implement session resume logic here
        console.log('Resuming session:', sessionData);
    }
});
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
