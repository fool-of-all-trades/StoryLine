<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\QuoteRepository;
use App\Models\Quote;
use DomainException;

final class QuoteService
{
    public function __construct(
        private QuoteRepository $repo = new QuoteRepository(),
        private string $localApiUrl = 'http://web/api/quotes/random' // quote api URL
    ) {}

    // gets the today's quote from the database or, if it doesn't exist, from the local API and saves it.
    public function getOrEnsureToday(): Quote
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        // check in db if today's quote exists
        if ($quote = $this->repo->getByDate($today)) {
            return $quote;
        }

        // fetch the quote from local API
        $data = $this->fetchLocalQuote();
        if (!$data || empty($data['sentence'])) {
            throw new DomainException('no_quote_available');
        }

        // save the quote to the db
        $this->repo->insertForDate(
            $today,
            $data['sentence'],
            $data['book'] ?? null,
            $data['author'] ?? null
        );

        // return today's quote
        return $this->repo->getByDate($today);
    }

    // returns the today's quote if it exists (without creating)
    public function getToday(): ?Quote
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        return $this->repo->getByDate($today);
    }

    // returns the quote for a specific date /api/quote?date=2025-10-19
    public function getByDate(string $ymd): ?Quote
    {
        return $this->repo->getByDate($ymd);
    }

    // helper to fetch quote from local API
    private function fetchLocalQuote(): ?array
    {
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $raw = @file_get_contents($this->localApiUrl, false, $ctx);
        if ($raw === false) return null;

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}
