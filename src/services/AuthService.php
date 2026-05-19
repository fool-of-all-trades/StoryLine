<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\Database;
use App\Repository\ProfileRepository;
use Delight\Auth\AmbiguousUsernameException;
use Delight\Auth\Auth;
use Delight\Auth\DuplicateUsernameException;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\Role as DelightRole;
use Delight\Auth\SecondFactorRequiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UnknownUsernameException;
use Delight\Auth\UserAlreadyExistsException;
use DomainException;
use PDOException;
use Throwable;

final class AuthService
{
    private ?Auth $auth = null;
    private bool $authUnavailable = false;

    public function __construct(
        private ?ProfileRepository $profiles = null
    ) {}

    public function isAvailable(): bool
    {
        return $this->auth() !== null;
    }

    /**
     * Registers a local account and creates the app-owned public profile.
     * Email verification is intentionally disabled in this phase by passing no
     * confirmation callback to delight-im/auth, so new users are verified now.
     *
     * @throws DomainException
     */
    public function register(string $displayName, string $email, string $password, string $passwordConfirm): array
    {
        $displayName = trim($displayName);
        $email = trim($email);

        $this->assertValidDisplayName($displayName);

        if ($password !== $passwordConfirm) {
            throw new DomainException('password_mismatch');
        }

        $this->assertStrongPassword($password);

        $auth = $this->auth();
        if (!$auth) {
            throw new DomainException('internal_error');
        }

        $profiles = $this->profiles ??= new ProfileRepository();
        if ($profiles->findByDisplayName($displayName)) {
            throw new DomainException('username_taken');
        }

        try {
            Database::begin();

            $userId = $auth->registerWithUniqueUsername($email, $password, $displayName);
            $profile = $profiles->createForUser($userId, $displayName);

            Database::commit();

            return [
                'id' => $userId,
                'email' => $email,
                'username' => $profile['display_name'] ?? $displayName,
                'public_id' => $profile['public_id'] ?? null,
                'role' => 'user',
                'verified' => true,
                'profile' => $profile,
            ];
        } catch (UserAlreadyExistsException $e) {
            Database::rollBack();
            throw new DomainException('email_taken');
        } catch (DuplicateUsernameException $e) {
            Database::rollBack();
            throw new DomainException('username_taken');
        } catch (InvalidEmailException $e) {
            Database::rollBack();
            throw new DomainException('invalid_email');
        } catch (InvalidPasswordException $e) {
            Database::rollBack();
            throw new DomainException('weak_password');
        } catch (TooManyRequestsException $e) {
            Database::rollBack();
            throw new DomainException('too_many_attempts');
        } catch (PDOException $e) {
            Database::rollBack();
            if ($e->getCode() === '23505') {
                throw new DomainException('username_taken');
            }

            error_log('[AuthService] register_profile_failed: ' . get_class($e));
            throw new DomainException('internal_error');
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('[AuthService] register_failed: ' . get_class($e));
            throw new DomainException('internal_error');
        }
    }

    /**
     * @throws DomainException
     */
    public function login(string $identifier, string $password): array
    {
        $identifier = trim($identifier);

        if ($identifier === '' || $password === '') {
            throw new DomainException('invalid_credentials');
        }

        $auth = $this->auth();
        if (!$auth) {
            throw new DomainException('internal_error');
        }

        try {
            if (str_contains($identifier, '@')) {
                $auth->login($identifier, $password);
            } else {
                $auth->loginWithUsername($identifier, $password);
            }

            $user = $this->currentUser();
            if (!$user || empty($user['public_id'])) {
                $this->logout();
                throw new DomainException('internal_error');
            }

            return $user;
        } catch (
            InvalidEmailException |
            InvalidPasswordException |
            UnknownUsernameException |
            AmbiguousUsernameException $e
        ) {
            throw new DomainException('invalid_credentials');
        } catch (EmailNotVerifiedException $e) {
            throw new DomainException('email_not_verified');
        } catch (SecondFactorRequiredException $e) {
            throw new DomainException('second_factor_required');
        } catch (TooManyRequestsException $e) {
            throw new DomainException('too_many_attempts');
        } catch (Throwable $e) {
            error_log('[AuthService] login_failed: ' . get_class($e));
            throw new DomainException('internal_error');
        }
    }

