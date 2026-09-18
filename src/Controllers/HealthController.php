<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use Throwable;

final class HealthController
{
    public function show(): void
    {
        try {
            Database::connection()->query('SELECT 1');
            Response::json(['status' => 'ok', 'database' => 'ok']);
        } catch (Throwable $exception) {
            Response::json(['status' => 'degraded', 'database' => 'error'], 503);
        }
    }
}
