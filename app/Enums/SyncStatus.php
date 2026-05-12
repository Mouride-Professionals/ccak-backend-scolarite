<?php

namespace App\Enums;

enum SyncStatus: string
{
    case SUCCESS = 'SUCCESS';
    case PARTIAL = 'PARTIAL';
    case FAILED = 'FAILED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
