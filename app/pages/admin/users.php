<?php
declare(strict_types=1);
require_admin();

$admin = current_user();
$__pageTitle = 'Users · Admin · ' . (string)config_get('app.name', 'Pamikil Learning');

if (is_post()) {
    Csrf::requireValidToken();

    $action = (string)($_POST['action'] ?? '');
    $userId = (int)($_POST['user_id'] ?? 0);

    $target = db()->fetch('SELECT id, role, username, email, is_active FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
    if (!$target) {
        flash('error', 'User not found.');
        redirect('/admin/users');
    }

    if ($action === 'toggle_active') {
        $new = ((int)($target['is_active'] ?? 1) === 1) ? 0 : 1;
        db()->execute('UPDATE users SET is_active = :a WHERE id = :id', ['a' => $new, 'id' => $userId]);
        db()->execute(
            'INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
             VALUES (:a, :act, :d, :ip, NOW())',
            [
                'a' => (int)$admin['id'],
                'act' => 'toggle_user_active',
                'd' => sprintf('User %s (%s) active=%d', (string)$target['username'], (string)$target['role'], $new),
                'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]
        );

        flash('success', $new ? 'User activated.' : 'User deactivated.');
        redirect('/admin/users');
    }

    if ($action === 'reset_password') {
        $newPass = substr(Security::randomString(16), 0, 12);
        $cost = (int)config_get('security.bcrypt_cost', 12);
        $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => $cost]);
        db()->execute('UPDATE users SET password_hash = :h WHERE id = :id', ['h' => $hash, 'id' => $userId]);

        db()->execute(
            'INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
             VALUES (:a, :act, :d, :ip, NOW())',
            [
                'a' => (int)$admin['id'],
                'act' => 'reset_password',
                'd' => sprintf('Password reset for user %s (%s)', (string)$target['username'], (string)$target['role']),
                'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]
        );

        flash('success', 'Password reset. New temporary password: ' . $newPass);
        redirect('/admin/users');
    }

    flash('error', 'Unknown action.');
    redirect('/admin/users');
}

$roleFilter = (string)($_GET['role'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];

if ($roleFilter !== '' && in_array($roleFilter, ['student', 'parent', 'admin'], true)) {
    $where[] = 'role = :role';
    $params['role'] = $roleFilter;
}

if ($q !== '') {
    $where[] = '(username LIKE :q OR email LIKE :q OR display_name LIKE :q)';
    $params['q'] = '%' . $q . '%';
}

$whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

$users = db()->fetchAll(
    "SELECT id, role, username, email, display_name, age_group, coins, is_active, created_at, last_login_at
     FROM users
     $whereSql
     ORDER BY created_at DESC
     LIMIT 200",
    $params
);

?>
<section class="page-header">
    <h1>User management</h1>
</section>

<form class="filters" method="get">
    <label class="field">
        <span class="field__label">Search</span>
        <input class="field__input" name="q" value="<?= e($q) ?>" placeholder="username, email">
    </label>

    <label class="field">
        <span class="field__label">Role</span>
        <select class="field__input" name="role">
            <option value="">All</option>
            <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Student</option>
            <option value="parent" <?= $roleFilter === 'parent' ? 'selected' : '' ?>>Parent</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </label>

    <button class="btn" type="submit">Apply</button>
</form>

<div class="card">
    <h2 class="card__title">Users</h2>

    <div class="table-wrap" role="region" aria-label="Users table" tabindex="0">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Role</th>
                    <th scope="col">Username</th>
                    <th scope="col">Email</th>
                    <th scope="col">Active</th>
                    <th scope="col">Coins</th>
                    <th scope="col">Created</th>
                    <th scope="col">Last login</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= e((string)$u['role']) ?></td>
                        <td><?= e((string)$u['username']) ?></td>
                        <td><?= e((string)$u['email']) ?></td>
                        <td><?= ((int)($u['is_active'] ?? 1) === 1) ? 'Yes' : 'No' ?></td>
                        <td><?= (int)($u['coins'] ?? 0) ?></td>
                        <td><?= e((string)$u['created_at']) ?></td>
                        <td><?= e((string)($u['last_login_at'] ?? '—')) ?></td>
                        <td>
                            <form method="post" class="inline-actions">
                                <?= Csrf::inputField() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button class="btn btn--small" name="action" value="toggle_active" type="submit"><?= ((int)($u['is_active'] ?? 1) === 1) ? 'Deactivate' : 'Activate' ?></button>
                                <button class="btn btn--small" name="action" value="reset_password" type="submit">Reset password</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
