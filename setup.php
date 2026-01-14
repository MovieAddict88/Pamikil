<?php
/**
 * Setup Utility - Pamikil Car Management System
 * Run this script to generate a proper password hash for the admin user
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Only allow running this script in CLI mode or with admin approval
if (php_sapi_name() !== 'cli' && !isset($_GET['confirm'])) {
    die('<html><head><title>Setup Utility</title></head><body>
        <h1>Pamikil Setup Utility</h1>
        <p>This utility helps you set up the admin password.</p>
        <p><a href="?confirm=yes">Click here to continue</a></p>
    </body></html>');
}

$db = getDb();
$defaultPassword = 'admin123';
$newPassword = $defaultPassword;

// Check if we want to set a custom password
if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
    $newPassword = $_POST['new_password'];
    $hash = hashPassword($newPassword);
    
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    
    echo "<h3>Password updated successfully!</h3>";
    echo "<p>New password for user 'admin': <strong>" . htmlspecialchars($newPassword) . "</strong></p>";
    echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";
    exit;
}

// Generate hash for default password
$hash = hashPassword($newPassword);

// Update admin user with new hash
try {
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    
    echo "<!DOCTYPE html><html><head><title>Setup Complete</title></head><body>";
    echo "<h1>✓ Setup Complete!</h1>";
    echo "<p>Admin password has been set successfully.</p>";
    echo "<h2>Login Credentials:</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($newPassword) . "</p>";
    echo "<p><a href='auth/login.php'><strong>Click here to login →</strong></a></p>";
    echo "<hr>";
    echo "<p><strong>Important:</strong> Please change this password after your first login!</p>";
    echo "<hr>";
    echo "<h3>Set Custom Password</h3>";
    echo "<form method='POST'>";
    echo "<label>New Password: <input type='password' name='new_password' required></label><br><br>";
    echo "<button type='submit'>Set Custom Password</button>";
    echo "</form>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "Error updating password: " . $e->getMessage();
}
?>
