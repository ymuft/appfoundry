<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$driver = Env::get('DB_DRIVER', 'sqlite');
$file = $driver === 'mysql'
    ? dirname(__DIR__) . '/migrations/001_init.mysql.sql'
    : dirname(__DIR__) . '/migrations/001_init.sql';

$sql = file_get_contents($file);
if ($sql === false) {
    fwrite(STDERR, "Unable to read migration file.\n");
    exit(1);
}

Database::connection()->exec($sql);
fwrite(STDOUT, "Migration complete.\n");
