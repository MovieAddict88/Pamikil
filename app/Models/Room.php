<?php
/**
 * Room Model
 */

class Room {
    private $db;
    private $table = 'rooms';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        return $this->db->fetch(
            "SELECT r.*, u.username as host_username, u.display_name as host_display_name
             FROM {$this->table} r
             LEFT JOIN users u ON r.host_id = u.id
             WHERE r.id = ? AND r.is_active = 1",
            [$id]
        );
    }
    
    public function findByCode($roomCode) {
        return $this->db->fetch(
            "SELECT r.*, u.username as host_username, u.display_name as host_display_name
             FROM {$this->table} r
             LEFT JOIN users u ON r.host_id = u.id
             WHERE r.room_code = ? AND r.is_active = 1",
            [$roomCode]
        );
    }
    
    public function create($data) {
        $required = ['name', 'room_code', 'host_id'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['settings'] = json_encode($data['settings'] ?? []);
        
        $roomId = $this->db->insert($this->table, $data);
        
        // Add host as participant
        if ($roomId) {
            $this->addParticipant($roomId, $data['host_id'], 'host');
        }
        
        return $roomId;
    }
    
    public function update($id, $data) {
        if (isset($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->update($id, ['is_active' => false]);
    }
    
    public function getPublic($limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT r.*, u.username as host_username, u.display_name as host_display_name,
                    COUNT(DISTINCT rp.user_id) as participant_count
             FROM {$this->table} r
             LEFT JOIN users u ON r.host_id = u.id
             LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.is_active = 1
             WHERE r.is_public = 1 AND r.is_active = 1
             GROUP BY r.id
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    public function getByHost($hostId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT r.*, 
                    COUNT(DISTINCT rp.user_id) as participant_count,
                    MAX(sq.requested_at) as last_activity
             FROM {$this->table} r
             LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.is_active = 1
             LEFT JOIN song_queue sq ON r.id = sq.room_id
             WHERE r.host_id = ? AND r.is_active = 1
             GROUP BY r.id
             ORDER BY last_activity DESC, r.created_at DESC
             LIMIT ?",
            [$hostId, $limit]
        );
    }
    
    public function addParticipant($roomId, $userId, $role = 'participant') {
        try {
            // Check if room exists and has space
            $room = $this->find($roomId);
            if (!$room) {
                throw new Exception("Room not found");
            }
            
            if ($room['current_participants'] >= $room['max_participants']) {
                throw new Exception("Room is full");
            }
            
            // Check if user is already in room
            $existing = $this->db->fetch(
                "SELECT id FROM room_participants WHERE room_id = ? AND user_id = ? AND is_active = 1",
                [$roomId, $userId]
            );
            
            if ($existing) {
                return $existing['id'];
            }
            
            // Add participant
            $participantId = $this->db->insert('room_participants', [
                'room_id' => $roomId,
                'user_id' => $userId,
                'role' => $role,
                'joined_at' => date('Y-m-d H:i:s'),
                'is_active' => true
            ]);
            
            // Update participant count
            $this->updateParticipantCount($roomId);
            
            return $participantId;
            
        } catch (Exception $e) {
            throw new Exception("Failed to join room: " . $e->getMessage());
        }
    }
    
    public function removeParticipant($roomId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Mark participant as inactive
            $this->db->update(
                'room_participants',
                ['left_at' => date('Y-m-d H:i:s'), 'is_active' => false],
                'room_id = ? AND user_id = ?',
                [$roomId, $userId]
            );
            
            // Update participant count
            $this->updateParticipantCount($roomId);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to leave room: " . $e->getMessage());
        }
    }
    
    public function getParticipants($roomId) {
        return $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar_url, 
                    rp.role, rp.joined_at, rp.left_at
             FROM room_participants rp
             INNER JOIN users u ON rp.user_id = u.id
             WHERE rp.room_id = ? AND rp.is_active = 1
             ORDER BY rp.joined_at ASC",
            [$roomId]
        );
    }
    
    public function getCurrentSong($roomId) {
        return $this->db->fetch(
            "SELECT sq.*, s.title, s.artist, s.duration, s.youtube_video_id, s.file_path,
                    u.username, u.display_name
             FROM song_queue sq
             INNER JOIN songs s ON sq.song_id = s.id
             INNER JOIN users u ON sq.user_id = u.id
             WHERE sq.room_id = ? AND sq.status = 'playing'
             ORDER BY sq.requested_at ASC
             LIMIT 1",
            [$roomId]
        );
    }
    
    public function getQueue($roomId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT sq.*, s.title, s.artist, s.duration, s.youtube_video_id, s.file_path,
                    u.username, u.display_name
             FROM song_queue sq
             INNER JOIN songs s ON sq.song_id = s.id
             INNER JOIN users u ON sq.user_id = u.id
             WHERE sq.room_id = ? AND sq.status = 'queued'
             ORDER BY sq.position ASC
             LIMIT ?",
            [$roomId, $limit]
        );
    }
    
    public function generateRoomCode() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        if ($this->findByCode($code)) {
            return $this->generateRoomCode(); // Recursively try again
        }
        
        return $code;
    }
    
    public function updateParticipantCount($roomId) {
        $count = $this->db->fetch(
            "SELECT COUNT(*) as count FROM room_participants 
             WHERE room_id = ? AND is_active = 1",
            [$roomId]
        );
        
        return $this->update($roomId, ['current_participants' => $count['count']]);
    }
    
    public function isUserInRoom($roomId, $userId) {
        $participant = $this->db->fetch(
            "SELECT id FROM room_participants 
             WHERE room_id = ? AND user_id = ? AND is_active = 1",
            [$roomId, $userId]
        );
        
        return (bool) $participant;
    }
    
    public function getUserRole($roomId, $userId) {
        $participant = $this->db->fetch(
            "SELECT role FROM room_participants 
             WHERE room_id = ? AND user_id = ? AND is_active = 1",
            [$roomId, $userId]
        );
        
        return $participant['role'] ?? null;
    }
    
    public function getRoomStats($roomId) {
        return $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT rp.user_id) as total_participants,
                COUNT(DISTINCT sq.id) as total_songs_played,
                AVG(sc.score) as avg_score,
                MAX(sc.score) as best_score,
                MAX(sq.played_at) as last_song_time
             FROM rooms r
             LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.is_active = 1
             LEFT JOIN song_queue sq ON r.id = sq.room_id AND sq.status = 'completed'
             LEFT JOIN scores sc ON sq.user_id = sc.user_id AND sq.song_id = sc.song_id
             WHERE r.id = ?",
            [$roomId]
        ) ?: [
            'total_participants' => 0,
            'total_songs_played' => 0,
            'avg_score' => 0,
            'best_score' => 0,
            'last_song_time' => null
        ];
    }
}