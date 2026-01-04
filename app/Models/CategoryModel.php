<?php
namespace App\Models;

use App\Config\Database;

class CategoryModel
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAllCategories()
    {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }
    
    public function getCategoryById($categoryId)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        return $stmt->fetch();
    }
    
    public function createCategory($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO categories (name, description, color, icon, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['color'] ?? '#0066cc',
            $data['icon'] ?? 'music'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateCategory($categoryId, $data)
    {
        $fields = [];
        $params = [];
        
        $allowedFields = ['name', 'description', 'color', 'icon', 'updated_at'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $categoryId;
        
        $stmt = $this->db->prepare("UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }
    
    public function deleteCategory($categoryId)
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$categoryId]);
    }
    
    public function getTotalCategories()
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM categories");
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    
    public function getCategoriesWithSongCounts()
    {
        $stmt = $this->db->query("
            SELECT c.*, COUNT(s.id) as song_count
            FROM categories c
            LEFT JOIN songs s ON c.id = s.category_id AND s.deleted_at IS NULL
            GROUP BY c.id, c.name, c.description, c.color, c.icon, c.created_at, c.updated_at
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll();
    }
}