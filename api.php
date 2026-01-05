<?php
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_content':
            handleGetContent();
            break;
            
        case 'get_content_by_id':
            handleGetContentById();
            break;
            
        case 'search':
            handleSearch();
            break;
            
        case 'get_watchlater':
            handleGetWatchLater();
            break;
            
        case 'add_watchlater':
            handleAddWatchLater();
            break;
            
        case 'remove_watchlater':
            handleRemoveWatchLater();
            break;
            
        case 'get_categories':
            handleGetCategories();
            break;
            
        case 'get_episode':
            handleGetEpisode();
            break;
            
        case 'get_last_update':
            handleGetLastUpdate();
            break;
            
        case 'admin_login':
            handleAdminLogin();
            break;
            
        case 'admin_logout':
            handleAdminLogout();
            break;
            
        case 'get_admin_data':
            handleGetAdminData();
            break;
            
        case 'save_content':
            handleSaveContent();
            break;
            
        case 'delete_content':
            handleDeleteContent();
            break;
            
        case 'bulk_update':
            handleBulkUpdate();
            break;
            
        case 'fetch_tmdb':
            handleFetchTMDB();
            break;
            
        case 'export_data':
            handleExportData();
            break;
            
        case 'auto_embed':
            handleAutoEmbed();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGetContent() {
    global $pdo;
    
    $type = $_GET['type'] ?? null;
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT * FROM content";
    $params = [];
    
    if ($type && $type !== 'all') {
        $sql .= " WHERE type = ?";
        $params[] = $type;
    }
    
    // Apply filters
    $genre = $_GET['genre'] ?? '';
    $year = $_GET['year'] ?? '';
    $search = $_GET['search'] ?? '';
    
    if ($type && $type !== 'all') {
        $sql .= " AND 1=1"; // Ensure proper AND syntax
    }
    
    if ($genre) {
        $sql .= " AND sub_category = ?";
        $params[] = $genre;
    }
    
    if ($year) {
        $sql .= " AND year = ?";
        $params[] = intval($year);
    }
    
    if ($search) {
        $sql .= " AND title LIKE ?";
        $params[] = "%{$search}%";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $content = $stmt->fetchAll();
    
    // Get servers for each content item
    foreach ($content as &$item) {
        $servers = getServers($item['id']);
        $item['servers'] = $servers;
        
        // If it's a series, get seasons and episodes
        if ($item['type'] === 'series') {
            $seasons = getSeries($item['id']);
            foreach ($seasons as &$season) {
                $season['episodes'] = getEpisodes($season['id']);
            }
            $item['seasons'] = $seasons;
        }
    }
    
    echo json_encode([
        'success' => true,
        'content' => $content,
        'page' => $page,
        'total' => count($content)
    ]);
}

function handleGetContentById() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('Content ID is required');
    }
    
    $content = getContentById($id);
    if (!$content) {
        throw new Exception('Content not found');
    }
    
    $servers = getServers($id);
    $content['servers'] = $servers;
    
    if ($content['type'] === 'series') {
        $seasons = getSeries($id);
        foreach ($seasons as &$season) {
            $season['episodes'] = getEpisodes($season['id']);
        }
        $content['seasons'] = $seasons;
    }
    
    echo json_encode([
        'success' => true,
        'content' => $content
    ]);
}

function handleSearch() {
    global $pdo;
    
    $query = trim($_GET['q'] ?? '');
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'results' => []
        ]);
        return;
    }
    
    $type = $_GET['type'] ?? null;
    $results = searchContent($query, $type);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
}

function handleGetWatchLater() {
    global $pdo;
    
    $userId = $_GET['user_id'] ?? '';
    if (!$userId) {
        throw new Exception('User ID is required');
    }
    
    $items = getWatchLater($userId);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
}

