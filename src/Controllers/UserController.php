<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Database;
use App\Core\Response;
use App\Core\View;
use App\Security\Auth;
use App\Security\Csrf;
use PDO;

final class UserController
{
    public function index(): void
    {
        $this->requireAdmin();
        $users = Database::connection()->query(
            'SELECT id, name, email, role, is_active, created_at FROM users ORDER BY id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        View::render('users', [
            'user' => Auth::user(),
            'users' => $users,
            'csrf' => Csrf::token(),
            'error' => null,
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        if (!Csrf::validate(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            http_response_code(419);
            echo 'Invalid CSRF token';
            return;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'user');
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !in_array($role, ['admin', 'manager', 'user'], true)) {
            http_response_code(422);
            $this->indexWithError('Use a valid email, a password with at least 12 characters, and a supported role.');
            return;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active, created_at) VALUES (:name, :email, :password_hash, :role, 1, :created_at)'
        );

        try {
            $statement->execute([
                'name' => $name,
                'email' => strtolower($email),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'created_at' => gmdate('c'),
            ]);
        } catch (\PDOException) {
            http_response_code(422);
            $this->indexWithError('That email is already registered.');
            return;
        }

        AuditLogger::record((int) Auth::user()['id'], 'user.created', ['email' => strtolower($email), 'role' => $role]);
        Response::redirect('/admin/users');
    }

    private function indexWithError(string $error): void
    {
        $users = Database::connection()->query(
            'SELECT id, name, email, role, is_active, created_at FROM users ORDER BY id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        View::render('users', [
            'user' => Auth::user(),
            'users' => $users,
            'csrf' => Csrf::token(),
            'error' => $error,
        ]);
    }

    private function requireAdmin(): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }

        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}
