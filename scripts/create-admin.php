<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\Database;
use App\Security\PasswordPolicy;

require dirname(__DIR__) . '/vendor/autoload.php';
Bootstrap::console(dirname(__DIR__));

function promptPassword(): string
{
    fwrite(STDOUT, 'Password: ');
    $hidden = false;

    if (PHP_OS_FAMILY !== 'Windows' && function_exists('shell_exec')) {
        $mode = trim((string) shell_exec('stty -g 2>/dev/null'));
        if ($mode !== '') {
            shell_exec('stty -echo');
            $hidden = true;
        }
    }

    try {
        return rtrim((string) fgets(STDIN), "\r\n");
    } finally {
        if ($hidden) {
            shell_exec('stty echo');
            fwrite(STDOUT, "\n");
        }
    }
}

$options = getopt('', ['name:', 'email:', 'password::']);
$name = trim((string) ($options['name'] ?? ''));
$email = strtolower(trim((string) ($options['email'] ?? '')));
$password = isset($options['password']) && is_string($options['password']) && $options['password'] !== ''
    ? $options['password']
    : promptPassword();

if ($name === '' || strlen($name) > 120 || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !PasswordPolicy::accepts($password)) {
    fwrite(STDERR, "Usage: php scripts/create-admin.php --name='Admin' --email='admin@example.com' [--password='12-72 characters']\n");
    fwrite(STDERR, "Omit --password to enter it without placing the secret in your shell history.\n");
    exit(1);
}

$statement = Database::connection()->prepare(
    'INSERT INTO users (name, email, password_hash, role, is_active, created_at) VALUES (:name, :email, :password_hash, :role, 1, :created_at)'
);

try {
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => gmdate('c'),
    ]);
} catch (\PDOException $exception) {
    if ((string) $exception->getCode() !== '23000') {
        throw $exception;
    }

    fwrite(STDERR, "An account with that email already exists.\n");
    exit(1);
}

fwrite(STDOUT, "Admin created: {$email}\n");
