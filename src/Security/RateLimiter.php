<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Database;
use PDO;

final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $pdo = Database::connection();
        $threshold = time() - $windowSeconds;

        $pdo->prepare('DELETE FROM rate_limits WHERE attempted_at < :threshold')
            ->execute(['threshold' => $threshold]);

        $statement = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE key_name = :key AND attempted_at >= :threshold');
        $statement->execute(['key' => $key, 'threshold' => $threshold]);

        return (int) $statement->fetchColumn() >= $maxAttempts;
    }

    public static function hit(string $key): void
    {
        Database::connection()
            ->prepare('INSERT INTO rate_limits (key_name, attempted_at) VALUES (:key, :attempted_at)')
            ->execute(['key' => $key, 'attempted_at' => time()]);
    }

    public static function clear(string $key): void
    {
        Database::connection()
            ->prepare('DELETE FROM rate_limits WHERE key_name = :key')
            ->execute(['key' => $key]);
    }
}