function handleAddWatchLater() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';
    $contentId = intval($input['content_id'] ?? 0);
    $episodeId = $input['episode_id'] ?? null;
    
    if (!$userId || !$contentId) {
        throw new Exception('User ID and Content ID are required');
    }
    
    $success = addToWatchLater($userId, $contentId, $episodeId);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Added to watch later' : 'Failed to add to watch later'
    ]);
}

function handleRemoveWatchLater() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';
    $contentId = intval($input['content_id'] ?? 0);
    $episodeId = $input['episode_id'] ?? null;
    
    if (!$userId || !$contentId) {
        throw new Exception('User ID and Content ID are required');
    }
    
    $success = removeFromWatchLater($userId, $contentId, $episodeId);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Removed from watch later' : 'Failed to remove from watch later'
    ]);
}

function handleGetCategories() {
    $categories = getCategories();
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
}

function handleGetEpisode() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('Episode ID is required');
    }
    
    $stmt = $pdo->prepare("
        SELECT e.*, s.content_id, s.season_number 
        FROM episodes e 
        JOIN series s ON e.series_id = s.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $episode = $stmt->fetch();
    
    if (!$episode) {
        throw new Exception('Episode not found');
    }
    
    $servers = getServers($episode['content_id'], $id);
    
    echo json_encode([
        'success' => true,
        'episode' => $episode,
        'servers' => $servers
    ]);
}

function handleGetLastUpdate() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM content");
    $result = $stmt->fetch();
    $lastUpdate = $result['last_update'] ?? null;
    
    echo json_encode([
        'success' => true,
        'last_update' => $lastUpdate
    ]);
}

function handleAdminLogin() {
    session_start();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    // Simple admin authentication (in production, use proper password hashing)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials'
        ]);
    }
}

function handleAdminLogout() {
    session_start();
    
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logout successful'
    ]);
}

