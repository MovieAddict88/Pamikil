<?php
/**
 * 404 Not Found Page
 */

http_response_code(404);

require_once APP_ROOT . '/templates/header.php';
?>

<div class="error-page">
    <div class="container">
        <div class="error-content">
            <h1 class="error-code">404</h1>
            <h2 class="error-title">Page Not Found</h2>
            <p class="error-message">
                Oops! The page you're looking for doesn't exist or has been moved.
            </p>
            <div class="error-actions">
                <a href="<?= SITE_URL ?>/" class="btn btn-primary">
                    <i class="fa fa-home"></i> Go Home
                </a>
                <a href="<?= SITE_URL ?>/activities" class="btn btn-outline">
                    <i class="fa fa-tasks"></i> Browse Activities
                </a>
            </div>
        </div>
        <div class="error-illustration">
            <i class="fa fa-search" style="font-size: 8rem; color: var(--secondary);"></i>
        </div>
    </div>
</div>

<style>
.error-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    padding: var(--spacing-xxl) 0;
}

.error-content {
    max-width: 500px;
}

.error-code {
    font-size: 8rem;
    font-weight: 700;
    color: var(--primary);
    line-height: 1;
    margin-bottom: var(--spacing-lg);
}

.error-title {
    font-size: var(--font-size-3xl);
    color: var(--text-primary);
    margin-bottom: var(--spacing-md);
}

.error-message {
    font-size: var(--font-size-lg);
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xl);
}

.error-actions {
    display: flex;
    gap: var(--spacing-md);
}

.error-illustration {
    text-align: center;
}
</style>

<?php require_once APP_ROOT . '/templates/footer.php'; ?>
