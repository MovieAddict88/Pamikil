<?php
declare(strict_types=1);
require_admin();

$__pageTitle = 'Reports · Admin · ' . (string)config_get('app.name', 'Pamikil Learning');

$weekStart = (string)(db()->fetch('SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS d')['d'] ?? date('Y-m-d'));

$byCategory = db()->fetchAll(
    'SELECT c.name, COUNT(*) AS completions, SUM(p.coins_earned) AS coins
     FROM activity_progress p
     JOIN activities a ON a.id = p.activity_id
     JOIN categories c ON c.id = a.category_id
     WHERE p.status = "completed" AND p.completed_at >= :w
     GROUP BY c.id
     ORDER BY completions DESC',
    ['w' => $weekStart]
);

$topActivities = db()->fetchAll(
    'SELECT a.title, a.type, COUNT(*) AS completions
     FROM activity_progress p
     JOIN activities a ON a.id = p.activity_id
     WHERE p.status = "completed" AND p.completed_at >= :w
     GROUP BY a.id
     ORDER BY completions DESC
     LIMIT 10',
    ['w' => $weekStart]
);

$topStudents = db()->fetchAll(
    'SELECT u.username, u.display_name, SUM(l.delta) AS coins
     FROM coins_ledger l
     JOIN users u ON u.id = l.user_id
     WHERE u.role = "student" AND l.created_at >= :w
     GROUP BY u.id
     ORDER BY coins DESC
     LIMIT 10',
    ['w' => $weekStart]
);

?>
<section class="page-header">
    <h1>Reports</h1>
    <p class="muted">Week starting <?= e($weekStart) ?></p>
</section>

<div class="grid">
    <section class="card">
        <h2 class="card__title">Completions by category</h2>
        <?php if (empty($byCategory)): ?>
            <p class="muted">No activity completions this week yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr><th>Category</th><th>Completions</th><th>Coins</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($byCategory as $r): ?>
                        <tr>
                            <td><?= e((string)$r['name']) ?></td>
                            <td><?= (int)$r['completions'] ?></td>
                            <td><?= (int)$r['coins'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__title">Top activities</h2>
        <ol class="leaderboard">
            <?php foreach ($topActivities as $r): ?>
                <li>
                    <span class="leaderboard__name"><?= e((string)$r['title']) ?></span>
                    <span class="leaderboard__score"><?= (int)$r['completions'] ?> plays</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="card">
        <h2 class="card__title">Top students (coins earned)</h2>
        <ol class="leaderboard">
            <?php foreach ($topStudents as $r): ?>
                <li>
                    <span class="leaderboard__name"><?= e((string)($r['display_name'] ?: $r['username'])) ?></span>
                    <span class="leaderboard__score"><?= (int)$r['coins'] ?> coins</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
</div>
