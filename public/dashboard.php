<?php
/**
 * Parent Dashboard Page
 */

require_once APP_ROOT . '/templates/header.php';

requireAuth('parent');

$userId = $auth->getCurrentUser()['id'];

// Get children linked to this parent
$db = Database::getInstance();
$children = $db->query(
    "SELECT u.*, COUNT(p.id) as activities_completed,
            SUM(CASE WHEN p.completed = 1 THEN 1 ELSE 0 END) as completed_count
     FROM users u
     LEFT JOIN user_activity_progress p ON u.id = p.user_id
     WHERE u.parent_id = ? AND u.role = 'student'
     GROUP BY u.id",
    [$userId]
);

// Get parent settings for each child
$activity = new Activity();
foreach ($children as &$child) {
    $child['progress'] = $activity->getUserStats($child['id']);
}
?>

<div class="page-header">
    <div class="container">
        <h1>Parent Dashboard</h1>
        <p>Monitor and manage your children's learning progress</p>
    </div>
</div>

<!-- Stats Overview -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= count($children) ?></h3>
                    <p>Children</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= array_sum(array_column($children, 'activities_completed')) ?></h3>
                    <p>Total Activities</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <h3>
                        <?= max(array_map(function($c) { return $c['progress']['total_completed'] ?? 0; }, $children)) ?>
                    </h3>
                    <p>Most Completed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>This Week</h3>
                    <p>Activity Period</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Children List -->
<section class="children-section">
    <div class="container">
        <div class="section-header">
            <h2>My Children</h2>
            <a href="<?= SITE_URL ?>/register" class="btn btn-primary">
                <i class="fa fa-plus"></i> Add Child
            </a>
        </div>
        
        <?php if (empty($children)): ?>
            <div class="empty-state text-center">
                <i class="fa fa-child" style="font-size: 4rem; color: var(--secondary);"></i>
                <h3>No children linked yet</h3>
                <p>Add your child's account to monitor their progress</p>
                <a href="<?= SITE_URL ?>/register" class="btn btn-primary">
                    Create Child Account
                </a>
            </div>
        <?php else: ?>
            <div class="children-grid">
                <?php foreach ($children as $child): ?>
                    <div class="child-card">
                        <div class="child-header">
                            <div class="child-avatar">
                                <?php if ($child['avatar_image']): ?>
                                    <img src="<?= htmlspecialchars($child['avatar_image']) ?>" 
                                         alt="<?= htmlspecialchars($child['username']) ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <?= strtoupper(substr($child['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="child-info">
                                <h3><?= htmlspecialchars($child['first_name']) ?></h3>
                                <p class="child-username">@<?= htmlspecialchars($child['username']) ?></p>
                            </div>
                            <div class="child-actions">
                                <a href="<?= SITE_URL ?>/progress?user=<?= $child['id'] ?>" 
                                   class="btn btn-sm btn-outline">
                                    <i class="fa fa-chart-line"></i> Progress
                                </a>
                                <a href="<?= SITE_URL ?>/dashboard?settings=<?= $child['id'] ?>" 
                                   class="btn btn-sm btn-outline">
                                    <i class="fa fa-cog"></i> Controls
                                </a>
                            </div>
                        </div>
                        
                        <div class="child-stats">
                            <div class="child-stat">
                                <span class="stat-label">Level</span>
                                <span class="stat-value"><?= $child['current_level'] ?></span>
                            </div>
                            <div class="child-stat">
                                <span class="stat-label">XP</span>
                                <span class="stat-value"><?= $child['total_xp'] ?></span>
                            </div>
                            <div class="child-stat">
                                <span class="stat-label">Coins</span>
                                <span class="stat-value">
                                    <i class="fa fa-coins"></i> <?= $child['coins'] ?>
                                </span>
                            </div>
                            <div class="child-stat">
                                <span class="stat-label">Completed</span>
                                <span class="stat-value"><?= $child['progress']['total_completed'] ?? 0 ?></span>
                            </div>
                        </div>
                        
                        <div class="child-progress">
                            <h4>Overall Progress</h4>
                            <div class="progress">
                                <?php 
                                $totalActivities = 15; // This would be dynamic based on available activities
                                $completed = $child['progress']['total_completed'] ?? 0;
                                $progressPercent = min(100, ($completed / max(1, $totalActivities)) * 100);
                                ?>
                                <div class="progress-bar" style="width: <?= $progressPercent ?>%"></div>
                            </div>
                            <p class="progress-text"><?= $completed ?> of <?= $totalActivities ?> activities</p>
                        </div>
                        
                        <?php if (!empty($child['progress']['by_subject'])): ?>
                        <div class="child-subjects">
                            <h4>Subjects</h4>
                            <div class="subject-list">
                                <?php foreach (array_slice($child['progress']['by_subject'], 0, 4) as $subject): ?>
                                    <span class="subject-badge" style="background: <?= $subject['color'] ?>">
                                        <?= htmlspecialchars($subject['name']) ?>: <?= $subject['count'] ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Quick Actions -->
<section class="actions-section">
    <div class="container">
        <h2>Quick Actions</h2>
        <div class="actions-grid">
            <a href="<?= SITE_URL ?>/progress" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-chart-bar"></i>
                </div>
                <h3>View All Progress</h3>
                <p>Detailed reports for all children</p>
            </a>
            <a href="<?= SITE_URL ?>/profile" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-cog"></i>
                </div>
                <h3>Settings</h3>
                <p>Manage notifications and preferences</p>
            </a>
            <a href="/docs/PARENT_GUIDE.md" target="_blank" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-book"></i>
                </div>
                <h3>Parent Guide</h3>
                <p>Tips and best practices</p>
            </a>
            <a href="<?= ADMIN_EMAIL ?>" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-envelope"></i>
                </div>
                <h3>Contact Support</h3>
                <p>Get help when needed</p>
            </a>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
