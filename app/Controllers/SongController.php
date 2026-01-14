<?php
namespace App\Controllers;

use App\Models\SongModel;

class SongController extends BaseController
{
    private $songModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->songModel = new SongModel();
    }
    
    public function getSongs()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, (int)($_GET['limit'] ?? 20));
        $category = $_GET['category'] ?? null;
        $artist = $_GET['artist'] ?? null;
        
        $songs = $this->songModel->getSongs($page, $limit, $category, $artist);
        $total = $this->songModel->getTotalSongs($category, $artist);
        
        $this->jsonResponse([
            'songs' => $songs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    public function getSong($songId)
    {
        $song = $this->songModel->getSongById($songId);
        
        if (!$song) {
            $this->jsonResponse(['error' => 'Song not found'], 404);
        }
        
        $this->jsonResponse($song);
    }
    
    public function search()
    {
        $query = $_GET['q'] ?? '';
        $searchType = $_GET['type'] ?? 'all'; // all, number, title, artist
        
        if (empty($query)) {
            $this->jsonResponse(['error' => 'Search query required'], 400);
        }
        
        $songs = $this->songModel->searchSongs($query, $searchType);
        
        $this->jsonResponse([
            'query' => $query,
            'results' => $songs,
            'count' => count($songs)
        ]);
    }
    
    public function searchYouTube()
    {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        $type = $_GET['search_type'] ?? 'song'; // song, playlist, channel
        
        if (empty($query)) {
            $this->jsonResponse(['error' => 'Search query required'], 400);
        }
        
        // YouTube API integration would happen here
        // For now, returning mock data
        $mockResults = $this->getYouTubeMockResults($query, $type);
        
        $this->jsonResponse([
            'success' => true,
            'results' => $mockResults
        ]);
    }
    
    public function importYouTube()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['video_id'])) {
            $this->jsonResponse(['error' => 'Video ID required'], 400);
        }
        
        // YouTube video import would happen here
        // Extract metadata, download thumbnail, etc.
        
        $songData = [
            'title' => $data['title'] ?? 'Unknown Title',
            'artist' => $data['artist'] ?? 'Unknown Artist',
            'video_id' => $data['video_id'],
            'video_type' => 'youtube',
            'category_id' => $data['category_id'] ?? 1,
            'duration' => $data['duration'] ?? 0,
            'thumbnail_url' => $data['thumbnail_url'] ?? null
        ];
        
        $songId = $this->songModel->addSong($songData);
        
        $this->jsonResponse([
            'success' => true,
            'song_id' => $songId
        ]);
    }
    
    private function getYouTubeMockResults($query, $type)
    {
        $mockSongs = [
            [
                'video_id' => 'dQw4w9WgXcQ',
                'title' => 'Never Gonna Give You Up',
                'artist' => 'Rick Astley',
                'duration' => '3:32',
                'thumbnail_url' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
                'view_count' => '1000000000'
            ],
            [
                'video_id' => '9bZkp7q19f0',
                'title' => 'Gangnam Style',
                'artist' => 'PSY',
                'duration' => '4:12',
                'thumbnail_url' => 'https://img.youtube.com/vi/9bZkp7q19f0/mqdefault.jpg',
                'view_count' => '4500000000'
            ]
        ];
        
        $mockPlaylists = [
            [
                'playlist_id' => 'PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf',
                'title' => 'Popular Karaoke Songs',
                'item_count' => 50,
                'thumbnail_url' => 'https://i.ytimg.com/vi/abcdefghijk/mqdefault.jpg'
            ]
        ];
        
        switch ($type) {
            case 'playlist':
                return $mockPlaylists;
            default:
                return array_filter($mockSongs, function($song) use ($query) {
                    return stripos($song['title'], $query) !== false || 
                           stripos($song['artist'], $query) !== false;
                });
        }
    }
}