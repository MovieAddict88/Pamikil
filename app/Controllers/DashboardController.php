<?php
/**
 * Dashboard Controller
 */

class DashboardController {
    private $userModel;
    private $roomModel;
    private $songModel;
    private $queueModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->roomModel = new Room();
        $this->songModel = new Song();
        $this->queueModel = new SongQueue();
        $this->db = Database::getInstance();
    }
    
    public function index() {
        $user = AuthController::checkAuth();
        
        // Get user's rooms and stats
        $userRooms = $this->roomModel->getByHost($user['id'], 10);
        $userStats = $this->userModel->getUserStats($user['id']);
        
        // Get popular songs
        $popularSongs = $this->songModel->getPopular(10);
        
        // Get recent songs
        $recentSongs = $this->songModel->getRecent(10);
        
        // Get public rooms
        $publicRooms = $this->roomModel->getPublic(10);
        
        $data = [
            'user' => $user,
            'userRooms' => $userRooms,
            'userStats' => $userStats,
            'popularSongs' => $popularSongs,
            'recentSongs' => $recentSongs,
            'publicRooms' => $publicRooms
        ];
        
        include __DIR__ . '/../Views/dashboard/index.php';
    }
    
    public function createRoom() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleCreateRoom();
        }
        
        return $this->showCreateRoomForm();
    }
    
    private function handleCreateRoom() {
        $user = AuthController::checkAuth();
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isPublic = isset($_POST['is_public']);
        $maxParticipants = (int)($_POST['max_participants'] ?? 20);
        
        $errors = [];
        
        if (empty($name)) {
            $errors[] = "Room name is required";
        } elseif (strlen($name) < 3) {
            $errors[] = "Room name must be at least 3 characters";
        }
        
        if ($maxParticipants < 2 || $maxParticipants > 100) {
            $errors[] = "Maximum participants must be between 2 and 100";
        }
        
        if (empty($errors)) {
            try {
                $roomCode = $this->roomModel->generateRoomCode();
                
                $roomId = $this->roomModel->create([
                    'name' => $name,
                    'description' => $description,
                    'room_code' => $roomCode,
                    'host_id' => $user['id'],
                    'is_public' => $isPublic,
                    'max_participants' => $maxParticipants,
                    'settings' => [
                        'autoplay' => isset($_POST['autoplay']),
                        'allow_duets' => isset($_POST['allow_duets']),
                        'max_songs_per_user' => (int)($_POST['max_songs_per_user'] ?? 3),
                        'require_approval' => isset($_POST['require_approval'])
                    ]
                ]);
                
                header("Location: /room/{$roomCode}");
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Failed to create room: " . $e->getMessage();
            }
        }
        
        return $this->showCreateRoomForm($errors);
    }
    
    private function showCreateRoomForm($errors = []) {
        $user = AuthController::checkAuth();
        $errorsJson = json_encode($errors);
        
        include __DIR__ . '/../Views/dashboard/create-room.php';
    }
    
    public function joinRoom($roomCode) {
        $user = AuthController::checkAuth();
        $room = $this->roomModel->findByCode($roomCode);
        
        if (!$room) {
            header('HTTP/1.1 404 Not Found');
            include __DIR__ . '/../Views/errors/404.php';
            return;
        }
        
        try {
            $this->roomModel->addParticipant($room['id'], $user['id']);
            header("Location: /room/{$roomCode}");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /dashboard');
            exit;
        }
    }
    
    public function myRooms() {
        $user = AuthController::checkAuth();
        $rooms = $this->roomModel->getByHost($user['id']);
        
        include __DIR__ . '/../Views/dashboard/my-rooms.php';
    }
    
    public function room($roomCode) {
        $user = AuthController::checkAuth();
        $room = $this->roomModel->findByCode($roomCode);
        
        if (!$room) {
            header('HTTP/1.1 404 Not Found');
            include __DIR__ . '/../Views/errors/404.php';
            return;
        }
        
        // Check if user is in room
        if (!$this->roomModel->isUserInRoom($room['id'], $user['id'])) {
            $_SESSION['error'] = "You need to join this room first";
            header('Location: /dashboard');
            exit;
        }
        
        // Get room data
        $participants = $this->roomModel->getParticipants($room['id']);
        $currentSong = $this->queueModel->getCurrentSong($room['id']);
        $queue = $this->queueModel->getRoomQueue($room['id'], 20);
        $userRole = $this->roomModel->getUserRole($room['id'], $user['id']);
        
        $data = [
            'room' => $room,
            'user' => $user,
            'participants' => $participants,
            'currentSong' => $currentSong,
            'queue' => $queue,
            'userRole' => $userRole,
            'queueStats' => $this->queueModel->getQueueStats($room['id'])
        ];
        
        include __DIR__ . '/../Views/room/index.php';
    }
    
    public function apiQueue($roomCode) {
        $user = AuthController::checkAuth();
        $room = $this->roomModel->findByCode($roomCode);
        
        if (!$room || !$this->roomModel->isUserInRoom($room['id'], $user['id'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Room not found or access denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
        $action = $_GET['action'] ?? 'get';
        
        switch ($action) {
            case 'get':
                $queue = $this->queueModel->getRoomQueue($room['id']);
                $currentSong = $this->queueModel->getCurrentSong($room['id']);
                echo json_encode([
                    'queue' => $queue,
                    'currentSong' => $currentSong,
                    'stats' => $this->queueModel->getQueueStats($room['id'])
                ]);
                break;
                
            case 'add':
                $this->handleAddToQueue($room);
                break;
                
            case 'remove':
                $this->handleRemoveFromQueue($room);
                break;
                
            case 'reorder':
                $this->handleReorderQueue($room);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
    }
    
    private function handleAddToQueue($room) {
        $user = AuthController::checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $songId = $input['song_id'] ?? null;
        
        if (!$songId) {
            http_response_code(400);
            echo json_encode(['error' => 'Song ID is required']);
            return;
        }
        
        // Check if song exists
        $song = $this->songModel->find($songId);
        if (!$song) {
            http_response_code(404);
            echo json_encode(['error' => 'Song not found']);
            return;
        }
        
        // Check if user can add song (room settings)
        $settings = json_decode($room['settings'], true) ?: [];
        if (!$this->queueModel->canUserAddSong($room['id'], $user['id'], $settings)) {
            http_response_code(400);
            echo json_encode(['error' => 'You have reached the maximum number of songs in queue']);
            return;
        }
        
        try {
            $queueId = $this->queueModel->addToQueue($room['id'], $songId, $user['id']);
            
            // Get updated queue
            $queue = $this->queueModel->getRoomQueue($room['id']);
            $currentSong = $this->queueModel->getCurrentSong($room['id']);
            
            echo json_encode([
                'success' => true,
                'queueId' => $queueId,
                'queue' => $queue,
                'currentSong' => $currentSong
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function handleRemoveFromQueue($room) {
        $user = AuthController::checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $queueId = $_GET['id'] ?? null;
        
        if (!$queueId) {
            http_response_code(400);
            echo json_encode(['error' => 'Queue ID is required']);
            return;
        }
        
        try {
            $this->queueModel->removeFromQueue($queueId);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function handleReorderQueue($room) {
        $user = AuthController::checkAuth();
        $userRole = $this->roomModel->getUserRole($room['id'], $user['id']);
        
        // Only host and moderators can reorder queue
        if (!in_array($userRole, ['host', 'moderator'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        
        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['error' => 'No items to reorder']);
            return;
        }
        
        try {
            foreach ($items as $index => $queueId) {
                $this->queueModel->reorderQueueItem($room['id'], $queueId, $index + 1);
            }
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}