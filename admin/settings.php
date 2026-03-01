<?php
session_start();
define('APP_ACCESS', true);

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = sanitize($_POST['site_name']);
    $siteUrl = sanitize($_POST['site_url']);
    $tmdbApiKey = sanitize($_POST['tmdb_api_key']);
    $youtubeApiKey = sanitize($_POST['youtube_api_key']);
    $itemsPerPage = (int)$_POST['items_per_page'];
    $enableRegistration = isset($_POST['enable_registration']) ? 1 : 0;
    $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    updateSetting('site_name', $siteName);
    updateSetting('site_url', $siteUrl);
    updateSetting('tmdb_api_key', $tmdbApiKey);
    updateSetting('youtube_api_key', $youtubeApiKey);
    updateSetting('items_per_page', $itemsPerPage);
    updateSetting('enable_registration', $enableRegistration);
    updateSetting('maintenance_mode', $maintenanceMode);
    
    $success = 'Settings saved successfully!';
}

$pageTitle = 'Settings';
include 'header.php';
?>

<div class="dashboard">
    <h1>Settings</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="dashboard-section">
            <h2>General Settings</h2>
            
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="<?php echo htmlspecialchars(getSetting('site_name', SITE_NAME)); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Site URL</label>
                <input type="url" name="site_url" value="<?php echo htmlspecialchars(getSetting('site_url', SITE_URL)); ?>" required>
                <div class="help-text">Your website's full URL (including https://)</div>
            </div>
            
            <div class="form-group">
                <label>Items Per Page</label>
                <input type="number" name="items_per_page" min="1" max="100" value="<?php echo getSetting('items_per_page', 20); ?>" required>
                <div class="help-text">Number of movies to display per page</div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="enable_registration" <?php echo getSetting('enable_registration', 1) ? 'checked' : ''; ?>>
                    Enable User Registration
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="maintenance_mode" <?php echo getSetting('maintenance_mode', 0) ? 'checked' : ''; ?>>
                    Maintenance Mode
                </label>
                <div class="help-text">When enabled, only admins can access the site</div>
            </div>
        </div>
        
        <div class="dashboard-section" style="margin-top: 30px;">
            <h2>API Configuration</h2>
            
            <div class="form-group">
                <label>TMDB API Key</label>
                <input type="text" name="tmdb_api_key" value="<?php echo htmlspecialchars(getSetting('tmdb_api_key', TMDB_API_KEY)); ?>">
                <div class="help-text">
                    Get your free API key from <a href="https://www.themoviedb.org/settings/api" target="_blank">TheMovieDB.org</a>
                </div>
            </div>
            
            <div class="form-group">
                <label>YouTube API Key</label>
                <input type="text" name="youtube_api_key" value="<?php echo htmlspecialchars(getSetting('youtube_api_key', YOUTUBE_API_KEY)); ?>">
                <div class="help-text">
                    Get your API key from <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a>
                </div>
            </div>
        </div>
        
        <div class="btn-group" style="margin-top: 30px;">
            <button type="submit" class="btn btn-success">Save Settings</button>
        </div>
    </form>
    
    <div class="dashboard-section" style="margin-top: 30px;">
        <h2>System Information</h2>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>MySQL Version:</strong> <?php echo $db->query("SELECT VERSION()")->fetchColumn(); ?></p>
            <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            <p><strong>Max Upload Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
            <p><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?> seconds</p>
            <p><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
