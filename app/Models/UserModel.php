<?php
namespace App\Models;

use App\Config\Database;

class UserModel
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createUser($data)
    {
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password'],
            $data['role']
        ]);
        return $this->db->lastInsertId();
    }
    
    public function updateLastLogin($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function getUserStats($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT s.id) as total_scores,
                AVG(s.final_score) as average_score,
                MAX(s.final_score) as highest_score,
                COUNT(DISTINCT r.id) as rooms_joined
            FROM users u
            LEFT JOIN scores s ON u.id = s.user_id
            LEFT JOIN room_participants rp ON u.id = rp.user_id
            LEFT JOIN rooms r ON rp.room_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}