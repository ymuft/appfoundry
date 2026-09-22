<?php

declare(strict_types=1);

namespace App\Core;

use App\Security\SecurityHeaders;

final class Bootstrap
{
    public static function console(string $projectRoot): void
    {
        Paths::setRoot($projectRoot);
        Env::load('.env');
    }

    public static function web(string $projectRoot): void
    {
        self::console($projectRoot);
        ConfigValidator::assertSafe();
        SecurityHeaders::apply();

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_name(Env::get('APP_SESSION_NAME', 'appfoundry_session') ?? 'appfoundry_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => Env::bool('APP_SECURE_COOKIES', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
