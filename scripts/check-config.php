<?php

declare(strict_types=1);

use App\Core\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$problems = [];
$key = Env::get('APP_KEY', '');
if ($key === null || strlen($key) < 32 || $key === 'change-me-to-a-long-random-value') {
    $problems[] = 'APP_KEY must be replaced with a random value of at least 32 characters.';
}

if (Env::get('APP_ENV', 'production') === 'production' && !Env::bool('APP_SECURE_COOKIES', false)) {
    $problems[] = 'APP_SECURE_COOKIES should be true in production behind HTTPS.';
}

if ($problems !== []) {
    foreach ($problems as $problem) {
        fwrite(STDERR, "- {$problem}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Configuration looks sane.\n");
