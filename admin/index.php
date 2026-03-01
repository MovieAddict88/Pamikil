<?php
session_start();
define('APP_ACCESS', true);

if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
    require_once '../includes/db.php';
    require_once '../includes/functions.php';
} else {
    header('Location: ../install.php');
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = getDB();

$statsQuery = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM movies) as total_movies,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM servers) as total_servers,
        (SELECT SUM(views) FROM movies) as total_views
");
$stats = $statsQuery->fetch();

$recentMovies = $db->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 10")->fetchAll();

$topMovies = $db->query("SELECT * FROM movies ORDER BY views DESC LIMIT 10")->fetchAll();

$recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();

include 'header.php';
?>

<div class="dashboard">
    <h1>Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎬</div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_movies']); ?></h3>
                <p>Total Movies</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_users']); ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🖥️</div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_servers']); ?></h3>
                <p>Servers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_views']); ?></h3>
                <p>Total Views</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-section">
            <h2>Recent Movies</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Year</th>
                            <th>Rating</th>
                            <th>Views</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentMovies)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No movies added yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentMovies as $movie): ?>
                                <tr>
                                    <td>
                                        <a href="edit-movie.php?id=<?php echo $movie['id']; ?>">
                                            <?php echo htmlspecialchars($movie['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $movie['year']; ?></td>
                                    <td><?php echo $movie['rating']; ?></td>
                                    <td><?php echo number_format($movie['views']); ?></td>
                                    <td><?php echo timeAgo($movie['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>Top Movies by Views</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Views</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topMovies)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">No movies added yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topMovies as $movie): ?>
                                <tr>
                                    <td>
                                        <a href="edit-movie.php?id=<?php echo $movie['id']; ?>">
                                            <?php echo htmlspecialchars($movie['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($movie['views']); ?></td>
                                    <td><?php echo $movie['rating']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Recent Users</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentUsers)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No users registered yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo timeAgo($user['created_at']); ?></td>
                                <td><?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Never'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
