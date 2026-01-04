<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🎬 <?php echo SITE_NAME; ?></h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <span>📊</span> Dashboard
                </a>
                <a href="movies.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'movies.php' ? 'active' : ''; ?>">
                    <span>🎬</span> Movies
                </a>
                <a href="add-movie.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'add-movie.php' ? 'active' : ''; ?>">
                    <span>➕</span> Add Movie
                </a>
                <a href="bulk-add.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'bulk-add.php' ? 'active' : ''; ?>">
                    <span>📦</span> Bulk Add
                </a>
                <a href="servers.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'servers.php' ? 'active' : ''; ?>">
                    <span>🖥️</span> Servers
                </a>
                <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                    <span>👥</span> Users
                </a>
                <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <span>⚙️</span> Settings
                </a>
                <a href="../index.php" class="nav-item" target="_blank">
                    <span>🌐</span> View Website
                </a>
                <a href="logout.php" class="nav-item">
                    <span>🚪</span> Logout
                </a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                </div>
                <div class="top-bar-right">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>
            
            <div class="content-wrapper">
