<?php
/**
 * Songs API Controller
 */

class SongsController {
    private $songModel;
    
    public function __construct() {
        $this->songModel = new Song();
    }
    
    public function search() {
        header('Content-Type: application/json');
        
        $query = $_GET['q'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        
        if (empty($query)) {
            echo json_encode(['results' => []]);
            return;
        }
        
        try {
            $songs = $this->songModel->search($query, $limit);
            echo json_encode(['results' => $songs]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Search failed']);
        }
    }
    
    public function popular() {
        header('Content-Type: application/json');
        
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        
        try {
            $songs = $this->songModel->getPopular($limit);
            echo json_encode(['songs' => $songs]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch popular songs']);
        }
    }
    
    public function recent() {
        header('Content-Type: application/json');
        
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        
        try {
            $songs = $this->songModel->getRecent($limit);
            echo json_encode(['songs' => $songs]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch recent songs']);
        }
    }
    
    public function byNumber() {
        header('Content-Type: application/json');
        
        $number = (int)($_GET['number'] ?? 0);
        
        if ($number <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid song number required']);
            return;
        }
        
        try {
            $song = $this->songModel->findByNumber($number);
            if ($song) {
                echo json_encode(['song' => $song]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Song not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch song']);
        }
    }
    
    public function random() {
        header('Content-Type: application/json');
        
        $limit = min((int)($_GET['limit'] ?? 10), 50);
        
        try {
            $songs = $this->songModel->getRandom($limit);
            echo json_encode(['songs' => $songs]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch random songs']);
        }
    }
    
    public function categories() {
        header('Content-Type: application/json');
        
        try {
            $categories = Database::getInstance()->fetchAll(
                "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name"
            );
            echo json_encode(['categories' => $categories]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch categories']);
        }
    }
    
    public function byCategory($categoryId) {
        header('Content-Type: application/json');
        
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;
        
        try {
            $songs = $this->songModel->getByCategory($categoryId, $limit, $offset);
            echo json_encode(['songs' => $songs]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch songs by category']);
        }
    }
}