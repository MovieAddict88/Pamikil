<?php
declare(strict_types=1);
require_parent();

$parent = current_user();
$__pageTitle = 'Parent Controls · ' . (string)config_get('app.name', 'Pamikil Learning');

$children = db()->fetchAll(
    'SELECT u.id, u.username, u.display_name
     FROM parent_child pc
     JOIN users u ON u.id = pc.child_id
     WHERE pc.parent_id = :p
     ORDER BY u.created_at DESC',
    ['p' => (int)$parent['id']]
);

if (empty($children)) {
    echo '<div class="panel"><h1>Parent controls</h1><p>No linked children yet. Go to the parent dashboard to link a child account.</p></div>';
    return;
}

$childId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (int)$children[0]['id'];
$child = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $childId) {
        $child = $c;
        break;
    }
}
if (!$child) {
    $child = $children[0];
    $childId = (int)$child['id'];
}

$categories = db()->fetchAll('SELECT id, name FROM categories ORDER BY sort_order, name');

if (is_post() && ($_POST['action'] ?? '') === 'save_controls') {
    Csrf::requireValidToken();

    $daily = (int)($_POST['daily_minutes_limit'] ?? 0);
    if ($daily < 0) {
        $daily = 0;
    }
    if ($daily > 600) {
        $daily = 600;
    }

    $maxAge = trim((string)($_POST['max_age_group'] ?? ''));
    if ($maxAge === '') {
        $maxAge = null;
    }
    if ($maxAge !== null && !in_array($maxAge, ['3-5', '6-8', '9-12'], true)) {
        $maxAge = null;
    }

    $allowed = $_POST['allowed_categories'] ?? [];
    $allowedIds = [];
    if (is_array($allowed)) {
        foreach ($allowed as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $allowedIds[] = $id;
            }
        }
    }

    $allowedJson = empty($allowedIds) ? null : json_encode(array_values(array_unique($allowedIds)));

    db()->execute(
        'INSERT INTO parent_controls (parent_id, child_id, daily_minutes_limit, max_age_group, allowed_categories_json, updated_at)
         VALUES (:p, :c, :d, :m, :a, NOW())
         ON DUPLICATE KEY UPDATE
            daily_minutes_limit = VALUES(daily_minutes_limit),
            max_age_group = VALUES(max_age_group),
            allowed_categories_json = VALUES(allowed_categories_json),
            updated_at = NOW()',
        ['p' => (int)$parent['id'], 'c' => $childId, 'd' => $daily, 'm' => $maxAge, 'a' => $allowedJson]
    );

    flash('success', 'Controls saved.');
    redirect('/parent/settings?child_id=' . $childId);
}

$controls = db()->fetch(
    'SELECT * FROM parent_controls WHERE parent_id = :p AND child_id = :c LIMIT 1',
    ['p' => (int)$parent['id'], 'c' => $childId]
);

$selectedCategories = [];
if ($controls && is_string($controls['allowed_categories_json'] ?? null)) {
    $decoded = json_decode((string)$controls['allowed_categories_json'], true);
    if (is_array($decoded)) {
        $selectedCategories = array_map('intval', $decoded);
    }
}

$weekly = db()->fetchAll(
    'SELECT DATE(completed_at) AS day, COUNT(*) AS activities, SUM(coins_earned) AS coins
     FROM activity_progress
     WHERE user_id = :u AND status = "completed" AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(completed_at)
     ORDER BY day DESC',
    ['u' => $childId]
);

?>
<section class="page-header">
    <h1>Controls for <?= e($child['display_name'] ?: $child['username']) ?></h1>
</section>

<form class="filters" method="get">
    <label class="field">
        <span class="field__label">Child</span>
        <select class="field__input" name="child_id" onchange="this.form.submit()">
            <?php foreach ($children as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $childId ? 'selected' : '' ?>><?= e($c['display_name'] ?: $c['username']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <noscript><button class="btn" type="submit">Switch</button></noscript>
</form>

<div class="grid">
    <section class="card">
        <h2 class="card__title">Time limits</h2>
        <form method="post" class="form">
            <?= Csrf::inputField() ?>
            <input type="hidden" name="action" value="save_controls">

            <label class="field">
                <span class="field__label">Daily minutes limit (0 = no limit)</span>
                <input class="field__input" type="number" min="0" max="600" name="daily_minutes_limit" value="<?= e((string)($controls['daily_minutes_limit'] ?? '0')) ?>">
            </label>

            <label class="field">
                <span class="field__label">Max allowed age group (optional)</span>
                <select class="field__input" name="max_age_group">
                    <option value="" <?= empty($controls['max_age_group']) ? 'selected' : '' ?>>No limit</option>
                    <option value="3-5" <?= (($controls['max_age_group'] ?? '') === '3-5') ? 'selected' : '' ?>>3–5</option>
                    <option value="6-8" <?= (($controls['max_age_group'] ?? '') === '6-8') ? 'selected' : '' ?>>6–8</option>
                    <option value="9-12" <?= (($controls['max_age_group'] ?? '') === '9-12') ? 'selected' : '' ?>>9–12</option>
                </select>
            </label>

            <fieldset class="fieldset">
                <legend>Allowed categories (leave all unchecked to allow everything)</legend>
                <div class="checkbox-grid">
                    <?php foreach ($categories as $cat): ?>
                        <label class="checkbox">
                            <input type="checkbox" name="allowed_categories[]" value="<?= (int)$cat['id'] ?>" <?= in_array((int)$cat['id'], $selectedCategories, true) ? 'checked' : '' ?>>
                            <span><?= e($cat['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <button class="btn btn--primary" type="submit">Save controls</button>
        </form>
    </section>

    <section class="card">
        <h2 class="card__title">Weekly report (last 7 days)</h2>
        <?php if (empty($weekly)): ?>
            <p class="muted">No completed activities in the last 7 days.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Day</th>
                        <th scope="col">Activities</th>
                        <th scope="col">Coins</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weekly as $row): ?>
                        <tr>
                            <td><?= e((string)$row['day']) ?></td>
                            <td><?= (int)$row['activities'] ?></td>
                            <td><?= (int)$row['coins'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
