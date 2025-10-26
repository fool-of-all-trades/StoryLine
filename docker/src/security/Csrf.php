<?php
declare(strict_types=1);

namespace App\Security;

final class Csrf
{
    public static function token(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function inputField(): string {
        return '<input type="hidden" name="csrf" value="'.htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8').'">';
    }

    public static function verify(): void {
        $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals(self::token(), (string)$sent)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error'=>'csrf_failed']);
            exit;
        }
    }

    public static function regenerate(): void {
        unset($_SESSION['csrf']);
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
}
