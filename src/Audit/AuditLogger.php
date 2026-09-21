<?php

declare(strict_types=1);

namespace App\Audit;

use App\Core\Database;
use Throwable;

final class AuditLogger
{
    public static function record(?int $userId, string $action, array $metadata = []): bool
    {
        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO audit_logs (user_id, action, ip_address, user_agent, metadata, created_at) VALUES (:user_id, :action, :ip_address, :user_agent, :metadata, :created_at)'
            );
            $statement->execute([
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => gmdate('c'),
            ]);
            return true;
        } catch (Throwable $exception) {
            error_log('AppFoundry audit log failure: ' . $exception->getMessage());
            return false;
        }
    }
}
