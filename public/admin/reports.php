<?php
/**
 * Admin Reports
 * Platform analytics and reporting
 */

require_once APP_ROOT . '/templates/header.php';

requireAdmin();

$admin = new Admin();
$gamification = new Gamification();

// Get analytics data
$period = get('period', 'week');
$analytics = $admin->getAnalytics($period);

// Get leaderboards
$xpLeaderboard = $gamification->getLeaderboard('weekly_xp', 10);
$coinsLeaderboard = $gamification->getLeaderboard('weekly_coins', 10);
?>

<div class="admin-header">
    <div class="container">
        <div class="admin-nav">
            <h1>Reports & Analytics</h1>
            <div class="admin-menu">
                <a href="<?= SITE_URL ?>/admin" class=""><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                <a href="<?= SITE_URL ?>/admin/users"><i class="fa fa-users"></i> Users</a>
                <a href="<?= SITE_URL ?>/admin/activities"><i class="fa fa-tasks"></i> Activities</a>
                <a href="<?= SITE_URL ?>/admin/reports" class="active"><i class="fa fa-chart-bar"></i> Reports</a>
                <a href="<?= SITE_URL ?>/admin/settings"><i class="fa fa-cog"></i> Settings</a>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back to Site</a>
    </div>
</div>

<!-- Period Selector -->
<section class="admin-filters">
    <div class="container">
        <div class="period-selector">
            <a href="<?= SITE_URL ?>/admin/reports?period=week" 
               class="period-btn <?= $period == 'week' ? 'active' : '' ?>">This Week</a>
            <a href="<?= SITE_URL ?>/admin/reports?period=month" 
               class="period-btn <?= $period == 'month' ? 'active' : '' ?>">This Month</a>
            <a href="<?= SITE_URL ?>/admin/reports?period=year" 
               class="period-btn <?= $period == 'year' ? 'active' : '' ?>">This Year</a>
        </div>
        <div class="export-actions">
            <button class="btn btn-outline" onclick="exportReport('users')">
                <i class="fa fa-download"></i> Export Users
            </button>
            <button class="btn btn-outline" onclick="exportReport('activities')">
                <i class="fa fa-download"></i> Export Activities
            </button>
            <button class="btn btn-outline" onclick="exportReport('completions')">
                <i class="fa fa-download"></i> Export Completions
            </button>
        </div>
    </div>
</section>

<!-- Key Metrics -->
<section class="analytics-metrics">
    <div class="container">
        <div class="metrics-grid">
            <div class="metric-card primary">
                <div class="metric-icon">
                    <i class="fa fa-user-plus"></i>
                </div>
                <div class="metric-content">
                    <h3><?= $analytics['new_users'] ?></h3>
                    <p>New Users</p>
                    <span class="metric-period">This <?= ucfirst($period) ?></span>
                </div>
            </div>
            
            <div class="metric-card success">
                <div class="metric-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="metric-content">
                    <h3><?= $analytics['completions'] ?></h3>
                    <p>Activity Completions</p>
                    <span class="metric-period">This <?= ucfirst($period) ?></span>
                </div>
            </div>
            
            <div class="metric-card info">
                <div class="metric-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="metric-content">
                    <h3><?= $analytics['active_users'] ?></h3>
                    <p>Active Users</p>
                    <span class="metric-period">This <?= ucfirst($period) ?></span>
                </div>
            </div>
            
            <div class="metric-card warning">
                <div class="metric-icon">
                    <i class="fa fa-chart-line"></i>
                </div>
                <div class="metric-content">
                    <h3><?= round($analytics['avg_score'], 1) ?>%</h3>
                    <p>Average Score</p>
                    <span class="metric-period">This <?= ucfirst($period) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Charts Grid -->
