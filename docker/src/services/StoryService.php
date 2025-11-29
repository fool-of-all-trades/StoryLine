<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\StoryRepository;
use App\Models\Story;
use DomainException;
use Throwable;
use DateTimeImmutable;

final class StoryService
{
    public function __construct(
        private StoryRepository $storyRepository = new StoryRepository(),
        private ?QuoteService $quoteService = null, 
    ) {}

    /**
     * @return array<Story>
     */
    public function listByDate(string $dateYmd, string $sort='new', int $page=1, int $limit=10): array
    {
        $dateYmd = $dateYmd === 'today'
            ? (new DateTimeImmutable('today'))->format('Y-m-d')
            : (new DateTimeImmutable($dateYmd))->format('Y-m-d');

        $sort   = \in_array($sort, ['top','new'], true) ? $sort : 'new';
        $page   = max(1, (int)$page);
        $limit  = max(1, min(10, (int)$limit));
        $offset = ($page - 1) * $limit;

        return $this->storyRepository->listByDate($dateYmd, $sort, $limit, $offset);
    }

    public function countByDate(string $dateYmd): int
    {
        $dateYmd = $dateYmd === 'today'
            ? (new DateTimeImmutable('today'))->format('Y-m-d')
            : (new DateTimeImmutable($dateYmd))->format('Y-m-d');

        return $this->storyRepository->countOnDate($dateYmd);
    }

    public function getStoryById(int $id): ?Story
    {
        return $this->storyRepository->getById($id);
    }

    public function getStoryByPublicId(string $uuid): ?Story
    {
        return $this->storyRepository->getStoryByPublicId($uuid);
    }

    /**
     * Add a story for today's prompt.
     * @throws DomainException 'no_prompt_today'|'already_submitted_today'|'quote_missing'|'too_many_words'|'db_error'
     */
    public function addTodayStory(
        ?int $userId,
        ?string $title,
        string $content,
        bool $anonymous,
        ?string $guestName = null,
        ?string $deviceToken = null,
        ?string $ipHash = null
    ): int {

        $isAnonymous = (bool)$anonymous;

        // Guest nick logic:
        // - if user logged in -> ignore guestName, there won't be any
        // - if guest + anon -> null guestName (complete anon)
        // - if guest + not anon -> use guestName (trimmed to 60 chars)
        $finalGuestName = null;

        if ($userId === null) {
            $rawGuest = trim((string)$guestName);

            if (!$isAnonymous && $rawGuest !== '') {
                // guest + not anonymous -> use guestName
                $finalGuestName = mb_substr($rawGuest, 0, 60);
            }
            // guest + anonymous -> finalGuestName stays null
        } else {
            // logged in user -> ignore guestName
            $finalGuestName = null;
        }

        // Get or create today's quote prompt
        $this->quoteService ??= new QuoteService();
        $prompt = $this->quoteService->getOrEnsureToday();
        if (!$prompt) {
            throw new DomainException('no_prompt_today');
        }

        // Create story model
        $story = new Story(
            id: 0,
            quoteId: $prompt->id,
            userId: $userId,
            deviceToken: $deviceToken,
            ipHash: $ipHash,
            title: $title ?: null,
            content: $content,
            isAnonymous: !empty($anonymous) && $anonymous !== '0',
            guestName: $finalGuestName
        );

        // try to add the story in transaction to database
        try {
            return $this->storyRepository->create($story);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Quote is not included'))
                throw new DomainException('quote_missing');
            if (str_contains($msg, 'Too many words'))
                throw new DomainException('too_many_words');
            if (str_contains($msg, 'Only one story per prompt'))
                throw new DomainException('already_submitted_today');
            if (str_contains($msg, 'uq_story_user_per_day') || str_contains($msg, 'uq_story_device_per_day'))
                throw new DomainException('already_submitted_today');

            throw new DomainException('db_error: '.$msg);
        }
    }

    /**
     * Returns profile data for a given user.
     * @return array{items: array, total_stories: int, total_words: int}
     */
    public function getProfileDataForUser(int $userId): array
    {
        $totalWords = $this->storyRepository->totalWordsByUser($userId);
        $totalStories = $this->storyRepository->totalStoriesByUser($userId);

        return [
            'total_words' => $totalWords,
            'total_stories' => $totalStories,
        ];
    }

    public function getStoresForUser(int $userId, int $limit = 8, int $offset = 0): array
    {
        $stories = $this->storyRepository->listByUser($userId, $limit, $offset);

        return [
            'items' => array_map(fn(Story $s) => $s->toArray(), $stories),
        ];
    }
}
