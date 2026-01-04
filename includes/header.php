<?php
/**
 * Header Template
 * Car Management System - Pamikil
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
</head>
<body>
<?php if (isLoggedIn()): ?>
<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Pamikil</h2>
            <small>Car Management</small>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="<?php echo SITE_URL; ?>/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                
                <?php if (hasAnyRole(['admin', 'manager', 'staff'])): ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/vehicles/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'vehicles') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-car-front"></i> Vehicles
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasAnyRole(['admin', 'manager', 'staff'])): ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/customers/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'customers') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i> Customers
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasAnyRole(['admin', 'manager'])): ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/sales/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-cart-check"></i> Sales
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasAnyRole(['admin', 'manager', 'staff'])): ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/services/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'services') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-tools"></i> Services
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasAnyRole(['admin', 'manager'])): ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/reports/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'reports') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-bar-chart"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole('admin')): ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/users/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'users') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-person-gear"></i> Users
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo SITE_URL; ?>/locations/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'locations') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-geo-alt"></i> Locations
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div class="header-title">
                <h2><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></h2>
            </div>
            <div class="header-actions d-flex align-items-center">
                <div class="user-info me-3">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <small class="text-muted">(<?php echo ucfirst($_SESSION['role']); ?>)</small>
                </div>
                <a href="<?php echo SITE_URL; ?>/auth/profile.php" class="btn btn-sm btn-outline-primary me-2">
                    <i class="bi bi-person"></i> Profile
                </a>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="btn btn-sm btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Page Content -->
        <div class="page-content">
<?php else: ?>
<?php
// Non-logged in header (for login page, etc.)
?>
<body>
<?php endif; ?>
