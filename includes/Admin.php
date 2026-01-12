<?php
/**
 * Admin Class
 * 
 * Handles all administrative functions including user management,
 * content management, analytics, and system settings.
 */

class Admin {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    public function isAdmin() {
        $auth = new Auth();
        return $auth->isAdmin();
    }
    
    /**
     * Get system statistics
     * 
     * @return array Statistics
     */
    public function getStats() {
        $stats = [];
        
        // User statistics
        $stats['total_users'] = $this->db->queryOne("SELECT COUNT(*) as count FROM users")['count'];
        $stats['active_users'] = $this->db->queryOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
        $stats['students'] = $this->db->queryOne("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'];
        $stats['parents'] = $this->db->queryOne("SELECT COUNT(*) as count FROM users WHERE role = 'parent'")['count'];
        
        // Activity statistics
        $stats['total_activities'] = $this->db->queryOne("SELECT COUNT(*) as count FROM activities")['count'];
        $stats['active_activities'] = $this->db->queryOne("SELECT COUNT(*) as count FROM activities WHERE is_active = 1")['count'];
        $stats['featured_activities'] = $this->db->queryOne("SELECT COUNT(*) as count FROM activities WHERE is_featured = 1")['count'];
        
        // Completion statistics
        $stats['total_completions'] = $this->db->queryOne("SELECT COUNT(*) as count FROM user_activity_progress WHERE completed = 1")['count'];
        
        // Recent activity
        $stats['recent_registrations'] = $this->db->query(
            "SELECT id, username, email, role, created_at FROM users 
             ORDER BY created_at DESC LIMIT 5"
        );
        
        $stats['recent_completions'] = $this->db->query(
            "SELECT p.*, u.username, a.title 
             FROM user_activity_progress p
             JOIN users u ON p.user_id = u.id
             JOIN activities a ON p.activity_id = a.id
             WHERE p.completed = 1
             ORDER BY p.completed_at DESC LIMIT 5"
        );
        
        return $stats;
    }
    
    /**
     * Get users with filters
     * 
     * @param array $filters Filter options
     * @param int $limit Number of results
     * @param int $offset Offset for pagination
     * @return array Users
     */
    public function getUsers($filters = [], $limit = 20, $offset = 0) {
        $where = ['1=1'];
        $params = [];
        
        // Filter by role
        if (!empty($filters['role'])) {
            $where[] = 'role = ?';
            $params[] = $filters['role'];
        }
        
        // Filter by status
        if (isset($filters['is_active'])) {
            $where[] = 'is_active = ?';
            $params[] = $filters['is_active'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = '(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
            $search = "%{$filters['search']}%";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT u.*, 
                         (SELECT COUNT(*) FROM user_activity_progress WHERE user_id = u.id AND completed = 1) as activities_completed,
                         (SELECT COUNT(*) FROM user_badges WHERE user_id = u.id) as badges_count
                  FROM users u
                  WHERE {$whereClause}
                  ORDER BY u.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|false User data
     */
    public function getUser($userId) {
        $query = "SELECT * FROM users WHERE id = ?";
        return $this->db->queryOne($query, [$userId]);
    }
    
    /**
     * Create or update user
     * 
     * @param array $userData User data
     * @return int|false User ID or false on failure
     */
    public function saveUser($userData) {
        $isNew = empty($userData['id']);
        $auth = new Auth();
        
        $this->db->beginTransaction();
        
        try {
            if ($isNew) {
                // Create new user
                $userId = $auth->register($userData);
                
                if (!$userId) {
                    $this->db->rollback();
                    return false;
                }
                
                $action = 'user_created';
            } else {
                // Update existing user
                $userId = $userData['id'];
                
                // Get old values for logging
                $oldUser = $this->getUser($userId);
                
                // Build update query
                $updateFields = [];
                $updateParams = [];
                
                $allowedFields = ['username', 'email', 'first_name', 'last_name', 'role', 'is_active', 'parent_id', 'date_of_birth'];
                
                foreach ($allowedFields as $field) {
                    if (isset($userData[$field])) {
                        $updateFields[] = "{$field} = ?";
                        $updateParams[] = $userData[$field];
                    }
                }
                
                // Update password if provided
                if (!empty($userData['password'])) {
                    $updateFields[] = 'password_hash = ?';
                    $updateParams[] = password_hash($userData['password'], PASSWORD_DEFAULT);
                }
                
                if (empty($updateFields)) {
                    $this->db->rollback();
                    return false;
                }
                
                $updateFields[] = 'updated_at = NOW()';
                $updateParams[] = $userId;
                
                $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $result = $this->db->execute($query, $updateParams);
                
                if (!$result) {
                    $this->db->rollback();
                    return false;
                }
                
                $action = 'user_updated';
            }
            
            // Log action
            $this->logAction($action, 'user', $userId, $oldUser ?? null, $this->getUser($userId));
            
            $this->db->commit();
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Save user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function deleteUser($userId) {
        // Prevent deleting own account
        $auth = new Auth();
        if ($auth->getCurrentUser()['id'] == $userId) {
            return false;
        }
        
        // Get old values
        $oldUser = $this->getUser($userId);
        
        $result = $this->db->execute("DELETE FROM users WHERE id = ?", [$userId]);
        
        if ($result) {
            $this->logAction('user_deleted', 'user', $userId, $oldUser, null);
        }
        
        return $result !== false;
    }
    
    /**
     * Get activities for admin
     * 
     * @param array $filters Filter options
     * @param int $limit Number of results
     * @param int $offset Offset for pagination
     * @return array Activities
     */
    public function getActivities($filters = [], $limit = 20, $offset = 0) {
        $where = ['1=1'];
        $params = [];
        
        // Filter by subject
        if (!empty($filters['subject'])) {
            $where[] = 'a.subject_id = ?';
            $params[] = $filters['subject'];
        }
        
        // Filter by type
        if (!empty($filters['type'])) {
            $where[] = 'a.activity_type = ?';
            $params[] = $filters['type'];
        }
        
        // Filter by status
        if (isset($filters['is_active'])) {
            $where[] = 'a.is_active = ?';
            $params[] = $filters['is_active'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = '(a.title LIKE ? OR a.description LIKE ?)';
            $search = "%{$filters['search']}%";
            $params = array_merge($params, [$search, $search]);
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT a.*, s.name as subject_name, ag.name as age_group_name,
                         d.name as difficulty_name, u.username as creator_username,
                         (SELECT COUNT(*) FROM user_activity_progress WHERE activity_id = a.id AND completed = 1) as completion_count
                  FROM activities a
                  LEFT JOIN subjects s ON a.subject_id = s.id
                  LEFT JOIN age_groups ag ON a.age_group_id = ag.id
                  LEFT JOIN difficulty_levels d ON a.difficulty_id = d.id
                  LEFT JOIN users u ON a.created_by = u.id
                  WHERE {$whereClause}
                  ORDER BY a.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Create or update activity
     * 
     * @param array $activityData Activity data
     * @return int|false Activity ID or false on failure
     */
    public function saveActivity($activityData) {
        $isNew = empty($activityData['id']);
        
        $this->db->beginTransaction();
        
        try {
            // Get admin user ID
            $auth = new Auth();
            $adminId = $auth->getCurrentUser()['id'];
            
            if ($isNew) {
                // Create new activity
                $query = "INSERT INTO activities (subject_id, age_group_id, difficulty_id, activity_type, 
                                                  title, slug, description, instructions, content_data, 
                                                  time_limit, passing_score, xp_reward, coin_reward, 
                                                  attempts_allowed, min_level_required, is_active, 
                                                  is_featured, created_by)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params = [
                    $activityData['subject_id'],
                    $activityData['age_group_id'],
                    $activityData['difficulty_id'],
                    $activityData['activity_type'],
                    $activityData['title'],
                    $this->generateSlug($activityData['title']),
                    $activityData['description'] ?? null,
                    $activityData['instructions'] ?? null,
                    json_encode($activityData['content']),
                    $activityData['time_limit'] ?? null,
                    $activityData['passing_score'] ?? 70,
                    $activityData['xp_reward'] ?? 10,
                    $activityData['coin_reward'] ?? 5,
                    $activityData['attempts_allowed'] ?? null,
                    $activityData['min_level_required'] ?? 1,
                    $activityData['is_active'] ?? 1,
                    $activityData['is_featured'] ?? 0,
                    $adminId
                ];
                
                $result = $this->db->execute($query, $params);
                
                if (!$result) {
                    $this->db->rollback();
                    return false;
                }
                
                $activityId = $this->db->lastInsertId();
                $action = 'activity_created';
                
            } else {
                // Update existing activity
                $activityId = $activityData['id'];
                $oldActivity = $this->getActivity($activityId);
                
                $updateFields = [];
                $updateParams = [];
                
                $allowedFields = ['subject_id', 'age_group_id', 'difficulty_id', 'activity_type',
                                   'title', 'description', 'instructions', 'time_limit', 'passing_score',
                                   'xp_reward', 'coin_reward', 'attempts_allowed', 'min_level_required',
                                   'is_active', 'is_featured'];
                
                foreach ($allowedFields as $field) {
                    if (isset($activityData[$field])) {
                        $updateFields[] = "{$field} = ?";
                        $updateParams[] = $activityData[$field];
                    }
                }
                
                // Update content if provided
                if (isset($activityData['content'])) {
                    $updateFields[] = 'content_data = ?';
                    $updateParams[] = json_encode($activityData['content']);
                }
                
                // Update slug if title changed
                if (isset($activityData['title'])) {
                    $updateFields[] = 'slug = ?';
                    $updateParams[] = $this->generateSlug($activityData['title']);
                }
                
                $updateFields[] = 'updated_at = NOW()';
                $updateParams[] = $activityId;
                
                $query = "UPDATE activities SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $result = $this->db->execute($query, $updateParams);
                
                if (!$result) {
                    $this->db->rollback();
                    return false;
                }
                
                $action = 'activity_updated';
            }
            
            // Update tags if provided
            if (!empty($activityData['tags'])) {
                $this->updateActivityTags($activityId, $activityData['tags']);
            }
            
            // Log action
            $this->logAction($action, 'activity', $activityId, $oldActivity ?? null, $this->getActivity($activityId));
            
            $this->db->commit();
            return $activityId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Save activity error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get activity details
     * 
     * @param int $activityId Activity ID
     * @return array|false Activity data
     */
    public function getActivity($activityId) {
        $query = "SELECT a.*, s.name as subject_name, ag.name as age_group_name,
                         d.name as difficulty_name
                  FROM activities a
                  LEFT JOIN subjects s ON a.subject_id = s.id
                  LEFT JOIN age_groups ag ON a.age_group_id = ag.id
                  LEFT JOIN difficulty_levels d ON a.difficulty_id = d.id
                  WHERE a.id = ?";
        return $this->db->queryOne($query, [$activityId]);
    }
    
    /**
     * Delete activity
     * 
     * @param int $activityId Activity ID
     * @return bool
     */
    public function deleteActivity($activityId) {
        $oldActivity = $this->getActivity($activityId);
        
        $result = $this->db->execute("DELETE FROM activities WHERE id = ?", [$activityId]);
        
        if ($result) {
            $this->logAction('activity_deleted', 'activity', $activityId, $oldActivity, null);
        }
        
        return $result !== false;
    }
    
    /**
     * Update activity tags
     * 
     * @param int $activityId Activity ID
     * @param array $tagIds Tag IDs
     */
    private function updateActivityTags($activityId, $tagIds) {
        // Remove existing tags
        $this->db->execute(
            "DELETE FROM activity_tag_relations WHERE activity_id = ?",
            [$activityId]
        );
        
        // Add new tags
        foreach ($tagIds as $tagId) {
            $this->db->execute(
                "INSERT INTO activity_tag_relations (activity_id, tag_id) VALUES (?, ?)",
                [$activityId, $tagId]
            );
        }
    }
    
    /**
     * Get system settings
     * 
     * @return array Settings
     */
    public function getSettings() {
        $query = "SELECT * FROM system_settings ORDER BY setting_key";
        $settings = $this->db->query($query);
        
        $result = [];
        foreach ($settings as $setting) {
            $value = $setting['setting_value'];
            
            // Convert based on type
            switch ($setting['setting_type']) {
                case 'number':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $result[$setting['setting_key']] = [
                'value' => $value,
                'type' => $setting['setting_type'],
                'description' => $setting['description']
            ];
        }
        
        return $result;
    }
    
    /**
     * Update system setting
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    public function updateSetting($key, $value) {
        $query = "SELECT * FROM system_settings WHERE setting_key = ?";
        $setting = $this->db->queryOne($query, [$key]);
        
        if (!$setting) {
            return false;
        }
        
        $auth = new Auth();
        $adminId = $auth->getCurrentUser()['id'];
        $oldValue = $setting['setting_value'];
        
        // Convert value based on type
        switch ($setting['setting_type']) {
            case 'number':
                $value = (int)$value;
                break;
            case 'boolean':
                $value = (bool)$value ? '1' : '0';
                break;
            case 'json':
                $value = json_encode($value);
                break;
        }
        
        $query = "UPDATE system_settings 
                  SET setting_value = ?, updated_by = ?, updated_at = NOW()
                  WHERE setting_key = ?";
        
        $result = $this->db->execute($query, [$value, $adminId, $key]);
        
        if ($result) {
            $this->logAction('setting_updated', 'system_setting', $key, 
                           ['setting_key' => $key, 'setting_value' => $oldValue], 
                           ['setting_key' => $key, 'setting_value' => $value]);
        }
        
        return $result !== false;
    }
    
    /**
     * Get recent activity logs
     * 
     * @param int $limit Number of logs
     * @return array Logs
     */
    public function getLogs($limit = 50) {
        $query = "SELECT l.*, u.username as admin_username
                  FROM admin_logs l
                  JOIN users u ON l.admin_id = u.id
                  ORDER BY l.created_at DESC
                  LIMIT ?";
        
        return $this->db->query($query, [$limit]);
    }
    
    /**
     * Log admin action
     * 
     * @param string $action Action performed
     * @param string $entityType Entity type
     * @param int|null $entityId Entity ID
     * @param array|null $oldValues Old values
     * @param array|null $newValues New values
     */
    private function logAction($action, $entityType, $entityId = null, $oldValues = null, $newValues = null) {
        $auth = new Auth();
        $admin = $auth->getCurrentUser();
        
        if (!$admin) {
            return;
        }
        
        $query = "INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, 
                                          old_values, new_values, ip_address, user_agent)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($query, [
            $admin['id'],
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            Security::getIP(),
            Security::getUserAgent()
        ]);
    }
    
    /**
     * Generate unique slug
     * 
     * @param string $title Title
     * @return string Unique slug
     */
    private function generateSlug($title) {
        $slug = slugify($title);
        $counter = 1;
        $originalSlug = $slug;
        
        while (true) {
            $query = "SELECT id FROM activities WHERE slug = ?";
            $exists = $this->db->queryOne($query, [$slug]);
            
            if (!$exists) {
                return $slug;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
    
    /**
     * Get analytics data
     * 
     * @param string $period Period (week, month, year)
     * @return array Analytics
     */
    public function getAnalytics($period = 'week') {
        $analytics = [];
        
        $dateRanges = [
            'week' => 'NOW() - INTERVAL 7 DAY',
            'month' => 'NOW() - INTERVAL 30 DAY',
            'year' => 'NOW() - INTERVAL 365 DAY'
        ];
        
        $dateRange = $dateRanges[$period] ?? $dateRanges['week'];
        
        // New users
        $analytics['new_users'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM users WHERE created_at >= {$dateRange}"
        )['count'];
        
        // Activity completions
        $analytics['completions'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM user_activity_progress 
             WHERE completed = 1 AND completed_at >= {$dateRange}"
        )['count'];
        
        // Active users
        $analytics['active_users'] = $this->db->queryOne(
            "SELECT COUNT(DISTINCT user_id) as count FROM user_activity_sessions 
             WHERE started_at >= {$dateRange}"
        )['count'];
        
        // Average score
        $analytics['avg_score'] = $this->db->queryOne(
            "SELECT AVG(best_score) as avg FROM user_activity_progress 
             WHERE completed = 1 AND completed_at >= {$dateRange}"
        )['avg'] ?? 0;
        
        // Completions by subject
        $analytics['completions_by_subject'] = $this->db->query(
            "SELECT s.name, COUNT(p.id) as count
             FROM user_activity_progress p
             JOIN activities a ON p.activity_id = a.id
             JOIN subjects s ON a.subject_id = s.id
             WHERE p.completed = 1 AND p.completed_at >= {$dateRange}
             GROUP BY s.id
             ORDER BY count DESC"
        );
        
        // Completions by day
        $analytics['completions_by_day'] = $this->db->query(
            "SELECT DATE(completed_at) as date, COUNT(*) as count
             FROM user_activity_progress
             WHERE completed = 1 AND completed_at >= {$dateRange}
             GROUP BY DATE(completed_at)
             ORDER BY date ASC"
        );
        
        return $analytics;
    }
}
