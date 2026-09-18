<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Core/Env.php';
require dirname(__DIR__) . '/src/Core/Router.php';
require dirname(__DIR__) . '/src/Security/Csrf.php';

use App\Core\Env;
use App\Core\Router;
use App\Security\Csrf;

$failures = 0;

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        $failures++;
    } else {
        fwrite(STDOUT, "PASS: {$message}\n");
    }
};

putenv('APPFOUNDRY_TEST_BOOL=true');
$assert(Env::bool('APPFOUNDRY_TEST_BOOL') === true, 'Env boolean parsing');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$token = Csrf::token();
$assert(strlen($token) === 64, 'CSRF token length');
$assert(Csrf::validate($token), 'CSRF accepts current token');
$assert(!Csrf::validate('invalid'), 'CSRF rejects invalid token');

$router = new Router();
$called = false;
$router->get('/test', static function () use (&$called): void { $called = true; });
ob_start();
$router->dispatch('GET', '/test');
ob_end_clean();
$assert($called, 'Router dispatches exact route');

exit($failures === 0 ? 0 : 1);
