<?php
declare(strict_types=1);

namespace ClinicOS\Auth;

use ClinicOS\Database\Connection;
use PDO;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function attempt(string $email, string $password, array $config): bool
    {
        $pdo = Connection::make($config);
        $statement = $pdo->prepare(
            'SELECT id, full_name, email, password_hash, role, is_active
             FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !(bool) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['authenticated_at'] = time();
        unset($_SESSION['_csrf_token']);

        self::$user = [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        self::$loaded = true;

        return true;
    }

    public static function user(array $config): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;
        $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            return null;
        }

        $pdo = Connection::make($config);
        $statement = $pdo->prepare(
            'SELECT id, full_name, email, role, is_active
             FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !(bool) $user['is_active']) {
            self::logout();
            return null;
        }

        self::$user = [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        return self::$user;
    }

    public static function check(array $config): bool
    {
        return self::user($config) !== null;
    }

    public static function requireLogin(array $config): array
    {
        $user = self::user($config);
        if (!$user) {
            header('Location: login.php');
            exit;
        }

        return $user;
    }

    public static function requireRole(array $config, array $roles): array
    {
        $user = self::requireLogin($config);
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            require dirname(__DIR__, 2) . '/public/errors/403.php';
            exit;
        }

        return $user;
    }

    public static function logout(): void
    {
        self::$user = null;
        self::$loaded = true;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();
    }
}