function handleGetAdminData() {
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Unauthorized');
    }
    
    global $pdo;
    
    // Get statistics
    $stats = [];
    
    // Total content count
    $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM content GROUP BY type");
    $typeCounts = $stmt->fetchAll();
    $stats['total_content'] = array_sum(array_column($typeCounts, 'count'));
    $stats['by_type'] = [];
    foreach ($typeCounts as $typeCount) {
        $stats['by_type'][$typeCount['type']] = $typeCount['count'];
    }
    
    // Recent content
    $stmt = $pdo->query("SELECT * FROM content ORDER BY created_at DESC LIMIT 10");
    $stats['recent_content'] = $stmt->fetchAll();
    
    // Categories
    $stats['categories'] = getCategories();
    
    // Settings
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = $stmt->fetchAll();
    $stats['settings'] = [];
    foreach ($settings as $setting) {
        $stats['settings'][$setting['key_name']] = $setting['key_value'];
    }
    
    // TMDB API key status
    $tmdbApiKey = getSetting('tmdb_api_key');
    $stats['tmdb_configured'] = !empty($tmdbApiKey);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function handleSaveContent() {
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Unauthorized');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $year = intval($input['year'] ?? 0);
    $type = $input['type'] ?? '';
    $subCategory = trim($input['sub_category'] ?? '');
    $country = trim($input['country'] ?? '');
    $rating = intval($input['rating'] ?? 0);
    $duration = trim($input['duration'] ?? '');
    $poster = trim($input['poster'] ?? '');
    $thumbnail = trim($input['thumbnail'] ?? '');
    $tmdbId = intval($input['tmdb_id'] ?? 0);
    
    if (!$title || !$type) {
        throw new Exception('Title and type are required');
    }
    
    global $pdo;
    
    if ($id) {
        // Update existing content
        $stmt = $pdo->prepare("
            UPDATE content SET 
            title = ?, description = ?, year = ?, type = ?, sub_category = ?, 
            country = ?, rating = ?, duration = ?, poster = ?, thumbnail = ?, tmdb_id = ?
            WHERE id = ?
        ");
        $success = $stmt->execute([
            $title, $description, $year, $type, $subCategory,
            $country, $rating, $duration, $poster, $thumbnail, $tmdbId, $id
        ]);
    } else {
        // Create new content
        $stmt = $pdo->prepare("
            INSERT INTO content (
                title, description, year, type, sub_category, 
                country, rating, duration, poster, thumbnail, tmdb_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([
            $title, $description, $year, $type, $subCategory,
            $country, $rating, $duration, $poster, $thumbnail, $tmdbId
        ]);
        $id = $pdo->lastInsertId();
    }
    
    // Handle servers
    if (isset($input['servers'])) {
        // Delete existing servers
        $stmt = $pdo->prepare("DELETE FROM servers WHERE content_id = ?");
        $stmt->execute([$id]);
        
        // Add new servers
        foreach ($input['servers'] as $server) {
            $stmt = $pdo->prepare("
                INSERT INTO servers (content_id, name, url, quality, language) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                trim($server['name'] ?? ''),
                trim($server['url'] ?? ''),
                trim($server['quality'] ?? ''),
                trim($server['language'] ?? '')
            ]);
        }
    }
    
    // Handle series seasons and episodes
    if ($type === 'series' && isset($input['seasons'])) {
        // Delete existing series data
        $stmt = $pdo->prepare("DELETE FROM episodes WHERE series_id IN (SELECT id FROM series WHERE content_id = ?)");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM series WHERE content_id = ?");
        $stmt->execute([$id]);
        
        // Add new seasons and episodes
        foreach ($input['seasons'] as $seasonData) {
            $seasonNumber = intval($seasonData['season_number'] ?? 0);
            $seasonTitle = trim($seasonData['title'] ?? '');
            $seasonPoster = trim($seasonData['poster'] ?? '');
            
            if ($seasonNumber > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO series (content_id, season_number, season_title, season_poster) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id, $seasonNumber, $seasonTitle, $seasonPoster]);
                $seriesId = $pdo->lastInsertId();
                
                // Add episodes for this season
                if (isset($seasonData['episodes'])) {
                    foreach ($seasonData['episodes'] as $episodeData) {
                        $episodeNumber = intval($episodeData['episode_number'] ?? 0);
                        $episodeTitle = trim($episodeData['title'] ?? '');
                        $episodeDescription = trim($episodeData['description'] ?? '');
                        $episodeDuration = trim($episodeData['duration'] ?? '');
                        $episodeThumbnail = trim($episodeData['thumbnail'] ?? '');
                        
                        if ($episodeNumber > 0) {
                            $stmt = $pdo->prepare("
                                INSERT INTO episodes (
                                    series_id, episode_number, title, description, duration, thumbnail
                                ) VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $seriesId, $episodeNumber, $episodeTitle,
                                $episodeDescription, $episodeDuration, $episodeThumbnail
                            ]);
                        }
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => $success,
        'id' => $id,
        'message' => $id ? 'Content updated successfully' : 'Content created successfully'
    ]);
}

function handleDeleteContent() {
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Unauthorized');
    }
    
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('Content ID is required');
    }
    
    global $pdo;
    
    // Delete in correct order due to foreign key constraints
    $stmt = $pdo->prepare("DELETE FROM episodes WHERE series_id IN (SELECT id FROM series WHERE content_id = ?)");
    $stmt->execute([$id]);
    
    $stmt = $pdo->prepare("DELETE FROM series WHERE content_id = ?");
    $stmt->execute([$id]);
    
    $stmt = $pdo->prepare("DELETE FROM servers WHERE content_id = ?");
    $stmt->execute([$id]);
    
    $stmt = $pdo->prepare("DELETE FROM watch_later WHERE content_id = ?");
    $stmt->execute([$id]);
    
    $stmt = $pdo->prepare("DELETE FROM content WHERE id = ?");
    $success = $stmt->execute([$id]);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Content deleted successfully' : 'Failed to delete content'
    ]);
}

