<?php
defined('APP_ACCESS') or die('Direct access not permitted');

class YouTube {
    private $apiKey;
    private $baseUrl = 'https://www.googleapis.com/youtube/v3';
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: YOUTUBE_API_KEY;
    }
    
    private function makeRequest($endpoint, $params = []) {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'YouTube API key not configured'];
        }
        
        $params['key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'API request failed with code: ' . $httpCode];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Failed to parse API response'];
        }
        
        return ['success' => true, 'data' => $data];
    }
    
    public function searchVideos($query, $maxResults = 20) {
        return $this->makeRequest('/search', [
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => $maxResults,
            'videoDefinition' => 'high'
        ]);
    }
    
    public function getVideoById($videoId) {
        return $this->makeRequest('/videos', [
            'part' => 'snippet,contentDetails,statistics',
            'id' => $videoId
        ]);
    }
    
    public function extractVideoId($url) {
        $pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return false;
    }
    
    public function getEmbedUrl($videoId) {
        return "https://www.youtube.com/embed/{$videoId}";
    }
    
    public function getThumbnail($videoId, $quality = 'maxresdefault') {
        return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
    }
}
