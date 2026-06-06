<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_VALIDATION = 'PENDING_VALIDATION';
    case VALIDATED = 'VALIDATED';
    case SUSPENDED = 'SUSPENDED';
    case CANCELLED = 'CANCELLED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
