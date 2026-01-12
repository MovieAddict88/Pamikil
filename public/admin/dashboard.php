<?php
/**
 * Admin Dashboard
 * Main admin panel with statistics and management
 */

require_once APP_ROOT . '/templates/header.php';

requireAdmin();

$admin = new Admin();
$stats = $admin->getStats();
?>

<!-- Admin Header -->
<div class="admin-header">
    <div class="container">
        <div class="admin-nav">
            <h1>Admin Dashboard</h1>
            <div class="admin-menu">
                <a href="<?= SITE_URL ?>/admin" class="active">
                    <i class="fa fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?= SITE_URL ?>/admin/users">
                    <i class="fa fa-users"></i> Users
                </a>
                <a href="<?= SITE_URL ?>/admin/activities">
                    <i class="fa fa-tasks"></i> Activities
                </a>
                <a href="<?= SITE_URL ?>/admin/reports">
                    <i class="fa fa-chart-bar"></i> Reports
                </a>
                <a href="<?= SITE_URL ?>/admin/settings">
                    <i class="fa fa-cog"></i> Settings
                </a>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/" class="btn btn-outline">
            <i class="fa fa-arrow-left"></i> Back to Site
        </a>
    </div>
</div>

<!-- Stats Overview -->
<section class="admin-stats">
    <div class="container">
        <h2 class="section-title">Platform Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fa fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_users'] ?></h3>
                    <p>Total Users</p>
                    <span class="stat-change">
                        <?= $stats['active_users'] ?> active
                    </span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fa fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['students'] ?></h3>
                    <p>Students</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fa fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_activities'] ?></h3>
                    <p>Activities</p>
                    <span class="stat-change">
                        <?= $stats['active_activities'] ?> active
                    </span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $stats['total_completions'] ?></h3>
                    <p>Completions</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Activity -->
<section class="admin-recent">
    <div class="container">
        <div class="admin-grid">
            <!-- Recent Registrations -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fa fa-user-plus"></i> Recent Registrations</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['recent_registrations'])): ?>
                        <p class="text-muted">No recent registrations</p>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($stats['recent_registrations'] as $user): ?>
                                <li>
                                    <span class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                                    <span class="user-role badge"><?= $user['role'] ?></span>
                                    <span class="user-date"><?= timeAgo($user['created_at']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Completions -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fa fa-trophy"></i> Recent Completions</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['recent_completions'])): ?>
                        <p class="text-muted">No recent completions</p>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($stats['recent_completions'] as $completion): ?>
                                <li>
                                    <span class="activity-name"><?= htmlspecialchars($completion['title']) ?></span>
                                    <span class="activity-user"><?= htmlspecialchars($completion['username']) ?></span>
                                    <span class="activity-date"><?= timeAgo($completion['completed_at']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Actions -->
<section class="admin-actions">
    <div class="container">
        <h2 class="section-title">Quick Actions</h2>
        <div class="actions-grid">
            <a href="<?= SITE_URL ?>/admin/users" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-user-plus"></i>
                </div>
                <h3>Add User</h3>
                <p>Create new user account</p>
            </a>
            
            <a href="<?= SITE_URL ?>/admin/activities" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-plus-circle"></i>
                </div>
                <h3>Add Activity</h3>
                <p>Create new learning activity</p>
            </a>
            
            <a href="<?= SITE_URL ?>/admin/reports" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-download"></i>
                </div>
                <h3>Export Reports</h3>
                <p>Download analytics data</p>
            </a>
            
            <a href="<?= SITE_URL ?>/admin/settings" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-tools"></i>
                </div>
                <h3>System Settings</h3>
                <p>Configure platform settings</p>
            </a>
        </div>
    </div>
</section>

<style>
.admin-header {
    background: var(--bg-dark);
    color: white;
    padding: var(--spacing-xl) 0;
}

.admin-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.admin-menu {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.admin-menu a {
    color: var(--text-light);
    opacity: 0.8;
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.admin-menu a:hover,
.admin-menu a.active {
    background: var(--primary);
    opacity: 1;
}

.admin-stats {
    padding: var(--spacing-xl) 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
}

.admin-recent {
    padding: var(--spacing-xl) 0;
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--spacing-xl);
}

.admin-card {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.card-header {
    padding: var(--spacing-lg);
    border-bottom: 1px solid #e0e0e0;
}

.card-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.card-body {
    padding: var(--spacing-lg);
}

.recent-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recent-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-md) 0;
    border-bottom: 1px solid #f0f0f0;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.recent-list li:last-child {
    border-bottom: none;
}

.user-role.badge {
    font-size: var(--font-size-xs);
    padding: 2px 8px;
}

.admin-actions {
    padding: var(--spacing-xl) 0;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
}

.action-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    text-align: center;
    box-shadow: var(--shadow-md);
    transition: transform var(--transition-base), box-shadow var(--transition-base);
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.action-icon {
    font-size: 3rem;
    color: var(--primary);
    margin-bottom: var(--spacing-md);
}

.action-card h3 {
    margin-bottom: var(--spacing-sm);
}

.action-card p {
    color: var(--text-secondary);
    margin: 0;
}

@media (max-width: 768px) {
    .admin-nav {
        flex-direction: column;
        align-items: stretch;
    }
    
    .admin-menu {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
