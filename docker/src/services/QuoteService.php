<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\QuoteRepository;
use App\Models\Quote;
use DomainException;
use DateTimeImmutable;


final class QuoteService
{
    public function __construct(
        private QuoteRepository $repo = new QuoteRepository(),
        private string $localApiUrl = 'http://web/api/quotes/random' // quote api URL
    ) {}

    /**  
     * Gets the quote for a given date (Y-m-d) from the database or, 
     * if it doesn't exist, from the local API and saves it. 
     * */

    public function getOrEnsureForDate(string $ymd): Quote
    {
        // date normalization
        try {
            $date = (new DateTimeImmutable($ymd))->format('Y-m-d');
        } catch (\Exception $e) {
            // fallback – if date is invalid, use today
            $date = (new DateTimeImmutable('today'))->format('Y-m-d');
        }

        $existing = $this->repo->getByDate($date);
        if ($existing !== null) {
            return $existing;
        }

        // if not in db, fetch from local API
        $quoteFromApi = $this->fetchLocalQuote();
        if (!$quoteFromApi || empty($quoteFromApi['sentence'])) {
            throw new DomainException('no_quote_available');
        }

        // save to database
        $this->repo->insertForDate(
            $date,
            $quoteFromApi['sentence'],
            $quoteFromApi['book'] ?? null,
            $quoteFromApi['author'] ?? null
        );

        return $this->repo->getByDate($date);
    }

    /** gets today's quote from the database or, if it doesn't exist, from the local API and saves it. */
    public function getOrEnsureToday(): Quote
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        return $this->getOrEnsureForDate($today);
    }

    /** returns the today's quote if it exists (without creating) */ 
    public function getToday(): ?Quote
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        return $this->repo->getByDate($today);
    }

    /** returns the quote for a specific date /api/quote?date=2025-10-19 */
    public function getByDate(string $ymd): ?Quote
    {
        return $this->repo->getByDate($ymd);
    }

    /** helper to fetch quote from local API */ 
    private function fetchLocalQuote(): ?array
    {
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        // later add error handling here, for now, just ignore errors
        $raw = @file_get_contents($this->localApiUrl, false, $ctx);
        if ($raw === false) return null;

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}
