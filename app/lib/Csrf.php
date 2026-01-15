<?php
declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = '__csrf_token';

    public static function token(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = Security::randomString(64);
        }
        return (string)$_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (!$token || !isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return Security::hashEquals((string)$_SESSION[self::SESSION_KEY], (string)$token);
    }

    public static function inputField(): string
    {
        $token = self::token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function requireValidToken(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::validate(is_string($token) ? $token : null)) {
            http_response_code(403);
            echo 'Invalid CSRF token.';
            exit;
        }
    }
}
