<?php
namespace App\Models;

use App\Config\Database;

class PlaylistModel
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAllPlaylists()
    {
        $stmt = $this->db->query("
            SELECT p.*, u.username as creator_username, 
                   COUNT(DISTINCT ps.song_id) as song_count
            FROM playlists p
            JOIN users u ON p.created_by = u.id
            LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id
            GROUP BY p.id, p.name, p.description, p.is_public, p.created_by, p.created_at, p.updated_at
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getPlaylistById($playlistId)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username as creator_username, 
                   COUNT(DISTINCT ps.song_id) as song_count
            FROM playlists p
            JOIN users u ON p.created_by = u.id
            LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id
            WHERE p.id = ?
            GROUP BY p.id, p.name, p.description, p.is_public, p.created_by, p.created_at, p.updated_at
        ");
        $stmt->execute([$playlistId]);
        return $stmt->fetch();
    }
    
    public function getPlaylistSongs($playlistId)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, ps.position, ps.added_at
            FROM songs s
            JOIN playlist_songs ps ON s.id = ps.song_id
            WHERE ps.playlist_id = ? AND s.deleted_at IS NULL
            ORDER BY ps.position ASC, ps.added_at ASC
        ");
        $stmt->execute([$playlistId]);
        return $stmt->fetchAll();
    }
    
    public function createPlaylist($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO playlists (name, description, is_public, created_by, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['is_public'] ?? 0,
            $data['created_by']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updatePlaylist($playlistId, $data)
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['name', 'description', 'is_public', 'updated_at'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $playlistId;
        
        $stmt = $this->db->prepare("UPDATE playlists SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }
    
    public function deletePlaylist($playlistId)
    {
        $this->db->beginTransaction();
        
        try {
            // Delete playlist songs first
            $stmt = $this->db->prepare("DELETE FROM playlist_songs WHERE playlist_id = ?");
            $stmt->execute([$playlistId]);
            
            // Delete playlist
            $stmt = $this->db->prepare("DELETE FROM playlists WHERE id = ?");
            $stmt->execute([$playlistId]);
            
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Error deleting playlist: " . $e->getMessage());
            return false;
        }
    }
    
    public function addSongToPlaylist($playlistId, $songId, $position = null)
    {
        if ($position === null) {
            $position = $this->getNextPosition($playlistId);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO playlist_songs (playlist_id, song_id, position, added_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        return $stmt->execute([$playlistId, $songId, $position]);
    }
    
    public function removeSongFromPlaylist($playlistId, $songId)
    {
        $stmt = $this->db->prepare("
            DELETE FROM playlist_songs 
            WHERE playlist_id = ? AND song_id = ?
        ");
        
        return $stmt->execute([$playlistId, $songId]);
    }
    
    public function reorderPlaylist($playlistId, $songIds)
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($songIds as $position => $songId) {
                $stmt = $this->db->prepare("
                    UPDATE playlist_songs 
                    SET position = ?, updated_at = NOW() 
                    WHERE playlist_id = ? AND song_id = ?
                ");
                $stmt->execute([$position + 1, $playlistId, $songId]);
            }
            
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Error reordering playlist: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPublicPlaylists()
    {
        $stmt = $this->db->query("
            SELECT p.*, u.username as creator_username, 
                   COUNT(DISTINCT ps.song_id) as song_count
            FROM playlists p
            JOIN users u ON p.created_by = u.id
            LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id
            WHERE p.is_public = 1
            GROUP BY p.id, p.name, p.description, p.is_public, p.created_by, p.created_at, p.updated_at
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getUserPlaylists($userId)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, COUNT(DISTINCT ps.song_id) as song_count
            FROM playlists p
            LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id
            WHERE p.created_by = ?
            GROUP BY p.id, p.name, p.description, p.is_public, p.created_by, p.created_at, p.updated_at
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getTotalPlaylists()
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM playlists");
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    
    private function getNextPosition($playlistId)
    {
        $stmt = $this->db->prepare("
            SELECT MAX(position) as max_position 
            FROM playlist_songs 
            WHERE playlist_id = ?
        ");
        $stmt->execute([$playlistId]);
        $result = $stmt->fetch();
        return ($result['max_position'] ?? 0) + 1;
    }
}