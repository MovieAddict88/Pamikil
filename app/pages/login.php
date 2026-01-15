<?php
declare(strict_types=1);
$__pageTitle = 'Log in · ' . (string)config_get('app.name', 'Pamikil Learning');

if (auth()->check()) {
    $user = current_user();
    if (($user['role'] ?? '') === 'student') {
        redirect('/student');
    }
    if (($user['role'] ?? '') === 'parent') {
        redirect('/parent');
    }
    redirect('/admin');
}

$errors = [];

if (is_post()) {
    Csrf::requireValidToken();

    $errors = Validator::validateLogin($_POST);

    if (empty($errors)) {
        $ok = auth()->login((string)$_POST['username_or_email'], (string)$_POST['password']);
        if ($ok) {
            $user = current_user();
            flash('success', 'Welcome back!');

            if (($user['role'] ?? '') === 'student') {
                redirect('/student');
            }
            if (($user['role'] ?? '') === 'parent') {
                redirect('/parent');
            }
            redirect('/admin');
        }

        $errors['general'] = 'Incorrect login details.';
    }
}

?>
<div class="panel">
    <h1>Log in</h1>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert--error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="form" autocomplete="on">
        <?= Csrf::inputField() ?>

        <label class="field">
            <span class="field__label">Username or email</span>
            <input class="field__input" name="username_or_email" value="<?= e((string)($_POST['username_or_email'] ?? '')) ?>" required>
            <?php if (isset($errors['username_or_email'])): ?>
                <span class="field__error"><?= e($errors['username_or_email']) ?></span>
            <?php endif; ?>
        </label>

        <label class="field">
            <span class="field__label">Password</span>
            <input class="field__input" name="password" type="password" required>
            <?php if (isset($errors['password'])): ?>
                <span class="field__error"><?= e($errors['password']) ?></span>
            <?php endif; ?>
        </label>

        <button class="btn btn--primary" type="submit">Log in</button>

        <p class="muted">
            No account? <a href="<?= e(url('/register')) ?>">Create one</a>.
        </p>
    </form>
</div>
