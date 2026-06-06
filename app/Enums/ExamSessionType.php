<?php

namespace App\Enums;

enum ExamSessionType: string
{
    case NORMAL = 'NORMAL';
    case RATTRAPAGE = 'RATTRAPAGE';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Session Normale',
            self::RATTRAPAGE => 'Session de Rattrapage',
        };
    }
}
