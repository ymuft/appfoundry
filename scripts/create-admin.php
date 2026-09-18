<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

$options = getopt('', ['name:', 'email:', 'password:']);
$name = trim((string) ($options['name'] ?? ''));
$email = trim((string) ($options['email'] ?? ''));
$password = (string) ($options['password'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Usage: php scripts/create-admin.php --name='Admin' --email='admin@example.com' --password='at-least-12-characters'\n");
    exit(1);
}

$statement = Database::connection()->prepare(
    'INSERT INTO users (name, email, password_hash, role, is_active, created_at) VALUES (:name, :email, :password_hash, :role, 1, :created_at)'
);
$statement->execute([
    'name' => $name,
    'email' => strtolower($email),
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'admin',
    'created_at' => gmdate('c'),
]);

fwrite(STDOUT, "Admin created: {$email}\n");
