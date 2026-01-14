<?php
namespace App\Models;

use App\Config\Database;

class SongModel
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getSongs($page = 1, $limit = 20, $category = null, $artist = null)
    {
        $offset = ($page - 1) * $limit;
        
        $conditions = ['s.deleted_at IS NULL'];
        $params = [];
        
        if ($category) {
            $conditions[] = 's.category_id = ?';
            $params[] = $category;
        }
        
        if ($artist) {
            $conditions[] = 's.artist LIKE ?';
            $params[] = '%' . $artist . '%';
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name
            FROM songs s
            LEFT JOIN categories c ON s.category_id = c.id
            {$whereClause}
            ORDER BY s.song_number ASC, s.artist ASC, s.title ASC
            LIMIT {$limit} OFFSET {$offset}
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getSongById($songId)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name
            FROM songs s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.id = ? AND s.deleted_at IS NULL
        ");
        
        $stmt->execute([$songId]);
        return $stmt->fetch();
    }
    
    public function getSongByNumber($songNumber)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name
            FROM songs s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.song_number = ? AND s.deleted_at IS NULL
        ");
        
        $stmt->execute([$songNumber]);
        return $stmt->fetch();
    }
    
    public function searchSongs($query, $searchType = 'all')
    {
        $searchTerm = '%' . $query . '%';
        $params = [];
        
        switch ($searchType) {
            case 'number':
                $whereClause = 'WHERE s.song_number LIKE ?';
                $params[] = $searchTerm;
                break;
            case 'title':
                $whereClause = 'WHERE s.title LIKE ?';
                $params[] = $searchTerm;
                break;
            case 'artist':
                $whereClause = 'WHERE s.artist LIKE ?';
                $params[] = $searchTerm;
                break;
            default:
                $whereClause = 'WHERE s.title LIKE ? OR s.artist LIKE ? OR s.song_number LIKE ?';
                $params = [$searchTerm, $searchTerm, $searchTerm];
                break;
        }
        
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name
            FROM songs s
            LEFT JOIN categories c ON s.category_id = c.id
            {$whereClause}
            AND s.deleted_at IS NULL
            ORDER BY s.artist ASC, s.title ASC
            LIMIT 50
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function addSong($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO songs (
                title, artist, song_number, category_id, duration, 
                video_type, video_id, video_url, embed_code, thumbnail_url,
                lyrics, language, bpm, key_signature, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $stmt->execute([
            $data['title'] ?? 'Unknown Title',
            $data['artist'] ?? 'Unknown Artist',
            $data['song_number'] ?? null,
            $data['category_id'] ?? 1,
            $data['duration'] ?? 0,
            $data['video_type'] ?? 'youtube',
            $data['video_id'] ?? null,
            $data['video_url'] ?? null,
            $data['embed_code'] ?? null,
            $data['thumbnail_url'] ?? null,
            $data['lyrics'] ?? null,
            $data['language'] ?? 'en',
            $data['bpm'] ?? null,
            $data['key'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateSong($songId, $data)
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'artist', 'song_number', 'category_id', 'duration', 
                         'video_type', 'video_id', 'video_url', 'embed_code', 'thumbnail_url',
                         'lyrics', 'language', 'bpm', 'key_signature', 'updated_at'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $songId;
        
        $stmt = $this->db->prepare("UPDATE songs SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }
    
    public function deleteSong($songId)
    {
        $stmt = $this->db->prepare("UPDATE songs SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$songId]);
    }
    
    public function getTotalSongs($category = null, $artist = null)
    {
        $conditions = ['deleted_at IS NULL'];
        $params = [];
        
        if ($category) {
            $conditions[] = 'category_id = ?';
            $params[] = $category;
        }
        
        if ($artist) {
            $conditions[] = 'artist LIKE ?';
            $params[] = '%' . $artist . '%';
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM songs {$whereClause}");
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return (int)$result['total'];
    }
    
    public function getRecentSongs($limit = 5)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name
            FROM songs s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.deleted_at IS NULL
            ORDER BY s.created_at DESC
            LIMIT {$limit}
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getSongsByCategory($categoryId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM songs 
            WHERE category_id = ? AND deleted_at IS NULL
            ORDER BY artist ASC, title ASC
        ");
        
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    
    public function incrementPlayCount($songId)
    {
        $stmt = $this->db->prepare("
            UPDATE songs 
            SET play_count = play_count + 1, last_played = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$songId]);
    }
}