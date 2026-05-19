<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use DomainException;
use Throwable;
use App\Security\Csrf;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    private function isSafeRedirect(string $url): bool {
        return str_starts_with($url, '/')
            && !str_starts_with($url, '//')
            && !str_contains($url, "\n");
    }

    private function requirePostWithCsrf(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }
        Csrf::verify();
    }

    public function loginPage(): void
    {
        $this->render('login');
    }

    public function registerPage(): void
    {
        $this->render('register');
    }

    /**
    *  Handle user login
    *
    * Requires a POST request with a valid CSRF token. Applies per-IP throttling
    * (5 failed attempts -> 60s lock; doubles every additional 5 failures).
    * On successful authentication, rotates the PHP session ID and regenerates the
    * CSRF token, stores the authenticated user payload in $_SESSION['user'], and
    * returns JSON { ok: true, user: {...} } with HTTP 200.
    *
    * Error responses:
    * - 429 Too Many Requests  — { error: "too_many_attempts", retry_after: <seconds> }
    * - 401 Unauthorized       — { error: "invalid_credentials" } (no user enumeration)
    * - 500 Internal Server Error — { error: "internal_error" }
    */
    public function login(): void
    {
        self::requirePostWithCsrf();

        $identifier = trim((string)($_POST['identifier'] ?? ($_POST['username'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        try {
            $payload = $this->authService->login($identifier, $password);

            session_regenerate_id(true);
            Csrf::regenerate();

            $_SESSION['user'] = $payload;

            $target = (string)($_POST['redirect'] ?? '/dashboard');
            if (!self::isSafeRedirect($target)) { $target = '/dashboard'; }

            http_response_code(303); // thanks to that refreshing /dashboard won't resend the form
            header('Location: ' . $target);
            exit;
        } catch (DomainException $e) {
            $code = $e->getMessage();

            if ($code === 'too_many_attempts') {
                $this->json(['error' => 'too_many_attempts'], 429);
            }

            if ($code === 'internal_error') {
                $this->json(['error' => 'internal_error'], 500);
            }

            $this->json(['error' => 'invalid_credentials'], 401);
        } catch (Throwable $e) {
            error_log('[AuthController] login_failed: ' . get_class($e));
            $this->json(['error' => 'internal_error'], 500);
        }
    }

    public function logout(): void
    {
        self::requirePostWithCsrf();

        $this->authService->logout();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        // start a new, empty session + fresh CSRF (immediately without logging in)
        session_start();
        Csrf::regenerate();

        http_response_code(204); // No Content

        // reload the dashboard page
        $target = (string)($_POST['redirect'] ?? '/dashboard');
        if (!self::isSafeRedirect($target)) { $target = '/dashboard'; }
        header('Location: ' . $target);
        exit;
    }

    public function register(): void
    {
        self::requirePostWithCsrf();
        
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        try {
            $this->authService->register($username, $email, $password, $passwordConfirm);
            $this->json([
                'status' => 'success',
                'message' => 'Registration successful',
            ], 200);
        } catch (DomainException $e) {
            if ($e->getMessage() === 'too_many_attempts') {
                $this->json([
                    'status' => 'error',
                    'code'   => 'too_many_attempts',
                ], 429);
            }

            $this->json([
                'status' => 'error',
                'code'   => $e->getMessage()
            ], 400);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'code'   => 'internal_error',
            ], 500);
        }
    }
}
