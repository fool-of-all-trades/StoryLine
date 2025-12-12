<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function dashboardPage(): void
    {
        $this->render('dashboard');
    }
}
