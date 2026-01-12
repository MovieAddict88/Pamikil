<?php
/**
 * Progress Page
 * Shows user progress and achievements
 */

require_once APP_ROOT . '/templates/header.php';

requireAuth();

$activity = new Activity();
$gamification = new Gamification();
$userId = $auth->getCurrentUser()['id'];

// Get user role
$role = $auth->getCurrentUser()['role'];

if ($role === 'parent') {
    // View children's progress
    $db = Database::getInstance();
    $children = $db->query(
        "SELECT * FROM users WHERE parent_id = ? AND role = 'student'",
        [$userId]
    );
    
    if (!empty($children)) {
        $childId = get('user', $children[0]['id']);
        $viewUserId = $childId;
    } else {
        $viewUserId = $userId;
    }
} else {
    $viewUserId = $userId;
}

// Get progress data
$stats = $activity->getUserStats($viewUserId);
$completedActivities = $activity->getUserCompletedActivities($viewUserId, 20);
$badges = $gamification->getUserBadges($viewUserId);
$allBadges = $gamification->getAllBadges();
$summary = $gamification->getUserSummary($viewUserId);
?>

<div class="page-header">
    <div class="container">
        <h1><?= $role === 'parent' ? 'Children\'s Progress' : 'My Progress' ?></h1>
        <p>Track learning achievements and growth</p>
    </div>
</div>

<?php if ($role === 'parent' && !empty($children)): ?>
<!-- Child Selector -->
<section class="child-selector">
    <div class="container">
        <div class="child-tabs">
            <?php foreach ($children as $child): ?>
                <a href="<?= SITE_URL ?>/progress?user=<?= $child['id'] ?>" 
                   class="child-tab <?= $viewUserId == $child['id'] ? 'active' : '' ?>">
                    <div class="tab-avatar">
                        <?php if ($child['avatar_image']): ?>
                            <img src="<?= htmlspecialchars($child['avatar_image']) ?>" alt="">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?= strtoupper(substr($child['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span><?= htmlspecialchars($child['first_name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Progress Overview -->
<section class="progress-overview">
    <div class="container">
        <div class="overview-cards">
            <div class="overview-card">
                <div class="card-icon">
                    <i class="fa fa-level-up-alt"></i>
                </div>
                <div class="card-content">
                    <h3>Level <?= $summary['user']['current_level'] ?></h3>
                    <p>Current Level</p>
                    <div class="progress">
                        <div class="progress-bar" 
                             style="width: <?= $summary['level_progress']['progress'] ?>%"></div>
                    </div>
                    <p class="progress-text">
                        <?= $summary['level_progress']['xp_in_level'] ?> XP to next level
                    </p>
                </div>
            </div>
            
            <div class="overview-card">
                <div class="card-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <h3><?= $summary['activities_completed'] ?></h3>
                    <p>Activities Completed</p>
                </div>
            </div>
            
            <div class="overview-card">
                <div class="card-icon">
                    <i class="fa fa-coins"></i>
                </div>
                <div class="card-content">
                    <h3><?= $summary['user']['coins'] ?></h3>
                    <p>Coins Earned</p>
                </div>
            </div>
            
            <div class="overview-card">
                <div class="card-icon">
                    <i class="fa fa-fire"></i>
                </div>
                <div class="card-content">
                    <h3><?= $summary['streak'] ?> Days</h3>
                    <p>Current Streak</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Progress by Subject -->
<section class="subject-progress">
    <div class="container">
        <h2 class="section-title">Progress by Subject</h2>
        <div class="subject-progress-grid">
            <?php if (empty($stats['by_subject'])): ?>
                <p class="text-muted">No activities completed yet. Start learning!</p>
            <?php else: ?>
                <?php foreach ($stats['by_subject'] as $subject): ?>
                    <div class="subject-progress-card">
                        <div class="subject-header">
                            <span class="subject-name" 
                                  style="color: <?= $subject['color'] ?>">
                                <?= htmlspecialchars($subject['name']) ?>
                            </span>
                            <span class="subject-count"><?= $subject['count'] ?> activities</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" 
                                 style="background: <?= $subject['color'] ?>;
                                        width: <?= min(100, ($subject['count'] / 5) * 100) ?>%">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Badges -->
<section class="badges-section">
    <div class="container">
        <h2 class="section-title">Badges (<?= count($badges) ?>/<?= count($allBadges) ?>)</h2>
        
        <?php if (empty($badges)): ?>
            <div class="empty-state text-center">
                <i class="fa fa-medal" style="font-size: 4rem; color: var(--secondary);"></i>
                <h3>No badges yet</h3>
                <p>Complete activities to earn badges!</p>
            </div>
        <?php else: ?>
            <div class="badges-grid">
                <?php foreach ($badges as $badge): ?>
                    <div class="badge-card earned">
                        <div class="badge-icon">
                            <i class="fa fa-trophy"></i>
                        </div>
                        <h4><?= htmlspecialchars($badge['name']) ?></h4>
                        <p class="badge-earned-date">Earned: <?= formatDate($badge['earned_at'], 'F j, Y') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Recent Activity -->
<section class="recent-activity">
    <div class="container">
        <h2 class="section-title">Recently Completed</h2>
        
        <?php if (empty($completedActivities)): ?>
            <div class="empty-state text-center">
                <i class="fa fa-tasks" style="font-size: 4rem; color: var(--secondary);"></i>
                <h3>No activities completed yet</h3>
                <p>
                    <?php if ($role === 'student'): ?>
                        <a href="<?= SITE_URL ?>/activities" class="btn btn-primary">Start Learning</a>
                    <?php else: ?>
                        Your child hasn't completed any activities yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($completedActivities as $item): ?>
                    <div class="activity-list-item">
                        <div class="activity-list-icon" 
                             style="background: <?= $item['color'] ?>">
                            <i class="fa fa-check"></i>
                        </div>
                        <div class="activity-list-content">
                            <h4><?= htmlspecialchars($item['title']) ?></h4>
                            <p>
                                <span class="subject-tag"><?= htmlspecialchars($item['subject_name']) ?></span>
                                <span class="completion-date">
                                    <?= timeAgo($item['completed_at']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="activity-list-stats">
                            <?= starRating($item['star_rating'] ?? 0) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
