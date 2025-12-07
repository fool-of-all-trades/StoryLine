<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\StoryRepository;
use App\Repository\UserRepository;
use App\Services\QuoteService;

use DateTimeImmutable;
use DomainException;

final class AdminService
{
    public function __construct(
        private UserRepository  $userRepository  = new UserRepository(),
        private StoryRepository $storyRepository = new StoryRepository(),
        private QuoteService $quoteService = new QuoteService(),
    ) {}

    /**
     * Returns dashboard data
     * for a given date (default: today).
     */
    public function getDashboardData(string $dateInput = 'today'): array
    {
        $date = $this->normalizeDate($dateInput);
        $ymd  = $date->format('Y-m-d');

        // Ensure quote for the date, even if no one has visited the site at the given date
        $quote = null;
        try {
            $quote = $this->quoteService->getOrEnsureForDate($ymd);
        } catch (DomainException $e) {
            if ($e->getMessage() === 'no_quote_available') {
                $quote = null;
            } else {
                throw $e;
            }
        }

        $storiesForDate = $this->storyRepository->countOnDate($ymd);
        $storiesTotal   = $this->storyRepository->countTotal();
        $topStory       = $this->storyRepository->topOfDay($ymd);
        $usersTotal     = $this->userRepository->countTotal();
        $storiesSeries  = $this->storyRepository->timeSeries(6, 'day');
        $usersSeries    = $this->userRepository->timeSeries(12, 'month');

        return [
            'date'           => $ymd,
            'quote'          => $quote,
            'topStory'       => $topStory,
            'storiesForDate' => $storiesForDate,
            'storiesTotal'   => $storiesTotal,
            'usersTotal'     => $usersTotal,
            'storiesSeries'  => $storiesSeries,
            'usersSeries'    => $usersSeries,
        ];
    }


    private function normalizeDate(string $input): DateTimeImmutable
    {
        $input = trim($input);
        if ($input === '' || $input === 'today') {
            return new DateTimeImmutable('today');
        }
        if ($input === 'yesterday') {
            return new DateTimeImmutable('yesterday');
        }

        try {
            return new DateTimeImmutable($input);
        } catch (\Exception $e) {
            // Fallback, in case of invalid date
            return new DateTimeImmutable('today');
        }
    }
}