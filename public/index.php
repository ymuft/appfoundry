<?php

declare(strict_types=1);

use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;
use App\Controllers\UserController;
use App\Core\Bootstrap;
use App\Core\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

Bootstrap::web(dirname(__DIR__));

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
