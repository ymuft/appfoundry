<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;
use App\Security\Auth;
use App\Security\Csrf;

final class DashboardController
{
    public function index(): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }

        View::render('dashboard', [
            'user' => Auth::user(),
            'csrf' => Csrf::token(),
        ]);
    }
}
