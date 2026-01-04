<?php
declare(strict_types=1);
$__pageTitle = 'Activity · ' . (string)config_get('app.name', 'Pamikil Learning');

$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($activityId <= 0) {
    redirect('/activities');
}

$activity = db()->fetch(
    'SELECT a.*, c.name AS category_name
     FROM activities a
     JOIN categories c ON c.id = a.category_id
     WHERE a.id = :id
     LIMIT 1',
    ['id' => $activityId]
);

if (!$activity) {
    http_response_code(404);
    echo '<div class="panel"><h1>Activity not found</h1></div>';
    return;
}

$__pageTitle = (string)$activity['title'] . ' · ' . (string)config_get('app.name', 'Pamikil Learning');

$ageGroups = db()->fetchAll('SELECT age_group FROM activity_age_groups WHERE activity_id = :id ORDER BY age_group', ['id' => $activityId]);
$tags = db()->fetchAll(
    'SELECT t.tag FROM activity_tags at JOIN tags t ON t.id = at.tag_id WHERE at.activity_id = :id ORDER BY t.tag',
    ['id' => $activityId]
);

$user = current_user();
$isStudent = $user && ($user['role'] ?? '') === 'student';

$locked = false;
if ($isStudent) {
    $hasPrereq = (bool)(db()->fetch('SELECT 1 FROM activity_prerequisites WHERE activity_id = :id LIMIT 1', ['id' => $activityId]) !== null);
    if ($hasPrereq) {
        $unlocked = db()->fetch(
            'SELECT 1 FROM user_activity_unlocks WHERE user_id = :u AND activity_id = :a LIMIT 1',
            ['u' => (int)$user['id'], 'a' => $activityId]
        );
        $completed = db()->fetch(
            'SELECT 1 FROM activity_progress WHERE user_id = :u AND activity_id = :a AND status = "completed" LIMIT 1',
            ['u' => (int)$user['id'], 'a' => $activityId]
        );
        $locked = !$unlocked && !$completed;
    }

    // Parent controls: category restrictions, age restrictions, and time limits
    $control = db()->fetch(
        'SELECT pc.*
         FROM parent_controls pc
         WHERE pc.child_id = :child
         ORDER BY pc.updated_at DESC
         LIMIT 1',
        ['child' => (int)$user['id']]
    );

    if ($control) {
        $maxAge = (string)($control['max_age_group'] ?? '');
        if ($maxAge !== '' && in_array($maxAge, ['3-5', '6-8', '9-12'], true)) {
            $childGroup = (string)($user['age_group'] ?? '');
            $order = ['3-5' => 1, '6-8' => 2, '9-12' => 3];
            if (isset($order[$childGroup], $order[$maxAge]) && $order[$childGroup] > $order[$maxAge]) {
                $locked = true;
            }
        }

        $allowed = $control['allowed_categories_json'] ?? null;
        if (is_string($allowed) && $allowed !== '') {
            $ids = json_decode($allowed, true);
            if (is_array($ids) && !empty($ids)) {
                if (!in_array((int)$activity['category_id'], array_map('intval', $ids), true)) {
                    $locked = true;
                }
            }
        }

        $dailyLimit = (int)($control['daily_minutes_limit'] ?? 0);
        if ($dailyLimit > 0) {
            $usage = db()->fetch(
                'SELECT minutes_used FROM student_daily_usage WHERE user_id = :u AND usage_date = CURDATE() LIMIT 1',
                ['u' => (int)$user['id']]
            );
            $minutesUsed = (int)($usage['minutes_used'] ?? 0);
            if ($minutesUsed >= $dailyLimit) {
                $locked = true;
                flash('error', 'Time limit reached for today. Ask a parent to increase your limit.');
            }
        }
    }
}

$type = (string)$activity['type'];
$jsMap = [
    'quiz' => '/assets/js/activities/quiz.js',
    'story' => '/assets/js/activities/story.js',
    'dragdrop' => '/assets/js/activities/dragdrop.js',
    'flashcards' => '/assets/js/activities/flashcards.js',
    'jigsaw' => '/assets/js/activities/jigsaw.js',
    'crossword' => '/assets/js/activities/crossword.js',
    'image_word' => '/assets/js/activities/image_word.js',
];

$jsFile = $jsMap[$type] ?? null;

$content = $activity['content_json'] ?? '{}';
$contentDecoded = is_string($content) ? json_decode($content, true) : null;
if (!is_array($contentDecoded)) {
    $contentDecoded = [];
}

?>
<section class="page-header">
    <div class="page-header__row">
        <div>
            <h1><?= e($activity['title']) ?></h1>
            <p class="muted"><?= e($activity['category_name']) ?> · Level <?= (int)$activity['difficulty'] ?> · Type: <?= e($type) ?></p>
        </div>
        <div class="page-header__actions">
            <a class="btn" href="<?= e(url('/activities')) ?>">Back</a>
        </div>
    </div>

    <p><?= e((string)$activity['description']) ?></p>

    <p class="muted">
        <strong>Age:</strong>
        <?php if (empty($ageGroups)): ?>All<?php else: ?>
            <?php foreach ($ageGroups as $i => $ag): ?>
                <?= $i ? ', ' : '' ?><?= e($ag['age_group']) ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
            <br><strong>Tags:</strong>
            <?php foreach ($tags as $i => $t): ?>
                <?= $i ? ', ' : '' ?><?= e($t['tag']) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </p>
</section>

<?php if ($locked): ?>
    <div class="panel">
        <h2>Locked</h2>
        <p>This activity is currently locked. Complete the required earlier activities or ask a parent to adjust restrictions.</p>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="panel" data-activity-player data-activity-id="<?= (int)$activityId ?>" data-activity-type="<?= e($type) ?>" data-requires-heartbeat="<?= $isStudent ? '1' : '0' ?>">
    <?php if (!$isStudent): ?>
        <div class="alert alert--info" role="status">
            You are viewing a demo. Log in as a Student to save progress, earn coins, and unlock badges.
        </div>
    <?php endif; ?>

    <noscript>
        <div class="alert alert--error" role="alert">This activity requires JavaScript.</div>
    </noscript>

    <div class="activity" id="activity-root" tabindex="0" aria-label="Interactive activity"></div>

    <script type="application/json" id="activity-data"><?= json_encode($contentDecoded, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    <script>window.PAMIKIL = window.PAMIKIL || {}; window.PAMIKIL.activity = { id: <?= (int)$activityId ?>, type: <?= json_encode($type) ?>, isStudent: <?= $isStudent ? 'true' : 'false' ?> };</script>

    <?php if ($jsFile): ?>
        <script defer src="<?= e(url($jsFile)) ?>"></script>
    <?php else: ?>
        <div class="alert alert--error" role="alert">Unknown activity type: <?= e($type) ?></div>
    <?php endif; ?>
</div>
