<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\UserRepository;
use App\Models\Role;
use App\Models\User;
use DomainException;

final class UserService
{
    public function __construct(
        private UserRepository $userRepository = new UserRepository()
    ) {}

    /**
     * @return array{id:int,username:string,role:string,created_at:string}
     * @throws DomainException 'bad_credentials'
     */
    public function login(string $identifier, string $password): array
    {
        $identifier = trim(mb_strtolower($identifier));
        if ($identifier === '' || $password === '') {
            throw new DomainException('bad_credentials');
        }

        // Does it look like an email (contains '@')?
        if (str_contains($identifier, '@')) {
            $user = $this->userRepository->findByEmail($identifier);
        } else {
            $user = $this->userRepository->findByUsername($identifier);
        }

        if (!$user || !$user->verifyPassword($password)) {
            throw new DomainException('bad_credentials');
        }

        // session will be handled by controller (here we just return the payload for session)
        return $user->toArray();
    }

    /**
     * @return User
     * @throws DomainException 'invalid_username'|'invalid_email'|'password_mismatch'|'weak_password'|'username_taken'|'email_taken'
     */
    public function register(string $username, string $email, string $password, string $passwordConfirm): User
    {
        $username = trim($username);
        $email = trim($email);

        if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
            throw new DomainException('Invalid username format');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Invalid email address');
        }

        if ($password !== $passwordConfirm) {
            throw new DomainException('Passwords do not match');
        }

        if ($this->userRepository->findByUsername($username)) {
            throw new DomainException('Username is already taken');
        }
        
        if ($this->userRepository->findByEmail($email)) {
            throw new DomainException('Email is already in use');
        }

        // Validate password strength
        $this->assertStrongPassword($password);

        // Password hashing, default for now is bcrypt, but if it changes in the future, then it will update to the stronger one
        $hash = password_hash($password, PASSWORD_DEFAULT);

        return $this->userRepository->create($username, $email, $hash, Role::User);
    }

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

        if (!preg_match('/^[A-Za-z0-9_]+$/u', $username)) {
            throw new DomainException('username_invalid_chars');
        }

        if($this->userRepository->findByUsername($username)) {
            throw new DomainException('username_taken');
        }

        $username = $username !== '' ? $username : null;

        $this->userRepository->updateUsername($userId, $username);
    }

    public function changePassword(int $userId, ?string $password): void
    {
        $password = trim((string)$password);

        if ($password === '') {
            throw new DomainException('password_required');
        }

        // validate password strength
        $this->assertStrongPassword($password);

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new DomainException('password_hash_error');
        }

        $this->userRepository->updatePassword($userId, $hash);
    }

    // helpter method to validate password strength
    private function assertStrongPassword(string $password): void
    {
        $password = trim($password);

        if ($password === '') {
            throw new DomainException('password_required');
        }

        if (mb_strlen($password) < 8) {
            throw new DomainException('password_too_short');
        }

        // at least one lowercase, one uppercase, one digit, one special character
        $hasLower   = preg_match('/[a-z]/', $password);
        $hasUpper   = preg_match('/[A-Z]/', $password);
        $hasDigit   = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

        if (!$hasLower || !$hasUpper || !$hasDigit || !$hasSpecial) {
            throw new DomainException('password_too_weak');
        }
    }

    public function changeAvatar(int $userId, ?string $path): void
    {
        if ($path === null) {
            $path = 'default-avatar.jpg';
        }
        else{
            $oldAvatarPath = $this->userRepository->findAvatarPathForUser($userId);
            if ($oldAvatarPath !== 'default-avatar.jpg' && file_exists(__DIR__ .'/../../public' . $oldAvatarPath)) {
                unlink(__DIR__ .'/../../public' . $oldAvatarPath);
            }
        }

        $this->userRepository->updateAvatar($userId, $path);
    }

}
