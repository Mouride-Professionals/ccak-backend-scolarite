<?php

namespace App\Enums;

enum StudentStatus: string
{
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case GRADUATED = 'GRADUATED';
    case WITHDRAWN = 'WITHDRAWN';
    case EXPELLED = 'EXPELLED';
    case CANCELLED = 'CANCELLED';
    case INACTIVE = 'INACTIVE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
