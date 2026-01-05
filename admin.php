<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin-login.php');
    exit();
}

require_once 'config.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: admin-login.php');
    exit();
}

$adminData = [];
try {
    // Get admin dashboard data
    $response = file_get_contents('http://localhost/api.php?action=get_admin_data');
    $apiResponse = json_decode($response, true);
    if ($apiResponse['success']) {
        $adminData = $apiResponse['data'];
    }
} catch (Exception $e) {
    $error = 'Failed to load admin data: ' . $e->getMessage();
}

$tmdbApiKey = getSetting('tmdb_api_key');
$githubToken = getSetting('github_token');
$githubRepo = getSetting('github_repo');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#e50914">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
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
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --shadow: 0 8px 32px rgba(0,0,0,0.4);
            --shadow-hover: 0 16px 48px rgba(0,0,0,0.6);
            --shadow-primary: 0 8px 32px rgba(229, 9, 20, 0.3);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--surface) 0%, var(--background) 70%, var(--secondary) 100%);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header */
        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 20px clamp(20px, 5vw, 40px);
            box-shadow: var(--shadow-primary);
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
            gap: 15px;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .logo i {
            font-size: 1.2em;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d3040f;
        }

        /* Main Container */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: clamp(20px, 4vw, 40px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(20px, 3vw, 30px);
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(20px, 4vw, 30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            font-weight: 600;
            color: var(--text-secondary);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-value {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            color: var(--text);
            margin-bottom: 10px;
        }

        .stat-subtitle {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Navigation Tabs */
        .nav-tabs {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: 10px;
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-tab {
            padding: 12px 20px;
            border-radius: var(--border-radius-sm);
            background: transparent;
            color: var(--text-secondary);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tab:hover,
        .nav-tab.active {
            background: var(--primary);
            color: white;
        }

        /* Content Sections */
        .content-section {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(20px, 4vw, 30px);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .section-title {
            font-size: clamp(1.3rem, 3vw, 1.8rem);
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            background: var(--surface-light);
            color: var(--text);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table th {
            background: var(--surface-light);
            font-weight: 600;
            color: var(--text-secondary);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Search and Filters */
        .search-filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            background: var(--surface-light);
            color: var(--text);
            font-size: 14px;
        }

        .filter-select {
            min-width: 150px;
        }

        /* Status Indicators */
        .status {
            padding: 8px 16px;
            border-radius: var(--border-radius-sm);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status.success {
            background: rgba(70, 211, 105, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .status.warning {
            background: rgba(255, 165, 0, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status.error {
            background: rgba(244, 6, 18, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        /* Action Buttons */
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            margin: 2px;
        }

        .btn-edit {
            background: var(--accent);
            color: white;
        }

        .btn-edit:hover {
            background: var(--accent-dark);
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #d3040f;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-dark);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            border-radius: var(--border-radius);
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: var(--surface-light);
            color: var(--text);
        }

        .modal-body {
            padding: 30px;
        }

        /* Loading */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .loading i {
            font-size: 24px;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 20px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .search-filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                font-size: 12px;
            }
            
            .data-table th,
            .data-table td {
                padding: 8px;
            }
        }

        @media (max-width: 480px) {
            .admin-header {
                padding: 15px 20px;
            }
            
            .modal-content {
                margin: 10px;
                max-height: 95vh;
            }
            
            .modal-header,
            .modal-body {
                padding: 20px;
            }
        }

        /* Checkbox and Selection */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--surface-light);
            border-radius: 4px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            width: 0%;
            transition: width 0.3s ease;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-secondary);
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-primary {
            background: var(--primary);
            color: white;
        }

        .badge-success {
            background: var(--success);
            color: white;
        }

        .badge-warning {
            background: var(--warning);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="header-content">
            <a href="#" class="logo">
                <i class="fas fa-cogs"></i>
                <?php echo APP_NAME; ?> Admin
            </a>
            <div class="header-actions">
                <a href="../index.php" class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    View Site
                </a>
                <a href="?action=logout" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="admin-container">
        <?php if (isset($error)): ?>
            <div class="content-section">
                <div class="status error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Content</div>
                        <div class="stat-icon">
                            <i class="fas fa-film"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($adminData['total_content'] ?? 0); ?></div>
                    <div class="stat-subtitle">Movies, Series & Live TV</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Movies</div>
                        <div class="stat-icon">
                            <i class="fas fa-video"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($adminData['by_type']['movie'] ?? 0); ?></div>
                    <div class="stat-subtitle">Available movies</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">TV Series</div>
                        <div class="stat-icon">
                            <i class="fas fa-tv"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($adminData['by_type']['series'] ?? 0); ?></div>
                    <div class="stat-subtitle">TV series episodes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Live TV</div>
                        <div class="stat-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($adminData['by_type']['live'] ?? 0); ?></div>
                    <div class="stat-subtitle">Live channels</div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="nav-tabs">
                <button class="nav-tab active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </button>
                <button class="nav-tab" data-tab="content">
                    <i class="fas fa-folder-open"></i>
                    Content Management
                </button>
                <button class="nav-tab" data-tab="tmdb">
                    <i class="fas fa-search"></i>
                    TMDB Integration
                </button>
                <button class="nav-tab" data-tab="auto-embed">
                    <i class="fas fa-magic"></i>
                    Auto Embed
                </button>
                <button class="nav-tab" data-tab="export">
                    <i class="fas fa-download"></i>
                    Export/Import
                </button>
                <button class="nav-tab" data-tab="settings">
                    <i class="fas fa-cog"></i>
                    Settings
                </button>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard-tab" class="tab-content">
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-chart-line"></i>
                            Recent Content
                        </h2>
                    </div>
                    
                    <?php if (!empty($adminData['recent_content'])): ?>
                        <div class="data-table-container" style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Year</th>
                                        <th>Rating</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adminData['recent_content'] as $content): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($content['title']); ?></strong>
                                                <br>
                                                <small style="color: var(--text-muted);">
                                                    <?php echo htmlspecialchars(substr($content['description'] ?? '', 0, 100)); ?>...
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $content['type'] === 'movie' ? 'primary' : ($content['type'] === 'series' ? 'success' : 'warning'); ?>">
                                                    <?php echo ucfirst($content['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $content['year'] ?? 'N/A'; ?></td>
                                            <td><?php echo $content['rating'] ?? 'N/A'; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($content['created_at'])); ?></td>
                                            <td>
                                                <button class="action-btn btn-view" onclick="viewContent(<?php echo $content['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn btn-edit" onclick="editContent(<?php echo $content['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn btn-delete" onclick="deleteContent(<?php echo $content['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-film"></i>
                            <h3>No Content Yet</h3>
                            <p>Start by adding some movies, series, or live TV content.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Management Tab -->
            <div id="content-tab" class="tab-content" style="display: none;">
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-folder-open"></i>
                            Content Management
                        </h2>
                        <button class="btn btn-primary" onclick="openAddContentModal()">
                            <i class="fas fa-plus"></i>
                            Add Content
                        </button>
                    </div>
                    
                    <div class="search-filter-bar">
                        <input type="text" class="search-input" placeholder="Search content..." id="contentSearch">
                        <select class="form-select filter-select" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="movie">Movies</option>
                            <option value="series">TV Series</option>
                            <option value="live">Live TV</option>
                        </select>
                        <button class="btn btn-primary" onclick="searchContent()">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                    </div>
                    
                    <div id="contentList">
                        <!-- Content list will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- TMDB Integration Tab -->
            <div id="tmdb-tab" class="tab-content" style="display: none;">
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-search"></i>
                            TMDB Integration
                        </h2>
                        <?php if (!$tmdbApiKey): ?>
                            <span class="status warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                TMDB API Key Required
                            </span>
                        <?php else: ?>
                            <span class="status success">
                                <i class="fas fa-check-circle"></i>
                                TMDB Connected
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Search Type</label>
                            <select class="form-select" id="tmdbSearchType">
                                <option value="movie">Movies</option>
                                <option value="series">TV Series</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Search Query</label>
                            <input type="text" class="form-input" id="tmdbSearchQuery" placeholder="Enter movie or series title">
                        </div>
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary" onclick="searchTMDB()">
                                <i class="fas fa-search"></i>
                                Search TMDB
                            </button>
                        </div>
                    </div>
                    
                    <div id="tmdbResults">
                        <!-- TMDB search results will appear here -->
                    </div>
                </div>
            </div>

            <!-- Auto Embed Tab -->
            <div id="auto-embed-tab" class="tab-content" style="display: none;">
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-magic"></i>
                            Auto Embed Sources
                        </h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">TMDB ID</label>
                            <input type="number" class="form-input" id="autoEmbedTmdbId" placeholder="Enter TMDB ID">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Content Type</label>
                            <select class="form-select" id="autoEmbedType">
                                <option value="movie">Movie</option>
                                <option value="series">TV Series</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary" onclick="applyAutoEmbed()">
                                <i class="fas fa-magic"></i>
                                Apply Auto Embed
                            </button>
                        </div>
                    </div>
                    
                    <div class="content-section">
                        <h3 style="margin-bottom: 20px;">Available Embed Sources</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" class="checkbox" id="vidsrc" checked disabled>
                                    <label for="vidsrc">VidSrc (Primary)</label>
                                </div>
                                <small style="color: var(--text-muted);">https://vidsrc.net/embed/</small>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" class="checkbox" id="multiembed" checked disabled>
                                    <label for="multiembed">MultiEmbed</label>
                                </div>
                                <small style="color: var(--text-muted);">https://multiembed.mov/?video_id={tmdb_id}&tmdb=1</small>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" class="checkbox" id="vidjoy" checked disabled>
                                    <label for="vidjoy">VidJoy</label>
                                </div>
                                <small style="color: var(--text-muted);">https://vidjoy.pro/embed/</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export/Import Tab -->
            <div id="export-tab" class="tab-content" style="display: none;">
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-download"></i>
                            Export & Import
                        </h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <h3>Export Data</h3>
                            <p style="color: var(--text-muted); margin-bottom: 15px;">
                                Export all content data in JSON format for backup or migration.
                            </p>
                            <button class="btn btn-primary" onclick="exportData()">
                                <i class="fas fa-download"></i>
                                Export JSON
                            </button>
                        </div>
                        
                        <div class="form-group">
                            <h3>Import Data</h3>
                            <p style="color: var(--text-muted); margin-bottom: 15px;">
                                Import content data from a JSON file.
                            </p>
                            <input type="file" class="form-input" id="importFile" accept=".json">
                            <button class="btn btn-primary" onclick="importData()" style="margin-top: 10px;">
                                <i class="fas fa-upload"></i>
                                Import Data
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($githubToken && $githubRepo): ?>
                        <div class="content-section">
                            <h3>GitHub Integration</h3>
                            <p style="color: var(--text-muted); margin-bottom: 20px;">
                                Auto-export data to GitHub repository.
                            </p>
                            <button class="btn btn-primary" onclick="exportToGitHub()">
                                <i class="fab fa-github"></i>
                                Export to GitHub
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settings-tab" class="tab-content" style="display: none;">
                <div class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-cog"></i>
                            Settings
                        </h2>
                    </div>
                    
                    <form id="settingsForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">TMDB API Key</label>
                                <input type="text" class="form-input" name="tmdb_api_key" 
                                       value="<?php echo htmlspecialchars($tmdbApiKey); ?>" 
                                       placeholder="Enter TMDB API Key">
                                <small style="color: var(--text-muted);">
                                    Get your API key from <a href="https://www.themoviedb.org/settings/api" target="_blank" style="color: var(--primary);">TMDB</a>
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">GitHub Token (Optional)</label>
                                <input type="text" class="form-input" name="github_token" 
                                       value="<?php echo htmlspecialchars($githubToken); ?>" 
                                       placeholder="Enter GitHub Personal Access Token">
                                <small style="color: var(--text-muted);">
                                    Required for auto-export to GitHub
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">GitHub Repository</label>
                                <input type="text" class="form-input" name="github_repo" 
                                       value="<?php echo htmlspecialchars($githubRepo); ?>" 
                                       placeholder="username/repository">
                                <small style="color: var(--text-muted);">
                                    Format: username/repository-name
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Theme</label>
                                <select class="form-select" name="theme">
                                    <option value="dark" <?php echo (getSetting('theme') === 'dark') ? 'selected' : ''; ?>>Dark</option>
                                    <option value="light" <?php echo (getSetting('theme') === 'light') ? 'selected' : ''; ?>>Light</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Auto Update</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" class="checkbox" name="auto_update" value="1" 
                                           <?php echo (getSetting('auto_update') === '1') ? 'checked' : ''; ?>>
                                    <label>Enable automatic content updates</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Settings
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Content Modal -->
    <div class="modal" id="contentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="contentModalTitle">Add Content</h3>
                <button class="modal-close" onclick="closeContentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="contentForm">
                    <input type="hidden" id="contentId" name="id">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Title *</label>
                            <input type="text" class="form-input" id="contentTitle" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Type *</label>
                            <select class="form-select" id="contentType" name="type" required>
                                <option value="">Select Type</option>
                                <option value="movie">Movie</option>
                                <option value="series">TV Series</option>
                                <option value="live">Live TV</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-input" id="contentYear" name="year" min="1900" max="2030">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Rating</label>
                            <input type="number" class="form-input" id="contentRating" name="rating" min="0" max="10" step="0.1">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Genre/Subcategory</label>
                            <input type="text" class="form-input" id="contentSubCategory" name="sub_category">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-input" id="contentCountry" name="country">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Duration</label>
                            <input type="text" class="form-input" id="contentDuration" name="duration" placeholder="e.g., 120 min">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Poster URL</label>
                            <input type="url" class="form-input" id="contentPoster" name="poster">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" class="form-input" id="contentThumbnail" name="thumbnail">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">TMDB ID</label>
                            <input type="number" class="form-input" id="contentTmdbId" name="tmdb_id">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="contentDescription" name="description" rows="4"></textarea>
                    </div>
                    
                    <!-- Servers Section -->
                    <div class="form-group">
                        <label class="form-label">Video Sources</label>
                        <div id="serversContainer">
                            <!-- Server inputs will be added here -->
                        </div>
                        <button type="button" class="btn btn-primary" onclick="addServer()" style="margin-top: 10px;">
                            <i class="fas fa-plus"></i>
                            Add Server
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Content
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Tab Management
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.dataset.tab;
                
                // Update active tab
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Show/hide content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                document.getElementById(tabName + '-tab').style.display = 'block';
                
                // Load tab-specific content
                loadTabContent(tabName);
            });
        });

        function loadTabContent(tabName) {
            switch (tabName) {
                case 'content':
                    loadContentList();
                    break;
                case 'tmdb':
                    // TMDB tab is self-contained
                    break;
                case 'auto-embed':
                    // Auto-embed tab is self-contained
                    break;
                case 'export':
                    // Export tab is self-contained
                    break;
                case 'settings':
                    // Settings tab is self-contained
                    break;
            }
        }

        // Content Management
        async function loadContentList() {
            const container = document.getElementById('contentList');
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading content...</div>';
            
            try {
                const response = await fetch('api.php?action=get_content&page=1');
                const data = await response.json();
                
                if (data.success) {
                    renderContentList(data.content);
                } else {
                    container.innerHTML = '<div class="status error">Failed to load content</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="status error">Error loading content: ' + error.message + '</div>';
            }
        }

        function renderContentList(content) {
            const container = document.getElementById('contentList');
            
            if (content.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No Content Found</h3>
                        <p>Add some content to get started.</p>
                    </div>
                `;
                return;
            }
            
            const table = `
                <div class="data-table-container" style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Year</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${content.map(item => `
                                <tr>
                                    <td>
                                        <strong>${item.title}</strong>
                                        <br>
                                        <small style="color: var(--text-muted);">
                                            ${item.sub_category || 'No genre'} • ${item.country || 'No country'}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-${item.type === 'movie' ? 'primary' : (item.type === 'series' ? 'success' : 'warning')}">
                                            ${item.type}
                                        </span>
                                    </td>
                                    <td>${item.year || 'N/A'}</td>
                                    <td>${item.rating || 'N/A'}</td>
                                    <td>
                                        <button class="action-btn btn-view" onclick="viewContent(${item.id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn btn-edit" onclick="editContent(${item.id})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn btn-delete" onclick="deleteContent(${item.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = table;
        }

        function openAddContentModal() {
            document.getElementById('contentModalTitle').textContent = 'Add Content';
            document.getElementById('contentForm').reset();
            document.getElementById('contentId').value = '';
            document.getElementById('serversContainer').innerHTML = '';
            addServer(); // Add first server input
            document.getElementById('contentModal').classList.add('active');
        }

        function editContent(id) {
            // Load content data and populate form
            fetch(`api.php?action=get_content&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = data.content;
                        document.getElementById('contentModalTitle').textContent = 'Edit Content';
                        document.getElementById('contentId').value = content.id;
                        document.getElementById('contentTitle').value = content.title;
                        document.getElementById('contentType').value = content.type;
                        document.getElementById('contentYear').value = content.year || '';
                        document.getElementById('contentRating').value = content.rating || '';
                        document.getElementById('contentSubCategory').value = content.sub_category || '';
                        document.getElementById('contentCountry').value = content.country || '';
                        document.getElementById('contentDuration').value = content.duration || '';
                        document.getElementById('contentPoster').value = content.poster || '';
                        document.getElementById('contentThumbnail').value = content.thumbnail || '';
                        document.getElementById('contentTmdbId').value = content.tmdb_id || '';
                        document.getElementById('contentDescription').value = content.description || '';
                        
                        // Load servers
                        document.getElementById('serversContainer').innerHTML = '';
                        if (content.servers && content.servers.length > 0) {
                            content.servers.forEach(server => {
                                addServer(server.name, server.url);
                            });
                        } else {
                            addServer();
                        }
                        
                        document.getElementById('contentModal').classList.add('active');
                    }
                });
        }

        function closeContentModal() {
            document.getElementById('contentModal').classList.remove('active');
        }

        function addServer(name = '', url = '') {
            const container = document.getElementById('serversContainer');
            const serverIndex = container.children.length;
            
            const serverDiv = document.createElement('div');
            serverDiv.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';
            serverDiv.innerHTML = `
                <input type="text" class="form-input" placeholder="Server name" 
                       value="${name}" name="servers[${serverIndex}][name]" style="flex: 1;">
                <input type="url" class="form-input" placeholder="Server URL" 
                       value="${url}" name="servers[${serverIndex}][url]" style="flex: 2;">
                <button type="button" class="btn btn-danger" onclick="removeServer(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            container.appendChild(serverDiv);
        }

        function removeServer(button) {
            button.parentElement.remove();
        }

        async function saveContent(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            // Parse servers
            data.servers = [];
            const serverInputs = document.querySelectorAll('#serversContainer input[name^="servers"]');
            const serverGroups = {};
            
            serverInputs.forEach(input => {
                const match = input.name.match(/servers\[(\d+)\]\[(name|url)\]/);
                if (match) {
                    const index = match[1];
                    const field = match[2];
                    if (!serverGroups[index]) {
                        serverGroups[index] = {};
                    }
                    serverGroups[index][field] = input.value;
                }
            });
            
            for (const group of Object.values(serverGroups)) {
                if (group.name && group.url) {
                    data.servers.push(group);
                }
            }
            
            try {
                const response = await fetch('api.php?action=save_content', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Content saved successfully!');
                    closeContentModal();
                    loadContentList();
                } else {
                    alert('Error saving content: ' + result.message);
                }
            } catch (error) {
                alert('Error saving content: ' + error.message);
            }
        }

        async function deleteContent(id) {
            if (!confirm('Are you sure you want to delete this content?')) {
                return;
            }
            
            try {
                const response = await fetch(`api.php?action=delete_content&id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    alert('Content deleted successfully!');
                    loadContentList();
                } else {
                    alert('Error deleting content: ' + result.message);
                }
            } catch (error) {
                alert('Error deleting content: ' + error.message);
            }
        }

        // TMDB Integration
        async function searchTMDB() {
            const query = document.getElementById('tmdbSearchQuery').value;
            const type = document.getElementById('tmdbSearchType').value;
            
            if (!query) {
                alert('Please enter a search query');
                return;
            }
            
            const container = document.getElementById('tmdbResults');
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Searching TMDB...</div>';
            
            try {
                const response = await fetch(`api.php?action=fetch_tmdb&query=${encodeURIComponent(query)}&type=${type}`);
                const data = await response.json();
                
                if (data.success) {
                    renderTMDBResults(data.results);
                } else {
                    container.innerHTML = '<div class="status error">Error searching TMDB: ' + (data.error || 'Unknown error') + '</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="status error">Error searching TMDB: ' + error.message + '</div>';
            }
        }

        function renderTMDBResults(results) {
            const container = document.getElementById('tmdbResults');
            
            if (results.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i><h3>No results found</h3><p>Try a different search query.</p></div>';
                return;
            }
            
            const grid = document.createElement('div');
            grid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;';
            
            results.forEach(result => {
                const card = document.createElement('div');
                card.style.cssText = `
                    background: var(--surface-light);
                    border-radius: var(--border-radius-sm);
                    padding: 20px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                `;
                
                card.innerHTML = `
                    <div style="display: flex; gap: 15px;">
                        <img src="${result.poster_path || 'https://via.placeholder.com/150x225/333/fff?text=No+Image'}" 
                             alt="${result.title}" style="width: 80px; height: 120px; object-fit: cover; border-radius: 8px;">
                        <div style="flex: 1;">
                            <h4 style="margin-bottom: 8px; color: var(--text);">${result.title}</h4>
                            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 10px;">
                                ${result.release_date || 'No release date'} • ★ ${result.vote_average || 'N/A'}
                            </p>
                            <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 15px;">
                                ${result.overview || 'No description available.'}
                            </p>
                            <button class="btn btn-primary" onclick="importFromTMDB(${result.id})">
                                <i class="fas fa-download"></i>
                                Import
                            </button>
                        </div>
                    </div>
                `;
                
                grid.appendChild(card);
            });
            
            container.innerHTML = '';
            container.appendChild(grid);
        }

        async function importFromTMDB(tmdbId) {
            alert('Import functionality will be implemented to fetch detailed data from TMDB and save to database.');
        }

        // Auto Embed
        async function applyAutoEmbed() {
            const tmdbId = document.getElementById('autoEmbedTmdbId').value;
            const type = document.getElementById('autoEmbedType').value;
            
            if (!tmdbId) {
                alert('Please enter a TMDB ID');
                return;
            }
            
            try {
                const response = await fetch('api.php?action=auto_embed', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tmdb_id: parseInt(tmdbId),
                        content_ids: [] // This would be populated with selected content IDs
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Auto-embed applied to ${result.processed} items!`);
                } else {
                    alert('Error applying auto-embed: ' + result.message);
                }
            } catch (error) {
                alert('Error applying auto-embed: ' + error.message);
            }
        }

        // Export/Import
        async function exportData() {
            try {
                const response = await fetch('api.php?action=export_data');
                const result = await response.json();
                
                if (result.success) {
                    const dataStr = JSON.stringify(result.data, null, 2);
                    const dataBlob = new Blob([dataStr], { type: 'application/json' });
                    const url = URL.createObjectURL(dataBlob);
                    
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `cinecraze-export-${new Date().toISOString().split('T')[0]}.json`;
                    link.click();
                    
                    URL.revokeObjectURL(url);
                } else {
                    alert('Error exporting data: ' + result.message);
                }
            } catch (error) {
                alert('Error exporting data: ' + error.message);
            }
        }

        async function importData() {
            const fileInput = document.getElementById('importFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a file to import');
                return;
            }
            
            try {
                const text = await file.text();
                const data = JSON.parse(text);
                
                // This would need to be implemented to handle the import
                alert('Import functionality will parse the JSON and add content to database.');
                
            } catch (error) {
                alert('Error importing data: ' + error.message);
            }
        }

        // Settings
        document.getElementById('settingsForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const settings = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('admin-settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(settings)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error saving settings: ' + result.message);
                }
            } catch (error) {
                alert('Error saving settings: ' + error.message);
            }
        });

        // Utility Functions
        function viewContent(id) {
            window.open(`../index.php?id=${id}`, '_blank');
        }

        function searchContent() {
            const query = document.getElementById('contentSearch').value;
            const type = document.getElementById('typeFilter').value;
            
            // This would implement content search functionality
            console.log('Searching for:', query, 'Type:', type);
        }

        // Event Listeners
        document.getElementById('contentForm').addEventListener('submit', saveContent);
        
        // Close modal when clicking outside
        document.getElementById('contentModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                closeContentModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeContentModal();
            }
        });
    </script>
</body>
</html>