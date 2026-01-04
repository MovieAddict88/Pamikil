<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    header('Location: modules/dashboard/index.php');
} else {
    header('Location: modules/auth/login.php');
}
exit();
?>
