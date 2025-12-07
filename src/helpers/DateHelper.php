<?php
declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;
use DomainException;

final class DateHelper
{
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
}
