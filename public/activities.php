<?php
/**
 * Activities Listing Page
 */

require_once APP_ROOT . '/templates/header.php';

// Get filters
$filters = [
    'subject' => get('subject'),
    'type' => get('type'),
    'difficulty' => get('difficulty'),
    'search' => get('search'),
    'user_level' => $auth->isLoggedIn() ? $auth->getCurrentUser()['current_level'] : 1
];

// Get activities
$activity = new Activity();
$activities = $activity->getActivities($filters, 20);

// Get filters options
$db = Database::getInstance();
$subjects = $db->query("SELECT * FROM subjects WHERE is_active = 1 ORDER BY display_order");
$difficulties = $db->query("SELECT * FROM difficulty_levels WHERE is_active = 1 ORDER BY level_number");
$activityTypes = $activity->getTypes();

// Get user's progress if logged in
$progress = [];
if ($auth->isLoggedIn() && $auth->isStudent()) {
    $completedActivities = $activity->getUserCompletedActivities($auth->getCurrentUser()['id'], 100);
    $progress = array_column($completedActivities, 'activity_id');
}
?>

<div class="page-header">
    <div class="container">
        <h1>Activities</h1>
        <p>Choose an activity to start learning!</p>
    </div>
</div>

<!-- Filters Section -->
<section class="filters-section">
    <div class="container">
        <form id="activityFilters" class="filters-form">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars(get('search')) ?>" 
                       placeholder="Search activities..." class="form-control">
            </div>
            
            <div class="filter-group">
                <label>Subject</label>
                <select name="subject" class="form-control">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>" 
                                <?= get('subject') == $subject['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($activityTypes as $type => $label): ?>
                        <option value="<?= $type ?>" 
                                <?= get('type') == $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Difficulty</label>
                <select name="difficulty" class="form-control">
                    <option value="">All Levels</option>
                    <?php foreach ($difficulties as $diff): ?>
                        <option value="<?= $diff['id'] ?>" 
                                <?= get('difficulty') == $diff['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($diff['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?= SITE_URL ?>/activities" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</section>

<!-- Activities Grid -->
<section class="activities-section">
    <div class="container">
        <?php if (empty($activities)): ?>
            <div class="empty-state text-center">
                <i class="fa fa-search" style="font-size: 4rem; color: var(--secondary);"></i>
                <h3>No activities found</h3>
                <p>Try adjusting your filters or search terms.</p>
                <a href="<?= SITE_URL ?>/activities" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="activities-grid">
                <?php foreach ($activities as $item): ?>
                    <a href="<?= SITE_URL ?>/activity/<?= $item['slug'] ?>" 
                       class="activity-card <?= in_array($item['id'], $progress) ? 'completed' : '' ?>">
                        <div class="activity-thumbnail">
                            <?php if ($item['thumbnail']): ?>
                                <img src="<?= htmlspecialchars($item['thumbnail']) ?>" 
                                     alt="<?= htmlspecialchars($item['title']) ?>">
                            <?php else: ?>
                                <div class="activity-placeholder" 
                                     style="background-color: <?= $item['subject_color'] ?>">
                                    <span><?= substr($item['title'], 0, 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (in_array($item['id'], $progress)): ?>
                                <div class="completed-badge">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="activity-info">
                            <span class="activity-type"><?= ucfirst($item['activity_type']) ?></span>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="activity-meta">
                                <span><i class="fa fa-star"></i> <?= htmlspecialchars($item['difficulty_name']) ?></span>
                                <span><i class="fa fa-coins"></i> +<?= $item['coin_reward'] ?></span>
                                <span><i class="fa fa-bolt"></i> +<?= $item['xp_reward'] ?> XP</span>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
