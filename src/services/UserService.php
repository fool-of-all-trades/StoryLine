<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\UserRepository;
use App\Models\User;
use DomainException;
use PDOException;

final class UserService
{
    public function __construct(
        private UserRepository $userRepository = new UserRepository()
    ) {}

    public function findById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function findByPublicId(string $uuid): ?User {
        return $this->userRepository->findByPublicId($uuid);
    }

    public function findPrivateIdByPublicId(string $uuid): ?int {
        return $this->userRepository->findPrivateIdByPublicId($uuid);
    }

    public function findByEmail(string $email): ?User {
        return $this->userRepository->findByEmail($email);
    }

    public function setFavoriteQuote(int $userId, ?string $sentence, ?string $book, ?string $author): void
    {
        $sentence = trim((string)$sentence);
        $book = trim((string)$book);
        $author = trim((string)$author);

        if ($sentence === '') {
            throw new DomainException('favorite_quote_sentence_required');
        }

        if (mb_strlen($sentence) > 500) {
            throw new DomainException('favorite_quote_sentence_too_long');
        }

        if ($book !== '' && mb_strlen($book) > 100) {
            throw new DomainException('favorite_quote_book_too_long');
        }

        if ($author !== '' && mb_strlen($author) > 100) {
            throw new DomainException('favorite_quote_author_too_long');
        }

        $sentence = $sentence !== '' ? $sentence : null;
        $book = $book !== '' ? $book : null;
        $author = $author !== '' ? $author : null;

        $this->userRepository->updateFavoriteQuote($userId, $sentence, $book, $author);
    }
    public function changeUsername(int $userId, ?string $username): void
    {
        $username = trim((string)$username);

        if ($username === '') {
            throw new DomainException('username_required');
        }

        if (mb_strlen($username) > 40) {
            throw new DomainException('username_too_long');
        }

        if (!preg_match('/^[A-Za-z0-9_.]+$/u', $username)) {
            throw new DomainException('username_invalid_chars');
        }

        $existingUser = $this->userRepository->findByUsername($username);
        if ($existingUser && $existingUser->id !== $userId) {
            throw new DomainException('username_taken');
        }

        $username = $username !== '' ? $username : null;

        try {
            $this->userRepository->updateUsername($userId, $username);
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                throw new DomainException('username_taken');
            }

            throw $e;
        }
    }

    public function findAvatarPathForUser(int $userId): ?string
    {
        $path = $this->userRepository->findAvatarPathForUser($userId);

        return $path !== '' ? $path : null;
    }

    public function updateAvatarPath(int $userId, ?string $path): void
    {
        $this->userRepository->updateAvatar($userId, $path);
    }

    public function deleteAvatarFileIfCustom(?string $avatarPath): void
    {
        if (!$avatarPath || $avatarPath === 'default-avatar.jpg' || $avatarPath === '/uploads/avatars/default-avatar.jpg') {
            return;
        }

        $fullPath = realpath(__DIR__ . '/../../public' . $avatarPath);
        $allowedDir = realpath(__DIR__ . '/../../public/uploads/avatars');
        $allowedPrefix = $allowedDir ? rtrim($allowedDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : null;

        if ($fullPath && $allowedPrefix && str_starts_with($fullPath, $allowedPrefix) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    public function changeAvatar(int $userId, ?string $path): void
    {
        if ($path === null) {
            $path = 'default-avatar.jpg';
        }

        $oldAvatarPath = $this->findAvatarPathForUser($userId);
        try {
            $this->updateAvatarPath($userId, $path);
        } catch (\Throwable $e) {
            $this->deleteAvatarFileIfCustom($path);
            throw $e;
        }

        $this->deleteAvatarFileIfCustom($oldAvatarPath);
    }
}
