<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Fetch categories
    $stmt = $conn->query("SELECT * FROM categories ORDER BY order_index ASC");
    $categories = $stmt->fetchAll();
    
    // Fetch featured content for carousel
    $stmt = $conn->query("SELECT m.*, c.name as category_name FROM movies m 
                         LEFT JOIN categories c ON m.category_id = c.id 
                         WHERE m.status = 'active' ORDER BY m.view_count DESC LIMIT 5");
    $featuredMovies = $stmt->fetchAll();
    
    // Fetch recent movies
    $stmt = $conn->query("SELECT * FROM movies WHERE status = 'active' ORDER BY created_at DESC LIMIT 20");
    $recentMovies = $stmt->fetchAll();
    
} catch (Exception $e) {
    $categories = [];
    $featuredMovies = [];
    $recentMovies = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e50914">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="CineCraze">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    
    <title>CineCraze - Premium Movie Streaming</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    
    <style>
        :root {
            --primary: #e50914;
            --primary-dark: #b20710;
            --movie-badge: #007bff;
            --series-badge: #28a745;
            --live-badge: #e50914;
            --dark: #0a0a0a;
            --dark-2: #1a1a1a;
            --dark-3: #2d2d2d;
            --light: #f5f5f5;
            --light-2: #e6e6e6;
            --gray: #8c8c8c;
            --transition: all 0.3s ease;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            --radius: 8px;
            --header-height: clamp(60px, 8vw, 80px);
            --bottom-nav-height: clamp(70px, 10vw, 85px);
            --youtube-red: #ff0000;
            --youtube-dark: #0f0f0f;
            --youtube-gray: #272727;
            --youtube-light-gray: #aaaaaa;
            --youtube-dark-gray: #181818;
            --youtube-light: #f1f1f1;
            --youtube-blue: #3ea6ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--youtube-dark);
            color: var(--light);
            overflow-x: hidden;
            transition: background-color 0.3s ease;
            padding-bottom: var(--bottom-nav-height);
        }

        /* Header Styles */
        header {
            background: var(--youtube-dark);
            padding: clamp(10px, 2vw, 20px) clamp(10px, 5vw, 5%);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition);
            border-bottom: 1px solid var(--youtube-gray);
            height: var(--header-height);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: clamp(8px, 2vw, 15px);
            font-size: clamp(20px, 4vw, 32px);
            font-weight: 700;
            color: var(--youtube-red);
            text-decoration: none;
        }

        .logo i {
            color: var(--youtube-red);
        }

        /* Search Container */
        .search-container {
            position: relative;
            width: clamp(200px, 40vw, 500px);
            transition: width 0.3s ease;
        }

        .search-input-container {
            position: relative;
            width: 100%;
        }

        .search-container input {
            width: 100%;
            padding: clamp(8px, 2vw, 15px) clamp(10px, 3vw, 20px);
            padding-right: clamp(35px, 6vw, 50px);
            border-radius: clamp(20px, 5vw, 30px);
            border: none;
            background-color: var(--youtube-gray);
            color: var(--light);
            font-size: clamp(14px, 2.5vw, 18px);
            transition: var(--transition);
        }

        .search-container input:focus {
            outline: none;
            background-color: var(--dark-3);
            box-shadow: 0 0 0 2px var(--youtube-red);
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background-color: var(--dark-3);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            max-height: clamp(200px, 40vw, 400px);
            overflow-y: auto;
            display: none;
            z-index: 1001;
        }

        .search-result-item {
            padding: clamp(10px, 2.5vw, 15px);
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 20px);
            border-bottom: 1px solid var(--dark-2);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-result-item:hover {
            background-color: var(--dark-2);
        }

        .search-result-item img {
            width: clamp(40px, 8vw, 60px);
            height: clamp(60px, 12vw, 80px);
            object-fit: cover;
            border-radius: 4px;
        }

        .search-result-info h4 {
            font-size: clamp(14px, 2.5vw, 18px);
            margin-bottom: clamp(2px, 0.5vw, 5px);
        }

        .search-result-info p {
            font-size: clamp(12px, 2vw, 16px);
            color: var(--gray);
        }

        .close-search-btn {
            position: absolute;
            right: clamp(10px, 2vw, 20px);
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            z-index: 3;
        }

        /* Header Controls */
        .header-controls {
            display: flex;
            align-items: center;
            gap: clamp(15px, 3vw, 25px);
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--light);
            font-size: clamp(18px, 3.5vw, 26px);
            cursor: pointer;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            color: var(--youtube-red);
        }

        .user-profile {
            width: clamp(35px, 7vw, 45px);
            height: clamp(35px, 7vw, 45px);
            border-radius: 50%;
            background-color: var(--youtube-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
            font-size: clamp(14px, 2.5vw, 18px);
        }

        .mobile-search-btn {
            display: none;
            background: var(--youtube-red);
            color: #fff;
            width: clamp(35px, 8vw, 45px);
            height: clamp(35px, 8vw, 45px);
            border: none;
            border-radius: 50%;
            font-size: clamp(16px, 3.5vw, 20px);
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .mobile-search-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        /* Main Content */
        main {
            padding-top: calc(var(--header-height) + clamp(15px, 3vw, 30px));
            min-height: calc(100vh - var(--header-height) - var(--bottom-nav-height));
        }

        /* Carousel */
        .carousel {
            margin: 0 clamp(10px, 5vw, 5%) clamp(20px, 4vw, 40px);
            border-radius: var(--radius);
            overflow: hidden;
            position: relative;
            height: clamp(250px, 50vw, 500px);
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
        }

        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carousel-content {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: clamp(20px, 5vw, 40px);
            background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, transparent 100%);
        }

        .carousel-content h2 {
            font-size: clamp(1.5rem, 5vw, 3rem);
            margin-bottom: clamp(8px, 2vw, 15px);
        }

        .carousel-content p {
            max-width: clamp(300px, 60vw, 600px);
            margin-bottom: clamp(15px, 3vw, 25px);
            color: var(--light-2);
            font-size: clamp(14px, 2.5vw, 18px);
        }

        .play-btn {
            background: var(--youtube-red);
            color: white;
            border: none;
            padding: clamp(10px, 2.5vw, 15px) clamp(20px, 4vw, 30px);
            border-radius: clamp(20px, 5vw, 30px);
            font-size: clamp(14px, 2.5vw, 18px);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: clamp(8px, 2vw, 12px);
        }

        .play-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 clamp(15px, 4vw, 30px);
            transform: translateY(-50%);
        }

        .carousel-btn {
            background: rgba(0,0,0,0.5);
            border: none;
            color: white;
            width: clamp(40px, 8vw, 60px);
            height: clamp(40px, 8vw, 60px);
            border-radius: 50%;
            font-size: clamp(16px, 3.5vw, 24px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .carousel-btn:hover {
            background: var(--youtube-red);
        }

        .carousel-indicators {
            position: absolute;
            bottom: clamp(15px, 3vw, 25px);
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: clamp(8px, 2vw, 12px);
        }

        .indicator {
            width: clamp(10px, 2vw, 15px);
            height: clamp(10px, 2vw, 15px);
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: var(--transition);
        }

        .indicator.active {
            background: var(--youtube-red);
            transform: scale(1.2);
        }

        /* Content Container */
        .content-container {
            padding: 0 clamp(10px, 5vw, 5%) clamp(50px, 10vw, 80px);
        }

        /* Server Selector */
        .server-selector {
            margin: clamp(15px, 3vw, 25px) 0;
            display: flex;
            gap: clamp(8px, 2vw, 12px);
            flex-wrap: wrap;
            justify-content: center;
        }

        .server-btn {
            padding: clamp(8px, 2vw, 12px) clamp(15px, 3vw, 20px);
            border: 2px solid var(--youtube-gray);
            background: var(--dark-3);
            color: var(--light);
            border-radius: clamp(20px, 5vw, 25px);
            cursor: pointer;
            transition: var(--transition);
            font-size: clamp(12px, 2.5vw, 16px);
            font-weight: 600;
        }

        .server-btn:hover,
        .server-btn.active {
            border-color: var(--youtube-red);
            background: var(--youtube-red);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(clamp(150px, 30vw, 250px), 1fr));
            gap: clamp(15px, 3vw, 30px);
        }

        .content-card {
            background-color: var(--youtube-gray);
            border-radius: var(--radius);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
            cursor: pointer;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .card-img {
            width: 100%;
            height: clamp(200px, 40vw, 300px);
            position: relative;
        }

        .card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card-img img.loaded {
            opacity: 1;
        }

        .card-badge {
            position: absolute;
            top: clamp(8px, 2vw, 12px);
            left: clamp(8px, 2vw, 12px);
            color: white;
            padding: clamp(4px, 1vw, 8px) clamp(8px, 2vw, 12px);
            border-radius: 4px;
            font-size: clamp(10px, 2vw, 14px);
            font-weight: 700;
            z-index: 2;
        }

        .badge-movie {
            background-color: var(--movie-badge);
        }

        .badge-series {
            background-color: var(--series-badge);
        }

        .badge-live {
            background-color: var(--live-badge);
        }

        .card-info {
            padding: clamp(12px, 2.5vw, 20px);
        }

        .card-title {
            font-weight: 600;
            line-height: 1.4;
            color: var(--light);
            font-size: clamp(14px, 2.5vw, 18px);
            margin-bottom: clamp(4px, 1vw, 8px);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-meta {
            font-size: clamp(12px, 2vw, 14px);
            color: var(--gray);
        }

        .watch-later-btn {
            position: absolute;
            top: clamp(8px, 2vw, 12px);
            right: clamp(8px, 2vw, 12px);
            background: rgba(0,0,0,0.7);
            border: none;
            color: white;
            width: clamp(30px, 6vw, 40px);
            height: clamp(30px, 6vw, 40px);
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(12px, 2.5vw, 16px);
            z-index: 3;
        }

        .watch-later-btn:hover {
            background: var(--youtube-red);
        }

        .watch-later-btn.saved {
            background: var(--youtube-red);
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--dark-3) 0%, var(--dark-2) 100%);
            border-top: 2px solid var(--primary);
            box-shadow: 0 -5px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(20px);
            z-index: 1000;
            height: var(--bottom-nav-height);
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
            color: var(--gray);
            min-width: clamp(60px, 12vw, 80px);
            text-align: center;
        }

        .nav-tab:hover {
            color: var(--light);
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

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            width: clamp(40px, 8vw, 60px);
            height: clamp(40px, 8vw, 60px);
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: var(--youtube-red);
            animation: spin 1s ease-in-out infinite;
            margin: clamp(20px, 4vw, 40px) auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: clamp(10px, 3vw, 20px);
        }

        .modal-content {
            background-color: var(--dark-2);
            border-radius: var(--radius);
            width: 100%;
            max-width: clamp(300px, 90vw, 800px);
            max-height: 90vh;
            overflow: auto;
            position: relative;
            box-shadow: var(--shadow);
        }

        .modal-header {
            padding: clamp(15px, 3vw, 25px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--dark-3);
        }

        .modal-title {
            font-size: clamp(18px, 4vw, 28px);
            font-weight: 700;
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--light);
            font-size: clamp(18px, 4vw, 28px);
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .modal-body {
            padding: clamp(15px, 3vw, 25px);
        }

        /* Player Container */
        .player-container {
            position: relative;
            width: 100%;
            height: clamp(200px, 50vw, 400px);
            background: #000;
            border-radius: var(--radius);
            overflow: hidden;
        }

        .player-container video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .search-container {
                display: none;
            }
            
            .mobile-search-btn {
                display: flex;
            }
            
            .search-container.show {
                display: block !important;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                padding: clamp(8px, 2vw, 15px);
                background-color: var(--youtube-dark);
                z-index: 2000;
            }

            .content-grid {
                grid-template-columns: repeat(auto-fill, minmax(clamp(120px, 45vw, 180px), 1fr));
                gap: clamp(10px, 2.5vw, 20px);
            }

            .carousel {
                height: clamp(200px, 50vw, 300px);
            }
        }

        @media (max-width: 480px) {
            .content-grid {
                grid-template-columns: repeat(auto-fill, minmax(clamp(100px, 45vw, 140px), 1fr));
                gap: clamp(8px, 2vw, 15px);
            }
            
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

        /* Smart TV and Large Screens */
        @media (min-width: 1600px) {
            .carousel {
                height: clamp(500px, 60vw, 700px);
            }
            
            .content-grid {
                grid-template-columns: repeat(auto-fill, minmax(clamp(250px, 20vw, 350px), 1fr));
                gap: clamp(25px, 3vw, 40px);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <a href="#" class="logo">
            <i class="fas fa-play"></i>
            CineCraze
        </a>

        <div class="search-container" id="searchContainer">
            <div class="search-input-container">
                <input type="text" id="searchInput" placeholder="Search movies, series...">
                <button class="close-search-btn" id="closeSearchBtn">
                    <i class="fas fa-times"></i>
                </button>
                <div class="search-results" id="searchResults"></div>
            </div>
        </div>

        <button class="mobile-search-btn" id="mobileSearchBtn">
            <i class="fas fa-search"></i>
        </button>

        <div class="header-controls">
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
            </button>
            <div class="user-profile" id="userProfile">U</div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Featured Carousel -->
        <section class="carousel" id="featuredCarousel">
            <div class="carousel-inner" id="carouselInner">
                <!-- Carousel items will be populated by JavaScript -->
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
                <!-- Indicators will be populated by JavaScript -->
            </div>
        </section>

        <!-- Server Selector -->
        <div class="server-selector" id="serverSelector">
            <button class="server-btn active" data-server="server1">Server 1</button>
            <button class="server-btn" data-server="server2">Server 2</button>
            <button class="server-btn" data-server="server3">Server 3</button>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner"></div>

        <!-- Content Grid -->
        <section class="content-container">
            <div class="content-grid" id="contentGrid">
                <!-- Content cards will be populated by JavaScript -->
            </div>
        </section>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="#" class="nav-tab active" data-tab="home">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="#" class="nav-tab" data-tab="movies">
            <i class="fas fa-film"></i>
            <span>Movies</span>
        </a>
        <a href="#" class="nav-tab" data-tab="series">
            <i class="fas fa-tv"></i>
            <span>Series</span>
        </a>
        <a href="#" class="nav-tab" data-tab="live">
            <i class="fas fa-broadcast-tower"></i>
            <span>Live TV</span>
        </a>
        <a href="#" class="nav-tab" data-tab="watchlater">
            <i class="fas fa-clock"></i>
            <span>Watch Later</span>
        </a>
    </nav>

    <!-- Video Player Modal -->
    <div class="modal" id="videoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="videoTitle"></h3>
                <button class="close-modal" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="player-container" id="playerContainer">
                    <!-- Video player will be inserted here -->
                </div>
                <div class="video-details" id="videoDetails">
                    <!-- Video details will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script>
        // Global variables
        let currentPlayer = null;
        let currentContent = [];
        let featuredContent = [];
        let currentTab = 'home';
        let watchLaterList = [];
        let db = null;

        // Database configuration for IndexedDB
        const DB_NAME = 'CineCrazeDB';
        const DB_VERSION = 1;

        // Initialize IndexedDB
        function initDatabase() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);
                
                request.onerror = () => reject(request.error);
                request.onsuccess = () => {
                    db = request.result;
                    resolve(db);
                };
                
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    
                    // Create movies store
                    if (!db.objectStoreNames.contains('movies')) {
                        const movieStore = db.createObjectStore('movies', { keyPath: 'id' });
                        movieStore.createIndex('title', 'title', { unique: false });
                        movieStore.createIndex('type', 'type', { unique: false });
                        movieStore.createIndex('genre', 'genre', { unique: false });
                    }
                    
                    // Create watch later store
                    if (!db.objectStoreNames.contains('watchLater')) {
                        const watchLaterStore = db.createObjectStore('watchLater', { keyPath: 'id' });
                        watchLaterStore.createIndex('movieId', 'movieId', { unique: true });
                    }
                    
                    // Create settings store
                    if (!db.objectStoreNames.contains('settings')) {
                        const settingsStore = db.createObjectStore('settings', { keyPath: 'key' });
                    }
                };
            });
        }

        // Load data from IndexedDB
        async function loadFromIndexedDB() {
            try {
                const transaction = db.transaction(['movies', 'watchLater', 'settings'], 'readonly');
                const stores = {
                    movies: transaction.objectStore('movies'),
                    watchLater: transaction.objectStore('watchLater'),
                    settings: transaction.objectStore('settings')
                };
                
                // Load movies
                const moviesRequest = stores.movies.getAll();
                moviesRequest.onsuccess = () => {
                    currentContent = moviesRequest.result;
                    renderContent();
                };
                
                // Load watch later
                const watchLaterRequest = stores.watchLater.getAll();
                watchLaterRequest.onsuccess = () => {
                    watchLaterList = watchLaterRequest.result;
                };
                
            } catch (error) {
                console.error('Error loading from IndexedDB:', error);
            }
        }

        // Save movie to IndexedDB
        function saveMovieToIndexedDB(movie) {
            const transaction = db.transaction(['movies'], 'readwrite');
            const store = transaction.objectStore('movies');
            store.put(movie);
        }

        // Save to watch later
        function saveToWatchLater(movie) {
            const watchLaterItem = {
                id: Date.now(),
                movieId: movie.id,
                movie: movie,
                addedAt: new Date().toISOString()
            };
            
            const transaction = db.transaction(['watchLater'], 'readwrite');
            const store = transaction.objectStore('watchLater');
            store.put(watchLaterItem);
            
            watchLaterList.push(watchLaterItem);
            
            // Show notification
            showNotification('Added to Watch Later!');
            
            // Update UI
            updateWatchLaterButton(movie.id, true);
        }

        // Remove from watch later
        function removeFromWatchLater(movieId) {
            const transaction = db.transaction(['watchLater'], 'readwrite');
            const store = transaction.objectStore('watchLater');
            
            // Find and delete the item
            const index = store.index('movieId');
            const request = index.getAll(movieId);
            
            request.onsuccess = () => {
                request.result.forEach(item => {
                    store.delete(item.id);
                });
                
                // Update local array
                watchLaterList = watchLaterList.filter(item => item.movieId !== movieId);
                
                // Show notification
                showNotification('Removed from Watch Later');
                
                // Update UI
                updateWatchLaterButton(movieId, false);
            };
        }

        // Check if movie is in watch later
        function isInWatchLater(movieId) {
            return watchLaterList.some(item => item.movieId === movieId);
        }

        // Update watch later button state
        function updateWatchLaterButton(movieId, isSaved) {
            const button = document.querySelector(`[data-movie-id="${movieId}"] .watch-later-btn`);
            if (button) {
                if (isSaved) {
                    button.classList.add('saved');
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    button.title = 'Remove from Watch Later';
                } else {
                    button.classList.remove('saved');
                    button.innerHTML = '<i class="fas fa-clock"></i>';
                    button.title = 'Add to Watch Later';
                }
            }
        }

        // Show notification
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: var(--youtube-red);
                color: white;
                padding: 12px 20px;
                border-radius: 25px;
                z-index: 10000;
                font-weight: 600;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Sample data for demo
        const sampleMovies = [
            {
                id: 1,
                title: "The Matrix",
                description: "A computer programmer discovers reality is a simulation",
                poster: "https://via.placeholder.com/300x450/1a1a1a/ffffff?text=The+Matrix",
                type: "movie",
                genre: "Sci-Fi",
                year: 1999,
                rating: 8.7,
                servers: [
                    { name: "Server 1", url: "https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4", quality: "720p" }
                ]
            },
            {
                id: 2,
                title: "Inception",
                description: "A thief enters dreams to steal secrets",
                poster: "https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Inception",
                type: "movie",
                genre: "Thriller",
                year: 2010,
                rating: 8.8,
                servers: [
                    { name: "Server 1", url: "https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4", quality: "720p" }
                ]
            },
            {
                id: 3,
                title: "Breaking Bad",
                description: "A chemistry teacher turns to drug making",
                poster: "https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Breaking+Bad",
                type: "series",
                genre: "Drama",
                year: 2008,
                rating: 9.5,
                servers: [
                    { name: "Server 1", url: "https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4", quality: "720p" }
                ],
                seasons: [
                    {
                        season: 1,
                        episodes: [
                            {
                                episode: 1,
                                title: "Pilot",
                                description: "Walter White receives his cancer diagnosis",
                                servers: [
                                    { name: "Server 1", url: "https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4", quality: "720p" }
                                ]
                            }
                        ]
                    }
                ]
            },
            {
                id: 4,
                title: "Netflix Live",
                description: "Live streaming channel",
                poster: "https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Netflix+Live",
                type: "live",
                genre: "Live",
                year: 2024,
                rating: 0,
                servers: [
                    { name: "Live 1", url: "https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4", quality: "Live" }
                ]
            }
        ];

        // Initialize app
        async function initApp() {
            try {
                await initDatabase();
                await loadFromIndexedDB();
                
                // If no data in IndexedDB, load sample data
                if (currentContent.length === 0) {
                    currentContent = sampleMovies;
                    sampleMovies.forEach(movie => saveMovieToIndexedDB(movie));
                }
                
                featuredContent = currentContent.slice(0, 5);
                renderFeaturedContent();
                renderContent();
                setupEventListeners();
                setupPWA();
                
            } catch (error) {
                console.error('App initialization error:', error);
                // Fallback to sample data
                currentContent = sampleMovies;
                featuredContent = currentContent.slice(0, 5);
                renderFeaturedContent();
                renderContent();
                setupEventListeners();
                setupPWA();
            }
        }

        // Render featured content carousel
        function renderFeaturedContent() {
            const carouselInner = document.getElementById('carouselInner');
            const carouselIndicators = document.getElementById('carouselIndicators');
            
            carouselInner.innerHTML = '';
            carouselIndicators.innerHTML = '';
            
            featuredContent.forEach((item, index) => {
                const carouselItem = document.createElement('div');
                carouselItem.className = 'carousel-item';
                carouselItem.innerHTML = `
                    <img src="${item.poster}" alt="${item.title}">
                    <div class="carousel-content">
                        <h2>${item.title}</h2>
                        <p>${item.description}</p>
                        <button class="play-btn" onclick="openVideo(${item.id})">
                            <i class="fas fa-play"></i> Play Now
                        </button>
                    </div>
                `;
                
                carouselInner.appendChild(carouselItem);
                
                const indicator = document.createElement('div');
                indicator.className = `indicator ${index === 0 ? 'active' : ''}`;
                indicator.addEventListener('click', () => setCarouselIndex(index));
                carouselIndicators.appendChild(indicator);
            });
        }

        // Render content based on current tab
        function renderContent() {
            const contentGrid = document.getElementById('contentGrid');
            contentGrid.innerHTML = '';
            
            let filteredContent = currentContent;
            
            // Filter by tab
            switch (currentTab) {
                case 'movies':
                    filteredContent = currentContent.filter(item => item.type === 'movie');
                    break;
                case 'series':
                    filteredContent = currentContent.filter(item => item.type === 'series');
                    break;
                case 'live':
                    filteredContent = currentContent.filter(item => item.type === 'live');
                    break;
                case 'watchlater':
                    filteredContent = watchLaterList.map(item => item.movie);
                    break;
                default:
                    // Home tab shows all content
                    break;
            }
            
            filteredContent.forEach(item => {
                const card = createContentCard(item);
                contentGrid.appendChild(card);
            });
        }

        // Create content card
        function createContentCard(item) {
            const card = document.createElement('div');
            card.className = 'content-card';
            card.setAttribute('data-movie-id', item.id);
            
            const badgeClass = `badge-${item.type}`;
            const isSaved = isInWatchLater(item.id);
            
            card.innerHTML = `
                <div class="card-img">
                    <img src="${item.poster}" alt="${item.title}" loading="lazy">
                    <div class="card-badge ${badgeClass}">${item.type.toUpperCase()}</div>
                    <button class="watch-later-btn ${isSaved ? 'saved' : ''}" 
                            onclick="toggleWatchLater(${item.id})" 
                            title="${isSaved ? 'Remove from Watch Later' : 'Add to Watch Later'}">
                        <i class="fas ${isSaved ? 'fa-check' : 'fa-clock'}"></i>
                    </button>
                </div>
                <div class="card-info">
                    <h3 class="card-title">${item.title}</h3>
                    <div class="card-meta">
                        ${item.year} • ${item.genre} • ⭐ ${item.rating}
                    </div>
                </div>
            `;
            
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.watch-later-btn')) {
                    openVideo(item.id);
                }
            });
            
            return card;
        }

        // Toggle watch later
        function toggleWatchLater(movieId) {
            const movie = currentContent.find(m => m.id === movieId);
            if (!movie) return;
            
            if (isInWatchLater(movieId)) {
                removeFromWatchLater(movieId);
            } else {
                saveToWatchLater(movie);
            }
        }

        // Open video player
        function openVideo(movieId) {
            const movie = currentContent.find(m => m.id === movieId);
            if (!movie) return;
            
            const modal = document.getElementById('videoModal');
            const title = document.getElementById('videoTitle');
            const playerContainer = document.getElementById('playerContainer');
            
            title.textContent = movie.title;
            
            // Create video element
            const video = document.createElement('video');
            video.controls = true;
            video.playsInline = true;
            video.style.width = '100%';
            video.style.height = '100%';
            
            if (movie.servers && movie.servers.length > 0) {
                video.src = movie.servers[0].url;
            }
            
            playerContainer.innerHTML = '';
            playerContainer.appendChild(video);
            
            // Initialize Plyr
            if (currentPlayer) {
                currentPlayer.destroy();
            }
            currentPlayer = new Plyr(video);
            
            modal.style.display = 'flex';
        }

        // Close video modal
        function closeVideo() {
            const modal = document.getElementById('videoModal');
            const playerContainer = document.getElementById('playerContainer');
            
            if (currentPlayer) {
                currentPlayer.destroy();
                currentPlayer = null;
            }
            
            playerContainer.innerHTML = '';
            modal.style.display = 'none';
        }

        // Setup event listeners
        function setupEventListeners() {
            // Mobile search
            document.getElementById('mobileSearchBtn').addEventListener('click', toggleMobileSearch);
            document.getElementById('closeSearchBtn').addEventListener('click', closeMobileSearch);
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', debounce(handleSearch, 300));
            searchInput.addEventListener('focus', showSearchResults);
            
            // Modal close
            document.getElementById('closeModal').addEventListener('click', closeVideo);
            document.getElementById('videoModal').addEventListener('click', (e) => {
                if (e.target.id === 'videoModal') closeVideo();
            });
            
            // Bottom navigation
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    switchTab(tab.dataset.tab);
                });
            });
            
            // Carousel controls
            document.getElementById('prevBtn').addEventListener('click', () => navigateCarousel(-1));
            document.getElementById('nextBtn').addEventListener('click', () => navigateCarousel(1));
            
            // Theme toggle
            document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        }

        // Toggle mobile search
        function toggleMobileSearch() {
            const searchContainer = document.getElementById('searchContainer');
            searchContainer.classList.toggle('show');
            if (searchContainer.classList.contains('show')) {
                document.getElementById('searchInput').focus();
            }
        }

        // Close mobile search
        function closeMobileSearch() {
            document.getElementById('searchContainer').classList.remove('show');
            document.getElementById('searchInput').value = '';
            document.getElementById('searchResults').style.display = 'none';
        }

        // Handle search
        function handleSearch() {
            const query = document.getElementById('searchInput').value.trim();
            const resultsContainer = document.getElementById('searchResults');
            
            if (query.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }
            
            const results = currentContent.filter(item =>
                item.title.toLowerCase().includes(query.toLowerCase()) ||
                item.genre.toLowerCase().includes(query.toLowerCase())
            );
            
            displaySearchResults(results);
        }

        // Display search results
        function displaySearchResults(results) {
            const resultsContainer = document.getElementById('searchResults');
            
            if (results.length === 0) {
                resultsContainer.innerHTML = '<div class="search-result-item">No results found</div>';
            } else {
                resultsContainer.innerHTML = results.map(item => `
                    <div class="search-result-item" onclick="openVideo(${item.id})">
                        <img src="${item.poster}" alt="${item.title}">
                        <div class="search-result-info">
                            <h4>${item.title}</h4>
                            <p>${item.year} • ${item.genre} • ${item.type}</p>
                        </div>
                    </div>
                `).join('');
            }
            
            resultsContainer.style.display = 'block';
        }

        // Show search results
        function showSearchResults() {
            if (document.getElementById('searchInput').value.trim().length >= 2) {
                handleSearch();
            }
        }

        // Switch tab
        function switchTab(tab) {
            currentTab = tab;
            
            // Update active tab
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            
            // Re-render content
            renderContent();
        }

        // Navigate carousel
        let currentCarouselIndex = 0;
        function navigateCarousel(direction) {
            currentCarouselIndex += direction;
            if (currentCarouselIndex < 0) currentCarouselIndex = featuredContent.length - 1;
            if (currentCarouselIndex >= featuredContent.length) currentCarouselIndex = 0;
            setCarouselIndex(currentCarouselIndex);
        }

        // Set carousel index
        function setCarouselIndex(index) {
            const carouselInner = document.getElementById('carouselInner');
            const indicators = document.querySelectorAll('.indicator');
            
            carouselInner.style.transform = `translateX(-${index * 100}%)`;
            
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
            
            currentCarouselIndex = index;
        }

        // Toggle theme
        function toggleTheme() {
            document.body.classList.toggle('light-theme');
            const icon = document.querySelector('#themeToggle i');
            icon.className = document.body.classList.contains('light-theme') 
                ? 'fas fa-sun' 
                : 'fas fa-moon';
        }

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Setup PWA
        function setupPWA() {
            // Register service worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            }
            
            // Handle app install prompt
            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
            });
        }

        // Auto-refresh data from server (detect changes)
        async function syncWithServer() {
            try {
                const response = await fetch('/api/sync.php');
                if (response.ok) {
                    const serverData = await response.json();
                    
                    // Check if data has changed
                    const localDataHash = JSON.stringify(currentContent);
                    const serverDataHash = JSON.stringify(serverData);
                    
                    if (localDataHash !== serverDataHash) {
                        currentContent = serverData;
                        serverData.forEach(movie => saveMovieToIndexedDB(movie));
                        renderContent();
                        showNotification('Content updated from server');
                    }
                }
            } catch (error) {
                console.error('Sync error:', error);
            }
        }

        // Start auto-sync every 5 minutes
        setInterval(syncWithServer, 300000);

        // Initialize app when DOM is loaded
        document.addEventListener('DOMContentLoaded', initApp);

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