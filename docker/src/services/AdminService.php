<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\StoryRepository;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use PDO;

final class AdminService
{
    public function __construct(
        private FlowerRepository $flowerRepository = new FlowerRepository(),
        private StoryRepository $storyRepository  = new StoryRepository(),
        private UserRepository $userRepository  = new UserRepository(),
    ) {}

    public function metrics(string $date): array {
        return [
            'date'           => $date,
            'stories_today'  => $this->storyRepository->countOnDate($date),
            'stories_total'  => $this->storyRepository->countTotal(),
            'users_total'    => $this->userRepository->countTotal(),
            'flowers_total'  => $this->flowerRepository->countTotal(),
        ];
    }

    public function topStoryOfDay(string $date): ?array {
        return $this->storyRepository->topOfDay($date);
    }

    public function timeSeries(string $metric, int $months, string $bucket): array {
        return match ($metric) {
            'stories' => $this->storyRepository->timeSeries($months, $bucket),
            'flowers' => $this->flowerRepository->timeSeries($months, $bucket),
            default   => $this->userRepository->timeSeries($months, $bucket),
        };
    }
}
