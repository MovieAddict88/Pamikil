<?php
declare(strict_types=1);

final class Security
{
    /** @param array<string,mixed> $config */
    public static function startSession(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        $sessionName = (string)($config['session']['name'] ?? 'pamikil_session');
        session_name($sessionName);

        $secure = self::isHttps();
        $sameSite = (string)($config['session']['cookie_samesite'] ?? 'Lax');

        $params = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        session_start();

        if (!isset($_SESSION['__created_at'])) {
            $_SESSION['__created_at'] = time();
            $_SESSION['__last_regen'] = time();
            session_regenerate_id(true);
        }
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        return false;
    }

    public static function randomString(int $length = 32): string
    {
        $bytes = random_bytes((int)ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    public static function hashEquals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }
}
