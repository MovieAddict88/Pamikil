<?php
declare(strict_types=1);

function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $configPath = dirname(__DIR__) . '/config.php';
    if (!is_file($configPath)) {
        return $config = [];
    }

    /** @var array $loaded */
    $loaded = require $configPath;
    return $config = $loaded;
}

function app_is_installed(): bool
{
    $config = app_config();
    if ($config === []) {
        return false;
    }

    try {
        $pdo = app_pdo();
        $pdo->query('SELECT 1 FROM app_settings LIMIT 1');
        return true;
    } catch (Throwable) {
        return false;
    }
}

function app_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    if ($config === []) {
        throw new RuntimeException('App not configured.');
    }

    $db = $config['db'] ?? [];

    $host = (string)($db['host'] ?? 'localhost');
    $name = (string)($db['name'] ?? '');
    $user = (string)($db['user'] ?? '');
    $pass = (string)($db['pass'] ?? '');
    $charset = (string)($db['charset'] ?? 'utf8mb4');

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function app_setting_get(string $key, ?string $default = null): ?string
{
    $pdo = app_pdo();
    $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1');
    $stmt->execute(['k' => $key]);
    $value = $stmt->fetchColumn();

    if ($value === false) {
        return $default;
    }

    return (string)$value;
}

function app_setting_set(string $key, string $value): void
{
    $pdo = app_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)\n'
        . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute(['k' => $key, 'v' => $value]);
}

function app_require_install(): void
{
    if (app_is_installed()) {
        return;
    }

    header('Location: /install.php');
    exit;
}

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function admin_is_logged_in(): bool
{
    app_start_session();
    return ($_SESSION['admin_logged_in'] ?? false) === true;
}

function admin_require_login(): void
{
    if (admin_is_logged_in()) {
        return;
    }

    header('Location: /admin/login.php');
    exit;
}

function admin_require_login_api(): void
{
    if (admin_is_logged_in()) {
        return;
    }

    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
