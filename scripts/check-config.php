<?php

declare(strict_types=1);

use App\Core\ConfigValidator;
use App\Core\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$problems = ConfigValidator::problems();
if ($problems !== []) {
    foreach ($problems as $problem) {
        fwrite(STDERR, "- {$problem}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Configuration looks sane.\n");
