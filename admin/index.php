<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!file_exists('../config.php')) {
    header('Location: ../install.php');
    exit;
}

require_once '../config.php';
require_once '../includes/db.php';

$tmdb_api_key = getSetting('tmdb_api_key', '');
$site_name = getSetting('site_name', 'CineCraze');

$stats = [
    'total_content' => $conn->query("SELECT COUNT(*) as count FROM content")->fetch_assoc()['count'],
    'total_movies' => $conn->query("SELECT COUNT(*) as count FROM content WHERE type = 'movie'")->fetch_assoc()['count'],
    'total_series' => $conn->query("SELECT COUNT(*) as count FROM content WHERE type = 'series'")->fetch_assoc()['count'],
    'total_views' => $conn->query("SELECT SUM(views) as count FROM content")->fetch_assoc()['count'] ?? 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#e50914">
    <title>Admin Dashboard - <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e50914;
            --primary-dark: #b8070f;
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
            --sidebar-width: 280px;
            --header-height: 70px;
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --shadow: 0 8px 32px rgba(0,0,0,0.4);
            --shadow-hover: 0 16px 48px rgba(0,0,0,0.6);
            --shadow-primary: 0 8px 32px rgba(229, 9, 20, 0.3);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(ellipse at center, var(--surface) 0%, var(--background) 70%, var(--secondary) 100%);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar - Fully Responsive */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-light) 100%);
            border-right: 2px solid var(--primary);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: clamp(20px, 4vw, 30px);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-bottom: 2px solid var(--primary-dark);
        }
        
        .sidebar-header h1 {
            font-size: clamp(1.3rem, 3vw, 1.8rem);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header p {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            opacity: 0.9;
        }
        
        .sidebar-menu {
            padding: clamp(15px, 3vw, 20px);
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 15px);
            padding: clamp(12px, 2.5vw, 15px) clamp(15px, 3vw, 20px);
            margin-bottom: 8px;
            border-radius: var(--border-radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        .menu-item:hover, .menu-item.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .menu-item i {
            font-size: clamp(1.1rem, 2.5vw, 1.3rem);
            width: clamp(20px, 4vw, 24px);
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Top Bar - Responsive */
        .top-bar {
            background: var(--surface);
            border-bottom: 1px solid var(--surface-light);
            padding: clamp(15px, 3vw, 20px) clamp(20px, 4vw, 30px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            gap: clamp(10px, 2vw, 20px);
            flex-wrap: wrap;
        }
        
        .menu-toggle {
            display: none;
            background: var(--surface-light);
            border: none;
            color: var(--text);
            width: clamp(40px, 8vw, 50px);
            height: clamp(40px, 8vw, 50px);
            border-radius: 8px;
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: var(--primary);
        }
        
        .top-bar-title {
            font-size: clamp(1.3rem, 3.5vw, 1.8rem);
            font-weight: 700;
        }
        
        .top-bar-actions {
            display: flex;
            gap: clamp(10px, 2vw, 15px);
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: clamp(12px, 2.5vw, 14px) clamp(20px, 4vw, 24px);
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: clamp(0.875rem, 2vw, 1rem);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: clamp(8px, 1.5vw, 10px);
            text-align: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary);
        }
        
        .btn-secondary {
            background: var(--surface-light);
            color: var(--text);
        }
        
        .btn-secondary:hover {
            background: var(--accent);
            color: var(--background);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-sm {
            padding: clamp(8px, 1.5vw, 10px) clamp(12px, 2.5vw, 16px);
            font-size: clamp(0.8rem, 1.8vw, 0.9rem);
        }
        
        /* Content Area - Responsive */
        .content-area {
            padding: clamp(20px, 4vw, 30px);
        }
        
        /* Stats Cards - Fully Responsive */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(clamp(200px, 35vw, 250px), 1fr));
            gap: clamp(15px, 3vw, 20px);
            margin-bottom: clamp(25px, 5vw, 30px);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-light) 100%);
            border-radius: var(--border-radius);
            padding: clamp(20px, 4vw, 25px);
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
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: clamp(10px, 2vw, 15px);
        }
        
        .stat-card-title {
            font-size: clamp(0.875rem, 2vw, 1rem);
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .stat-card-icon {
            width: clamp(40px, 8vw, 50px);
            height: clamp(40px, 8vw, 50px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1.2rem, 3vw, 1.5rem);
        }
        
        .stat-card-icon.red { background: rgba(229, 9, 20, 0.2); color: var(--primary); }
        .stat-card-icon.blue { background: rgba(0, 212, 255, 0.2); color: var(--accent); }
        .stat-card-icon.green { background: rgba(70, 211, 105, 0.2); color: var(--success); }
        .stat-card-icon.orange { background: rgba(255, 165, 0, 0.2); color: var(--warning); }
        
        .stat-card-value {
            font-size: clamp(1.8rem, 5vw, 2.5rem);
            font-weight: 700;
            color: var(--text);
        }
        
        /* Card Component - Responsive */
        .card {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(20px, 4vw, 30px);
            margin-bottom: clamp(20px, 4vw, 30px);
            box-shadow: var(--shadow);
            border: 1px solid var(--surface-light);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: clamp(20px, 4vw, 25px);
            flex-wrap: wrap;
            gap: clamp(10px, 2vw, 15px);
        }
        
        .card-title {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 12px);
        }
        
        /* Form - Fully Responsive */
        .form-group {
            margin-bottom: clamp(20px, 4vw, 25px);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(clamp(200px, 40%, 300px), 1fr));
            gap: clamp(15px, 3vw, 20px);
            margin-bottom: clamp(20px, 4vw, 25px);
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        
        input, select, textarea {
            width: 100%;
            padding: clamp(12px, 2.5vw, 14px) clamp(14px, 3vw, 16px);
            border: 2px solid var(--surface-light);
            border-radius: var(--border-radius-sm);
            background: var(--background);
            color: var(--text);
            font-size: clamp(0.875rem, 2vw, 1rem);
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(229, 9, 20, 0.15);
            background: var(--surface);
        }
        
        textarea {
            resize: vertical;
            min-height: clamp(100px, 20vw, 120px);
        }
        
        /* Table - Fully Responsive */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius);
            margin-bottom: clamp(20px, 4vw, 30px);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        
        thead {
            background: var(--surface-light);
        }
        
        th, td {
            padding: clamp(12px, 2.5vw, 15px);
            text-align: left;
            border-bottom: 1px solid var(--surface-light);
        }
        
        th {
            font-weight: 600;
            color: var(--text-secondary);
            white-space: nowrap;
        }
        
        tbody tr {
            transition: background 0.2s ease;
        }
        
        tbody tr:hover {
            background: var(--surface-light);
        }
        
        .table-actions {
            display: flex;
            gap: clamp(5px, 1vw, 8px);
            flex-wrap: wrap;
        }
        
        /* Modal - Responsive */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            overflow-y: auto;
            padding: clamp(15px, 3vw, 20px);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--surface);
            border-radius: var(--border-radius);
            padding: clamp(25px, 5vw, 35px);
            max-width: clamp(500px, 90vw, 800px);
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: clamp(20px, 4vw, 25px);
        }
        
        .modal-title {
            font-size: clamp(1.3rem, 3.5vw, 1.8rem);
            font-weight: 700;
        }
        
        .close-modal {
            background: var(--danger);
            border: none;
            color: white;
            width: clamp(35px, 6vw, 40px);
            height: clamp(35px, 6vw, 40px);
            border-radius: 50%;
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            transform: scale(1.1);
        }
        
        /* Alert */
        .alert {
            padding: clamp(12px, 2.5vw, 15px) clamp(15px, 3vw, 20px);
            border-radius: var(--border-radius-sm);
            margin-bottom: clamp(15px, 3vw, 20px);
            font-weight: 600;
            font-size: clamp(0.875rem, 2vw, 1rem);
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 12px);
        }
        
        .alert-success { background: rgba(70, 211, 105, 0.1); color: var(--success); border-left-color: var(--success); }
        .alert-warning { background: rgba(255, 165, 0, 0.1); color: var(--warning); border-left-color: var(--warning); }
        .alert-danger { background: rgba(244, 6, 18, 0.1); color: var(--danger); border-left-color: var(--danger); }
        .alert-info { background: rgba(0, 212, 255, 0.1); color: var(--accent); border-left-color: var(--accent); }
        
        /* Loading */
        .loading-spinner {
            display: inline-block;
            width: clamp(20px, 4vw, 24px);
            height: clamp(20px, 4vw, 24px);
            border: 3px solid var(--surface-light);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Smartphone (320px - 480px) */
        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .top-bar {
                padding: 15px;
            }
            
            .content-area {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            th, td {
                padding: 8px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .top-bar-actions {
                width: 100%;
            }
        }
        
        /* Tablet (481px - 768px) */
        @media (min-width: 481px) and (max-width: 768px) {
            .sidebar {
                width: 250px;
            }
            
            :root {
                --sidebar-width: 250px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .sidebar.hidden ~ .main-content {
                margin-left: 0;
            }
        }
        
        /* Laptop and above (769px+) */
        @media (min-width: 769px) {
            .sidebar.hidden {
                transform: translateX(0);
            }
        }
        
        /* Large TV (1920px+) */
        @media (min-width: 1920px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            :root {
                --sidebar-width: 320px;
            }
            
            .sidebar {
                width: 320px;
            }
        }
        
        /* 4K TV (2560px+) */
        @media (min-width: 2560px) {
            :root {
                --sidebar-width: 400px;
            }
            
            .sidebar {
                width: 400px;
            }
            
            body {
                font-size: 18px;
            }
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-film"></i> <?php echo htmlspecialchars($site_name); ?></h1>
                <p>Admin Dashboard</p>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#" class="menu-item active" data-section="dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="menu-item" data-section="content">
                    <i class="fas fa-video"></i>
                    <span>Content</span>
                </a>
                <a href="#" class="menu-item" data-section="add-content">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Content</span>
                </a>
                <a href="#" class="menu-item" data-section="tmdb">
                    <i class="fas fa-search"></i>
                    <span>TMDB Import</span>
                </a>
                <a href="#" class="menu-item" data-section="categories">
                    <i class="fas fa-folder"></i>
                    <span>Categories</span>
                </a>
                <a href="#" class="menu-item" data-section="settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../index.php" class="menu-item" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Visit Site</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Bar -->
            <div class="top-bar">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 class="top-bar-title" id="pageTitle">Dashboard</h2>
                <div class="top-bar-actions">
                    <span style="color: var(--text-secondary); font-size: clamp(0.875rem, 2vw, 1rem);">
                        Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                    </span>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area" id="contentArea">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Total Content</span>
                                <div class="stat-card-icon red">
                                    <i class="fas fa-video"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo $stats['total_content']; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Movies</span>
                                <div class="stat-card-icon blue">
                                    <i class="fas fa-film"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo $stats['total_movies']; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Series</span>
                                <div class="stat-card-icon green">
                                    <i class="fas fa-tv"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo $stats['total_series']; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Total Views</span>
                                <div class="stat-card-icon orange">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?php echo number_format($stats['total_views']); ?></div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i>
                                Quick Actions
                            </h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(clamp(200px, 40%, 250px), 1fr)); gap: clamp(15px, 3vw, 20px);">
                            <button class="btn btn-primary" onclick="showSection('add-content')">
                                <i class="fas fa-plus"></i> Add New Content
                            </button>
                            <button class="btn btn-secondary" onclick="showSection('tmdb')">
                                <i class="fas fa-search"></i> Import from TMDB
                            </button>
                            <button class="btn btn-success" onclick="showSection('categories')">
                                <i class="fas fa-folder"></i> Manage Categories
                            </button>
                            <button class="btn btn-primary" onclick="showSection('settings')">
                                <i class="fas fa-cog"></i> Settings
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Content Section -->
                <div id="content-section" class="section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-video"></i>
                                Manage Content
                            </h3>
                            <button class="btn btn-primary btn-sm" onclick="showSection('add-content')">
                                <i class="fas fa-plus"></i> Add New
                            </button>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" id="contentSearch" placeholder="Search content..." style="margin-bottom: 0;">
                            </div>
                            <div class="form-group">
                                <select id="contentTypeFilter" style="margin-bottom: 0;">
                                    <option value="">All Types</option>
                                    <option value="movie">Movies</option>
                                    <option value="series">Series</option>
                                    <option value="live">Live</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Poster</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Year</th>
                                        <th>Views</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="contentTableBody">
                                    <tr>
                                        <td colspan="6" style="text-align: center;">
                                            <div class="loading-spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Add Content Section -->
                <div id="add-content-section" class="section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i>
                                Add New Content
                            </h3>
                        </div>
                        
                        <form id="addContentForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Title *</label>
                                    <input type="text" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label>Type *</label>
                                    <select name="type" id="contentType" required>
                                        <option value="movie">Movie</option>
                                        <option value="series">Series</option>
                                        <option value="live">Live</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Year</label>
                                    <input type="number" name="year" min="1900" max="2100">
                                </div>
                                <div class="form-group">
                                    <label>Rating</label>
                                    <input type="number" name="rating" min="0" max="10" step="0.1">
                                </div>
                                <div class="form-group">
                                    <label>Duration</label>
                                    <input type="text" name="duration" placeholder="e.g., 2h 30m">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="4"></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Genre</label>
                                    <input type="text" name="genre" placeholder="Action, Drama, Comedy">
                                </div>
                                <div class="form-group">
                                    <label>Country</label>
                                    <input type="text" name="country">
                                </div>
                                <div class="form-group">
                                    <label>Language</label>
                                    <input type="text" name="language">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Poster URL</label>
                                    <input type="url" name="poster_url">
                                </div>
                                <div class="form-group">
                                    <label>Backdrop URL</label>
                                    <input type="url" name="backdrop_url">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category_id" id="categorySelect">
                                        <option value="">None</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Featured</label>
                                    <select name="featured">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Video Source URL *</label>
                                <input type="url" name="source_url" required placeholder="https://example.com/embed/video">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Server Name</label>
                                    <input type="text" name="server_name" value="Server 1">
                                </div>
                                <div class="form-group">
                                    <label>Quality</label>
                                    <input type="text" name="quality" placeholder="HD, 4K, etc.">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Content
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- TMDB Section -->
                <div id="tmdb-section" class="section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-search"></i>
                                Import from TMDB
                            </h3>
                        </div>
                        
                        <?php if (empty($tmdb_api_key)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Please set your TMDB API key in Settings to use this feature.
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label>Search Movie/Series</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="tmdbSearch" placeholder="Search TMDB...">
                                    <select id="tmdbType">
                                        <option value="movie">Movie</option>
                                        <option value="tv">TV Series</option>
                                    </select>
                                    <button class="btn btn-primary" onclick="searchTMDB()">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                            
                            <div id="tmdbResults"></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Categories Section -->
                <div id="categories-section" class="section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-folder"></i>
                                Categories
                            </h3>
                            <button class="btn btn-primary btn-sm" onclick="showAddCategoryModal()">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriesTableBody">
                                    <tr>
                                        <td colspan="4" style="text-align: center;">
                                            <div class="loading-spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Section -->
                <div id="settings-section" class="section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cog"></i>
                                Settings
                            </h3>
                        </div>
                        
                        <form id="settingsForm">
                            <div class="form-group">
                                <label>Site Name</label>
                                <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Site Description</label>
                                <textarea name="site_description" rows="3"><?php echo htmlspecialchars(getSetting('site_description', '')); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>TMDB API Key</label>
                                <input type="text" name="tmdb_api_key" value="<?php echo htmlspecialchars($tmdb_api_key); ?>">
                                <small style="color: var(--text-secondary);">Get your API key from <a href="https://www.themoviedb.org/settings/api" target="_blank" style="color: var(--accent);">TMDB</a></small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Content Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Content</h3>
                <button class="close-modal" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editContentForm">
                <input type="hidden" name="id" id="editId">
                <!-- Form fields will be populated dynamically -->
                <div id="editFormFields"></div>
            </form>
        </div>
    </div>
    
    <script src="admin.js"></script>
</body>
</html>
