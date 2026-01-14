<?php
namespace App\Controllers;

use App\Models\SongModel;
use App\Models\CategoryModel;
use App\Models\PlaylistModel;

class AdminController extends BaseController
{
    private $songModel;
    private $categoryModel;
    private $playlistModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->songModel = new SongModel();
        $this->categoryModel = new CategoryModel();
        $this->playlistModel = new PlaylistModel();
    }
    
    public function dashboard()
    {
        $stats = [
            'total_songs' => $this->songModel->getTotalSongs(),
            'total_categories' => $this->categoryModel->getTotalCategories(),
            'total_playlists' => $this->playlistModel->getTotalPlaylists(),
            'recent_songs' => $this->songModel->getRecentSongs(5)
        ];
        
        $this->render('admin/dashboard', ['stats' => $stats]);
    }
    
    public function manageSongs()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        
        $songs = $this->songModel->getSongs($page, $limit);
        $categories = $this->categoryModel->getAllCategories();
        
        $total = $this->songModel->getTotalSongs();
        
        $this->render('admin/songs', [
            'songs' => $songs,
            'categories' => $categories,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    public function addSong()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['title']) || !isset($data['artist'])) {
            $this->jsonResponse(['error' => 'Title and artist are required'], 400);
        }
        
        $songData = [
            'title' => $data['title'],
            'artist' => $data['artist'],
            'song_number' => $data['song_number'] ?? null,
            'category_id' => $data['category_id'] ?? 1,
            'duration' => $data['duration'] ?? 0,
            'video_type' => $data['video_type'] ?? 'youtube',
            'video_id' => $data['video_id'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'embed_code' => $data['embed_code'] ?? null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'lyrics' => $data['lyrics'] ?? null,
            'language' => $data['language'] ?? 'en',
            'bpm' => $data['bpm'] ?? null,
            'key' => $data['key'] ?? null
        ];
        
        $songId = $this->songModel->addSong($songData);
        
        if ($songId) {
            $this->jsonResponse([
                'success' => true,
                'song_id' => $songId
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to add song'], 500);
        }
    }
    
    public function editSong($songId)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        $success = $this->songModel->updateSong($songId, $data);
        
        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update song'], 500);
        }
    }
    
    public function deleteSong($songId)
    {
        $success = $this->songModel->deleteSong($songId);
        
        if ($success) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['error' => 'Failed to delete song'], 500);
        }
    }
    
    public function searchYouTube()
    {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'song';
        
        if (empty($query)) {
            $this->jsonResponse(['error' => 'Search query required'], 400);
        }
        
        // YouTube API integration
        $results = $this->searchYouTubeAPI($query, $type);
        
        $this->jsonResponse($results);
    }
    
    public function importYouTube()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['video_id'])) {
            $this->jsonResponse(['error' => 'Video ID required'], 400);
        }
        
        // Import YouTube video metadata
        $videoData = $this->getYouTubeVideoData($data['video_id']);
        
        if ($videoData) {
            $songId = $this->songModel->addSong($videoData);
            $this->jsonResponse(['success' => true, 'song_id' => $songId]);
        } else {
            $this->jsonResponse(['error' => 'Failed to import video'], 500);
        }
    }
    
    public function uploadVideo()
    {
        if (!isset($_FILES['video'])) {
            $this->jsonResponse(['error' => 'No video file uploaded'], 400);
        }
        
        $file = $_FILES['video'];
        $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->jsonResponse(['error' => 'Invalid video format'], 400);
        }
        
        $uploadDir = __DIR__ . '/../../uploads/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $songData = [
                'title' => $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME),
                'artist' => $_POST['artist'] ?? 'Unknown Artist',
                'video_type' => 'upload',
                'video_url' => '/uploads/videos/' . $filename,
                'duration' => $_POST['duration'] ?? 0,
                'category_id' => $_POST['category_id'] ?? 1
            ];
            
            $songId = $this->songModel->addSong($songData);
            
            $this->jsonResponse([
                'success' => true,
                'song_id' => $songId,
                'filename' => $filename
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to upload video'], 500);
        }
    }
    
    public function manageCategories()
    {
        $categories = $this->categoryModel->getAllCategories();
        $this->render('admin/categories', ['categories' => $categories]);
    }
    
    public function managePlaylists()
    {
        $playlists = $this->playlistModel->getAllPlaylists();
        $this->render('admin/playlists', ['playlists' => $playlists]);
    }
    
    private function searchYouTubeAPI($query, $type)
    {
        // This would integrate with YouTube Data API
        // For now, returning mock data
        return [
            'query' => $query,
            'results' => [],
            'type' => $type
        ];
    }
    
    private function getYouTubeVideoData($videoId)
    {
        // This would fetch video data from YouTube API
        // For now, returning mock data
        return [
            'title' => 'YouTube Video',
            'artist' => 'YouTube Artist',
            'video_type' => 'youtube',
            'video_id' => $videoId,
            'duration' => 0
        ];
    }
}