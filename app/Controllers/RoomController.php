<?php
namespace App\Controllers;

use App\Models\RoomModel;
use App\Models\QueueModel;

class RoomController extends BaseController
{
    private $roomModel;
    private $queueModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->roomModel = new RoomModel();
        $this->queueModel = new QueueModel();
    }
    
    public function index()
    {
        if (!$this->isAuth()) {
            $this->redirect('/login');
        }
        
        $rooms = $this->roomModel->getActiveRooms();
        $recentActivity = $this->roomModel->getRecentActivity($_SESSION['user_id']);
        
        $this->render('rooms/index', [
            'rooms' => $rooms,
            'recent_activity' => $recentActivity
        ]);
    }
    
    public function soloMode()
    {
        if (!$this->isAuth()) {
            $this->redirect('/login');
        }
        
        $roomCode = $this->generateRoomCode();
        
        $roomId = $this->roomModel->createRoom([
            'name' => 'Solo Session - ' . $_SESSION['username'],
            'code' => $roomCode,
            'created_by' => $_SESSION['user_id'],
            'is_private' => 1,
            'max_participants' => 1,
            'status' => 'active'
        ]);
        
        if ($roomId) {
            $this->roomModel->addParticipant($roomId, $_SESSION['user_id']);
            $this->redirect('/room/' . $roomCode);
        } else {
            $this->setFlash('error', 'Failed to create solo room');
            $this->redirect('/');
        }
    }
    
    public function create()
    {
        if (!$this->isAuth()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['name'])) {
            $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        
        $roomCode = $this->generateRoomCode();
        
        $roomData = [
            'name' => $data['name'],
            'code' => $roomCode,
            'created_by' => $_SESSION['user_id'],
            'is_private' => $data['is_private'] ?? 0,
            'max_participants' => $data['max_participants'] ?? 10,
            'status' => 'waiting'
        ];
        
        $roomId = $this->roomModel->createRoom($roomData);
        
        if ($roomId) {
            $this->roomModel->addParticipant($roomId, $_SESSION['user_id']);
            $this->jsonResponse([
                'success' => true,
                'room_code' => $roomCode
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to create room'], 500);
        }
    }
    
    public function join($roomCode)
    {
        if (!$this->isAuth()) {
            $this->redirect('/login');
        }
        
        $room = $this->roomModel->getRoomByCode($roomCode);
        
        if (!$room) {
            $this->setFlash('error', 'Room not found');
            $this->redirect('/');
        }
        
        if ($this->roomModel->isUserInRoom($room['id'], $_SESSION['user_id'])) {
            $this->redirect('/room/' . $roomCode);
        }
        
        $participantCount = $this->roomModel->getParticipantCount($room['id']);
        
        if ($participantCount >= $room['max_participants']) {
            $this->setFlash('error', 'Room is full');
            $this->redirect('/');
        }
        
        $this->roomModel->addParticipant($room['id'], $_SESSION['user_id']);
        
        $this->broadcastRoomState($room['id'], 'user_joined');
        
        $this->redirect('/room/' . $roomCode);
    }
    
    public function show($roomCode)
    {
        if (!$this->isAuth()) {
            $this->redirect('/login');
        }
        
        $room = $this->roomModel->getRoomByCode($roomCode);
        
        if (!$room) {
            $this->setFlash('error', 'Room not found');
            $this->redirect('/');
        }
        
        if (!$this->roomModel->isUserInRoom($room['id'], $_SESSION['user_id'])) {
            $this->setFlash('error', 'You are not a member of this room');
            $this->redirect('/');
        }
        
        $participants = $this->roomModel->getParticipants($room['id']);
        $queue = $this->queueModel->getQueueByRoomId($room['id']);
        $currentSong = $this->queueModel->getCurrentSong($room['id']);
        
        $this->render('rooms/room', [
            'room' => $room,
            'participants' => $participants,
            'queue' => $queue,
            'current_song' => $currentSong,
            'is_host' => $room['created_by'] == $_SESSION['user_id']
        ]);
    }
    
    private function broadcastRoomState($roomId, $state)
    {
        $message = [
            'room_id' => $roomId,
            'state' => $state,
            'timestamp' => time()
        ];
        
        // WebSocket broadcast would happen here
        // For now, store in session for SSE
        $_SESSION["room_update_{$roomId}"] = $message;
    }
    
    private function generateRoomCode()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code exists and regenerate if needed
        if ($this->roomModel->getRoomByCode($code)) {
            return $this->generateRoomCode();
        }
        
        return $code;
    }
}