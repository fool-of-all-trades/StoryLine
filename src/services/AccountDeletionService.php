<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\Database;
use Delight\Auth\Role as DelightRole;
use DomainException;
use PDO;
use Throwable;

final class AccountDeletionService
{
    public function __construct(
        private AuthService $authService,
        private ?PDO $pdo = null
    ) {
        $this->pdo ??= Database::get();
    }

    /**
     * @throws DomainException
     */
    public function deleteAccount(int $userId, string $currentPassword, string $mode, string $confirmation): void
    {
        $mode = trim($mode);
        $confirmation = trim($confirmation);

        if (!in_array($mode, ['delete_all', 'orphan_public'], true)) {
            throw new DomainException('invalid_delete_mode');
        }

        $this->authService->reconfirmPassword($currentPassword);

        try {
            Database::begin();

            $user = $this->lockUser($userId);
            if (!$user) {
                throw new DomainException('authentication_required');
            }

            $isAdmin = (((int)$user['roles_mask']) & DelightRole::ADMIN) === DelightRole::ADMIN;
            $expectedConfirmation = $isAdmin ? 'DELETE ADMIN ACCOUNT' : 'DELETE MY ACCOUNT';
            if ($confirmation !== $expectedConfirmation) {
                throw new DomainException('confirmation_required');
            }

            if ($isAdmin && $this->countOtherAdmins($userId) === 0) {
                throw new DomainException('cannot_delete_last_admin');
            }

            $this->authService->logout();

            if ($mode === 'delete_all') {
                $this->deleteAllOwnedStories($userId);
            } else {
                $this->orphanPublicStories($userId);
            }

            $this->cleanPackageRows($userId);
            $this->anonymizeAuditRows($userId);
            $this->deleteProfile($userId);
            $this->authService->deleteUserById($userId);

            Database::commit();
        } catch (DomainException $e) {
            Database::rollBack();
            throw $e;
        } catch (Throwable $e) {
            Database::rollBack();
            error_log('[AccountDeletionService] delete_account_failed: ' . get_class($e));
            throw new DomainException('internal_error');
        }

        $this->authService->destroySession();
    }

    private function lockUser(int $userId): ?array
    {
        $st = $this->pdo->prepare('SELECT id, roles_mask FROM users WHERE id = :id FOR UPDATE');
        $st->execute([':id' => $userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function countOtherAdmins(int $userId): int
    {
        $st = $this->pdo->prepare(
            'SELECT COUNT(*)::int
             FROM users
             WHERE id <> :id
               AND (roles_mask & :admin_role) = :admin_role'
        );
        $st->execute([
            ':id' => $userId,
            ':admin_role' => DelightRole::ADMIN,
        ]);

        return (int)$st->fetchColumn();
    }

    private function deleteAllOwnedStories(int $userId): void
    {
        $this->execute('DELETE FROM flowers WHERE user_id = :id', $userId);
        $this->execute('DELETE FROM stories WHERE user_id = :id', $userId);
    }

    private function orphanPublicStories(int $userId): void
    {
        $this->execute('DELETE FROM flowers WHERE user_id = :id', $userId);
        $this->execute(
            "DELETE FROM stories WHERE user_id = :id AND visibility = 'private'",
            $userId
        );
        $this->execute(
            "UPDATE stories
             SET user_id = NULL,
                 is_anonymous = TRUE
             WHERE user_id = :id
               AND visibility = 'public'",
            $userId
        );
    }

    private function cleanPackageRows(int $userId): void
    {
        $this->execute('DELETE FROM users_confirmations WHERE user_id = :id', $userId);
        $this->execute('DELETE FROM users_resets WHERE "user" = :id', $userId);
        $this->execute('DELETE FROM users_remembered WHERE "user" = :id', $userId);
        $this->execute('DELETE FROM users_2fa WHERE user_id = :id', $userId);
        $this->execute('DELETE FROM users_otps WHERE user_id = :id', $userId);
    }

    private function anonymizeAuditRows(int $userId): void
    {
        $st = $this->pdo->prepare(
            'UPDATE users_audit_log
             SET user_id = NULL,
                 admin_id = NULL,
                 details_json = NULL
             WHERE user_id = :id
                OR admin_id = :id'
        );
        $st->execute([':id' => $userId]);
    }

    private function deleteProfile(int $userId): void
    {
        $this->execute('DELETE FROM user_profiles WHERE user_id = :id', $userId);
    }

    private function execute(string $sql, int $userId): void
    {
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $userId]);
    }
}
