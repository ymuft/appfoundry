<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;
use App\Security\Auth;
use App\Security\Csrf;
use App\Security\RateLimiter;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        View::render('login', ['csrf' => Csrf::token(), 'error' => null]);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $csrf = $_POST['_csrf'] ?? null;
        $key = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ':' . strtolower($email);

        if (!Csrf::validate(is_string($csrf) ? $csrf : null)) {
            http_response_code(419);
            View::render('login', ['csrf' => Csrf::token(), 'error' => 'Your session expired. Try again.']);
            return;
        }

        if (RateLimiter::tooManyAttempts($key, 5, 900)) {
            http_response_code(429);
            View::render('login', ['csrf' => Csrf::token(), 'error' => 'Too many login attempts. Try again later.']);
            return;
        }

        if (!Auth::attempt($email, $password)) {
            RateLimiter::hit($key);
            http_response_code(422);
            View::render('login', ['csrf' => Csrf::token(), 'error' => 'Invalid credentials.']);
            return;
        }

        RateLimiter::clear($key);
        Response::redirect('/');
    }

    public function logout(): void
    {
        $csrf = $_POST['_csrf'] ?? null;
        if (!Csrf::validate(is_string($csrf) ? $csrf : null)) {
            http_response_code(419);
            echo 'Invalid CSRF token';
            return;
        }

        Auth::logout();
        Response::redirect('/login');
    }
}
