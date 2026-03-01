<?php
session_start();
define('APP_ACCESS', true);

if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
} else {
    header('Location: install.php');
    exit;
}

$db = getDB();

$perPage = (int)getSetting('items_per_page', 20);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$countStmt = $db->query("SELECT COUNT(*) FROM movies WHERE is_published = 1");
$totalMovies = $countStmt->fetchColumn();

$stmt = $db->prepare("SELECT * FROM movies WHERE is_published = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$movies = $stmt->fetchAll();

$featuredStmt = $db->query("SELECT * FROM movies WHERE is_featured = 1 AND is_published = 1 ORDER BY RAND() LIMIT 1");
$featuredMovie = $featuredStmt->fetch();

$pageTitle = 'Home';
include 'includes/header.php';
?>

<?php if ($featuredMovie): ?>
    <section class="hero" style="background-image: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('<?php echo htmlspecialchars($featuredMovie['backdrop'] ?: $featuredMovie['poster']); ?>');">
        <div class="hero-content">
            <h1 class="hero-title"><?php echo htmlspecialchars($featuredMovie['title']); ?></h1>
            <div class="hero-meta">
                <span><?php echo $featuredMovie['year']; ?></span>
                <span>⭐ <?php echo $featuredMovie['rating']; ?></span>
                <?php if ($featuredMovie['runtime']): ?>
                    <span><?php echo formatRuntime($featuredMovie['runtime']); ?></span>
                <?php endif; ?>
            </div>
            <p class="hero-description"><?php echo htmlspecialchars(substr($featuredMovie['description'], 0, 200)); ?>...</p>
            <div class="hero-buttons">
                <a href="watch.php?slug=<?php echo $featuredMovie['slug']; ?>" class="btn btn-primary">
                    ▶ Watch Now
                </a>
                <a href="watch.php?slug=<?php echo $featuredMovie['slug']; ?>" class="btn btn-secondary">
                    ℹ More Info
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="container">
    <div class="section-header">
        <h2>Latest Movies</h2>
    </div>
    
    <?php if (empty($movies)): ?>
        <div class="no-results">
            <p>No movies available yet. Check back soon!</p>
        </div>
    <?php else: ?>
        <div class="movies-grid">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-item">
                    <a href="watch.php?slug=<?php echo $movie['slug']; ?>">
                        <?php if (!empty($movie['poster'])): ?>
                            <img src="<?php echo htmlspecialchars($movie['poster']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                 loading="lazy"
                                 onerror="this.src='https://via.placeholder.com/300x450?text=No+Poster'">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/300x450?text=No+Poster" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <?php endif; ?>
                        <div class="movie-overlay">
                            <div class="movie-info">
                                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                                <div class="movie-meta">
                                    <span><?php echo $movie['year']; ?></span>
                                    <span>⭐ <?php echo $movie['rating']; ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php echo pagination($totalMovies, $perPage, $page, 'index.php'); ?>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
