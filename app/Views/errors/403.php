<?php 
$title = "Access Denied";
$bodyClass = "error-page";
ob_start();
?>

<div class="error-container">
    <div class="error-content">
        <div class="error-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1>403</h1>
        <h2>Access Denied</h2>
        <p>You don't have permission to access this page or perform this action.</p>
        
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