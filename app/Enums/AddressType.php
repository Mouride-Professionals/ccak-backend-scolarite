<?php

namespace App\Enums;

enum AddressType: string
{
    case HOME = 'HOME';
    case UNIVERSITY_CITY = 'UNIVERSITY_CITY';
    case WORK = 'WORK';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
