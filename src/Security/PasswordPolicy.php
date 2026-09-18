<?php

declare(strict_types=1);

namespace App\Security;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;
    public const MAX_LENGTH = 72;

    public static function accepts(string $password): bool
    {
        $length = strlen($password);
        return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
    }
}
