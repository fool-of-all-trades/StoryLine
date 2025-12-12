<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use DomainException;
use Throwable;
use App\Security\Csrf;
use App\Helpers\Logger;

class AuthController extends BaseController
{
    private UserService $userService;

    public function __construct() {
        $this->userService = new UserService();
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

        $logger = new Logger(getenv('APP_LOG_PATH') ?: __DIR__ . '/../../var/log/app.log');

        // ---- Throttle (rate limiting) ----
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'login_attempts_' . $ip; // per-IP

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['cnt' => 0, 'until' => 0];
        }

        $now = time();

        // Hash the IP with a salt to avoid leaking real IPs in the logger
        $salt = getenv('APP_IP_SALT') ?: 'default_salt_value_that_if_everything_works_out_wont_be_used';
        $ipHash = $ip ? hash('sha256', $ip . '|' . $salt) : null;

        $identifier = trim((string)($_POST['identifier'] ?? ($_POST['username'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        // If they try while the time hasn't passed yet -> 429
        if ($now < $_SESSION[$key]['until']) {
            $retryAfter = $_SESSION[$key]['until'] - $now;

            $logger->warning('login_rate_limited', [
                'identifier' => $identifier ?? null, // (see note below)
                'ip_hash' => $ipHash,
                'retry_after' => $retryAfter,
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            header('Retry-After: ' . $retryAfter);
            $this->json(['error' => 'too_many_attempts', 'retry_after' => $retryAfter], 429);
        }

        try {
            $payload = $this->userService->login($identifier, $password);

            // SUCCESS: reset throttle + rotate session & CSRF
            $_SESSION[$key] = ['cnt' => 0, 'until' => 0];
            session_regenerate_id(true);
            Csrf::regenerate();

            $_SESSION['user'] = $payload;

            $target = (string)($_POST['redirect'] ?? '/dashboard');
            if (!self::isSafeRedirect($target)) { $target = '/dashboard'; }

            http_response_code(303); // thanks to that refreshing /dashboard won't resend the form
            header('Location: ' . $target);
            exit;
        } catch (DomainException $e) {
            // FAIL -> increase throttle counter
            $_SESSION[$key]['cnt']++;

            $MAX_ATTEMPTS = 5;
            $BASE_LOCK = 60; // seconds

            // backoff: every MAX_ATTEMPTS doubles the lock time
            $multiplier = 1 << (int)floor(($_SESSION[$key]['cnt'] - 1) / $MAX_ATTEMPTS);
            $lockSeconds = $BASE_LOCK * $multiplier;

            if ($_SESSION[$key]['cnt'] >= $MAX_ATTEMPTS) {
                $_SESSION[$key]['cnt'] = 0;
                $_SESSION[$key]['until'] = $now + $lockSeconds;
            }

            $logger->warning('login_failed', [
                'identifier' => $identifier,
                'ip_hash' => $ipHash,
                'cnt' => $_SESSION[$key]['cnt'],
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            // not saying if that user exists! why would I help attackers?
            $this->json(['error' => 'invalid_credentials'], 401);
        } catch (Throwable $e) {
            $this->json(['error' => 'internal_error'], 500);
        }
    }

    public function logout(): void
    {
        self::requirePostWithCsrf();

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
            $user = $this->userService->register($username, $email, $password, $passwordConfirm);
            $this->json([
                'status' => 'success',
                'message' => 'Registration successful',
            ], 200);
        } catch (DomainException $e) {
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
