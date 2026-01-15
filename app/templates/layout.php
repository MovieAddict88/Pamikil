<?php
declare(strict_types=1);

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$user = current_user();
$flashes = consume_flashes();

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <meta name="base-url" content="<?= e(url('/')) ?>">
    <title><?= e(page_title()) ?></title>

    <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
    <script>
        window.PAMIKIL_BASE = <?= json_encode(rtrim((string)config_get('app.base_url', ''), '/')) ?>;
    </script>
    <script defer src="<?= e(url('/assets/js/app.js')) ?>"></script>
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <?php require __DIR__ . '/nav.php'; ?>

    <div class="container">
        <?php if (!empty($flashes)): ?>
            <div class="alerts" role="status" aria-live="polite">
                <?php foreach ($flashes as $f): ?>
                    <div class="alert alert--<?= e($f['type']) ?>">
                        <?= e($f['message']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main id="main" class="main" tabindex="-1">
            <?php require $__pageFile; ?>
        </main>

        <?php require __DIR__ . '/footer.php'; ?>
    </div>
</body>
</html>
