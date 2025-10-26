<?php
declare(strict_types=1);

namespace App\Controllers;

use DomainException;
use Throwable;
use App\Security\Csrf;
use App\Services\AdminService;

final class AdminController
{
    private static function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // require_role(['admin']); // to be implemented
}