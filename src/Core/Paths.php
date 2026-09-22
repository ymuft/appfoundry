<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Paths
{
    private static ?string $root = null;

    public static function setRoot(string $root): void
    {
        $root = trim($root);
        $resolved = $root === '' ? false : realpath($root);
        if ($resolved === false || !is_dir($resolved)) {
            throw new RuntimeException('Application root does not exist: ' . $root);
        }

        self::$root = rtrim($resolved, DIRECTORY_SEPARATOR);
    }

    public static function root(): string
    {
        if (self::$root !== null) {
            return self::$root;
        }

        $configured = getenv('APP_ROOT');
        if ($configured !== false && trim($configured) !== '') {
            self::setRoot($configured);
            return self::$root;
        }

        self::setRoot(dirname(__DIR__, 2));
        return self::$root;
    }

    public static function resolve(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return self::root();
        }

        if (self::isAbsolute($path)) {
            return $path;
        }

        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return self::root() . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
