<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/db.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        $types = '';
        
        if (isset($_GET['category_id']) && $_GET['category_id'] !== '') {
            $where[] = 'category_id = ?';
            $params[] = (int)$_GET['category_id'];
            $types .= 'i';
        }
        
        if (isset($_GET['type']) && $_GET['type'] !== '') {
            $where[] = 'type = ?';
            $params[] = $_GET['type'];
            $types .= 's';
        }
        
        if (isset($_GET['genre']) && $_GET['genre'] !== '') {
            $where[] = 'genre LIKE ?';
            $params[] = '%' . $_GET['genre'] . '%';
            $types .= 's';
        }
        
        if (isset($_GET['year']) && $_GET['year'] !== '') {
            $where[] = 'year = ?';
            $params[] = (int)$_GET['year'];
            $types .= 'i';
        }
        
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $where[] = '(title LIKE ? OR description LIKE ?)';
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $types .= 'ss';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $countSql = "SELECT COUNT(*) as total FROM content $whereClause";
        if (!empty($params)) {
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $totalResult = $countStmt->get_result();
            $total = $totalResult->fetch_assoc()['total'];
        } else {
            $total = $conn->query($countSql)->fetch_assoc()['total'];
        }
        
        $sql = "SELECT c.*, cat.name as category_name 
                FROM content c 
                LEFT JOIN categories cat ON c.category_id = cat.id 
                $whereClause 
                ORDER BY c.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $content = [];
        while ($row = $result->fetch_assoc()) {
            $content[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $content,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;
        
    case 'get':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT c.*, cat.name as category_name FROM content c LEFT JOIN categories cat ON c.category_id = cat.id WHERE c.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $conn->query("UPDATE content SET views = views + 1 WHERE id = $id");
                
                $sourcesStmt = $conn->prepare("SELECT * FROM sources WHERE content_id = ? ORDER BY order_num, id");
                $sourcesStmt->bind_param('i', $id);
                $sourcesStmt->execute();
                $sourcesResult = $sourcesStmt->get_result();
                $row['sources'] = [];
                while ($source = $sourcesResult->fetch_assoc()) {
                    $row['sources'][] = $source;
                }
                
                $subtitlesStmt = $conn->prepare("SELECT * FROM subtitles WHERE content_id = ?");
                $subtitlesStmt->bind_param('i', $id);
                $subtitlesStmt->execute();
                $subtitlesResult = $subtitlesStmt->get_result();
                $row['subtitles'] = [];
                while ($subtitle = $subtitlesResult->fetch_assoc()) {
                    $row['subtitles'][] = $subtitle;
                }
                
                if ($row['type'] === 'series') {
                    $seasonsStmt = $conn->prepare("SELECT * FROM seasons WHERE content_id = ? ORDER BY season_number");
                    $seasonsStmt->bind_param('i', $id);
                    $seasonsStmt->execute();
                    $seasonsResult = $seasonsStmt->get_result();
                    $row['seasons'] = [];
                    while ($season = $seasonsResult->fetch_assoc()) {
                        $episodesStmt = $conn->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
                        $episodesStmt->bind_param('i', $season['id']);
                        $episodesStmt->execute();
                        $episodesResult = $episodesStmt->get_result();
                        $season['episodes'] = [];
                        while ($episode = $episodesResult->fetch_assoc()) {
                            $epSourcesStmt = $conn->prepare("SELECT * FROM episode_sources WHERE episode_id = ? ORDER BY order_num, id");
                            $epSourcesStmt->bind_param('i', $episode['id']);
                            $epSourcesStmt->execute();
                            $epSourcesResult = $epSourcesStmt->get_result();
                            $episode['sources'] = [];
                            while ($epSource = $epSourcesResult->fetch_assoc()) {
                                $episode['sources'][] = $epSource;
                            }
                            $season['episodes'][] = $episode;
                        }
                        $row['seasons'][] = $season;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $row
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Content not found'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid ID'
            ]);
        }
        break;
        
    case 'featured':
        $stmt = $conn->prepare("SELECT c.*, cat.name as category_name FROM content c LEFT JOIN categories cat ON c.category_id = cat.id WHERE c.featured = 1 ORDER BY c.created_at DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $content = [];
        while ($row = $result->fetch_assoc()) {
            $content[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $content
        ]);
        break;
        
    case 'related':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT genre, category_id FROM content WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $genre = $row['genre'];
                $category_id = $row['category_id'];
                
                $relatedStmt = $conn->prepare("SELECT c.*, cat.name as category_name FROM content c LEFT JOIN categories cat ON c.category_id = cat.id WHERE c.id != ? AND (c.genre LIKE ? OR c.category_id = ?) ORDER BY RAND() LIMIT 12");
                $genreSearch = '%' . $genre . '%';
                $relatedStmt->bind_param('isi', $id, $genreSearch, $category_id);
                $relatedStmt->execute();
                $relatedResult = $relatedStmt->get_result();
                
                $related = [];
                while ($relatedRow = $relatedResult->fetch_assoc()) {
                    $related[] = $relatedRow;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $related
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid ID'
            ]);
        }
        break;
        
    case 'genres':
        $result = $conn->query("SELECT DISTINCT genre FROM content WHERE genre IS NOT NULL AND genre != ''");
        $genres = [];
        while ($row = $result->fetch_assoc()) {
            $genreList = explode(',', $row['genre']);
            foreach ($genreList as $genre) {
                $genre = trim($genre);
                if ($genre && !in_array($genre, $genres)) {
                    $genres[] = $genre;
                }
            }
        }
        sort($genres);
        
        echo json_encode([
            'success' => true,
            'data' => $genres
        ]);
        break;
        
    case 'years':
        $result = $conn->query("SELECT DISTINCT year FROM content WHERE year IS NOT NULL ORDER BY year DESC");
        $years = [];
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['year'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $years
        ]);
        break;
        
    case 'interaction':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        
        if ($id > 0 && in_array($type, ['like', 'dislike'])) {
            $field = $type === 'like' ? 'likes' : 'dislikes';
            $conn->query("UPDATE content SET $field = $field + 1 WHERE id = $id");
            
            echo json_encode([
                'success' => true,
                'message' => ucfirst($type) . ' recorded'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}
?>
