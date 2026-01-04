<?php
declare(strict_types=1);
require_parent();

$user = current_user();
$__pageTitle = 'Parent Dashboard · ' . (string)config_get('app.name', 'Pamikil Learning');

if (is_post() && ($_POST['action'] ?? '') === 'link_child') {
    Csrf::requireValidToken();

    $childUsername = trim((string)($_POST['child_username'] ?? ''));
    $childEmail = trim((string)($_POST['child_email'] ?? ''));

    if ($childUsername === '' || $childEmail === '') {
        flash('error', 'Enter the child username and email.');
        redirect('/parent');
    }

    $child = db()->fetch(
        'SELECT id FROM users WHERE role = "student" AND username = :u AND email = :e LIMIT 1',
        ['u' => $childUsername, 'e' => $childEmail]
    );

    if (!$child) {
        flash('error', 'Could not find that student. Double-check the username and email.');
        redirect('/parent');
    }

    db()->beginTransaction();
    try {
        db()->execute(
            'INSERT IGNORE INTO parent_child (parent_id, child_id, created_at)
             VALUES (:p, :c, NOW())',
            ['p' => (int)$user['id'], 'c' => (int)$child['id']]
        );

        db()->execute(
            'INSERT IGNORE INTO parent_controls (parent_id, child_id, daily_minutes_limit, max_age_group, allowed_categories_json, updated_at)
             VALUES (:p, :c, 0, NULL, NULL, NOW())',
            ['p' => (int)$user['id'], 'c' => (int)$child['id']]
        );

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    flash('success', 'Child linked!');
    redirect('/parent');
}

$children = db()->fetchAll(
    'SELECT u.*
     FROM parent_child pc
     JOIN users u ON u.id = pc.child_id
     WHERE pc.parent_id = :p
     ORDER BY u.created_at DESC',
    ['p' => (int)$user['id']]
);

?>
<section class="page-header">
    <h1>Parent dashboard</h1>
    <p class="muted">Monitor progress, set time limits, and manage content restrictions.</p>
</section>

<div class="grid">
    <section class="card">
        <h2 class="card__title">Link a child account</h2>
        <p class="muted">Enter the student’s username and email. (You can find these in the child’s profile or during signup.)</p>

        <form method="post" class="form">
            <?= Csrf::inputField() ?>
            <input type="hidden" name="action" value="link_child">

            <label class="field">
                <span class="field__label">Child username</span>
                <input class="field__input" name="child_username" required>
            </label>

            <label class="field">
                <span class="field__label">Child email</span>
                <input class="field__input" type="email" name="child_email" required>
            </label>

            <button class="btn btn--primary" type="submit">Link child</button>
        </form>

        <p class="muted">Tip: For better safety on production, enable a “Parent Link Code” flow (see docs/technical.md).</p>
    </section>

    <section class="card">
        <h2 class="card__title">Linked children</h2>
        <?php if (empty($children)): ?>
            <p class="muted">No children linked yet.</p>
        <?php else: ?>
            <div class="grid cards">
                <?php foreach ($children as $child): ?>
                    <?php
                        $total = db()->fetch(
                            'SELECT COUNT(DISTINCT a.id) AS c
                             FROM activities a
                             LEFT JOIN activity_age_groups ag ON ag.activity_id = a.id
                             WHERE (:age = "" OR ag.age_group = :age OR ag.activity_id IS NULL)',
                            ['age' => (string)($child['age_group'] ?? '')]
                        );
                        $totalActivities = (int)($total['c'] ?? 0);
                        $completed = db()->fetch(
                            'SELECT COUNT(*) AS c FROM activity_progress WHERE user_id = :u AND status = "completed"',
                            ['u' => (int)$child['id']]
                        );
                        $completedCount = (int)($completed['c'] ?? 0);
                        $pct = $totalActivities > 0 ? (int)round(($completedCount / $totalActivities) * 100) : 0;

                        $usage = db()->fetch(
                            'SELECT minutes_used FROM student_daily_usage WHERE user_id = :u AND usage_date = CURDATE() LIMIT 1',
                            ['u' => (int)$child['id']]
                        );
                        $minutesUsed = (int)($usage['minutes_used'] ?? 0);

                        $controls = db()->fetch(
                            'SELECT daily_minutes_limit FROM parent_controls WHERE parent_id = :p AND child_id = :c LIMIT 1',
                            ['p' => (int)$user['id'], 'c' => (int)$child['id']]
                        );
                        $limit = (int)($controls['daily_minutes_limit'] ?? 0);

                        $recent = db()->fetchAll(
                            'SELECT a.title, p.completed_at
                             FROM activity_progress p
                             JOIN activities a ON a.id = p.activity_id
                             WHERE p.user_id = :u AND p.status = "completed"
                             ORDER BY p.completed_at DESC
                             LIMIT 3',
                            ['u' => (int)$child['id']]
                        );
                    ?>
                    <article class="card" aria-label="Child summary">
                        <h3 class="card__title"><?= e($child['display_name'] ?: $child['username']) ?></h3>
                        <p class="muted">Coins: <strong><?= (int)$child['coins'] ?></strong> · Age group: <strong><?= e((string)($child['age_group'] ?? '')) ?></strong></p>

                        <div class="progress" aria-label="Child progress">
                            <div class="progress__bar" style="--progress: <?= (int)$pct ?>%"></div>
                            <div class="progress__label"><?= (int)$pct ?>% complete</div>
                        </div>

                        <p class="muted">Time today: <strong><?= (int)$minutesUsed ?> min</strong><?= $limit > 0 ? (' / ' . (int)$limit . ' min limit') : '' ?></p>

                        <?php if (!empty($recent)): ?>
                            <p class="muted"><strong>Recent:</strong></p>
                            <ul class="list">
                                <?php foreach ($recent as $r): ?>
                                    <li class="list__item"><?= e($r['title']) ?> <span class="muted">(<?= e((string)$r['completed_at']) ?>)</span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <a class="btn" href="<?= e(url('/parent/settings?child_id=' . (int)$child['id'])) ?>">Controls</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
