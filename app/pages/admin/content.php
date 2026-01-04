<?php
declare(strict_types=1);
require_admin();

$admin = current_user();
$__pageTitle = 'Content · Admin · ' . (string)config_get('app.name', 'Pamikil Learning');

$categories = db()->fetchAll('SELECT id, name FROM categories ORDER BY sort_order, name');
$allActivities = db()->fetchAll('SELECT id, title, type, difficulty FROM activities ORDER BY title');

$types = [
    'quiz' => 'Multiple-choice quiz',
    'story' => 'Interactive story',
    'dragdrop' => 'Drag & drop',
    'flashcards' => 'Flashcards',
    'jigsaw' => 'Jigsaw puzzle',
    'crossword' => 'Crossword',
    'image_word' => 'Image-to-word matching',
];

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$activity = null;
$selectedAgeGroups = [];
$selectedPrereqs = [];
$selectedTags = '';

if ($editId > 0) {
    $activity = db()->fetch('SELECT * FROM activities WHERE id = :id', ['id' => $editId]);
    if ($activity) {
        $ags = db()->fetchAll('SELECT age_group FROM activity_age_groups WHERE activity_id = :id', ['id' => $editId]);
        $selectedAgeGroups = array_map(static fn($r) => (string)$r['age_group'], $ags);

        $prs = db()->fetchAll('SELECT prereq_activity_id FROM activity_prerequisites WHERE activity_id = :id', ['id' => $editId]);
        $selectedPrereqs = array_map(static fn($r) => (int)$r['prereq_activity_id'], $prs);

        $tags = db()->fetchAll('SELECT t.tag FROM activity_tags at JOIN tags t ON t.id = at.tag_id WHERE at.activity_id = :id ORDER BY t.tag', ['id' => $editId]);
        $selectedTags = implode(', ', array_map(static fn($r) => (string)$r['tag'], $tags));
    }
}