function handleBulkUpdate() {
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Unauthorized');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $updates = $input['updates'] ?? [];
    $operation = $input['operation'] ?? '';
    
    if (empty($updates)) {
        throw new Exception('No updates provided');
    }
    
    global $pdo;
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($updates as $update) {
        try {
            $id = intval($update['id'] ?? 0);
            if (!$id) continue;
            
            $fields = [];
            $values = [];
            
            foreach ($update as $field => $value) {
                if ($field === 'id') continue;
                if (in_array($field, ['title', 'description', 'sub_category', 'country', 'duration', 'poster', 'thumbnail'])) {
                    $fields[] = "$field = ?";
                    $values[] = trim($value);
                } elseif (in_array($field, ['year', 'rating', 'tmdb_id'])) {
                    $fields[] = "$field = ?";
                    $values[] = intval($value);
                }
            }
            
            if (!empty($fields)) {
                $values[] = $id;
                $sql = "UPDATE content SET " . implode(', ', $fields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                $successCount++;
            }
        } catch (Exception $e) {
            $errorCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Bulk update completed: $successCount successful, $errorCount failed",
        'successful' => $successCount,
        'failed' => $errorCount
    ]);
}

function handleFetchTMDB() {
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Unauthorized');
    }
    
    $query = trim($_GET['query'] ?? '');
    $type = $_GET['type'] ?? 'movie';
    
    if (!$query) {
        throw new Exception('Search query is required');
    }
    
    $tmdbApiKey = getSetting('tmdb_api_key');
    if (empty($tmdbApiKey)) {
        throw new Exception('TMDB API key not configured');
    }
    
    $searchType = $type === 'series' ? 'tv' : 'movie';
    $url = TMDB_API_BASE . "/search/{$searchType}?api_key={$tmdbApiKey}&query=" . urlencode($query);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'CineCraze/2.0'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        throw new Exception('Failed to fetch from TMDB');
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['results'])) {
        throw new Exception('Invalid TMDB response');
    }
    
    $results = [];
    foreach ($data['results'] as $result) {
        $item = [
            'id' => $result['id'],
            'title' => $result['title'] ?? $result['name'] ?? '',
            'overview' => $result['overview'] ?? '',
            'poster_path' => $result['poster_path'] ? TMDB_IMAGE_BASE . $result['poster_path'] : '',
            'backdrop_path' => $result['backdrop_path'] ? TMDB_IMAGE_BASE . $result['backdrop_path'] : '',
            'release_date' => $result['release_date'] ?? $result['first_air_date'] ?? '',
            'vote_average' => $result['vote_average'] ?? 0,
            'genre_ids' => $result['genre_ids'] ?? []
        ];
        $results[] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
}

