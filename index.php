<?php
require_once 'config.php';

// Get initial data
$categories = getCategories();
$featuredContent = getContent(null, 10, 0);
$tmdbApiKey = getSetting('tmdb_api_key');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="<?php echo PWA_THEME_COLOR; ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="icons/icon-512x512.png">
    
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.plyr.io/3.7.8/plyr.css" rel="stylesheet">
    
    <title><?php echo APP_NAME; ?></title>
    
    <style>
        :root {
            --primary: #e50914;
            --primary-dark: #b20710;
            --movie-badge: #007bff;
            --series-badge: #28a745;
            --live-badge: #e50914;
            --dark: #141414;
            --dark-2: #1a1a1a;
            --dark-3: #222222;
            --light: #f5f5f5;
            --light-2: #e6e6e6;
            --gray: #8c8c8c;
            --success: #46d369;
            --warning: #ffa500;
            --danger: #f40612;
            --accent: #00d4ff;
            --accent-dark: #0099cc;
            --bottom-bar-height: 80px;
            --header-height: 70px;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --shadow: 0 8px 32px rgba(0,0,0,0.4);
            --shadow-hover: 0 16px 48px rgba(0,0,0,0.6);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark-2) 70%, var(--dark-3) 100%);
            color: var(--light);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
            padding-bottom: var(--bottom-bar-height);
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(20px);
            z-index: 1000;
            padding: 15px clamp(20px, 5vw, 40px);
            border-bottom: 1px solid rgba(229, 9, 20, 0.2);
            transition: var(--transition);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            font-size: 1.2em;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 600px;
            margin: 0 clamp(20px, 4vw, 40px);
        }

        .search-wrapper {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            font-size: 16px;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }

        .search-input::placeholder {
            color: var(--gray);
        }

        .search-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 18px;
            transition: var(--transition);
        }

        .search-btn:hover {
            color: var(--primary);
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--dark-2);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1001;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .search-result-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-result-item:hover {
            background: rgba(229, 9, 20, 0.1);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius-sm);
        }

        .search-result-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--light);
        }

        .search-result-info p {
            font-size: 14px;
            color: var(--gray);
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--light);
            font-size: 20px;
            cursor: pointer;
            transition: var(--transition);
            padding: 10px;
            border-radius: 50%;
        }

        .theme-toggle:hover {
            background: rgba(229, 9, 20, 0.1);
            color: var(--primary);
        }

        .user-profile {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }

        .user-profile:hover {
            transform: scale(1.05);
        }

        /* Main Content */
        main {
            padding-top: calc(var(--header-height) + 30px);
            max-width: 1400px;
            margin: 0 auto;
            padding-left: clamp(20px, 5vw, 40px);
            padding-right: clamp(20px, 5vw, 40px);
        }

        /* Filters */
        .filters {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filters h3 {
            margin-bottom: 20px;
            color: var(--light);
            font-size: clamp(1.2rem, 3vw, 1.5rem);
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .filter-label {
            color: var(--gray);
            font-weight: 500;
            min-width: fit-content;
        }

        .filter-select {
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            font-size: 14px;
            transition: var(--transition);
            min-width: 150px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
        }

        .filter-btn {
            padding: 12px 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* Server Selector */
        .server-selector {
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .server-selector h4 {
            margin-bottom: 15px;
            color: var(--light);
        }

        .server-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .server-option {
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            font-weight: 500;
        }

        .server-option:hover,
        .server-option.selected {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* Carousel */
        .carousel {
            margin: 0 0 40px 0;
            border-radius: var(--border-radius);
            overflow: hidden;
            position: relative;
            height: clamp(300px, 50vw, 500px);
        }

        .carousel-inner {
            display: flex;
            height: 100%;
            transition: transform 0.5s ease;
        }

        .carousel-item {
            min-width: 100%;
            height: 100%;
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .carousel-item::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, transparent 100%);
        }

        .carousel-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: clamp(20px, 4vw, 40px);
            color: var(--light);
            z-index: 2;
        }

        .carousel-content h2 {
            font-size: clamp(1.8rem, 4vw, 3rem);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .carousel-content p {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            margin-bottom: 20px;
            color: var(--light-2);
            max-width: 600px;
        }

        .carousel-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
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
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 clamp(20px, 4vw, 40px);
            transform: translateY(-50%);
            z-index: 3;
        }

        .carousel-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carousel-btn:hover {
            background: var(--primary);
            transform: scale(1.1);
        }

        .carousel-indicators {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: var(--transition);
        }

        .indicator.active {
            background: var(--primary);
            transform: scale(1.2);
        }

        /* Content Grid */
        .content-section {
            margin-bottom: 50px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            color: var(--light);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: clamp(15px, 3vw, 25px);
        }

        .content-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            background: rgba(255, 255, 255, 0.1);
        }

        .content-card img {
            width: 100%;
            height: clamp(200px, 30vw, 300px);
            object-fit: cover;
            transition: var(--transition);
        }

        .content-card:hover img {
            transform: scale(1.05);
        }

        .content-info {
            padding: 15px;
        }

        .content-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--light);
            line-height: 1.4;
        }

        .content-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .content-year {
            color: var(--gray);
            font-size: 14px;
        }

        .content-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-movie {
            background: var(--movie-badge);
            color: white;
        }

        .type-series {
            background: var(--series-badge);
            color: white;
        }

        .type-live {
            background: var(--live-badge);
            color: white;
        }

        .content-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .watch-btn {
            background: var(--primary);
            color: white;
        }

        .watch-btn:hover {
            background: var(--primary-dark);
        }

        .watchlater-btn {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .watchlater-btn:hover {
            background: var(--accent);
            color: white;
        }

        .watchlater-btn.saved {
            background: var(--accent);
            color: white;
        }

        /* Watch Later Tab */
        .watchlater-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .watchlater-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .watchlater-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .watchlater-header {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .watchlater-poster {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--border-radius-sm);
        }

        .watchlater-info h4 {
            color: var(--light);
            margin-bottom: 5px;
        }

        .watchlater-info p {
            color: var(--gray);
            font-size: 14px;
        }

        /* Video Player Modal */
        .video-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.95);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .video-modal.active {
            display: flex;
        }

        .video-container {
            width: 100%;
            max-width: 1200px;
            height: 100%;
            max-height: 80vh;
            position: relative;
            background: #000;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .video-player {
            width: 100%;
            height: 100%;
        }

        .video-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.5);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            z-index: 10;
            transition: var(--transition);
        }

        .video-close:hover {
            background: var(--primary);
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(229, 9, 20, 0.2);
            z-index: 1000;
            padding: 10px 20px calc(10px + env(safe-area-inset-bottom));
        }

        .nav-container {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border-radius: var(--border-radius-sm);
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            min-width: 60px;
        }

        .nav-item:hover,
        .nav-item.active {
            color: var(--primary);
            background: rgba(229, 9, 20, 0.1);
        }

        .nav-item i {
            font-size: 20px;
        }

        .nav-item span {
            font-size: 12px;
            font-weight: 500;
        }

        /* Loading */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: var(--gray);
        }

        .loading i {
            font-size: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
            }
            
            .header-content {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .search-container {
                order: 3;
                flex-basis: 100%;
                margin: 0;
            }
            
            .main {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .filters {
                padding: 20px;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .server-grid {
                grid-template-columns: 1fr;
            }
            
            .carousel {
                height: clamp(250px, 50vw, 350px);
            }
            
            .content-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .watchlater-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .content-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .carousel-actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }

        /* Light Theme */
        .light-theme {
            --dark: #f5f5f5;
            --dark-2: #e6e6e6;
            --dark-3: #d0d0d0;
            --light: #141414;
            --light-2: #333;
            --gray: #666;
        }

        .light-theme body {
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark-2) 70%, var(--dark-3) 100%);
        }

        .light-theme .content-card,
        .light-theme .filters,
        .light-theme .server-selector,
        .light-theme .watchlater-item {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.1);
        }

        .light-theme .search-input,
        .light-theme .filter-select,
        .light-theme .server-option {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.1);
            color: var(--light);
        }

        .light-theme .video-modal {
            background: rgba(0,0,0,0.98);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <a href="#" class="logo" onclick="showTab('home')">
                <i class="fas fa-play"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" class="search-input" placeholder="Search movies, series, and more..." id="searchInput">
                    <button class="search-btn" id="searchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="search-results" id="searchResults"></div>
            </div>
            
            <div class="header-controls">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="user-profile" id="userProfile">
                    <?php echo strtoupper(substr($_SESSION['user_id'] ?? 'U', 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Home Tab -->
        <div id="home-tab" class="tab-content">
            <!-- Hero Carousel -->
            <div class="carousel" id="carousel">
                <div class="carousel-inner" id="carouselInner">
                    <!-- Carousel items will be loaded here -->
                </div>
                <div class="carousel-controls">
                    <button class="carousel-btn" id="prevBtn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-btn" id="nextBtn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="carousel-indicators" id="carouselIndicators">
                    <!-- Indicators will be loaded here -->
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <h3><i class="fas fa-filter"></i> Filter Content</h3>
                <div class="filter-group">
                    <span class="filter-label">Type:</span>
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="movie">Movies</button>
                    <button class="filter-btn" data-filter="series">Series</button>
                    <button class="filter-btn" data-filter="live">Live TV</button>
                    
                    <span class="filter-label">Genre:</span>
                    <select class="filter-select" id="genreFilter">
                        <option value="">All Genres</option>
                        <?php foreach ($categories as $category): ?>
                            <?php if ($category['main_category'] === 'Movies'): ?>
                                <?php $subCategories = json_decode($category['sub_categories'], true); ?>
                                <?php foreach ($subCategories as $sub): ?>
                                    <option value="<?php echo htmlspecialchars($sub); ?>"><?php echo htmlspecialchars($sub); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    
                    <span class="filter-label">Year:</span>
                    <select class="filter-select" id="yearFilter">
                        <option value="">All Years</option>
                        <?php for ($year = date('Y'); $year >= 1950; $year--): ?>
                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                        <?php endfor; ?>
                    </select>
                    
                    <button class="filter-btn" id="clearFilters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <!-- Content Sections -->
            <div id="content-sections">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>

        <!-- Movies Tab -->
        <div id="movies-tab" class="tab-content" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-film"></i> Movies
                    </h2>
                </div>
                <div class="content-grid" id="moviesGrid">
                    <!-- Movies will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Series Tab -->
        <div id="series-tab" class="tab-content" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-tv"></i> TV Series
                    </h2>
                </div>
                <div class="content-grid" id="seriesGrid">
                    <!-- Series will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Live TV Tab -->
        <div id="livetv-tab" class="tab-content" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-broadcast-tower"></i> Live TV
                    </h2>
                </div>
                <div class="content-grid" id="liveGrid">
                    <!-- Live TV content will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Watch Later Tab -->
        <div id="watchlater-tab" class="tab-content" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-clock"></i> Watch Later
                    </h2>
                    <button class="filter-btn" id="clearWatchLater">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>
                <div class="watchlater-grid" id="watchLaterGrid">
                    <!-- Watch Later items will be loaded here -->
                </div>
            </div>
        </div>
    </main>

    <!-- Video Player Modal -->
    <div class="video-modal" id="videoModal">
        <div class="video-container">
            <button class="video-close" id="videoClose">
                <i class="fas fa-times"></i>
            </button>
            <div class="video-player" id="videoPlayer">
                <!-- Video player will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="nav-container">
            <a href="#" class="nav-item active" data-tab="home">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="#" class="nav-item" data-tab="movies">
                <i class="fas fa-film"></i>
                <span>Movies</span>
            </a>
            <a href="#" class="nav-item" data-tab="series">
                <i class="fas fa-tv"></i>
                <span>Series</span>
            </a>
            <a href="#" class="nav-item" data-tab="livetv">
                <i class="fas fa-broadcast-tower"></i>
                <span>Live TV</span>
            </a>
            <a href="#" class="nav-item" data-tab="watchlater">
                <i class="fas fa-clock"></i>
                <span>Watch Later</span>
            </a>
        </div>
    </nav>

    <!-- Scripts -->
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script>
        // App State
        let currentUser = localStorage.getItem('cinecraze_user_id') || 'user_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('cinecraze_user_id', currentUser);
        
        let appState = {
            currentTab: 'home',
            currentFilter: 'all',
            currentGenre: '',
            currentYear: '',
            carouselIndex: 0,
            currentContent: null,
            currentEpisode: null,
            currentPlayer: null,
            cachedContent: [],
            watchLater: [],
            lastUpdate: null
        };

        // Initialize IndexedDB
        let db;
        const dbName = 'CineCrazeDB';
        const dbVersion = 1;

        function initDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(dbName, dbVersion);
                
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    db = request.result;
                    resolve(db);
                };
                
                request.onupgradeneeded = (event) => {
                    db = event.target.result;
                    
                    // Content store
                    if (!db.objectStoreNames.contains('content')) {
                        const contentStore = db.createObjectStore('content', { keyPath: 'id' });
                        contentStore.createIndex('type', 'type', { unique: false });
                        contentStore.createIndex('year', 'year', { unique: false });
                        contentStore.createIndex('title', 'title', { unique: false });
                    }
                    
                    // Watch Later store
                    if (!db.objectStoreNames.contains('watchlater')) {
                        const watchLaterStore = db.createObjectStore('watchlater', { keyPath: 'id', autoIncrement: true });
                        watchLaterStore.createIndex('user_id', 'user_id', { unique: false });
                        watchLaterStore.createIndex('content_id', 'content_id', { unique: false });
                    }
                    
                    // Settings store
                    if (!db.objectStoreNames.contains('settings')) {
                        db.createObjectStore('settings', { keyPath: 'key' });
                    }
                };
            });
        }

        // Cache Management
        async function cacheContent(content) {
            const transaction = db.transaction(['content'], 'readwrite');
            const store = transaction.objectStore('content');
            
            content.forEach(item => {
                store.put(item);
            });
            
            return new Promise((resolve, reject) => {
                transaction.oncomplete = () => resolve();
                transaction.onerror = () => reject(transaction.error);
            });
        }

        async function getCachedContent(filter = {}) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction(['content'], 'readonly');
                const store = transaction.objectStore('content');
                const request = store.getAll();
                
                request.onsuccess = () => {
                    let content = request.result;
                    
                    // Apply filters
                    if (filter.type && filter.type !== 'all') {
                        content = content.filter(item => item.type === filter.type);
                    }
                    if (filter.genre) {
                        content = content.filter(item => item.sub_category === filter.genre);
                    }
                    if (filter.year) {
                        content = content.filter(item => item.year == filter.year);
                    }
                    
                    resolve(content);
                };
                
                request.onerror = () => reject(request.error);
            });
        }

        async function saveWatchLater(item) {
            const transaction = db.transaction(['watchlater'], 'readwrite');
            const store = transaction.objectStore('watchlater');
            
            const watchLaterItem = {
                user_id: currentUser,
                content_id: item.id,
                episode_id: item.episode_id || null,
                title: item.title,
                poster: item.poster,
                type: item.type,
                added_at: new Date().toISOString()
            };
            
            return new Promise((resolve, reject) => {
                const request = store.add(watchLaterItem);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }

        async function getWatchLaterItems() {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction(['watchlater'], 'readonly');
                const store = transaction.objectStore('watchlater');
                const index = store.index('user_id');
                const request = index.getAll(currentUser);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }

        async function removeWatchLaterItem(contentId, episodeId = null) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction(['watchlater'], 'readwrite');
                const store = transaction.objectStore('watchlater');
                const index = store.index('content_id');
                const request = index.getAll(contentId);
                
                request.onsuccess = () => {
                    const items = request.result;
                    const userItems = items.filter(item => 
                        item.user_id === currentUser && 
                        (episodeId ? item.episode_id === episodeId : !item.episode_id)
                    );
                    
                    let deleteCount = 0;
                    userItems.forEach(item => {
                        const deleteRequest = store.delete(item.id);
                        deleteRequest.onsuccess = () => deleteCount++;
                    });
                    
                    transaction.oncomplete = () => resolve(deleteCount);
                    transaction.onerror = () => reject(transaction.error);
                };
                
                request.onerror = () => reject(request.error);
            });
        }

        // API Functions
        async function fetchContent(type = null, page = 1) {
            try {
                const response = await fetch(`api.php?action=get_content&type=${type}&page=${page}`);
                const data = await response.json();
                return data.success ? data.content : [];
            } catch (error) {
                console.error('Error fetching content:', error);
                return [];
            }
        }

        async function searchContent(query) {
            try {
                const response = await fetch(`api.php?action=search&q=${encodeURIComponent(query)}`);
                const data = await response.json();
                return data.success ? data.results : [];
            } catch (error) {
                console.error('Error searching content:', error);
                return [];
            }
        }

        async function fetchWatchLater() {
            try {
                const response = await fetch(`api.php?action=get_watchlater&user_id=${currentUser}`);
                const data = await response.json();
                return data.success ? data.items : [];
            } catch (error) {
                console.error('Error fetching watch later:', error);
                return [];
            }
        }

        // UI Functions
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(`${tabName}-tab`).style.display = 'block';
            
            // Add active class to selected nav item
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            
            appState.currentTab = tabName;
            
            // Load tab-specific content
            loadTabContent(tabName);
        }

        async function loadTabContent(tabName) {
            switch (tabName) {
                case 'home':
                    await loadHomeContent();
                    break;
                case 'movies':
                    await loadMoviesContent();
                    break;
                case 'series':
                    await loadSeriesContent();
                    break;
                case 'livetv':
                    await loadLiveTVContent();
                    break;
                case 'watchlater':
                    await loadWatchLaterContent();
                    break;
            }
        }

        async function loadHomeContent() {
            const contentSections = document.getElementById('content-sections');
            contentSections.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            try {
                const [featured, movies, series, live] = await Promise.all([
                    fetchContent(null, 1),
                    fetchContent('movie', 1),
                    fetchContent('series', 1),
                    fetchContent('live', 1)
                ]);
                
                // Cache content
                await cacheContent([...featured, ...movies, ...series, ...live]);
                
                // Update carousel
                updateCarousel(featured.slice(0, 5));
                
                // Render content sections
                contentSections.innerHTML = `
                    <div class="content-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-film"></i> Featured Movies
                            </h2>
                        </div>
                        <div class="content-grid">
                            ${movies.slice(0, 8).map(item => renderContentCard(item)).join('')}
                        </div>
                    </div>
                    
                    <div class="content-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-tv"></i> Popular Series
                            </h2>
                        </div>
                        <div class="content-grid">
                            ${series.slice(0, 8).map(item => renderContentCard(item)).join('')}
                        </div>
                    </div>
                    
                    <div class="content-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-broadcast-tower"></i> Live TV
                            </h2>
                        </div>
                        <div class="content-grid">
                            ${live.slice(0, 8).map(item => renderContentCard(item)).join('')}
                        </div>
                    </div>
                `;
                
                // Add event listeners to cards
                addContentCardListeners();
                
            } catch (error) {
                console.error('Error loading home content:', error);
                contentSections.innerHTML = '<div class="loading"><i class="fas fa-exclamation-triangle"></i> Error loading content</div>';
            }
        }

        async function loadMoviesContent() {
            const grid = document.getElementById('moviesGrid');
            grid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            try {
                const movies = await fetchContent('movie', 1);
                await cacheContent(movies);
                
                grid.innerHTML = movies.map(item => renderContentCard(item)).join('');
                addContentCardListeners();
                
            } catch (error) {
                console.error('Error loading movies:', error);
                grid.innerHTML = '<div class="loading"><i class="fas fa-exclamation-triangle"></i> Error loading movies</div>';
            }
        }

        async function loadSeriesContent() {
            const grid = document.getElementById('seriesGrid');
            grid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            try {
                const series = await fetchContent('series', 1);
                await cacheContent(series);
                
                grid.innerHTML = series.map(item => renderContentCard(item)).join('');
                addContentCardListeners();
                
            } catch (error) {
                console.error('Error loading series:', error);
                grid.innerHTML = '<div class="loading"><i class="fas fa-exclamation-triangle"></i> Error loading series</div>';
            }
        }

        async function loadLiveTVContent() {
            const grid = document.getElementById('liveGrid');
            grid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            try {
                const live = await fetchContent('live', 1);
                await cacheContent(live);
                
                grid.innerHTML = live.map(item => renderContentCard(item)).join('');
                addContentCardListeners();
                
            } catch (error) {
                console.error('Error loading live TV:', error);
                grid.innerHTML = '<div class="loading"><i class="fas fa-exclamation-triangle"></i> Error loading live TV</div>';
            }
        }

        async function loadWatchLaterContent() {
            const grid = document.getElementById('watchLaterGrid');
            grid.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            try {
                const watchLaterItems = await fetchWatchLater();
                
                if (watchLaterItems.length === 0) {
                    grid.innerHTML = '<div class="loading"><i class="fas fa-clock"></i> No items in Watch Later</div>';
                    return;
                }
                
                grid.innerHTML = watchLaterItems.map(item => renderWatchLaterItem(item)).join('');
                addWatchLaterListeners();
                
            } catch (error) {
                console.error('Error loading watch later:', error);
                grid.innerHTML = '<div class="loading"><i class="fas fa-exclamation-triangle"></i> Error loading watch later</div>';
            }
        }

        function renderContentCard(item) {
            const isInWatchLater = appState.watchLater.some(wl => wl.content_id === item.id);
            
            return `
                <div class="content-card" data-id="${item.id}" data-type="${item.type}">
                    <img src="${item.poster || item.thumbnail || 'https://via.placeholder.com/300x450/333/fff?text=No+Image'}" alt="${item.title}">
                    <div class="content-info">
                        <h3 class="content-title">${item.title}</h3>
                        <div class="content-meta">
                            <span class="content-year">${item.year || 'N/A'}</span>
                            <span class="content-type type-${item.type}">${item.type}</span>
                        </div>
                        <div class="content-actions">
                            <button class="action-btn watch-btn" onclick="playContent(${item.id})">
                                <i class="fas fa-play"></i> Watch
                            </button>
                            <button class="action-btn watchlater-btn ${isInWatchLater ? 'saved' : ''}" onclick="toggleWatchLater(${item.id}, '${item.title}', '${item.poster}', '${item.type}')">
                                <i class="fas ${isInWatchLater ? 'fa-check' : 'fa-clock'}"></i>
                                ${isInWatchLater ? 'Saved' : 'Watch Later'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderWatchLaterItem(item) {
            return `
                <div class="watchlater-item" data-id="${item.content_id}" data-episode-id="${item.episode_id || ''}">
                    <div class="watchlater-header">
                        <img src="${item.poster || 'https://via.placeholder.com/80x120/333/fff?text=No+Image'}" alt="${item.title}" class="watchlater-poster">
                        <div class="watchlater-info">
                            <h4>${item.title}</h4>
                            <p>${item.type} • Added ${new Date(item.added_at).toLocaleDateString()}</p>
                            ${item.episode_title ? `<p>Episode: ${item.episode_title}</p>` : ''}
                        </div>
                    </div>
                    <div class="content-actions">
                        <button class="action-btn watch-btn" onclick="playContent(${item.content_id}, ${item.episode_id})">
                            <i class="fas fa-play"></i> Watch
                        </button>
                        <button class="action-btn watchlater-btn" onclick="removeFromWatchLater(${item.content_id}, ${item.episode_id})">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `;
        }

        function addContentCardListeners() {
            document.querySelectorAll('.content-card').forEach(card => {
                card.addEventListener('click', (e) => {
                    if (!e.target.closest('.content-actions')) {
                        const id = card.dataset.id;
                        const type = card.dataset.type;
                        showContentDetails(id, type);
                    }
                });
            });
        }

        function addWatchLaterListeners() {
            document.querySelectorAll('.watchlater-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    if (!e.target.closest('.content-actions')) {
                        const id = item.dataset.id;
                        const episodeId = item.dataset.episodeId;
                        showContentDetails(id, 'series', episodeId);
                    }
                });
            });
        }

        // Carousel Functions
        function updateCarousel(items) {
            const inner = document.getElementById('carouselInner');
            const indicators = document.getElementById('carouselIndicators');
            
            inner.innerHTML = items.map((item, index) => `
                <div class="carousel-item" style="background-image: url('${item.poster || item.thumbnail || 'https://via.placeholder.com/1200x600/333/fff?text=No+Image'}')">
                    <div class="carousel-content">
                        <h2>${item.title}</h2>
                        <p>${item.description || 'No description available.'}</p>
                        <div class="carousel-actions">
                            <button class="btn btn-primary" onclick="playContent(${item.id})">
                                <i class="fas fa-play"></i> Watch Now
                            </button>
                            <button class="btn btn-secondary" onclick="showContentDetails(${item.id}, '${item.type}')">
                                <i class="fas fa-info-circle"></i> More Info
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            indicators.innerHTML = items.map((_, index) => `
                <div class="indicator ${index === 0 ? 'active' : ''}" onclick="goToSlide(${index})"></div>
            `).join('');
            
            appState.carouselIndex = 0;
        }

        function nextSlide() {
            const items = document.querySelectorAll('.carousel-item');
            appState.carouselIndex = (appState.carouselIndex + 1) % items.length;
            updateCarouselPosition();
        }

        function prevSlide() {
            const items = document.querySelectorAll('.carousel-item');
            appState.carouselIndex = appState.carouselIndex === 0 ? items.length - 1 : appState.carouselIndex - 1;
            updateCarouselPosition();
        }

        function goToSlide(index) {
            appState.carouselIndex = index;
            updateCarouselPosition();
        }

        function updateCarouselPosition() {
            const inner = document.getElementById('carouselInner');
            inner.style.transform = `translateX(-${appState.carouselIndex * 100}%)`;
            
            // Update indicators
            document.querySelectorAll('.indicator').forEach((indicator, index) => {
                indicator.classList.toggle('active', index === appState.carouselIndex);
            });
        }

        // Video Player Functions
        async function playContent(contentId, episodeId = null) {
            try {
                const response = await fetch(`api.php?action=get_content&id=${contentId}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error('Content not found');
                }
                
                const content = data.content;
                let videoUrl = '';
                
                if (content.type === 'series' && episodeId) {
                    // Get specific episode
                    const episodeResponse = await fetch(`api.php?action=get_episode&id=${episodeId}`);
                    const episodeData = await episodeResponse.json();
                    
                    if (episodeData.success) {
                        const servers = episodeData.servers;
                        if (servers.length > 0) {
                            videoUrl = servers[0].url;
                        }
                    }
                } else {
                    // Get content servers
                    const servers = content.servers || [];
                    if (servers.length > 0) {
                        videoUrl = servers[0].url;
                    }
                }
                
                if (!videoUrl) {
                    throw new Error('No video source available');
                }
                
                openVideoPlayer(videoUrl, content.title);
                
            } catch (error) {
                console.error('Error playing content:', error);
                alert('Error playing content: ' + error.message);
            }
        }

        function openVideoPlayer(url, title) {
            const modal = document.getElementById('videoModal');
            const playerContainer = document.getElementById('videoPlayer');
            
            playerContainer.innerHTML = `
                <video id="video" controls crossorigin>
                    <source src="${url}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            `;
            
            modal.classList.add('active');
            
            // Initialize Plyr
            appState.currentPlayer = new Plyr('#video', {
                controls: [
                    'play-large',
                    'restart',
                    'rewind',
                    'play',
                    'fast-forward',
                    'progress',
                    'current-time',
                    'duration',
                    'mute',
                    'volume',
                    'captions',
                    'settings',
                    'pip',
                    'airplay',
                    'fullscreen'
                ]
            });
            
            // Auto-play
            appState.currentPlayer.play();
        }

        function closeVideoPlayer() {
            const modal = document.getElementById('videoModal');
            
            if (appState.currentPlayer) {
                appState.currentPlayer.destroy();
                appState.currentPlayer = null;
            }
            
            modal.classList.remove('active');
            document.getElementById('videoPlayer').innerHTML = '';
        }

        // Watch Later Functions
        async function toggleWatchLater(contentId, title, poster, type) {
            const button = event.target.closest('.watchlater-btn');
            const isSaved = button.classList.contains('saved');
            
            try {
                if (isSaved) {
                    await removeFromWatchLater(contentId);
                    button.classList.remove('saved');
                    button.innerHTML = '<i class="fas fa-clock"></i> Watch Later';
                    
                    // Remove from app state
                    appState.watchLater = appState.watchLater.filter(item => item.content_id !== contentId);
                } else {
                    await saveWatchLater({ id: contentId, title, poster, type });
                    button.classList.add('saved');
                    button.innerHTML = '<i class="fas fa-check"></i> Saved';
                    
                    // Add to app state
                    appState.watchLater.push({ content_id: contentId, title, poster, type });
                }
                
                // Update watch later count in nav
                updateWatchLaterCount();
                
            } catch (error) {
                console.error('Error toggling watch later:', error);
                alert('Error updating watch later');
            }
        }

        async function removeFromWatchLater(contentId, episodeId = null) {
            try {
                await removeWatchLaterItem(contentId, episodeId);
                
                // Remove from UI
                const item = document.querySelector(`[data-id="${contentId}"][data-episode-id="${episodeId || ''}"]`);
                if (item) {
                    item.remove();
                }
                
                // Update app state
                appState.watchLater = appState.watchLater.filter(item => 
                    !(item.content_id === contentId && (episodeId ? item.episode_id === episodeId : true))
                );
                
                updateWatchLaterCount();
                
            } catch (error) {
                console.error('Error removing from watch later:', error);
                throw error;
            }
        }

        function updateWatchLaterCount() {
            const count = appState.watchLater.length;
            const watchLaterNav = document.querySelector('[data-tab="watchlater"]');
            const existingCount = watchLaterNav.querySelector('.badge');
            
            if (existingCount) {
                existingCount.remove();
            }
            
            if (count > 0) {
                const badge = document.createElement('span');
                badge.className = 'badge';
                badge.textContent = count;
                badge.style.cssText = `
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: var(--primary);
                    color: white;
                    border-radius: 50%;
                    width: 20px;
                    height: 20px;
                    font-size: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                `;
                watchLaterNav.style.position = 'relative';
                watchLaterNav.appendChild(badge);
            }
        }

        // Search Functions
        async function handleSearch() {
            const query = document.getElementById('searchInput').value.trim();
            const resultsContainer = document.getElementById('searchResults');
            
            if (query.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }
            
            try {
                const results = await searchContent(query);
                
                if (results.length === 0) {
                    resultsContainer.innerHTML = '<div class="search-result-item">No results found</div>';
                } else {
                    resultsContainer.innerHTML = results.map(item => `
                        <div class="search-result-item" onclick="selectSearchResult(${item.id}, '${item.type}')">
                            <img src="${item.poster || item.thumbnail || 'https://via.placeholder.com/60x80/333/fff?text=No+Image'}" alt="${item.title}">
                            <div class="search-result-info">
                                <h4>${item.title}</h4>
                                <p>${item.type} • ${item.year || 'N/A'}</p>
                            </div>
                        </div>
                    `).join('');
                }
                
                resultsContainer.style.display = 'block';
                
            } catch (error) {
                console.error('Error searching:', error);
                resultsContainer.innerHTML = '<div class="search-result-item">Search error</div>';
                resultsContainer.style.display = 'block';
            }
        }

        function selectSearchResult(contentId, type) {
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('searchInput').value = '';
            
            if (type === 'series') {
                showTab('series');
            } else if (type === 'movie') {
                showTab('movies');
            } else if (type === 'live') {
                showTab('livetv');
            }
            
            // Scroll to content or highlight it
            setTimeout(() => {
                const card = document.querySelector(`[data-id="${contentId}"]`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.style.animation = 'pulse 0.5s ease-in-out';
                }
            }, 300);
        }

        // Filter Functions
        function applyFilters() {
            appState.currentFilter = document.querySelector('.filter-btn.active').dataset.filter;
            appState.currentGenre = document.getElementById('genreFilter').value;
            appState.currentYear = document.getElementById('yearFilter').value;
            
            loadTabContent(appState.currentTab);
        }

        function clearFilters() {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('[data-filter="all"]').classList.add('active');
            document.getElementById('genreFilter').value = '';
            document.getElementById('yearFilter').value = '';
            
            appState.currentFilter = 'all';
            appState.currentGenre = '';
            appState.currentYear = '';
            
            loadTabContent(appState.currentTab);
        }

        // Theme Functions
        function toggleTheme() {
            document.body.classList.toggle('light-theme');
            const icon = document.querySelector('#themeToggle i');
            
            if (document.body.classList.contains('light-theme')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                localStorage.setItem('theme', 'light');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Auto-update detection
        async function checkForUpdates() {
            try {
                const response = await fetch('api.php?action=get_last_update');
                const data = await response.json();
                
                if (data.success && data.last_update !== appState.lastUpdate) {
                    appState.lastUpdate = data.last_update;
                    
                    // Show update notification
                    showUpdateNotification();
                }
            } catch (error) {
                console.error('Error checking for updates:', error);
            }
        }

        function showUpdateNotification() {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--primary);
                color: white;
                padding: 15px 20px;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
                z-index: 2000;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-sync-alt"></i>
                <span>New content available!</span>
                <button onclick="this.parentElement.remove(); loadTabContent('${appState.currentTab}');" style="background: none; border: none; color: white; margin-left: 10px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', async () => {
            // Initialize database
            await initDB();
            
            // Load theme
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                document.querySelector('#themeToggle i').className = 'fas fa-sun';
            }
            
            // Load initial content
            await loadHomeContent();
            
            // Load watch later
            appState.watchLater = await getWatchLaterItems();
            updateWatchLaterCount();
            
            // Set up auto-update check
            setInterval(checkForUpdates, 30000); // Check every 30 seconds
            
            // Set up carousel auto-advance
            setInterval(nextSlide, 5000); // Change slide every 5 seconds
        });

        // Navigation event listeners
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = item.dataset.tab;
                showTab(tab);
            });
        });

        // Search event listeners
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(handleSearch, 300);
        });

        document.getElementById('searchBtn').addEventListener('click', handleSearch);

        // Filter event listeners
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                applyFilters();
            });
        });

        document.getElementById('genreFilter').addEventListener('change', applyFilters);
        document.getElementById('yearFilter').addEventListener('change', applyFilters);
        document.getElementById('clearFilters').addEventListener('click', clearFilters);

        // Theme toggle
        document.getElementById('themeToggle').addEventListener('click', toggleTheme);

        // Carousel controls
        document.getElementById('nextBtn').addEventListener('click', nextSlide);
        document.getElementById('prevBtn').addEventListener('click', prevSlide);

        // Video modal controls
        document.getElementById('videoClose').addEventListener('click', closeVideoPlayer);
        document.getElementById('videoModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                closeVideoPlayer();
            }
        });

        // Keyboard controls
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeVideoPlayer();
                document.getElementById('searchResults').style.display = 'none';
            }
        });

        // Watch Later clear button
        document.getElementById('clearWatchLater').addEventListener('click', async () => {
            if (confirm('Are you sure you want to clear all Watch Later items?')) {
                try {
                    const transaction = db.transaction(['watchlater'], 'readwrite');
                    const store = transaction.objectStore('watchlater');
                    const index = store.index('user_id');
                    const request = index.getAllKeys(currentUser);
                    
                    request.onsuccess = () => {
                        const keys = request.result;
                        keys.forEach(key => {
                            store.delete(key);
                        });
                        
                        appState.watchLater = [];
                        updateWatchLaterCount();
                        loadWatchLaterContent();
                    };
                } catch (error) {
                    console.error('Error clearing watch later:', error);
                }
            }
        });

        // Register service worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</body>
</html>