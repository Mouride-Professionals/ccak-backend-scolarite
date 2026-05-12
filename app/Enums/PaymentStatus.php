<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case PARTIALLY_PAID = 'PARTIALLY_PAID';
    case FULLY_PAID = 'FULLY_PAID';
    case OVERDUE = 'OVERDUE';
    case CANCELLED = 'CANCELLED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
