<?php
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';
require_once 'includes/db.php';

$site_name = getSetting('site_name', 'CineCraze');
$site_description = getSetting('site_description', 'Your Premium Streaming Platform');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
    <meta name="theme-color" content="#e50914">
    <title><?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($site_name); ?>">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/images/icon-512.png">
    <link rel="apple-touch-icon" href="assets/images/icon-192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    
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
            --transition: all 0.3s ease;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            --radius: 8px;
            --header-height: 70px;
            --footer-height: 120px;
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
        }

        body.light-theme {
            background-color: var(--youtube-light);
            color: var(--dark);
        }

        /* Header Styles - Responsive */
        header {
            background: var(--youtube-dark);
            padding: clamp(10px, 2vw, 15px) clamp(3%, 5vw, 5%);
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
            gap: clamp(10px, 2vw, 20px);
        }

        header.scrolled {
            background-color: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: clamp(8px, 1.5vw, 10px);
            font-size: clamp(1.2rem, 3.5vw, 1.75rem);
            font-weight: 700;
            color: var(--youtube-red);
            text-decoration: none;
            white-space: nowrap;
        }

        .logo i {
            color: var(--youtube-red);
            font-size: clamp(1.5rem, 4vw, 2rem);
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: clamp(200px, 40%, 600px);
            transition: width 0.3s ease;
        }

        .search-input-container {
            position: relative;
            width: 100%;
        }

        .search-container input {
            width: 100%;
            padding: clamp(8px, 1.5vw, 12px) clamp(12px, 2vw, 15px);
            padding-right: 40px;
            border-radius: 30px;
            border: none;
            background-color: var(--youtube-gray);
            color: var(--light);
            font-size: clamp(0.875rem, 2vw, 1rem);
            transition: var(--transition);
        }

        .search-container input:focus {
            outline: none;
            background-color: var(--dark-3);
            box-shadow: 0 0 0 2px var(--youtube-red);
        }

        .search-results {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            width: 100%;
            background-color: var(--dark-3);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            max-height: clamp(250px, 50vh, 400px);
            overflow-y: auto;
            display: none;
            z-index: 1001;
        }

        .search-result-item {
            padding: clamp(10px, 2vw, 12px) clamp(12px, 2.5vw, 15px);
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 15px);
            border-bottom: 1px solid var(--dark-2);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-result-item:hover {
            background-color: var(--dark-2);
        }

        .search-result-item img {
            width: clamp(40px, 8vw, 50px);
            height: clamp(56px, 11vw, 70px);
            object-fit: cover;
            border-radius: 4px;
        }

        .search-result-info h4 {
            font-size: clamp(0.875rem, 2vw, 1rem);
            margin-bottom: 5px;
        }

        .search-result-info p {
            font-size: clamp(0.75rem, 1.8vw, 0.875rem);
            color: var(--gray);
        }

        .close-search-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            z-index: 3;
            font-size: clamp(1rem, 2.5vw, 1.25rem);
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 20px);
        }

        .theme-toggle, .install-btn {
            background: none;
            border: none;
            color: var(--light);
            font-size: clamp(1.1rem, 2.5vw, 1.375rem);
            cursor: pointer;
            transition: var(--transition);
            padding: clamp(5px, 1vw, 8px);
        }

        .theme-toggle:hover, .install-btn:hover {
            color: var(--youtube-red);
            transform: scale(1.1);
        }

        .user-profile {
            width: clamp(32px, 6vw, 40px);
            height: clamp(32px, 6vw, 40px);
            border-radius: 50%;
            background-color: var(--youtube-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        /* Main Content */
        main {
            padding-top: calc(var(--header-height) + clamp(15px, 3vw, 20px));
            min-height: calc(100vh - var(--header-height) - var(--footer-height));
        }

        /* Carousel - Responsive */
        .carousel {
            margin: 0 clamp(3%, 5vw, 5%) clamp(20px, 4vw, 30px);
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
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carousel-content {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: clamp(15px, 4vw, 30px);
            background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, transparent 100%);
        }

        .carousel-content h2 {
            font-size: clamp(1.2rem, 4vw, 2.5rem);
            margin-bottom: clamp(5px, 1.5vw, 10px);
        }

        .carousel-content p {
            max-width: clamp(300px, 80%, 600px);
            margin-bottom: clamp(10px, 2.5vw, 20px);
            color: var(--light-2);
            font-size: clamp(0.875rem, 2vw, 1rem);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 clamp(10px, 2vw, 20px);
            transform: translateY(-50%);
        }

        .carousel-btn {
            background: rgba(0,0,0,0.5);
            border: none;
            color: white;
            width: clamp(35px, 7vw, 50px);
            height: clamp(35px, 7vw, 50px);
            border-radius: 50%;
            font-size: clamp(0.875rem, 2.5vw, 1.25rem);
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
            bottom: clamp(10px, 2vw, 20px);
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: clamp(5px, 1.5vw, 10px);
        }

        .indicator {
            width: clamp(8px, 1.5vw, 12px);
            height: clamp(8px, 1.5vw, 12px);
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: var(--transition);
        }

        .indicator.active {
            background: var(--youtube-red);
            transform: scale(1.2);
        }

        /* Filters Section - Responsive */
        .filters-section {
            padding: 0 clamp(3%, 5vw, 5%);
            margin-bottom: clamp(20px, 4vw, 30px);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: clamp(15px, 3vw, 20px);
            flex-wrap: wrap;
            gap: clamp(10px, 2vw, 15px);
        }

        .section-title {
            font-size: clamp(1.2rem, 3.5vw, 1.5rem);
            font-weight: 700;
        }

        .filters-row {
            display: flex;
            flex-wrap: wrap;
            gap: clamp(10px, 2vw, 15px);
            margin-bottom: clamp(15px, 3vw, 20px);
        }

        .filter-group {
            flex: 1;
            min-width: clamp(150px, 30%, 200px);
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-size: clamp(0.75rem, 2vw, 0.875rem);
            color: var(--gray);
        }

        .filter-select {
            width: 100%;
            padding: clamp(10px, 2vw, 12px) clamp(12px, 2.5vw, 15px);
            border-radius: var(--radius);
            background-color: var(--youtube-gray);
            border: 1px solid var(--dark-2);
            color: var(--light);
            font-size: clamp(0.875rem, 2vw, 1rem);
            cursor: pointer;
        }

        .view-toggle {
            display: flex;
            gap: clamp(8px, 1.5vw, 10px);
        }

        .view-btn {
            background-color: var(--youtube-gray);
            border: none;
            color: var(--light);
            width: clamp(35px, 6vw, 40px);
            height: clamp(35px, 6vw, 40px);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        .view-btn.active, .view-btn:hover {
            background-color: var(--youtube-red);
        }

        /* Content Grid - Fully Responsive */
        .content-container {
            padding: 0 clamp(3%, 5vw, 5%) clamp(30px, 6vw, 50px);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(clamp(150px, 20vw, 220px), 1fr));
            gap: clamp(15px, 3vw, 25px);
        }

        .content-list {
            display: flex;
            flex-direction: column;
            gap: clamp(10px, 2vw, 15px);
            display: none;
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
            transform: translateY(-10px);
            box-shadow: var(--shadow);
        }

        .content-card.list {
            display: flex;
            flex-direction: row;
            min-height: clamp(120px, 20vw, 150px);
        }

        .content-card.list .card-img {
            width: clamp(80px, 15vw, 120px);
            height: 100%;
        }

        .card-img {
            width: 100%;
            height: 100%;
            position: relative;
            aspect-ratio: 2/3;
        }

        .content-card.grid .card-img {
            height: auto;
        }

        .card-img img {
            display: block;
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
            top: clamp(8px, 1.5vw, 10px);
            left: clamp(8px, 1.5vw, 10px);
            color: white;
            padding: clamp(4px, 1vw, 6px) clamp(8px, 1.5vw, 10px);
            border-radius: 4px;
            font-size: clamp(0.7rem, 1.8vw, 0.875rem);
            font-weight: 700;
            z-index: 2;
        }

        .badge-movie { background-color: var(--movie-badge); }
        .badge-series { background-color: var(--series-badge); }
        .badge-live { background-color: var(--live-badge); }

        .card-info {
            padding: clamp(10px, 2vw, 15px);
            background-color: var(--youtube-dark-gray);
        }

        .content-card.grid .card-info {
            min-height: clamp(60px, 10vw, 80px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .content-card.list .card-info {
            flex: 1;
            padding: clamp(12px, 2.5vw, 20px);
        }

        .card-title {
            font-weight: 600;
            line-height: 1.4;
            color: var(--light);
            margin-bottom: clamp(4px, 1vw, 8px);
            font-size: clamp(0.875rem, 2vw, 1rem);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-meta {
            display: flex;
            gap: clamp(8px, 1.5vw, 12px);
            font-size: clamp(0.75rem, 1.8vw, 0.875rem);
            color: var(--gray);
            flex-wrap: wrap;
        }

        .card-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Video Player Modal - Responsive */
        .player-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 2000;
            overflow-y: auto;
            padding: clamp(10px, 2vw, 20px);
        }

        .player-modal.active {
            display: flex;
            flex-direction: column;
        }

        .player-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: clamp(10px, 2vw, 20px);
            background: rgba(0,0,0,0.8);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .player-title {
            font-size: clamp(1rem, 3vw, 1.5rem);
            font-weight: 600;
        }

        .close-player {
            background: var(--youtube-red);
            border: none;
            color: white;
            width: clamp(35px, 6vw, 40px);
            height: clamp(35px, 6vw, 40px);
            border-radius: 50%;
            font-size: clamp(1rem, 2.5vw, 1.25rem);
            cursor: pointer;
            transition: var(--transition);
        }

        .close-player:hover {
            transform: scale(1.1);
        }

        .player-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            gap: clamp(15px, 3vw, 20px);
        }

        .player-video {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: var(--radius);
            overflow: hidden;
        }

        .player-video iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .player-controls {
            background: var(--dark-2);
            padding: clamp(15px, 3vw, 20px);
            border-radius: var(--radius);
        }

        .player-tabs {
            display: flex;
            gap: clamp(8px, 1.5vw, 10px);
            margin-bottom: clamp(15px, 3vw, 20px);
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .player-tab {
            padding: clamp(8px, 1.5vw, 10px) clamp(15px, 3vw, 20px);
            background: var(--dark-3);
            border: none;
            color: var(--light);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        .player-tab.active {
            background: var(--youtube-red);
        }

        .servers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(clamp(100px, 20vw, 150px), 1fr));
            gap: clamp(8px, 1.5vw, 10px);
        }

        .server-btn {
            padding: clamp(10px, 2vw, 12px);
            background: var(--dark-3);
            border: 2px solid var(--dark-3);
            color: var(--light);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        .server-btn:hover, .server-btn.active {
            border-color: var(--youtube-red);
            background: var(--youtube-red);
        }

        /* Footer - Responsive */
        footer {
            background: var(--dark-2);
            padding: clamp(30px, 5vw, 50px) clamp(3%, 5vw, 5%);
            margin-top: clamp(40px, 6vw, 60px);
            border-top: 1px solid var(--dark-3);
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(clamp(200px, 30%, 250px), 1fr));
            gap: clamp(30px, 5vw, 40px);
        }

        .footer-section h3 {
            margin-bottom: clamp(15px, 3vw, 20px);
            font-size: clamp(1rem, 2.5vw, 1.25rem);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: clamp(8px, 1.5vw, 10px);
        }

        .footer-section a {
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        .footer-section a:hover {
            color: var(--youtube-red);
        }

        .footer-bottom {
            text-align: center;
            margin-top: clamp(30px, 5vw, 40px);
            padding-top: clamp(20px, 4vw, 30px);
            border-top: 1px solid var(--dark-3);
            color: var(--gray);
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        .loading {
            text-align: center;
            padding: clamp(40px, 8vw, 60px);
            font-size: clamp(1rem, 2.5vw, 1.25rem);
        }

        .loading i {
            font-size: clamp(2rem, 5vw, 3rem);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Smartphone specific (320px - 480px) */
        @media (max-width: 480px) {
            .logo span {
                display: none;
            }
            
            .search-container {
                max-width: 100%;
            }
            
            .filters-row {
                flex-direction: column;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .content-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .player-modal {
                padding: 0;
            }
            
            .player-content {
                gap: 10px;
            }
        }

        /* Tablet (481px - 768px) */
        @media (min-width: 481px) and (max-width: 768px) {
            .content-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Laptop (769px - 1024px) */
        @media (min-width: 769px) and (max-width: 1024px) {
            .content-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Desktop (1025px - 1440px) */
        @media (min-width: 1025px) and (max-width: 1440px) {
            .content-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        /* Large Desktop & TV (1441px+) */
        @media (min-width: 1441px) {
            .content-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            
            :root {
                --header-height: 80px;
            }
        }

        /* 4K TV (2560px+) */
        @media (min-width: 2560px) {
            .content-grid {
                grid-template-columns: repeat(8, 1fr);
            }
            
            .carousel {
                height: 800px;
            }
        }

        /* Install Prompt */
        .install-prompt {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--youtube-red);
            color: white;
            padding: clamp(12px, 2.5vw, 15px) clamp(20px, 4vw, 25px);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            z-index: 1500;
            display: none;
            align-items: center;
            gap: clamp(15px, 3vw, 20px);
            max-width: 90%;
        }

        .install-prompt button {
            background: white;
            color: var(--youtube-red);
            border: none;
            padding: clamp(8px, 1.5vw, 10px) clamp(15px, 3vw, 20px);
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: clamp(0.875rem, 2vw, 1rem);
        }

        .install-prompt .close-prompt {
            background: transparent;
            color: white;
            padding: clamp(5px, 1vw, 8px);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header id="header">
        <a href="index.php" class="logo">
            <i class="fas fa-film"></i>
            <span><?php echo htmlspecialchars($site_name); ?></span>
        </a>
        
        <div class="search-container">
            <div class="search-input-container">
                <input type="text" id="searchInput" placeholder="Search movies, series..." autocomplete="off">
                <button class="close-search-btn" id="closeSearchBtn" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-results" id="searchResults"></div>
        </div>
        
        <div class="header-controls">
            <button class="install-btn" id="installBtn" style="display: none;" title="Install App">
                <i class="fas fa-download"></i>
            </button>
            <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
            <div class="user-profile" title="Profile">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Carousel -->
        <div class="carousel" id="carousel">
            <div class="carousel-inner" id="carouselInner"></div>
            <div class="carousel-controls">
                <button class="carousel-btn" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                <button class="carousel-btn" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="carousel-indicators" id="carouselIndicators"></div>
        </div>

        <!-- Filters -->
        <section class="filters-section">
            <div class="section-header">
                <h2 class="section-title">Browse Content</h2>
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid" title="Grid View">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn" data-view="list" title="List View">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            
            <div class="filters-row">
                <div class="filter-group">
                    <label>Category</label>
                    <select class="filter-select" id="categoryFilter">
                        <option value="">All Categories</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Type</label>
                    <select class="filter-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="movie">Movies</option>
                        <option value="series">Series</option>
                        <option value="live">Live</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Genre</label>
                    <select class="filter-select" id="genreFilter">
                        <option value="">All Genres</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Year</label>
                    <select class="filter-select" id="yearFilter">
                        <option value="">All Years</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Content Grid -->
        <div class="content-container">
            <div class="content-grid" id="contentGrid">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading content...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Video Player Modal -->
    <div class="player-modal" id="playerModal">
        <div class="player-header">
            <h2 class="player-title" id="playerTitle">Player</h2>
            <button class="close-player" id="closePlayer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="player-content">
            <div class="player-video" id="playerVideo">
                <iframe id="videoFrame" allowfullscreen></iframe>
            </div>
            
            <div class="player-controls">
                <div class="player-tabs" id="playerTabs">
                    <button class="player-tab active" data-tab="servers">Servers</button>
                    <button class="player-tab" data-tab="info">Info</button>
                    <button class="player-tab" data-tab="related">Related</button>
                </div>
                
                <div id="serversContent" class="servers-grid"></div>
                <div id="infoContent" style="display: none;"></div>
                <div id="relatedContent" style="display: none;" class="content-grid"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About</h3>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Careers</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Help</h3>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Support</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Follow Us</h3>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Install Prompt -->
    <div class="install-prompt" id="installPrompt">
        <span>Install <?php echo htmlspecialchars($site_name); ?> for a better experience!</span>
        <button id="installPromptBtn">Install</button>
        <button class="close-prompt" id="closePrompt">✕</button>
    </div>

    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
