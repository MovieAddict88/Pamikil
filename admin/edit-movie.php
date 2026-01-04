<?php
session_start();
define('APP_ACCESS', true);

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/youtube.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = getDB();
$youtube = new YouTube();
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movieId]);
$movie = $stmt->fetch();

if (!$movie) {
    redirect('movies.php');
}

$serversStmt = $db->query("SELECT * FROM servers WHERE is_active = 1 ORDER BY name");
$servers = $serversStmt->fetchAll();

$movieServersStmt = $db->prepare("
    SELECT ms.*, s.name as server_name 
    FROM movie_servers ms 
    JOIN servers s ON ms.server_id = s.id 
    WHERE ms.movie_id = ?
");
$movieServersStmt->execute([$movieId]);
$movieServers = $movieServersStmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_movie'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $year = (int)$_POST['year'];
        $rating = (float)$_POST['rating'];
        $runtime = (int)$_POST['runtime'];
        $genres = sanitize($_POST['genres']);
        $director = sanitize($_POST['director']);
        $cast = sanitize($_POST['cast']);
        $trailerUrl = sanitize($_POST['trailer_url']);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        
        if (empty($title)) {
            $error = 'Movie title is required';
        } else {
            $posterPath = $movie['poster'];
            $backdropPath = $movie['backdrop'];
            
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
                if (!empty($movie['poster']) && file_exists($movie['poster'])) {
                    unlink($movie['poster']);
                }
                $uploadResult = uploadFile($_FILES['poster'], 'uploads/posters/');
                if ($uploadResult['success']) {
                    $posterPath = $uploadResult['path'];
                }
            }
            
            if (isset($_FILES['backdrop']) && $_FILES['backdrop']['error'] === UPLOAD_ERR_OK) {
                if (!empty($movie['backdrop']) && file_exists($movie['backdrop'])) {
                    unlink($movie['backdrop']);
                }
                $uploadResult = uploadFile($_FILES['backdrop'], 'uploads/posters/');
                if ($uploadResult['success']) {
                    $backdropPath = $uploadResult['path'];
                }
            }
            
            try {
                $stmt = $db->prepare("
                    UPDATE movies SET 
                        title = ?, description = ?, year = ?, rating = ?, runtime = ?, 
                        genres = ?, director = ?, cast = ?, trailer_url = ?, 
                        poster = ?, backdrop = ?, is_featured = ?, is_published = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $title, $description, $year, $rating, $runtime, 
                    $genres, $director, $cast, $trailerUrl, 
                    $posterPath, $backdropPath, $isFeatured, $isPublished, $movieId
                ]);
                
                $success = 'Movie updated successfully!';
                
                $stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
                $stmt->execute([$movieId]);
                $movie = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['add_server'])) {
        $serverId = (int)$_POST['server_id'];
        $videoUrl = sanitize($_POST['video_url']);
        $quality = sanitize($_POST['quality']);
        $videoType = sanitize($_POST['video_type']);
        $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
        
        if (empty($videoUrl)) {
            $error = 'Video URL is required';
        } else {
            if ($isPrimary) {
                $db->prepare("UPDATE movie_servers SET is_primary = 0 WHERE movie_id = ?")->execute([$movieId]);
            }
            
            $stmt = $db->prepare("INSERT INTO movie_servers (movie_id, server_id, video_url, quality, video_type, is_primary) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$movieId, $serverId, $videoUrl, $quality, $videoType, $isPrimary]);
            
            $success = 'Video server added successfully!';
            
            $movieServersStmt = $db->prepare("SELECT ms.*, s.name as server_name FROM movie_servers ms JOIN servers s ON ms.server_id = s.id WHERE ms.movie_id = ?");
            $movieServersStmt->execute([$movieId]);
            $movieServers = $movieServersStmt->fetchAll();
        }
    } elseif (isset($_POST['delete_server'])) {
        $serverLinkId = (int)$_POST['server_link_id'];
        $db->prepare("DELETE FROM movie_servers WHERE id = ?")->execute([$serverLinkId]);
        
        $success = 'Video server removed successfully!';
        
        $movieServersStmt = $db->prepare("SELECT ms.*, s.name as server_name FROM movie_servers ms JOIN servers s ON ms.server_id = s.id WHERE ms.movie_id = ?");
        $movieServersStmt->execute([$movieId]);
        $movieServers = $movieServersStmt->fetchAll();
    }
}

$pageTitle = 'Edit Movie';
include 'header.php';
?>

