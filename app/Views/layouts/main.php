<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= $title ?? 'Karaoke App' ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/responsive.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">
</head>
<body class="<?= $bodyClass ?? '' ?>">
    <!-- Navigation -->
    <?php if (AuthController::isLoggedIn()): ?>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="/dashboard">
                    <i class="fas fa-microphone-alt"></i>
                    <span>Karaoke App</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="/dashboard" class="nav-link <?= $_SERVER['REQUEST_URI'] === '/dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="/dashboard/rooms" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/dashboard/rooms') === 0 ? 'active' : '' ?>">
                    <i class="fas fa-door-open"></i>
                    <span>My Rooms</span>
                </a>
                
                <?php if (AuthController::getCurrentUser()['is_admin']): ?>
                <a href="/admin" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin') === 0 ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Admin</span>
                </a>
                <?php endif; ?>
                
                <div class="nav-user">
                    <div class="user-menu">
                        <button class="user-button">
                            <img src="<?= AuthController::getCurrentUser()['avatar_url'] ?? asset('images/default-avatar.png') ?>" 
                                 alt="User" class="user-avatar">
                            <span><?= htmlspecialchars(AuthController::getCurrentUser()['display_name']) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="user-dropdown">
                            <a href="/profile" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                Profile
                            </a>
                            <a href="/settings" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/logout" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Flash Messages -->
        <?php $flash = get_flash(); ?>
        <?php if (!empty($flash)): ?>
        <div class="flash-container">
            <?php foreach ($flash as $type => $message): ?>
            <div class="flash-message flash-<?= $type ?>">
                <div class="flash-content">
                    <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <button class="flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <?= $content ?? '' ?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> Karaoke App. Built with pure PHP and MySQL.</p>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="<?= asset('js/main.js') ?>"></script>
    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>