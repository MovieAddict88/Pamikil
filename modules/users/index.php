<?php
define('BASE_URL', '../..');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_role(['Admin', 'Manager']);

$users = $conn->query("SELECT u.*, l.name as location_name FROM users u LEFT JOIN locations l ON u.location_id = l.id ORDER BY u.created_at DESC");

$page_title = 'User Management';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-shield"></i> User Management</h2>
        <?php if (has_role('Admin')): ?>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add User
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($users->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo escape_output($user['username']); ?></strong></td>
                        <td><?php echo escape_output($user['full_name']); ?></td>
                        <td><?php echo escape_output($user['email']); ?></td>
                        <td>
                            <span class="badge <?php 
                                echo $user['role'] == 'Admin' ? 'badge-error' : 
                                     ($user['role'] == 'Manager' ? 'badge-warning' : 'badge-info'); 
                            ?>">
                                <?php echo escape_output($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo escape_output($user['location_name']) ?: 'N/A'; ?></td>
                        <td><?php echo $user['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-error">Inactive</span>'; ?></td>
                        <td><?php echo $user['last_login'] ? format_datetime($user['last_login']) : 'Never'; ?></td>
                        <td class="table-actions no-export">
                            <?php if (has_role('Admin')): ?>
                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="toggle_status.php?id=<?php echo $user['id']; ?>" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-center text-muted">No users found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
