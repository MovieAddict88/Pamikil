<?php
session_start();
define('APP_ACCESS', true);

require_once 'config/database.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = getDB();

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    redirect('index.php');
}

$stmt = $db->prepare("SELECT * FROM movies WHERE slug = ? AND is_published = 1");
$stmt->execute([$slug]);
$movie = $stmt->fetch();

if (!$movie) {
    redirect('index.php');
}

$serversStmt = $db->prepare("
    SELECT ms.*, s.name as server_name, s.type as server_type 
    FROM movie_servers ms 
    JOIN servers s ON ms.server_id = s.id 
    WHERE ms.movie_id = ? AND s.is_active = 1
    ORDER BY ms.is_primary DESC, ms.id ASC
");
$serversStmt->execute([$movie['id']]);
$movieServers = $serversStmt->fetchAll();

$userId = $_SESSION['user_id'] ?? null;
logMovieView($movie['id'], $userId);

$pageTitle = $movie['title'];
include 'includes/header.php';
?>

<div class="watch-page">
    <div class="player-container">
        <?php if (!empty($movieServers)): ?>
            <?php
            $primaryServer = $movieServers[0];
            $videoType = $primaryServer['video_type'];
            $videoUrl = $primaryServer['video_url'];
            ?>
            
            <?php if ($videoType === 'youtube'): ?>
                <div id="player" data-plyr-provider="youtube" data-plyr-embed-id="<?php 
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $matches);
                    echo $matches[1] ?? '';
                ?>"></div>
            <?php elseif ($videoType === 'mpd' || $videoType === 'dash'): ?>
                <video id="player" class="video-player" controls crossorigin playsinline></video>
                <script src="https://cdn.jsdelivr.net/npm/shaka-player@4.3.0/dist/shaka-player.compiled.js"></script>
            <?php else: ?>
                <video id="player" class="video-player" controls crossorigin playsinline>
                    <source src="<?php echo htmlspecialchars($videoUrl); ?>" type="video/<?php echo $videoType; ?>">
                </video>
            <?php endif; ?>
        <?php else: ?>
            <div style="padding: 100px; text-align: center; background: #000;">
                <p>No video sources available for this movie.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($movieServers) > 1): ?>
        <div class="server-tabs">
            <?php foreach ($movieServers as $index => $server): ?>
                <div class="server-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                     onclick="changeServer(<?php echo $index; ?>, '<?php echo htmlspecialchars($server['video_url'], ENT_QUOTES); ?>', '<?php echo $server['video_type']; ?>')">
                    <?php echo htmlspecialchars($server['server_name']); ?>
                    <?php if ($server['quality']): ?>
                        (<?php echo htmlspecialchars($server['quality']); ?>)
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="movie-details">
        <div class="movie-header">
            <div class="movie-poster">
                <?php if (!empty($movie['poster'])): ?>
                    <img src="<?php echo htmlspecialchars($movie['poster']); ?>" 
                         alt="<?php echo htmlspecialchars($movie['title']); ?>">
                <?php endif; ?>
            </div>
            
            <div class="movie-content">
                <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                
                <?php if ($movie['original_title'] && $movie['original_title'] !== $movie['title']): ?>
                    <p style="color: var(--text-secondary); margin-bottom: 15px;">
                        Original: <?php echo htmlspecialchars($movie['original_title']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="movie-meta-info">
                    <?php if ($movie['year']): ?>
                        <span>📅 <?php echo $movie['year']; ?></span>
                    <?php endif; ?>
                    
                    <span>⭐ <?php echo $movie['rating']; ?>/10</span>
                    
                    <?php if ($movie['runtime']): ?>
                        <span>⏱️ <?php echo formatRuntime($movie['runtime']); ?></span>
                    <?php endif; ?>
                    
                    <span>👁️ <?php echo number_format($movie['views']); ?> views</span>
                </div>
                
                <?php if ($movie['description']): ?>
                    <div class="movie-description">
                        <h3>Overview</h3>
                        <p><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($movie['trailer_url']): ?>
                    <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" 
                       target="_blank" class="btn btn-secondary" style="margin-top: 15px;">
                        🎬 Watch Trailer
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="movie-info-grid">
            <?php if ($movie['genres']): ?>
                <div class="info-item">
                    <strong>Genres</strong>
                    <?php echo htmlspecialchars($movie['genres']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($movie['director']): ?>
                <div class="info-item">
                    <strong>Director</strong>
                    <?php echo htmlspecialchars($movie['director']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($movie['language']): ?>
                <div class="info-item">
                    <strong>Language</strong>
                    <?php echo htmlspecialchars($movie['language']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($movie['country']): ?>
                <div class="info-item">
                    <strong>Country</strong>
                    <?php echo htmlspecialchars($movie['country']); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($movie['cast']): ?>
            <div style="margin-top: 20px;">
                <h3 style="margin-bottom: 10px;">Cast</h3>
                <p style="color: var(--text-secondary);"><?php echo htmlspecialchars($movie['cast']); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
<script>
    let currentPlayer = null;
    let shakaPlayer = null;
    
    function initPlayer() {
        const videoElement = document.getElementById('player');
        
        <?php if ($videoType === 'mpd' || $videoType === 'dash'): ?>
            if (shaka.Player.isBrowserSupported()) {
                shakaPlayer = new shaka.Player(videoElement);
                shakaPlayer.load('<?php echo htmlspecialchars($videoUrl); ?>').then(() => {
                    currentPlayer = new Plyr(videoElement, {
                        controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'fullscreen'],
                        settings: ['quality', 'speed'],
                    });
                }).catch((error) => {
                    console.error('Error loading video:', error);
                });
            }
        <?php else: ?>
            currentPlayer = new Plyr(videoElement, {
                controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'pip', 'airplay', 'fullscreen'],
                settings: ['quality', 'speed'],
            });
        <?php endif; ?>
    }
    
    function changeServer(index, url, type) {
        const tabs = document.querySelectorAll('.server-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        tabs[index].classList.add('active');
        
        if (currentPlayer) {
            currentPlayer.destroy();
        }
        
        if (shakaPlayer) {
            shakaPlayer.destroy();
            shakaPlayer = null;
        }
        
        const playerContainer = document.querySelector('.player-container');
        
        if (type === 'youtube') {
            const videoId = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
            if (videoId) {
                playerContainer.innerHTML = '<div id="player" data-plyr-provider="youtube" data-plyr-embed-id="' + videoId[1] + '"></div>';
                currentPlayer = new Plyr('#player');
            }
        } else if (type === 'mpd' || type === 'dash') {
            playerContainer.innerHTML = '<video id="player" class="video-player" controls crossorigin playsinline></video>';
            const videoElement = document.getElementById('player');
            
            if (shaka.Player.isBrowserSupported()) {
                shakaPlayer = new shaka.Player(videoElement);
                shakaPlayer.load(url).then(() => {
                    currentPlayer = new Plyr(videoElement);
                });
            }
        } else {
            playerContainer.innerHTML = '<video id="player" class="video-player" controls crossorigin playsinline><source src="' + url + '" type="video/' + type + '"></video>';
            currentPlayer = new Plyr('#player');
        }
    }
    
    document.addEventListener('DOMContentLoaded', initPlayer);
</script>

<?php include 'includes/footer.php'; ?>
