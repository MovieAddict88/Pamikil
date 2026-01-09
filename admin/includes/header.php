<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="../assets/images/icon-192.png">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>
                    <i class="fas fa-car"></i>
                    <?php echo SITE_NAME; ?>
                </h2>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="cars.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'cars.php' ? 'active' : ''; ?>">
                    <i class="fas fa-car"></i>
                    <span>Cars</span>
                </a>
                <a href="customers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="sales.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'active' : ''; ?>">
                    <i class="fas fa-handshake"></i>
                    <span>Sales</span>
                </a>
                <a href="maintenance.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance</span>
                </a>
                <a href="inquiries.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'inquiries.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Inquiries</span>
                </a>
                <a href="test-drives.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'test-drives.php' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i>
                    <span>Test Drives</span>
                </a>
                <a href="../home.php" class="menu-item" target="_blank">
                    <i class="fas fa-globe"></i>
                    <span>View Website</span>
                </a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-content">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?></h1>
                    <div class="user-menu">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: clamp(0.875rem, 2vw, 1rem);">
                                    <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                                </div>
                                <div style="font-size: clamp(0.75rem, 1.8vw, 0.875rem); color: var(--secondary);">
                                    Administrator
                                </div>
                            </div>
                        </div>
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
