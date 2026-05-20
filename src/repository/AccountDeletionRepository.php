<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class AccountDeletionRepository
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= Database::get();
    }

    public function lockUser(int $userId): ?array
    {
        $st = $this->pdo->prepare('SELECT id, roles_mask FROM users WHERE id = :id FOR UPDATE');
        $st->execute([':id' => $userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function countOtherAdmins(int $userId, int $adminRole): int
    {
        $st = $this->pdo->prepare(
            'SELECT COUNT(*)::int
             FROM users
             WHERE id <> :id
               AND (roles_mask & :admin_role) = :admin_role'
        );
        $st->execute([
            ':id' => $userId,
            ':admin_role' => $adminRole,
        ]);

        return (int)$st->fetchColumn();
    }

    public function deleteFlowersMadeByUser(int $userId): void
    {
        $this->execute('DELETE FROM flowers WHERE user_id = :id', $userId);
    }

    public function deleteAllOwnedStories(int $userId): void
    {
        $this->execute('DELETE FROM stories WHERE user_id = :id', $userId);
    }

    public function deletePrivateStories(int $userId): void
    {
        $this->execute(
            "DELETE FROM stories WHERE user_id = :id AND visibility = 'private'",
            $userId
        );
    }

    public function orphanPublicStories(int $userId): void
    {
        $this->execute(
            "UPDATE stories
             SET user_id = NULL,
                 is_anonymous = TRUE
             WHERE user_id = :id
               AND visibility = 'public'",
            $userId
        );
    }

    public function cleanPackageAuxRows(int $userId): void
    {
        $this->execute('DELETE FROM users_confirmations WHERE user_id = :id', $userId);
        $this->execute('DELETE FROM users_resets WHERE "user" = :id', $userId);
        $this->execute('DELETE FROM users_remembered WHERE "user" = :id', $userId);
        $this->execute('DELETE FROM users_2fa WHERE user_id = :id', $userId);
        $this->execute('DELETE FROM users_otps WHERE user_id = :id', $userId);
    }

    public function anonymizeAuditRows(int $userId): void
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

    public function deleteProfile(int $userId): void
    {
        $this->execute('DELETE FROM user_profiles WHERE user_id = :id', $userId);
    }

    private function execute(string $sql, int $userId): void
    {
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $userId]);
    }
}
