<?php
declare(strict_types=1);

namespace App\Repository;
use App\Repository\Database;
use PDO;

final class PasswordResetRepository
{
    public function __construct(private ?PDO $pdo = null) {
        $this->pdo ??= Database::get();
    }

    public function createToken(int $userId, string $token): string
    {
        $st = $this->pdo->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at)
            VALUES (:u, :t, NOW() + INTERVAL '1 hour')
        ");
        $st->execute([':u' => $userId, ':t' => $token]);

        return $token;
    }

    public function validateToken(string $token): ?int
    {
        $st = $this->pdo->prepare("
            SELECT user_id FROM password_reset_tokens
            WHERE token = :t AND expires_at > NOW()
        ");
        $st->execute([':t' => $token]);
        $row = $st->fetch();

        return $row ? (int)$row['user_id'] : null;
    }

    public function deleteToken(string $token): void
    {
        $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE token = :t")
                ->execute([':t' => $token]);
    }

}
