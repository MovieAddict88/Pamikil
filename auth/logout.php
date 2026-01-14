<?php
/**
 * Logout Handler
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

logout();

redirect(SITE_URL . '/auth/login.php');
exit;
