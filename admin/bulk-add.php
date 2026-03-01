<?php
session_start();
define('APP_ACCESS', true);

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/tmdb.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = getDB();
$tmdb = new TMDB();
$error = '';
$success = '';
$addedCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_add'])) {
    $tmdbIds = sanitize($_POST['tmdb_ids']);
    $ids = array_filter(array_map('trim', explode("\n", $tmdbIds)));
    
    foreach ($ids as $tmdbId) {
        if (!is_numeric($tmdbId)) continue;
        
        $checkStmt = $db->prepare("SELECT id FROM movies WHERE tmdb_id = ?");
        $checkStmt->execute([$tmdbId]);
        if ($checkStmt->fetch()) {
            continue;
        }
        
        $result = $tmdb->getMovieById($tmdbId);
        
        if (!$result['success']) continue;
        
        $movieData = $result['data'];
        $slug = generateSlug($movieData['title']);
        
        $checkStmt = $db->prepare("SELECT id FROM movies WHERE slug = ?");
        $checkStmt->execute([$slug]);
        if ($checkStmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        $posterPath = '';
        $backdropPath = '';
        
        if (!empty($movieData['poster'])) {
            $posterFilename = 'poster_' . $tmdbId . '_' . uniqid() . '.jpg';
            $posterPath = 'uploads/posters/' . $posterFilename;
            if (downloadImage($movieData['poster'], $posterPath)) {
                $posterPath = $posterPath;
            } else {
                $posterPath = $movieData['poster'];
            }
        }
        
        if (!empty($movieData['backdrop'])) {
            $backdropFilename = 'backdrop_' . $tmdbId . '_' . uniqid() . '.jpg';
            $backdropPath = 'uploads/posters/' . $backdropFilename;
            if (downloadImage($movieData['backdrop'], $backdropPath)) {
                $backdropPath = $backdropPath;
            } else {
                $backdropPath = $movieData['backdrop'];
            }
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO movies (tmdb_id, title, original_title, slug, description, poster, backdrop, 
                                  year, rating, runtime, genres, language, country, director, cast, trailer_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $movieData['tmdb_id'],
                $movieData['title'],
                $movieData['original_title'],
                $slug,
                $movieData['description'],
                $posterPath,
                $backdropPath,
                $movieData['year'],
                $movieData['rating'],
                $movieData['runtime'],
                $movieData['genres'],
                $movieData['language'],
                $movieData['country'],
                $movieData['director'],
                $movieData['cast'],
                $movieData['trailer_url']
            ]);
            
            $addedCount++;
        } catch (PDOException $e) {
            continue;
        }
    }
    
    if ($addedCount > 0) {
        $success = "Successfully added $addedCount movie(s)!";
    } else {
        $error = 'No movies were added. They may already exist or TMDB IDs are invalid.';
    }
}

$pageTitle = 'Bulk Add Movies';
include 'header.php';
?>

<div class="dashboard">
    <h1>Bulk Add Movies</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?> <a href="movies.php">View Movies</a></div>
    <?php endif; ?>
    
    <div class="dashboard-section">
        <h2>Add Multiple Movies from TMDB</h2>
        <p style="margin-bottom: 20px; color: #666;">
            Enter TMDB IDs (one per line) to automatically fetch and add movies to your database.
        </p>
        
        <form method="POST">
            <div class="form-group">
                <label>TMDB Movie IDs (one per line)</label>
                <textarea name="tmdb_ids" rows="15" placeholder="550
680
13
27205
278
238
424
389
129
155" required></textarea>
                <div class="help-text">
                    Find movie IDs on <a href="https://www.themoviedb.org/" target="_blank">TheMovieDB.org</a>. 
                    The ID is in the URL (e.g., for Fight Club: https://www.themoviedb.org/movie/550, the ID is 550)
                </div>
            </div>
            
            <div class="alert alert-info">
                <strong>Note:</strong> This process may take a few minutes depending on the number of movies. 
                Movies that already exist will be skipped automatically.
            </div>
            
            <button type="submit" name="bulk_add" class="btn btn-success">Add Movies</button>
            <a href="movies.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    
    <div class="dashboard-section" style="margin-top: 30px;">
        <h2>Popular Movie IDs</h2>
        <p style="margin-bottom: 15px;">Here are some popular movies you can add:</p>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <strong>The Shawshank Redemption</strong> - ID: 278
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>The Godfather</strong> - ID: 238
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>The Dark Knight</strong> - ID: 155
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>Pulp Fiction</strong> - ID: 680
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>Fight Club</strong> - ID: 550
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>Inception</strong> - ID: 27205
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>The Matrix</strong> - ID: 603
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>Interstellar</strong> - ID: 157336
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
