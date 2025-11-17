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
    public function login(string $username, string $password): array
    {
        $username = trim(mb_strtolower($username));
        if ($username === '' || $password === '') {
            throw new DomainException('bad_credentials');
        }

        $user = $this->userRepository->findByUsername($username);
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

        if (strlen($password) < 8) {
            throw new DomainException('Password must be at least 8 characters long');
        }

        if ($this->userRepository->findByUsername($username)) {
            throw new DomainException('Username is already taken');
        }
        if ($this->userRepository->findByEmail($email)) {
            throw new DomainException('Email is already in use');
        }

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
}
