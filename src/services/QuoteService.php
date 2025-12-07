<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\QuoteRepository;
use App\Models\Quote;
use App\Helpers\DateHelper;

use DomainException;
use DateTimeImmutable;


final class QuoteService
{
    public function __construct(
        private QuoteRepository $repo = new QuoteRepository(),
        private string $localApiUrl = 'http://web/api/quotes/random' // quote api URL
    ) {}

     /**
      * fetches quote for a given date (Y-m-d) from the local API
      */
     private function fetchLocalQuoteForDate(string $ymd): ?array
    {
        $url = $this->localApiUrl . '?date=' . rawurlencode($ymd);
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            error_log('[QuoteService] fetchLocalQuoteForDate http_fail url=' . $url);
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**  
    * Gets the quote for a given date (Y-m-d) from the database or, 
    * if it doesn't exist, from the local API and saves it. 
    * */
    public function getOrEnsureForDate(string $dateInput): Quote
    {
        $ymd = DateHelper::normalizeYmd($dateInput, false);

        // check if quote for a given date exists in database and return it
        $existing = $this->repo->getByDate($ymd);
        if ($existing !== null) {
            error_log('[QuoteService] quote_from_db ymd=' . $ymd . ' hash=' . sha1($existing->sentence ?? ''));
            return $existing;
        }

        // if it doesn't exist in db then fetch from local API
        $q = $this->fetchLocalQuoteForDate($ymd);
        if (!$q || empty($q['sentence'])) {
            throw new DomainException('no_quote_available');
        }

        // insert into database
        $this->repo->insertForDate(
            $ymd,
            $q['sentence'],
            $q['book'] ?? null,
            $q['author'] ?? null
        );

        // retrieve saved quote from database and return if not null
        $saved = $this->repo->getByDate($ymd);

        if ($saved === null) {
            throw new DomainException('quote_insert_failed');
        }

        return $saved;
    }

    /** gets today's quote from the database or, if it doesn't exist, from the local API and saves it. */
    public function getOrEnsureToday(): Quote
    {
        return $this->getOrEnsureForDate('today');
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
}
