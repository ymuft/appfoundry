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

$pdo = Database::connection();

$insertAudit = $pdo->prepare(
    'INSERT INTO audit_logs (user_id, action, ip_address, user_agent, metadata, created_at)
     VALUES (:uid, :action, :ip, :ua, :meta, :ts)'
);

$insertAudit->execute(['uid' => null, 'action' => 'test.first',  'ip' => '127.0.0.1', 'ua' => 'test', 'meta' => '{"n":1}', 'ts' => gmdate('c')]);
$insertAudit->execute(['uid' => null, 'action' => 'test.second', 'ip' => '127.0.0.1', 'ua' => 'test', 'meta' => '{"n":2}', 'ts' => gmdate('c')]);

$rows = $pdo->query('SELECT action FROM audit_logs ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
$assert(isset($rows[0]) && $rows[0]['action'] === 'test.second', 'Audit log returns newest events first');

for ($i = 0; $i < 60; $i++) {
    $insertAudit->execute([
        'uid' => null,
        'action' => 'test.x',
        'ip' => '127.0.0.1',
        'ua' => 'test',
        'meta' => '{"n":' . $i . '}',
        'ts' => gmdate('c'),
    ]);
}

$statement = $pdo->prepare('SELECT id FROM audit_logs ORDER BY id DESC LIMIT :limit OFFSET :offset');

$statement->bindValue('limit', 50, PDO::PARAM_INT);
$statement->bindValue('offset', 0, PDO::PARAM_INT);
$statement->execute();
$page1 = $statement->fetchAll();
$assert(count($page1) === 50, 'Audit log first page returns 50 rows');

$statement->bindValue('offset', 50, PDO::PARAM_INT);
$statement->execute();
$page2 = $statement->fetchAll();
$assert(count($page2) >= 1, 'Audit log second page returns remaining rows');
$assert($page1[0]['id'] !== $page2[0]['id'], 'Audit log pages do not overlap');

$malicious = json_encode([
    'email' => 'x@example.com',
    'name'  => '<script>alert(1)</script>',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$rendered = (static function () use ($malicious): string {
    ob_start();
    App\Core\View::render('audit', [
        'user' => [
            'id'    => 1,
            'email' => 'admin@example.com',
            'name'  => 'Admin',
            'role'  => 'admin',
        ],
        'events' => [[
            'id'         => 1,
            'user_id'    => null,
            'action'     => 'test.xss',
            'ip_address' => '127.0.0.1',
            'metadata'   => $malicious,
            'created_at' => gmdate('c'),
        ]],
        'page' => 1,
    ]);
    return (string) ob_get_clean();
})();

$assert(
    !str_contains($rendered, '<script>alert(1)</script>'),
    'Audit view does not emit raw <script> from metadata'
);
$assert(
    str_contains($rendered, '&lt;script&gt;alert(1)&lt;/script&gt;'),
    'Audit view HTML-escapes metadata'
);

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