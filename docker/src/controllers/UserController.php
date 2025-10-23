<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use App\Models\Role;
use DomainException;
use Throwable;

final class UserController
{
    private static function json(mixed $data, int $code=200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function login(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $svc = new UserService();

        try {
            $payload = $svc->login($username, $password);
            session_regenerate_id(true);
            $_SESSION['user'] = $payload;
            self::json(['ok'=>true, 'user'=>$payload], 200);
        } catch (DomainException $e) {
            self::json(['error'=>$e->getMessage()], 401);
        } catch (Throwable $e) {
            self::json(['error'=>'internal_error'], 500);
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::json(['ok'=>true], 200);
    }

    public static function register(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $svc = new UserService();
        try {
            $id = $svc->register($username, $password, Role::User);
            self::json(['id'=>$id], 201);
        } catch (DomainException $e) {
            $code = match($e->getMessage()) {
                'username_taken' => 409,
                'weak_password', 'weak_username' => 400,
                default => 400,
            };
            self::json(['error'=>$e->getMessage()], $code);
        } catch (Throwable $e) {
            self::json(['error'=>'internal_error'], 500);
        }
    }
}
