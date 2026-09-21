<?php

declare(strict_types=1);

use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\UserController;
use App\Core\ConfigValidator;
use App\Core\Env;
use App\Core\Router;
use App\Security\SecurityHeaders;

require dirname(__DIR__) . '/vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');
ConfigValidator::assertSafe();
SecurityHeaders::apply();

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');

session_name(Env::get('APP_SESSION_NAME', 'appfoundry_session') ?? 'appfoundry_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => Env::bool('APP_SECURE_COOKIES', false),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$auth = new AuthController();
$dashboard = new DashboardController();
$health = new HealthController();
$users = new UserController();
$audit = new AuditController();

$router = new Router();
$router->get('/', [$dashboard, 'index']);
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->post('/logout', [$auth, 'logout']);
$router->get('/health', [$health, 'show']);
$router->get('/admin/users', [$users, 'index']);
$router->post('/admin/users', [$users, 'create']);
$router->get('/admin/audit', [$audit, 'index']);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
