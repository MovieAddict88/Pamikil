<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$checks = [];
$allPassed = true;

function checkStatus($name, $status, $message = '') {
    global $checks, $allPassed;
    $checks[] = [
        'name' => $name,
        'status' => $status,
        'message' => $message
    ];
    if (!$status) {
        $allPassed = false;
    }
}

checkStatus('PHP Version', version_compare(PHP_VERSION, '7.4.0', '>='), 'Current: ' . PHP_VERSION);
checkStatus('PDO Extension', extension_loaded('pdo'), 'Required for database');
checkStatus('PDO MySQL', extension_loaded('pdo_mysql'), 'Required for MySQL connection');
checkStatus('cURL Extension', extension_loaded('curl'), 'Required for API calls');
checkStatus('JSON Extension', extension_loaded('json'), 'Required for API data');
checkStatus('mbstring Extension', extension_loaded('mbstring'), 'Recommended for string handling');
checkStatus('fileinfo Extension', extension_loaded('fileinfo'), 'Required for file uploads');

$writableDirs = ['uploads', 'uploads/movies', 'uploads/posters', 'uploads/temp', 'config'];
foreach ($writableDirs as $dir) {
    if (is_dir($dir)) {
        checkStatus("Directory: $dir", is_writable($dir), is_writable($dir) ? 'Writable' : 'NOT writable - chmod 755');
    } else {
        checkStatus("Directory: $dir", false, 'Does not exist');
    }
}

$requiredFiles = [
    'install.php' => 'Installation wizard',
    'index.php' => 'Homepage',
    'watch.php' => 'Video player',
    'includes/db.php' => 'Database class',
    'includes/functions.php' => 'Helper functions',
    'includes/tmdb.php' => 'TMDB API',
    'includes/youtube.php' => 'YouTube API',
    'admin/index.php' => 'Admin dashboard',
    'admin/login.php' => 'Admin login',
    'assets/css/style.css' => 'Public styles',
    'assets/css/admin.css' => 'Admin styles'
];

foreach ($requiredFiles as $file => $description) {
    checkStatus("File: $file", file_exists($file), $description);
}

if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    checkStatus('mod_rewrite', in_array('mod_rewrite', $modules), 'Required for pretty URLs');
}

checkStatus('Upload Max Filesize', ini_get('upload_max_filesize'), 'Current: ' . ini_get('upload_max_filesize'));
checkStatus('Post Max Size', ini_get('post_max_size'), 'Current: ' . ini_get('post_max_size'));
checkStatus('Max Execution Time', ini_get('max_execution_time'), 'Current: ' . ini_get('max_execution_time') . 's');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieStream - System Check</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
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
        
        .content {
            padding: 40px;
        }
        
        .summary {
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            font-size: 18px;
            font-weight: 500;
        }
        
        .summary.pass {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .summary.fail {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .check-item {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }
        
        .check-item:last-child {
            border-bottom: none;
        }
        
        .check-status {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .check-status.pass {
            background: #d4edda;
            color: #28a745;
        }
        
        .check-status.fail {
            background: #f8d7da;
            color: #dc3545;
        }
        
        .check-details {
            flex: 1;
        }
        
        .check-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .check-message {
            color: #666;
            font-size: 14px;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 14px 30px;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 MovieStream</h1>
            <p>System Requirements Check</p>
        </div>
        
        <div class="content">
            <?php if ($allPassed): ?>
                <div class="summary pass">
                    ✅ All checks passed! Your system is ready for installation.
                </div>
            <?php else: ?>
                <div class="summary fail">
                    ⚠️ Some checks failed. Please fix the issues below before installing.
                </div>
            <?php endif; ?>
            
            <div class="checks">
                <?php foreach ($checks as $check): ?>
                    <div class="check-item">
                        <div class="check-status <?php echo $check['status'] ? 'pass' : 'fail'; ?>">
                            <?php echo $check['status'] ? '✓' : '✗'; ?>
                        </div>
                        <div class="check-details">
                            <div class="check-name"><?php echo htmlspecialchars($check['name']); ?></div>
                            <?php if ($check['message']): ?>
                                <div class="check-message"><?php echo htmlspecialchars($check['message']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="actions">
                <?php if ($allPassed): ?>
                    <a href="install.php" class="btn btn-primary">Proceed to Installation →</a>
                <?php else: ?>
                    <a href="check.php" class="btn btn-secondary">Refresh Checks</a>
                <?php endif; ?>
                <a href="README.md" class="btn btn-secondary">Read Documentation</a>
            </div>
        </div>
    </div>
</body>
</html>