    public function logout(): void
    {
        try {
            $this->auth()?->logOut();
        } catch (Throwable $e) {
            error_log('[AuthService] logout_failed: ' . get_class($e));
        }

        $this->auth = null;
    }

    public function isLoggedIn(): bool
    {
        try {
            return (bool)($this->auth()?->isLoggedIn() ?? false);
        } catch (Throwable $e) {
            $this->markUnavailable($e);
            return false;
        }
    }

    public function userId(): ?int
    {
        try {
            if (!$this->isLoggedIn()) {
                return null;
            }

            return (int)$this->auth()->getUserId();
        } catch (Throwable $e) {
            $this->markUnavailable($e);
            return null;
        }
    }

    public function currentUser(): ?array
    {
        try {
            $auth = $this->auth();
            if (!$auth || !$auth->isLoggedIn()) {
                return null;
            }

            $userId = (int)$auth->getUserId();
            $profile = $this->safeProfileByUserId($userId);
            if (!$profile) {
                return null;
            }

            $isAdmin = $this->hasRole(DelightRole::ADMIN);

            return [
                'id' => $userId,
                'email' => $auth->getEmail(),
                'username' => $profile['display_name'],
                'public_id' => $profile['public_id'],
                'role' => $isAdmin ? 'admin' : 'user',
                'verified' => $this->isVerified($userId),
                'is_admin' => $isAdmin,
                'is_remembered' => $auth->isRemembered(),
                'profile' => $profile,
            ];
        } catch (Throwable $e) {
            $this->markUnavailable($e);
            return null;
        }
    }

    public function hasRole(int $role): bool
    {
        try {
            return (bool)($this->auth()?->hasRole($role) ?? false);
        } catch (Throwable $e) {
            $this->markUnavailable($e);
            return false;
        }
    }

    private function auth(): ?Auth
    {
        if ($this->authUnavailable) {
            return null;
        }

        if ($this->auth instanceof Auth) {
            return $this->auth;
        }

        try {
            $this->auth = new Auth(Database::get(), $_SERVER['REMOTE_ADDR'] ?? null);
            return $this->auth;
        } catch (Throwable $e) {
            $this->markUnavailable($e);
            return null;
        }
    }

    private function safeProfileByUserId(int $userId): ?array
    {
        try {
            $profiles = $this->profiles ??= new ProfileRepository();

            return $profiles->findByUserId($userId);
        } catch (Throwable $e) {
            error_log('[AuthService] profile_lookup_unavailable: ' . get_class($e));
            return null;
        }
    }

    private function isVerified(int $userId): bool
    {
        try {
            $st = Database::get()->prepare('SELECT verified FROM users WHERE id = :id');
            $st->execute([':id' => $userId]);

            return (int)$st->fetchColumn() === 1;
        } catch (Throwable $e) {
            error_log('[AuthService] verified_lookup_unavailable: ' . get_class($e));
            return false;
        }
    }

    private function assertValidDisplayName(string $displayName): void
    {
        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $displayName)) {
            throw new DomainException('invalid_username');
        }
    }

    private function assertStrongPassword(string $password): void
    {
        if ($password === '') {
            throw new DomainException('password_required');
        }

        if (mb_strlen($password) < 8) {
            throw new DomainException('password_too_short');
        }

        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasDigit = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

        if (!$hasLower || !$hasUpper || !$hasDigit || !$hasSpecial) {
            throw new DomainException('password_too_weak');
        }
    }

    private function markUnavailable(Throwable $e): void
    {
        if (!$this->authUnavailable) {
            error_log('[AuthService] delight_auth_unavailable: ' . get_class($e));
        }

        $this->authUnavailable = true;
        $this->auth = null;
    }
}
