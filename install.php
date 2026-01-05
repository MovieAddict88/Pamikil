<?php
/**
 * CineCraze Auto-Installation Script
 * Automatically sets up PHP/MySQL database and application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class CineCrazeInstaller {
    private $db_host = 'localhost';
    private $db_name = 'cinecraze_db';
    private $db_user = 'root';
    private $db_pass = '';
    private $connection;
    
    public function __construct() {
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>CineCraze Auto-Installation</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
                    color: white; 
                    margin: 0; 
                    padding: 20px;
                    min-height: 100vh;
                }
                .container { 
                    max-width: 800px; 
                    margin: 0 auto; 
                    background: #1a1a1a; 
                    border-radius: 15px; 
                    padding: 30px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px;
                    padding: 20px;
                    background: linear-gradient(135deg, #e50914 0%, #b20710 100%);
                    border-radius: 10px;
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 2.5rem; 
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
                }
                .step { 
                    background: #2d2d2d; 
                    margin: 20px 0; 
                    padding: 20px; 
                    border-radius: 10px; 
                    border-left: 5px solid #e50914;
                }
                .success { 
                    background: rgba(70, 211, 105, 0.1); 
                    border-left-color: #46d369; 
                    color: #46d369;
                }
                .error { 
                    background: rgba(244, 6, 18, 0.1); 
                    border-left-color: #f40612; 
                    color: #f40612;
                }
                .warning { 
                    background: rgba(255, 165, 0, 0.1); 
                    border-left-color: #ffa500; 
                    color: #ffa500;
                }
                .progress { 
                    width: 100%; 
                    height: 20px; 
                    background: #333; 
                    border-radius: 10px; 
                    overflow: hidden;
                    margin: 20px 0;
                }
                .progress-bar { 
                    height: 100%; 
                    background: linear-gradient(90deg, #e50914, #ff0000); 
                    transition: width 0.3s ease;
                }
                .config-form { 
                    background: #2d2d2d; 
                    padding: 20px; 
                    border-radius: 10px; 
                    margin: 20px 0;
                }
                .form-group { 
                    margin: 15px 0;
                }
                label { 
                    display: block; 
                    margin-bottom: 5px; 
                    font-weight: bold;
                }
                input, select { 
                    width: 100%; 
                    padding: 10px; 
                    border: none; 
                    border-radius: 5px; 
                    background: #333; 
                    color: white;
                }
                button { 
                    background: #e50914; 
                    color: white; 
                    border: none; 
                    padding: 15px 30px; 
                    border-radius: 5px; 
                    cursor: pointer; 
                    font-size: 16px;
                    margin: 10px 5px;
                }
                button:hover { 
                    background: #b20710; 
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎬 CineCraze Installation</h1>
                    <p>Auto-setup for PHP/MySQL Movie Streaming Platform</p>
                </div>";
    }
    
    public function install() {
        // Check if installation is needed
        if (file_exists('config/database.php')) {
            echo "<div class='step warning'>
                    <h3>⚠️ Installation Already Completed</h3>
                    <p>CineCraze is already installed. Configuration files found.</p>
                    <button onclick='location.reload()'>Reinstall</button>
                    <button onclick='window.location.href=\"index.php\"'>Go to Site</button>
                  </div>";
            echo "</div></body></html>";
            return;
        }
        
        // Show configuration form
        $this->showConfigForm();
    }
    
    private function showConfigForm() {
        echo "<div class='step'>
                <h3>📋 Database Configuration</h3>
                <p>Configure your MySQL database connection settings:</p>
                
                <form method='POST' class='config-form'>
                    <div class='form-group'>
                        <label>Database Host:</label>
                        <input type='text' name='db_host' value='localhost' required>
                    </div>
                    <div class='form-group'>
                        <label>Database Name:</label>
                        <input type='text' name='db_name' value='cinecraze_db' required>
                    </div>
                    <div class='form-group'>
                        <label>Database Username:</label>
                        <input type='text' name='db_user' value='root' required>
                    </div>
                    <div class='form-group'>
                        <label>Database Password:</label>
                        <input type='password' name='db_pass' value=''>
                    </div>
                    <div class='form-group'>
                        <label>Admin Email:</label>
                        <input type='email' name='admin_email' required>
                    </div>
                    <div class='form-group'>
                        <label>Admin Password:</label>
                        <input type='password' name='admin_password' required>
                    </div>
                    <button type='submit' name='install'>🚀 Start Installation</button>
                </form>
              </div>
              </div></body></html>";
        
        if (isset($_POST['install'])) {
            $this->processInstallation($_POST);
        }
    }
    
    private function processInstallation($config) {
        $this->db_host = $config['db_host'];
        $this->db_name = $config['db_name'];
        $this->db_user = $config['db_user'];
        $this->db_pass = $config['db_pass'];
        $admin_email = $config['admin_email'];
        $admin_password = password_hash($config['admin_password'], PASSWORD_DEFAULT);
        
        // Step 1: Create Database
        echo "<div class='step'>
                <h3>📊 Creating Database...</h3>
                <div class='progress'><div class='progress-bar' style='width: 25%'></div></div>";
        
        if ($this->createDatabase()) {
            echo "<div class='step success'>✅ Database created successfully</div>";
        } else {
            echo "<div class='step error'>❌ Database creation failed</div>";
            return;
        }
        
        // Step 2: Create Tables
        echo "<div class='step'>
                <h3>🗃️ Creating Database Tables...</h3>
                <div class='progress'><div class='progress-bar' style='width: 50%'></div></div>";
        
        if ($this->createTables()) {
            echo "<div class='step success'>✅ Database tables created successfully</div>";
        } else {
            echo "<div class='step error'>❌ Table creation failed</div>";
            return;
        }
        
        // Step 3: Insert Sample Data
        echo "<div class='step'>
                <h3>📝 Inserting Sample Data...</h3>
                <div class='progress'><div class='progress-bar' style='width: 75%'></div></div>";
        
        if ($this->insertSampleData()) {
            echo "<div class='step success'>✅ Sample data inserted successfully</div>";
        } else {
            echo "<div class='step error'>❌ Sample data insertion failed</div>";
            return;
        }
        
        // Step 4: Create Configuration Files
        echo "<div class='step'>
                <h3>⚙️ Creating Configuration Files...</h3>
                <div class='progress'><div class='progress-bar' style='width: 90%'></div></div>";
        
        if ($this->createConfigFiles($config, $admin_email, $admin_password)) {
            echo "<div class='step success'>✅ Configuration files created successfully</div>";
        } else {
            echo "<div class='step error'>❌ Configuration file creation failed</div>";
            return;
        }
        
        // Step 5: Complete Installation
        echo "<div class='step'>
                <h3>🎉 Installation Complete!</h3>
                <div class='progress'><div class='progress-bar' style='width: 100%'></div></div>
                <div class='step success'>
                    <h4>✅ CineCraze Installation Successful!</h4>
                    <p>Your movie streaming platform is now ready!</p>
                    <p><strong>Admin Login:</strong> {$admin_email}</p>
                    <p><strong>Admin Dashboard:</strong> <a href='admin.php'>Admin Panel</a></p>
                    <p><strong>Main Site:</strong> <a href='index.php'>CineCraze</a></p>
                </div>
              </div>
              </div></body></html>";
    }
    
    private function createDatabase() {
        try {
            $pdo = new PDO("mysql:host={$this->db_host}", $this->db_user, $this->db_pass);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->db_name}");
            return true;
        } catch (Exception $e) {
            echo "<p>Database creation error: " . $e->getMessage() . "</p>";
            return false;
        }
    }
    
    private function connectDatabase() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->db_host};dbname={$this->db_name}", 
                $this->db_user, 
                $this->db_pass
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (Exception $e) {
            echo "<p>Database connection error: " . $e->getMessage() . "</p>";
            return false;
        }
    }
    
    private function createTables() {
        if (!$this->connectDatabase()) return false;
        
        $tables = [
            // Categories table
            "CREATE TABLE categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                description TEXT,
                icon VARCHAR(50),
                order_index INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Movies table
            "CREATE TABLE movies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                poster VARCHAR(500),
                thumbnail VARCHAR(500),
                year INT,
                duration INT,
                rating DECIMAL(3,1),
                country VARCHAR(100),
                genre VARCHAR(100),
                type ENUM('movie', 'series', 'live') DEFAULT 'movie',
                imdb_id VARCHAR(20),
                trailer_url VARCHAR(500),
                status ENUM('active', 'inactive') DEFAULT 'active',
                view_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Movie servers table
            "CREATE TABLE movie_servers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                movie_id INT,
                server_name VARCHAR(100) NOT NULL,
                server_url VARCHAR(500) NOT NULL,
                quality VARCHAR(20),
                language VARCHAR(50),
                is_subtitle BOOLEAN DEFAULT FALSE,
                subtitle_url VARCHAR(500),
                is_primary BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
            )",
            
            // Seasons table (for series)
            "CREATE TABLE seasons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                movie_id INT,
                season_number INT NOT NULL,
                title VARCHAR(255),
                poster VARCHAR(500),
                episode_count INT DEFAULT 0,
                FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
            )",
            
            // Episodes table
            "CREATE TABLE episodes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                season_id INT,
                episode_number INT NOT NULL,
                title VARCHAR(255),
                description TEXT,
                thumbnail VARCHAR(500),
                duration INT,
                air_date DATE,
                FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE
            )",
            
            // Episode servers table
            "CREATE TABLE episode_servers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                episode_id INT,
                server_name VARCHAR(100) NOT NULL,
                server_url VARCHAR(500) NOT NULL,
                quality VARCHAR(20),
                language VARCHAR(50),
                is_subtitle BOOLEAN DEFAULT FALSE,
                subtitle_url VARCHAR(500),
                FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
            )",
            
            // Users table
            "CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                avatar VARCHAR(500),
                role ENUM('admin', 'user') DEFAULT 'user',
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL
            )",
            
            // Watch later table
            "CREATE TABLE watch_later (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                movie_id INT,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
                UNIQUE KEY unique_watch_later (user_id, movie_id)
            )",
            
            // View history table
            "CREATE TABLE view_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                movie_id INT,
                watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                progress_seconds INT DEFAULT 0,
                completed BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
            )",
            
            // Settings table
            "CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                description VARCHAR(255),
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];
        
        try {
            foreach ($tables as $table) {
                $this->connection->exec($table);
            }
            return true;
        } catch (Exception $e) {
            echo "<p>Table creation error: " . $e->getMessage() . "</p>";
            return false;
        }
    }
    
    private function insertSampleData() {
        if (!$this->connectDatabase()) return false;
        
        try {
            // Insert categories
            $categories = [
                ['Movies', 'movies', 'Latest and popular movies'],
                ['TV Series', 'tv-series', 'TV shows and series'],
                ['Live TV', 'live-tv', 'Live television channels']
            ];
            
            $stmt = $this->connection->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            foreach ($categories as $category) {
                $stmt->execute($category);
            }
            
            // Insert admin user
            $adminData = [
                'admin',
                'admin@cinecraze.com',
                password_hash('admin123', PASSWORD_DEFAULT),
                'Admin',
                'User',
                'admin'
            ];
            
            $stmt = $this->connection->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute($adminData);
            
            // Insert sample movies
            $sampleMovies = [
                ['The Matrix', 'the-matrix', 'A computer programmer discovers reality is a simulation', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=The+Matrix', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=The+Matrix', 1999, 136, 8.7, 'USA', 'Sci-Fi', 'movie'],
                ['Inception', 'inception', 'A thief enters dreams to steal secrets', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Inception', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Inception', 2010, 148, 8.8, 'USA', 'Thriller', 'movie'],
                ['Breaking Bad', 'breaking-bad', 'A chemistry teacher turns to drug making', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Breaking+Bad', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Breaking+Bad', 2008, 0, 9.5, 'USA', 'Drama', 'series'],
                ['Netflix Live', 'netflix-live', 'Live streaming channel', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Netflix+Live', 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=Netflix+Live', 2024, 0, 0, 'USA', 'Live', 'live']
            ];
            
            $stmt = $this->connection->prepare("INSERT INTO movies (title, slug, description, poster, thumbnail, year, duration, rating, country, genre, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($sampleMovies as $movie) {
                $stmt->execute($movie);
            }
            
            // Insert sample servers
            $stmt = $this->connection->prepare("INSERT INTO movie_servers (movie_id, server_name, server_url, quality, is_primary) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([1, 'Server 1', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4', '720p', true]);
            $stmt->execute([2, 'Server 1', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4', '720p', true]);
            
            // Insert sample seasons for Breaking Bad
            $stmt = $this->connection->prepare("INSERT INTO seasons (movie_id, season_number, title, episode_count) VALUES (?, ?, ?, ?)");
            $stmt->execute([3, 1, 'Season 1', 7]);
            $stmt->execute([3, 2, 'Season 2', 13]);
            
            // Insert sample episodes
            $stmt = $this->connection->prepare("INSERT INTO episodes (season_id, episode_number, title, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([1, 1, 'Pilot', 'Walter White receives his cancer diagnosis']);
            $stmt->execute([1, 2, 'Cat in the Bag', 'Walt and Jesse dispose of a body']);
            
            // Insert sample episode servers
            $stmt = $this->connection->prepare("INSERT INTO episode_servers (episode_id, server_name, server_url, quality) VALUES (?, ?, ?, ?)");
            $stmt->execute([1, 'Server 1', 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4', '720p']);
            
            // Insert settings
            $settings = [
                ['site_name', 'CineCraze', 'Website name'],
                ['site_description', 'Premium movie and TV streaming platform', 'Website description'],
                ['youtube_api_key', '', 'YouTube API key for movie search'],
                ['tmdb_api_key', '', 'TMDB API key for movie metadata']
            ];
            
            $stmt = $this->connection->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
            foreach ($settings as $setting) {
                $stmt->execute($setting);
            }
            
            return true;
        } catch (Exception $e) {
            echo "<p>Sample data insertion error: " . $e->getMessage() . "</p>";
            return false;
        }
    }
    
    private function createConfigFiles($config, $admin_email, $admin_password) {
        try {
            // Create config directory
            if (!is_dir('config')) {
                mkdir('config', 0755, true);
            }
            
            // Create database config
            $dbConfig = "<?php
// Database Configuration
define('DB_HOST', '{$config['db_host']}');
define('DB_NAME', '{$config['db_name']}');
define('DB_USER', '{$config['db_user']}');
define('DB_PASS', '{$config['db_pass']}');

// Application Configuration
define('SITE_NAME', 'CineCraze');
define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
define('ADMIN_EMAIL', '{$admin_email}');

// Database Connection Class
class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$this->connection = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException \$e) {
            die('Database connection failed: ' . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Utility functions
function isLoggedIn() {
    return isset(\$_SESSION['user_id']) && !empty(\$_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset(\$_SESSION['user_role']) && \$_SESSION['user_role'] === 'admin';
}

function redirect(\$url) {
    header('Location: ' . \$url);
    exit();
}
?>";
            
            file_put_contents('config/database.php', $dbConfig);
            
            // Create .htaccess for URL rewriting
            $htaccess = "RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/?$ movie.php?slug=\$1 [L,QSA]

# Enable CORS for API calls
Header always set Access-Control-Allow-Origin \"*\"
Header always set Access-Control-Allow-Methods \"GET, POST, PUT, DELETE, OPTIONS\"
Header always set Access-Control-Allow-Headers \"Content-Type, Authorization\"

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection \"1; mode=block\"

# Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>";
            
            file_put_contents('.htaccess', $htaccess);
            
            return true;
        } catch (Exception $e) {
            echo "<p>Config file creation error: " . $e->getMessage() . "</p>";
            return false;
        }
    }
}

// Initialize installer
$installer = new CineCrazeInstaller();
$installer->install();
?>