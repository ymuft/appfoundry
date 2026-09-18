<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\Router;
use App\Security\Auth;
use App\Security\Csrf;
use App\Security\PasswordPolicy;
use App\Security\RateLimiter;

ob_start();
$failures = 0;
$results = [];

$assert = static function (bool $condition, string $message) use (&$failures, &$results): void {
    $results[] = [$condition, $message];
    if (!$condition) {
        $failures++;
    }
};

putenv('APPFOUNDRY_TEST_BOOL=true');
$assert(Env::bool('APPFOUNDRY_TEST_BOOL') === true, 'Env boolean parsing');
$assert(PasswordPolicy::accepts(str_repeat('a', 12)), 'Password policy accepts minimum length');
$assert(!PasswordPolicy::accepts(str_repeat('a', 73)), 'Password policy rejects bcrypt-truncating length');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$token = Csrf::token();
$assert(strlen($token) === 64, 'CSRF token length');
$assert(Csrf::validate($token), 'CSRF accepts current token');
Csrf::rotate();
$assert(!Csrf::validate($token), 'CSRF rotation invalidates previous token');

$router = new Router();
$called = false;
$router->get('/test', static function () use (&$called): void { $called = true; });
$router->dispatch('GET', '/test?query=1');
$assert($called, 'Router dispatches exact route while ignoring query string');

$tempDatabase = sys_get_temp_dir() . '/appfoundry-test-' . bin2hex(random_bytes(6)) . '.sqlite';
putenv('DB_DRIVER=sqlite');
putenv('DB_DATABASE=' . $tempDatabase);
register_shutdown_function(static function () use ($tempDatabase): void {
    @unlink($tempDatabase);
    @unlink($tempDatabase . '-wal');
    @unlink($tempDatabase . '-shm');
});

$sql = file_get_contents(dirname(__DIR__) . '/migrations/001_init.sql');
$assert(is_string($sql), 'SQLite migration can be read');
if (is_string($sql)) {
    Database::connection()->exec($sql);
}

$key = 'test:' . bin2hex(random_bytes(4));
$assert(!RateLimiter::tooManyAttempts($key, 2, 60), 'Rate limiter starts below limit');
RateLimiter::hit($key);
RateLimiter::hit($key);
$assert(RateLimiter::tooManyAttempts($key, 2, 60), 'Rate limiter blocks at configured threshold');
RateLimiter::clear($key);
$assert(!RateLimiter::tooManyAttempts($key, 2, 60), 'Rate limiter clear resets key');

$password = 'correct-horse-battery-staple';
Database::connection()->prepare(
    'INSERT INTO users (name, email, password_hash, role, is_active, created_at) VALUES (:name, :email, :password_hash, :role, 1, :created_at)'
)->execute([
    'name' => 'Test Admin',
    'email' => 'admin@example.com',
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'admin',
    'created_at' => gmdate('c'),
]);

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'AppFoundry test runner';
$assert(!Auth::attempt('admin@example.com', 'wrong-password'), 'Authentication rejects invalid password');
$assert(Auth::attempt('ADMIN@example.com', $password), 'Authentication accepts valid credentials case-insensitively');
$assert(Auth::hasRole('admin'), 'Authenticated session exposes role');
$auditCount = (int) Database::connection()->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'auth.login'")->fetchColumn();
$assert($auditCount === 1, 'Successful login is written to the audit log');

ob_end_clean();
foreach ($results as [$passed, $message]) {
    fwrite($passed ? STDOUT : STDERR, ($passed ? 'PASS: ' : 'FAIL: ') . $message . "\n");
}

exit($failures === 0 ? 0 : 1);
