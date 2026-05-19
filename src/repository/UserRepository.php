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

    private function profileSelectSql(string $where): string
    {
        return <<<SQL
            SELECT
                u.id,
                up.display_name AS username,
                u.password AS password_hash,
                up.public_id,
                u.email,
                CASE WHEN (u.roles_mask & 1) = 1 THEN 'admin' ELSE 'user' END AS role,
                COALESCE(up.created_at, to_timestamp(u.registered)) AS created_at,
                up.favorite_quote_sentence,
                up.favorite_quote_book,
                up.favorite_quote_author,
                up.avatar_path
            FROM user_profiles up
            JOIN users u ON u.id = up.user_id
            WHERE $where
            LIMIT 1
        SQL;
    }

    public function findById(int $id): ?User {
        $st = $this->pdo->prepare($this->profileSelectSql('u.id = :id'));
        $st->execute([':id'=>$id]);
        $row = $st->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByPublicId(string $uuid): ?User {
        $st = $this->pdo->prepare($this->profileSelectSql('up.public_id = :pid'));
        $st->execute([':pid' => $uuid]);
        $row = $st->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findPrivateIdByPublicId(string $uuid): ?int {
        $st = $this->pdo->prepare('SELECT user_id FROM user_profiles WHERE public_id = :pid');
        $st->execute([':pid' => $uuid]);
        $row = $st->fetchColumn();

        return $row !== false ? (int)$row : null;
    }

    public function findByUsername(string $username): ?User {
        $st = $this->pdo->prepare($this->profileSelectSql('LOWER(TRIM(up.display_name)) = LOWER(TRIM(:u))'));
        $st->execute(['u' => $username]); 
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User {
        $st = $this->pdo->prepare($this->profileSelectSql('u.email = :email'));
        $st->execute([':email' => $email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    public function create(string $username, string $email, string $passwordHash, Role $role = Role::User): User {
        // It's just a one statement, so no need for transaction here, pdo treats it as such under the hood anyway

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
        $sql = 'UPDATE user_profiles
                SET favorite_quote_sentence = :s,
                    favorite_quote_book     = :b,
                    favorite_quote_author   = :a
                WHERE user_id = :id';

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
        $st = $this->pdo->prepare('UPDATE user_profiles SET display_name = :u WHERE user_id = :id');
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

    public function updateAvatar(int $userId, ?string $avatarPath): void
    {
        $st = $this->pdo->prepare('UPDATE user_profiles SET avatar_path = :p WHERE user_id = :id');
        $st->execute([
            ':id' => $userId,
            ':p'  => $avatarPath,
        ]);
    }

    public function findAvatarPathForUser(int $userId): string
    {
        $st = $this->pdo->prepare('SELECT avatar_path FROM user_profiles WHERE user_id = :id');
        $st->execute([':id' => $userId]);

        return (string)$st->fetchColumn();
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
