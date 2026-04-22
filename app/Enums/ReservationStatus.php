<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public static function blocking(): array
    {
        return [
            self::Pending->value,
            self::Confirmed->value,
        ];
    }
}
