<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\ConfigValidator;

require dirname(__DIR__) . '/vendor/autoload.php';
Bootstrap::console(dirname(__DIR__));

$problems = ConfigValidator::problems();
if ($problems !== []) {
    foreach ($problems as $problem) {
        fwrite(STDERR, "- {$problem}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Configuration looks sane.\n");
