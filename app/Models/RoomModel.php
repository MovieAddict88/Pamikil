<?php
namespace App\Models;

use App\Config\Database;

class RoomModel
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createRoom($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO rooms (name, code, created_by, is_private, max_participants, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['name'],
            $data['code'],
            $data['created_by'],
            $data['is_private'],
            $data['max_participants'],
            $data['status']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getRoomById($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.username as creator_username
            FROM rooms r
            JOIN users u ON r.created_by = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$roomId]);
        return $stmt->fetch();
    }
    
    public function getRoomByCode($roomCode)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.username as creator_username
            FROM rooms r
            JOIN users u ON r.created_by = u.id
            WHERE r.code = ?
        ");
        $stmt->execute([$roomCode]);
        return $stmt->fetch();
    }
    
    public function getActiveRooms()
    {
        $stmt = $this->db->query("
            SELECT r.*, u.username as creator_username, 
                   COUNT(rp.user_id) as participant_count
            FROM rooms r
            JOIN users u ON r.created_by = u.id
            LEFT JOIN room_participants rp ON r.id = rp.room_id
            WHERE r.status IN ('waiting', 'active')
            GROUP BY r.id
            ORDER BY r.created_at DESC
            LIMIT 20
        ");
        return $stmt->fetchAll();
    }
    
    public function getRecentActivity($userId)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.username as creator_username
            FROM rooms r
            JOIN users u ON r.created_by = u.id
            JOIN room_participants rp ON r.id = rp.room_id
            WHERE rp.user_id = ? AND rp.left_at IS NULL
            ORDER BY r.updated_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function addParticipant($roomId, $userId)
    {
        $stmt = $this->db->prepare("
            INSERT INTO room_participants (room_id, user_id, joined_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE joined_at = NOW(), left_at = NULL
        ");
        
        return $stmt->execute([$roomId, $userId]);
    }
    
    public function removeParticipant($roomId, $userId)
    {
        $stmt = $this->db->prepare("
            UPDATE room_participants 
            SET left_at = NOW() 
            WHERE room_id = ? AND user_id = ? AND left_at IS NULL
        ");
        
        return $stmt->execute([$roomId, $userId]);
    }
    
    public function isUserInRoom($roomId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM room_participants 
            WHERE room_id = ? AND user_id = ? AND left_at IS NULL
        ");
        
        $stmt->execute([$roomId, $userId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    public function getParticipantCount($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM room_participants 
            WHERE room_id = ? AND left_at IS NULL
        ");
        
        $stmt->execute([$roomId]);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }
    
    public function getParticipants($roomId)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.role, rp.joined_at
            FROM room_participants rp
            JOIN users u ON rp.user_id = u.id
            WHERE rp.room_id = ? AND rp.left_at IS NULL
            ORDER BY rp.joined_at ASC
        ");
        
        $stmt->execute([$roomId]);
        return $stmt->fetchAll();
    }
    
    public function updateRoomStatus($roomId, $status)
    {
        $stmt = $this->db->prepare("
            UPDATE rooms 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([$status, $roomId]);
    }
    
    public function deleteRoom($roomId)
    {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$roomId]);
    }
}