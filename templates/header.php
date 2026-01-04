<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= defined('PAGE_DESCRIPTION') ? htmlspecialchars(PAGE_DESCRIPTION) : 'Fun and engaging learning for kids!' ?>">
    <title><?= defined('PAGE_TITLE') ? htmlspecialchars(PAGE_TITLE) . ' - ' : '' ?>Pamikil Learning</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/responsive.css') ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="<?= defined('PAGE_CLASS') ? PAGE_CLASS : '' ?>">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a href="<?= SITE_URL ?>/" class="navbar-brand">
                <span class="brand-icon">📚</span>
                <span class="brand-text">Pamikil</span>
            </a>
            
            <button class="navbar-toggler" type="button" id="navbarToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <div class="navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <?php if (!$auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/" class="nav-link">Home</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($auth->isStudent()): ?>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/activities" class="nav-link">Activities</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/progress" class="nav-link">My Progress</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/rewards" class="nav-link">Rewards</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/leaderboard" class="nav-link">Leaderboard</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($auth->isParent()): ?>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/dashboard" class="nav-link">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/progress" class="nav-link">Children's Progress</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item">
                            <a href="<?= SITE_URL ?>/admin" class="nav-link">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="navbar-actions">
                    <?php if ($auth->isLoggedIn()): ?>
                        <!-- User Menu -->
                        <div class="user-dropdown">
                            <button class="user-button" id="userMenuToggle">
                                <?php if ($auth->getCurrentUser()['avatar_image']): ?>
                                    <img src="<?= htmlspecialchars($auth->getCurrentUser()['avatar_image']) ?>" 
                                         alt="Avatar" class="user-avatar-small">
                                <?php else: ?>
                                    <div class="user-avatar-placeholder">
                                        <?= strtoupper(substr($auth->getCurrentUser()['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="user-name"><?= htmlspecialchars($auth->getCurrentUser()['username']) ?></span>
                                <i class="fa fa-chevron-down"></i>
                            </button>
                            
                            <div class="dropdown-menu" id="userDropdown">
                                <?php if ($auth->isStudent()): ?>
                                    <a href="<?= SITE_URL ?>/avatar" class="dropdown-item">
                                        <i class="fa fa-user"></i> My Avatar
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?= SITE_URL ?>/profile" class="dropdown-item">
                                    <i class="fa fa-cog"></i> Settings
                                </a>
                                
                                <?php if ($auth->isStudent()): ?>
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-stats">
                                        <span><i class="fa fa-coins"></i> <?= $auth->getCurrentUser()['coins'] ?></span>
                                        <span><i class="fa fa-star"></i> Level <?= $auth->getCurrentUser()['current_level'] ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="dropdown-divider"></div>
                                <a href="<?= SITE_URL ?>/logout" class="dropdown-item text-danger">
                                    <i class="fa fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/login" class="btn btn-primary">Log In</a>
                        <a href="<?= SITE_URL ?>/register" class="btn btn-outline">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