if (is_post() && ($_POST['action'] ?? '') === 'save_activity') {
    Csrf::requireValidToken();

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $type = (string)($_POST['type'] ?? '');
    $difficulty = (int)($_POST['difficulty'] ?? 1);
    $contentJson = trim((string)($_POST['content_json'] ?? '{}'));

    if ($title === '' || $categoryId <= 0 || !isset($types[$type])) {
        flash('error', 'Please fill in required fields (title, category, type).');
        redirect('/admin/content' . ($id ? ('?id=' . $id) : ''));
    }

    if ($difficulty < 1) $difficulty = 1;
    if ($difficulty > 5) $difficulty = 5;

    $decoded = json_decode($contentJson, true);
    if (!is_array($decoded)) {
        flash('error', 'Content JSON must be valid JSON.');
        redirect('/admin/content' . ($id ? ('?id=' . $id) : ''));
    }

    $ageGroups = $_POST['age_groups'] ?? [];
    $ageGroups = is_array($ageGroups) ? array_values(array_unique(array_filter(array_map('strval', $ageGroups)))) : [];
    $ageGroups = array_values(array_filter($ageGroups, static fn($ag) => in_array($ag, ['3-5', '6-8', '9-12'], true)));

    $tagsRaw = trim((string)($_POST['tags'] ?? ''));
    $tagList = [];
    if ($tagsRaw !== '') {
        foreach (preg_split('/[,\n]+/', $tagsRaw) as $t) {
            $t = trim($t);
            if ($t === '') continue;
            $t = strtolower($t);
            if (strlen($t) > 24) {
                $t = substr($t, 0, 24);
            }
            $tagList[] = $t;
        }
        $tagList = array_values(array_unique($tagList));
    }

    $prereqs = $_POST['prerequisites'] ?? [];
    $prereqIds = [];
    if (is_array($prereqs)) {
        foreach ($prereqs as $pid) {
            $pid = (int)$pid;
            if ($pid > 0 && $pid !== $id) {
                $prereqIds[] = $pid;
            }
        }
    }
    $prereqIds = array_values(array_unique($prereqIds));

    db()->beginTransaction();
    try {
        if ($id > 0) {
            db()->execute(
                'UPDATE activities
                 SET category_id = :c, type = :t, title = :title, description = :d, difficulty = :diff, content_json = :json, updated_at = NOW()
                 WHERE id = :id',
                ['c' => $categoryId, 't' => $type, 'title' => $title, 'd' => $description, 'diff' => $difficulty, 'json' => json_encode($decoded, JSON_UNESCAPED_UNICODE), 'id' => $id]
            );
        } else {
            db()->execute(
                'INSERT INTO activities (category_id, type, title, description, difficulty, content_json, created_at, updated_at)
                 VALUES (:c, :t, :title, :d, :diff, :json, NOW(), NOW())',
                ['c' => $categoryId, 't' => $type, 'title' => $title, 'd' => $description, 'diff' => $difficulty, 'json' => json_encode($decoded, JSON_UNESCAPED_UNICODE)]
            );
            $id = (int)db()->lastInsertId();
        }

        db()->execute('DELETE FROM activity_age_groups WHERE activity_id = :id', ['id' => $id]);
        foreach ($ageGroups as $ag) {
            db()->execute('INSERT INTO activity_age_groups (activity_id, age_group) VALUES (:id, :ag)', ['id' => $id, 'ag' => $ag]);
        }

        db()->execute('DELETE FROM activity_tags WHERE activity_id = :id', ['id' => $id]);
        foreach ($tagList as $tag) {
            db()->execute('INSERT IGNORE INTO tags (tag) VALUES (:t)', ['t' => $tag]);
            $tagIdRow = db()->fetch('SELECT id FROM tags WHERE tag = :t', ['t' => $tag]);
            if ($tagIdRow) {
                db()->execute('INSERT INTO activity_tags (activity_id, tag_id) VALUES (:a, :t)', ['a' => $id, 't' => (int)$tagIdRow['id']]);
            }
        }

        db()->execute('DELETE FROM activity_prerequisites WHERE activity_id = :id', ['id' => $id]);
        foreach ($prereqIds as $pid) {
            db()->execute('INSERT INTO activity_prerequisites (activity_id, prereq_activity_id) VALUES (:a, :p)', ['a' => $id, 'p' => $pid]);
        }

        db()->execute(
            'INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
             VALUES (:a, :act, :d, :ip, NOW())',
            [
                'a' => (int)$admin['id'],
                'act' => 'save_activity',
                'd' => sprintf('Saved activity #%d (%s)', $id, $title),
                'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]
        );

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    flash('success', 'Activity saved.');
    redirect('/admin/content?id=' . $id);
}

// Prefill form values
$form = [
    'id' => $activity['id'] ?? 0,
    'title' => $activity['title'] ?? '',
    'description' => $activity['description'] ?? '',
    'category_id' => $activity['category_id'] ?? ($categories[0]['id'] ?? 0),
    'type' => $activity['type'] ?? 'quiz',
    'difficulty' => $activity['difficulty'] ?? 1,
    'content_json' => $activity['content_json'] ?? "{}",
    'tags' => $selectedTags,
];

?>
<section class="page-header">
    <h1>Content management</h1>
    <p class="muted">Create and edit activities. Content is stored as JSON so the same platform supports many activity types without frameworks.</p>
</section>

<div class="grid admin-content">
    <section class="card">
        <h2 class="card__title"><?= $editId > 0 ? 'Edit activity' : 'Create new activity' ?></h2>

        <form method="post" class="form">
            <?= Csrf::inputField() ?>
            <input type="hidden" name="action" value="save_activity">
            <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

            <label class="field">
                <span class="field__label">Title *</span>
                <input class="field__input" name="title" value="<?= e((string)$form['title']) ?>" required>
            </label>

            <label class="field">
                <span class="field__label">Description</span>
                <textarea class="field__input" name="description" rows="3"><?= e((string)$form['description']) ?></textarea>
            </label>

            <div class="grid two">
                <label class="field">
                    <span class="field__label">Category *</span>
                    <select class="field__input" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= (int)$form['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span class="field__label">Type *</span>
                    <select class="field__input" name="type" required>
                        <?php foreach ($types as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= (string)$form['type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span class="field__label">Difficulty (1–5)</span>
                    <input class="field__input" type="number" min="1" max="5" name="difficulty" value="<?= (int)$form['difficulty'] ?>">
                </label>
            </div>

            <fieldset class="fieldset">
                <legend>Age groups</legend>
                <div class="checkbox-grid">
                    <?php foreach (['3-5' => '3–5', '6-8' => '6–8', '9-12' => '9–12'] as $ag => $label): ?>
                        <label class="checkbox">
                            <input type="checkbox" name="age_groups[]" value="<?= e($ag) ?>" <?= in_array($ag, $selectedAgeGroups, true) ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="muted">If none are selected, the activity is shown for all ages.</p>
            </fieldset>

            <label class="field">
                <span class="field__label">Tags (comma-separated)</span>
                <input class="field__input" name="tags" value="<?= e((string)$form['tags']) ?>" placeholder="e.g., animals, addition, phonics">
            </label>

            <label class="field">
                <span class="field__label">Prerequisites (unlock mechanism)</span>
                <select class="field__input" name="prerequisites[]" multiple size="6">
                    <?php foreach ($allActivities as $a): ?>
                        <?php if ((int)$a['id'] === (int)$form['id']) continue; ?>
                        <option value="<?= (int)$a['id'] ?>" <?= in_array((int)$a['id'], $selectedPrereqs, true) ? 'selected' : '' ?>><?= e($a['title']) ?> (<?= e($a['type']) ?> · L<?= (int)$a['difficulty'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="muted">Students must complete all prerequisites to unlock this activity.</p>
            </label>

            <label class="field">
                <span class="field__label">Content JSON *</span>
                <textarea class="field__input" name="content_json" rows="14" required spellcheck="false"><?= e((string)$form['content_json']) ?></textarea>
                <p class="muted">Tip: see docs/technical.md for JSON examples per activity type.</p>
            </label>

            <button class="btn btn--primary" type="submit">Save activity</button>
        </form>
    </section>

    <section class="card">
        <h2 class="card__title">All activities</h2>
        <div class="table-wrap" role="region" aria-label="Activities table" tabindex="0">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Type</th><th>Level</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($allActivities as $a): ?>
                        <tr>
                            <td><?= (int)$a['id'] ?></td>
                            <td><?= e($a['title']) ?></td>
                            <td><?= e($a['type']) ?></td>
                            <td><?= (int)$a['difficulty'] ?></td>
                            <td><a class="btn btn--small" href="<?= e(url('/admin/content?id=' . (int)$a['id'])) ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
