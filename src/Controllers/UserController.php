<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Database;
use App\Core\Response;
use App\Core\View;
use App\Security\Auth;
use App\Security\Csrf;
use App\Security\PasswordPolicy;
use PDO;
use PDOException;

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
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = (string) ($_POST['role'] ?? 'user');
        $password = (string) ($_POST['password'] ?? '');

        if (
            $name === ''
            || strlen($name) > 120
            || strlen($email) > 190
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !PasswordPolicy::accepts($password)
            || !in_array($role, ['admin', 'manager', 'user'], true)
        ) {
            http_response_code(422);
            $this->indexWithError('Use a valid name and email, a 12-72 character password, and a supported role.');
            return;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active, created_at) VALUES (:name, :email, :password_hash, :role, 1, :created_at)'
        );

        try {
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'created_at' => gmdate('c'),
            ]);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            http_response_code(422);
            $this->indexWithError('That email is already registered.');
            return;
        }

        AuditLogger::record((int) Auth::user()['id'], 'user.created', ['email' => $email, 'role' => $role]);
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
