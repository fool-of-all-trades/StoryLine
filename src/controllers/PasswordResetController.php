<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\Csrf;
use App\Services\AuthService;
use DomainException;
use Throwable;

class PasswordResetController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = auth_service();
    }

    public function passwordForgotPage(): void
    {
        $this->render('password_forgot');
    }

    public function passwordResetPage(): void
    {
        $selector = (string)($_GET['selector'] ?? '');
        $token = (string)($_GET['token'] ?? '');
        $resetError = null;

        if ($selector === '' || $token === '') {
            $resetError = 'invalid_or_expired_token';
        } else {
            try {
                $this->authService->assertCanResetPassword($selector, $token);
            } catch (DomainException $e) {
                $resetError = $e->getMessage();
            }
        }

        $this->render('password_reset', [
            'selector' => $selector,
            'token' => $token,
            'resetError' => $resetError,
        ]);
    }

    public function forgot(): void
    {
        Csrf::verify();

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '') {
            $this->json(['error' => 'email_required'], 422);
        }

        try {
            $this->authService->requestPasswordReset($email);
        } catch (DomainException $e) {
            error_log('[PasswordResetController] forgot_failed: ' . $e->getMessage());
        } catch (Throwable $e) {
            error_log('[PasswordResetController] forgot_failed: ' . get_class($e));
        }

        $this->json(['status' => 'ok']);
    }


    public function reset(): void
    {
        Csrf::verify();

        $selector = (string)($_POST['selector'] ?? '');
        $token = (string)($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        try {
            $this->authService->resetPassword($selector, $token, $password, $passwordConfirm);
            $this->json(['status' => 'ok']);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'too_many_requests' => 429,
                'internal_error' => 500,
                default => 422,
            };

            $this->json(['error' => $code], $status);
        } catch (Throwable $e) {
            error_log('[PasswordResetController] reset_failed: ' . get_class($e));
            $this->json(['error' => 'internal_error'], 500);
        }
    }

}
