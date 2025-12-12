<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\UserService;
use App\Services\PasswordResetService;
use App\Security\Csrf;

use DomainException;

use Throwable;

class PasswordResetController extends BaseController
{
    private UserService $userService;
    private PasswordResetService $resetService;

    public function __construct() {
        $this->userService = new UserService();
        $this->resetService = new PasswordResetService();
    }

    public function passwordForgotPage(): void
    {
        $this->render('password_forgot');
    }

    public function passwordResetPage(): void
    {
        $this->render('password_reset');
    }

    public function forgot(): void
    {
        Csrf::verify();
        header("Content-Type: application/json");

        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            http_response_code(422);
            echo json_encode(['error' => 'email_required']);
            return;
        }

        $user = $this->userService->findByEmail($email);

        if (!$user) {
            echo json_encode(['status' => 'ok']);
            return;
        }

        try {
            $token = $this->resetService->createToken($user->id);
        } catch (Throwable $e) {
            error_log("Password reset error: ".$e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'server_error']);
            return;
        }

        $resetUrl = "http://localhost:8081/password/reset?token=" . urlencode($token);

        file_put_contents('/app/mail.log',
            "RESET for $email:\n$resetUrl\n\n",
            FILE_APPEND
        );

        echo json_encode(['status' => 'ok']);
    }


    public function reset(): void
    {
        Csrf::verify();

        header("Content-Type: application/json");

        $token = $_POST['token'] ?? '';
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if ($pass1 !== $pass2) {
            http_response_code(422);
            echo json_encode(['error' => 'password_mismatch']);
            return;
        }

        $userId = $this->resetService->validateToken($token);

        if (!$userId) {
            http_response_code(422);
            echo json_encode(['error' => 'invalid_or_expired_token']);
            return;
        }

        // Update password
        try {
            $this->userService->changePassword($userId, $pass1);
        } catch (DomainException $e) {
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        $this->resetService->deleteToken($token);

        echo json_encode(['status' => 'ok']);
    }

}