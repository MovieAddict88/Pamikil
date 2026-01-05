<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGet($conn);
            break;
        case 'POST':
            handlePost($conn);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($conn) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'movies':
            getMovies($conn);
            break;
        case 'categories':
            getCategories($conn);
            break;
        case 'sync':
            syncData($conn);
            break;
        case 'search':
            searchContent($conn);
            break;
        case 'watchlater':
            getWatchLater($conn);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePost($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'add_watchlater':
            addToWatchLater($conn, $input);
            break;
        case 'remove_watchlater':
            removeFromWatchLater($conn, $input);
            break;
        case 'increment_view':
            incrementViewCount($conn, $input);
            break;
        case 'search_youtube':
            searchYouTubeAPI($input);
            break;
        case 'search_tmdb':
            searchTMDBAPI($input);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getMovies($conn) {
    $type = $_GET['type'] ?? '';
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $sql = "SELECT m.*, GROUP_CONCAT(ms.server_name) as servers, GROUP_CONCAT(ms.server_url) as server_urls 
            FROM movies m 
            LEFT JOIN movie_servers ms ON m.id = ms.movie_id 
            WHERE m.status = 'active'";
    
    $params = [];
    
    if ($type && in_array($type, ['movie', 'series', 'live'])) {
        $sql .= " AND m.type = ?";
        $params[] = $type;
    }
    
    $sql .= " GROUP BY m.id ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $movies = $stmt->fetchAll();
    
    // Process movies to add servers array
    foreach ($movies as &$movie) {
        $servers = [];
        if ($movie['server_urls']) {
            $serverNames = explode(',', $movie['servers']);
            $serverUrls = explode(',', $movie['server_urls']);
            
            foreach ($serverUrls as $index => $url) {
                $servers[] = [
                    'name' => $serverNames[$index] ?? "Server " . ($index + 1),
                    'url' => $url,
                    'quality' => '720p' // Default quality
                ];
            }
        }
        $movie['servers'] = $servers;
        unset($movie['servers'], $movie['server_urls']); // Remove temp fields
    }
    
    echo json_encode(['success' => true, 'data' => $movies]);
}

function getCategories($conn) {
    $stmt = $conn->query("SELECT * FROM categories ORDER BY order_index ASC");
    $categories = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $categories]);
}

function syncData($conn) {
    // Get all movies with latest data
    $stmt = $conn->query("SELECT * FROM movies WHERE status = 'active' ORDER BY updated_at DESC");
    $movies = $stmt->fetchAll();
    
    // Get categories
    $stmt = $conn->query("SELECT * FROM categories ORDER BY order_index ASC");
    $categories = $stmt->fetchAll();
    
    $data = [
        'movies' => $movies,
        'categories' => $categories,
        'last_sync' => date('c')
    ];
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function searchContent($conn) {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? '';
    
    if (!$query) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $sql = "SELECT * FROM movies WHERE status = 'active' AND (title LIKE ? OR description LIKE ? OR genre LIKE ?)";
    $params = ["%$query%", "%$query%", "%$query%"];
    
    if ($type && in_array($type, ['movie', 'series', 'live'])) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY view_count DESC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $results]);
}

function getWatchLater($conn) {
    // This would require user authentication
    // For now, return empty array as demo
    echo json_encode(['success' => true, 'data' => []]);
}

function addToWatchLater($conn, $input) {
    $movieId = $input['movie_id'] ?? null;
    $userId = $input['user_id'] ?? 1; // Default user for demo
    
    if (!$movieId) {
        throw new Exception('Movie ID required');
    }
    
    $stmt = $conn->prepare("INSERT IGNORE INTO watch_later (user_id, movie_id) VALUES (?, ?)");
    $result = $stmt->execute([$userId, $movieId]);
    
    echo json_encode(['success' => $result, 'message' => 'Added to watch later']);
}

function removeFromWatchLater($conn, $input) {
    $movieId = $input['movie_id'] ?? null;
    $userId = $input['user_id'] ?? 1;
    
    if (!$movieId) {
        throw new Exception('Movie ID required');
    }
    
    $stmt = $conn->prepare("DELETE FROM watch_later WHERE user_id = ? AND movie_id = ?");
    $result = $stmt->execute([$userId, $movieId]);
    
    echo json_encode(['success' => $result, 'message' => 'Removed from watch later']);
}

function incrementViewCount($conn, $input) {
    $movieId = $input['movie_id'] ?? null;
    
    if (!$movieId) {
        throw new Exception('Movie ID required');
    }
    
    $stmt = $conn->prepare("UPDATE movies SET view_count = view_count + 1 WHERE id = ?");
    $result = $stmt->execute([$movieId]);
    
    echo json_encode(['success' => $result, 'message' => 'View count updated']);
}

function searchYouTubeAPI($input) {
    $query = $input['query'] ?? '';
    $apiKey = getSetting('youtube_api_key');
    
    if (!$apiKey) {
        echo json_encode(['success' => false, 'error' => 'YouTube API key not configured']);
        return;
    }
    
    if (!$query) {
        echo json_encode(['success' => false, 'error' => 'Query required']);
        return;
    }
    
    $url = "https://www.googleapis.com/youtube/v3/search?" . http_build_query([
        'part' => 'snippet',
        'q' => $query,
        'type' => 'video',
        'maxResults' => 10,
        'key' => $apiKey
    ]);
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['items'])) {
        $results = array_map(function($item) {
            return [
                'id' => $item['id']['videoId'],
                'title' => $item['snippet']['title'],
                'description' => $item['snippet']['description'],
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'],
                'published_at' => $item['snippet']['publishedAt']
            ];
        }, $data['items']);
        
        echo json_encode(['success' => true, 'data' => $results]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No results found']);
    }
}

function searchTMDBAPI($input) {
    $query = $input['query'] ?? '';
    $apiKey = getSetting('tmdb_api_key');
    
    if (!$apiKey) {
        echo json_encode(['success' => false, 'error' => 'TMDB API key not configured']);
        return;
    }
    
    if (!$query) {
        echo json_encode(['success' => false, 'error' => 'Query required']);
        return;
    }
    
    $url = "https://api.themoviedb.org/3/search/multi?" . http_build_query([
        'api_key' => $apiKey,
        'query' => $query,
        'include_adult' => 'false',
        'language' => 'en-US',
        'page' => '1'
    ]);
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['results'])) {
        $results = array_map(function($item) {
            $type = $item['media_type'] === 'tv' ? 'series' : 'movie';
            
            return [
                'id' => $item['id'],
                'title' => $item['title'] ?? $item['name'],
                'release_date' => $item['release_date'] ?? $item['first_air_date'],
                'overview' => $item['overview'],
                'poster_path' => $item['poster_path'] ? "https://image.tmdb.org/t/p/w300" . $item['poster_path'] : null,
                'vote_average' => $item['vote_average'],
                'type' => $type
            ];
        }, $data['results']);
        
        echo json_encode(['success' => true, 'data' => $results]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No results found']);
    }
}

function getSetting($key) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : null;
    } catch (Exception $e) {
        return null;
    }
}
?>