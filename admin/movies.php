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
$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE title LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM movies $whereClause");
$countStmt->execute($params);
$totalMovies = $countStmt->fetchColumn();

$stmt = $db->prepare("SELECT * FROM movies $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$movies = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    $stmt = $db->prepare("SELECT poster, backdrop FROM movies WHERE id = ?");
    $stmt->execute([$deleteId]);
    $movie = $stmt->fetch();
    
    if ($movie) {
        if (!empty($movie['poster']) && file_exists($movie['poster'])) {
            unlink($movie['poster']);
        }
        if (!empty($movie['backdrop']) && file_exists($movie['backdrop'])) {
            unlink($movie['backdrop']);
        }
        
        $stmt = $db->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$deleteId]);
        
        redirect('movies.php?deleted=1');
    }
}

$pageTitle = 'Movies';
include 'header.php';
?>

<div class="dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Movies (<?php echo number_format($totalMovies); ?>)</h1>
        <div class="btn-group">
            <a href="add-movie.php" class="btn btn-primary">Add Movie</a>
            <a href="bulk-add.php" class="btn btn-success">Bulk Add</a>
        </div>
    </div>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Movie deleted successfully!</div>
    <?php endif; ?>
    
    <div class="search-bar">
        <form method="GET">
            <input type="text" name="search" placeholder="Search movies by title or description..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if (!empty($search)): ?>
                <a href="movies.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if (empty($movies)): ?>
        <div class="dashboard-section">
            <p style="text-align: center; padding: 40px; color: #7f8c8d;">
                No movies found. <a href="add-movie.php">Add your first movie</a>
            </p>
        </div>
    <?php else: ?>
        <div class="movie-grid">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card">
                    <?php if (!empty($movie['poster'])): ?>
                        <img src="../<?php echo htmlspecialchars($movie['poster']); ?>" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                             onerror="this.src='https://via.placeholder.com/200x300?text=No+Poster'">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/200x300?text=No+Poster" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <?php endif; ?>
                    
                    <div class="movie-card-body">
                        <div class="movie-card-title" title="<?php echo htmlspecialchars($movie['title']); ?>">
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </div>
                        <div class="movie-card-info">
                            <span><?php echo $movie['year']; ?></span>
                            <span>⭐ <?php echo $movie['rating']; ?></span>
                            <span>👁️ <?php echo number_format($movie['views']); ?></span>
                        </div>
                        <div class="movie-card-actions">
                            <a href="edit-movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="movies.php?delete=<?php echo $movie['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirmDelete('Are you sure you want to delete this movie?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php
        $baseUrl = 'movies.php?';
        if (!empty($search)) {
            $baseUrl .= 'search=' . urlencode($search) . '&';
        }
        echo pagination($totalMovies, $perPage, $page, rtrim($baseUrl, '&'));
        ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
