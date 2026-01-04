<?php
namespace App\Models;

use App\Config\Database;

class QueueModel
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getQueueByRoomId($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT q.*, s.title, s.artist, s.duration, s.thumbnail_url, s.video_type, s.video_id,
                   u.username as requested_by
            FROM queue q
            JOIN songs s ON q.song_id = s.id
            JOIN users u ON q.requested_by = u.id
            WHERE q.room_id = ? AND q.status IN ('waiting', 'playing')
            ORDER BY q.position ASC, q.created_at ASC
        ");
        
        $stmt->execute([$roomId]);
        return $stmt->fetchAll();
    }
    
    public function getNextSong($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT q.*, s.title, s.artist, s.duration, s.thumbnail_url, s.video_type, s.video_id
            FROM queue q
            JOIN songs s ON q.song_id = s.id
            WHERE q.room_id = ? AND q.status = 'waiting'
            ORDER BY q.position ASC, q.created_at ASC
            LIMIT 1
        ");
        
        $stmt->execute([$roomId]);
        return $stmt->fetch();
    }
    
    public function getCurrentSong($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT q.*, s.title, s.artist, s.duration, s.thumbnail_url, s.video_type, s.video_id
            FROM queue q
            JOIN songs s ON q.song_id = s.id
            WHERE q.room_id = ? AND q.status = 'playing'
            ORDER BY q.updated_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$roomId]);
        return $stmt->fetch();
    }
    
    public function addToQueue($roomId, $songId, $requestedBy, $priority = 'normal')
    {
        $position = $this->getNextPosition($roomId);
        
        $stmt = $this->db->prepare("
            INSERT INTO queue (room_id, song_id, requested_by, position, priority, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'waiting', NOW())
        ");
        
        $stmt->execute([$roomId, $songId, $requestedBy, $position, $priority]);
        
        $queueId = $this->db->lastInsertId();
        
        // Increment song play count
        $songModel = new SongModel();
        $songModel->incrementPlayCount($songId);
        
        return $position;
    }
    
    public function markAsPlaying($queueId)
    {
        $stmt = $this->db->prepare("
            UPDATE queue 
            SET status = 'playing', started_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$queueId]);
    }
    
    public function markAsCompleted($queueId)
    {
        $stmt = $this->db->prepare("
            UPDATE queue 
            SET status = 'completed', completed_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$queueId]);
    }
    
    public function removeFromQueue($queueId)
    {
        $stmt = $this->db->prepare("DELETE FROM queue WHERE id = ?");
        return $stmt->execute([$queueId]);
    }
    
    public function clearQueue($roomId)
    {
        $stmt = $this->db->prepare("DELETE FROM queue WHERE room_id = ?");
        return $stmt->execute([$roomId]);
    }
    
    public function reorderQueue($roomId, $newOrder)
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($newOrder as $index => $queueId) {
                $stmt = $this->db->prepare("
                    UPDATE queue 
                    SET position = ?, updated_at = NOW() 
                    WHERE id = ? AND room_id = ?
                ");
                $stmt->execute([$index + 1, $queueId, $roomId]);
            }
            
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Error reordering queue: " . $e->getMessage());
            return false;
        }
    }
    
    public function moveToTop($queueId)
    {
        $this->db->beginTransaction();
        
        try {
            $queueItem = $this->getQueueItem($queueId);
            if (!$queueItem) {
                throw new \Exception("Queue item not found");
            }
            
            // Shift all items down
            $stmt = $this->db->prepare("
                UPDATE queue 
                SET position = position + 1, updated_at = NOW() 
                WHERE room_id = ? AND position < ? AND status = 'waiting'
            ");
            $stmt->execute([$queueItem['room_id'], $queueItem['position']]);
            
            // Move target item to position 1
            $stmt = $this->db->prepare("
                UPDATE queue 
                SET position = 1, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$queueId]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error moving queue item to top: " . $e->getMessage());
            return false;
        }
    }
    
    public function moveToBottom($queueId)
    {
        $this->db->beginTransaction();
        
        try {
            $queueItem = $this->getQueueItem($queueId);
            if (!$queueItem) {
                throw new \Exception("Queue item not found");
            }
            
            $maxPosition = $this->getMaxPosition($queueItem['room_id']);
            
            // Shift items up
            $stmt = $this->db->prepare("
                UPDATE queue 
                SET position = position - 1, updated_at = NOW() 
                WHERE room_id = ? AND position > ? AND status = 'waiting'
            ");
            $stmt->execute([$queueItem['room_id'], $queueItem['position']]);
            
            // Move target item to bottom
            $stmt = $this->db->prepare("
                UPDATE queue 
                SET position = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$maxPosition, $queueId]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error moving queue item to bottom: " . $e->getMessage());
            return false;
        }
    }
    
    private function getQueueItem($queueId)
    {
        $stmt = $this->db->prepare("SELECT * FROM queue WHERE id = ?");
        $stmt->execute([$queueId]);
        return $stmt->fetch();
    }
    
    private function getNextPosition($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT MAX(position) as max_position 
            FROM queue 
            WHERE room_id = ? AND status = 'waiting'
        ");
        
        $stmt->execute([$roomId]);
        $result = $stmt->fetch();
        
        return ($result['max_position'] ?? 0) + 1;
    }
    
    private function getMaxPosition($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT MAX(position) as max_position 
            FROM queue 
            WHERE room_id = ? AND status = 'waiting'
        ");
        
        $stmt->execute([$roomId]);
        $result = $stmt->fetch();
        
        return ($result['max_position'] ?? 0) + 1;
    }
}