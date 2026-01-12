<?php
/**
 * Admin Users Management
 * Create, edit, delete, and manage users
 */

require_once APP_ROOT . '/templates/header.php';

requireAdmin();

$admin = new Admin();
$auth = new Auth();
$db = Database::getInstance();

// Handle user actions
$editUser = null;
$userId = get('id');

if ($userId && is_numeric($userId)) {
    $editUser = $admin->getUser($userId);
}

// Get users list
$filters = [
    'role' => get('role'),
    'is_active' => get('status') !== null ? (get('status') == '1' ? 1 : 0) : null,
    'search' => get('search')
];

$users = $admin->getUsers($filters, 20, get('offset', 0) * 20);

// Count total for pagination
$userCount = $db->queryOne("SELECT COUNT(*) as count FROM users")['count'];
?>

<div class="admin-header">
    <div class="container">
        <div class="admin-nav">
            <h1>User Management</h1>
            <div class="admin-menu">
                <a href="<?= SITE_URL ?>/admin" class=""><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                <a href="<?= SITE_URL ?>/admin/users" class="active"><i class="fa fa-users"></i> Users</a>
                <a href="<?= SITE_URL ?>/admin/activities"><i class="fa fa-tasks"></i> Activities</a>
                <a href="<?= SITE_URL ?>/admin/reports"><i class="fa fa-chart-bar"></i> Reports</a>
                <a href="<?= SITE_URL ?>/admin/settings"><i class="fa fa-cog"></i> Settings</a>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/" class="btn btn-outline"><i class="fa fa-arrow-left"></i> Back to Site</a>
    </div>
</div>

