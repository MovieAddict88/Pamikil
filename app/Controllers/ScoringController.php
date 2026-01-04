<?php
namespace App\Controllers;

use App\Models\ScoringModel;

class ScoringController extends BaseController
{
    private $scoringModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->scoringModel = new ScoringModel();
    }
    
    public function startScoring()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['song_id'])) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        $scoringId = $this->scoringModel->startScoring(
            $_SESSION['user_id'],
            $data['song_id'],
            $data['room_id'] ?? null
        );
        
        $this->jsonResponse([
            'success' => true,
            'scoring_id' => $scoringId
        ]);
    }
    
    public function updateScore()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['scoring_id']) || !isset($data['pitch_accuracy']) || !isset($data['timing_accuracy'])) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        $calculatedScore = $this->calculateScore(
            $data['pitch_accuracy'],
            $data['timing_accuracy'],
            $data['volume_stability'] ?? 0.8,
            $data['note_completion'] ?? 0.9
        );
        
        $this->scoringModel->updateScore(
            $data['scoring_id'],
            $calculatedScore,
            $data['pitch_accuracy'],
            $data['timing_accuracy'],
            $data['volume_stability'] ?? null,
            $data['note_completion'] ?? null
        );
        
        $this->jsonResponse([
            'success' => true,
            'score' => $calculatedScore,
            'current_score' => $calculatedScore
        ]);
    }
    
    public function endScoring()
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['scoring_id'])) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        $finalScore = $this->scoringModel->endScoring($data['scoring_id']);
        
        if (isset($data['room_id'])) {
            $this->broadcastScoringUpdate($data['room_id'], [
                'user_id' => $_SESSION['user_id'],
                'score' => $finalScore
            ]);
        }
        
        $this->jsonResponse([
            'success' => true,
            'final_score' => $finalScore
        ]);
    }
    
    public function getLeaderboard()
    {
        $timeframe = $_GET['timeframe'] ?? 'alltime';
        $limit = min((int)($_GET['limit'] ?? 10), 50);
        
        $leaderboard = $this->scoringModel->getLeaderboard($timeframe, $limit);
        
        $this->jsonResponse($leaderboard);
    }
    
    private function calculateScore($pitchAccuracy, $timingAccuracy, $volumeStability, $noteCompletion)
    {
        $pitchWeight = 0.4;
        $timingWeight = 0.3;
        $volumeWeight = 0.15;
        $completionWeight = 0.15;
        
        $baseScore = (
            $pitchAccuracy * $pitchWeight +
            $timingAccuracy * $timingWeight +
            $volumeStability * $volumeWeight +
            $noteCompletion * $completionWeight
        ) * 100;
        
        $difficultyBonus = $this->getDifficultyBonus($pitchAccuracy, $timingAccuracy);
        $consistencyBonus = $this->getConsistencyBonus($pitchAccuracy, $timingAccuracy);
        
        $finalScore = $baseScore + $difficultyBonus + $consistencyBonus;
        
        return min(100, max(0, $finalScore));
    }
    
    private function getDifficultyBonus($pitchAccuracy, $timingAccuracy)
    {
        $avgAccuracy = ($pitchAccuracy + $timingAccuracy) / 2;
        if ($avgAccuracy >= 0.95) return 5;
        if ($avgAccuracy >= 0.9) return 3;
        if ($avgAccuracy >= 0.85) return 1;
        return 0;
    }
    
    private function getConsistencyBonus($pitchAccuracy, $timingAccuracy)
    {
        $consistency = 1 - abs($pitchAccuracy - $timingAccuracy);
        return $consistency * 2;
    }
    
    private function broadcastScoringUpdate($roomId, $data)
    {
        // WebSocket broadcast would happen here
        // For now, we'll return the data for potential SSE implementation
        return $data;
    }
}