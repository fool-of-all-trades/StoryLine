<?php
declare(strict_types=1);

namespace App\Models;

final class Flower
{
    public function __construct(
        public int $id,
        public int $storyId,
        public ?int $userId,                 // null = anonimowy dawca
        public ?string $deviceToken,         // identyfikator anona (cookie)
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable()
    ) {}

    public static function fromArray(array $row): self {
        return new self(
            id: (int)$row['id'],
            storyId: (int)$row['story_id'],
            userId: isset($row['user_id']) ? (int)$row['user_id'] : null,
            deviceToken: $row['device_token'] ?? null,
            createdAt: new \DateTimeImmutable((string)$row['created_at'])
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'story_id' => $this->storyId,
            'user_id' => $this->userId,
            'device_token' => $this->deviceToken,
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
