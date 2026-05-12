<?php

namespace App\Enums;

enum AcademicYearStatus: string
{
    case OPEN = 'O';
    case CLOSED = 'F';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
