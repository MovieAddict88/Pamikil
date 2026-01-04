<?php
declare(strict_types=1);

$user = current_user();
$role = $user['role'] ?? null;

?>
<header class="topbar" role="banner">
    <div class="topbar__inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="Pamikil Learning Home">
            <span class="brand__logo" aria-hidden="true">📚</span>
            <span class="brand__name"><?= e((string)config_get('app.name', 'Pamikil Learning')) ?></span>
        </a>

        <nav class="nav" aria-label="Primary">
            <a class="nav__link" href="<?= e(url('/activities')) ?>">Activities</a>

            <?php if (!$user): ?>
                <a class="nav__link" href="<?= e(url('/login')) ?>">Log in</a>
                <a class="nav__link nav__link--cta" href="<?= e(url('/register')) ?>">Sign up</a>
            <?php else: ?>
                <?php if ($role === 'student'): ?>
                    <a class="nav__link" href="<?= e(url('/student')) ?>">My Dashboard</a>
                    <a class="nav__link" href="<?= e(url('/student/avatar')) ?>">Avatar</a>
                    <a class="nav__link" href="<?= e(url('/student/certificates')) ?>">Certificates</a>
                <?php elseif ($role === 'parent'): ?>
                    <a class="nav__link" href="<?= e(url('/parent')) ?>">Parent Dashboard</a>
                    <a class="nav__link" href="<?= e(url('/parent/settings')) ?>">Controls</a>
                <?php elseif ($role === 'admin'): ?>
                    <a class="nav__link" href="<?= e(url('/admin')) ?>">Admin</a>
                    <a class="nav__link" href="<?= e(url('/admin/content')) ?>">Content</a>
                    <a class="nav__link" href="<?= e(url('/admin/users')) ?>">Users</a>
                    <a class="nav__link" href="<?= e(url('/admin/reports')) ?>">Reports</a>
                <?php endif; ?>

                <a class="nav__link" href="<?= e(url('/logout')) ?>">Log out</a>
            <?php endif; ?>
        </nav>

        <div class="prefs" aria-label="Display preferences">
            <button class="prefs__btn" type="button" data-action="toggle-contrast" aria-pressed="false">High contrast</button>
            <button class="prefs__btn" type="button" data-action="font-minus" aria-label="Decrease font size">A-</button>
            <button class="prefs__btn" type="button" data-action="font-plus" aria-label="Increase font size">A+</button>
        </div>
    </div>
</header>
