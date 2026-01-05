<?php
/**
 * CineCraze Auto-Installation Script
 * Automatically sets up database and installs the application
 */

// Configuration
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'cinecraze',
    'charset' => 'utf8mb4'
];

// Enable error reporting for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineCraze Installation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #e50914;
            --primary-dark: #b20710;
            --dark: #141414;
            --dark-2: #1a1a1a;
            --light: #f5f5f5;
            --gray: #8c8c8c;
            --success: #28a745;
            --warning: #ffa500;
            --error: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark-2) 100%);
            color: var(--light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .installer {
            background: var(--dark-2);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(229, 9, 20, 0.2);
        }

        .installer-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .installer-header h1 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .installer-header p {
            opacity: 0.9;
            font-size: clamp(1rem, 2vw, 1.1rem);
        }

        .installer-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--light);
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #333;
            border-radius: 8px;
            background: var(--dark);
            color: var(--light);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
        }

        .install-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .install-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(229, 9, 20, 0.3);
        }

        .install-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }

        .status.success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .status.error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
        }

        .status.warning {
            background: rgba(255, 165, 0, 0.1);
            border: 1px solid var(--warning);
            color: var(--warning);
        }

        .progress {
            display: none;
            margin: 20px 0;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #333;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            width: 0%;
            transition: width 0.3s ease;
        }

        .hidden {
            display: none !important;
        }

        @media (max-width: 768px) {
            .installer {
                margin: 10px;
            }
            
            .installer-header,
            .installer-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <h1>
                <i class="fas fa-film"></i>
                CineCraze
            </h1>
            <p>Automatic Installation & Setup</p>
        </div>
        
        <div class="installer-body">
            <?php if (isset($_POST['install'])): ?>
                <?php
                $host = $_POST['host'] ?? 'localhost';
                $username = $_POST['username'] ?? 'root';
                $password = $_POST['password'] ?? '';
                $database = $_POST['database'] ?? 'cinecraze';
                
                $pdo = null;
                $errors = [];
                
                try {
                    // Test database connection
                    $dsn = "mysql:host={$host};charset=utf8mb4";
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create database
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$database}`");
                    
                    // Create tables
                    $sql = "
                        -- Categories table
                        CREATE TABLE IF NOT EXISTS categories (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            main_category VARCHAR(255) NOT NULL,
                            sub_categories JSON,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        );
                        
                        -- Content table
                        CREATE TABLE IF NOT EXISTS content (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(500) NOT NULL,
                            description TEXT,
                            year INT,
                            type ENUM('movie', 'series', 'live') NOT NULL,
                            sub_category VARCHAR(255),
                            country VARCHAR(255),
                            rating INT DEFAULT 0,
                            duration VARCHAR(50),
                            poster VARCHAR(500),
                            thumbnail VARCHAR(500),
                            tmdb_id INT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        );
                        
                        -- Series table (for series content)
                        CREATE TABLE IF NOT EXISTS series (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            content_id INT NOT NULL,
                            season_number INT NOT NULL,
                            season_title VARCHAR(255),
                            season_poster VARCHAR(500),
                            episode_count INT DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_season (content_id, season_number)
                        );
                        
                        -- Episodes table
                        CREATE TABLE IF NOT EXISTS episodes (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            series_id INT NOT NULL,
                            episode_number INT NOT NULL,
                            title VARCHAR(500),
                            description TEXT,
                            duration VARCHAR(50),
                            thumbnail VARCHAR(500),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_episode (series_id, episode_number)
                        );
                        
                        -- Servers table
                        CREATE TABLE IF NOT EXISTS servers (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            content_id INT NOT NULL,
                            episode_id INT NULL,
                            name VARCHAR(255) NOT NULL,
                            url VARCHAR(500) NOT NULL,
                            quality VARCHAR(50),
                            language VARCHAR(50),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
                            FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
                        );
                        
                        -- Watch Later table
                        CREATE TABLE IF NOT EXISTS watch_later (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id VARCHAR(255) NOT NULL,
                            content_id INT NOT NULL,
                            episode_id INT NULL,
                            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
                            FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_user_content (user_id, content_id, episode_id)
                        );
                        
                        -- Settings table
                        CREATE TABLE IF NOT EXISTS settings (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            key_name VARCHAR(255) UNIQUE NOT NULL,
                            key_value TEXT,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        );
                        
                        -- Insert default categories
                        INSERT IGNORE INTO categories (main_category, sub_categories) VALUES 
                        ('Movies', '[\"Action\", \"Adventure\", \"Animation\", \"Comedy\", \"Crime\", \"Documentary\", \"Drama\", \"Family\", \"Fantasy\", \"History\", \"Horror\", \"Music\", \"Mystery\", \"Romance\", \"Science Fiction\", \"TV Movie\", \"Thriller\", \"War\", \"Western\"]'),
                        ('TV Series', '[\"Action & Adventure\", \"Animation\", \"Comedy\", \"Crime\", \"Documentary\", \"Drama\", \"Family\", \"Kids\", \"Mystery\", \"News\", \"Reality\", \"Sci-Fi & Fantasy\", \"Soap\", \"Talk\", \"War & Politics\", \"Western\"]'),
                        ('Live TV', '[\"News\", \"Sports\", \"Entertainment\", \"Music\", \"Documentary\"]');
                        
                        -- Insert default settings
                        INSERT IGNORE INTO settings (key_name, key_value) VALUES 
                        ('app_name', 'CineCraze'),
                        ('app_version', '2.0.0'),
                        ('tmdb_api_key', ''),
                        ('github_token', ''),
                        ('github_repo', ''),
                        ('theme', 'dark'),
                        ('auto_update', '1');
                    ";
                    
                    $pdo->exec($sql);
                    
                    // Save configuration
                    $config_content = "<?php
// Database Configuration
define('DB_HOST', '{$host}');
define('DB_NAME', '{$database}');
define('DB_USER', '{$username}');
define('DB_PASS', '{$password}');

// Application Configuration
define('APP_NAME', 'CineCraze');
define('APP_VERSION', '2.0.0');
define('TMDB_API_BASE', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/w500');
define('DEFAULT_LANGUAGE', 'en-US');

// PWA Configuration
define('PWA_NAME', 'CineCraze');
define('PWA_SHORT_NAME', 'CineCraze');
define('PWA_THEME_COLOR', '#e50914');
define('PWA_BACKGROUND_COLOR', '#141414');
?>";
                    
                    file_put_contents('config.php', $config_content);
                    
                    echo "<div class='status success' id='status'>";
                    echo "<i class='fas fa-check-circle'></i> Installation completed successfully!";
                    echo "</div>";
                    
                    echo "<script>";
                    echo "document.getElementById('status').style.display = 'block';";
                    echo "setTimeout(function(){";
                    echo "  window.location.href = 'index.php';";
                    echo "}, 3000);";
                    echo "</script>";
                    
                } catch (Exception $e) {
                    echo "<div class='status error' id='status'>";
                    echo "<i class='fas fa-exclamation-triangle'></i> Installation failed: " . $e->getMessage();
                    echo "</div>";
                    echo "<script>document.getElementById('status').style.display = 'block';</script>";
                }
                ?>
            <?php else: ?>
                <form method="POST" id="installForm">
                    <div class="form-group">
                        <label for="host">
                            <i class="fas fa-server"></i> Database Host
                        </label>
                        <input type="text" id="host" name="host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="database">
                            <i class="fas fa-database"></i> Database Name
                        </label>
                        <input type="text" id="database" name="database" value="cinecraze" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Database Username
                        </label>
                        <input type="text" id="username" name="username" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-key"></i> Database Password
                        </label>
                        <input type="password" id="password" name="password" value="">
                    </div>
                    
                    <button type="submit" name="install" class="install-btn" id="installBtn">
                        <i class="fas fa-download"></i>
                        Install CineCraze
                    </button>
                </form>
                
                <div class="progress" id="progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.getElementById('installForm').addEventListener('submit', function() {
            const btn = document.getElementById('installBtn');
            const progress = document.getElementById('progress');
            const progressFill = document.getElementById('progressFill');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Installing...';
            progress.style.display = 'block';
            
            let width = 0;
            const interval = setInterval(() => {
                width += Math.random() * 15;
                if (width >= 100) {
                    width = 100;
                    clearInterval(interval);
                }
                progressFill.style.width = width + '%';
            }, 200);
        });
    </script>
</body>
</html>