<!-- Filters -->
<section class="admin-filters">
    <div class="container">
        <form method="GET" class="filters-form">
            <input type="hidden" name="page" value="users">
            <div class="filter-group">
                <input type="text" name="search" value="<?= htmlspecialchars(get('search')) ?>" 
                       placeholder="Search users..." class="form-control">
            </div>
            <div class="filter-group">
                <select name="role" class="form-control">
                    <option value="">All Roles</option>
                    <option value="student" <?= get('role') === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="parent" <?= get('role') === 'parent' ? 'selected' : '' ?>>Parent</option>
                    <option value="admin" <?= get('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="filter-group">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="1" <?= get('status') === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= get('status') === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= SITE_URL ?>/admin/users" class="btn btn-outline">Clear</a>
                <button type="button" class="btn btn-success" id="addUserBtn">
                    <i class="fa fa-plus"></i> Add User
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Users Table -->
<section class="admin-content">
    <div class="container">
        <div class="data-table">
            <div class="table-header">
                <div class="table-cell">User</div>
                <div class="table-cell">Role</div>
                <div class="table-cell">Level</div>
                <div class="table-cell">Activities</div>
                <div class="table-cell">Status</div>
                <div class="table-cell">Joined</div>
                <div class="table-cell">Actions</div>
            </div>
            
            <?php if (empty($users)): ?>
                <div class="table-empty">
                    <p>No users found</p>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="table-row <?= $user['id'] == $auth->getCurrentUser()['id'] ? 'current-user' : '' ?>">
                        <div class="table-cell">
                            <div class="user-cell">
                                <?php if ($user['avatar_image']): ?>
                                    <img src="<?= htmlspecialchars($user['avatar_image']) ?>" 
                                         alt="" class="user-avatar-mini">
                                <?php else: ?>
                                    <div class="avatar-placeholder-mini">
                                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="user-cell-info">
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    <span class="user-email"><?= htmlspecialchars($user['email']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="table-cell">
                            <span class="badge badge-<?= $user['role'] ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </div>
                        <div class="table-cell">
                            <i class="fa fa-level-up-alt"></i> <?= $user['current_level'] ?>
                        </div>
                        <div class="table-cell"><?= $user['activities_completed'] ?></div>
                        <div class="table-cell">
                            <span class="status-badge status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div class="table-cell"><?= formatDate($user['created_at']) ?></div>
                        <div class="table-cell actions">
                            <a href="<?= SITE_URL ?>/admin/users?id=<?= $user['id'] ?>" 
                               class="btn btn-sm btn-outline" title="Edit">
                                <i class="fa fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $auth->getCurrentUser()['id']): ?>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete('user', <?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')"
                                        title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($userCount > 20): ?>
            <div class="pagination">
                <?php 
                $totalPages = ceil($userCount / 20);
                $currentPage = (int)get('offset', 0) / 20;
                
                for ($i = 0; $i < $totalPages; $i++): ?>
                    <a href="<?= SITE_URL ?>/admin/users?offset=<?= $i * 20 ?>" 
                       class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                        <?= $i + 1 ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Add/Edit User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="userModalTitle">Add User</h2>
            <button class="modal-close" id="closeUserModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm" method="POST" action="/api/admin/save-user.php">
                <?= Security::getCSRFTokenField() ?>
                <input type="hidden" name="id" id="userId" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required class="form-control"
                               pattern="[a-zA-Z0-9_]{3,}"
                               title="Minimum 3 characters (letters, numbers, underscore)">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Password <?= $editUser ? '(leave blank to keep current)' : '*'; ?></label>
                        <input type="password" name="password" 
                               <?= $editUser ? '' : 'required' ?> 
                               class="form-control"
                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required class="form-control">
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date of Birth (for students)</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Link to Parent (for students)</label>
                    <select name="parent_id" class="form-control">
                        <option value="">No parent</option>
                        <?php 
                        $parents = $db->query("SELECT id, username, first_name, last_name FROM users WHERE role = 'parent'");
                        foreach ($parents as $parent): ?>
                            <option value="<?= $parent['id'] ?>">
                                <?= htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']) ?> 
                                (<?= htmlspecialchars($parent['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" checked> 
                        Active Account
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancelUser">Cancel</button>
            <button class="btn btn-primary" id="saveUser">Save User</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    
    // Add user button
    document.getElementById('addUserBtn').addEventListener('click', () => {
        openModal('Add User', form, null);
    });
    
    // Edit existing user
    <?php if ($editUser): ?>
        openModal('Edit User', form, {
            id: <?= $editUser['id'] ?>,
            first_name: '<?= htmlspecialchars($editUser['first_name']) ?>',
            last_name: '<?= htmlspecialchars($editUser['last_name']) ?>',
            username: '<?= htmlspecialchars($editUser['username']) ?>',
            email: '<?= htmlspecialchars($editUser['email']) ?>',
            role: '<?= $editUser['role'] ?>',
            date_of_birth: '<?= $editUser['date_of_birth'] ?>',
            parent_id: '<?= $editUser['parent_id'] ?>',
            is_active: <?= $editUser['is_active'] ? 'true' : 'false' ?>
        });
    <?php endif; ?>
    
    // Close modal
    document.getElementById('closeUserModal').addEventListener('click', () => {
        modal.style.display = 'none';
    });
    
    document.getElementById('cancelUser').addEventListener('click', () => {
        modal.style.display = 'none';
    });
    
    // Save user
    document.getElementById('saveUser').addEventListener('click', () => {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        fetch('/api/admin/save-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                App.showToast('User saved successfully!', 'success');
                location.reload();
            } else {
                App.showToast(result.error || 'Failed to save user', 'error');
            }
        });
    });
    
    function openModal(title, form, data) {
        document.getElementById('userModalTitle').textContent = title;
        
        if (data) {
            form.querySelector('[name="id"]').value = data.id || '';
            form.querySelector('[name="first_name"]').value = data.first_name || '';
            form.querySelector('[name="last_name"]').value = data.last_name || '';
            form.querySelector('[name="username"]').value = data.username || '';
            form.querySelector('[name="email"]').value = data.email || '';
            form.querySelector('[name="role"]').value = data.role || 'student';
            form.querySelector('[name="date_of_birth"]').value = data.date_of_birth || '';
            form.querySelector('[name="parent_id"]').value = data.parent_id || '';
            form.querySelector('[name="is_active"]').checked = data.is_active;
            form.querySelector('[name="password"]').required = false;
        } else {
            form.reset();
            form.querySelector('[name="id"]').value = '';
            form.querySelector('[name="is_active"]').checked = true;
            form.querySelector('[name="password"]').required = true;
        }
        
        modal.style.display = 'block';
    }
    
    window.confirmDelete = function(type, id, name) {
        if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
            fetch(`/api/admin/delete-${type}.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    App.showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully!`, 'success');
                    location.reload();
                } else {
                    App.showToast(result.error || 'Failed to delete', 'error');
                }
            });
        }
    };
});
</script>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: var(--radius-lg);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-lg);
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-body {
    padding: var(--spacing-xl);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    border-top: 1px solid #e0e0e0;
}

.data-table {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 150px;
    background: var(--bg-dark);
    color: white;
    font-weight: 600;
    padding: var(--spacing-md);
}

.table-cell {
    padding: var(--spacing-md);
    display: flex;
    align-items: center;
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 150px;
    padding: var(--spacing-md);
    border-bottom: 1px solid #f0f0f0;
    transition: background var(--transition-fast);
}

.table-row:hover {
    background: #f8f9fa;
}

.table-row.current-user {
    background: #fff3cd;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.user-cell-info {
    display: flex;
    flex-direction: column;
}

.user-email {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
}

.user-avatar-mini {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.avatar-placeholder-mini {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.badge {
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 600;
}

.badge-student { background: var(--success); color: white; }
.badge-parent { background: var(--info); color: white; }
.badge-admin { background: var(--danger); color: white; }

.status-badge {
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 600;
}

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }

.table-empty {
    padding: var(--spacing-xxl);
    text-align: center;
    color: var(--text-secondary);
}

.actions {
    display: flex;
    gap: var(--spacing-sm);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-xl);
    padding: var(--spacing-lg);
}

.page-link {
    padding: var(--spacing-sm) var(--spacing-md);
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.page-link:hover,
.page-link.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

@media (max-width: 768px) {
    .table-header,
    .table-row {
        grid-template-columns: 1fr;
        gap: var(--spacing-sm);
    }
    
    .table-header {
        display: none;
    }
    
    .table-row {
        padding: var(--spacing-md);
        display: block;
    }
}
</style>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
