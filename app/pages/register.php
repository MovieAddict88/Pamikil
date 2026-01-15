<?php
declare(strict_types=1);
$__pageTitle = 'Sign up · ' . (string)config_get('app.name', 'Pamikil Learning');

if (auth()->check()) {
    redirect('/');
}

$errors = [];

if (is_post()) {
    Csrf::requireValidToken();

    $errors = Validator::validateRegistration($_POST);

    if (empty($errors)) {
        $role = (string)$_POST['role'];
        $username = trim((string)$_POST['username']);
        $email = trim((string)$_POST['email']);
        $displayName = trim((string)($_POST['display_name'] ?? $username));
        $ageGroup = $role === 'student' ? (string)($_POST['age_group'] ?? null) : null;

        $existing = db()->fetch('SELECT 1 FROM users WHERE username = :u OR email = :e LIMIT 1', ['u' => $username, 'e' => $email]);
        if ($existing) {
            $errors['general'] = 'That username or email is already in use.';
        } else {
            $cost = (int)config_get('security.bcrypt_cost', 12);
            $hash = password_hash((string)$_POST['password'], PASSWORD_BCRYPT, ['cost' => $cost]);

            db()->beginTransaction();
            try {
                db()->execute(
                    'INSERT INTO users (role, username, email, password_hash, display_name, age_group, coins, created_at)
                     VALUES (:role, :username, :email, :hash, :display_name, :age_group, 0, NOW())',
                    [
                        'role' => $role,
                        'username' => $username,
                        'email' => $email,
                        'hash' => $hash,
                        'display_name' => $displayName,
                        'age_group' => $ageGroup,
                    ]
                );
                $userId = (int)db()->lastInsertId();

                db()->execute(
                    'INSERT INTO user_avatar (user_id, base_item_id, hat_item_id, shirt_item_id, pet_item_id, bg_item_id)
                     VALUES (:u,
                        (SELECT id FROM avatar_items WHERE slot = "base" AND is_default = 1 LIMIT 1),
                        NULL,
                        NULL,
                        NULL,
                        (SELECT id FROM avatar_items WHERE slot = "bg" AND is_default = 1 LIMIT 1)
                     )',
                    ['u' => $userId]
                );

                // Grant all default avatar items to the new user
                db()->execute(
                    'INSERT INTO user_avatar_items (user_id, item_id, owned_at)
                     SELECT :u, id, NOW() FROM avatar_items WHERE is_default = 1',
                    ['u' => $userId]
                );

                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

            auth()->login($username, (string)$_POST['password']);
            flash('success', 'Account created!');

            if ($role === 'student') {
                redirect('/student');
            }
            redirect('/parent');
        }
    }
}

$roleValue = (string)($_POST['role'] ?? 'student');
?>
<div class="panel">
    <h1>Create an account</h1>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert--error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="form" autocomplete="on">
        <?= Csrf::inputField() ?>

        <fieldset class="fieldset">
            <legend>Who is this account for?</legend>
            <label class="radio">
                <input type="radio" name="role" value="student" <?= $roleValue === 'student' ? 'checked' : '' ?>>
                <span>Student</span>
            </label>
            <label class="radio">
                <input type="radio" name="role" value="parent" <?= $roleValue === 'parent' ? 'checked' : '' ?>>
                <span>Parent</span>
            </label>
            <?php if (isset($errors['role'])): ?>
                <div class="field__error"><?= e($errors['role']) ?></div>
            <?php endif; ?>
        </fieldset>

        <label class="field">
            <span class="field__label">Display name</span>
            <input class="field__input" name="display_name" value="<?= e((string)($_POST['display_name'] ?? '')) ?>" placeholder="e.g., Sam">
        </label>

        <label class="field">
            <span class="field__label">Username</span>
            <input class="field__input" name="username" value="<?= e((string)($_POST['username'] ?? '')) ?>" required>
            <?php if (isset($errors['username'])): ?>
                <span class="field__error"><?= e($errors['username']) ?></span>
            <?php endif; ?>
        </label>

        <label class="field">
            <span class="field__label">Email</span>
            <input class="field__input" type="email" name="email" value="<?= e((string)($_POST['email'] ?? '')) ?>" required>
            <?php if (isset($errors['email'])): ?>
                <span class="field__error"><?= e($errors['email']) ?></span>
            <?php endif; ?>
        </label>

        <label class="field" data-role-dependent="student">
            <span class="field__label">Student age group</span>
            <select class="field__input" name="age_group">
                <option value="">Choose…</option>
                <option value="3-5" <?= (($_POST['age_group'] ?? '') === '3-5') ? 'selected' : '' ?>>3–5 years</option>
                <option value="6-8" <?= (($_POST['age_group'] ?? '') === '6-8') ? 'selected' : '' ?>>6–8 years</option>
                <option value="9-12" <?= (($_POST['age_group'] ?? '') === '9-12') ? 'selected' : '' ?>>9–12 years</option>
            </select>
            <?php if (isset($errors['age_group'])): ?>
                <span class="field__error"><?= e($errors['age_group']) ?></span>
            <?php endif; ?>
        </label>

        <label class="field">
            <span class="field__label">Password</span>
            <input class="field__input" type="password" name="password" required>
            <?php if (isset($errors['password'])): ?>
                <span class="field__error"><?= e($errors['password']) ?></span>
            <?php endif; ?>
        </label>

        <button class="btn btn--primary" type="submit">Create account</button>

        <p class="muted">
            Already have an account? <a href="<?= e(url('/login')) ?>">Log in</a>.
        </p>
    </form>
</div>
