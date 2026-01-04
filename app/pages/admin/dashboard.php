<?php
declare(strict_types=1);
require_admin();

$admin = current_user();
$__pageTitle = 'Admin Dashboard · ' . (string)config_get('app.name', 'Pamikil Learning');

$stats = [
    'students' => (int)(db()->fetch('SELECT COUNT(*) AS c FROM users WHERE role = "student"')['c'] ?? 0),
    'parents' => (int)(db()->fetch('SELECT COUNT(*) AS c FROM users WHERE role = "parent"')['c'] ?? 0),
    'admins' => (int)(db()->fetch('SELECT COUNT(*) AS c FROM users WHERE role = "admin"')['c'] ?? 0),
    'activities' => (int)(db()->fetch('SELECT COUNT(*) AS c FROM activities')['c'] ?? 0),
];

$weekStart = (string)(db()->fetch('SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS d')['d'] ?? date('Y-m-d'));

$weekCompletions = (int)(db()->fetch(
    'SELECT COUNT(*) AS c FROM activity_progress WHERE status = "completed" AND completed_at >= :w',
    ['w' => $weekStart]
)['c'] ?? 0);

$weekCoins = (int)(db()->fetch(
    'SELECT COALESCE(SUM(delta),0) AS c FROM coins_ledger WHERE created_at >= :w',
    ['w' => $weekStart]
)['c'] ?? 0);

$recentLogs = db()->fetchAll(
    'SELECT action, details, created_at FROM admin_logs WHERE admin_id = :a ORDER BY created_at DESC LIMIT 8',
    ['a' => (int)$admin['id']]
);

?>
<section class="page-header">
    <h1>Admin dashboard</h1>
    <p class="muted">Manage content and view platform health.</p>
</section>

<div class="grid cards">
    <article class="card">
        <h2 class="card__title">Users</h2>
        <p class="card__text">Students: <strong><?= (int)$stats['students'] ?></strong><br>Parents: <strong><?= (int)$stats['parents'] ?></strong><br>Admins: <strong><?= (int)$stats['admins'] ?></strong></p>
        <a class="btn" href="<?= e(url('/admin/users')) ?>">Manage users</a>
    </article>

    <article class="card">
        <h2 class="card__title">Content</h2>
        <p class="card__text">Activities: <strong><?= (int)$stats['activities'] ?></strong></p>
        <a class="btn" href="<?= e(url('/admin/content')) ?>">Manage activities</a>
    </article>

    <article class="card">
        <h2 class="card__title">This week</h2>
        <p class="card__text">Starting: <strong><?= e($weekStart) ?></strong><br>Completions: <strong><?= (int)$weekCompletions ?></strong><br>Coins Δ: <strong><?= (int)$weekCoins ?></strong></p>
        <a class="btn" href="<?= e(url('/admin/reports')) ?>">View reports</a>
    </article>

    <article class="card">
        <h2 class="card__title">My recent admin actions</h2>
        <?php if (empty($recentLogs)): ?>
            <p class="muted">No logs yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($recentLogs as $l): ?>
                    <li class="list__item"><strong><?= e($l['action']) ?></strong> <span class="muted">(<?= e((string)$l['created_at']) ?>)</span><br><?= e((string)$l['details']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</div>
