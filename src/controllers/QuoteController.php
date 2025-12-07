<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\QuoteService;
use DomainException;
use Throwable;
use App\Security\Csrf;

final class QuoteController
{

    private static function quoteService(): QuoteService
    {
        return new QuoteService();
    }

    private static function json(mixed $data, int $code=200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // GET /api/quote/today - get today's quote
    public static function today(): void
    {
        $quoteService = self::quoteService();
        $todayQuote = $quoteService->getToday();
        if (!$todayQuote) {
            self::json(['error'=>'no_quote_today'], 404);
        }
        self::json($todayQuote->toArray());
    }

    // POST /api/quote/today -> generate today's quote if it doesn't exist yet
    public static function ensureToday(): void
    {
        Csrf::verify();

        $quoteService = self::quoteService();
        try {
            $todayQuote = $quoteService->getOrEnsureToday();
            self::json($todayQuote->toArray(), 201);
        } catch (DomainException $e) {
            self::json(['error'=>$e->getMessage()], 400);
        } catch (Throwable $e) {
            self::json(['error'=>'internal_error','message'=>$e->getMessage()], 500);
        }
    }

    // GET /api/quote?date=YYYY-MM-DD - get quote for given date
    public static function byDate(): void
    {
        $date = $_GET['date'] ?? null;
        if ($date === null || $date === '') {
            self::json(['error' => 'missing_date'], 400);
        }

        $quoteService = self::quoteService();

        try {
            $quote = $quoteService->getByDate($date);

            self::json($quote->toArray());
        } catch (DomainException $e) {

            if ($e->getMessage() === 'invalid_date_format') {
                self::json(['error' => 'invalid_date_format'], 400);
                return;
            }

            if ($e->getMessage() === 'no_quote_available') {
                self::json(['error' => 'no_quote_for_date'], 404);
                return;
            }

            if ($e->getMessage() === 'quote_insert_failed') {
                self::json(['error' => 'quote_insert_failed'], 500);
                return;
            }

            self::json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            self::json(['error' => 'internal_error'], 500);
        }
    }

    // POST /api/quote?date=YYYY-MM-DD - ensure quote for given date
    public static function ensureByDate(): void
    {
        $date = $_GET['date'] ?? null;
        if ($date === null || $date === '') {
            self::json(['error' => 'missing_date'], 400);
        }

        $quoteService = self::quoteService();

        try {
            $quote = $quoteService->getOrEnsureForDate($date);

            self::json($quote->toArray());
        } catch (DomainException $e) {

            if ($e->getMessage() === 'invalid_date_format') {
                self::json(['error' => 'invalid_date_format'], 400);
                return;
            }

            if ($e->getMessage() === 'no_quote_available') {
                self::json(['error' => 'no_quote_for_date'], 404);
                return;
            }

            if ($e->getMessage() === 'quote_insert_failed') {
                self::json(['error' => 'quote_insert_failed'], 500);
                return;
            }

            self::json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            self::json(['error' => 'internal_error'], 500);
        }
    }
}
