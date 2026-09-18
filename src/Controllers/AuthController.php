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
    private const ACCOUNT_ATTEMPTS = 5;
    private const IP_ATTEMPTS = 25;
    private const WINDOW_SECONDS = 900;

    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        View::render('login', ['csrf' => Csrf::token(), 'error' => null]);
    }

    public function login(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $csrf = $_POST['_csrf'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ipKey = 'login:ip:' . $ip;
        $accountKey = 'login:account:' . hash('sha256', $email);

        if (!Csrf::validate(is_string($csrf) ? $csrf : null)) {
            http_response_code(419);
            View::render('login', ['csrf' => Csrf::token(), 'error' => 'Your session expired. Try again.']);
            return;
        }

        if (
            RateLimiter::tooManyAttempts($accountKey, self::ACCOUNT_ATTEMPTS, self::WINDOW_SECONDS)
            || RateLimiter::tooManyAttempts($ipKey, self::IP_ATTEMPTS, self::WINDOW_SECONDS)
        ) {
            http_response_code(429);
            View::render('login', ['csrf' => Csrf::token(), 'error' => 'Too many login attempts. Try again later.']);
            return;
        }

        if ($email === '' || strlen($email) > 190 || strlen($password) > 4096 || !Auth::attempt($email, $password)) {
            RateLimiter::hit($accountKey);
            RateLimiter::hit($ipKey);
            http_response_code(422);
            View::render('login', ['csrf' => Csrf::token(), 'error' => 'Invalid credentials.']);
            return;
        }

        RateLimiter::clear($accountKey);
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
