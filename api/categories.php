<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/db.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $result = $conn->query("SELECT * FROM categories ORDER BY order_num, name");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}
?>
