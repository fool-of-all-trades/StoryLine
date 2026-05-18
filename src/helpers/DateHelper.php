<?php
declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;
use DomainException;

final class DateHelper
{
    private const DEFAULT_PUBLIC_START_DATE = '2026-05-15';

    /**
     * normalizes various date inputs into Y-m-d format
     * for admin users, future dates are allowed
     * for non-admin users, future dates are not allowed
     */
    public static function normalizeYmd(string $input, bool $allowFutureForNonAdmin = false): string
    {
        $input = trim($input);

        if ($input === '' || $input === 'today') {
            $dt = new DateTimeImmutable('today');
        } elseif ($input === 'yesterday') {
            $dt = new DateTimeImmutable('yesterday');
        } else {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $input);

            $today = new DateTimeImmutable('today');

            if (!$allowFutureForNonAdmin && !is_admin() && $dt > $today) {
                // no peeking into the future, unless you're me, I can, cuz I'm a witch
                $dt = $today;
            }

            $errors = DateTimeImmutable::getLastErrors();
            if (!$dt || ($errors['warning_count'] ?? 0) || ($errors['error_count'] ?? 0)) {
                error_log('[DateHelper] invalid_date_format input=' . $input);
                throw new DomainException('invalid_date_format');
            }
        }

        return $dt->format('Y-m-d');
    }

    public static function publicStartYmd(): string
    {
        $configured = trim((string)(getenv('PUBLIC_LAUNCH_DATE') ?: self::DEFAULT_PUBLIC_START_DATE));

        try {
            return self::parseExactYmd($configured)->format('Y-m-d');
        } catch (DomainException $e) {
            error_log('[DateHelper] invalid_public_launch_date_config');
            return self::DEFAULT_PUBLIC_START_DATE;
        }
    }

    public static function normalizePublicYmd(string $input): string
    {
        $input = trim($input);

        if ($input === '' || $input === 'today') {
            $dt = new DateTimeImmutable('today');
        } elseif ($input === 'yesterday') {
            $dt = new DateTimeImmutable('yesterday');
        } else {
            $dt = self::parseExactYmd($input);
        }

        $start = new DateTimeImmutable(self::publicStartYmd());
        $today = new DateTimeImmutable('today');

        if ($dt < $start || $dt > $today) {
            throw new DomainException('date_out_of_range');
        }

        return $dt->format('Y-m-d');
    }

    private static function parseExactYmd(string $input): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $input);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors)
            && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);

        if (!$dt || $hasErrors || $dt->format('Y-m-d') !== $input) {
            throw new DomainException('invalid_date_format');
        }

        return $dt;
    }
}
