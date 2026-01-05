<?php
require_once 'config/database.php';

session_start();

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_movie':
                addMovie($conn);
                break;
            case 'update_movie':
                updateMovie($conn);
                break;
            case 'delete_movie':
                deleteMovie($conn);
                break;
            case 'import_youtube':
                importFromYouTube($conn);
                break;
            case 'import_tmdb':
                importFromTMDB($conn);
                break;
            case 'export_data':
                exportData($conn);
                break;
        }
    }
}

// Functions
function addMovie($conn) {
    try {
        $stmt = $conn->prepare("INSERT INTO movies (title, slug, description, poster, thumbnail, year, duration, rating, country, genre, type, imdb_id, trailer_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['title'],
            createSlug($_POST['title']),
            $_POST['description'],
            $_POST['poster'],
            $_POST['thumbnail'],
            $_POST['year'],
            $_POST['duration'],
            $_POST['rating'],
            $_POST['country'],
            $_POST['genre'],
            $_POST['type'],
            $_POST['imdb_id'],
            $_POST['trailer_url']
        ]);
        
        $movieId = $conn->lastInsertId();
        
        // Add servers
        if (!empty($_POST['servers'])) {
            $serverData = json_decode($_POST['servers'], true);
            foreach ($serverData as $server) {
                $stmt = $conn->prepare("INSERT INTO movie_servers (movie_id, server_name, server_url, quality, is_primary) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$movieId, $server['name'], $server['url'], $server['quality'], $server['primary'] ? 1 : 0]);
            }
        }
        
        $success = "Movie added successfully!";
    } catch (Exception $e) {
        $error = "Error adding movie: " . $e->getMessage();
    }
}

function updateMovie($conn) {
    try {
        $stmt = $conn->prepare("UPDATE movies SET title=?, description=?, poster=?, thumbnail=?, year=?, duration=?, rating=?, country=?, genre=?, type=?, imdb_id=?, trailer_url=? WHERE id=?");
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['poster'],
            $_POST['thumbnail'],
            $_POST['year'],
            $_POST['duration'],
            $_POST['rating'],
            $_POST['country'],
            $_POST['genre'],
            $_POST['type'],
            $_POST['imdb_id'],
            $_POST['trailer_url'],
            $_POST['movie_id']
        ]);
        
        // Update servers (delete existing and add new)
        $conn->prepare("DELETE FROM movie_servers WHERE movie_id = ?")->execute([$_POST['movie_id']]);
        
        if (!empty($_POST['servers'])) {
            $serverData = json_decode($_POST['servers'], true);
            foreach ($serverData as $server) {
                $stmt = $conn->prepare("INSERT INTO movie_servers (movie_id, server_name, server_url, quality, is_primary) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['movie_id'], $server['name'], $server['url'], $server['quality'], $server['primary'] ? 1 : 0]);
            }
        }
        
        $success = "Movie updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating movie: " . $e->getMessage();
    }
}

function deleteMovie($conn) {
    try {
        $stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$_POST['movie_id']]);
        $success = "Movie deleted successfully!";
    } catch (Exception $e) {
        $error = "Error deleting movie: " . $e->getMessage();
    }
}

function createSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Fetch data for dashboard
$stmt = $conn->query("SELECT COUNT(*) as total_movies FROM movies");
$totalMovies = $stmt->fetch()['total_movies'];

$stmt = $conn->query("SELECT COUNT(*) as total_series FROM movies WHERE type = 'series'");
$totalSeries = $stmt->fetch()['total_series'];

$stmt = $conn->query("SELECT COUNT(*) as total_live FROM movies WHERE type = 'live'");
$totalLive = $stmt->fetch()['total_live'];

$stmt = $conn->query("SELECT SUM(view_count) as total_views FROM movies");
$totalViews = $stmt->fetch()['total_views'] ?? 0;

$stmt = $conn->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 10");
$recentMovies = $stmt->fetchAll();