function handleExportData() {
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Unauthorized');
    }
    
    global $pdo;
    
    // Get all data
    $categories = getCategories();
    
    $stmt = $pdo->query("SELECT * FROM content ORDER BY type, title");
    $content = $stmt->fetchAll();
    
    // Structure data as original JSON format
    $exportData = [
        'categories' => $categories,
        'content' => []
    ];
    
    foreach ($content as $item) {
        $contentItem = [
            'type' => $item['type'],
            'Title' => $item['title'],
            'Description' => $item['description'],
            'Year' => $item['year'],
            'SubCategory' => $item['sub_category'],
            'Country' => $item['country'],
            'Rating' => $item['rating'],
            'Duration' => $item['duration'],
            'Poster' => $item['poster'],
            'Thumbnail' => $item['thumbnail']
        ];
        
        // Add servers
        $servers = getServers($item['id']);
        if (!empty($servers)) {
            $contentItem['Servers'] = array_map(function($server) {
                return [
                    'name' => $server['name'],
                    'url' => $server['url']
                ];
            }, $servers);
        }
        
        // Add series data if applicable
        if ($item['type'] === 'series') {
            $seasons = getSeries($item['id']);
            $contentItem['Seasons'] = [];
            
            foreach ($seasons as $season) {
                $seasonData = [
                    'Season' => $season['season_number'],
                    'Title' => $season['season_title'],
                    'SeasonPoster' => $season['season_poster'],
                    'Episodes' => []
                ];
                
                $episodes = getEpisodes($season['id']);
                foreach ($episodes as $episode) {
                    $episodeData = [
                        'Episode' => $episode['episode_number'],
                        'Title' => $episode['title'],
                        'Description' => $episode['description'],
                        'Duration' => $episode['duration'],
                        'Thumbnail' => $episode['thumbnail']
                    ];
                    
                    // Get episode servers
                    $episodeServers = getServers($item['id'], $episode['id']);
                    if (!empty($episodeServers)) {
                        $episodeData['Servers'] = array_map(function($server) {
                            return [
                                'name' => $server['name'],
                                'url' => $server['url']
                            ];
                        }, $episodeServers);
                    }
                    
                    $seasonData['Episodes'][] = $episodeData;
                }
                
                $contentItem['Seasons'][] = $seasonData;
            }
        }
        
        $exportData['content'][] = $contentItem;
    }
    
    // Group by type
    $groupedContent = [
        'Movies' => [],
        'TV Series' => [],
        'Live TV' => []
    ];
    
    foreach ($exportData['content'] as $item) {
        $typeKey = $item['type'] === 'movie' ? 'Movies' : 
                  ($item['type'] === 'series' ? 'TV Series' : 'Live TV');
        $groupedContent[$typeKey][] = $item;
    }
    
    $finalExport = [
        'categories' => $exportData['categories'],
        'content' => $groupedContent
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $finalExport
    ]);
}

function handleAutoEmbed() {
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Unauthorized');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $tmdbId = intval($input['tmdb_id'] ?? 0);
    $contentIds = $input['content_ids'] ?? [];
    
    if (!$tmdbId || empty($contentIds)) {
        throw new Exception('TMDB ID and content IDs are required');
    }
    
    $tmdbApiKey = getSetting('tmdb_api_key');
    if (empty($tmdbApiKey)) {
        throw new Exception('TMDB API key not configured');
    }
    
    global $pdo;
    $successCount = 0;
    
    foreach ($contentIds as $contentId) {
        try {
            $content = getContentById($contentId);
            if (!$content) continue;
            
            // Generate auto-embed URLs based on TMDB ID
            $embedSources = generateAutoEmbedSources($tmdbId, $content['type']);
            
            // Delete existing servers
            $stmt = $pdo->prepare("DELETE FROM servers WHERE content_id = ?");
            $stmt->execute([$contentId]);
            
            // Add new auto-embed servers
            foreach ($embedSources as $source) {
                $stmt = $pdo->prepare("
                    INSERT INTO servers (content_id, name, url) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$contentId, $source['name'], $source['url']]);
            }
            
            $successCount++;
            
        } catch (Exception $e) {
            // Log error but continue with other content
            error_log("Auto-embed failed for content {$contentId}: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Auto-embed applied to {$successCount} items",
        'processed' => $successCount
    ]);
}

function generateAutoEmbedSources($tmdbId, $type) {
    $sources = [];
    
    // VidSrc sources
    $vidsrcUrl = $type === 'series' 
        ? "https://vidsrc.net/embed/tv/{$tmdbId}"
        : "https://vidsrc.net/embed/movie/{$tmdbId}";
    
    $sources[] = [
        'name' => 'VidSrc',
        'url' => $vidsrcUrl
    ];
    
    // MultiEmbed source
    $multiEmbedUrl = "https://multiembed.mov/?video_id={$tmdbId}&tmdb=1";
    
    $sources[] = [
        'name' => 'MultiEmbed',
        'url' => $multiEmbedUrl
    ];
    
    // VidJoy source
    $vidjoyUrl = $type === 'series'
        ? "https://vidjoy.pro/embed/tv/{$tmdbId}"
        : "https://vidjoy.pro/embed/movie/{$tmdbId}";
    
    $sources[] = [
        'name' => 'VidJoy',
        'url' => $vidjoyUrl
    ];
    
    return $sources;
}
?>