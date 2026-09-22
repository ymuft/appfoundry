<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\Database;
use App\Core\Env;
use App\Core\Paths;

require dirname(__DIR__) . '/vendor/autoload.php';
Bootstrap::console(dirname(__DIR__));

$driver = Env::get('DB_DRIVER', 'sqlite');
$file = Paths::resolve($driver === 'mysql'
    ? 'migrations/001_init.mysql.sql'
    : 'migrations/001_init.sql');

$sql = file_get_contents($file);
if ($sql === false) {
    fwrite(STDERR, "Unable to read migration file.\n");
    exit(1);
}

Database::connection()->exec($sql);
fwrite(STDOUT, "Migration complete.\n");
