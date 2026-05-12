<?php

namespace App\Enums;

enum Provenance: string
{
    case ETAT = 'ETAT';
    case PLATEFORME = 'PLATEFORME';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
