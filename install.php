<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('INSTALL_MODE', true);

if (file_exists('config/database.php') && !isset($_GET['force'])) {
    die('Application is already installed. Delete config/database.php to reinstall or add ?force=1 to URL.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $_SESSION['db_host'] = $_POST['db_host'] ?? 'localhost';
        $_SESSION['db_name'] = $_POST['db_name'] ?? '';
        $_SESSION['db_user'] = $_POST['db_user'] ?? '';
        $_SESSION['db_pass'] = $_POST['db_pass'] ?? '';
        
        try {
            $conn = new PDO(
                "mysql:host={$_SESSION['db_host']}", 
                $_SESSION['db_user'], 
                $_SESSION['db_pass']
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $conn->exec("CREATE DATABASE IF NOT EXISTS `{$_SESSION['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $success[] = "Database created successfully!";
            
            header('Location: install.php?step=2');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database connection failed: " . $e->getMessage();
        }
    } elseif ($step === 2) {
        $_SESSION['admin_username'] = $_POST['admin_username'] ?? '';
        $_SESSION['admin_email'] = $_POST['admin_email'] ?? '';
        $_SESSION['admin_password'] = $_POST['admin_password'] ?? '';
        $_SESSION['site_name'] = $_POST['site_name'] ?? 'MovieStream';
        $_SESSION['site_url'] = $_POST['site_url'] ?? '';
        
        if (empty($_SESSION['admin_username']) || empty($_SESSION['admin_password'])) {
            $errors[] = "Username and password are required!";
        } else {
            header('Location: install.php?step=3');
            exit;
        }
    } elseif ($step === 3) {
        $_SESSION['tmdb_api_key'] = $_POST['tmdb_api_key'] ?? '';
        $_SESSION['youtube_api_key'] = $_POST['youtube_api_key'] ?? '';
        
        header('Location: install.php?step=4');
        exit;
    }
}

if ($step === 4 && !empty($_SESSION['db_name'])) {
    try {
        $conn = new PDO(
            "mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']}", 
            $_SESSION['db_user'], 
            $_SESSION['db_pass']
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) UNIQUE NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) UNIQUE NOT NULL,
            `email` VARCHAR(255) UNIQUE NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `last_login` TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `servers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `url` VARCHAR(500) NOT NULL,
            `type` ENUM('direct', 'youtube', 'embed') DEFAULT 'direct',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `movies` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `tmdb_id` INT UNIQUE,
            `title` VARCHAR(255) NOT NULL,
            `original_title` VARCHAR(255),
            `slug` VARCHAR(255) UNIQUE NOT NULL,
            `description` TEXT,
            `poster` VARCHAR(500),
            `backdrop` VARCHAR(500),
            `year` INT,
            `rating` DECIMAL(3,1) DEFAULT 0.0,
            `runtime` INT,
            `genres` VARCHAR(255),
            `language` VARCHAR(50),
            `country` VARCHAR(100),
            `director` VARCHAR(255),
            `cast` TEXT,
            `trailer_url` VARCHAR(500),
            `views` INT DEFAULT 0,
            `is_featured` TINYINT(1) DEFAULT 0,
            `is_published` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tmdb (tmdb_id),
            INDEX idx_slug (slug),
            INDEX idx_year (year),
            INDEX idx_rating (rating),
            INDEX idx_featured (is_featured),
            INDEX idx_published (is_published),
            FULLTEXT idx_search (title, description)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `movie_servers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT NOT NULL,
            `server_id` INT NOT NULL,
            `video_url` VARCHAR(1000) NOT NULL,
            `quality` VARCHAR(50),
            `video_type` ENUM('mp4', 'mkv', 'avi', 'webm', 'mpd', 'dash', 'youtube') DEFAULT 'mp4',
            `is_primary` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
            FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
            INDEX idx_movie (movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` TEXT,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `views_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT NOT NULL,
            `user_id` INT NULL,
            `ip_address` VARCHAR(45),
            `user_agent` VARCHAR(255),
            `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
            INDEX idx_movie (movie_id),
            INDEX idx_date (viewed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $conn->exec($sql);
        $success[] = "Database tables created successfully!";
        
        $hashedPassword = password_hash($_SESSION['admin_password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['admin_username'], $_SESSION['admin_email'], $hashedPassword]);
        $success[] = "Admin account created successfully!";
        
        $settings = [
            ['site_name', $_SESSION['site_name']],
            ['site_url', $_SESSION['site_url']],
            ['tmdb_api_key', $_SESSION['tmdb_api_key'] ?? ''],
            ['youtube_api_key', $_SESSION['youtube_api_key'] ?? ''],
            ['items_per_page', '20'],
            ['enable_registration', '1'],
            ['maintenance_mode', '0']
        ];
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        $success[] = "Settings saved successfully!";
        
        $configContent = "<?php
defined('APP_ACCESS') or die('Direct access not permitted');

define('DB_HOST', '{$_SESSION['db_host']}');
define('DB_NAME', '{$_SESSION['db_name']}');
define('DB_USER', '{$_SESSION['db_user']}');
define('DB_PASS', '{$_SESSION['db_pass']}');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', '{$_SESSION['site_url']}');
define('SITE_NAME', '{$_SESSION['site_name']}');

define('TMDB_API_KEY', '{$_SESSION['tmdb_api_key']}');
define('YOUTUBE_API_KEY', '{$_SESSION['youtube_api_key']}');

date_default_timezone_set('UTC');
";
        
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        file_put_contents('config/database.php', $configContent);
        $success[] = "Configuration file created successfully!";
        
        if (!file_exists('uploads/movies/.htaccess')) {
            $uploadsDir = ['uploads/movies', 'uploads/posters', 'uploads/temp'];
            foreach ($uploadsDir as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($dir . '/.htaccess', "Options -Indexes\n");
            }
        }
        
        if (!file_exists('.htaccess')) {
            $htaccess = "RewriteEngine On
RewriteBase /

# Redirect to HTTPS (optional, uncomment if needed)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST%}%{REQUEST_URI} [L,R=301]

# Block access to sensitive files
<FilesMatch \"^(config|includes|install).*\\.php$\">
    Order allow,deny
    Deny from all
</FilesMatch>

# Allow specific public files
<FilesMatch \"^(index|watch|search|category|login|register|logout)\\.php$\">
    Order allow,deny
    Allow from all
</FilesMatch>

# Protect config directory
<IfModule mod_rewrite.c>
    RewriteRule ^config/ - [F,L]
</IfModule>

# Pretty URLs
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^movie/([a-zA-Z0-9-]+)$ watch.php?slug=$1 [L,QSA]
RewriteRule ^category/([a-zA-Z0-9-]+)$ category.php?slug=$1 [L,QSA]
RewriteRule ^search$ search.php [L,QSA]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options \"nosniff\"
    Header set X-Frame-Options \"SAMEORIGIN\"
    Header set X-XSS-Protection \"1; mode=block\"
</IfModule>

# PHP settings
<IfModule mod_php7.c>
    php_value upload_max_filesize 2048M
    php_value post_max_size 2048M
    php_value max_execution_time 3600
    php_value max_input_time 3600
</IfModule>
";
            file_put_contents('.htaccess', $htaccess);
            $success[] = ".htaccess file created successfully!";
        }
        
        session_destroy();
        
    } catch (PDOException $e) {
        $errors[] = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieStream Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 700px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 1;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #4caf50;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .success-icon {
            font-size: 64px;
            color: #4caf50;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success-message {
            text-align: center;
        }
        
        .success-message h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .success-links {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .success-links a {
            flex: 1;
            padding: 14px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }
        
        .requirements {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .requirements h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .requirement-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .requirement-item:last-child {
            border-bottom: none;
        }
        
        .status-ok {
            color: #4caf50;
            font-weight: 500;
        }
        
        .status-error {
            color: #f44336;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 MovieStream</h1>
            <p>Complete Installation Wizard</p>
        </div>
        
        <div class="content">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <?php foreach ($success as $msg): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="steps">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">3</div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?> <?php echo $step > 4 ? 'completed' : ''; ?>">4</div>
            </div>
            
            <?php if ($step === 1): ?>
                <h2 style="margin-bottom: 20px;">Step 1: Database Configuration</h2>
                
                <div class="requirements">
                    <h3>System Requirements</h3>
                    <div class="requirement-item">
                        <span>PHP Version (7.4+)</span>
                        <span class="<?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'status-ok' : 'status-error'; ?>">
                            <?php echo PHP_VERSION; ?>
                        </span>
                    </div>
                    <div class="requirement-item">
                        <span>PDO MySQL Extension</span>
                        <span class="<?php echo extension_loaded('pdo_mysql') ? 'status-ok' : 'status-error'; ?>">
                            <?php echo extension_loaded('pdo_mysql') ? 'Installed' : 'Missing'; ?>
                        </span>
                    </div>
                    <div class="requirement-item">
                        <span>cURL Extension</span>
                        <span class="<?php echo extension_loaded('curl') ? 'status-ok' : 'status-error'; ?>">
                            <?php echo extension_loaded('curl') ? 'Installed' : 'Missing'; ?>
                        </span>
                    </div>
                    <div class="requirement-item">
                        <span>JSON Extension</span>
                        <span class="<?php echo extension_loaded('json') ? 'status-ok' : 'status-error'; ?>">
                            <?php echo extension_loaded('json') ? 'Installed' : 'Missing'; ?>
                        </span>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <div class="help-text">Usually "localhost" for most hosting services</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" required>
                        <div class="help-text">Database will be created if it doesn't exist</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass">
                    </div>
                    
                    <button type="submit" class="btn">Continue to Next Step →</button>
                </form>
            
            <?php elseif ($step === 2): ?>
                <h2 style="margin-bottom: 20px;">Step 2: Admin Account Setup</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Admin Username</label>
                        <input type="text" name="admin_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_password" required minlength="6">
                        <div class="help-text">Minimum 6 characters</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="MovieStream" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Site URL</label>
                        <input type="url" name="site_url" placeholder="https://yourdomain.com" required>
                        <div class="help-text">Your website's full URL (including https://)</div>
                    </div>
                    
                    <button type="submit" class="btn">Continue to Next Step →</button>
                </form>
            
            <?php elseif ($step === 3): ?>
                <h2 style="margin-bottom: 20px;">Step 3: API Configuration (Optional)</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label>TMDB API Key</label>
                        <input type="text" name="tmdb_api_key">
                        <div class="help-text">Get your free API key from <a href="https://www.themoviedb.org/settings/api" target="_blank">TheMovieDB.org</a></div>
                    </div>
                    
                    <div class="form-group">
                        <label>YouTube API Key</label>
                        <input type="text" name="youtube_api_key">
                        <div class="help-text">Get your API key from <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a></div>
                    </div>
                    
                    <div class="help-text" style="margin-bottom: 20px;">
                        <strong>Note:</strong> You can skip this step and add API keys later from the admin panel.
                    </div>
                    
                    <button type="submit" class="btn">Complete Installation →</button>
                </form>
            
            <?php elseif ($step === 4 && empty($errors)): ?>
                <div class="success-message">
                    <div class="success-icon">✓</div>
                    <h2>Installation Completed Successfully!</h2>
                    <p>Your MovieStream application is now ready to use.</p>
                    
                    <div class="alert alert-success" style="margin-top: 30px; text-align: left;">
                        <strong>Important:</strong> For security reasons, please delete or rename the install.php file.
                    </div>
                    
                    <div class="success-links">
                        <a href="admin/login.php">Admin Dashboard</a>
                        <a href="index.php">Visit Website</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
