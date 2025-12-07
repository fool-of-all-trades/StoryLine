<?php
declare(strict_types=1);

namespace App\Models;

enum Role: string {
    case User = 'user';
    case Admin = 'admin';

    public static function fromString(string $v): self {
        return match ($v) {
            'admin' => self::Admin,
            default => self::User,
        };
    }
}
