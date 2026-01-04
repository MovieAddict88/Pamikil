<?php
/**
 * Activity Class
 * 
 * Handles all activity-related operations including loading,
 * executing, and tracking progress for different activity types.
 */

class Activity {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get activity by ID
     * 
     * @param int $activityId Activity ID
     * @return array|false Activity data
     */
    public function getById($activityId) {
        $query = "SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.color as subject_color,
                         ag.name as age_group_name, ag.min_age, ag.max_age,
                         d.name as difficulty_name, d.level_number as difficulty_level,
                         u.first_name as creator_name
                  FROM activities a
                  LEFT JOIN subjects s ON a.subject_id = s.id
                  LEFT JOIN age_groups ag ON a.age_group_id = ag.id
                  LEFT JOIN difficulty_levels d ON a.difficulty_id = d.id
                  LEFT JOIN users u ON a.created_by = u.id
                  WHERE a.id = ? AND a.is_active = 1";
        
        $activity = $this->db->queryOne($query, [$activityId]);
        
        if ($activity) {
            $activity['content'] = json_decode($activity['content_data'], true);
        }
        
        return $activity;
    }
    
    /**
     * Get activity by slug
     * 
     * @param string $slug Activity slug
     * @return array|false Activity data
     */
    public function getBySlug($slug) {
        $query = "SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.color as subject_color,
                         ag.name as age_group_name, ag.min_age, ag.max_age,
                         d.name as difficulty_name, d.level_number as difficulty_level
                  FROM activities a
                  LEFT JOIN subjects s ON a.subject_id = s.id
                  LEFT JOIN age_groups ag ON a.age_group_id = ag.id
                  LEFT JOIN difficulty_levels d ON a.difficulty_id = d.id
                  WHERE a.slug = ? AND a.is_active = 1";
        
        $activity = $this->db->queryOne($query, [$slug]);
        
        if ($activity) {
            $activity['content'] = json_decode($activity['content_data'], true);
        }
        
        return $activity;
    }
    
