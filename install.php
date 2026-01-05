<?php
session_start();

if (file_exists('config.php') && !isset($_GET['force'])) {
    header('Location: index.php');
    exit;
}

$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $host = trim($_POST['db_host']);
        $username = trim($_POST['db_username']);
        $password = $_POST['db_password'];
        $database = trim($_POST['db_database']);
        
        try {
            $conn = new mysqli($host, $username, $password);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            $conn->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->select_db($database);
            
            $sql = "
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `order_num` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `content` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `type` ENUM('movie', 'series', 'live') NOT NULL DEFAULT 'movie',
                `description` TEXT,
                `year` INT,
                `rating` DECIMAL(3,1),
                `duration` VARCHAR(50),
                `genre` VARCHAR(255),
                `country` VARCHAR(255),
                `language` VARCHAR(100),
                `poster_url` TEXT,
                `backdrop_url` TEXT,
                `trailer_url` TEXT,
                `category_id` INT,
                `views` INT DEFAULT 0,
                `likes` INT DEFAULT 0,
                `dislikes` INT DEFAULT 0,
                `tmdb_id` INT,
                `imdb_id` VARCHAR(50),
                `featured` BOOLEAN DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
                INDEX `idx_type` (`type`),
                INDEX `idx_featured` (`featured`),
                INDEX `idx_category` (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `sources` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `content_id` INT NOT NULL,
                `server_name` VARCHAR(100) NOT NULL,
                `quality` VARCHAR(50),
                `url` TEXT NOT NULL,
                `type` ENUM('embed', 'direct') DEFAULT 'embed',
                `language` VARCHAR(50),
                `order_num` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`content_id`) REFERENCES `content`(`id`) ON DELETE CASCADE,
                INDEX `idx_content` (`content_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `seasons` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `content_id` INT NOT NULL,
                `season_number` INT NOT NULL,
                `name` VARCHAR(255),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`content_id`) REFERENCES `content`(`id`) ON DELETE CASCADE,
                UNIQUE KEY `unique_season` (`content_id`, `season_number`),
                INDEX `idx_content` (`content_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `episodes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `season_id` INT NOT NULL,
                `episode_number` INT NOT NULL,
                `title` VARCHAR(255),
                `description` TEXT,
                `duration` VARCHAR(50),
                `thumbnail_url` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`season_id`) REFERENCES `seasons`(`id`) ON DELETE CASCADE,
                UNIQUE KEY `unique_episode` (`season_id`, `episode_number`),
                INDEX `idx_season` (`season_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `episode_sources` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `episode_id` INT NOT NULL,
                `server_name` VARCHAR(100) NOT NULL,
                `quality` VARCHAR(50),
                `url` TEXT NOT NULL,
                `type` ENUM('embed', 'direct') DEFAULT 'embed',
                `language` VARCHAR(50),
                `order_num` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
                INDEX `idx_episode` (`episode_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `subtitles` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `content_id` INT,
                `episode_id` INT,
                `language` VARCHAR(50) NOT NULL,
                `label` VARCHAR(100) NOT NULL,
                `url` TEXT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`content_id`) REFERENCES `content`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
                INDEX `idx_content` (`content_id`),
                INDEX `idx_episode` (`episode_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) UNIQUE,
                `role` ENUM('admin', 'moderator') DEFAULT 'admin',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            if ($conn->multi_query($sql)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->next_result());
            }
            
            if ($conn->error) {
                throw new Exception("Database creation error: " . $conn->error);
            }
            
            $conn->query("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
                ('site_name', 'CineCraze'),
                ('site_description', 'Your Premium Streaming Platform'),
                ('tmdb_api_key', ''),
                ('items_per_page', '20'),
                ('enable_registration', '0')
                ON DUPLICATE KEY UPDATE setting_key=setting_key");
            
            $config_content = "<?php\n";
            $config_content .= "define('DB_HOST', '" . addslashes($host) . "');\n";
            $config_content .= "define('DB_USERNAME', '" . addslashes($username) . "');\n";
            $config_content .= "define('DB_PASSWORD', '" . addslashes($password) . "');\n";
            $config_content .= "define('DB_DATABASE', '" . addslashes($database) . "');\n";
            $config_content .= "define('SITE_URL', 'http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "');\n";
            $config_content .= "?>";
            
            file_put_contents('config.php', $config_content);
            
            $conn->close();
            $_SESSION['install_step'] = 2;
            $step = 2;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($step === 2) {
        require_once 'config.php';
        require_once 'includes/db.php';
        
        $username = trim($_POST['admin_username']);
        $password = $_POST['admin_password'];
        $email = trim($_POST['admin_email']);
        
        if (strlen($username) < 4) {
            $error = "Username must be at least 4 characters";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param("sss", $username, $hashed_password, $email);
            
            if ($stmt->execute()) {
                unset($_SESSION['install_step']);
                $success = "Installation completed successfully!";
                $step = 3;
            } else {
                $error = "Failed to create admin user: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineCraze Installation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(15px, 3vw, 20px);
        }
        
        .install-container {
            background: white;
            border-radius: clamp(12px, 2vw, 20px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: clamp(30px, 5vw, 50px);
        }
        
        .logo {
            text-align: center;
            margin-bottom: clamp(25px, 4vw, 40px);
        }
        
        .logo i {
            font-size: clamp(3rem, 8vw, 4rem);
            color: #e50914;
            margin-bottom: 15px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: clamp(15px, 3vw, 20px);
            font-size: clamp(1.5rem, 4vw, 2rem);
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: clamp(25px, 4vw, 40px);
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: clamp(30px, 5vw, 40px);
            position: relative;
            flex-wrap: wrap;
            gap: 10px;
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
            width: clamp(40px, 8vw, 50px);
            height: clamp(40px, 8vw, 50px);
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #999;
            position: relative;
            z-index: 1;
            font-size: clamp(0.9rem, 2vw, 1.2rem);
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
            margin-bottom: clamp(20px, 3vw, 25px);
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        input, select {
            width: 100%;
            padding: clamp(12px, 2.5vw, 15px);
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: clamp(0.9rem, 2vw, 1rem);
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: clamp(14px, 3vw, 18px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: clamp(1rem, 2.2vw, 1.1rem);
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: clamp(12px, 2.5vw, 15px);
            border-radius: 8px;
            margin-bottom: clamp(20px, 3vw, 25px);
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #66bb6a;
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: clamp(20px, 3vw, 30px);
        }
        
        .success-icon i {
            font-size: clamp(4rem, 10vw, 5rem);
            color: #4caf50;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: clamp(20px, 3vw, 30px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .help-text {
            font-size: clamp(0.8rem, 1.8vw, 0.9rem);
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 600px) {
            .steps {
                justify-content: center;
            }
            
            .steps::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <i class="fas fa-film"></i>
            <h1>CineCraze</h1>
            <p class="subtitle">Installation Wizard</p>
        </div>
        
        <div class="steps">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 1): ?>
            <h2 style="margin-bottom: 20px; color: #333; font-size: clamp(1.2rem, 3vw, 1.5rem);">Database Configuration</h2>
            <form method="POST">
                <input type="hidden" name="step" value="1">
                
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                    <p class="help-text">Usually "localhost"</p>
                </div>
                
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_username" required>
                </div>
                
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_password">
                </div>
                
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_database" value="cinecraze" required>
                    <p class="help-text">Database will be created if it doesn't exist</p>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-arrow-right"></i> Continue
                </button>
            </form>
        
        <?php elseif ($step === 2): ?>
            <h2 style="margin-bottom: 20px; color: #333; font-size: clamp(1.2rem, 3vw, 1.5rem);">Create Admin Account</h2>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="admin_username" required minlength="4">
                    <p class="help-text">Minimum 4 characters</p>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="admin_email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="admin_password" required minlength="6">
                    <p class="help-text">Minimum 6 characters</p>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-check"></i> Complete Installation
                </button>
            </form>
        
        <?php elseif ($step === 3): ?>
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h2 style="margin-bottom: 20px; color: #333; text-align: center; font-size: clamp(1.2rem, 3vw, 1.5rem);">Installation Complete!</h2>
            
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> CineCraze has been successfully installed!
            </div>
            
            <div class="btn-group">
                <a href="index.php" class="btn" style="text-decoration: none; text-align: center;">
                    <i class="fas fa-home"></i> Visit Site
                </a>
                <a href="admin/" class="btn btn-secondary" style="text-decoration: none; text-align: center;">
                    <i class="fas fa-user-shield"></i> Admin Panel
                </a>
            </div>
            
            <div class="alert alert-error" style="margin-top: 30px;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Security Warning:</strong> Please delete the install.php file for security reasons.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
