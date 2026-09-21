<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\View;
use App\Security\Auth;
use PDO;

final class AuditController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $this->requireAdmin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $statement = Database::connection()->prepare(
            'SELECT id, user_id, action, ip_address, metadata, created_at
             FROM audit_logs
             ORDER BY id DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('limit', self::PER_PAGE + 1, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $events = $statement->fetchAll(PDO::FETCH_ASSOC);

        $hasNextPage = count($events) > self::PER_PAGE;
        if ($hasNextPage) {
            array_pop($events);
        }

        View::render('audit', [
            'user' => Auth::user(),
            'events' => $events,
            'page' => $page,
            'hasNextPage' => $hasNextPage,
        ]);
    }

    private function requireAdmin(): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }

        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}
