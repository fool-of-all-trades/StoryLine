<?php
declare(strict_types=1);

namespace App\Models;

final class Quote
{
    public function __construct(
        public int $id,
        public \DateTimeImmutable $date,
        public string $sentence,
        public ?string $book = null,
        public ?string $author = null,
        public ?string $sourceId = null,     // id from API
        public \DateTimeImmutable $fetchedAt = new \DateTimeImmutable()
    ) {}

    public static function fromArray(array $row): self {
        return new self(
            id: (int)$row['id'],
            date: new \DateTimeImmutable((string)$row['date']),
            sentence: (string)$row['sentence'],
            book: $row['source_book'] ?? null,
            author: $row['source_author'] ?? null,
            sourceId: $row['source_id'] ?? null,
            fetchedAt: new \DateTimeImmutable((string)$row['fetched_at'])
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'sentence' => $this->sentence,
            'source_book' => $this->book,
            'source_author' => $this->author,
            'source_id' => $this->sourceId,
            'fetched_at' => $this->fetchedAt->format('c'),
        ];
    }
}