    /**
     * Get activities with filters
     * 
     * @param array $filters Filter options
     * @param int $limit Number of results
     * @param int $offset Offset for pagination
     * @return array Activities
     */
    public function getActivities($filters = [], $limit = 20, $offset = 0) {
        $where = ['a.is_active = 1'];
        $params = [];
        
        // Filter by subject
        if (!empty($filters['subject'])) {
            $where[] = 'a.subject_id = ?';
            $params[] = $filters['subject'];
        }
        
        // Filter by age group
        if (!empty($filters['age_group'])) {
            $where[] = 'a.age_group_id = ?';
            $params[] = $filters['age_group'];
        }
        
        // Filter by difficulty
        if (!empty($filters['difficulty'])) {
            $where[] = 'a.difficulty_id = ?';
            $params[] = $filters['difficulty'];
        }
        
        // Filter by type
        if (!empty($filters['type'])) {
            $where[] = 'a.activity_type = ?';
            $params[] = $filters['type'];
        }
        
        // Filter by level requirement
        if (!empty($filters['user_level'])) {
            $where[] = 'a.min_level_required <= ?';
            $params[] = $filters['user_level'];
        }
        
        // Search by title/description
        if (!empty($filters['search'])) {
            $where[] = '(a.title LIKE ? OR a.description LIKE ?)';
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Featured only
        if (!empty($filters['featured'])) {
            $where[] = 'a.is_featured = 1';
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.color as subject_color,
                         ag.name as age_group_name, ag.min_age, ag.max_age,
                         d.name as difficulty_name, d.level_number as difficulty_level
                  FROM activities a
                  LEFT JOIN subjects s ON a.subject_id = s.id
                  LEFT JOIN age_groups ag ON a.age_group_id = ag.id
                  LEFT JOIN difficulty_levels d ON a.difficulty_id = d.id
                  WHERE {$whereClause}
                  ORDER BY a.is_featured DESC, a.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get activity types
     * 
     * @return array Activity types
     */
    public function getTypes() {
        return [
            'quiz' => 'Quiz',
            'story' => 'Interactive Story',
            'dragdrop' => 'Drag & Drop',
            'flashcard' => 'Flashcards',
            'puzzle' => 'Jigsaw Puzzle',
            'crossword' => 'Crossword',
            'matching' => 'Matching Game'
        ];
    }
    
    /**
     * Get featured activities
     * 
     * @param int $limit Number of activities
     * @return array Featured activities
     */
    public function getFeatured($limit = 6) {
        return $this->getActivities(['featured' => true], $limit);
    }
    
    /**
     * Get activities by subject
     * 
     * @param int $subjectId Subject ID
     * @param int $limit Number of activities
     * @return array Activities
     */
    public function getBySubject($subjectId, $limit = 12) {
        return $this->getActivities(['subject' => $subjectId], $limit);
    }
    
    /**
     * Get user progress for an activity
     * 
     * @param int $userId User ID
     * @param int $activityId Activity ID
     * @return array|false Progress data
     */
    public function getUserProgress($userId, $activityId) {
        $query = "SELECT * FROM user_activity_progress 
                  WHERE user_id = ? AND activity_id = ?";
        
        return $this->db->queryOne($query, [$userId, $activityId]);
    }
    
    /**
     * Start an activity session
     * 
     * @param int $userId User ID
     * @param int $activityId Activity ID
     * @return int|false Session ID
     */
    public function startSession($userId, $activityId) {
        $activity = $this->getById($activityId);
        if (!$activity) {
            return false;
        }
        
        // Initialize session data
        $sessionData = [
            'current_question' => 1,
            'answers' => [],
            'score' => 0,
            'time_spent' => 0
        ];
        
        $query = "INSERT INTO user_activity_sessions 
                  (user_id, activity_id, session_data, current_question, score, time_spent, status)
                  VALUES (?, ?, ?, ?, ?, ?, 'in_progress')";
        
        $result = $this->db->execute($query, [
            $userId,
            $activityId,
            json_encode($sessionData),
            1,
            0,
            0
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Get active session for user and activity
     * 
     * @param int $userId User ID
     * @param int $activityId Activity ID
     * @return array|false Session data
     */
    public function getActiveSession($userId, $activityId) {
        $query = "SELECT * FROM user_activity_sessions 
                  WHERE user_id = ? AND activity_id = ? AND status = 'in_progress'
                  ORDER BY started_at DESC
                  LIMIT 1";
        
        $session = $this->db->queryOne($query, [$userId, $activityId]);
        
        if ($session) {
            $session['data'] = json_decode($session['session_data'], true);
        }
        
        return $session;
    }
    
    /**
     * Save session progress (auto-save)
     * 
     * @param int $sessionId Session ID
     * @param array $sessionData Session data
     * @return bool
     */
    public function saveSession($sessionId, $sessionData) {
        $query = "UPDATE user_activity_sessions 
                  SET session_data = ?, current_question = ?, score = ?, time_spent = ?,
                      last_activity = NOW()
                  WHERE id = ?";
        
        return $this->db->execute($query, [
            json_encode($sessionData),
            $sessionData['current_question'] ?? 1,
            $sessionData['score'] ?? 0,
            $sessionData['time_spent'] ?? 0,
            $sessionId
        ]);
    }
    
    /**
     * Complete an activity session
     * 
     * @param int $sessionId Session ID
     * @param int $score Final score
     * @param int $timeSpent Time spent in seconds
     * @return array Completion result
     */
    public function completeSession($sessionId, $score, $timeSpent) {
        // Get session
        $query = "SELECT * FROM user_activity_sessions WHERE id = ?";
        $session = $this->db->queryOne($query, [$sessionId]);
        
        if (!$session) {
            return false;
        }
        
        // Get activity details
        $activity = $this->getById($session['activity_id']);
        if (!$activity) {
            return false;
        }
        
        // Calculate score percentage
        $totalQuestions = $this->getTotalQuestions($activity);
        $scorePercentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
        
        // Calculate star rating
        $starRating = $this->calculateStarRating($scorePercentage);
        
        // Check if passed
        $passed = $scorePercentage >= $activity['passing_score'];
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // Update session
            $this->db->execute(
                "UPDATE user_activity_sessions 
                 SET score = ?, time_spent = ?, completed_at = NOW(), status = 'completed'
                 WHERE id = ?",
                [$score, $timeSpent, $sessionId]
            );
            
            // Update or create progress record
            $existingProgress = $this->getUserProgress($session['user_id'], $activity['id']);
            
            if ($existingProgress) {
                // Update if this is better score or time
                $updateScore = !$existingProgress['best_score'] || $score > $existingProgress['best_score'];
                $updateTime = !$existingProgress['best_time'] || $timeSpent < $existingProgress['best_time'];
                
                $this->db->execute(
                    "UPDATE user_activity_progress 
                     SET attempts_count = attempts_count + 1,
                         best_score = IF(? OR best_score IS NULL, ?, best_score),
                         best_time = IF(? OR best_time IS NULL, ?, best_time),
                         completed = completed OR ?,
                         completed_at = IF(? OR completed_at IS NULL, NOW(), completed_at),
                         star_rating = IF(? OR star_rating IS NULL OR ? > star_rating, ?, star_rating),
                         last_attempt_at = NOW()
                     WHERE id = ?",
                    [$updateScore, $score, $updateTime, $timeSpent, $passed, $passed, 
                     $passed, $starRating, $starRating, $existingProgress['id']]
                );
            } else {
                // Create new progress record
                $this->db->execute(
                    "INSERT INTO user_activity_progress 
                     (user_id, activity_id, attempts_count, best_score, best_time, completed, 
                      last_attempt_at, completed_at, star_rating)
                     VALUES (?, ?, 1, ?, ?, ?, NOW(), ?, ?)",
                    [$session['user_id'], $activity['id'], $score, $timeSpent, $passed ? 1 : 0,
                     $passed ? 'NOW()' : null, $passed ? $starRating : null]
                );
            }
            
            // Award rewards if passed
            if ($passed) {
                $gamification = new Gamification();
                $gamification->awardActivityCompletion(
                    $session['user_id'],
                    $activity,
                    $starRating,
                    $timeSpent
                );
            }
            
            // Update time tracking
            $this->updateTimeTracking($session['user_id'], $timeSpent, $passed ? 1 : 0);
            
            $this->db->commit();
            
            return [
                'passed' => $passed,
                'score' => $score,
                'score_percentage' => round($scorePercentage, 2),
                'star_rating' => $starRating,
                'xp_earned' => $passed ? $activity['xp_reward'] : 0,
                'coins_earned' => $passed ? $activity['coin_reward'] : 0
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Session completion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total number of questions/items in an activity
     * 
     * @param array $activity Activity data
     * @return int Number of questions
     */
    private function getTotalQuestions($activity) {
        $content = $activity['content'];
        
        switch ($activity['activity_type']) {
            case 'quiz':
            case 'story':
                return count($content['questions'] ?? []);
            case 'flashcard':
                return count($content['cards'] ?? []);
            case 'dragdrop':
                return count($content['items'] ?? []);
            case 'matching':
                return count($content['pairs'] ?? []);
            case 'puzzle':
                return (int)($content['grid_size'] ?? 3) * (int)($content['grid_size'] ?? 3);
            case 'crossword':
                return count($content['clues']['across'] ?? []) + count($content['clues']['down'] ?? []);
            default:
                return 1;
        }
    }
    
    /**
     * Calculate star rating based on score
     * 
     * @param float $scorePercentage Score percentage
     * @return int Star rating (1-5)
     */
    private function calculateStarRating($scorePercentage) {
        if ($scorePercentage >= 100) return 5;
        if ($scorePercentage >= 90) return 5;
        if ($scorePercentage >= 75) return 4;
        if ($scorePercentage >= 60) return 3;
        if ($scorePercentage >= 40) return 2;
        return 1;
    }
    
    /**
     * Update time tracking for user
     * 
     * @param int $userId User ID
     * @param int $seconds Time in seconds
     * @param int $activitiesCompleted Number of activities completed
     */
    private function updateTimeTracking($userId, $seconds, $activitiesCompleted = 0) {
        $today = date('Y-m-d');
        $minutes = ceil($seconds / 60);
        
        $query = "INSERT INTO time_tracking (user_id, date, total_minutes, activities_completed)
                  VALUES (?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                      total_minutes = total_minutes + ?,
                      activities_completed = activities_completed + ?";
        
        $this->db->execute($query, [
            $userId,
            $today,
            $minutes,
            $activitiesCompleted,
            $minutes,
            $activitiesCompleted
        ]);
    }
    
    /**
     * Get user's completed activities
     * 
     * @param int $userId User ID
     * @param int $limit Number of activities
     * @return array Completed activities
     */
    public function getUserCompletedActivities($userId, $limit = 10) {
        $query = "SELECT p.*, a.title, a.activity_type, a.slug, s.name as subject_name, s.color
                  FROM user_activity_progress p
                  JOIN activities a ON p.activity_id = a.id
                  JOIN subjects s ON a.subject_id = s.id
                  WHERE p.user_id = ? AND p.completed = 1
                  ORDER BY p.completed_at DESC
                  LIMIT ?";
        
        return $this->db->query($query, [$userId, $limit]);
    }
    
    /**
     * Get user activity statistics
     * 
     * @param int $userId User ID
     * @return array Statistics
     */
    public function getUserStats($userId) {
        $stats = [
            'total_completed' => 0,
            'total_attempts' => 0,
            'avg_score' => 0,
            'best_streak' => 0,
            'by_subject' => []
        ];
        
        // Total completed
        $query = "SELECT COUNT(*) as count FROM user_activity_progress 
                  WHERE user_id = ? AND completed = 1";
        $result = $this->db->queryOne($query, [$userId]);
        $stats['total_completed'] = $result['count'] ?? 0;
        
        // Total attempts
        $query = "SELECT SUM(attempts_count) as total FROM user_activity_progress 
                  WHERE user_id = ?";
        $result = $this->db->queryOne($query, [$userId]);
        $stats['total_attempts'] = $result['total'] ?? 0;
        
        // Average score
        $query = "SELECT AVG(best_score) as avg FROM user_activity_progress 
                  WHERE user_id = ? AND best_score IS NOT NULL";
        $result = $this->db->queryOne($query, [$userId]);
        $stats['avg_score'] = round($result['avg'] ?? 0, 1);
        
        // By subject
        $query = "SELECT s.name, s.color, COUNT(p.id) as count
                  FROM user_activity_progress p
                  JOIN activities a ON p.activity_id = a.id
                  JOIN subjects s ON a.subject_id = s.id
                  WHERE p.user_id = ? AND p.completed = 1
                  GROUP BY s.id
                  ORDER BY count DESC";
        $stats['by_subject'] = $this->db->query($query, [$userId]);
        
        return $stats;
    }
    
    /**
     * Get recommended activities for user
     * 
     * @param int $userId User ID
     * @param int $limit Number of activities
     * @return array Recommended activities
     */
    public function getRecommended($userId, $limit = 6) {
        // Get user's age group and level
        $query = "SELECT age_group_id, current_level FROM users WHERE id = ?";
        $user = $this->db->queryOne($query, [$userId]);
        
        if (!$user) {
            return [];
        }
        
        // Get activities the user hasn't completed
        $filters = [
            'age_group' => $user['age_group_id'],
            'user_level' => $user['current_level']
        ];
        
        // Get activities not completed by user
        $query = "SELECT a.*, s.name as subject_name, s.slug as subject_slug, s.color
                  FROM activities a
                  JOIN subjects s ON a.subject_id = s.id
                  WHERE a.is_active = 1 
                    AND a.age_group_id = ?
                    AND a.min_level_required <= ?
                    AND a.id NOT IN (
                        SELECT activity_id FROM user_activity_progress 
                        WHERE user_id = ? AND completed = 1
                    )
                  ORDER BY a.is_featured DESC, RAND()
                  LIMIT ?";
        
        return $this->db->query($query, [
            $user['age_group_id'],
            $user['current_level'],
            $userId,
            $limit
        ]);
    }
}
