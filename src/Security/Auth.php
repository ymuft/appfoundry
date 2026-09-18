<?php

declare(strict_types=1);

namespace App\Security;

use App\Audit\AuditLogger;
use App\Core\Database;
use PDO;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT id, email, name, password_hash, role, is_active FROM users WHERE lower(email) = lower(:email) LIMIT 1'
        );
        $statement->execute(['email' => trim($email)]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !(bool) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
        ];
        Csrf::rotate();
        AuditLogger::record((int) $user['id'], 'auth.login', ['email' => $user['email']]);

        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user !== null) {
            AuditLogger::record((int) $user['id'], 'auth.logout');
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
