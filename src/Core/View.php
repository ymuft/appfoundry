<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        if (!preg_match('/\A[a-zA-Z0-9_\/-]+\z/', $template)) {
            throw new RuntimeException('Invalid view name.');
        }

        $views = realpath(Paths::resolve('views'));
        if ($views === false) {
            throw new RuntimeException('Views directory not found.');
        }

        $file = realpath($views . '/' . $template . '.php');
        if ($file === false || !str_starts_with($file, $views . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}
