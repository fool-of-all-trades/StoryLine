<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\QuoteService;
use DomainException;
use Throwable;
use App\Security\Csrf;

class QuoteController extends BaseController
{
    private QuoteService $quoteService;

    public function __construct() {
        $this->quoteService = new QuoteService();
    }

    // GET /api/quote/today - get today's quote
    public function today(): void
    {
        $todayQuote = $this->quoteService->getToday();
        if (!$todayQuote) {
            $this->json(['error'=>'no_quote_today'], 404);
        }
        $this->json($todayQuote->toArray());
    }

    // POST /api/quote/today -> generate today's quote if it doesn't exist yet
    public function ensureToday(): void
    {
        Csrf::verify();

        try {
            $todayQuote = $this->quoteService->getOrEnsureToday();
            $this->json($todayQuote->toArray(), 201);
        } catch (DomainException $e) {
            $this->json(['error'=>$e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->json(['error'=>'internal_error','message'=>$e->getMessage()], 500);
        }
    }

    // GET /api/quote?date=YYYY-MM-DD - get quote for given date
    public function byDate(): void
    {
        $date = $_GET['date'] ?? null;
        if ($date === null || $date === '') {
            $this->json(['error' => 'missing_date'], 400);
        }

        try {
            $quote = $this->quoteService->getByDate($date);

            $this->json($quote->toArray());
        } catch (DomainException $e) {

            if ($e->getMessage() === 'invalid_date_format') {
                $this->json(['error' => 'invalid_date_format'], 400);
                return;
            }

            if ($e->getMessage() === 'no_quote_available') {
                $this->json(['error' => 'no_quote_for_date'], 404);
                return;
            }

            if ($e->getMessage() === 'quote_insert_failed') {
                $this->json(['error' => 'quote_insert_failed'], 500);
                return;
            }

            $this->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->json(['error' => 'internal_error'], 500);
        }
    }

    // POST /api/quote?date=YYYY-MM-DD - ensure quote for given date
    public function ensureByDate(): void
    {
        $date = $_GET['date'] ?? null;
        if ($date === null || $date === '') {
            $this->json(['error' => 'missing_date'], 400);
        }

        try {
            $quote = $this->quoteService->getOrEnsureForDate($date);

            $this->json($quote->toArray());
        } catch (DomainException $e) {

            if ($e->getMessage() === 'invalid_date_format') {
                $this->json(['error' => 'invalid_date_format'], 400);
                return;
            }

            if ($e->getMessage() === 'no_quote_available') {
                $this->json(['error' => 'no_quote_for_date'], 404);
                return;
            }

            if ($e->getMessage() === 'quote_insert_failed') {
                $this->json(['error' => 'quote_insert_failed'], 500);
                return;
            }

            $this->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            $this->json(['error' => 'internal_error'], 500);
        }
    }
}
