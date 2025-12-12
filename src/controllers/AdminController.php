<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\AdminService;

class AdminController extends BaseController
{
    /** GET /admin */
    public function index(): void
    {
        if (!is_admin()) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $date  = $_GET['date'] ?? 'today';

        $service = new AdminService();

        // $stats will be visible in admin.php
        $stats   = $service->getDashboardData($date);

        $title = "StoryLine — Admin";
        
        $this->render('admin', [
            'title' => $title,
            'stats' => $stats,
            'date'  => $date,
        ]);
    }
}
