<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config.php';
require_once '../includes/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add_content':
        $title = trim($_POST['title']);
        $slug = cleanUrl($title);
        $type = $_POST['type'];
        $description = trim($_POST['description'] ?? '');
        $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
        $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
        $duration = trim($_POST['duration'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $language = trim($_POST['language'] ?? '');
        $poster_url = trim($_POST['poster_url'] ?? '');
        $backdrop_url = trim($_POST['backdrop_url'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $featured = (int)($_POST['featured'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO content (title, slug, type, description, year, rating, duration, genre, country, language, poster_url, backdrop_url, category_id, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssidssssssi", $title, $slug, $type, $description, $year, $rating, $duration, $genre, $country, $language, $poster_url, $backdrop_url, $category_id, $featured);
        
        if ($stmt->execute()) {
            $content_id = $conn->insert_id;
            
            if (!empty($_POST['source_url'])) {
                $source_url = trim($_POST['source_url']);
                $server_name = trim($_POST['server_name'] ?? 'Server 1');
                $quality = trim($_POST['quality'] ?? '');
                
                $sourceStmt = $conn->prepare("INSERT INTO sources (content_id, server_name, quality, url) VALUES (?, ?, ?, ?)");
                $sourceStmt->bind_param("isss", $content_id, $server_name, $quality, $source_url);
                $sourceStmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'Content added successfully', 'id' => $content_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add content: ' . $stmt->error]);
        }
        break;
        
    case 'update_content':
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $slug = cleanUrl($title);
        $type = $_POST['type'];
        $description = trim($_POST['description'] ?? '');
        $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
        $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
        $duration = trim($_POST['duration'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $language = trim($_POST['language'] ?? '');
        $poster_url = trim($_POST['poster_url'] ?? '');
        $backdrop_url = trim($_POST['backdrop_url'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $featured = (int)($_POST['featured'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE content SET title=?, slug=?, type=?, description=?, year=?, rating=?, duration=?, genre=?, country=?, language=?, poster_url=?, backdrop_url=?, category_id=?, featured=? WHERE id=?");
        $stmt->bind_param("ssssidsssssssii", $title, $slug, $type, $description, $year, $rating, $duration, $genre, $country, $language, $poster_url, $backdrop_url, $category_id, $featured, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Content updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update content']);
        }
        break;
        
    case 'delete_content':
        $id = (int)$_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM content WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Content deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete content']);
        }
        break;
        
    case 'get_content':
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT * FROM content WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $sourcesStmt = $conn->prepare("SELECT * FROM sources WHERE content_id=? ORDER BY order_num, id");
            $sourcesStmt->bind_param("i", $id);
            $sourcesStmt->execute();
            $sourcesResult = $sourcesStmt->get_result();
            $row['sources'] = [];
            while ($source = $sourcesResult->fetch_assoc()) {
                $row['sources'][] = $source;
            }
            
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Content not found']);
        }
        break;
        
    case 'list_content':
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        
        $where = [];
        $params = [];
        $types = '';
        
        if ($search) {
            $where[] = 'title LIKE ?';
            $params[] = '%' . $search . '%';
            $types .= 's';
        }
        
        if ($type) {
            $where[] = 'type = ?';
            $params[] = $type;
            $types .= 's';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT id, title, type, year, poster_url, views FROM content $whereClause ORDER BY created_at DESC LIMIT 100";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        $content = [];
        while ($row = $result->fetch_assoc()) {
            $content[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $content]);
        break;
        
    case 'add_category':
        $name = trim($_POST['name']);
        $slug = cleanUrl($name);
        $order_num = (int)($_POST['order_num'] ?? 0);
        
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, order_num) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $slug, $order_num);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add category']);
        }
        break;
        
    case 'update_category':
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $slug = cleanUrl($name);
        $order_num = (int)($_POST['order_num'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, order_num=? WHERE id=?");
        $stmt->bind_param("ssii", $name, $slug, $order_num, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update category']);
        }
        break;
        
    case 'delete_category':
        $id = (int)$_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
        }
        break;
        
    case 'list_categories':
        $result = $conn->query("SELECT * FROM categories ORDER BY order_num, name");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $categories]);
        break;
        
    case 'save_settings':
        $settings = $_POST['settings'] ?? [];
        
        foreach ($settings as $key => $value) {
            updateSetting($key, $value);
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
        break;
        
    case 'tmdb_search':
        $query = $_GET['query'] ?? '';
        $type = $_GET['type'] ?? 'movie';
        $api_key = getSetting('tmdb_api_key');
        
        if (empty($api_key)) {
            echo json_encode(['success' => false, 'message' => 'TMDB API key not set']);
            break;
        }
        
        $url = "https://api.themoviedb.org/3/search/$type?api_key=$api_key&query=" . urlencode($query);
        $response = file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            echo json_encode(['success' => true, 'data' => $data['results'] ?? []]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to search TMDB']);
        }
        break;
        
    case 'tmdb_import':
        $tmdb_id = (int)$_POST['tmdb_id'];
        $type = $_POST['type'] ?? 'movie';
        $api_key = getSetting('tmdb_api_key');
        
        if (empty($api_key)) {
            echo json_encode(['success' => false, 'message' => 'TMDB API key not set']);
            break;
        }
        
        $url = "https://api.themoviedb.org/3/$type/$tmdb_id?api_key=$api_key";
        $response = file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            
            $title = $type === 'movie' ? $data['title'] : $data['name'];
            $slug = cleanUrl($title);
            $description = $data['overview'] ?? '';
            $year = isset($data['release_date']) ? (int)substr($data['release_date'], 0, 4) : (isset($data['first_air_date']) ? (int)substr($data['first_air_date'], 0, 4) : null);
            $rating = isset($data['vote_average']) ? (float)$data['vote_average'] : null;
            $poster_url = isset($data['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] : '';
            $backdrop_url = isset($data['backdrop_path']) ? 'https://image.tmdb.org/t/p/original' . $data['backdrop_path'] : '';
            $content_type = $type === 'tv' ? 'series' : 'movie';
            
            $genres = [];
            if (isset($data['genres'])) {
                foreach ($data['genres'] as $genre) {
                    $genres[] = $genre['name'];
                }
            }
            $genre = implode(', ', $genres);
            
            $stmt = $conn->prepare("INSERT INTO content (title, slug, type, description, year, rating, genre, poster_url, backdrop_url, tmdb_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssidsssi", $title, $slug, $content_type, $description, $year, $rating, $genre, $poster_url, $backdrop_url, $tmdb_id);
            
            if ($stmt->execute()) {
                $content_id = $conn->insert_id;
                echo json_encode(['success' => true, 'message' => 'Content imported successfully', 'id' => $content_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to import content']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch from TMDB']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
