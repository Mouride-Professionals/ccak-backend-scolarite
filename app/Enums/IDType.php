<?php

namespace App\Enums;

enum IDType: string
{
    case PASSPORT = 'PASSPORT';
    case NATIONAL_ID = 'NATIONAL_ID';
    case DRIVING_LICENSE = 'DRIVING_LICENSE';
    case OTHER = 'OTHER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
