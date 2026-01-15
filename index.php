<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($scriptDir !== '' && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}
$path = '/' . ltrim($path, '/');

$route = $path === '/' ? 'home' : trim($path, '/');
if (isset($_GET['page'])) {
    $route = trim((string)$_GET['page'], '/');
}

$routes = [
    'home' => 'home.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'logout' => 'logout.php',

    'activities' => 'activities/list.php',
    'activity' => 'activities/play.php',

    'student' => 'student/dashboard.php',
    'student/avatar' => 'student/avatar.php',
    'student/certificates' => 'student/certificates.php',

    'parent' => 'parent/dashboard.php',
    'parent/settings' => 'parent/settings.php',

    'admin' => 'admin/dashboard.php',
    'admin/content' => 'admin/content.php',
    'admin/users' => 'admin/users.php',
    'admin/reports' => 'admin/reports.php',

    '404' => '404.php',
];

$pageRelative = $routes[$route] ?? $routes['404'];
$pageFile = __DIR__ . '/app/pages/' . $pageRelative;

if (!is_file($pageFile)) {
    http_response_code(404);
    $pageFile = __DIR__ . '/app/pages/404.php';
}

$__pageFile = $pageFile;
require __DIR__ . '/app/templates/layout.php';
