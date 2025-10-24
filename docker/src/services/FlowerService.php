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
     * Adds or removes a flower for a story by a user.
     * @return array{flowered:bool,count:int}
     * @throws DomainException 'story_not_found'
     */
    public function toggleFlower(int $storyId, int $userId): array
    {
        $story = $this->storyRepository->getById($storyId);
        if (!$story) throw new DomainException('story_not_found');

        return $this->flowerRepository->toggle($storyId, $userId);
    }

    /** cheks if the user has already given a flower to the story */
    public function hasFlower(int $storyId, int $userId): bool
    {
        return $this->flowerRepository->hasFlower($storyId, $userId);
    }

    /** Counts the story's flowers */
    public function countForStory(int $storyId): int
    {
        return $this->flowerRepository->countForStory($storyId);
    }
}
