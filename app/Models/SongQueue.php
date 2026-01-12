<?php
/**
 * Song Queue Model
 */

class SongQueue {
    private $db;
    private $table = 'song_queue';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        return $this->db->fetch(
            "SELECT sq.*, s.title, s.artist, s.duration, s.youtube_video_id, s.file_path,
                    u.username, u.display_name, r.name as room_name
             FROM {$this->table} sq
             INNER JOIN songs s ON sq.song_id = s.id
             INNER JOIN users u ON sq.user_id = u.id
             INNER JOIN rooms r ON sq.room_id = r.id
             WHERE sq.id = ?",
            [$id]
        );
    }
    
    public function addToQueue($roomId, $songId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get next position in queue
            $position = $this->getNextPosition($roomId);
            
            // Add to queue
            $queueId = $this->db->insert($this->table, [
                'room_id' => $roomId,
                'song_id' => $songId,
                'user_id' => $userId,
                'requested_at' => date('Y-m-d H:i:s'),
                'status' => 'queued',
                'position' => $position
            ]);
            
            $this->db->commit();
            return $queueId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to add song to queue: " . $e->getMessage());
        }
    }
    
    public function getNextPosition($roomId) {
        $result = $this->db->fetch(
            "SELECT MAX(position) as max_position FROM {$this->table} 
             WHERE room_id = ? AND status = 'queued'",
            [$roomId]
        );
        
        return ($result['max_position'] ?? 0) + 1;
    }
    
    public function startNextSong($roomId) {
        try {
            $this->db->beginTransaction();
            
            // Get next song in queue
            $nextSong = $this->db->fetch(
                "SELECT * FROM {$this->table} 
                 WHERE room_id = ? AND status = 'queued' 
                 ORDER BY position ASC 
                 LIMIT 1",
                [$roomId]
            );
            
            if (!$nextSong) {
                $this->db->rollback();
                return null;
            }
            
            // Update current playing song status
            $this->db->update(
                $this->table,
                ['status' => 'completed', 'played_at' => date('Y-m-d H:i:s')],
                'room_id = ? AND status = "playing"',
                [$roomId]
            );
            
            // Start new song
            $this->db->update(
                $this->table,
                ['status' => 'playing', 'played_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$nextSong['id']]
            );
            
            // Reorder remaining queue
            $this->reorderQueue($roomId);
            
            $this->db->commit();
            return $nextSong['id'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to start next song: " . $e->getMessage());
        }
    }
    
    public function startSong($queueId) {
        try {
            $this->db->beginTransaction();
            
            // Get the queue item
            $queueItem = $this->db->fetch(
                "SELECT * FROM {$this->table} WHERE id = ?",
                [$queueId]
            );
            
            if (!$queueItem) {
                throw new Exception("Queue item not found");
            }
            
            // Update current playing song status
            $this->db->update(
                $this->table,
                ['status' => 'completed', 'played_at' => date('Y-m-d H:i:s')],
                'room_id = ? AND status = "playing"',
                [$queueItem['room_id']]
            );
            
            // Start the selected song
            $this->db->update(
                $this->table,
                ['status' => 'playing', 'played_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$queueId]
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to start song: " . $e->getMessage());
        }
    }
    
    public function completeSong($queueId) {
        return $this->db->update(
            $this->table,
            ['status' => 'completed', 'played_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$queueId]
        );
    }
    
    public function skipSong($queueId) {
        try {
            $this->db->beginTransaction();
            
            // Mark as skipped
            $this->db->update(
                $this->table,
                ['status' => 'skipped', 'played_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$queueId]
            );
            
            // Get the queue item to find room
            $queueItem = $this->db->fetch(
                "SELECT room_id FROM {$this->table} WHERE id = ?",
                [$queueId]
            );
            
            if ($queueItem) {
                // Start next song if available
                $this->startNextSong($queueItem['room_id']);
                
                // Reorder queue
                $this->reorderQueue($queueItem['room_id']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to skip song: " . $e->getMessage());
        }
    }
    
    public function removeFromQueue($queueId) {
        try {
            $this->db->beginTransaction();
            
            // Get the queue item
            $queueItem = $this->db->fetch(
                "SELECT * FROM {$this->table} WHERE id = ?",
                [$queueId]
            );
            
            if (!$queueItem) {
                throw new Exception("Queue item not found");
            }
            
            // Delete the queue item
            $this->db->delete($this->table, 'id = ?', [$queueId]);
            
            // Reorder remaining queue
            $this->reorderQueue($queueItem['room_id']);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to remove from queue: " . $e->getMessage());
        }
    }
    
    public function reorderQueue($roomId) {
        try {
            $this->db->beginTransaction();
            
            // Get all queued items ordered by current position
            $queuedItems = $this->db->fetchAll(
                "SELECT id FROM {$this->table} 
                 WHERE room_id = ? AND status = 'queued' 
                 ORDER BY position ASC",
                [$roomId]
            );
            
            // Update positions
            foreach ($queuedItems as $index => $item) {
                $this->db->update(
                    $this->table,
                    ['position' => $index + 1],
                    'id = ?',
                    [$item['id']]
                );
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to reorder queue: " . $e->getMessage());
        }
    }
    
    public function getRoomQueue($roomId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT sq.*, s.title, s.artist, s.duration, s.youtube_video_id, s.file_path,
                    u.username, u.display_name, u.avatar_url
             FROM {$this->table} sq
             INNER JOIN songs s ON sq.song_id = s.id
             INNER JOIN users u ON sq.user_id = u.id
             WHERE sq.room_id = ? 
             ORDER BY 
                 CASE sq.status
                     WHEN 'playing' THEN 1
                     WHEN 'queued' THEN 2
                     WHEN 'completed' THEN 3
                     WHEN 'skipped' THEN 4
                 END,
                 sq.position ASC
             LIMIT ?",
            [$roomId, $limit]
        );
    }
    
    public function getCurrentSong($roomId) {
        return $this->db->fetch(
            "SELECT sq.*, s.title, s.artist, s.duration, s.youtube_video_id, s.file_path,
                    u.username, u.display_name, u.avatar_url
             FROM {$this->table} sq
             INNER JOIN songs s ON sq.song_id = s.id
             INNER JOIN users u ON sq.user_id = u.id
             WHERE sq.room_id = ? AND sq.status = 'playing'
             ORDER BY sq.requested_at ASC
             LIMIT 1",
            [$roomId]
        );
    }
    
    public function getUserQueue($roomId, $userId) {
        return $this->db->fetchAll(
            "SELECT sq.*, s.title, s.artist, s.duration
             FROM {$this->table} sq
             INNER JOIN songs s ON sq.song_id = s.id
             WHERE sq.room_id = ? AND sq.user_id = ? AND sq.status IN ('queued', 'playing')
             ORDER BY sq.position ASC",
            [$roomId, $userId]
        );
    }
    
    public function isUserInQueue($roomId, $userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE room_id = ? AND user_id = ? AND status IN ('queued', 'playing')",
            [$roomId, $userId]
        );
        
        return $result['count'] > 0;
    }
    
    public function canUserAddSong($roomId, $userId, $config) {
        // Check if user already has songs in queue
        $userQueueCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE room_id = ? AND user_id = ? AND status = 'queued'",
            [$roomId, $userId]
        );
        
        $maxPerUser = $config['max_songs_per_user'] ?? 3;
        
        return $userQueueCount['count'] < $maxPerUser;
    }
    
    public function reorderQueueItem($roomId, $queueId, $newPosition) {
        return $this->db->update(
            $this->table,
            ['position' => $newPosition],
            'room_id = ? AND id = ? AND status = "queued"',
            [$roomId, $queueId]
        );
    }
    
    public function getQueueStats($roomId) {
        return $this->db->fetch(
            "SELECT 
                COUNT(CASE WHEN status = 'queued' THEN 1 END) as queued_count,
                COUNT(CASE WHEN status = 'playing' THEN 1 END) as playing_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = 'skipped' THEN 1 END) as skipped_count,
                COUNT(DISTINCT user_id) as unique_users
             FROM {$this->table} 
             WHERE room_id = ?",
            [$roomId]
        );
    }
}