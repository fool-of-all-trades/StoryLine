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
     * @return int new user id
     * @throws DomainException 'username_taken'|'weak_password'
     */
    public function register(string $username, string $password, Role $role = Role::User): int
    {
        $username = trim(mb_strtolower($username));
        if ($username === '' || mb_strlen($username) < 3) {
            throw new DomainException('weak_username');
        }
        if (mb_strlen($password) < 6) {
            throw new DomainException('weak_password');
        }

        // unique username check
        if ($this->userRepository->findByUsername($username)) {
            throw new DomainException('username_taken');
        }

        return $this->userRepository->create($username, $password, $role);
    }

    public function findById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function findByPublicId(string $uuid): ?User {
        return $this->userRepository->findByPublicId($uuid);
    }
}
