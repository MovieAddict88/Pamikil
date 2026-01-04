<?php
/**
 * Users Index
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireRole('admin');

$pageTitle = 'Users Management';
$db = getDb();

$role = sanitize($_GET['role'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));

$where = [];
$params = [];

if (!empty($role)) {
    $where[] = "role = ?";
    $params[] = $role;
}

$whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';

$countSql = "SELECT COUNT(*) as total FROM users WHERE $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalUsers = $stmt->fetch()['total'];

$pagination = getPagination($totalUsers, $page, ADMIN_ITEMS_PER_PAGE);

$sql = "
    SELECT u.*, l.name as location_name
    FROM users u
    LEFT JOIN locations l ON u.location_id = l.id
    WHERE $whereClause
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$params[] = $pagination['items_per_page'];
$params[] = $pagination['offset'];
$stmt->execute($params);
$users = $stmt->fetchAll();

$roles = ['admin', 'manager', 'staff'];

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo SITE_URL; ?>/users/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add User
        </a>
    </div>
    
    <form method="GET" action="" class="d-flex gap-2">
        <select name="role" class="form-select" style="width: auto;">
            <option value="">All Roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo $role === $r ? 'selected' : ''; ?>>
                    <?php echo ucfirst($r); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="<?php echo SITE_URL; ?>/users/index.php" class="btn btn-outline-secondary">Clear</a>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="users-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="9" class="text-center text-muted">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars(formatPHMobileNumber($user['phone'])); ?></td>
                                <td><?php echo getStatusBadge($user['role'], 'default'); ?></td>
                                <td><?php echo htmlspecialchars($user['location_name'] ?? 'N/A'); ?></td>
                                <td><?php echo getStatusBadge($user['status']); ?></td>
                                <td><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></td>
                                <td class="no-export">
                                    <div class="action-buttons">
                                        <?php if ($user['id'] !== getCurrentUserId()): ?>
                                            <a href="<?php echo SITE_URL; ?>/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/users/reset-password.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Reset Password">
                                                <i class="bi bi-key"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/users/delete.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger btn-delete" data-confirm="Delete this user?">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Current user</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php echo renderPagination($pagination, SITE_URL . '/users/index.php' . (!empty($role) ? '?role=' . $role . '&' : '?')); ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
