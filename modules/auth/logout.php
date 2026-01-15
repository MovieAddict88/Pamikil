<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (is_logged_in()) {
    log_transaction($conn, 'LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
    
    session_unset();
    session_destroy();
}

header('Location: login.php');
exit();
?>
