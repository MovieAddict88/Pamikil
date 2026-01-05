<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'cinecraze');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'CineCraze');
define('APP_VERSION', '2.0.0');
define('TMDB_API_BASE', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/w500');
define('DEFAULT_LANGUAGE', 'en-US');

// PWA Configuration
define('PWA_NAME', 'CineCraze');
define('PWA_SHORT_NAME', 'CineCraze');
define('PWA_THEME_COLOR', '#e50914');
define('PWA_BACKGROUND_COLOR', '#141414');

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper functions
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY main_category");
    return $stmt->fetchAll();
}

function getContent($type = null, $limit = 50, $offset = 0) {
    global $pdo;
    $sql = "SELECT * FROM content";
    $params = [];
    
    if ($type) {
        $sql .= " WHERE type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getContentById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getSeries($contentId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM series WHERE content_id = ? ORDER BY season_number");
    $stmt->execute([$contentId]);
    return $stmt->fetchAll();
}

function getEpisodes($seriesId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY episode_number");
    $stmt->execute([$seriesId]);
    return $stmt->fetchAll();
}

function getServers($contentId, $episodeId = null) {
    global $pdo;
    if ($episodeId) {
        $stmt = $pdo->prepare("SELECT * FROM servers WHERE content_id = ? AND episode_id = ? ORDER BY id");
        $stmt->execute([$contentId, $episodeId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM servers WHERE content_id = ? AND episode_id IS NULL ORDER BY id");
        $stmt->execute([$contentId]);
    }
    return $stmt->fetchAll();
}

function searchContent($query, $type = null) {
    global $pdo;
    $sql = "SELECT * FROM content WHERE title LIKE ?";
    $params = ["%{$query}%"];
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY title LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getWatchLater($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT wl.*, c.title, c.poster, c.type, e.title as episode_title, e.episode_number, s.season_number 
        FROM watch_later wl 
        JOIN content c ON wl.content_id = c.id 
        LEFT JOIN episodes e ON wl.episode_id = e.id 
        LEFT JOIN series s ON e.series_id = s.id 
        WHERE wl.user_id = ? 
        ORDER BY wl.added_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function addToWatchLater($userId, $contentId, $episodeId = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO watch_later (user_id, content_id, episode_id) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $contentId, $episodeId]);
    } catch (Exception $e) {
        return false;
    }
}

function removeFromWatchLater($userId, $contentId, $episodeId = null) {
    global $pdo;
    $sql = "DELETE FROM watch_later WHERE user_id = ? AND content_id = ?";
    $params = [$userId, $contentId];
    
    if ($episodeId) {
        $sql .= " AND episode_id = ?";
        $params[] = $episodeId;
    }
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function isInWatchLater($userId, $contentId, $episodeId = null) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM watch_later WHERE user_id = ? AND content_id = ?";
    $params = [$userId, $contentId];
    
    if ($episodeId) {
        $sql .= " AND episode_id = ?";
        $params[] = $episodeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT key_value FROM settings WHERE key_name = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['key_value'] : null;
}

function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = ?");
    return $stmt->execute([$key, $value, $value]);
}
?>