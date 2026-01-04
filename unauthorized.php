<?php
/**
 * Unauthorized Access Page
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="card" style="max-width: 500px; width: 100%;">
            <div class="card-body text-center">
                <div style="font-size: 64px; color: var(--danger-color); margin-bottom: 20px;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <h2>Access Denied</h2>
                <p class="text-muted mt-3">
                    You don't have permission to access this page.
                </p>
                <p class="text-muted">
                    Please contact your administrator if you believe this is an error.
                </p>
                <div class="mt-4">
                    <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-primary">
                        <i class="bi bi-house"></i> Go to Dashboard
                    </a>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="btn btn-secondary">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
