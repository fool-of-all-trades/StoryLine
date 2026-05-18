<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DateHelper;
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
            $this->json($this->safeQuoteDomainError($e), $this->quoteDomainStatus($e));
        } catch (Throwable $e) {
            error_log('[QuoteController] ensure_today_failed: ' . $e->getMessage());
            $this->json(['error'=>'internal_error'], 500);
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
            $date = DateHelper::normalizePublicYmd((string)$date);
            $quote = $this->quoteService->getByDate($date);
            if (!$quote) {
                $this->json(['error' => 'no_quote_for_date'], 404);
            }

            $this->json($quote->toArray());
        } catch (DomainException $e) {

            if ($e->getMessage() === 'invalid_date_format') {
                $this->json(['error' => 'invalid_date_format'], 400);
                return;
            }

            if ($e->getMessage() === 'date_out_of_range') {
                $this->json(['error' => 'date_out_of_range'], 400);
                return;
            }

            if ($e->getMessage() === 'no_quote_available') {
                $this->json(['error' => 'no_quote_for_date'], 404);
                return;
            }

            if ($e->getMessage() === 'quote_insert_failed') {
                error_log('[QuoteController] quote_by_date_insert_failed');
                $this->json(['error' => 'internal_error'], 500);
                return;
            }

            error_log('[QuoteController] quote_by_date_domain_error: ' . $e->getMessage());
            $this->json(['error' => 'internal_error'], 500);
        } catch (Throwable $e) {
            error_log('[QuoteController] quote_by_date_failed: ' . $e->getMessage());
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

            if ($e->getMessage() === 'date_out_of_range') {
                $this->json(['error' => 'date_out_of_range'], 400);
                return;
            }

            if ($e->getMessage() === 'no_quote_available') {
                $this->json(['error' => 'no_quote_for_date'], 404);
                return;
            }

            if ($e->getMessage() === 'quote_insert_failed') {
                error_log('[QuoteController] ensure_by_date_insert_failed');
                $this->json(['error' => 'internal_error'], 500);
                return;
            }

            error_log('[QuoteController] ensure_by_date_domain_error: ' . $e->getMessage());
            $this->json(['error' => 'internal_error'], 500);
        } catch (Throwable $e) {
            error_log('[QuoteController] ensure_by_date_failed: ' . $e->getMessage());
            $this->json(['error' => 'internal_error'], 500);
        }
    }

    private function safeQuoteDomainError(DomainException $e): array
    {
        return match ($e->getMessage()) {
            'invalid_date_format' => ['error' => 'invalid_date_format'],
            'date_out_of_range' => ['error' => 'date_out_of_range'],
            'no_quote_available' => ['error' => 'no_quote_for_date'],
            'quote_insert_failed' => ['error' => 'internal_error'],
            default => ['error' => 'internal_error'],
        };
    }

    private function quoteDomainStatus(DomainException $e): int
    {
        return match ($e->getMessage()) {
            'invalid_date_format' => 400,
            'date_out_of_range' => 400,
            'no_quote_available' => 404,
            default => 500,
        };
    }
}
