<?php
declare(strict_types=1);

/** @return array<string,mixed> */
function app_config(): array
{
    return $GLOBALS['APP_CONFIG'] ?? [];
}

function config_get(string $path, $default = null)
{
    $config = app_config();
    $parts = explode('.', $path);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function db(): Database
{
    return $GLOBALS['DB'];
}

function auth(): Auth
{
    return $GLOBALS['AUTH'];
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string)config_get('app.base_url', ''), '/');
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
}

function flash(string $type, string $message): void
{
    if (!isset($_SESSION['__flash']) || !is_array($_SESSION['__flash'])) {
        $_SESSION['__flash'] = [];
    }
    $_SESSION['__flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int,array{type:string,message:string}> */
function consume_flashes(): array
{
    $flashes = $_SESSION['__flash'] ?? [];
    unset($_SESSION['__flash']);
    if (!is_array($flashes)) {
        return [];
    }
    return array_values(array_filter($flashes, static fn($f) => is_array($f) && isset($f['type'], $f['message'])));
}

function current_user(): ?array
{
    return auth()->user();
}

function require_student(): void
{
    auth()->requireRole('student');
}

function require_parent(): void
{
    auth()->requireRole('parent');
}

function require_admin(): void
{
    auth()->requireRole('admin');
}

function page_title(string $default = 'Pamikil Learning'): string
{
    global $__pageTitle;
    $title = isset($__pageTitle) ? (string)$__pageTitle : $default;
    return $title;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
