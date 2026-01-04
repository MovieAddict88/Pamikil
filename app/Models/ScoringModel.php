<?php
namespace App\Models;

use App\Config\Database;

class ScoringModel
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function startScoring($userId, $songId, $roomId = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO scores (user_id, song_id, room_id, start_time, created_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$userId, $songId, $roomId]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateScore($scoringId, $currentScore, $pitchAccuracy, $timingAccuracy, $volumeStability = null, $noteCompletion = null)
    {
        $stmt = $this->db->prepare("
            UPDATE scores 
            SET current_score = ?, 
                pitch_accuracy = ?, 
                timing_accuracy = ?, 
                volume_stability = ?, 
                note_completion = ?,
                last_update = NOW() 
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $currentScore,
            $pitchAccuracy,
            $timingAccuracy,
            $volumeStability,
            $noteCompletion,
            $scoringId
        ]);
    }
    
    public function endScoring($scoringId)
    {
        $stmt = $this->db->prepare("
            UPDATE scores 
            SET final_score = current_score, 
                end_time = NOW(), 
                status = 'completed',
                last_update = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([$scoringId]);
        
        $stmt = $this->db->prepare("SELECT final_score FROM scores WHERE id = ?");
        $stmt->execute([$scoringId]);
        $result = $stmt->fetch();
        
        return $result['final_score'] ?? 0;
    }
    
    public function getLeaderboard($timeframe = 'alltime', $limit = 10)
    {
        $timeCondition = '';
        
        switch ($timeframe) {
            case 'today':
                $timeCondition = 'AND s.created_at >= CURDATE()';
                break;
            case 'week':
                $timeCondition = 'AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                break;
            case 'month':
                $timeCondition = 'AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                break;
            case 'alltime':
            default:
                $timeCondition = '';
                break;
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                u.id as user_id,
                u.username,
                AVG(s.final_score) as average_score,
                MAX(s.final_score) as highest_score,
                COUNT(s.id) as total_scores,
                SUM(CASE WHEN s.final_score >= 90 THEN 1 ELSE 0 END) as gold_performances
            FROM scores s
            JOIN users u ON s.user_id = u.id
            WHERE s.status = 'completed' 
            AND s.final_score IS NOT NULL
            {$timeCondition}
            GROUP BY u.id, u.username
            HAVING COUNT(s.id) >= 3
            ORDER BY average_score DESC, total_scores DESC
            LIMIT {$limit}
        ");
        
        try {
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Database error in getLeaderboard: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUserScores($userId, $limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, so.title as song_title, so.artist as song_artist
            FROM scores s
            JOIN songs so ON s.song_id = so.id
            WHERE s.user_id = ? AND s.status = 'completed'
            ORDER BY s.created_at DESC
            LIMIT {$limit}
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getSongHighScores($songId, $limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, u.username
            FROM scores s
            JOIN users u ON s.user_id = u.id
            WHERE s.song_id = ? AND s.status = 'completed'
            ORDER BY s.final_score DESC
            LIMIT {$limit}
        ");
        
        $stmt->execute([$songId]);
        return $stmt->fetchAll();
    }
}