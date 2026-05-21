<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ProfileRepository
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= Database::get();
    }

    public function findByUserId(int $userId): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM user_profiles WHERE user_id = :id');
        $st->execute([':id' => $userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByPublicId(string $publicId): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM user_profiles WHERE public_id = :pid');
        $st->execute([':pid' => $publicId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findPrivateIdByPublicId(string $publicId): ?int
    {
        $st = $this->pdo->prepare('SELECT user_id FROM user_profiles WHERE public_id = :pid');
        $st->execute([':pid' => $publicId]);
        $id = $st->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    public function findByDisplayName(string $displayName): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM user_profiles WHERE LOWER(TRIM(display_name)) = LOWER(TRIM(:name))');
        $st->execute([':name' => $displayName]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createForUser(int $userId, string $displayName): array
    {
        $st = $this->pdo->prepare(
            'INSERT INTO user_profiles (user_id, display_name)
             VALUES (:id, :name)
             RETURNING *'
        );
        $st->execute([
            ':id' => $userId,
            ':name' => $displayName,
        ]);

        return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateDisplayName(int $userId, string $displayName): void
    {
        $st = $this->pdo->prepare(
            'UPDATE user_profiles SET display_name = :name WHERE user_id = :id'
        );
        $st->execute([
            ':id' => $userId,
            ':name' => $displayName,
        ]);
    }

    public function updateFavoriteQuote(int $userId, ?string $sentence, ?string $book, ?string $author): void
    {
        $st = $this->pdo->prepare(
            'UPDATE user_profiles
             SET favorite_quote_sentence = :sentence,
                 favorite_quote_book = :book,
                 favorite_quote_author = :author
             WHERE user_id = :id'
        );
        $st->execute([
            ':id' => $userId,
            ':sentence' => $sentence,
            ':book' => $book,
            ':author' => $author,
        ]);
    }

    public function updateAvatar(int $userId, ?string $avatarPath): void
    {
        $st = $this->pdo->prepare('UPDATE user_profiles SET avatar_path = :path WHERE user_id = :id');
        $st->execute([
            ':id' => $userId,
            ':path' => $avatarPath,
        ]);
    }

    public function findAvatarPathForUser(int $userId): ?string
    {
        $st = $this->pdo->prepare('SELECT avatar_path FROM user_profiles WHERE user_id = :id');
        $st->execute([':id' => $userId]);
        $path = $st->fetchColumn();

        return $path !== false ? (string)$path : null;
    }
}
