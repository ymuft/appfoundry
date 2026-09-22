<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $driver = Env::get('DB_DRIVER', 'sqlite');

        if ($driver === 'sqlite') {
            $database = Env::get('DB_DATABASE', 'storage/app.sqlite');
            if ($database === null || trim($database) === '') {
                throw new RuntimeException('DB_DATABASE is required for SQLite.');
            }
            $database = Paths::resolve($database);
            $directory = dirname($database);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create SQLite directory: ' . $directory);
            }
            $dsn = 'sqlite:' . $database;
            $username = null;
            $password = null;
        } elseif ($driver === 'mysql') {
            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = Env::get('DB_PORT', '3306');
            $database = Env::get('DB_DATABASE', 'appfoundry');
            $username = Env::get('DB_USERNAME', 'appfoundry');
            $password = Env::get('DB_PASSWORD', '');
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);
        } else {
            throw new RuntimeException('Unsupported DB_DRIVER: ' . $driver);
        }

        self::$connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        if ($driver === 'sqlite') {
            self::$connection->exec('PRAGMA foreign_keys = ON');
            self::$connection->exec('PRAGMA busy_timeout = 5000');
        }

        return self::$connection;
    }
}
