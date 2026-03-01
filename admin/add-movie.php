<?php
session_start();
define('APP_ACCESS', true);

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/tmdb.php';
require_once '../includes/youtube.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = getDB();
$tmdb = new TMDB();
$youtube = new YouTube();
$error = '';
$success = '';
$tmdbResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_tmdb'])) {
        $tmdbId = sanitize($_POST['tmdb_id']);
        
        if (!empty($tmdbId)) {
            $result = $tmdb->getMovieById($tmdbId);
            
            if ($result['success']) {
                $tmdbResult = $result['data'];
            } else {
                $error = $result['error'];
            }
        } else {
            $error = 'Please enter a TMDB ID';
        }
    } elseif (isset($_POST['add_movie'])) {
        $title = sanitize($_POST['title']);
        $originalTitle = sanitize($_POST['original_title']);
        $description = sanitize($_POST['description']);
        $year = (int)$_POST['year'];
        $rating = (float)$_POST['rating'];
        $runtime = (int)$_POST['runtime'];
        $genres = sanitize($_POST['genres']);
        $language = sanitize($_POST['language']);
        $country = sanitize($_POST['country']);
        $director = sanitize($_POST['director']);
        $cast = sanitize($_POST['cast']);
        $trailerUrl = sanitize($_POST['trailer_url']);
        $tmdbId = !empty($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : null;
        
        if (empty($title)) {
            $error = 'Movie title is required';
        } else {
            $slug = generateSlug($title);
            
            $checkStmt = $db->prepare("SELECT id FROM movies WHERE slug = ?");
            $checkStmt->execute([$slug]);
            if ($checkStmt->fetch()) {
                $slug = $slug . '-' . time();
            }
            
            $posterPath = '';
            $backdropPath = '';
            
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['poster'], 'uploads/posters/');
                if ($uploadResult['success']) {
                    $posterPath = $uploadResult['path'];
                }
            } elseif (!empty($_POST['poster_url'])) {
                $posterFilename = 'poster_' . uniqid() . '.jpg';
                $posterPath = 'uploads/posters/' . $posterFilename;
                if (downloadImage($_POST['poster_url'], $posterPath)) {
                    $posterPath = $posterPath;
                } else {
                    $posterPath = $_POST['poster_url'];
                }
            }
            
            if (isset($_FILES['backdrop']) && $_FILES['backdrop']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['backdrop'], 'uploads/posters/');
                if ($uploadResult['success']) {
                    $backdropPath = $uploadResult['path'];
                }
            } elseif (!empty($_POST['backdrop_url'])) {
                $backdropFilename = 'backdrop_' . uniqid() . '.jpg';
                $backdropPath = 'uploads/posters/' . $backdropFilename;
                if (downloadImage($_POST['backdrop_url'], $backdropPath)) {
                    $backdropPath = $backdropPath;
                } else {
                    $backdropPath = $_POST['backdrop_url'];
                }
            }
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO movies (tmdb_id, title, original_title, slug, description, poster, backdrop, 
                                      year, rating, runtime, genres, language, country, director, cast, trailer_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $tmdbId, $title, $originalTitle, $slug, $description, $posterPath, $backdropPath,
                    $year, $rating, $runtime, $genres, $language, $country, $director, $cast, $trailerUrl
                ]);
                
                $movieId = $db->lastInsertId();
                
                $success = 'Movie added successfully! <a href="edit-movie.php?id=' . $movieId . '">Edit Movie</a> | <a href="add-movie.php">Add Another</a>';
                
                $_POST = [];
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Add Movie';
include 'header.php';
?>

