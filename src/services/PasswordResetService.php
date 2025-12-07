<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\PasswordResetRepository;
use DomainException;

final class PasswordResetService
{
    public function __construct(
        private PasswordResetRepository $passwordResetRepository = new PasswordResetRepository()
    ) {}

    public function createToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        return $this->passwordResetRepository->createToken($userId, $token);
    }

    public function validateToken(string $token): ?int
    {
        return $this->passwordResetRepository->validateToken($token);
    }

    public function deleteToken(string $token): void
    {
        $this->passwordResetRepository->deleteToken($token);
    }
}
