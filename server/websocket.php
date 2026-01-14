<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class KaraokeWebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'join_room':
                $this->handleJoinRoom($from, $data);
                break;
                
            case 'leave_room':
                $this->handleLeaveRoom($from, $data);
                break;
                
            case 'queue_update':
                $this->handleQueueUpdate($from, $data);
                break;
                
            case 'chat_message':
                $this->handleChatMessage($from, $data);
                break;
                
            case 'scoring_update':
                $this->handleScoringUpdate($from, $data);
                break;
                
            case 'room_state':
                $this->handleRoomState($from, $data);
                break;
        }
    }

    private function handleJoinRoom(ConnectionInterface $conn, $data)
    {
        if (!isset($data['room_id'])) {
            return;
        }

        $roomId = $data['room_id'];
        
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [];
        }
        
        $this->rooms[$roomId][$conn->resourceId] = $conn;
        $conn->roomId = $roomId;
        
        $this->broadcastToRoom($roomId, [
            'type' => 'user_joined',
            'user_id' => $conn->resourceId,
            'timestamp' => time()
        ]);
        
        echo "Client {$conn->resourceId} joined room {$roomId}\n";
    }

    private function handleLeaveRoom(ConnectionInterface $conn, $data)
    {
        if (!isset($conn->roomId)) {
            return;
        }
        
        $roomId = $conn->roomId;
        unset($this->rooms[$roomId][$conn->resourceId]);
        
        if (empty($this->rooms[$roomId])) {
            unset($this->rooms[$roomId]);
        }
        
        $this->broadcastToRoom($roomId, [
            'type' => 'user_left',
            'user_id' => $conn->resourceId,
            'timestamp' => time()
        ]);
        
        echo "Client {$conn->resourceId} left room {$roomId}\n";
    }

    private function handleQueueUpdate(ConnectionInterface $from, $data)
    {
        if (!isset($from->roomId)) {
            return;
        }
        
        $this->broadcastToRoom($from->roomId, [
            'type' => 'queue_updated',
            'data' => $data['data'],
            'timestamp' => time()
        ]);
    }

    private function handleChatMessage(ConnectionInterface $from, $data)
    {
        if (!isset($from->roomId) || !isset($data['message'])) {
            return;
        }
        
        $this->broadcastToRoom($from->roomId, [
            'type' => 'chat_message',
            'user_id' => $from->resourceId,
            'message' => htmlspecialchars($data['message']),
            'timestamp' => time()
        ]);
    }

    private function handleScoringUpdate(ConnectionInterface $from, $data)
    {
        if (!isset($from->roomId)) {
            return;
        }
        
        $this->broadcastToRoom($from->roomId, [
            'type' => 'scoring_update',
            'score' => $data['score'],
            'user_id' => $data['user_id'],
            'timestamp' => time()
        ]);
    }

    private function handleRoomState(ConnectionInterface $from, $data)
    {
        if (!isset($from->roomId)) {
            return;
        }
        
        $this->broadcastToRoom($from->roomId, [
            'type' => 'room_state',
            'state' => $data['state'],
            'timestamp' => time()
        ]);
    }

    private function broadcastToRoom($roomId, $message)
    {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $messageStr = json_encode($message);
        
        foreach ($this->rooms[$roomId] as $client) {
            $client->send($messageStr);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        if (isset($conn->roomId)) {
            $this->handleLeaveRoom($conn, []);
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new KaraokeWebSocket()
        )
    ),
    8080
);

echo "WebSocket server started on port 8080\n";
$server->run();