<div class="dashboard">
    <h1>Add New Movie</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="dashboard-section">
        <h2>Search by TMDB ID</h2>
        <form method="POST">
            <div class="form-group">
                <label>TMDB Movie ID</label>
                <input type="number" name="tmdb_id" placeholder="Enter TMDB ID (e.g., 550 for Fight Club)" 
                       value="<?php echo isset($_POST['tmdb_id']) ? htmlspecialchars($_POST['tmdb_id']) : ''; ?>">
                <div class="help-text">
                    Find movies on <a href="https://www.themoviedb.org/" target="_blank">TheMovieDB.org</a> 
                    and copy the movie ID from the URL
                </div>
            </div>
            <button type="submit" name="search_tmdb" class="btn btn-primary">Search TMDB</button>
        </form>
    </div>
    
    <div class="dashboard-section" style="margin-top: 30px;">
        <h2>Movie Details</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="tmdb_id" value="<?php echo $tmdbResult['tmdb_id'] ?? ''; ?>">
            <input type="hidden" name="poster_url" value="<?php echo $tmdbResult['poster'] ?? ''; ?>">
            <input type="hidden" name="backdrop_url" value="<?php echo $tmdbResult['backdrop'] ?? ''; ?>">
            
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required 
                       value="<?php echo $tmdbResult['title'] ?? ($_POST['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Original Title</label>
                <input type="text" name="original_title" 
                       value="<?php echo $tmdbResult['original_title'] ?? ($_POST['original_title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="6"><?php echo $tmdbResult['description'] ?? ($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" min="1900" max="2100" 
                           value="<?php echo $tmdbResult['year'] ?? ($_POST['year'] ?? date('Y')); ?>">
                </div>
                
                <div class="form-group">
                    <label>Rating</label>
                    <input type="number" name="rating" min="0" max="10" step="0.1" 
                           value="<?php echo $tmdbResult['rating'] ?? ($_POST['rating'] ?? '0'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Runtime (minutes)</label>
                <input type="number" name="runtime" min="0" 
                       value="<?php echo $tmdbResult['runtime'] ?? ($_POST['runtime'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Genres</label>
                <input type="text" name="genres" placeholder="Action, Drama, Thriller" 
                       value="<?php echo $tmdbResult['genres'] ?? ($_POST['genres'] ?? ''); ?>">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Language</label>
                    <input type="text" name="language" 
                           value="<?php echo $tmdbResult['language'] ?? ($_POST['language'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" 
                           value="<?php echo $tmdbResult['country'] ?? ($_POST['country'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Director</label>
                <input type="text" name="director" 
                       value="<?php echo $tmdbResult['director'] ?? ($_POST['director'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Cast</label>
                <input type="text" name="cast" placeholder="Actor 1, Actor 2, Actor 3" 
                       value="<?php echo $tmdbResult['cast'] ?? ($_POST['cast'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Trailer URL</label>
                <input type="url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=..." 
                       value="<?php echo $tmdbResult['trailer_url'] ?? ($_POST['trailer_url'] ?? ''); ?>">
            </div>
            
            <?php if (!empty($tmdbResult['poster'])): ?>
                <div class="form-group">
                    <label>Poster Preview</label>
                    <img src="<?php echo htmlspecialchars($tmdbResult['poster']); ?>" 
                         alt="Poster" style="max-width: 200px; border-radius: 8px;">
                    <div class="help-text">Poster will be downloaded automatically</div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Upload Poster</label>
                    <input type="file" name="poster" accept="image/*">
                </div>
            <?php endif; ?>
            
            <?php if (!empty($tmdbResult['backdrop'])): ?>
                <div class="form-group">
                    <label>Backdrop Preview</label>
                    <img src="<?php echo htmlspecialchars($tmdbResult['backdrop']); ?>" 
                         alt="Backdrop" style="max-width: 400px; border-radius: 8px;">
                    <div class="help-text">Backdrop will be downloaded automatically</div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Upload Backdrop</label>
                    <input type="file" name="backdrop" accept="image/*">
                </div>
            <?php endif; ?>
            
            <div class="btn-group">
                <button type="submit" name="add_movie" class="btn btn-success">Add Movie</button>
                <a href="movies.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
