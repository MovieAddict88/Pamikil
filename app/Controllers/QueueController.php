<?php
namespace App\Controllers;

use App\Models\QueueModel;
use App\Models\RoomModel;

class QueueController extends BaseController
{
    private $queueModel;
    private $roomModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->queueModel = new QueueModel();
        $this->roomModel = new RoomModel();
    }
    
    public function getQueue($roomId)
    {
        $queue = $this->queueModel->getQueueByRoomId($roomId);
        $this->jsonResponse($queue);
    }
    
    public function addToQueue($roomId)
    {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['song_id'])) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        $position = $this->queueModel->addToQueue(
            $roomId,
            $data['song_id'],
            $_SESSION['user_id']
        );
        
        $this->broadcastQueueUpdate($roomId);
        
        $this->jsonResponse([
            'success' => true,
            'position' => $position
        ]);
    }
    
    public function nextSong($roomId)
    {
        $this->requireAuth();
        
        $room = $this->roomModel->getRoomById($roomId);
        
        if (!$room || $room['created_by'] != $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Unauthorized'], 403);
        }
        
        $nextSong = $this->queueModel->getNextSong($roomId);
        
        if ($nextSong) {
            $this->queueModel->markAsPlaying($nextSong['id']);
            $this->queueModel->removeFromQueue($nextSong['id']);
        }
        
        $this->broadcastQueueUpdate($roomId);
        
        $this->jsonResponse([
            'success' => true,
            'song' => $nextSong
        ]);
    }
    
    public function reorder($roomId)
    {
        $this->requireAuth();
        
        $room = $this->roomModel->getRoomById($roomId);
        
        if (!$room || $room['created_by'] != $_SESSION['user_id']) {
            $this->jsonResponse(['error' => 'Unauthorized'], 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['queue_order'])) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        $this->queueModel->reorderQueue($roomId, $data['queue_order']);
        
        $this->broadcastQueueUpdate($roomId);
        
        $this->jsonResponse(['success' => true]);
    }
    
    private function broadcastQueueUpdate($roomId)
    {
        // WebSocket broadcast would happen here
        // For now, we'll store in session for SSE implementation
        $_SESSION["queue_update_{$roomId}"] = time();
    }
}