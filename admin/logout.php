<?php
declare(strict_types=1);

require __DIR__ . '/../includes/app.php';

app_start_session();
$_SESSION = [];
session_destroy();

header('Location: /admin/login.php');
exit;