<section class="analytics-charts">
    <div class="container">
        <div class="charts-grid">
            <!-- Completions by Subject -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa fa-book"></i> Completions by Subject</h3>
                </div>
                <div class="chart-content">
                    <?php if (empty($analytics['completions_by_subject'])): ?>
                        <p class="text-center text-muted">No data available</p>
                    <?php else: ?>
                        <div class="bar-chart">
                            <?php foreach ($analytics['completions_by_subject'] as $subject): ?>
                                <div class="bar-item">
                                    <span class="bar-label"><?= htmlspecialchars($subject['name']) ?></span>
                                    <div class="bar-wrapper">
                                        <div class="bar-fill" 
                                             style="width: 100%; 
                                                    background: <?= $subject['color'] ?>">
                                            <span class="bar-value"><?= $subject['count'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Daily Completions Trend -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa fa-chart-area"></i> Daily Completions</h3>
                </div>
                <div class="chart-content">
                    <?php if (empty($analytics['completions_by_day'])): ?>
                        <p class="text-center text-muted">No data available</p>
                    <?php else: ?>
                        <div class="trend-chart">
                            <?php 
                            $maxValue = max(array_column($analytics['completions_by_day'], 'count'));
                            foreach ($analytics['completions_by_day'] as $day): 
                                $height = $maxValue > 0 ? ($day['count'] / $maxValue) * 100 : 0;
                            ?>
                                <div class="trend-item">
                                    <div class="trend-bar" 
                                         style="height: <?= $height ?>%;"></div>
                                    <span class="trend-label"><?= date('M j', strtotime($day['date'])) ?></span>
                                    <span class="trend-value"><?= $day['count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Leaderboard Preview -->
<section class="analytics-leaderboard">
    <div class="container">
        <div class="leaderboard-grid">
            <div class="lb-card">
                <div class="lb-header">
                    <h3><i class="fa fa-bolt"></i> Top XP Earners</h3>
                </div>
                <div class="lb-list">
                    <?php foreach (array_slice($xpLeaderboard, 0, 5) as $entry): ?>
                        <div class="lb-entry">
                            <span class="lb-rank">#<?= $entry['rank'] ?></span>
                            <span class="lb-user"><?= htmlspecialchars($entry['username']) ?></span>
                            <span class="lb-score"><?= number_format($entry['score']) ?> XP</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="lb-card">
                <div class="lb-header">
                    <h3><i class="fa fa-coins"></i> Top Coin Collectors</h3>
                </div>
                <div class="lb-list">
                    <?php foreach (array_slice($coinsLeaderboard, 0, 5) as $entry): ?>
                        <div class="lb-entry">
                            <span class="lb-rank">#<?= $entry['rank'] ?></span>
                            <span class="lb-user"><?= htmlspecialchars($entry['username']) ?></span>
                            <span class="lb-score"><?= number_format($entry['score']) ?> Coins</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function exportReport(type) {
    const url = `/api/admin/export-${type}.php?period=<?= $period ?>`;
    const link = document.createElement('a');
    link.href = url;
    link.download = `pamikil_${type}_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    App.showToast(`Exporting ${type} report...`, 'info');
}
</script>

<style>
.analytics-metrics {
    padding: var(--spacing-xl) 0;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-xl);
}

.metric-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-md);
    border-left: 4px solid;
}

.metric-card.primary { border-color: var(--primary); }
.metric-card.success { border-color: var(--success); }
.metric-card.info { border-color: var(--info); }
.metric-card.warning { border-color: var(--warning); }

.metric-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-md);
    opacity: 0.8;
}

.metric-card.primary .metric-icon { color: var(--primary); }
.metric-card.success .metric-icon { color: var(--success); }
.metric-card.info .metric-icon { color: var(--info); }
.metric-card.warning .metric-icon { color: var(--warning); }

.metric-content h3 {
    font-size: var(--font-size-3xl);
    margin-bottom: var(--spacing-sm);
}

.metric-content p {
    font-size: var(--font-size-lg);
    color: var(--text-secondary);
    margin: 0;
}

.metric-period {
    display: block;
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin-top: var(--spacing-sm);
}

.analytics-charts {
    padding: var(--spacing-xl) 0;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--spacing-xl);
}

.chart-card {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.chart-header h3 {
    padding: var(--spacing-lg);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.chart-content {
    padding: var(--spacing-xl);
}

.bar-chart {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.bar-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.bar-label {
    min-width: 100px;
    font-weight: 600;
}

.bar-wrapper {
    flex: 1;
    background: #f0f0f0;
    border-radius: var(--radius-full);
    overflow: hidden;
}

.bar-fill {
    height: 30px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: var(--spacing-md);
    color: white;
    font-weight: 600;
    transition: width 1s ease;
}

.trend-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    height: 250px;
    gap: var(--spacing-sm);
}

.trend-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-sm);
    flex: 1;
}

.trend-bar {
    width: 100%;
    background: linear-gradient(180deg, var(--primary), var(--primary-light));
    border-radius: var(--radius-sm) var(--radius-sm) 0 0;
    transition: height 1s ease;
}

.trend-label {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
}

.trend-value {
    font-weight: 600;
}

.analytics-leaderboard {
    padding: var(--spacing-xl) 0;
}

.leaderboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--spacing-xl);
}

.lb-card {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.lb-header h3 {
    padding: var(--spacing-lg);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.lb-list {
    padding: var(--spacing-lg);
}

.lb-entry {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-md) 0;
    border-bottom: 1px solid #f0f0f0;
}

.lb-entry:last-child {
    border-bottom: none;
}

.lb-rank {
    font-weight: 600;
    min-width: 50px;
}

.lb-user {
    flex: 1;
}

.lb-score {
    font-weight: 600;
}

.period-selector {
    display: flex;
    gap: var(--spacing-md);
    align-items: center;
}

.period-btn {
    padding: var(--spacing-sm) var(--spacing-lg);
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.period-btn:hover,
.period-btn.active {
    border-color: var(--primary);
    background: var(--primary);
    color: white;
}

.export-actions {
    display: flex;
    gap: var(--spacing-md);
}
</style>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
