<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\AccountDeletionRepository;
use App\Repository\Database;
use Delight\Auth\Role as DelightRole;
use DomainException;
use Throwable;

final class AccountDeletionService
{
    public function __construct(
        private AuthService $authService,
        private AccountDeletionRepository $repository = new AccountDeletionRepository()
    ) {}

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

            $user = $this->repository->lockUser($userId);
            if (!$user) {
                throw new DomainException('authentication_required');
            }

            $isAdmin = (((int)$user['roles_mask']) & DelightRole::ADMIN) === DelightRole::ADMIN;
            $expectedConfirmation = $isAdmin ? 'DELETE ADMIN ACCOUNT' : 'DELETE MY ACCOUNT';
            if ($confirmation !== $expectedConfirmation) {
                throw new DomainException('confirmation_required');
            }

            if ($isAdmin && $this->repository->countOtherAdmins($userId, DelightRole::ADMIN) === 0) {
                throw new DomainException('cannot_delete_last_admin');
            }

            $this->authService->logout();

            if ($mode === 'delete_all') {
                $this->repository->deleteFlowersMadeByUser($userId);
                $this->repository->deleteAllOwnedStories($userId);
            } else {
                $this->repository->deleteFlowersMadeByUser($userId);
                $this->repository->deletePrivateStories($userId);
                $this->repository->orphanPublicStories($userId);
            }

            $this->repository->cleanPackageAuxRows($userId);
            $this->repository->anonymizeAuditRows($userId);
            $this->repository->deleteProfile($userId);
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
}