$stmt = $conn->query("SELECT * FROM categories ORDER BY order_index ASC");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e50914">
    <title>CineCraze Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e50914;
            --primary-dark: #b20710;
            --secondary: #221f1f;
            --background: #0a0a0a;
            --surface: #1a1a1a;
            --surface-light: #2d2d2d;
            --surface-hover: #333333;
            --text: #ffffff;
            --text-secondary: #b3b3b3;
            --text-muted: #808080;
            --success: #46d369;
            --warning: #ffa500;
            --danger: #f40612;
            --accent: #00d4ff;
            --accent-dark: #0099cc;
            --bottom-bar-height: clamp(70px, 10vw, 85px);
            --border-radius: clamp(8px, 2vw, 16px);
            --border-radius-sm: clamp(6px, 1.5vw, 12px);
            --shadow: 0 5px 15px rgba(0,0,0,0.5);
            --shadow-hover: 0 8px 25px rgba(0,0,0,0.6);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background) 0%, var(--surface) 100%);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: var(--bottom-bar-height);
        }

        /* Header */
        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: clamp(15px, 3vw, 25px) clamp(15px, 5vw, 5%);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 15px);
            font-size: clamp(20px, 4vw, 28px);
            font-weight: 700;
            color: white;
        }

        .admin-nav {
            display: flex;
            gap: clamp(15px, 3vw, 25px);
            align-items: center;
        }

        .nav-item {
            color: white;
            text-decoration: none;
            padding: clamp(8px, 2vw, 12px) clamp(15px, 3vw, 20px);
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            font-size: clamp(14px, 2.5vw, 16px);
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-item.active {
            background: rgba(255,255,255,0.2);
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: clamp(20px, 4vw, 40px) clamp(15px, 5vw, 5%);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(clamp(250px, 30vw, 300px), 1fr));
            gap: clamp(15px, 3vw, 25px);
            margin-bottom: clamp(25px, 5vw, 40px);
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(20px, 4vw, 30px);
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--surface-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            font-size: clamp(30px, 6vw, 40px);
            margin-bottom: clamp(10px, 2vw, 15px);
            opacity: 0.8;
        }

        .stat-number {
            font-size: clamp(24px, 5vw, 36px);
            font-weight: 700;
            margin-bottom: clamp(5px, 1vw, 8px);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: clamp(12px, 2.5vw, 14px);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Content Sections */
        .content-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(20px, 4vw, 30px);
            margin-bottom: clamp(25px, 5vw, 40px);
            box-shadow: var(--shadow);
            border: 1px solid var(--surface-light);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: clamp(20px, 4vw, 30px);
            padding-bottom: clamp(15px, 3vw, 20px);
            border-bottom: 1px solid var(--surface-light);
        }

        .section-title {
            font-size: clamp(18px, 4vw, 24px);
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 15px);
        }

        .btn {
            padding: clamp(10px, 2.5vw, 15px) clamp(20px, 4vw, 25px);
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: clamp(12px, 2.5vw, 14px);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: clamp(6px, 1.5vw, 10px);
            text-align: center;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-secondary {
            background: var(--surface-light);
            color: var(--text);
            border: 1px solid var(--surface-light);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(clamp(250px, 40vw, 300px), 1fr));
            gap: clamp(15px, 3vw, 20px);
            margin-bottom: clamp(20px, 4vw, 30px);
        }

        .form-group {
            margin-bottom: clamp(15px, 3vw, 20px);
        }

        label {
            display: block;
            margin-bottom: clamp(5px, 1vw, 8px);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: clamp(12px, 2.5vw, 14px);
        }

        input, select, textarea {
            width: 100%;
            padding: clamp(10px, 2.5vw, 15px);
            border: 1px solid var(--surface-light);
            border-radius: var(--border-radius-sm);
            background: var(--background);
            color: var(--text);
            font-size: clamp(14px, 2.5vw, 16px);
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
            background: var(--surface);
        }

        textarea {
            resize: vertical;
            min-height: clamp(80px, 15vw, 120px);
        }

        /* Movie List */
        .movie-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(clamp(280px, 40vw, 350px), 1fr));
            gap: clamp(15px, 3vw, 20px);
        }

        .movie-item {
            background: var(--surface-light);
            border-radius: var(--border-radius);
            padding: clamp(15px, 3vw, 20px);
            border: 1px solid var(--surface-light);
            transition: all 0.3s ease;
        }

        .movie-item:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .movie-header {
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 15px);
            margin-bottom: clamp(10px, 2vw, 15px);
        }

        .movie-poster {
            width: clamp(50px, 10vw, 70px);
            height: clamp(70px, 14vw, 100px);
            object-fit: cover;
            border-radius: var(--border-radius-sm);
        }

        .movie-info {
            flex: 1;
        }

        .movie-title {
            font-weight: 600;
            margin-bottom: clamp(3px, 0.5vw, 5px);
            font-size: clamp(14px, 2.5vw, 16px);
        }

        .movie-meta {
            color: var(--text-muted);
            font-size: clamp(12px, 2vw, 14px);
        }

        .movie-actions {
            display: flex;
            gap: clamp(8px, 2vw, 12px);
            margin-top: clamp(10px, 2vw, 15px);
        }

        /* Tabs */
        .tab-nav {
            display: flex;
            gap: clamp(5px, 1vw, 8px);
            margin-bottom: clamp(20px, 4vw, 30px);
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: clamp(8px, 2vw, 12px) clamp(15px, 3vw, 20px);
            background: var(--surface-light);
            color: var(--text-secondary);
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: clamp(12px, 2.5vw, 14px);
            font-weight: 600;
        }

        .tab-btn:hover {
            background: var(--surface-hover);
            color: var(--text);
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Search Results */
        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(clamp(200px, 30vw, 250px), 1fr));
            gap: clamp(15px, 3vw, 20px);
            margin-top: clamp(15px, 3vw, 20px);
        }

        .search-result-item {
            background: var(--surface-light);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .search-result-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .search-result-img {
            width: 100%;
            height: clamp(150px, 30vw, 200px);
            object-fit: cover;
        }

        .search-result-info {
            padding: clamp(10px, 2vw, 15px);
        }

        .search-result-title {
            font-weight: 600;
            margin-bottom: clamp(5px, 1vw, 8px);
            font-size: clamp(13px, 2.5vw, 15px);
        }

        .search-result-meta {
            color: var(--text-muted);
            font-size: clamp(11px, 2vw, 13px);
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: clamp(6px, 1.5vw, 8px);
            background: var(--surface-light);
            border-radius: 3px;
            overflow: hidden;
            margin: clamp(10px, 2vw, 15px) 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            width: 0%;
            transition: width 0.3s ease;
        }

        /* Status Messages */
        .status-message {
            padding: clamp(12px, 2.5vw, 15px);
            border-radius: var(--border-radius-sm);
            margin: clamp(10px, 2vw, 15px) 0;
            font-weight: 600;
            font-size: clamp(14px, 2.5vw, 16px);
        }

        .status-success {
            background: rgba(70, 211, 105, 0.1);
            color: var(--success);
            border: 1px solid rgba(70, 211, 105, 0.3);
        }

        .status-error {
            background: rgba(244, 6, 18, 0.1);
            color: var(--danger);
            border: 1px solid rgba(244, 6, 18, 0.3);
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-light) 100%);
            border-top: 2px solid var(--primary);
            box-shadow: 0 -5px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(20px);
            z-index: 1000;
            height: var(--bottom-bar-height);
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 clamp(8px, 2vw, 15px);
        }

        .nav-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(6px, 1.5vw, 10px);
            cursor: pointer;
            border-radius: clamp(8px, 2vw, 12px);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-muted);
            min-width: clamp(60px, 12vw, 80px);
            text-align: center;
        }

        .nav-tab:hover {
            color: var(--text);
            background: rgba(229, 9, 20, 0.1);
            transform: translateY(-2px);
        }

        .nav-tab.active {
            color: var(--primary);
            background: rgba(229, 9, 20, 0.1);
        }

        .nav-tab i {
            font-size: clamp(18px, 4vw, 24px);
            margin-bottom: clamp(4px, 1vw, 8px);
        }

        .nav-tab span {
            font-size: clamp(10px, 2vw, 14px);
            font-weight: 600;
            line-height: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-nav {
                display: none;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .movie-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .nav-tab {
                min-width: clamp(50px, 20vw, 70px);
            }
            
            .nav-tab i {
                font-size: clamp(16px, 5vw, 20px);
            }
            
            .nav-tab span {
                font-size: clamp(9px, 2.5vw, 12px);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-cog"></i>
                CineCraze Admin
            </div>
            <nav class="admin-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i> Site
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Stats -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--primary);">
                    <i class="fas fa-film"></i>
                </div>
                <div class="stat-number"><?php echo number_format($totalMovies); ?></div>
                <div class="stat-label">Total Movies</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--accent);">
                    <i class="fas fa-tv"></i>
                </div>
                <div class="stat-number"><?php echo number_format($totalSeries); ?></div>
                <div class="stat-label">TV Series</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success);">
                    <i class="fas fa-broadcast-tower"></i>
                </div>
                <div class="stat-number"><?php echo number_format($totalLive); ?></div>
                <div class="stat-label">Live Channels</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--warning);">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?php echo number_format($totalViews); ?></div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>

        <!-- Status Messages -->
        <?php if (isset($success)): ?>
            <div class="status-message status-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="status-message status-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Content Management Tabs -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-video"></i>
                    Content Management
                </h2>
                <button class="btn btn-primary" onclick="showTab('add-movie')">
                    <i class="fas fa-plus"></i> Add New Content
                </button>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <button class="tab-btn active" onclick="showTab('add-movie')">
                    <i class="fas fa-plus"></i> Add Content
                </button>
                <button class="tab-btn" onclick="showTab('youtube-import')">
                    <i class="fab fa-youtube"></i> YouTube Import
                </button>
                <button class="tab-btn" onclick="showTab('tmdb-import')">
                    <i class="fas fa-database"></i> TMDB Import
                </button>
                <button class="tab-btn" onclick="showTab('bulk-import')">
                    <i class="fas fa-upload"></i> Bulk Import
                </button>
                <button class="tab-btn" onclick="showTab('export-data')">
                    <i class="fas fa-download"></i> Export Data
                </button>
                <button class="tab-btn" onclick="showTab('manage-content')">
                    <i class="fas fa-list"></i> Manage Content
                </button>
            </div>

            <!-- Add Movie Tab -->
            <div class="tab-content active" id="add-movie">
                <form method="POST" id="movieForm">
                    <input type="hidden" name="action" value="add_movie">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Type *</label>
                            <select id="type" name="type" required>
                                <option value="movie">Movie</option>
                                <option value="series">TV Series</option>
                                <option value="live">Live TV</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="genre">Genre *</label>
                            <input type="text" id="genre" name="genre" required placeholder="Action, Drama, Comedy...">
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" placeholder="USA, UK, Japan...">
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year</label>
                            <input type="number" id="year" name="year" min="1900" max="2030" value="2024">
                        </div>
                        
                        <div class="form-group">
                            <label for="rating">Rating (0-10)</label>
                            <input type="number" id="rating" name="rating" min="0" max="10" step="0.1" value="8.0">
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">Duration (minutes)</label>
                            <input type="number" id="duration" name="duration" min="1" placeholder="120">
                        </div>
                        
                        <div class="form-group">
                            <label for="imdb_id">IMDB ID</label>
                            <input type="text" id="imdb_id" name="imdb_id" placeholder="tt1234567">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Movie/Series description..."></textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="poster">Poster URL</label>
                            <input type="url" id="poster" name="poster" placeholder="https://...">
                        </div>
                        
                        <div class="form-group">
                            <label for="thumbnail">Thumbnail URL</label>
                            <input type="url" id="thumbnail" name="thumbnail" placeholder="https://...">
                        </div>
                        
                        <div class="form-group">
                            <label for="trailer_url">Trailer URL</label>
                            <input type="url" id="trailer_url" name="trailer_url" placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>
                    
                    <!-- Server Configuration -->
                    <div class="form-group">
                        <label>Video Servers</label>
                        <div id="serverContainer">
                            <div class="server-item" style="margin-bottom: 15px; padding: 15px; background: var(--surface-light); border-radius: 8px;">
                                <div class="form-grid">
                                    <div>
                                        <label>Server Name</label>
                                        <input type="text" name="server_name[]" placeholder="Server 1" required>
                                    </div>
                                    <div>
                                        <label>Server URL</label>
                                        <input type="url" name="server_url[]" placeholder="https://..." required>
                                    </div>
                                    <div>
                                        <label>Quality</label>
                                        <select name="server_quality[]">
                                            <option value="480p">480p</option>
                                            <option value="720p" selected>720p</option>
                                            <option value="1080p">1080p</option>
                                            <option value="4K">4K</option>
                                        </select>
                                    </div>
                                    <div style="display: flex; align-items: center;">
                                        <label style="margin-right: 10px;">Primary</label>
                                        <input type="checkbox" name="server_primary[]" checked>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addServer()">
                            <i class="fas fa-plus"></i> Add Server
                        </button>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 15px 40px;">
                            <i class="fas fa-save"></i> Save Content
                        </button>
                    </div>
                </form>
            </div>

            <!-- YouTube Import Tab -->
            <div class="tab-content" id="youtube-import">
                <div class="form-group">
                    <label for="youtube_search">Search YouTube</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="youtube_search" placeholder="Search for movies, series..." style="flex: 1;">
                        <button type="button" class="btn btn-primary" onclick="searchYouTube()">
                            <i class="fab fa-youtube"></i> Search
                        </button>
                    </div>
                </div>
                
                <div id="youtube_results" class="search-results"></div>
                
                <form method="POST" id="youtubeImportForm" style="margin-top: 20px; display: none;">
                    <input type="hidden" name="action" value="import_youtube">
                    <input type="hidden" id="youtube_data" name="youtube_data">
                    
                    <div class="status-message status-info">
                        <i class="fas fa-info-circle"></i> 
                        Import selected YouTube video as content
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-import"></i> Import Selected Video
                    </button>
                </form>
            </div>

            <!-- TMDB Import Tab -->
            <div class="tab-content" id="tmdb-import">
                <div class="form-group">
                    <label for="tmdb_search">Search TMDB</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="tmdb_search" placeholder="Search TMDB database..." style="flex: 1;">
                        <button type="button" class="btn btn-primary" onclick="searchTMDB()">
                            <i class="fas fa-database"></i> Search
                        </button>
                    </div>
                </div>
                
                <div id="tmdb_results" class="search-results"></div>
                
                <form method="POST" id="tmdbImportForm" style="margin-top: 20px; display: none;">
                    <input type="hidden" name="action" value="import_tmdb">
                    <input type="hidden" id="tmdb_data" name="tmdb_data">
                    
                    <div class="status-message status-info">
                        <i class="fas fa-info-circle"></i> 
                        Import selected TMDB content
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-import"></i> Import from TMDB
                    </button>
                </form>
            </div>

            <!-- Bulk Import Tab -->
            <div class="tab-content" id="bulk-import">
                <div class="form-group">
                    <label for="bulk_file">Upload JSON File</label>
                    <input type="file" id="bulk_file" accept=".json" onchange="handleBulkFile()">
                    <small style="color: var(--text-muted);">Upload a JSON file with movie/series data</small>
                </div>
                
                <div id="bulk_preview" style="margin-top: 20px;"></div>
                
                <form method="POST" id="bulkImportForm" style="margin-top: 20px; display: none;">
                    <input type="hidden" name="action" value="bulk_import">
                    <input type="hidden" id="bulk_data" name="bulk_data">
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Import All Items
                    </button>
                </form>
            </div>

            <!-- Export Data Tab -->
            <div class="tab-content" id="export-data">
                <div class="form-group">
                    <label>Export Options</label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button class="btn btn-primary" onclick="exportData('json')">
                            <i class="fas fa-download"></i> Export as JSON
                        </button>
                        <button class="btn btn-secondary" onclick="exportData('csv')">
                            <i class="fas fa-table"></i> Export as CSV
                        </button>
                        <button class="btn btn-warning" onclick="exportData('backup')">
                            <i class="fas fa-archive"></i> Full Database Backup
                        </button>
                    </div>
                </div>
                
                <div id="export_progress" class="progress-bar" style="display: none;">
                    <div class="progress-fill" id="export_progress_fill"></div>
                </div>
            </div>

            <!-- Manage Content Tab -->
            <div class="tab-content" id="manage-content">
                <div class="form-group">
                    <label for="content_filter">Filter Content</label>
                    <select id="content_filter" onchange="filterContent()">
                        <option value="all">All Content</option>
                        <option value="movie">Movies</option>
                        <option value="series">TV Series</option>
                        <option value="live">Live TV</option>
                    </select>
                </div>
                
                <div id="content_list" class="movie-list">
                    <?php foreach ($recentMovies as $movie): ?>
                        <div class="movie-item" data-type="<?php echo $movie['type']; ?>">
                            <div class="movie-header">
                                <img src="<?php echo $movie['poster'] ?: 'https://via.placeholder.com/70x100/2d2d2d/ffffff?text=No+Image'; ?>" 
                                     alt="<?php echo $movie['title']; ?>" class="movie-poster">
                                <div class="movie-info">
                                    <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
                                    <div class="movie-meta">
                                        <?php echo $movie['year']; ?> • <?php echo htmlspecialchars($movie['genre']); ?> • ⭐ <?php echo $movie['rating']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="movie-actions">
                                <button class="btn btn-primary" onclick="editMovie(<?php echo $movie['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger" onclick="deleteMovie(<?php echo $movie['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="#" class="nav-tab active" onclick="showTab('add-movie')">
            <i class="fas fa-plus"></i>
            <span>Add</span>
        </a>
        <a href="#" class="nav-tab" onclick="showTab('youtube-import')">
            <i class="fab fa-youtube"></i>
            <span>YouTube</span>
        </a>
        <a href="#" class="nav-tab" onclick="showTab('tmdb-import')">
            <i class="fas fa-database"></i>
            <span>TMDB</span>
        </a>
        <a href="#" class="nav-tab" onclick="showTab('manage-content')">
            <i class="fas fa-list"></i>
            <span>Manage</span>
        </a>
        <a href="index.php" class="nav-tab">
            <i class="fas fa-home"></i>
            <span>Site</span>
        </a>
    </nav>

    <script>
        // Tab Management
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Update bottom navigation
            document.querySelectorAll('.bottom-nav .nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Update bottom nav based on tab
            const bottomNavMap = {
                'add-movie': 0,
                'youtube-import': 1,
                'tmdb-import': 2,
                'manage-content': 3
            };
            
            if (bottomNavMap[tabName] !== undefined) {
                document.querySelectorAll('.bottom-nav .nav-tab')[bottomNavMap[tabName]].classList.add('active');
            }
        }

        // Server Management
        function addServer() {
            const container = document.getElementById('serverContainer');
            const serverItem = document.createElement('div');
            serverItem.className = 'server-item';
            serverItem.style.cssText = 'margin-bottom: 15px; padding: 15px; background: var(--surface-light); border-radius: 8px;';
            serverItem.innerHTML = `
                <div class="form-grid">
                    <div>
                        <label>Server Name</label>
                        <input type="text" name="server_name[]" placeholder="Server ${container.children.length + 1}" required>
                    </div>
                    <div>
                        <label>Server URL</label>
                        <input type="url" name="server_url[]" placeholder="https://..." required>
                    </div>
                    <div>
                        <label>Quality</label>
                        <select name="server_quality[]">
                            <option value="480p">480p</option>
                            <option value="720p">720p</option>
                            <option value="1080p">1080p</option>
                            <option value="4K">4K</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <label style="margin-right: 10px;">Primary</label>
                        <input type="checkbox" name="server_primary[]">
                    </div>
                </div>
                <button type="button" class="btn btn-danger" onclick="removeServer(this)" style="margin-top: 10px;">
                    <i class="fas fa-trash"></i> Remove Server
                </button>
            `;
            container.appendChild(serverItem);
        }

        function removeServer(button) {
            button.closest('.server-item').remove();
        }

        // YouTube Integration
        async function searchYouTube() {
            const query = document.getElementById('youtube_search').value;
            if (!query) return;
            
            const resultsContainer = document.getElementById('youtube_results');
            resultsContainer.innerHTML = '<div style="text-align: center; padding: 20px;">Searching...</div>';
            
            try {
                // Note: YouTube API integration would go here
                // This is a placeholder for the actual API call
                const mockResults = [
                    {
                        id: 'dQw4w9WgXcQ',
                        title: 'Sample Movie Trailer',
                        description: 'A great movie trailer',
                        thumbnail: 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg'
                    }
                ];
                
                displayYouTubeResults(mockResults);
            } catch (error) {
                resultsContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--danger);">Error searching YouTube</div>';
            }
        }

        function displayYouTubeResults(results) {
            const container = document.getElementById('youtube_results');
            container.innerHTML = results.map(result => `
                <div class="search-result-item" onclick="selectYouTubeResult(${JSON.stringify(result).replace(/"/g, '&quot;')})">
                    <img src="${result.thumbnail}" alt="${result.title}" class="search-result-img">
                    <div class="search-result-info">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-meta">YouTube Video</div>
                    </div>
                </div>
            `).join('');
        }

        function selectYouTubeResult(result) {
            document.getElementById('youtube_data').value = JSON.stringify(result);
            document.getElementById('youtubeImportForm').style.display = 'block';
            
            // Auto-fill the add movie form
            document.getElementById('title').value = result.title;
            document.getElementById('description').value = result.description;
            document.getElementById('trailer_url').value = `https://youtube.com/watch?v=${result.id}`;
            
            showNotification('YouTube video selected for import');
        }

        // TMDB Integration
        async function searchTMDB() {
            const query = document.getElementById('tmdb_search').value;
            if (!query) return;
            
            const resultsContainer = document.getElementById('tmdb_results');
            resultsContainer.innerHTML = '<div style="text-align: center; padding: 20px;">Searching TMDB...</div>';
            
            try {
                // Note: TMDB API integration would go here
                const mockResults = [
                    {
                        id: 12345,
                        title: 'Sample Movie',
                        release_date: '2024-01-01',
                        overview: 'A great movie overview',
                        poster_path: 'https://via.placeholder.com/300x450',
                        vote_average: 8.5
                    }
                ];
                
                displayTMDBResults(mockResults);
            } catch (error) {
                resultsContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--danger);">Error searching TMDB</div>';
            }
        }

        function displayTMDBResults(results) {
            const container = document.getElementById('tmdb_results');
            container.innerHTML = results.map(result => `
                <div class="search-result-item" onclick="selectTMDBResult(${JSON.stringify(result).replace(/"/g, '&quot;')})">
                    <img src="${result.poster_path}" alt="${result.title}" class="search-result-img">
                    <div class="search-result-info">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-meta">${result.release_date} • ⭐ ${result.vote_average}</div>
                    </div>
                </div>
            `).join('');
        }

        function selectTMDBResult(result) {
            document.getElementById('tmdb_data').value = JSON.stringify(result);
            document.getElementById('tmdbImportForm').style.display = 'block';
            
            // Auto-fill the add movie form
            document.getElementById('title').value = result.title;
            document.getElementById('description').value = result.overview;
            document.getElementById('year').value = new Date(result.release_date).getFullYear();
            document.getElementById('rating').value = result.vote_average;
            document.getElementById('imdb_id').value = result.id;
            
            showNotification('TMDB content selected for import');
        }

        // Bulk Import
        function handleBulkFile() {
            const file = document.getElementById('bulk_file').files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    const preview = document.getElementById('bulk_preview');
                    
                    preview.innerHTML = `
                        <h4>Preview (${data.length} items)</h4>
                        <div class="search-results">
                            ${data.slice(0, 5).map(item => `
                                <div class="search-result-item">
                                    <div class="search-result-info">
                                        <div class="search-result-title">${item.title || 'Untitled'}</div>
                                        <div class="search-result-meta">${item.type || 'Unknown'} • ${item.year || 'Unknown'}</div>
                                    </div>
                                </div>
                            `).join('')}
                            ${data.length > 5 ? `<div style="text-align: center; padding: 10px; color: var(--text-muted);">...and ${data.length - 5} more items</div>` : ''}
                        </div>
                    `;
                    
                    document.getElementById('bulk_data').value = JSON.stringify(data);
                    document.getElementById('bulkImportForm').style.display = 'block';
                    
                } catch (error) {
                    preview.innerHTML = '<div style="color: var(--danger);">Invalid JSON file</div>';
                }
            };
            reader.readAsText(file);
        }

        // Export Functions
        function exportData(format) {
            const progressBar = document.getElementById('export_progress');
            const progressFill = document.getElementById('export_progress_fill');
            
            progressBar.style.display = 'block';
            progressFill.style.width = '0%';
            
            // Simulate export progress
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                progressFill.style.width = progress + '%';
                
                if (progress >= 100) {
                    clearInterval(interval);
                    progressBar.style.display = 'none';
                    
                    // Create download link
                    const link = document.createElement('a');
                    link.href = `/api/export.php?format=${format}`;
                    link.download = `cinecraze_export_${Date.now()}.${format}`;
                    link.click();
                    
                    showNotification(`Data exported as ${format.toUpperCase()}`);
                }
            }, 200);
        }

        // Content Management
        function editMovie(movieId) {
            // Redirect to edit page or open modal
            window.location.href = `edit_movie.php?id=${movieId}`;
        }

        function deleteMovie(movieId) {
            if (confirm('Are you sure you want to delete this content?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_movie">
                    <input type="hidden" name="movie_id" value="${movieId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function filterContent() {
            const filter = document.getElementById('content_filter').value;
            const items = document.querySelectorAll('.movie-item');
            
            items.forEach(item => {
                if (filter === 'all' || item.dataset.type === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Utility Functions
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'status-message status-success';
            notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                min-width: 300px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>