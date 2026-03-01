<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo getSetting('site_name', SITE_NAME); ?></title>
    <meta name="description" content="Watch movies online for free">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                🎬 <?php echo getSetting('site_name', SITE_NAME); ?>
            </a>
            
            <ul class="nav-menu">
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="search.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>">Search</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <?php if (getSetting('enable_registration', 1)): ?>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="nav-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    
    <main>
