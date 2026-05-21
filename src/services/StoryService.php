<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\StoryRepository;
use App\Models\Story;
use App\Helpers\DateHelper;

use DomainException;
use Throwable;
use DateTimeImmutable;

final class StoryService
{
    public function __construct(
        private StoryRepository $storyRepository = new StoryRepository(),
        private ?QuoteService $quoteService = new QuoteService(), 
    ) {}

    /**
     * @return array<Story>
     */
    public function listByDate(string $dateYmd, string $sort='new', int $page=1, int $limit=10): array
    {
        $dateYmd = DateHelper::normalizePublicYmd($dateYmd);

        $sort   = \in_array($sort, ['top','new'], true) ? $sort : 'new';
        $page   = max(1, (int)$page);
        $limit  = max(1, min(10, (int)$limit));
        $offset = ($page - 1) * $limit;

        return $this->storyRepository->listByDate($dateYmd, $sort, $limit, $offset);
    }

    public function countByDate(string $dateYmd): int
    {
        $dateYmd = DateHelper::normalizePublicYmd($dateYmd);

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

    public function getStoryByPublicIdForViewer(string $uuid, ?int $viewerUserId = null): ?Story
    {
        return $this->storyRepository->getStoryByPublicIdForViewer($uuid, $viewerUserId);
    }

    public function changeVisibilityForOwner(int $userId, string $uuid, string $mode): array
    {
        $mode = strtolower(trim($mode));
        [$visibility, $isAnonymous] = match ($mode) {
            'public' => ['public', false],
            'anonymous' => ['public', true],
            'private' => ['private', false],
            default => throw new DomainException('invalid_visibility'),
        };

        $story = $this->storyRepository->findOwnershipByPublicId($uuid);
        if (!$story) {
            throw new DomainException('story_not_found');
        }

        if (empty($story['user_id']) || (int)$story['user_id'] !== $userId) {
            throw new DomainException('forbidden');
        }

        try {
            $updated = $this->storyRepository->updateVisibilityForOwner($uuid, $userId, $visibility, $isAnonymous);
        } catch (Throwable $e) {
            error_log('[StoryService] update_visibility_failed: ' . get_class($e));
            throw new DomainException('internal_error');
        }

        if (!$updated) {
            throw new DomainException('story_not_found');
        }

        return [
            'mode' => $mode,
            'visibility' => $visibility,
            'is_anonymous' => $isAnonymous,
        ];
    }

    public function deleteOwnStory(int $userId, string $uuid): void
    {
        $story = $this->storyRepository->findOwnershipByPublicId($uuid);
        if (!$story) {
            throw new DomainException('story_not_found');
        }

        if (empty($story['user_id']) || (int)$story['user_id'] !== $userId) {
            throw new DomainException('forbidden');
        }

        try {
            $deleted = $this->storyRepository->deleteForOwner($uuid, $userId);
        } catch (Throwable $e) {
            error_log('[StoryService] delete_story_failed: ' . get_class($e));
            throw new DomainException('internal_error');
        }

        if (!$deleted) {
            throw new DomainException('story_not_found');
        }
    }

    /**
     * Add a story for today's prompt.
     * @throws DomainException 'no_prompt_today'|'already_submitted_today'|'quote_missing'|'too_many_words'|'db_error'
     */
    public function addTodayStory(
        int $userId,
        ?string $title,
        string $content,
        bool $anonymous,
        string $visibility = 'public'
    ): string {
        if (!in_array($visibility, ['public', 'private'], true)) {
            throw new DomainException('invalid_visibility');
        }
        if ($visibility === 'private') {
            $anonymous = false;
        }

        // Get or create today's quote prompt
        $prompt = $this->quoteService->getOrEnsureToday();
        if (!$prompt) {
            throw new DomainException('no_prompt_today');
        }

        // Create story model
        $story = new Story(
            id: 0,
            quoteId: $prompt->id,
            userId: $userId,
            title: $title ?: null,
            content: $content,
            isAnonymous: !empty($anonymous) && $anonymous !== '0',
            visibility: $visibility
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
            if(str_contains($msg, 'Content must include the prompt sentence'))
                throw new DomainException('prompt_missing_in_content');
            if (str_contains($msg, 'Only one story per prompt'))
                throw new DomainException('already_submitted_today');
            if (str_contains($msg, 'uq_story_user_per_day'))
                throw new DomainException('already_submitted_today');
            if (str_contains($msg, 'Story owner is required'))
                throw new DomainException('authentication_required');

            error_log('[StoryService] add_today_story_failed: ' . $msg);
            throw new DomainException('story_create_failed');
        }
    }

    /**
     * Returns profile data for a given user.
     * @return array{items: array, total_stories: int, total_words: int}
     */
    public function getProfileDataForUser(int $userId, bool $includePrivate = false): array
    {
        $totalWords = $this->storyRepository->totalWordsByUser($userId, $includePrivate);
        $totalStories = $this->storyRepository->totalStoriesByUser($userId, $includePrivate);

        return [
            'total_words' => $totalWords,
            'total_stories' => $totalStories,
        ];
    }

    public function getStoresForUser(int $userId, int $limit = 8, int $offset = 0, bool $includePrivate = false): array
    {
        $stories = $this->storyRepository->listByUser($userId, $limit, $offset, $includePrivate);

        return [
            'items' => array_map(fn(Story $s) => $s->toArray(), $stories),
        ];
    }
}
