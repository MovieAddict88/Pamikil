<?php
declare(strict_types=1);
require_student();

$user = current_user();
$__pageTitle = 'Student Dashboard · ' . (string)config_get('app.name', 'Pamikil Learning');

$ageGroup = (string)($user['age_group'] ?? '');

$totalActivitiesRow = db()->fetch(
    'SELECT COUNT(DISTINCT a.id) AS c
     FROM activities a
     LEFT JOIN activity_age_groups ag ON ag.activity_id = a.id
     WHERE (:age = "" OR ag.age_group = :age OR ag.activity_id IS NULL)',
    ['age' => $ageGroup]
);
$totalActivities = (int)($totalActivitiesRow['c'] ?? 0);

$completedRow = db()->fetch(
    'SELECT COUNT(*) AS c
     FROM activity_progress
     WHERE user_id = :u AND status = "completed"',
    ['u' => (int)$user['id']]
);
$completed = (int)($completedRow['c'] ?? 0);

$progressPercent = $totalActivities > 0 ? (int)round(($completed / $totalActivities) * 100) : 0;

$badges = db()->fetchAll(
    'SELECT b.name, b.description, b.icon, ub.earned_at
     FROM user_badges ub
     JOIN badges b ON b.id = ub.badge_id
     WHERE ub.user_id = :u
     ORDER BY ub.earned_at DESC
     LIMIT 12',
    ['u' => (int)$user['id']]
);

$recent = db()->fetchAll(
    'SELECT a.id, a.title, a.type, p.score_percent, p.stars, p.completed_at
     FROM activity_progress p
     JOIN activities a ON a.id = p.activity_id
     WHERE p.user_id = :u AND p.status = "completed"
     ORDER BY p.completed_at DESC
     LIMIT 8',
    ['u' => (int)$user['id']]
);

$startOfWeek = db()->fetch('SELECT DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS d');
$weekStart = (string)($startOfWeek['d'] ?? date('Y-m-d'));

$leaders = db()->fetchAll(
    'SELECT u.display_name, u.username, SUM(l.delta) AS coins
     FROM coins_ledger l
     JOIN users u ON u.id = l.user_id
     WHERE u.role = "student" AND l.created_at >= :weekStart
     GROUP BY u.id
     ORDER BY coins DESC
     LIMIT 10',
    ['weekStart' => $weekStart]
);

$avatar = db()->fetch(
    'SELECT
        b.asset_path AS base_asset,
        h.asset_path AS hat_asset,
        s.asset_path AS shirt_asset,
        p.asset_path AS pet_asset,
        bg.asset_path AS bg_asset
     FROM user_avatar ua
     LEFT JOIN avatar_items b ON b.id = ua.base_item_id
     LEFT JOIN avatar_items h ON h.id = ua.hat_item_id
     LEFT JOIN avatar_items s ON s.id = ua.shirt_item_id
     LEFT JOIN avatar_items p ON p.id = ua.pet_item_id
     LEFT JOIN avatar_items bg ON bg.id = ua.bg_item_id
     WHERE ua.user_id = :u',
    ['u' => (int)$user['id']]
);

?>
<section class="page-header">
    <h1>Hello, <?= e($user['display_name'] ?: $user['username']) ?>!</h1>
    <p class="muted">Coins: <strong><?= (int)$user['coins'] ?></strong> · Age group: <strong><?= e($ageGroup ?: '—') ?></strong></p>
</section>

<div class="grid dashboard">
    <section class="card">
        <h2 class="card__title">My progress</h2>
        <div class="progress" aria-label="Overall progress">
            <div class="progress__bar" style="--progress: <?= (int)$progressPercent ?>%"></div>
            <div class="progress__label"><?= (int)$progressPercent ?>% complete</div>
        </div>
        <p class="muted"><?= (int)$completed ?> of <?= (int)$totalActivities ?> activities completed.</p>
        <a class="btn btn--primary" href="<?= e(url('/activities')) ?>">Continue learning</a>
    </section>

    <section class="card">
        <h2 class="card__title">My avatar</h2>
        <div class="avatar" aria-label="Avatar preview">
            <?php if ($avatar && !empty($avatar['bg_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['bg_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['base_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['base_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['shirt_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['shirt_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['hat_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['hat_asset'], '/'))) ?>"><?php endif; ?>
            <?php if ($avatar && !empty($avatar['pet_asset'])): ?><img alt="" class="avatar__layer" src="<?= e(url('/' . ltrim((string)$avatar['pet_asset'], '/'))) ?>"><?php endif; ?>
        </div>
        <a class="btn" href="<?= e(url('/student/avatar')) ?>">Customize</a>
    </section>

    <section class="card">
        <h2 class="card__title">Badges</h2>
        <?php if (empty($badges)): ?>
            <p class="muted">No badges yet. Complete an activity to earn your first badge!</p>
        <?php else: ?>
            <ul class="badge-list">
                <?php foreach ($badges as $b): ?>
                    <li class="badge" title="<?= e($b['description']) ?>">
                        <span class="badge__icon" aria-hidden="true"><?= e($b['icon']) ?></span>
                        <span class="badge__name"><?= e($b['name']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__title">Weekly leaderboard</h2>
        <p class="muted">Week starting <?= e($weekStart) ?></p>
        <ol class="leaderboard">
            <?php foreach ($leaders as $row): ?>
                <li>
                    <span class="leaderboard__name"><?= e($row['display_name'] ?: $row['username']) ?></span>
                    <span class="leaderboard__score"><?= (int)$row['coins'] ?> coins</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="card">
        <h2 class="card__title">Recent completions</h2>
        <?php if (empty($recent)): ?>
            <p class="muted">You haven't completed any activities yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($recent as $r): ?>
                    <li class="list__item">
                        <a href="<?= e(url('/activity?id=' . (int)$r['id'])) ?>"><?= e($r['title']) ?></a>
                        <span class="muted"> · <?= (int)$r['stars'] ?>★ · <?= (int)$r['score_percent'] ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
