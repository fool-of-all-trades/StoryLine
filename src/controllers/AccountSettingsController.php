<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Security\Csrf;
use App\Services\AccountDeletionService;
use App\Services\AuthService;
use App\Services\AvatarService;
use App\Services\UserService;
use DomainException;
use Throwable;

class AccountSettingsController extends BaseController
{
    private UserService $userService;
    private AuthService $authService;
    private AccountDeletionService $accountDeletionService;
    private AvatarService $avatarService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->authService = auth_service();
        $this->accountDeletionService = new AccountDeletionService($this->authService);
        $this->avatarService = new AvatarService();
    }

    public function updateFavoriteQuote(): void
    {
        Csrf::verify();

        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sentence = $_POST['favorite_quote_sentence'] ?? '';
        $book = $_POST['favorite_quote_book'] ?? '';
        $author = $_POST['favorite_quote_author'] ?? '';

        try {
            $this->userService->setFavoriteQuote((int)$currentUser['id'], $sentence, $book, $author);
            http_response_code(200);
            echo json_encode([
                'status' => 'ok',
                'favorite_quote' => [
                    'sentence' => $sentence,
                    'book'     => $book,
                    'author'   => $author,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            http_response_code(422);
            echo json_encode(['error' => $code], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateUsername(): void
    {
        Csrf::verify();

        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $username = $_POST['username'] ?? '';

        try {
            $this->userService->changeUsername((int)$currentUser['id'], $username);
            http_response_code(200);
            echo json_encode([
                'status' => 'ok',
                'username' => $username,
            ], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            http_response_code(422);
            echo json_encode(['error' => $code], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updatePassword(): void
    {
        Csrf::verify();

        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'authentication_required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? ($_POST['password'] ?? '');
        $newPasswordConfirm = $_POST['confirm_password'] ?? ($_POST['password_confirm'] ?? '');

        try {
            $this->authService->changePassword($currentPassword, $newPassword, $newPasswordConfirm);
            http_response_code(200);
            echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'authentication_required' => 401,
                'too_many_requests' => 429,
                'internal_error' => 500,
                default => 422,
            };

            $clientCode = in_array($code, [
                'password_required',
                'password_too_short',
                'password_too_weak',
                'password_too_long',
            ], true) ? 'invalid_password' : $code;

            http_response_code($status);
            echo json_encode(['error' => $clientCode], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('[AccountSettingsController] password_change_failed: ' . get_class($e));
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateAvatar(): void
    {
        Csrf::verify();
        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!isset($_FILES['avatar'])) {
            http_response_code(400);
            echo json_encode(['error' => 'upload_failed'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $relativePath = $this->avatarService->updateAvatar(
                (int)$currentUser['id'],
                (string)$currentUser['public_id'],
                $_FILES['avatar']
            );
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = $code === 'upload_failed' ? 400 : 422;
            http_response_code($status);
            echo json_encode(['error' => $code], JSON_UNESCAPED_UNICODE);
            return;
        } catch (Throwable $e) {
            error_log('[AccountSettingsController] avatar_update_failed: ' . get_class($e));
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'ok',
            'avatar_url' => $relativePath,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function deleteAccount(): void
    {
        Csrf::verify();
        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'authentication_required'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $mode = (string)($_POST['mode'] ?? '');
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $confirmation = (string)($_POST['confirmation'] ?? '');

        try {
            $this->accountDeletionService->deleteAccount(
                (int)$currentUser['id'],
                $currentPassword,
                $mode,
                $confirmation
            );

            http_response_code(200);
            echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'authentication_required' => 401,
                'cannot_delete_last_admin' => 403,
                'too_many_requests' => 429,
                'internal_error' => 500,
                default => 422,
            };

            $safeError = in_array($code, [
                'authentication_required',
                'current_password_required',
                'invalid_current_password',
                'invalid_delete_mode',
                'confirmation_required',
                'cannot_delete_last_admin',
                'too_many_requests',
            ], true) ? $code : 'internal_error';

            if ($safeError === 'internal_error') {
                error_log('[AccountSettingsController] delete_account_domain_error: ' . $code);
            }

            http_response_code($status);
            echo json_encode(['error' => $safeError], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('[AccountSettingsController] delete_account_failed: ' . get_class($e));
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
        }
    }
}
