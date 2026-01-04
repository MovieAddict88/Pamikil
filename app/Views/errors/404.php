<?php 
$title = "Page Not Found";
$bodyClass = "error-page";
ob_start();
?>

<div class="error-container">
    <div class="error-content">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>Sorry, the page you're looking for doesn't exist or has been moved.</p>
        
        <div class="error-actions">
            <a href="/dashboard" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Go Home
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Go Back
            </button>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>