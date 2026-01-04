<?php
declare(strict_types=1);
require_student();

$user = current_user();
$__pageTitle = 'Certificates · ' . (string)config_get('app.name', 'Pamikil Learning');

if (is_post()) {
    Csrf::requireValidToken();

    $code = Security::randomString(12);
    db()->execute(
        'INSERT INTO certificates (user_id, title, certificate_code, issued_at)
         VALUES (:u, :t, :c, NOW())',
        ['u' => (int)$user['id'], 't' => 'Learning Achievement Certificate', 'c' => $code]
    );

    flash('success', 'Certificate created! You can print it now.');
    redirect('/student/certificates');
}

$printId = isset($_GET['print']) ? (int)$_GET['print'] : 0;

if ($printId > 0) {
    $__pageTitle = 'Print certificate · ' . (string)config_get('app.name', 'Pamikil Learning');

    $cert = db()->fetch('SELECT * FROM certificates WHERE id = :id AND user_id = :u', ['id' => $printId, 'u' => (int)$user['id']]);
    if (!$cert) {
        flash('error', 'Certificate not found.');
        redirect('/student/certificates');
    }

    $completedRow = db()->fetch(
        'SELECT COUNT(*) AS c FROM activity_progress WHERE user_id = :u AND status = "completed"',
        ['u' => (int)$user['id']]
    );
    $completed = (int)($completedRow['c'] ?? 0);

    ?>
    <div class="certificate" aria-label="Printable certificate">
        <div class="certificate__inner">
            <div class="certificate__top">
                <div class="certificate__brand">📚 <?= e((string)config_get('app.name', 'Pamikil Learning')) ?></div>
                <div class="certificate__code">Code: <?= e((string)$cert['certificate_code']) ?></div>
            </div>

            <h1 class="certificate__title"><?= e((string)$cert['title']) ?></h1>
            <p class="certificate__text">This certificate is proudly presented to</p>
            <p class="certificate__name"><?= e($user['display_name'] ?: $user['username']) ?></p>
            <p class="certificate__text">for completing <strong><?= (int)$completed ?></strong> learning activities.</p>

            <div class="certificate__footer">
                <div>
                    <div class="certificate__label">Issued</div>
                    <div><?= e((string)$cert['issued_at']) ?></div>
                </div>
                <div>
                    <div class="certificate__label">Signature</div>
                    <div class="certificate__signature">____________________</div>
                </div>
            </div>

            <div class="certificate__actions no-print">
                <button class="btn btn--primary" type="button" onclick="window.print()">Print</button>
                <a class="btn" href="<?= e(url('/student/certificates')) ?>">Back</a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$certificates = db()->fetchAll('SELECT * FROM certificates WHERE user_id = :u ORDER BY issued_at DESC', ['u' => (int)$user['id']]);

?>
<section class="page-header">
    <h1>Certificates</h1>
    <p class="muted">Create and print certificates to celebrate progress.</p>
</section>

<div class="grid">
    <section class="card">
        <h2 class="card__title">Create a new certificate</h2>
        <form method="post">
            <?= Csrf::inputField() ?>
            <button class="btn btn--primary" type="submit">Create certificate</button>
        </form>
    </section>

    <section class="card">
        <h2 class="card__title">My certificates</h2>
        <?php if (empty($certificates)): ?>
            <p class="muted">No certificates yet.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($certificates as $c): ?>
                    <li class="list__item">
                        <strong><?= e((string)$c['title']) ?></strong>
                        <span class="muted"> · <?= e((string)$c['issued_at']) ?></span>
                        <a class="btn btn--small" href="<?= e(url('/student/certificates?print=' . (int)$c['id'])) ?>">Print</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
