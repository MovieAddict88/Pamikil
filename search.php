<?php
session_start();
define('APP_ACCESS', true);

require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = getDB();

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$movies = [];

if (!empty($query)) {
    $stmt = $db->prepare("
        SELECT * FROM movies 
        WHERE is_published = 1 
        AND (title LIKE ? OR description LIKE ? OR genres LIKE ? OR cast LIKE ? OR director LIKE ?)
        ORDER BY rating DESC, views DESC
        LIMIT 50
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $movies = $stmt->fetchAll();
}

$pageTitle = 'Search';
include 'includes/header.php';
?>

<div class="search-container">
    <h1 style="text-align: center; margin-bottom: 30px;">Search Movies</h1>
    
    <div class="search-box">
        <form method="GET" style="display: flex; width: 100%; gap: 10px;">
            <input type="text" name="q" placeholder="Search for movies, actors, directors..." 
                   value="<?php echo htmlspecialchars($query); ?>" required autofocus>
            <button type="submit">Search</button>
        </form>
    </div>
    
    <?php if (!empty($query)): ?>
        <h2 style="margin-bottom: 30px;">
            <?php if (count($movies) > 0): ?>
                Found <?php echo count($movies); ?> result(s) for "<?php echo htmlspecialchars($query); ?>"
            <?php else: ?>
                No results found for "<?php echo htmlspecialchars($query); ?>"
            <?php endif; ?>
        </h2>
        
        <?php if (!empty($movies)): ?>
            <div class="movies-grid">
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-item">
                        <a href="watch.php?slug=<?php echo $movie['slug']; ?>">
                            <?php if (!empty($movie['poster'])): ?>
                                <img src="<?php echo htmlspecialchars($movie['poster']); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
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
        <?php endif; ?>
    <?php else: ?>
        <div class="no-results">
            <p>Enter a search term to find movies</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
