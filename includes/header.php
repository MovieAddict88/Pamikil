<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', dirname(dirname($_SERVER['PHP_SELF'])));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? escape_output($page_title) . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if (is_logged_in()): ?>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="<?php echo BASE_URL; ?>/modules/dashboard/index.php">
                    <i class="fas fa-car"></i> <?php echo APP_NAME; ?>
                </a>
            </div>
            <ul class="navbar-menu">
                <li><a href="<?php echo BASE_URL; ?>/modules/dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>/modules/vehicles/index.php"><i class="fas fa-car"></i> Vehicles</a></li>
                <li><a href="<?php echo BASE_URL; ?>/modules/customers/index.php"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="<?php echo BASE_URL; ?>/modules/sales/index.php"><i class="fas fa-shopping-cart"></i> Sales</a></li>
                <li><a href="<?php echo BASE_URL; ?>/modules/services/index.php"><i class="fas fa-wrench"></i> Services</a></li>
                <li><a href="<?php echo BASE_URL; ?>/modules/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if (has_role(['Admin', 'Manager'])): ?>
                <li><a href="<?php echo BASE_URL; ?>/modules/users/index.php"><i class="fas fa-user-shield"></i> Users</a></li>
                <?php endif; ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i> <?php echo escape_output($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo BASE_URL; ?>/modules/auth/profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/modules/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
            <div class="navbar-toggle" id="navbar-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="main-content">
        <div class="container">
            <?php echo get_alert_html(); ?>
