<?php
declare(strict_types=1);

namespace App\Controllers;

final class QuotesApiController
{
    private static function json(mixed $data, int $code=200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /api/quotes/random  → { sentence, author?, book? } */
    public static function random(): void
    {
        // Load quotes from a local JSON file, error if file doesn't exist
        $path = getenv('QUOTES_LOCAL_PATH') ?: '/app/database/quotes.json';
        if (!is_file($path)) {
            self::json(['error'=>'quotes_file_missing','path'=>$path], 500);
        }

        // Read and parse JSON data, error if file is empty
        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data) || empty($data)) {
            self::json(['error'=>'quotes_empty'], 500);
        }

        // Quote choice is deterministic, based on the date
        $startDate = new \DateTimeImmutable('2025-01-01');
        $today = new \DateTimeImmutable('today');
        $diffDays = (int)$startDate->diff($today)->days;

        $index = $diffDays % count($data);

        // Get the quote at given index
        $row = $data[$index];

        // If by any means the quote object doesn't have a sentence, return error
        if (empty($row['sentence'])) {
            self::json(['error'=>'invalid_quote_entry','index'=>$index], 500);
        }

        // Sends the quote data as JSON
        self::json([
            'sentence' => (string)$row['sentence'],
            'author'   => $row['author'] ?? null,
            'book'     => $row['book'] ?? null,
            'index'    => $index,
            'source'   => 'sequential-local'
        ]);
    }
}
