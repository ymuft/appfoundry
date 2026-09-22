<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Bootstrap;
use App\Core\Env;
use App\Core\Paths;
use App\Core\View;

$failures = 0;
$assert = static function (bool $condition, string $message) use (&$failures): void {
    fwrite($condition ? STDOUT : STDERR, ($condition ? 'PASS: ' : 'FAIL: ') . $message . "\n");
    if (!$condition) {
        $failures++;
    }
};

$tempRoot = sys_get_temp_dir() . '/appfoundry-paths-' . bin2hex(random_bytes(6));
mkdir($tempRoot . '/views', 0775, true);
mkdir($tempRoot . '/storage', 0775, true);
file_put_contents($tempRoot . '/.env', "APPFOUNDRY_EMBED_TEST=loaded\n");
file_put_contents($tempRoot . '/views/embedded.php', '<strong><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></strong>');

$cleanup = static function (string $directory) use (&$cleanup): void {
    if (!is_dir($directory)) {
        return;
    }
    foreach (scandir($directory) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $directory . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $cleanup($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($directory);
};
register_shutdown_function(static function () use ($cleanup, $tempRoot): void {
    $cleanup($tempRoot);
});

Bootstrap::console($tempRoot);
$assert(Paths::root() === realpath($tempRoot), 'Bootstrap sets explicit application root');
$assert(Env::get('APPFOUNDRY_EMBED_TEST') === 'loaded', 'Relative .env is loaded from application root');
$assert(
    Paths::resolve('storage/app.sqlite') === realpath($tempRoot) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app.sqlite',
    'Relative filesystem path resolves from application root'
);

$absolute = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'absolute.sqlite';
$assert(Paths::resolve($absolute) === $absolute, 'Absolute filesystem path remains unchanged');

ob_start();
View::render('embedded', ['message' => 'embedded-ready']);
$rendered = (string) ob_get_clean();
$assert($rendered === '<strong>embedded-ready</strong>', 'View resolves from configured application root');

$invalidRootRejected = false;
try {
    Paths::setRoot($tempRoot . '/does-not-exist');
} catch (RuntimeException) {
    $invalidRootRejected = true;
}
$assert($invalidRootRejected, 'Invalid application root is rejected');

exit($failures === 0 ? 0 : 1);
