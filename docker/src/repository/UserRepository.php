<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\User;
use App\Models\Role;
use PDO;

final class UserRepository
{
    public function __construct(private ?PDO $pdo = null) {
        $this->pdo ??= Database::get();
    }

    public function findById(int $id): ?User {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $st->execute([':id'=>$id]);
        $row = $st->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByUsername(string $username): ?User {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE username = :u');
        $st->execute([':u'=>$username]);
        $row = $st->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function create(string $username, string $plainPassword, Role $role = Role::User): int {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $st = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, role) VALUES (:u,:h,:r) RETURNING id'
        );
        $st->execute([':u'=>$username, ':h'=>$hash, ':r'=>$role->value]);
        return (int)$st->fetchColumn();
    }

    // HELPERS FOR ADMIN PANEL
    public function countTotal(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function timeSeries(int $months=12, string $bucket='month'): array {
        $bucket = $bucket === 'day' ? 'day' : 'month';
        $sql = "SELECT date_trunc('$bucket', created_at)::date AS bucket, COUNT(*)::int AS cnt
                FROM users
                WHERE created_at >= (CURRENT_DATE - INTERVAL :months)
                GROUP BY 1 ORDER BY 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':months' => $months.' months']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
