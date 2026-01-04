<?php
/**
 * Gamification Class
 * 
 * Handles all gamification features including rewards, badges,
 * achievements, leaderboards, and avatar customization.
 */

class Gamification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Award rewards for activity completion
     * 
     * @param int $userId User ID
     * @param array $activity Activity data
     * @param int $starRating Star rating earned
     * @param int $timeSpent Time spent in seconds
     * @return array Rewards earned
     */
    public function awardActivityCompletion($userId, $activity, $starRating, $timeSpent) {
        $rewards = [
            'xp' => $activity['xp_reward'],
            'coins' => $activity['coin_reward'],
            'badges' => []
        ];
        
        // Bonus coins for 5-star rating
        if ($starRating >= 5) {
            $bonusCoins = round($activity['coin_reward'] * 0.5);
            $rewards['coins'] += $bonusCoins;
            $rewards['bonus_coins'] = $bonusCoins;
        }
        
        // Bonus XP for fast completion
        if ($timeSpent < 60) {
            $bonusXP = round($activity['xp_reward'] * 0.2);
            $rewards['xp'] += $bonusXP;
            $rewards['bonus_xp'] = $bonusXP;
        }
        
        // Award XP and coins
        $this->addXP($userId, $rewards['xp']);
        $this->addCoins($userId, $rewards['coins']);
        
        // Check for badges
        $badges = $this->checkAndAwardBadges($userId);
        $rewards['badges'] = $badges;
        
        return $rewards;
    }
    
    /**
     * Add XP to user
     * 
     * @param int $userId User ID
     * @param int $xp XP to add
     * @return bool
     */
    public function addXP($userId, $xp) {
        $this->db->beginTransaction();
        
        try {
            // Get current XP
            $query = "SELECT total_xp, current_level FROM users WHERE id = ?";
            $user = $this->db->queryOne($query, [$userId]);
            
            $newXP = $user['total_xp'] + $xp;
            $currentLevel = $user['current_level'];
            
            // Calculate new level
            $newLevel = $this->calculateLevel($newXP);
            
            // Check if leveled up
            $leveledUp = $newLevel > $currentLevel;
            
            // Update user XP and level
            $this->db->execute(
                "UPDATE users SET total_xp = ?, current_level = ?, updated_at = NOW() WHERE id = ?",
                [$newXP, $newLevel, $userId]
            );
            
            // Award level-up bonus
            if ($leveledUp) {
                $levelBonus = $newLevel * COINS_PER_LEVEL;
                $this->addCoins($userId, $levelBonus);
            }
            
            $this->db->commit();
            
            return [
                'xp_added' => $xp,
                'total_xp' => $newXP,
                'old_level' => $currentLevel,
                'new_level' => $newLevel,
                'leveled_up' => $leveledUp,
                'level_bonus' => $leveledUp ? $levelBonus : 0
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Add XP error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add coins to user
     * 
     * @param int $userId User ID
     * @param int $coins Coins to add
     * @return bool
     */
    public function addCoins($userId, $coins) {
        $query = "UPDATE users SET coins = coins + ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($query, [$coins, $userId]) !== false;
    }
    
    /**
     * Subtract coins from user
     * 
     * @param int $userId User ID
     * @param int $coins Coins to subtract
     * @return bool True if successful
     */
    public function subtractCoins($userId, $coins) {
        // Check if user has enough coins
        $query = "SELECT coins FROM users WHERE id = ?";
        $user = $this->db->queryOne($query, [$userId]);
        
        if (!$user || $user['coins'] < $coins) {
            return false;
        }
        
        return $this->db->execute(
            "UPDATE users SET coins = coins - ?, updated_at = NOW() WHERE id = ?",
            [$coins, $userId]
        ) !== false;
    }
    
    /**
     * Calculate level from total XP
     * 
     * @param int $totalXP Total XP
     * @return int Level
     */
    private function calculateLevel($totalXP) {
        return floor(sqrt($totalXP / XP_PER_LEVEL)) + 1;
    }
    
    /**
     * Check and award badges for user
     * 
     * @param int $userId User ID
     * @return array Badges awarded
     */
    public function checkAndAwardBadges($userId) {
        $awardedBadges = [];
        
        // Get all active badges
        $query = "SELECT * FROM badges WHERE is_active = 1";
        $badges = $this->db->query($query);
        
        // Get user's current badges
        $userBadges = $this->getUserBadges($userId);
        $userBadgeIds = array_column($userBadges, 'badge_id');
        
        foreach ($badges as $badge) {
            // Skip if already earned
            if (in_array($badge['id'], $userBadgeIds)) {
                continue;
            }
            
            // Check if user meets badge requirements
            if ($this->checkBadgeRequirements($userId, $badge)) {
                if ($this->awardBadge($userId, $badge['id'])) {
                    $awardedBadges[] = $badge;
                    
                    // Award badge rewards
                    if ($badge['coin_reward'] > 0) {
                        $this->addCoins($userId, $badge['coin_reward']);
                    }
                    if ($badge['xp_reward'] > 0) {
                        $this->addXP($userId, $badge['xp_reward']);
                    }
                }
            }
        }
        
        return $awardedBadges;
    }
    
    /**
     * Check if user meets badge requirements
     * 
     * @param int $userId User ID
     * @param array $badge Badge data
     * @return bool True if requirements met
     */
    private function checkBadgeRequirements($userId, $badge) {
        $requirements = json_decode($badge['requirement_data'], true);
        
        switch ($badge['badge_type']) {
            case 'completion':
                // Check total activities completed
                $query = "SELECT COUNT(*) as count FROM user_activity_progress 
                          WHERE user_id = ? AND completed = 1";
                $result = $this->db->queryOne($query, [$userId]);
                return ($result['count'] ?? 0) >= ($requirements['activities_completed'] ?? 0);
                
            case 'streak':
                // Check consecutive days
                $streak = $this->getStreak($userId);
                return $streak >= ($requirements['consecutive_days'] ?? 0);
                
            case 'score':
                // Check for perfect scores
                $query = "SELECT COUNT(*) as count FROM user_activity_progress 
                          WHERE user_id = ? AND completed = 1 AND best_score >= ?";
                $result = $this->db->queryOne($query, [
                    $userId,
                    $requirements['score_percentage'] ?? 100
                ]);
                return ($result['count'] ?? 0) > 0;
                
            case 'time':
                // Check for fast completions
                $query = "SELECT COUNT(*) as count FROM user_activity_sessions 
                          WHERE user_id = ? AND status = 'completed' AND time_spent <= ?";
                $result = $this->db->queryOne($query, [
                    $userId,
                    $requirements['max_seconds'] ?? 60
                ]);
                return ($result['count'] ?? 0) > 0;
                
            case 'special':
                // Special badge logic
                if (isset($requirements['subject'])) {
                    // Check activities in specific subject
                    $query = "SELECT COUNT(*) as count FROM user_activity_progress p
                              JOIN activities a ON p.activity_id = a.id
                              JOIN subjects s ON a.subject_id = s.id
                              WHERE p.user_id = ? AND p.completed = 1 AND s.slug = ?";
                    $result = $this->db->queryOne($query, [
                        $userId,
                        $requirements['subject']
                    ]);
                    return ($result['count'] ?? 0) >= ($requirements['activities_completed'] ?? 0);
                }
                
                if (isset($requirements['activity_type'])) {
                    // Check activities of specific type
                    $query = "SELECT COUNT(*) as count FROM user_activity_progress p
                              JOIN activities a ON p.activity_id = a.id
                              WHERE p.user_id = ? AND p.completed = 1 AND a.activity_type = ?";
                    $result = $this->db->queryOne($query, [
                        $userId,
                        $requirements['activity_type']
                    ]);
                    return ($result['count'] ?? 0) >= ($requirements['activities_completed'] ?? 0);
                }
                
                if (isset($requirements['level_reached'])) {
                    // Check user level
                    $query = "SELECT current_level FROM users WHERE id = ?";
                    $result = $this->db->queryOne($query, [$userId]);
                    return ($result['current_level'] ?? 0) >= $requirements['level_reached'];
                }
                
                if (isset($requirements['badges_earned'])) {
                    // Check total badges earned
                    $userBadges = $this->getUserBadges($userId);
                    return count($userBadges) >= $requirements['badges_earned'];
                }
                
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Award badge to user
     * 
     * @param int $userId User ID
     * @param int $badgeId Badge ID
     * @return bool
     */
    public function awardBadge($userId, $badgeId) {
        $query = "INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)";
        return $this->db->execute($query, [$userId, $badgeId]) !== false;
    }
    
    /**
     * Get user's badges
     * 
     * @param int $userId User ID
     * @return array User badges
     */
    public function getUserBadges($userId) {
        $query = "SELECT b.*, ub.earned_at 
                  FROM user_badges ub
                  JOIN badges b ON ub.badge_id = b.id
                  WHERE ub.user_id = ?
                  ORDER BY ub.earned_at DESC";
        
        return $this->db->query($query, [$userId]);
    }
    
    /**
     * Get all available badges
     * 
     * @return array All badges
     */
    public function getAllBadges() {
        $query = "SELECT * FROM badges WHERE is_active = 1 ORDER BY badge_type, name";
        return $this->db->query($query);
    }
    
    /**
     * Get user's consecutive day streak
     * 
     * @param int $userId User ID
     * @return int Streak days
     */
    public function getStreak($userId) {
        $streak = 0;
        $checkDate = date('Y-m-d');
        
        // Check if user has activity today
        $query = "SELECT COUNT(*) as count FROM user_activity_sessions 
                  WHERE user_id = ? AND DATE(started_at) = ?";
        $result = $this->db->queryOne($query, [$userId, $checkDate]);
        
        if ($result['count'] == 0) {
            // No activity today, check if yesterday
            $checkDate = date('Y-m-d', strtotime('-1 day'));
            $query = "SELECT COUNT(*) as count FROM user_activity_sessions 
                      WHERE user_id = ? AND DATE(started_at) = ?";
            $result = $this->db->queryOne($query, [$userId, $checkDate]);
            
            if ($result['count'] == 0) {
                return 0; // No streak
            }
        }
        
        // Count consecutive days
        while (true) {
            $query = "SELECT COUNT(*) as count FROM user_activity_sessions 
                      WHERE user_id = ? AND DATE(started_at) = ?";
            $result = $this->db->queryOne($query, [$userId, $checkDate]);
            
            if ($result['count'] > 0) {
                $streak++;
                $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Get leaderboard entries
     * 
     * @param string $type Leaderboard type
     * @param int $limit Number of entries
     * @return array Leaderboard entries
     */
    public function getLeaderboard($type = 'weekly_xp', $limit = 10) {
        // Calculate period based on type
        $periodStart = date('Y-m-d 00:00:00', strtotime('Monday this week'));
        $periodEnd = date('Y-m-d 23:59:59', strtotime('Sunday this week'));
        
        // Update/create leaderboard
        $this->updateLeaderboard($type, $periodStart, $periodEnd);
        
        // Get current leaderboard
        $query = "SELECT le.*, u.username, u.first_name, u.last_name, u.avatar_image
                  FROM leaderboard_entries le
                  JOIN users u ON le.user_id = u.id
                  JOIN leaderboards l ON le.leaderboard_id = l.id
                  WHERE l.leaderboard_type = ? 
                    AND l.period_start = ? 
                    AND l.period_end = ?
                  ORDER BY le.rank ASC
                  LIMIT ?";
        
        return $this->db->query($query, [$type, $periodStart, $periodEnd, $limit]);
    }
    
    /**
     * Get user's leaderboard position
     * 
     * @param int $userId User ID
     * @param string $type Leaderboard type
     * @return array|null User's entry or null
     */
    public function getUserLeaderboardPosition($userId, $type = 'weekly_xp') {
        $leaderboard = $this->getLeaderboard($type, 100);
        
        foreach ($leaderboard as $entry) {
            if ($entry['user_id'] == $userId) {
                return $entry;
            }
        }
        
        return null;
    }
    
    /**
     * Update leaderboard
     * 
     * @param string $type Leaderboard type
     * @param string $periodStart Period start
     * @param string $periodEnd Period end
     */
    private function updateLeaderboard($type, $periodStart, $periodEnd) {
        // Get or create leaderboard
        $query = "SELECT id FROM leaderboards 
                  WHERE leaderboard_type = ? AND period_start = ? AND period_end = ?";
        $leaderboard = $this->db->queryOne($query, [$type, $periodStart, $periodEnd]);
        
        if (!$leaderboard) {
            $this->db->execute(
                "INSERT INTO leaderboards (leaderboard_type, period_start, period_end) 
                 VALUES (?, ?, ?)",
                [$type, $periodStart, $periodEnd]
            );
            $leaderboardId = $this->db->lastInsertId();
        } else {
            $leaderboardId = $leaderboard['id'];
        }
        
        // Clear existing entries
        $this->db->execute("DELETE FROM leaderboard_entries WHERE leaderboard_id = ?", [$leaderboardId]);
        
        // Generate scores based on type
        $scoreColumn = '';
        $orderBy = 'score DESC';
        
        switch ($type) {
            case 'weekly_xp':
                $query = "SELECT user_id, SUM(xp_reward) as score
                          FROM (
                              SELECT s.user_id, a.xp_reward
                              FROM user_activity_sessions s
                              JOIN activities a ON s.activity_id = a.id
                              WHERE s.started_at BETWEEN ? AND ? AND s.status = 'completed'
                          ) as weekly_scores
                          GROUP BY user_id";
                break;
                
            case 'weekly_coins':
                $query = "SELECT user_id, SUM(coin_reward) as score
                          FROM (
                              SELECT s.user_id, a.coin_reward
                              FROM user_activity_sessions s
                              JOIN activities a ON s.activity_id = a.id
                              WHERE s.started_at BETWEEN ? AND ? AND s.status = 'completed'
                          ) as weekly_scores
                          GROUP BY user_id";
                break;
                
            case 'total_xp':
                $query = "SELECT id as user_id, total_xp as score FROM users WHERE role = 'student'";
                break;
                
            case 'total_coins':
                $query = "SELECT id as user_id, coins as score FROM users WHERE role = 'student'";
                break;
                
            case 'activities_completed':
                $query = "SELECT user_id, COUNT(*) as score
                          FROM user_activity_progress
                          WHERE completed = 1
                          GROUP BY user_id";
                break;
                
            default:
                return;
        }
        
        // Execute score query with params if needed
        if ($type == 'weekly_xp' || $type == 'weekly_coins') {
            $scores = $this->db->query($query, [$periodStart, $periodEnd]);
        } else {
            $scores = $this->db->query($query);
        }
        
        // Insert entries with ranks
        $rank = 1;
        foreach ($scores as $score) {
            $this->db->execute(
                "INSERT INTO leaderboard_entries (leaderboard_id, user_id, score, rank)
                 VALUES (?, ?, ?, ?)",
                [$leaderboardId, $score['user_id'], $score['score'], $rank]
            );
            $rank++;
        }
    }
    
    /**
     * Get available avatar items
     * 
     * @param string|null $itemType Filter by item type
     * @return array Avatar items
     */
    public function getAvatarItems($itemType = null) {
        $query = "SELECT * FROM avatar_items WHERE is_active = 1";
        $params = [];
        
        if ($itemType) {
            $query .= " AND item_type = ?";
            $params[] = $itemType;
        }
        
        $query .= " ORDER BY item_type, display_order, cost";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get user's owned avatar items
     * 
     * @param int $userId User ID
     * @param string|null $itemType Filter by item type
     * @return array Owned items
     */
    public function getUserAvatarItems($userId, $itemType = null) {
        $query = "SELECT ai.*, uoi.purchased_at
                  FROM user_owned_avatar_items uoi
                  JOIN avatar_items ai ON uoi.item_id = ai.id
                  WHERE uoi.user_id = ? AND ai.is_active = 1";
        $params = [$userId];
        
        if ($itemType) {
            $query .= " AND ai.item_type = ?";
            $params[] = $itemType;
        }
        
        $query .= " ORDER BY ai.item_type, ai.display_order";
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Get user's avatar settings
     * 
     * @param int $userId User ID
     * @return array Avatar settings
     */
    public function getUserAvatarSettings($userId) {
        $query = "SELECT s.*, 
                         (SELECT image FROM avatar_items WHERE id = s.hair_id) as hair_image,
                         (SELECT image FROM avatar_items WHERE id = s.face_id) as face_image,
                         (SELECT image FROM avatar_items WHERE id = s.clothing_id) as clothing_image,
                         (SELECT image FROM avatar_items WHERE id = s.accessory_id) as accessory_image,
                         (SELECT image FROM avatar_items WHERE id = s.background_id) as background_image
                  FROM user_avatar_settings s
                  WHERE s.user_id = ?";
        
        return $this->db->queryOne($query, [$userId]);
    }
    
    /**
     * Purchase avatar item
     * 
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @return array|false Result or false on failure
     */
    public function purchaseAvatarItem($userId, $itemId) {
        // Get item info
        $query = "SELECT * FROM avatar_items WHERE id = ? AND is_active = 1";
        $item = $this->db->queryOne($query, [$itemId]);
        
        if (!$item) {
            return false;
        }
        
        // Check level requirement
        $query = "SELECT current_level FROM users WHERE id = ?";
        $user = $this->db->queryOne($query, [$userId]);
        
        if (!$user || $user['current_level'] < $item['min_level_required']) {
            return ['success' => false, 'error' => 'level_requirement_not_met'];
        }
        
        // Check if already owned
        $query = "SELECT id FROM user_owned_avatar_items WHERE user_id = ? AND item_id = ?";
        $owned = $this->db->queryOne($query, [$userId, $itemId]);
        
        if ($owned) {
            return ['success' => false, 'error' => 'already_owned'];
        }
        
        // Check if user has enough coins
        if (!$this->subtractCoins($userId, $item['cost'])) {
            return ['success' => false, 'error' => 'insufficient_coins'];
        }
        
        // Add to owned items
        $query = "INSERT INTO user_owned_avatar_items (user_id, item_id) VALUES (?, ?)";
        $result = $this->db->execute($query, [$userId, $itemId]);
        
        if ($result) {
            return ['success' => true, 'item' => $item, 'cost' => $item['cost']];
        }
        
        return false;
    }
    
    /**
     * Update user avatar settings
     * 
     * @param int $userId User ID
     * @param array $settings Avatar settings
     * @return bool
     */
    public function updateAvatarSettings($userId, $settings) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['hair_id', 'face_id', 'clothing_id', 'accessory_id', 'background_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($settings[$field])) {
                // Verify user owns the item
                $query = "SELECT COUNT(*) as count FROM user_owned_avatar_items 
                          WHERE user_id = ? AND item_id = ?";
                $result = $this->db->queryOne($query, [$userId, $settings[$field]]);
                
                if ($result['count'] > 0) {
                    $fields[] = "{$field} = ?";
                    $params[] = $settings[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $userId;
        
        $query = "UPDATE user_avatar_settings SET " . implode(', ', $fields) . " WHERE user_id = ?";
        return $this->db->execute($query, $params) !== false;
    }
    
    /**
     * Get user's gamification summary
     * 
     * @param int $userId User ID
     * @return array Summary
     */
    public function getUserSummary($userId) {
        $query = "SELECT u.*, COUNT(ub.id) as badges_count, 
                         (SELECT COUNT(*) FROM user_activity_progress WHERE user_id = u.id AND completed = 1) as activities_completed
                  FROM users u
                  LEFT JOIN user_badges ub ON u.id = ub.user_id
                  WHERE u.id = ?
                  GROUP BY u.id";
        
        $user = $this->db->queryOne($query, [$userId]);
        
        if (!$user) {
            return null;
        }
        
        // Calculate progress to next level
        $progress = xpProgress($user['total_xp'], $user['current_level']);
        
        // Get recent badges
        $recentBadges = $this->getUserBadges($userId);
        $recentBadges = array_slice($recentBadges, 0, 5);
        
        // Get leaderboard position
        $leaderboardPos = $this->getUserLeaderboardPosition($userId);
        
        return [
            'user' => $user,
            'level_progress' => $progress,
            'badges_count' => $user['badges_count'],
            'activities_completed' => $user['activities_completed'],
            'recent_badges' => $recentBadges,
            'leaderboard_position' => $leaderboardPos,
            'streak' => $this->getStreak($userId)
        ];
    }
}
