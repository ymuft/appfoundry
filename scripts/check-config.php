<?php

declare(strict_types=1);

use App\Core\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$problems = [];
$environment = Env::get('APP_ENV', 'local') ?? 'local';
$allowedEnvironments = ['local', 'development', 'testing', 'production'];

if (!in_array($environment, $allowedEnvironments, true)) {
    $problems[] = 'APP_ENV must be one of: ' . implode(', ', $allowedEnvironments) . '.';
}

if ($environment === 'production' && !Env::bool('APP_SECURE_COOKIES', false)) {
    $problems[] = 'APP_SECURE_COOKIES must be true in production behind HTTPS.';
}

if (
    $environment === 'production'
    && Env::get('DB_DRIVER', 'sqlite') === 'mysql'
    && in_array(Env::get('DB_PASSWORD', ''), ['', 'change-me'], true)
) {
    $problems[] = 'DB_PASSWORD must be changed before using MySQL in production.';
}

if ($problems !== []) {
    foreach ($problems as $problem) {
        fwrite(STDERR, "- {$problem}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Configuration looks sane.\n");
