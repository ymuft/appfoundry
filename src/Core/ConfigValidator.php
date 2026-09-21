<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class ConfigValidator
{
    /** @return list<string> */
    public static function problems(): array
    {
        $problems = [];
        $environment = Env::get('APP_ENV', 'local') ?? 'local';
        $driver = Env::get('DB_DRIVER', 'sqlite') ?? 'sqlite';

        if (!in_array($environment, ['local', 'development', 'testing', 'production'], true)) {
            $problems[] = 'APP_ENV must be one of: local, development, testing, production.';
        }

        if (!in_array($driver, ['sqlite', 'mysql'], true)) {
            $problems[] = 'DB_DRIVER must be one of: sqlite, mysql.';
        }

        if ($environment === 'production' && !Env::bool('APP_SECURE_COOKIES', false)) {
            $problems[] = 'APP_SECURE_COOKIES must be true in production behind HTTPS.';
        }

        if (
            $environment === 'production'
            && $driver === 'mysql'
            && in_array(Env::get('DB_PASSWORD', ''), ['', 'change-me'], true)
        ) {
            $problems[] = 'DB_PASSWORD must be changed before using MySQL in production.';
        }

        return $problems;
    }

    public static function assertSafe(): void
    {
        $problems = self::problems();
        if ($problems === []) {
            return;
        }

        throw new RuntimeException("Unsafe AppFoundry configuration:\n- " . implode("\n- ", $problems));
    }
}
