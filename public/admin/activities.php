<?php
/**
 * Admin Activities Management
 * Create, edit, delete, and manage learning activities
 */

require_once APP_ROOT . '/templates/header.php';

requireAdmin();

$admin = new Admin();
$db = Database::getInstance();

// Get edit activity data
$editActivity = null;
$activityId = get('id');

if ($activityId && is_numeric($activityId)) {
    $editActivity = $admin->getActivity($activityId);
}

// Get activities list
$filters = [
    'subject' => get('subject'),
    'type' => get('type'),
    'is_active' => get('status') !== null ? (get('status') == '1' ? 1 : 0) : null,
    'search' => get('search')
];

$activities = $admin->getActivities($filters, 20, get('offset', 0) * 20);

// Get filter options
$subjects = $db->query("SELECT * FROM subjects WHERE is_active = 1 ORDER BY display_order");
$activityTypes = ['quiz' => 'Quiz', 'story' => 'Story', 'dragdrop' => 'Drag & Drop', 
                 'flashcard' => 'Flashcards', 'puzzle' => 'Puzzle', 'crossword' => 'Crossword', 'matching' => 'Matching'];
?>

<div class="admin-header">
    <div class="container">
        <div class="admin-nav">
            <h1>Activity Management</h1>
            <div class="admin-menu">
                <a href="<?= SITE_URL ?>/admin" class=""><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                <a href="<?= SITE_URL ?>/admin/users"><i class="fa fa-users"></i> Users</a>
                <a href="<?= SITE_URL ?>/admin/activities" class="active"><i class="fa fa-tasks"></i> Activities</a>
                <a href="<?= SITE_URL ?>/admin/reports"><i class="fa fa-chart-bar"></i> Reports</a>
                <a href="<?= SITE_URL ?>/admin/settings"><i class="fa fa-cog"></i> Settings</a>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back to Site</a>
    </div>
</div>

<!-- Filters -->
<section class="admin-filters">
    <div class="container">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" value="<?= htmlspecialchars(get('search')) ?>" 
                       placeholder="Search activities..." class="form-control">
            </div>
            <div class="filter-group">
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
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="1" <?= get('status') === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= get('status') === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= SITE_URL ?>/admin/activities" class="btn btn-outline">Clear</a>
                <button type="button" class="btn btn-success" id="addActivityBtn">
                    <i class="fa fa-plus"></i> Add Activity
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Activities Grid -->
<section class="admin-activities">
    <div class="container">
        <?php if (empty($activities)): ?>
            <div class="empty-state text-center">
                <i class="fa fa-tasks" style="font-size: 4rem; color: var(--secondary);"></i>
                <h3>No activities found</h3>
                <p>Adjust your filters or create a new activity.</p>
                <button class="btn btn-primary" id="addActivityBtn2">Add Activity</button>
            </div>
        <?php else: ?>
            <div class="activities-grid">
                <?php foreach ($activities as $item): ?>
                    <div class="activity-card <?= $item['is_active'] ? '' : 'inactive' ?>">
                        <div class="card-status">
                            <span class="status-dot status-<?= $item['is_active'] ? 'active' : 'inactive' ?>"></span>
                            <?php if ($item['is_featured']): ?>
                                <span class="featured-badge"><i class="fa fa-star"></i></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-thumbnail">
                            <?php if ($item['thumbnail']): ?>
                                <img src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="">
                            <?php else: ?>
                                <div class="activity-placeholder" 
                                     style="background-color: <?= $item['subject_color'] ?>">
                                    <span><?= substr($item['title'], 0, 2) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <span class="card-type"><?= ucfirst($item['activity_type']) ?></span>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="card-meta">
                                <span><i class="fa fa-book"></i> <?= htmlspecialchars($item['subject_name']) ?></span>
                                <span><i class="fa fa-signal"></i> <?= htmlspecialchars($item['difficulty_name']) ?></span>
                                <span><i class="fa fa-users"></i> Ages <?= $item['min_age'] ?>-<?= $item['max_age'] ?></span>
                            </p>
                            <div class="card-stats">
                                <span><i class="fa fa-check-circle"></i> <?= $item['completion_count'] ?> completed</span>
                                <span><i class="fa fa-coins"></i> <?= $item['coin_reward'] ?></span>
                                <span><i class="fa fa-bolt"></i> <?= $item['xp_reward'] ?> XP</span>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="<?= SITE_URL ?>/activity/<?= $item['slug'] ?>" 
                               class="btn btn-sm btn-outline" target="_blank">
                                <i class="fa fa-eye"></i> View
                            </a>
                            <button class="btn btn-sm btn-primary" 
                                    onclick="editActivity(<?= $item['id'] ?>)">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <?php if (!$item['is_active']): ?>
                                <button class="btn btn-sm btn-success" 
                                        onclick="toggleActivity(<?= $item['id'] ?>, 1)">
                                    <i class="fa fa-check"></i> Activate
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="toggleActivity(<?= $item['id'] ?>, 0)">
                                    <i class="fa fa-pause"></i> Deactivate
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="confirmDelete('activity', <?= $item['id'] ?>, '<?= htmlspecialchars($item['title']) ?>')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.activity-card {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: transform var(--transition-base);
    position: relative;
}

.activity-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.activity-card.inactive {
    opacity: 0.6;
}

.card-status {
    position: absolute;
    top: var(--spacing-md);
    right: var(--spacing-md);
    display: flex;
    gap: var(--spacing-sm);
    z-index: 1;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ddd;
}

.status-dot.status-active { background: var(--success); }
.status-dot.status-inactive { background: var(--danger); }

.featured-badge {
    color: var(--warning);
    font-size: 1.2rem;
}

.card-thumbnail {
    width: 100%;
    height: 150px;
    overflow: hidden;
}

.card-thumbnail img,
.card-thumbnail .activity-placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-content {
    padding: var(--spacing-md);
}

.card-type {
    display: inline-block;
    padding: 2px 8px;
    background: var(--primary);
    color: white;
    border-radius: var(--radius-sm);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: var(--spacing-sm);
}

.card-content h3 {
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-sm);
}

.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    margin-bottom: var(--spacing-md);
}

.card-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.card-stats {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.card-actions {
    display: flex;
    gap: var(--spacing-sm);
    padding: var(--spacing-md);
    border-top: 1px solid #f0f0f0;
}

.empty-state {
    padding: var(--spacing-xxl);
}
</style>

<script>
function toggleActivity(id, status) {
    const action = status ? 'activate' : 'deactivate';
    if (confirm(`Are you sure you want to ${action} this activity?`)) {
        fetch(`/api/admin/toggle-activity.php?id=${id}&status=${status}`, {
            method: 'POST'
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                App.showToast(`Activity ${action}d successfully!`, 'success');
                location.reload();
            } else {
                App.showToast(result.error || 'Failed to toggle activity', 'error');
            }
        });
    }
}

window.confirmDelete = function(type, id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        fetch(`/api/admin/delete-${type}.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                App.showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully!`, 'success');
                location.reload();
            } else {
                App.showToast(result.error || 'Failed to delete', 'error');
            }
        });
    }
};

function editActivity(id) {
    // Would open edit modal or redirect to edit page
    window.location.href = `<?= SITE_URL ?>/admin/activities?id=${id}`;
}

document.getElementById('addActivityBtn').addEventListener('click', () => {
    window.location.href = `<?= SITE_URL ?>/admin/activities?action=create`;
});

document.getElementById('addActivityBtn2').addEventListener('click', () => {
    window.location.href = `<?= SITE_URL ?>/admin/activities?action=create`;
});
</script>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
