<?php
declare(strict_types=1);

require __DIR__ . '/../../includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!app_is_installed()) {
    http_response_code(503);
    echo json_encode(['error' => 'not_installed'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

admin_require_login_api();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');

    if (!is_string($raw) || trim($raw) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'empty_body'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded) || !isset($decoded['Categories']) || !is_array($decoded['Categories'])) {
            throw new InvalidArgumentException('Invalid catalog structure. Expected {"Categories": [...]}');
        }

        app_setting_set('catalog_json', json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        echo json_encode(['ok' => true], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(422);
        echo json_encode(['error' => 'invalid_json', 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$catalog = app_setting_get('catalog_json', '{"Categories":[]}');
echo $catalog;
