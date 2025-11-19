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

    public function findByPublicId(string $uuid): ?User {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE public_id = :pid');
        $st->execute([':pid' => $uuid]);
        $row = $st->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByUsername(string $username): ?User {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE username = :u');
        $st->execute([':u'=>$username]);
        $row = $st->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User {
        $st = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $st->execute([':email' => $email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    public function create(string $username, string $email, string $passwordHash, Role $role = Role::User): User {

        $sql = "INSERT INTO users (username, email, password_hash, role)
                VALUES (:username, :email, :password_hash, :role)
                RETURNING *";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => $role->value,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return User::fromArray($row);
    }

    public function updateFavoriteQuote(int $userId, ?string $sentence, ?string $book, ?string $author): void
    {
        $sql = 'UPDATE users
                SET favorite_quote_sentence = :s,
                    favorite_quote_book     = :b,
                    favorite_quote_author   = :a
                WHERE id = :id';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':id' => $userId,
            ':s'  => $sentence,
            ':b'  => $book,
            ':a'  => $author,
        ]);
    }

    public function updateUsername(int $userId, ?string $username): void
    {
        $st = $this->pdo->prepare('UPDATE users SET username = :u WHERE id = :id');
        $st->execute([
            ':id' => $userId,
            ':u'  => $username
        ]);
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $st = $this->pdo->prepare('UPDATE users SET password_hash = :ph WHERE id = :id');
        $st->execute([
            ':id' => $userId,
            ':ph'  => $hash
        ]);
    }

    // HELPERS FOR ADMIN PANEL
    public function countTotal(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function timeSeries(int $months = 12, string $bucket = 'month'): array
    {
        $bucket = $bucket === 'day' ? 'day' : 'month';

        $sql = "SELECT date_trunc('$bucket', created_at)::date AS bucket, COUNT(*)::int AS cnt
                FROM users
                WHERE created_at >= (CURRENT_DATE - CAST(:interval AS interval))
                GROUP BY 1
                ORDER BY 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':interval' => $months . ' months',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}
