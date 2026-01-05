<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $format = $_GET['format'] ?? 'json';
    
    switch ($format) {
        case 'json':
            exportJSON($conn);
            break;
        case 'csv':
            exportCSV($conn);
            break;
        case 'backup':
            exportBackup($conn);
            break;
        default:
            throw new Exception('Invalid export format');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function exportJSON($conn) {
    // Get all data
    $movies = $conn->query("SELECT * FROM movies ORDER BY created_at DESC")->fetchAll();
    $categories = $conn->query("SELECT * FROM categories ORDER BY order_index ASC")->fetchAll();
    $servers = $conn->query("SELECT * FROM movie_servers")->fetchAll();
    
    $data = [
        'export_date' => date('c'),
        'version' => '1.0.0',
        'categories' => $categories,
        'movies' => $movies,
        'servers' => $servers
    ];
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // Output file
    $filename = 'cinecraze_export_' . date('Y-m-d_H-i-s') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json));
    
    echo $json;
}

function exportCSV($conn) {
    $movies = $conn->query("SELECT m.*, GROUP_CONCAT(ms.server_name) as server_names, 
                           GROUP_CONCAT(ms.server_url) as server_urls
                           FROM movies m 
                           LEFT JOIN movie_servers ms ON m.id = ms.movie_id 
                           GROUP BY m.id")->fetchAll();
    
    $filename = 'cinecraze_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'ID', 'Title', 'Description', 'Poster', 'Thumbnail', 'Year', 
        'Duration', 'Rating', 'Country', 'Genre', 'Type', 'IMDB ID',
        'Trailer URL', 'Status', 'View Count', 'Servers'
    ]);
    
    // CSV data
    foreach ($movies as $movie) {
        fputcsv($output, [
            $movie['id'],
            $movie['title'],
            $movie['description'],
            $movie['poster'],
            $movie['thumbnail'],
            $movie['year'],
            $movie['duration'],
            $movie['rating'],
            $movie['country'],
            $movie['genre'],
            $movie['type'],
            $movie['imdb_id'],
            $movie['trailer_url'],
            $movie['status'],
            $movie['view_count'],
            $movie['server_names'] . '|' . $movie['server_urls']
        ]);
    }
    
    fclose($output);
}

function exportBackup($conn) {
    // Get all table structures and data
    $tables = ['categories', 'movies', 'movie_servers', 'seasons', 'episodes', 
               'episode_servers', 'users', 'watch_later', 'view_history', 'settings'];
    
    $backup = "-- CineCraze Database Backup\n";
    $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $backup .= "-- Table structure for table `$table`\n";
        
        // Get CREATE TABLE statement
        $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch();
        $backup .= $create['Create Table'] . ";\n\n";
        
        $backup .= "-- Dumping data for table `$table`\n";
        
        // Get table data
        $rows = $conn->query("SELECT * FROM `$table`")->fetchAll();
        
        foreach ($rows as $row) {
            $values = array_map(function($value) use ($conn) {
                return $value === null ? 'NULL' : "'" . $conn->quote($value) . "'";
            }, $row);
            
            $backup .= "INSERT INTO `$table` (`" . implode('`, `', array_keys($row)) . "`) VALUES (" . implode(', ', $values) . ");\n";
        }
        
        $backup .= "\n";
    }
    
    $filename = 'cinecraze_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($backup));
    
    echo $backup;
}
?>