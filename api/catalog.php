<?php
declare(strict_types=1);

require __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!app_is_installed()) {
    http_response_code(503);
    echo json_encode([
        'error' => 'not_installed',
        'message' => 'CineCraze is not installed. Run /install.php',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$catalog = app_setting_get('catalog_json', null);

if (!$catalog) {
    $fallbackPath = dirname(__DIR__) . '/pagsure.json';
    $catalog = is_file($fallbackPath) ? (string)file_get_contents($fallbackPath) : '{"Categories":[]}';
}

echo $catalog;
