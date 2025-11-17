<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class User
{
    public function __construct(
        public int $id,
        public string $username,
        public string $passwordHash,
        public string $public_id,
        public string $email,
        public Role $role = Role::User,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {}

    public static function fromArray(array $row): self {
        return new self(
            id: (int)$row['id'],
            username: (string)$row['username'],
            passwordHash: (string)$row['password_hash'],
            public_id: $row['public_id'] ?? '',
            email: $row['email'] ?? null,
            role: Role::fromString((string)($row['role'] ?? 'user')),
            createdAt: new DateTimeImmutable((string)$row['created_at']),
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email, 
            'role' => $this->role->value,
            'created_at' => $this->createdAt->format('c'),
        ];
    }

    public function isAdmin(): bool { return $this->role === Role::Admin; }
    public function verifyPassword(string $plain): bool { return password_verify($plain, $this->passwordHash); }
}
