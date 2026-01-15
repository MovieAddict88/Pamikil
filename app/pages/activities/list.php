<?php
declare(strict_types=1);
$__pageTitle = 'Activities · ' . (string)config_get('app.name', 'Pamikil Learning');

$user = current_user();
$isStudent = $user && ($user['role'] ?? '') === 'student';

$categories = db()->fetchAll('SELECT id, name FROM categories ORDER BY sort_order, name');

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$ageGroup = isset($_GET['age_group']) ? (string)$_GET['age_group'] : '';
$search = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];

if ($categoryId > 0) {
    $where[] = 'a.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

if ($ageGroup !== '' && in_array($ageGroup, ['3-5', '6-8', '9-12'], true)) {
    $where[] = 'EXISTS (SELECT 1 FROM activity_age_groups f WHERE f.activity_id = a.id AND f.age_group = :age_group)';
    $params['age_group'] = $ageGroup;
}

if ($search !== '') {
    $where[] = '(a.title LIKE :q OR a.description LIKE :q OR EXISTS (
        SELECT 1 FROM activity_tags at
        JOIN tags t ON t.id = at.tag_id
        WHERE at.activity_id = a.id AND t.tag LIKE :q
    ))';
    $params['q'] = '%' . $search . '%';
}

$whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

if ($isStudent) {
    $params['user_id'] = (int)$user['id'];
    $sql = "
        SELECT
            a.id, a.type, a.title, a.description, a.difficulty,
            c.name AS category_name,
            GROUP_CONCAT(DISTINCT aag.age_group ORDER BY aag.age_group SEPARATOR ', ') AS age_groups,
            GROUP_CONCAT(DISTINCT t.tag ORDER BY t.tag SEPARATOR ', ') AS tags,
            EXISTS(SELECT 1 FROM activity_prerequisites ap WHERE ap.activity_id = a.id) AS has_prereq,
            p.status AS progress_status,
            uau.activity_id AS unlocked_id
        FROM activities a
        JOIN categories c ON c.id = a.category_id
        LEFT JOIN activity_age_groups aag ON aag.activity_id = a.id
        LEFT JOIN activity_tags at ON at.activity_id = a.id
        LEFT JOIN tags t ON t.id = at.tag_id
        LEFT JOIN activity_progress p ON p.activity_id = a.id AND p.user_id = :user_id
        LEFT JOIN user_activity_unlocks uau ON uau.activity_id = a.id AND uau.user_id = :user_id
        $whereSql
        GROUP BY a.id
        ORDER BY c.sort_order, a.difficulty, a.title
        LIMIT 200
    ";
} else {
    $sql = "
        SELECT
            a.id, a.type, a.title, a.description, a.difficulty,
            c.name AS category_name,
            GROUP_CONCAT(DISTINCT aag.age_group ORDER BY aag.age_group SEPARATOR ', ') AS age_groups,
            GROUP_CONCAT(DISTINCT t.tag ORDER BY t.tag SEPARATOR ', ') AS tags
        FROM activities a
        JOIN categories c ON c.id = a.category_id
        LEFT JOIN activity_age_groups aag ON aag.activity_id = a.id
        LEFT JOIN activity_tags at ON at.activity_id = a.id
        LEFT JOIN tags t ON t.id = at.tag_id
        $whereSql
        GROUP BY a.id
        ORDER BY c.sort_order, a.difficulty, a.title
        LIMIT 200
    ";
}

$activities = db()->fetchAll($sql, $params);

?>
<section class="page-header">
    <h1>Activities</h1>
    <p class="muted">Filter by subject, age group, and search tags.</p>
</section>

<form class="filters" method="get" aria-label="Activity filters">
    <label class="field">
        <span class="field__label">Search</span>
        <input class="field__input" name="q" value="<?= e($search) ?>" placeholder="Try: animals, addition, reading">
    </label>

    <label class="field">
        <span class="field__label">Category</span>
        <select class="field__input" name="category">
            <option value="0">All</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="field">
        <span class="field__label">Age group</span>
        <select class="field__input" name="age_group">
            <option value="">All</option>
            <option value="3-5" <?= $ageGroup === '3-5' ? 'selected' : '' ?>>3–5</option>
            <option value="6-8" <?= $ageGroup === '6-8' ? 'selected' : '' ?>>6–8</option>
            <option value="9-12" <?= $ageGroup === '9-12' ? 'selected' : '' ?>>9–12</option>
        </select>
    </label>

    <button class="btn" type="submit">Apply</button>
</form>

<div class="grid cards">
    <?php foreach ($activities as $a): ?>
        <?php
            $locked = false;
            $completed = false;
            if ($isStudent) {
                $completed = (($a['progress_status'] ?? '') === 'completed');
                $hasPrereq = (bool)($a['has_prereq'] ?? 0);
                $unlocked = !$hasPrereq || !empty($a['unlocked_id']) || $completed;
                $locked = !$unlocked;
            }
        ?>
        <article class="card" aria-label="Activity">
            <div class="card__meta">
                <span class="pill"><?= e($a['category_name']) ?></span>
                <span class="pill pill--soft"><?= e(strtoupper((string)$a['type'])) ?></span>
                <span class="pill" aria-label="Difficulty">Level <?= (int)$a['difficulty'] ?></span>
            </div>

            <h2 class="card__title">
                <?= e($a['title']) ?>
                <?php if ($locked): ?>
                    <span class="pill pill--warn" title="Locked">🔒 Locked</span>
                <?php elseif ($completed): ?>
                    <span class="pill pill--success" title="Completed">✅ Done</span>
                <?php endif; ?>
            </h2>

            <p class="card__text"><?= e($a['description']) ?></p>

            <p class="muted">
                <strong>Age:</strong> <?= e((string)($a['age_groups'] ?? 'All')) ?>
                <?php if (!empty($a['tags'])): ?>
                    <br><strong>Tags:</strong> <?= e((string)$a['tags']) ?>
                <?php endif; ?>
            </p>

            <div class="card__actions">
                <a class="btn btn--primary" href="<?= e(url('/activity?id=' . (int)$a['id'])) ?>">Open</a>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (empty($activities)): ?>
        <div class="panel">
            <p>No activities found. Try adjusting your filters.</p>
        </div>
    <?php endif; ?>
</div>
