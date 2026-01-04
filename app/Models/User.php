<?php
/**
 * User Model
 */

class User {
    private $db;
    private $table = 'users';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND is_active = 1",
            [$id]
        );
    }
    
    public function findByUsername($username) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE username = ? AND is_active = 1",
            [$username]
        );
    }
    
    public function findByEmail($email) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE email = ? AND is_active = 1",
            [$email]
        );
    }
    
    public function create($data) {
        $required = ['username', 'email', 'password_hash', 'display_name'];
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
        // Soft delete - just mark as inactive
        return $this->update($id, ['is_active' => false]);
    }
    
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $this->db->update($this->table, 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );
            return $user;
        }
        
        return false;
    }
    
    public function getAll($limit = 50, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT id, username, email, display_name, avatar_url, is_admin, created_at 
             FROM {$this->table} 
             WHERE is_active = 1 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    public function search($query, $limit = 20) {
        return $this->db->fetchAll(
            "SELECT id, username, email, display_name, avatar_url, is_admin 
             FROM {$this->table} 
             WHERE (username LIKE ? OR display_name LIKE ? OR email LIKE ?) 
             AND is_active = 1 
             ORDER BY 
                 CASE 
                     WHEN username LIKE ? THEN 1 
                     WHEN display_name LIKE ? THEN 2 
                     ELSE 3 
                 END
             LIMIT ?",
            [
                "%{$query}%", "%{$query}%", "%{$query}%",
                "{$query}%", "{$query}%",
                $limit
            ]
        );
    }
    
    public function isAdmin($userId) {
        $user = $this->find($userId);
        return $user && $user['is_admin'];
    }
    
    public function createPasswordHash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public function validatePassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function getUserStats($userId) {
        $db = $this->db;
        
        $stats = $db->fetch(
            "SELECT 
                COUNT(DISTINCT r.id) as rooms_joined,
                COUNT(DISTINCT sq.id) as songs_sung,
                AVG(s.score) as avg_score,
                MAX(s.score) as best_score
             FROM users u
             LEFT JOIN room_participants rp ON u.id = rp.user_id AND rp.is_active = 1
             LEFT JOIN rooms r ON rp.room_id = r.id
             LEFT JOIN song_queue sq ON u.id = sq.user_id
             LEFT JOIN scores s ON u.id = s.user_id AND s.song_id = sq.song_id
             WHERE u.id = ?",
            [$userId]
        );
        
        return $stats ?: [
            'rooms_joined' => 0,
            'songs_sung' => 0,
            'avg_score' => 0,
            'best_score' => 0
        ];
    }
}