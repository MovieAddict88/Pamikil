<?php
/**
 * Entry Point
 * Car Management System - Pamikil
 * Redirects to login or dashboard
 */

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
} else {
    redirect(SITE_URL . '/auth/login.php');
}
exit;
