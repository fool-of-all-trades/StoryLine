<?php
declare(strict_types=1);

namespace App\Models;
use DateTimeImmutable;

final class Story
{
    public function __construct(
        public int $id,
        public int $quoteId,
        public ?int $userId,                     // null = anonimuous
        public ?string $deviceToken,             // anon user identification
        public ?string $ipHash,                  // hash(IP+salt) anty-spam
        public ?string $title,
        public string $content,
        public bool $isAnonymous = false,
        public int $wordCount = 0,
        public ?string $username = null,
        public ?string $user_public_id = null,
        public ?string $story_public_id = null,
        public ?int $flower_count = null,
        public ?string $prompt_date = null,
        public ?string $prompt_sentence = null,
        public DateTimeImmutable $createdAt = new DateTimeImmutable()
    ) {
        if ($this->wordCount === 0) {
            $this->wordCount = self::countWords($this->content);
        }
    }

    public static function fromArray(array $row): self {
        return new self(
            id: (int)$row['id'],
            quoteId: (int)$row['prompt_id'],
            userId: isset($row['user_id']) ? (int)$row['user_id'] : null,
            deviceToken: $row['device_token'] ?? null,
            ipHash: $row['ip_hash'] ?? null,
            title: $row['title'] ?? null,
            content: (string)$row['content'],
            isAnonymous: (bool)$row['is_anonymous'],
            wordCount: (int)($row['word_count'] ?? 0),
            createdAt: new DateTimeImmutable((string)$row['created_at']),
            username: $row['username'] ?? null, // can be anonymous, like da hackers
            user_public_id: $row['user_public_id'] ?? null,
            story_public_id: $row['story_public_id'] ?? null,
            flower_count: $row['flower_count'] ?? 0, 
            prompt_date: $row['prompt_date'] ?? null, 
            prompt_sentence: $row['prompt_sentence'] ?? null, 
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'prompt_id' => $this->quoteId,
            'user_id' => $this->userId,
            'device_token' => $this->deviceToken,
            'ip_hash' => $this->ipHash,
            'title' => $this->title,
            'content' => $this->content,
            'is_anonymous' => $this->isAnonymous,
            'word_count' => $this->wordCount,
            'created_at' => $this->createdAt->format('c'),
            'username' => $this->username,
            'user_public_id' => $this->user_public_id,
            'story_public_id' => $this->story_public_id,
            'flower_count' => (int)($this->flower_count ?? 0),
            'prompt_date' => $this->prompt_date,
            'prompt_sentence' => $this->prompt_sentence,
        ];
    }

    // Will count words (unicode)
    public static function countWords(string $text): int {
        $text = trim($text);
        if ($text === '') return 0;
        return preg_match_all('/\S+/u', $text, $m) ?: 0;
    }

    // Does the story contain the exact quote? 
    public function containsExactSentence(string $sentence, bool $caseSensitive = false): bool {
        $norm = static fn(string $s) => preg_replace('/\s+/u', ' ', trim($s));
        $hay = $norm($this->content);
        $needle = $norm($sentence);
        if (!$caseSensitive) { $hay = mb_strtolower($hay); $needle = mb_strtolower($needle); }
        return mb_strpos($hay, $needle) !== false;
    }

    // Short preview for lists
    public function preview(int $chars = 160): string {
        $plain = preg_replace('/\s+/u', ' ', trim($this->content));
        return mb_strlen($plain) > $chars ? mb_substr($plain, 0, $chars) . '…' : $plain;
    }
}
