<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AdminService;

final class AdminController
{
    private static function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /admin */
    public static function index(): void
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
        include __DIR__ . '/../../public/views/admin.php';
    }

    // if I ever decide to make admin panel fetch stats via AJAX
    // public static function stats(): void
    // {
    //     if (!is_admin()) {
    //         http_response_code(403);
    //         echo json_encode(['error' => 'forbidden']);
    //         return;
    //     }

    //     $date    = $_GET['date'] ?? 'today';
    //     $service = new AdminService();
    //     $stats   = $service->getDashboardData($date);

    //     header('Content-Type: application/json; charset=utf-8');
    //     echo json_encode($stats, JSON_UNESCAPED_UNICODE);
    // }

}
