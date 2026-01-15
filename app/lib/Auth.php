<?php
declare(strict_types=1);

final class Auth
{
    private Database $db;

    /** @var array<string,mixed> */
    private array $config;

    /** @var array<string,mixed>|null */
    private ?array $userCache = null;

    /** @param array<string,mixed> $config */
    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /** @return array<string,mixed>|null */
    public function user(): ?array
    {
        if ($this->userCache !== null) {
            return $this->userCache;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!is_int($userId) && !ctype_digit((string)$userId)) {
            return null;
        }

        $row = $this->db->fetch('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => (int)$userId]);
        $this->userCache = $row;
        return $row;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function login(string $usernameOrEmail, string $password): bool
    {
        $usernameOrEmail = trim($usernameOrEmail);

        $user = $this->db->fetch(
            'SELECT * FROM users WHERE (username = :u OR email = :u) LIMIT 1',
            ['u' => $usernameOrEmail]
        );

        if (!$user || !isset($user['password_hash']) || !is_string($user['password_hash'])) {
            return false;
        }

        if ((int)($user['is_active'] ?? 1) !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['__last_activity'] = time();
        $_SESSION['__last_regen'] = time();

        $this->db->execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => (int)$user['id']]);

        $this->userCache = $user;
        return true;
    }

    public function logout(): void
    {
        $this->userCache = null;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }

        session_destroy();
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            flash('error', 'Please log in to continue.');
            redirect('/login');
        }
    }

    /** @param 'student'|'parent'|'admin' $role */
    public function requireRole(string $role): void
    {
        $this->requireLogin();
        $user = $this->user();
        if (!$user || ($user['role'] ?? null) !== $role) {
            http_response_code(403);
            echo 'Forbidden.';
            exit;
        }
    }

    public function enforceSessionTimeout(): void
    {
        $timeout = (int)($this->config['session']['timeout_seconds'] ?? 1800);
        $regen = (int)($this->config['session']['regenerate_seconds'] ?? 600);

        $now = time();

        if (isset($_SESSION['__last_activity']) && is_int($_SESSION['__last_activity'])) {
            if (($now - $_SESSION['__last_activity']) > $timeout) {
                $this->logout();
                Security::startSession($this->config);
                flash('error', 'Your session timed out. Please log in again.');
                return;
            }
        }

        $_SESSION['__last_activity'] = $now;

        if (isset($_SESSION['__last_regen']) && is_int($_SESSION['__last_regen']) && ($now - $_SESSION['__last_regen']) > $regen) {
            session_regenerate_id(true);
            $_SESSION['__last_regen'] = $now;
        }
    }
}
