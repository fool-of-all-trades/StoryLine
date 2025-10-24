<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\QuoteService;
use DomainException;
use Throwable;
use App\Security\Csrf;

final class QuoteController
{
    private static function json(mixed $data, int $code=200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // GET /api/quote/today - get today's quote
    public static function today(): void
    {
        $quoteService = new QuoteService();
        $todayQuote = $quoteService->getToday();
        if (!$todayQuote) {
            self::json(['error'=>'no_quote_today'], 404);
        }
        self::json($todayQuote->toArray());
    }

    // POST /api/quote/ensure -> generate today's quote if it doesn't exist yet
    public static function ensureToday(): void
    {
        Csrf::verify();

        $quoteService = new QuoteService();
        try {
            $todayQuote = $quoteService->getOrEnsureToday();
            self::json($todayQuote->toArray(), 201);
        } catch (DomainException $e) {
            self::json(['error'=>$e->getMessage()], 400);
        } catch (Throwable $e) {
            self::json(['error'=>'internal_error','message'=>$e->getMessage()], 500);
        }
    }
}
