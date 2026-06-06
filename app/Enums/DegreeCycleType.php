<?php

namespace App\Enums;

enum DegreeCycleType: string
{
    case LICENCE = 'LICENCE';
    case MASTER = 'MASTER';
    case DOCTORAT = 'DOCTORAT';
    case CLASSE_PREPARATOIRE = 'CLASSE_PREPARATOIRE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