<div class="dashboard">
    <h1>Edit Movie: <?php echo htmlspecialchars($movie['title']); ?></h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <div>
            <div class="dashboard-section">
                <h2>Movie Details</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($movie['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="6"><?php echo htmlspecialchars($movie['description']); ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Year</label>
                            <input type="number" name="year" value="<?php echo $movie['year']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Rating</label>
                            <input type="number" name="rating" min="0" max="10" step="0.1" value="<?php echo $movie['rating']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Runtime (minutes)</label>
                        <input type="number" name="runtime" value="<?php echo $movie['runtime']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Genres</label>
                        <input type="text" name="genres" value="<?php echo htmlspecialchars($movie['genres']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Director</label>
                        <input type="text" name="director" value="<?php echo htmlspecialchars($movie['director']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Cast</label>
                        <input type="text" name="cast" value="<?php echo htmlspecialchars($movie['cast']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Trailer URL</label>
                        <input type="url" name="trailer_url" value="<?php echo htmlspecialchars($movie['trailer_url']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_featured" <?php echo $movie['is_featured'] ? 'checked' : ''; ?>>
                            Featured Movie
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_published" <?php echo $movie['is_published'] ? 'checked' : ''; ?>>
                            Published
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>Update Poster</label>
                        <?php if (!empty($movie['poster'])): ?>
                            <img src="../<?php echo htmlspecialchars($movie['poster']); ?>" 
                                 alt="Current Poster" style="max-width: 150px; border-radius: 8px; margin-bottom: 10px;">
                        <?php endif; ?>
                        <input type="file" name="poster" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>Update Backdrop</label>
                        <?php if (!empty($movie['backdrop'])): ?>
                            <img src="../<?php echo htmlspecialchars($movie['backdrop']); ?>" 
                                 alt="Current Backdrop" style="max-width: 300px; border-radius: 8px; margin-bottom: 10px;">
                        <?php endif; ?>
                        <input type="file" name="backdrop" accept="image/*">
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" name="update_movie" class="btn btn-success">Update Movie</button>
                        <a href="movies.php" class="btn btn-secondary">Back to Movies</a>
                        <a href="../watch.php?slug=<?php echo $movie['slug']; ?>" class="btn btn-primary" target="_blank">View Movie</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div>
            <div class="dashboard-section">
                <h2>Video Servers</h2>
                
                <?php if (!empty($movieServers)): ?>
                    <?php foreach ($movieServers as $ms): ?>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 10px;">
                            <strong><?php echo htmlspecialchars($ms['server_name']); ?></strong>
                            <?php if ($ms['is_primary']): ?>
                                <span style="background: #27ae60; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">Primary</span>
                            <?php endif; ?>
                            <br>
                            <small>Type: <?php echo strtoupper($ms['video_type']); ?></small>
                            <?php if ($ms['quality']): ?>
                                <small> | Quality: <?php echo htmlspecialchars($ms['quality']); ?></small>
                            <?php endif; ?>
                            <br>
                            <small style="color: #666; word-break: break-all;"><?php echo htmlspecialchars(substr($ms['video_url'], 0, 50)) . '...'; ?></small>
                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="server_link_id" value="<?php echo $ms['id']; ?>">
                                <button type="submit" name="delete_server" class="btn btn-danger" style="padding: 5px 10px; font-size: 13px;">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #7f8c8d; text-align: center; padding: 20px;">No video servers added yet</p>
                <?php endif; ?>
                
                <form method="POST" style="margin-top: 20px;">
                    <div class="form-group">
                        <label>Server</label>
                        <select name="server_id" required>
                            <option value="">Select Server</option>
                            <?php foreach ($servers as $server): ?>
                                <option value="<?php echo $server['id']; ?>"><?php echo htmlspecialchars($server['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Video URL *</label>
                        <input type="url" name="video_url" placeholder="https://..." required>
                        <div class="help-text">Direct video URL or YouTube link</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Video Type</label>
                        <select name="video_type" required>
                            <option value="mp4">MP4</option>
                            <option value="mkv">MKV</option>
                            <option value="webm">WebM</option>
                            <option value="avi">AVI</option>
                            <option value="mpd">MPD/DASH</option>
                            <option value="youtube">YouTube</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quality</label>
                        <select name="quality">
                            <option value="">Auto</option>
                            <option value="480p">480p</option>
                            <option value="720p">720p</option>
                            <option value="1080p">1080p</option>
                            <option value="4K">4K</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_primary">
                            Set as Primary Server
                        </label>
                    </div>
                    
                    <button type="submit" name="add_server" class="btn btn-success">Add Server</button>
                </form>
            </div>
            
            <div class="dashboard-section" style="margin-top: 20px;">
                <h3>Movie Stats</h3>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                    <p><strong>Views:</strong> <?php echo number_format($movie['views']); ?></p>
                    <p><strong>Added:</strong> <?php echo date('M d, Y', strtotime($movie['created_at'])); ?></p>
                    <p><strong>Updated:</strong> <?php echo date('M d, Y', strtotime($movie['updated_at'])); ?></p>
                    <p><strong>Slug:</strong> <?php echo htmlspecialchars($movie['slug']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
