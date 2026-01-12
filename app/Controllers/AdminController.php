<?php
/**
 * Admin Controller
 */

class AdminController {
    private $userModel;
    private $songModel;
    private $roomModel;
    private $config;
    
    public function __construct() {
        AuthController::checkAdmin();
        $this->userModel = new User();
        $this->songModel = new Song();
        $this->roomModel = new Room();
        $this->config = require __DIR__ . '/../../config/app.php';
    }
    
    public function dashboard() {
        // Get admin dashboard statistics
        $stats = $this->getDashboardStats();
        
        $data = [
            'stats' => $stats,
            'recentUsers' => $this->userModel->getAll(10),
            'recentSongs' => $this->songModel->getRecent(10),
            'publicRooms' => $this->roomModel->getPublic(5)
        ];
        
        include __DIR__ . '/../Views/admin/dashboard.php';
    }
    
    public function songs() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleSongAction();
        }
        
        return $this->showSongsList();
    }
    
    public function songForm($id = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleSongSave($id);
        }
        
        $song = null;
        if ($id) {
            $song = $this->songModel->find($id);
            if (!$song) {
                header('HTTP/1.1 404 Not Found');
                include __DIR__ . '/../Views/errors/404.php';
                return;
            }
        }
        
        include __DIR__ . '/../Views/admin/song-form.php';
    }
    
    public function youtubeSearch() {
        header('Content-Type: application/json');
        
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'video'; // video, playlist
        
        if (empty($query)) {
            echo json_encode(['error' => 'Search query is required']);
            return;
        }
        
        try {
            if ($type === 'playlist') {
                $results = $this->searchYouTubePlaylists($query);
            } else {
                $results = $this->searchYouTubeVideos($query);
            }
            
            echo json_encode([
                'success' => true,
                'results' => $results,
                'query' => $query,
                'type' => $type
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function youtubeImport() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $videoId = $input['video_id'] ?? '';
        $playlistId = $input['playlist_id'] ?? '';
        $importType = $input['type'] ?? 'video';
        
        try {
            if ($importType === 'playlist' && $playlistId) {
                $results = $this->importYouTubePlaylist($playlistId);
            } else {
                $results = $this->importYouTubeVideo($videoId);
            }
            
            echo json_encode([
                'success' => true,
                'imported' => $results
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function uploadMedia() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showUploadForm();
        }
        
        return $this->handleMediaUpload();
    }
    
    private function getDashboardStats() {
        $db = Database::getInstance();
        
        return [
            'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
            'total_songs' => $db->fetch("SELECT COUNT(*) as count FROM songs WHERE is_active = 1")['count'],
            'total_rooms' => $db->fetch("SELECT COUNT(*) as count FROM rooms WHERE is_active = 1")['count'],
            'active_rooms' => $db->fetch("SELECT COUNT(*) as count FROM rooms r INNER JOIN room_participants rp ON r.id = rp.room_id WHERE r.is_active = 1 AND rp.is_active = 1")['count'],
            'total_songs_played' => $db->fetch("SELECT COUNT(*) as count FROM song_queue WHERE status = 'completed'")['count'],
            'recent_registrations' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count']
        ];
    }
    
    private function handleSongAction() {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'delete':
                return $this->handleSongDelete();
            case 'toggle_active':
                return $this->handleSongToggle();
            default:
                return $this->showSongsList();
        }
    }
    
    private function handleSongDelete() {
        $songId = $_POST['id'] ?? null;
        
        if (!$songId) {
            $_SESSION['error'] = "Song ID is required";
            header('Location: /admin/songs');
            exit;
        }
        
        try {
            $this->songModel->delete($songId);
            $_SESSION['success'] = "Song deleted successfully";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to delete song: " . $e->getMessage();
        }
        
        header('Location: /admin/songs');
        exit;
    }
    
    private function handleSongToggle() {
        $songId = $_POST['id'] ?? null;
        
        if (!$songId) {
            $_SESSION['error'] = "Song ID is required";
            header('Location: /admin/songs');
            exit;
        }
        
        try {
            $song = $this->songModel->find($songId);
            if ($song) {
                $newStatus = !$song['is_active'];
                $this->songModel->update($songId, ['is_active' => $newStatus]);
                $_SESSION['success'] = "Song " . ($newStatus ? "activated" : "deactivated") . " successfully";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to update song: " . $e->getMessage();
        }
        
        header('Location: /admin/songs');
        exit;
    }
    
    private function handleSongSave($id = null) {
        $data = [
            'song_number' => (int)($_POST['song_number'] ?? 0),
            'title' => trim($_POST['title'] ?? ''),
            'artist' => trim($_POST['artist'] ?? ''),
            'album' => trim($_POST['album'] ?? ''),
            'duration' => (int)($_POST['duration'] ?? 0),
            'language' => trim($_POST['language'] ?? ''),
            'genre' => trim($_POST['genre'] ?? ''),
            'year_released' => (int)($_POST['year_released'] ?? null),
            'youtube_video_id' => trim($_POST['youtube_video_id'] ?? ''),
            'youtube_playlist_id' => trim($_POST['youtube_playlist_id'] ?? ''),
            'file_path' => trim($_POST['file_path'] ?? ''),
            'embed_code' => trim($_POST['embed_code'] ?? ''),
            'external_url' => trim($_POST['external_url'] ?? ''),
            'lyrics' => trim($_POST['lyrics'] ?? ''),
            'is_active' => isset($_POST['is_active'])
        ];
        
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = "Song title is required";
        }
        
        if (empty($data['artist'])) {
            $errors[] = "Artist is required";
        }
        
        if ($data['song_number'] <= 0) {
            $errors[] = "Valid song number is required";
        } elseif (!$this->songModel->validateSongNumber($data['song_number'], $id)) {
            $errors[] = "Song number already exists";
        }
        
        if (empty($errors)) {
            try {
                if ($id) {
                    $this->songModel->update($id, $data);
                    $_SESSION['success'] = "Song updated successfully";
                } else {
                    $songId = $this->songModel->create($data);
                    $_SESSION['success'] = "Song created successfully";
                    $id = $songId;
                }
                
                // Handle categories
                $this->handleSongCategories($id, $_POST['categories'] ?? []);
                
                header('Location: /admin/songs');
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Failed to save song: " . $e->getMessage();
            }
        }
        
        $song = array_merge($id ? $this->songModel->find($id) : [], $data);
        $errorsJson = json_encode($errors);
        
        include __DIR__ . '/../Views/admin/song-form.php';
    }
    
    private function handleSongCategories($songId, $categoryIds) {
        // Remove existing categories
        $this->songModel->db->delete('song_categories', 'song_id = ?', [$songId]);
        
        // Add new categories
        foreach ($categoryIds as $categoryId) {
            if ($categoryId) {
                $this->songModel->attachCategory($songId, $categoryId);
            }
        }
    }
    
    private function showSongsList() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $category = (int)($_GET['category'] ?? 0);
        
        if ($search) {
            $songs = $this->songModel->search($search, $limit + 1);
        } elseif ($category) {
            $songs = $this->songModel->getByCategory($category, $limit + 1, $offset);
        } else {
            $songs = $this->songModel->getRecent($limit + 1);
        }
        
        $hasMore = count($songs) > $limit;
        if ($hasMore) {
            array_pop($songs);
        }
        
        // Get categories for filter
        $categories = Database::getInstance()->fetchAll(
            "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name"
        );
        
        $data = [
            'songs' => $songs,
            'categories' => $categories,
            'currentPage' => $page,
            'hasMore' => $hasMore,
            'search' => $search,
            'selectedCategory' => $category
        ];
        
        include __DIR__ . '/../Views/admin/songs.php';
    }
    
    private function showUploadForm() {
        include __DIR__ . '/../Views/admin/upload.php';
    }
    
    private function handleMediaUpload() {
        $errors = [];
        $uploadedFiles = [];
        
        if (!isset($_FILES['media_files']) || empty($_FILES['media_files']['name'][0])) {
            $errors[] = "No files selected for upload";
            return $this->showUploadForm();
        }
        
        $uploadDir = $this->config['uploads']['upload_path'] . '/media';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $files = $_FILES['media_files'];
        $fileCount = count($files['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $filename = $files['name'][$i];
                $tmpName = $files['tmp_name'][$i];
                $fileSize = $files['size'][$i];
                $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Check file type
                $isVideo = in_array($fileExt, $this->config['uploads']['allowed_types']['video']);
                $isAudio = in_array($fileExt, $this->config['uploads']['allowed_types']['audio']);
                
                if (!$isVideo && !$isAudio) {
                    $errors[] = "File type not allowed: {$filename}";
                    continue;
                }
                
                // Check file size
                if ($fileSize > $this->config['uploads']['max_size']) {
                    $errors[] = "File too large: {$filename}";
                    continue;
                }
                
                // Generate unique filename
                $newFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
                $targetPath = $uploadDir . '/' . $newFilename;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = [
                        'original_name' => $filename,
                        'stored_name' => $newFilename,
                        'path' => '/uploads/media/' . $newFilename,
                        'size' => $fileSize,
                        'type' => $isVideo ? 'video' : 'audio'
                    ];
                } else {
                    $errors[] = "Failed to upload: {$filename}";
                }
            }
        }
        
        $data = [
            'uploadedFiles' => $uploadedFiles,
            'errors' => $errors
        ];
        
        include __DIR__ . '/../Views/admin/upload-results.php';
    }
    
    private function searchYouTubeVideos($query) {
        $apiKey = $this->config['youtube']['api_key'];
        if (empty($apiKey)) {
            throw new Exception("YouTube API key not configured");
        }
        
        $url = "https://www.googleapis.com/youtube/v3/search?" . http_build_query([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => $this->config['youtube']['max_results'],
            'key' => $apiKey,
            'regionCode' => $this->config['youtube']['region']
        ]);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (!$data || isset($data['error'])) {
            throw new Exception("YouTube API error: " . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        $results = [];
        foreach ($data['items'] as $item) {
            $results[] = [
                'video_id' => $item['id']['videoId'],
                'title' => $item['snippet']['title'],
                'description' => $item['snippet']['description'],
                'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
                'channel_title' => $item['snippet']['channelTitle'],
                'published_at' => $item['snippet']['publishedAt'],
                'duration' => null, // Would need additional API call to get duration
            ];
        }
        
        return $results;
    }
    
    private function searchYouTubePlaylists($query) {
        $apiKey = $this->config['youtube']['api_key'];
        if (empty($apiKey)) {
            throw new Exception("YouTube API key not configured");
        }
        
        $url = "https://www.googleapis.com/youtube/v3/search?" . http_build_query([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'playlist',
            'maxResults' => $this->config['youtube']['max_results'],
            'key' => $apiKey,
            'regionCode' => $this->config['youtube']['region']
        ]);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (!$data || isset($data['error'])) {
            throw new Exception("YouTube API error: " . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        $results = [];
        foreach ($data['items'] as $item) {
            $results[] = [
                'playlist_id' => $item['id']['playlistId'],
                'title' => $item['snippet']['title'],
                'description' => $item['snippet']['description'],
                'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
                'channel_title' => $item['snippet']['channelTitle'],
                'published_at' => $item['snippet']['publishedAt'],
                'video_count' => null, // Would need additional API call to get count
            ];
        }
        
        return $results;
    }
    
    private function importYouTubeVideo($videoId) {
        if (empty($videoId)) {
            throw new Exception("Video ID is required");
        }
        
        // Get video details from YouTube API
        $apiKey = $this->config['youtube']['api_key'];
        $url = "https://www.googleapis.com/youtube/v3/videos?" . http_build_query([
            'part' => 'snippet,contentDetails',
            'id' => $videoId,
            'key' => $apiKey
        ]);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (!$data || empty($data['items'])) {
            throw new Exception("Video not found");
        }
        
        $video = $data['items'][0];
        $snippet = $video['snippet'];
        $contentDetails = $video['contentDetails'];
        
        // Parse duration
        $duration = $this->parseYouTubeDuration($contentDetails['duration']);
        
        // Create song entry
        $songData = [
            'song_number' => $this->songModel->getNextSongNumber(),
            'title' => $snippet['title'],
            'artist' => $snippet['channelTitle'],
            'description' => $snippet['description'],
            'duration' => $duration,
            'youtube_video_id' => $videoId,
            'language' => $this->detectLanguage($snippet['defaultLanguage'] ?? $snippet['defaultAudioLanguage'] ?? 'en'),
            'lyrics' => '', // Would need additional processing for lyrics
            'is_active' => true
        ];
        
        return $this->songModel->create($songData);
    }
    
    private function importYouTubePlaylist($playlistId) {
        if (empty($playlistId)) {
            throw new Exception("Playlist ID is required");
        }
        
        $apiKey = $this->config['youtube']['api_key'];
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?" . http_build_query([
            'part' => 'snippet',
            'playlistId' => $playlistId,
            'maxResults' => 50,
            'key' => $apiKey
        ]);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (!$data || empty($data['items'])) {
            throw new Exception("Playlist not found or empty");
        }
        
        $imported = [];
        foreach ($data['items'] as $item) {
            if ($item['snippet']['resourceId']['kind'] === 'youtube#video') {
                $videoId = $item['snippet']['resourceId']['videoId'];
                try {
                    $songId = $this->importYouTubeVideo($videoId);
                    $imported[] = $songId;
                } catch (Exception $e) {
                    // Log error but continue with other videos
                    error_log("Failed to import video {$videoId}: " . $e->getMessage());
                }
            }
        }
        
        return $imported;
    }
    
    private function parseYouTubeDuration($duration) {
        // Parse ISO 8601 duration (PT4M13S -> seconds)
        $matches = [];
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches)) {
            $hours = (int)($matches[1] ?? 0);
            $minutes = (int)($matches[2] ?? 0);
            $seconds = (int)($matches[3] ?? 0);
            return $hours * 3600 + $minutes * 60 + $seconds;
        }
        return 0;
    }
    
    private function detectLanguage($langCode) {
        // Simple language mapping
        $languages = [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese'
        ];
        
        return $languages[substr($langCode, 0, 2)] ?? 'English';
    }
}