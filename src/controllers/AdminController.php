<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AdminService;
use Throwable;

class AdminController extends BaseController
{
    /** GET /admin */
    public function index(): void
    {
        if (!is_admin()) {
            $this->notFound('Not found');
        }

        $date  = $_GET['date'] ?? 'today';

        $service = new AdminService();

        try {
            $stats = $service->getDashboardData($date);
        } catch (Throwable $e) {
            error_log('[AdminController] dashboard_failed: ' . get_class($e));
            http_response_code(500);
            echo 'Admin dashboard is temporarily unavailable.';
            return;
        }

        $title = "StoryLine — Admin";
        
        $this->render('admin', [
            'title' => $title,
            'stats' => $stats,
            'date'  => $date,
        ]);
    }
}
