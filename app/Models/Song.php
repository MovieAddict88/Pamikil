<?php
/**
 * Song Model
 */

class Song {
    private $db;
    private $table = 'songs';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        return $this->db->fetch(
            "SELECT s.*, GROUP_CONCAT(c.name) as categories 
             FROM {$this->table} s 
             LEFT JOIN song_categories sc ON s.id = sc.song_id 
             LEFT JOIN categories c ON sc.category_id = c.id 
             WHERE s.id = ? AND s.is_active = 1 
             GROUP BY s.id",
            [$id]
        );
    }
    
    public function findByNumber($songNumber) {
        return $this->db->fetch(
            "SELECT s.*, GROUP_CONCAT(c.name) as categories 
             FROM {$this->table} s 
             LEFT JOIN song_categories sc ON s.id = sc.song_id 
             LEFT JOIN categories c ON sc.category_id = c.id 
             WHERE s.song_number = ? AND s.is_active = 1 
             GROUP BY s.id",
            [$songNumber]
        );
    }
    
    public function create($data) {
        $required = ['song_number', 'title', 'artist'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->update($id, ['is_active' => false]);
    }
    
    public function search($query, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT s.*, GROUP_CONCAT(c.name) as categories 
             FROM {$this->table} s 
             LEFT JOIN song_categories sc ON s.id = sc.song_id 
             LEFT JOIN categories c ON sc.category_id = c.id 
             WHERE (s.title LIKE ? OR s.artist LIKE ? OR MATCH(s.lyrics) AGAINST(? IN BOOLEAN MODE)) 
             AND s.is_active = 1 
             GROUP BY s.id 
             ORDER BY 
                 CASE 
                     WHEN s.title LIKE ? THEN 1 
                     WHEN s.artist LIKE ? THEN 2 
                     ELSE 3 
                 END
             LIMIT ?",
            [
                "%{$query}%", "%{$query}%", "{$query}*",
                "{$query}%", "{$query}%",
                $limit
            ]
        );
    }
    
    public function getByCategory($categoryId, $limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT s.*, GROUP_CONCAT(c.name) as categories 
             FROM {$this->table} s 
             INNER JOIN song_categories sc ON s.id = sc.song_id 
             LEFT JOIN categories c ON sc.category_id = c.id 
             WHERE sc.category_id = ? AND s.is_active = 1 
             GROUP BY s.id 
             ORDER BY s.title 
             LIMIT ? OFFSET ?",
            [$categoryId, $limit, $offset]
        );
    }
    
    public function getRandom($limit = 10) {
        return $this->db->fetchAll(
            "SELECT s.*, GROUP_CONCAT(c.name) as categories 
             FROM {$this->table} s 
             LEFT JOIN song_categories sc ON s.id = sc.song_id 
             LEFT JOIN categories c ON sc.category_id = c.id 
             WHERE s.is_active = 1 
             GROUP BY s.id 
             ORDER BY RAND() 
             LIMIT ?",
            [$limit]
        );
    }
    
    public function getPopular($limit = 20) {
        return $this->db->fetchAll(
            "SELECT s.*, 
                    COUNT(sq.id) as play_count,
                    GROUP_CONCAT(DISTINCT c.name) as categories
             FROM {$this->table} s 
             LEFT JOIN song_queue sq ON s.id = sq.song_id 
             LEFT JOIN song_categories sc ON s.id = sc.song_id 
             LEFT JOIN categories c ON sc.category_id = c.id 
             WHERE s.is_active = 1 
             GROUP BY s.id 
             ORDER BY play_count DESC, s.title 
             LIMIT ?",
            [$limit]
        );
    }
    
    public function getRecent($limit = 20) {
        return $this->db->fetchAll(
            "SELECT s.*, GROUP_CONCAT(DISTINCT c.name) as categories 
             FROM {$this->table} s 
             LEFT JOIN song_categories sc ON s.id = sc.song_id 
             LEFT JOIN categories c ON sc.category_id = c.id 
             WHERE s.is_active = 1 
             GROUP BY s.id 
             ORDER BY s.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    public function getByYoutubeId($youtubeId) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} 
             WHERE youtube_video_id = ? AND is_active = 1",
            [$youtubeId]
        );
    }
    
    public function attachCategory($songId, $categoryId) {
        try {
            $this->db->query(
                "INSERT IGNORE INTO song_categories (song_id, category_id) VALUES (?, ?)",
                [$songId, $categoryId]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function detachCategory($songId, $categoryId) {
        return $this->db->delete(
            'song_categories', 
            'song_id = ? AND category_id = ?', 
            [$songId, $categoryId]
        );
    }
    
    public function getCategories($songId) {
        return $this->db->fetchAll(
            "SELECT c.* FROM categories c 
             INNER JOIN song_categories sc ON c.id = sc.category_id 
             WHERE sc.song_id = ? AND c.is_active = 1",
            [$songId]
        );
    }
    
    public function getNextSongNumber() {
        $result = $this->db->fetch("SELECT MAX(song_number) as max_number FROM {$this->table}");
        return ($result['max_number'] ?? 0) + 1;
    }
    
    public function validateSongNumber($songNumber, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE song_number = ? AND is_active = 1";
        $params = [$songNumber];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return !$this->db->fetch($sql, $params);
    }
    
    public function getSongStats($songId) {
        return $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT sq.id) as total_performances,
                COUNT(DISTINCT sq.room_id) as rooms_played,
                AVG(scores.score) as avg_score,
                MAX(scores.score) as best_score
             FROM songs s
             LEFT JOIN song_queue sq ON s.id = sq.song_id
             LEFT JOIN scores ON s.id = scores.song_id AND sq.user_id = scores.user_id
             WHERE s.id = ?",
            [$songId]
        ) ?: [
            'total_performances' => 0,
            'rooms_played' => 0,
            'avg_score' => 0,
            'best_score' => 0
        ];
    }
}