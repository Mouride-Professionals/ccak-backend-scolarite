<?php

namespace App\Enums;

enum Gender: string
{
    case M = 'M';
    case F = 'F';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
