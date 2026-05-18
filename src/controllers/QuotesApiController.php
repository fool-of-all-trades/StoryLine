<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DateHelper;
use DateTimeImmutable;
use DomainException;

class QuotesApiController extends BaseController
{
    /** GET /api/quotes/random  → { sentence, author?, book? } */
    public function random(): void
    {
        // Load quotes from a local JSON file, error if file doesn't exist
        $path = getenv('QUOTES_LOCAL_PATH') ?: '/app/database/quotes.json';
        if (!is_file($path)) {
            $this->json(['error'=>'quotes_file_missing','path'=>$path], 500);
        }

        // Read and parse JSON data, error if file is empty
        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data) || empty($data)) {
            $this->json(['error'=>'quotes_empty'], 500);
        }

        // Quote choice is deterministic, based on the date
        $startDate = new DateTimeImmutable(DateHelper::publicStartYmd());

        try {
            $targetYmd = DateHelper::normalizePublicYmd((string)($_GET['date'] ?? 'today'));
            $targetDate = new DateTimeImmutable($targetYmd);
        } catch (DomainException $e) {
            $status = $e->getMessage() === 'date_out_of_range' ? 400 : 400;
            $error = $e->getMessage() === 'date_out_of_range' ? 'date_out_of_range' : 'invalid_date_format';
            $this->json(['error' => $error], $status);
        }

        $diffDays = (int)$startDate->diff($targetDate)->days;
        $index = $diffDays % count($data);

        // Get the quote at given index
        $row = $data[$index];

        // If by any means the quote object doesn't have a sentence, return error
        if (empty($row['sentence'])) {
            $this->json(['error'=>'invalid_quote_entry','index'=>$index], 500);
        }

        // Sends the quote data as JSON
        $this->json([
            'sentence' => (string)$row['sentence'],
            'author'   => $row['author'] ?? null,
            'book'     => $row['book'] ?? null,
            'index'    => $index,
            'source'   => 'date-hash-local'
        ]);
    }
}
