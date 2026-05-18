<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\QuoteService;
use Throwable;

class DashboardController extends BaseController
{
    public function dashboardPage(): void
    {
        try {
            (new QuoteService())->getOrEnsureToday();
        } catch (Throwable $e) {
            error_log('[DashboardController] quote_ensure_failed');
        }

        $this->render('dashboard');
    }
}
