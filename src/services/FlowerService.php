<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\FlowerRepository;
use App\Repository\StoryRepository;
use DomainException;

final class flowerService
{
    public function __construct(
        private FlowerRepository $flowerRepository = new FlowerRepository(),
        private StoryRepository $storyRepository  = new StoryRepository(),
    ) {}

    /**
     * Toggle flower by story public UUID.
     * @return array{flowered:bool,count:int}
     * @throws DomainException 'story_not_found'
     */
    public function toggleFlower(string $storyId, int $userId): array
    {
        $story = $this->storyRepository->getStoryByPublicId($storyId);
        if (!$story) throw new DomainException('story_not_found');

        return $this->flowerRepository->toggle((int)$story->id, $userId);
    }

    /** cheks if the user has already given a flower to the story */
    public function hasFlower(string $storyId, int $userId): bool
    {
        $story = $this->storyRepository->getStoryByPublicId($storyId);
        if (!$story) throw new DomainException('story_not_found');

        return $this->flowerRepository->hasFlower((int)$story->id, $userId);
    }

    /** Counts the story's flowers */
    public function countForStory(string $storyId): int
    {
        $story = $this->storyRepository->getStoryByPublicId($storyId);
        if (!$story) throw new DomainException('story_not_found');

        return $this->flowerRepository->countForStory((int)$story->id);
    }
}
