<?php
session_start();
define('APP_ACCESS', true);

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = getDB();
$perPage = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE username LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM users $whereClause");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();

$stmt = $db->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$deleteId]);
    redirect('users.php?deleted=1');
}

$pageTitle = 'Users';
include 'header.php';
?>

<div class="dashboard">
    <h1>Users (<?php echo number_format($totalUsers); ?>)</h1>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">User deleted successfully!</div>
    <?php endif; ?>
    
    <div class="search-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search users by username or email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if (!empty($search)): ?>
                <a href="users.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="dashboard-section">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Never'; ?></td>
                                <td>
                                    <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                       class="btn btn-danger" 
                                       style="padding: 5px 10px; font-size: 13px;"
                                       onclick="return confirmDelete('Are you sure you want to delete this user?')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php
        $baseUrl = 'users.php?';
        if (!empty($search)) {
            $baseUrl .= 'search=' . urlencode($search) . '&';
        }
        echo pagination($totalUsers, $perPage, $page, rtrim($baseUrl, '&'));
        ?>
    </div>
</div>

<?php include 'footer.php'; ?>
